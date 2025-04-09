<?php
/**
 * StatusCheckApiController - API para verificação de status de processamento assíncrono
 * 
 * @package App\Controllers\Api
 * @category Security
 * @author Taverna da Impressão 3D Dev Team
 */

namespace App\Controllers\Api;

use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;
use App\Models\AsyncProcess\StatusRepository;
use App\Lib\Security\RateLimiter;
use App\Lib\Http\ApiResponse;

class StatusCheckApiController
{
    use InputValidationTrait;
    
    /**
     * @var StatusRepository
     */
    private $statusRepository;
    
    /**
     * @var SecurityManager
     */
    private $securityManager;
    
    /**
     * @var RateLimiter
     */
    private $rateLimiter;
    
    /**
     * Constructor
     * 
     * @param StatusRepository $statusRepository Repositório de status
     * @param SecurityManager $securityManager Gerenciador de segurança
     * @param RateLimiter $rateLimiter Limitador de taxa
     */
    public function __construct(
        StatusRepository $statusRepository, 
        SecurityManager $securityManager,
        RateLimiter $rateLimiter
    ) {
        $this->statusRepository = $statusRepository;
        $this->securityManager = $securityManager;
        $this->rateLimiter = $rateLimiter;
    }
    
    /**
     * Verifica o status de um processo assíncrono
     * 
     * @return void
     */
    public function checkStatus()
    {
        // Aplicar rate limiting para evitar sobrecarga
        if (!$this->rateLimiter->check('status_check_api', 60, 10)) {
            ApiResponse::error('Limite de solicitações excedido. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Validar token CSRF para requisições POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $this->validateInput('csrf_token', 'string', ['required' => true]);
            if ($csrfToken === null || !$this->securityManager->validateCsrfToken($csrfToken)) {
                ApiResponse::error('Erro de validação CSRF', 403);
                return;
            }
        }
        
        // Validar e sanitizar o token de processo
        $processToken = $this->validateInput('process_token', 'string', [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9]{32}$/' // Formato esperado: 32 caracteres alfanuméricos
        ]);
        
        if ($processToken === null) {
            ApiResponse::error('Token de processo inválido', 400);
            return;
        }
        
        // Verificar permissões do usuário
        $userId = $this->securityManager->getCurrentUserId();
        if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
            ApiResponse::error('Acesso não autorizado ao processo', 403);
            return;
        }
        
        try {
            // Obter status do processo
            $processStatus = $this->statusRepository->getProcessStatus($processToken);
            
            if ($processStatus === null) {
                ApiResponse::error('Processo não encontrado', 404);
                return;
            }
            
            // Sanitizar dados de saída
            $safeStatus = [];
            foreach ($processStatus as $key => $value) {
                // Não incluir dados sensíveis
                if (!in_array($key, ['internal_log', 'debug_info', 'raw_data'])) {
                    $safeStatus[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }
            }
            
            // Adicionar detalhes adicionais para processos concluídos
            if ($safeStatus['status'] === 'completed') {
                $safeStatus['download_url'] = $this->securityManager->generateSignedUrl(
                    'download', 
                    ['token' => $processToken]
                );
            }
            
            // Retornar resposta com cabeçalhos de segurança
            ApiResponse::success($safeStatus, 200);
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log("Erro ao verificar status: " . $e->getMessage());
            
            // Mensagem genérica para o usuário
            ApiResponse::error('Ocorreu um erro ao processar sua solicitação', 500);
        }
    }
}
