<?php
/**
 * AsyncNotificationPreferenceController - Gerenciamento de preferências de notificação para processos assíncronos
 * 
 * @package App\Controllers
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */

namespace App\Controllers;

use App\Lib\Controller;
use App\Lib\Security\InputValidationTrait;
use App\Lib\Security\SecurityManager;
use App\Lib\Security\CsrfProtection;
use App\Models\NotificationPreferenceModel;

class AsyncNotificationPreferenceController extends Controller {
    use InputValidationTrait;
    
    /**
     * @var NotificationPreferenceModel
     */
    private $preferenceModel;
    
    /**
     * @var array
     */
    private $asyncNotificationTypes = [
        'process_status',
        'process_progress',
        'process_completed',
        'process_failed',
        'process_results',
        'process_expiration'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Verificar autenticação para todo o controlador
        if (!SecurityManager::checkAuthentication()) {
            $this->redirect('/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
        
        $this->preferenceModel = new NotificationPreferenceModel();
    }
    
    /**
     * Mostra página de preferências de notificação
     * 
     * @return void
     */
    public function index() {
        // Definir constante para segurança da view
        define('SECURE_ACCESS', true);
        
        // Obter ID do usuário autenticado
        $userId = $_SESSION['user_id'];
        
        // Obter token CSRF para o formulário
        $csrfToken = CsrfProtection::getToken();
        
        // Obter tipos de notificação assíncrona disponíveis
        $notificationTypes = $this->getAsyncNotificationTypes();
        
        // Obter preferências atuais do usuário
        $preferences = $this->preferenceModel->getUserAsyncPreferences($userId);
        
        // Renderizar view
        $this->render('account/async_notification_preferences', [
            'notificationTypes' => $notificationTypes,
            'preferences' => $preferences,
            'csrfToken' => $csrfToken,
            'pageTitle' => 'Preferências de Notificação - Processos'
        ]);
    }
    
    /**
     * Processa submissão do formulário de preferências
     * 
     * @return void
     */
    public function save() {
        // Definir constante para segurança da view
        define('SECURE_ACCESS', true);
        
        // Obter ID do usuário autenticado
        $userId = $_SESSION['user_id'];
        
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if ($csrfToken === null || !CsrfProtection::validateToken($csrfToken)) {
            // Token CSRF inválido, recarregar página com erro
            $this->redirect('/conta/notification-preferences/async?error=csrf');
            return;
        }
        
        // Obter preferências enviadas
        $submittedPreferences = $this->postValidatedParam('preferences', 'array', [
            'default' => []
        ]);
        
        try {
            // Validar e normalizar preferências
            $validatedPreferences = $this->validatePreferences($submittedPreferences);
            
            // Verificar se alguma preferência foi enviada
            if (empty($validatedPreferences)) {
                throw new \Exception('Nenhuma preferência válida recebida.');
            }
            
            // Salvar preferências
            $result = $this->preferenceModel->updateAsyncPreferences($userId, $validatedPreferences);
            
            if ($result) {
                // Sucesso, redirecionar com mensagem
                $this->redirect('/conta/notification-preferences/async?success=1');
            } else {
                // Erro ao salvar, recarregar página com erro
                $this->redirect('/conta/notification-preferences/async?error=save');
            }
        } catch (\Exception $e) {
            // Log do erro real para debugging interno
            error_log('Erro ao salvar preferências de notificação: ' . $e->getMessage());
            
            // Recarregar página com erro genérico
            $this->redirect('/conta/notification-preferences/async?error=1');
        }
    }
    
    /**
     * Valida e normaliza as preferências submetidas
     * 
     * @param array $preferences Preferências brutas submetidas pelo formulário
     * @return array Preferências validadas e normalizadas
     */
    private function validatePreferences(array $preferences) {
        $validatedPreferences = [];
        
        // Obter tipos de notificação disponíveis
        $validTypes = $this->getAsyncNotificationTypes();
        $validTypeCodes = array_column($validTypes, 'code');
        
        // Processar cada preferência
        foreach ($preferences as $typeCode => $settings) {
            // Verificar se é um tipo válido
            if (!in_array($typeCode, $validTypeCodes)) {
                continue;
            }
            
            // Verificar se o tipo é crítico (não pode ser desativado)
            $isCritical = false;
            foreach ($validTypes as $type) {
                if ($type['code'] === $typeCode && isset($type['is_critical']) && $type['is_critical']) {
                    $isCritical = true;
                    break;
                }
            }
            
            // Para tipos críticos, forçar is_enabled = true
            $isEnabled = $isCritical ? true : (isset($settings['is_enabled']) && $settings['is_enabled'] == '1');
            
            // Se desabilitado, definir todos os canais como desabilitados também
            $emailEnabled = $isEnabled && (isset($settings['email_enabled']) && $settings['email_enabled'] == '1');
            $pushEnabled = $isEnabled && (isset($settings['push_enabled']) && $settings['push_enabled'] == '1');
            
            // Adicionar à lista de preferências validadas
            $validatedPreferences[$typeCode] = [
                'is_enabled' => $isEnabled,
                'email_enabled' => $emailEnabled,
                'push_enabled' => $pushEnabled
            ];
        }
        
        return $validatedPreferences;
    }
    
    /**
     * Obtém os tipos de notificação para processos assíncronos
     * 
     * @return array Array de tipos de notificação
     */
    private function getAsyncNotificationTypes() {
        // Em um cenário real, isso viria do banco de dados
        // Por simplicidade, estamos definindo estaticamente aqui
        return [
            [
                'code' => 'process_status',
                'name' => 'Mudança de Status de Processo',
                'description' => 'Notificações sobre mudanças no status de seus processos',
                'category' => 'async_process',
                'is_critical' => false
            ],
            [
                'code' => 'process_progress',
                'name' => 'Progresso de Processo',
                'description' => 'Atualizações sobre o progresso de processos em andamento',
                'category' => 'async_process',
                'is_critical' => false
            ],
            [
                'code' => 'process_completed',
                'name' => 'Conclusão de Processo',
                'description' => 'Notificações quando seus processos são concluídos com sucesso',
                'category' => 'async_process',
                'is_critical' => true
            ],
            [
                'code' => 'process_failed',
                'name' => 'Falha em Processo',
                'description' => 'Alertas quando ocorrem falhas em seus processos',
                'category' => 'async_process',
                'is_critical' => true
            ],
            [
                'code' => 'process_results',
                'name' => 'Resultados Disponíveis',
                'description' => 'Notificações quando os resultados de seus processos estão disponíveis',
                'category' => 'async_process',
                'is_critical' => true
            ],
            [
                'code' => 'process_expiration',
                'name' => 'Expiração de Processo',
                'description' => 'Avisos sobre processos que estão prestes a expirar',
                'category' => 'async_process',
                'is_critical' => false
            ]
        ];
    }
}
