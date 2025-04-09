<?php
/**
 * NotificationController - Controlador de notificações para usuários
 * 
 * Gerencia a listagem, filtragem e marcação de notificações para usuários finais,
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Controllers
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */

require_once __DIR__ . '/../lib/Controller.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Security/CsrfProtection.php';
require_once __DIR__ . '/../lib/Security/SecurityHeaders.php';
require_once __DIR__ . '/../lib/Notification/NotificationManager.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../models/NotificationPreferenceModel.php';

class NotificationController extends Controller {
    use InputValidationTrait;
    
    /**
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * @var NotificationModel
     */
    private $notificationModel;
    
    /**
     * @var NotificationPreferenceModel
     */
    private $preferenceModel;
    
    /**
     * @var \PDO
     */
    private $pdo;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Inicializar conexão com banco de dados
        $this->pdo = Database::getConnection();
        
        // Inicializar modelos
        $this->notificationManager = NotificationManager::getInstance();
        $this->notificationModel = new NotificationModel();
        $this->preferenceModel = new NotificationPreferenceModel();
        
        // Verificar autenticação para todos os métodos
        $this->requireAuthentication();
    }
    
    /**
     * Página de listagem de notificações
     */
    public function index() {
        // Aplicar cabeçalhos de segurança
        SecurityHeaders::apply();
        
        // Obter ID do usuário atual
        $userId = $_SESSION['user_id'];
        
        // Validar parâmetros de paginação
        $page = $this->getValidatedParam('page', 'int', [
            'default' => 1,
            'min' => 1
        ]);
        
        $limit = $this->getValidatedParam('limit', 'int', [
            'default' => 10,
            'min' => 1,
            'max' => 50
        ]);
        
        // Validar parâmetros de filtro
        $filterType = $this->getValidatedParam('type', 'string', [
            'allowedValues' => ['unread', 'process_status', 'process_results', 'process_completed', 'process_failed']
        ]);
        
        // Construir filtro para consulta
        $filter = [];
        if ($filterType === 'unread') {
            $filter['status'] = 'unread';
        } elseif (!empty($filterType)) {
            $filter['types'] = [$filterType];
        }
        
        // Adicionar tipos relacionados a processos assíncronos por padrão
        if (empty($filter['types'])) {
            $filter['types'] = [
                'process_status', 'process_progress', 'process_completed', 
                'process_failed', 'process_results', 'process_expiration'
            ];
        }
        
        // Calcular offset para paginação
        $offset = ($page - 1) * $limit;
        
        // Obter notificações
        $notifications = $this->notificationManager->getUserNotifications(
            $userId,
            array_merge($filter, [
                'limit' => $limit,
                'offset' => $offset
            ])
        );
        
        // Obter contagem total para paginação
        $totalCount = $this->notificationManager->countUserNotifications(
            $userId,
            $filter
        );
        
        // Verificar se existem notificações não lidas
        $unreadCount = $this->notificationManager->countUnreadNotifications($userId);
        $unreadNotifications = $unreadCount > 0;
        
        // Calcular informações de paginação
        $totalPages = ceil($totalCount / $limit);
        $currentPage = $page;
        
        // Construir string de consulta para links de paginação
        $filterQuery = !empty($filterType) ? '&type=' . urlencode($filterType) : '';
        
        // Renderizar view
        $data = [
            'notifications' => $notifications,
            'totalCount' => $totalCount,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'filterType' => $filterType,
            'filterQuery' => $filterQuery,
            'unreadNotifications' => $unreadNotifications
        ];
        
        // Incluir a view diretamente
        include(VIEWS_PATH . '/user_account/notifications.php');
    }
    
    /**
     * Página de preferências de notificações
     */
    public function preferences() {
        // Aplicar cabeçalhos de segurança
        SecurityHeaders::apply();
        
        // Obter ID do usuário atual
        $userId = $_SESSION['user_id'];
        
        // Obter tipos de notificação disponíveis
        $notificationTypes = $this->notificationModel->getNotificationTypes([
            'category' => 'async_process'
        ]);
        
        // Obter preferências atuais do usuário
        $userPreferences = $this->preferenceModel->getUserNotificationPreferences($userId);
        
        // Organizar preferências por tipo para fácil acesso
        $preferencesByType = [];
        foreach ($userPreferences as $pref) {
            $preferencesByType[$pref['notification_type']] = $pref;
        }
        
        // Verificar se houve submissão de formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processPreferencesForm($userId);
        }
        
        // Renderizar view
        $data = [
            'notificationTypes' => $notificationTypes,
            'userPreferences' => $preferencesByType
        ];
        
        // Incluir a view diretamente
        include(VIEWS_PATH . '/user_account/notification_preferences.php');
    }
    
    /**
     * Processa submissão de formulário de preferências
     * 
     * @param int $userId ID do usuário
     */
    private function processPreferencesForm($userId) {
        // Verificar CSRF token
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            $this->setFlashMessage('error', 'Erro de validação do formulário. Tente novamente.');
            return;
        }
        
        // Obter preferências enviadas
        $formPreferences = $_POST['preferences'] ?? [];
        
        if (!is_array($formPreferences)) {
            $this->setFlashMessage('error', 'Dados de preferências inválidos.');
            return;
        }
        
        // Array para armazenar preferências sanitizadas
        $sanitizedPreferences = [];
        
        // Sanitizar e validar cada preferência
        foreach ($formPreferences as $type => $channels) {
            // Validar tipo de notificação
            $type = $this->validateString($type, [
                'pattern' => '/^[a-z_]+$/',
                'maxLength' => 50
            ]);
            
            if ($type === null) {
                continue;
            }
            
            // Valores padrão
            $isEnabled = false;
            $emailEnabled = false;
            $pushEnabled = false;
            
            // Verificar canais habilitados
            if (is_array($channels)) {
                $isEnabled = true; // Se pelo menos um canal for selecionado
                $emailEnabled = isset($channels['email']);
                $pushEnabled = isset($channels['push']);
            }
            
            // Adicionar à lista de preferências sanitizadas
            $sanitizedPreferences[$type] = [
                'is_enabled' => $isEnabled,
                'email_enabled' => $emailEnabled,
                'push_enabled' => $pushEnabled
            ];
        }
        
        // Atualizar preferências no banco de dados
        $success = $this->preferenceModel->updateUserNotificationPreferences($userId, $sanitizedPreferences);
        
        if ($success) {
            $this->setFlashMessage('success', 'Preferências de notificação atualizadas com sucesso.');
        } else {
            $this->setFlashMessage('error', 'Ocorreu um erro ao atualizar suas preferências. Tente novamente.');
        }
    }
    
    /**
     * Verifica autenticação e redireciona se não estiver autenticado
     */
    private function requireAuthentication() {
        if (!SecurityManager::checkAuthentication()) {
            // Redirecionar para login com URL de retorno
            $returnUrl = urlencode($_SERVER['REQUEST_URI']);
            header("Location: /login?return={$returnUrl}");
            exit;
        }
    }
    
    /**
     * Define uma mensagem flash para exibição na próxima requisição
     * 
     * @param string $type Tipo da mensagem (success, error, info, warning)
     * @param string $message Texto da mensagem
     */
    private function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}
