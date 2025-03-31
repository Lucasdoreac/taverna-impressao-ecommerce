<?php
/**
 * NotificationPreferenceController - Controlador para gerenciamento de preferências de notificação
 * 
 * Este controlador gerencia as preferências de notificação dos usuários,
 * permitindo a personalização dos tipos de notificações, canais de entrega e frequência.
 * 
 * @version 1.0.0
 * @author Taverna da Impressão
 */
class NotificationPreferenceController extends Controller {
    private $notificationPreferenceModel;
    private $notificationModel;
    private $authHelper;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Inicializar modelos e helpers
        $this->notificationPreferenceModel = new NotificationPreferenceModel();
        $this->notificationModel = new NotificationModel();
        $this->authHelper = new AuthHelper();
        
        // Verificar autenticação para todas as ações
        $this->checkAuthentication();
        
        // Verificar permissões de admin para ações administrativas
        $adminActions = ['admin', 'adminSave', 'metrics'];
        if (in_array($this->getCurrentAction(), $adminActions)) {
            $this->checkAdminPermission();
        }
    }
    
    /**
     * Exibe a página de preferências de notificação para o usuário
     */
    public function index() {
        // Obter ID do usuário atual
        $userId = $_SESSION['user']['id'];
        
        // Verificar se o usuário já tem preferências inicializadas
        if (!$this->notificationPreferenceModel->hasUserPreferences($userId)) {
            // Inicializar preferências padrão
            $this->notificationPreferenceModel->initializeDefaultPreferences($userId);
        }
        
        // Obter preferências do usuário
        $preferences = $this->notificationPreferenceModel->getUserPreferences($userId);
        
        // Obter todos os tipos de notificação e canais disponíveis
        $notificationTypes = $this->notificationPreferenceModel->getAllNotificationTypes();
        $notificationChannels = $this->notificationPreferenceModel->getAllNotificationChannels();
        
        // Renderizar a view
        $this->view('account/notification_preferences', [
            'preferences' => $preferences,
            'notificationTypes' => $notificationTypes,
            'notificationChannels' => $notificationChannels,
            'title' => 'Preferências de Notificação'
        ]);
    }
    
    /**
     * Processa o salvamento das preferências de notificação do usuário
     */
    public function save() {
        // Verificar método da requisição
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'preferencias-notificacao');
            exit;
        }
        
        // Obter ID do usuário atual
        $userId = $_SESSION['user']['id'];
        
        // Processar os dados do formulário
        $preferences = [];
        
        if (isset($_POST['preferences']) && is_array($_POST['preferences'])) {
            foreach ($_POST['preferences'] as $typeId => $channels) {
                foreach ($channels as $channelId => $settings) {
                    $isEnabled = isset($settings['enabled']) ? true : false;
                    $frequency = isset($settings['frequency']) ? $settings['frequency'] : 'realtime';
                    
                    $preferences[] = [
                        'type_id' => (int)$typeId,
                        'channel_id' => (int)$channelId,
                        'is_enabled' => $isEnabled,
                        'frequency' => $frequency
                    ];
                }
            }
        }
        
        // Salvar preferências
        $result = $this->notificationPreferenceModel->updateMultiplePreferences($userId, $preferences);
        
        // Verificar se a requisição é AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Responder com JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool)$result,
                'message' => $result ? 'Preferências atualizadas com sucesso!' : 'Erro ao atualizar preferências. Por favor, tente novamente.'
            ]);
            exit;
        } else {
            // Responder com redirecionamento
            if ($result) {
                $_SESSION['success'] = 'Preferências de notificação atualizadas com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao atualizar preferências. Por favor, tente novamente.';
            }
            
            header('Location: ' . BASE_URL . 'preferencias-notificacao');
            exit;
        }
    }
    
    /**
     * Atualiza uma preferência específica via AJAX
     */
    public function update() {
        // Verificar método da requisição e se é AJAX
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
            !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
            
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Método inválido ou requisição não AJAX']);
            exit;
        }
        
        // Obter ID do usuário atual
        $userId = $_SESSION['user']['id'];
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['typeId']) || !isset($data['channelId']) || !isset($data['isEnabled'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit;
        }
        
        $typeId = (int)$data['typeId'];
        $channelId = (int)$data['channelId'];
        $isEnabled = (bool)$data['isEnabled'];
        $frequency = isset($data['frequency']) ? $data['frequency'] : 'realtime';
        
        // Atualizar preferência
        $result = $this->notificationPreferenceModel->updatePreference(
            $userId,
            $typeId,
            $channelId,
            $isEnabled,
            $frequency
        );
        
        // Responder com JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => (bool)$result,
            'message' => $result ? 'Preferência atualizada com sucesso!' : 'Erro ao atualizar preferência.'
        ]);
        exit;
    }
    
    /**
     * Inicializa preferências padrão para o usuário atual
     */
    public function initialize() {
        // Obter ID do usuário atual
        $userId = $_SESSION['user']['id'];
        
        // Inicializar preferências padrão
        $result = $this->notificationPreferenceModel->initializeDefaultPreferences($userId);
        
        // Verificar se a requisição é AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Responder com JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool)$result,
                'message' => $result ? 'Preferências inicializadas com sucesso!' : 'Erro ao inicializar preferências.'
            ]);
            exit;
        } else {
            // Responder com redirecionamento
            if ($result) {
                $_SESSION['success'] = 'Preferências de notificação inicializadas com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao inicializar preferências. Por favor, tente novamente.';
            }
            
            header('Location: ' . BASE_URL . 'preferencias-notificacao');
            exit;
        }
    }
    
    /**
     * Exibe a página de administração de preferências de notificação
     */
    public function admin() {
        // Obter todos os tipos de notificação e canais disponíveis
        $notificationTypes = $this->notificationPreferenceModel->getAllNotificationTypes();
        $notificationChannels = $this->notificationPreferenceModel->getAllNotificationChannels();
        
        // Obter métricas de preferências
        $metrics = $this->notificationPreferenceModel->getPreferenceMetrics();
        
        // Renderizar a view
        $this->view('admin/notification_preferences', [
            'notificationTypes' => $notificationTypes,
            'notificationChannels' => $notificationChannels,
            'metrics' => $metrics,
            'title' => 'Administração de Preferências de Notificação'
        ]);
    }
    
    /**
     * Processa o salvamento das configurações de administração
     */
    public function adminSave() {
        // Verificar método da requisição
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/notificacoes/preferencias');
            exit;
        }
        
        // TODO: Implementar a lógica de salvamento das configurações administrativas
        // Esta função será implementada quando for criada a view de administração
        
        $_SESSION['success'] = 'Configurações administrativas atualizadas com sucesso!';
        header('Location: ' . BASE_URL . 'admin/notificacoes/preferencias');
        exit;
    }
    
    /**
     * Exibe as métricas e estatísticas de preferências de notificação
     */
    public function metrics() {
        // Obter métricas de preferências
        $metrics = $this->notificationPreferenceModel->getPreferenceMetrics();
        
        // Renderizar a view ou retornar JSON dependendo do tipo de requisição
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode($metrics);
            exit;
        } else {
            $this->view('admin/notification_metrics', [
                'metrics' => $metrics,
                'title' => 'Métricas de Preferências de Notificação'
            ]);
        }
    }
    
    /**
     * Obtém a ação atual da requisição
     * 
     * @return string Nome da ação atual
     */
    private function getCurrentAction() {
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', trim($uri, '/'));
        
        // Verificar a URI para determinar a ação
        if (count($parts) >= 2 && $parts[0] === 'preferencias-notificacao') {
            if (count($parts) >= 3 && $parts[1] === 'salvar') {
                return 'save';
            } else if (count($parts) >= 3 && $parts[1] === 'atualizar') {
                return 'update';
            } else if (count($parts) >= 3 && $parts[1] === 'inicializar') {
                return 'initialize';
            } else {
                return 'index';
            }
        } else if (count($parts) >= 3 && $parts[0] === 'admin' && $parts[1] === 'notificacoes' && $parts[2] === 'preferencias') {
            if (count($parts) >= 4 && $parts[3] === 'salvar') {
                return 'adminSave';
            } else if (count($parts) >= 4 && $parts[3] === 'metricas') {
                return 'metrics';
            } else {
                return 'admin';
            }
        }
        
        return '';
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuthentication() {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'É necessário fazer login para acessar esta página.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }
    
    /**
     * Verifica se o usuário tem permissões de administrador
     */
    private function checkAdminPermission() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['error'] = 'Você não tem permissão para acessar esta página.';
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}
