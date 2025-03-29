<?php
/**
 * NotificationController - Controlador para gerenciamento de notificações
 * 
 * Este controlador gerencia as notificações para clientes e administradores,
 * especialmente relacionadas ao sistema de impressão 3D.
 */
class NotificationController extends Controller {
    private $notificationModel;
    private $authHelper;
    
    public function __construct() {
        // Inicializar modelo e helper
        $this->notificationModel = new NotificationModel();
        $this->authHelper = new AuthHelper();
        
        // Verificar autenticação para todas as ações
        $this->checkAuthentication();
        
        // Verificar permissões de admin para ações administrativas
        $adminActions = ['index', 'config', 'send'];
        if (in_array($this->getCurrentAction(), $adminActions)) {
            $this->checkAdminPermission();
        }
    }
    
    /**
     * Exibe a lista de notificações enviadas (para administradores)
     */
    public function index() {
        // Obter parâmetros de filtro
        $filters = [
            'user_id' => isset($_GET['user_id']) ? $_GET['user_id'] : null,
            'type' => isset($_GET['type']) ? $_GET['type'] : null,
            'status' => isset($_GET['status']) ? $_GET['status'] : null,
            'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : null,
            'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : null
        ];
        
        // Obter lista de notificações com paginação
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        
        $notifications = $this->notificationModel->getAllNotifications($filters, $page, $perPage);
        $totalNotifications = $this->notificationModel->countAllNotifications($filters);
        
        $totalPages = ceil($totalNotifications / $perPage);
        
        // Obter lista de usuários para o filtro
        $userModel = new UserModel();
        $users = $userModel->getAllUsers();
        
        // Renderizar a view
        $this->view('admin/notifications/index', [
            'notifications' => $notifications,
            'users' => $users,
            'filters' => $filters,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalNotifications' => $totalNotifications,
            'title' => 'Gerenciamento de Notificações'
        ]);
    }
    
    /**
     * Exibe e processa o formulário de configuração de notificações (para administradores)
     */
    public function config() {
        // Processar envio do formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'notify_on_status_change' => isset($_POST['notify_on_status_change']) ? 1 : 0,
                'notify_on_printer_assignment' => isset($_POST['notify_on_printer_assignment']) ? 1 : 0,
                'notify_on_completion' => isset($_POST['notify_on_completion']) ? 1 : 0,
                'notify_on_failure' => isset($_POST['notify_on_failure']) ? 1 : 0,
                'notify_on_delay' => isset($_POST['notify_on_delay']) ? 1 : 0,
                'automatic_notifications' => isset($_POST['automatic_notifications']) ? 1 : 0
            ];
            
            // Salvar configurações
            $settingsModel = new SettingsModel();
            foreach ($settings as $key => $value) {
                $settingsModel->setSetting('notifications_' . $key, $value);
            }
            
            $_SESSION['success'] = 'Configurações de notificações atualizadas com sucesso.';
            header('Location: ' . BASE_URL . 'admin/print_queue/notificacoes/config');
            exit;
        }
        
        // Obter configurações atuais
        $settingsModel = new SettingsModel();
        $settings = [
            'notify_on_status_change' => $settingsModel->getSetting('notifications_notify_on_status_change', 1),
            'notify_on_printer_assignment' => $settingsModel->getSetting('notifications_notify_on_printer_assignment', 1),
            'notify_on_completion' => $settingsModel->getSetting('notifications_notify_on_completion', 1),
            'notify_on_failure' => $settingsModel->getSetting('notifications_notify_on_failure', 1),
            'notify_on_delay' => $settingsModel->getSetting('notifications_notify_on_delay', 1),
            'automatic_notifications' => $settingsModel->getSetting('notifications_automatic_notifications', 1)
        ];
        
        // Renderizar a view
        $this->view('admin/notifications/config', [
            'settings' => $settings,
            'title' => 'Configuração de Notificações'
        ]);
    }
    
    /**
     * Processa o envio de uma notificação manual (para administradores)
     */
    public function send() {
        // Verificar método da requisição
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/print_queue/notificacoes');
            exit;
        }
        
        // Obter dados da requisição
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
        $queueItemId = isset($_POST['queue_item_id']) ? (int)$_POST['queue_item_id'] : null;
        $type = isset($_POST['type']) ? $_POST['type'] : 'info';
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        
        // Validar dados obrigatórios
        if (empty($userId) || empty($title) || empty($message)) {
            $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
            header('Location: ' . BASE_URL . 'admin/print_queue/notificacoes');
            exit;
        }
        
        // Preparar dados da notificação
        $notificationData = [
            'user_id' => $userId,
            'order_id' => $orderId,
            'queue_item_id' => $queueItemId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'created_by' => $_SESSION['user']['id'],
            'status' => 'unread'
        ];
        
        // Salvar notificação
        $notificationId = $this->notificationModel->create($notificationData);
        
        if ($notificationId) {
            $_SESSION['success'] = 'Notificação enviada com sucesso.';
        } else {
            $_SESSION['error'] = 'Erro ao enviar notificação. Por favor, tente novamente.';
        }
        
        header('Location: ' . BASE_URL . 'admin/print_queue/notificacoes');
        exit;
    }
    
    /**
     * Marca uma ou mais notificações como lidas (para clientes)
     */
    public function markAsRead() {
        // Verificar método da requisição
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'minha-conta');
            exit;
        }
        
        // Obter dados da requisição
        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : null;
        $markAll = isset($_POST['mark_all']) ? (bool)$_POST['mark_all'] : false;
        
        // Obter ID do usuário atual
        $userId = $_SESSION['user']['id'];
        
        if ($markAll) {
            // Marcar todas as notificações do usuário como lidas
            $result = $this->notificationModel->markAllAsRead($userId);
            
            $message = 'Todas as notificações foram marcadas como lidas.';
        } else if ($notificationId) {
            // Marcar uma notificação específica como lida
            $result = $this->notificationModel->markAsRead($notificationId, $userId);
            
            $message = 'Notificação marcada como lida.';
        } else {
            // Dados inválidos
            $_SESSION['error'] = 'Dados inválidos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta');
            exit;
        }
        
        // Verificar se a requisição é AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            // Responder com JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool)$result,
                'message' => $result ? $message : 'Erro ao marcar notificação(ões) como lida(s).'
            ]);
            exit;
        } else {
            // Responder com redirecionamento
            if ($result) {
                $_SESSION['success'] = $message;
            } else {
                $_SESSION['error'] = 'Erro ao marcar notificação(ões) como lida(s). Por favor, tente novamente.';
            }
            
            header('Location: ' . BASE_URL . 'minha-conta');
            exit;
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
        
        // Verificar se a URI contém '/admin/print_queue/notificacoes'
        if (count($parts) >= 3 && $parts[0] === 'admin' && $parts[1] === 'print_queue' && $parts[2] === 'notificacoes') {
            if (count($parts) >= 4 && $parts[3] === 'config') {
                return 'config';
            } else if (count($parts) >= 4 && $parts[3] === 'enviar-notificacao') {
                return 'send';
            } else {
                return 'index';
            }
        } else if (count($parts) >= 2 && $parts[0] === 'notificacoes' && $parts[1] === 'marcar-como-lidas') {
            return 'markAsRead';
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