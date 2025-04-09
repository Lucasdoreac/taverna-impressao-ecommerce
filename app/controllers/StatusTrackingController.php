<?php
/**
 * StatusTrackingController - Controlador para a interface de acompanhamento de cotações
 * 
 * @package App\Controllers
 * @category Frontend
 * @author Taverna da Impressão 3D Dev Team
 */

namespace App\Controllers;

use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;
use App\Models\AsyncProcess\StatusRepository;
use App\Lib\Security\SecurityHeaders;
use App\Lib\Notification\AsyncProcessNotificationHandler;
use App\Lib\Notification\NotificationManager;

class StatusTrackingController
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
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * @var AsyncProcessNotificationHandler
     */
    private $notificationHandler;
    
    /**
     * Constructor
     * 
     * @param StatusRepository $statusRepository Repositório de status
     * @param SecurityManager $securityManager Gerenciador de segurança
     * @param NotificationManager $notificationManager Gerenciador de notificações
     */
    public function __construct(
        StatusRepository $statusRepository, 
        SecurityManager $securityManager,
        NotificationManager $notificationManager = null
    ) {
        $this->statusRepository = $statusRepository;
        $this->securityManager = $securityManager;
        $this->notificationManager = $notificationManager ?? new NotificationManager();
        
        // Inicializar manipulador de notificações
        $this->notificationHandler = new AsyncProcessNotificationHandler(
            $this->notificationManager,
            $this->statusRepository
        );
    }
    
    /**
     * Apresenta a página de acompanhamento de cotação
     * 
     * @return void
     */
    public function showTrackingPage()
    {
        // Aplicar cabeçalhos de segurança
        SecurityHeaders::apply();
        
        // Validar token de processo na URL
        $processToken = $this->validateInput('token', 'string', [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        if ($processToken === null) {
            // Redirecionar para página de erro caso token seja inválido
            header('Location: /error/invalid-token');
            exit;
        }
        
        // Verificar se o processo existe
        $processInfo = $this->statusRepository->getProcessStatus($processToken);
        if ($processInfo === null) {
            // Redirecionar para página de erro caso processo não exista
            header('Location: /error/not-found');
            exit;
        }
        
        // Verificar permissões do usuário
        $userId = $this->securityManager->getCurrentUserId();
        if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
            // Redirecionar para página de erro caso usuário não tenha permissão
            header('Location: /error/unauthorized');
            exit;
        }
        
        // Obter ID da cotação para exibição
        $quoteId = $processInfo['id'] ?? 'N/A';
        
        // Verificar se deve marcar notificações como lidas para este processo
        $markRead = $this->validateInput('mark_read', 'int', [
            'default' => 0
        ]);
        
        if ($markRead === 1 && $userId > 0) {
            $this->markProcessNotificationsAsRead($processToken, $userId);
        }
        
        // Obter token CSRF para a página
        $csrfToken = SecurityManager::getCsrfToken();
        
        // Obter notificações recentes para este processo
        $recentNotifications = [];
        if ($userId > 0) {
            $recentNotifications = $this->getRecentProcessNotifications($processToken, $userId);
        }
        
        // Incluir a view, passando os dados necessários
        include __DIR__ . '/../Views/status_tracking.php';
    }
    
    /**
     * Processa uma mudança de status e gera notificação
     * 
     * @param string $processToken Token do processo
     * @param string $newStatus Novo status
     * @param string $oldStatus Antigo status
     * @param array $context Contexto adicional
     * @return bool Sucesso da operação
     */
    public function handleStatusChange($processToken, $newStatus, $oldStatus, array $context = [])
    {
        try {
            // Validar parâmetros
            $processToken = $this->validateString($processToken, [
                'required' => true,
                'pattern' => '/^[a-zA-Z0-9]{32}$/'
            ]);
            
            $newStatus = $this->validateString($newStatus, [
                'required' => true,
                'maxLength' => 50
            ]);
            
            $oldStatus = $this->validateString($oldStatus, [
                'required' => true,
                'maxLength' => 50
            ]);
            
            if ($processToken === null || $newStatus === null || $oldStatus === null) {
                return false;
            }
            
            // Obter informações do processo
            $process = $this->statusRepository->getProcessStatus($processToken);
            if (!$process) {
                return false;
            }
            
            $userId = $process['user_id'] ?? 0;
            if ($userId <= 0) {
                return false;
            }
            
            // Atualizar status no repositório
            $updateResult = $this->statusRepository->updateProcessStatus(
                $processToken,
                $newStatus,
                array_merge($context, ['previous_status' => $oldStatus])
            );
            
            if (!$updateResult) {
                return false;
            }
            
            // Enviar notificação através do handler
            $notifyResult = $this->notificationHandler->handleStatusChange(
                $processToken,
                $oldStatus,
                $newStatus,
                $userId,
                $context
            );
            
            return $notifyResult;
        } catch (\Exception $e) {
            error_log('Erro ao processar mudança de status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o progresso de um processo e gera notificação se necessário
     * 
     * @param string $processToken Token do processo
     * @param int $percentComplete Percentual de conclusão (0-100)
     * @param array $context Contexto adicional
     * @return bool Sucesso da operação
     */
    public function updateProgress($processToken, $percentComplete, array $context = [])
    {
        try {
            // Validar parâmetros
            $processToken = $this->validateString($processToken, [
                'required' => true,
                'pattern' => '/^[a-zA-Z0-9]{32}$/'
            ]);
            
            $percentComplete = intval($percentComplete);
            if ($percentComplete < 0 || $percentComplete > 100) {
                $percentComplete = max(0, min(100, $percentComplete));
            }
            
            if ($processToken === null) {
                return false;
            }
            
            // Obter informações do processo
            $process = $this->statusRepository->getProcessStatus($processToken);
            if (!$process) {
                return false;
            }
            
            $userId = $process['user_id'] ?? 0;
            if ($userId <= 0) {
                return false;
            }
            
            // Atualizar progresso no repositório
            $updateResult = $this->statusRepository->updateProcessProgress(
                $processToken,
                $percentComplete,
                $context
            );
            
            if (!$updateResult) {
                return false;
            }
            
            // Enviar notificação através do handler
            $notifyResult = $this->notificationHandler->handleProgressUpdate(
                $processToken,
                $percentComplete,
                $userId,
                $context
            );
            
            return $notifyResult;
        } catch (\Exception $e) {
            error_log('Erro ao atualizar progresso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca notificações para um processo específico como lidas
     * 
     * @param string $processToken Token do processo
     * @param int $userId ID do usuário
     * @return bool Sucesso da operação
     */
    private function markProcessNotificationsAsRead($processToken, $userId)
    {
        try {
            return $this->notificationManager->markAllAsRead($userId, [
                'types' => [
                    'process_status', 'process_progress', 'process_completed', 
                    'process_failed', 'process_results', 'process_expiration'
                ],
                'process_token' => $processToken
            ]);
        } catch (\Exception $e) {
            error_log('Erro ao marcar notificações como lidas: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém notificações recentes para um processo específico
     * 
     * @param string $processToken Token do processo
     * @param int $userId ID do usuário
     * @param int $limit Limite de notificações
     * @return array Notificações recentes
     */
    private function getRecentProcessNotifications($processToken, $userId, $limit = 5)
    {
        try {
            return $this->notificationManager->getUserNotifications(
                $userId,
                [
                    'types' => [
                        'process_status', 'process_progress', 'process_completed', 
                        'process_failed', 'process_results', 'process_expiration'
                    ],
                    'process_token' => $processToken,
                    'limit' => $limit,
                    'offset' => 0
                ]
            );
        } catch (\Exception $e) {
            error_log('Erro ao obter notificações recentes: ' . $e->getMessage());
            return [];
        }
    }
}
