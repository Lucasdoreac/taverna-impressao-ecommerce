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
        $adminActions = ['admin', 'adminSave', 'metrics', 'preview'];
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
        
        // Inicializar resultado para monitorar o sucesso das operações
        $result = true;
        
        // Processar formulário para novos tipos de notificação
        if (isset($_POST['new_type']) && is_array($_POST['new_type'])) {
            $newType = $_POST['new_type'];
            
            // Verificar se todos os campos necessários existem
            if (!empty($newType['name']) && !empty($newType['code']) && !empty($newType['category'])) {
                $typeData = [
                    'name' => trim($newType['name']),
                    'code' => trim($newType['code']),
                    'category' => trim($newType['category']),
                    'description' => isset($newType['description']) ? trim($newType['description']) : '',
                    'is_critical' => isset($newType['is_critical']) ? 1 : 0,
                    'is_active' => isset($newType['is_active']) ? 1 : 0
                ];
                
                // Adicionar novo tipo de notificação
                $addResult = $this->notificationPreferenceModel->addNotificationType($typeData);
                if (!$addResult) {
                    $_SESSION['error'] = 'Erro ao adicionar novo tipo de notificação.';
                    $result = false;
                }
            }
        }
        
        // Processar edições de tipos existentes
        if (isset($_POST['edit_type']) && is_array($_POST['edit_type'])) {
            foreach ($_POST['edit_type'] as $typeId => $typeData) {
                if (!empty($typeData['name']) && !empty($typeData['code'])) {
                    $updateData = [
                        'id' => (int)$typeId,
                        'name' => trim($typeData['name']),
                        'code' => trim($typeData['code']),
                        'category' => trim($typeData['category']),
                        'description' => isset($typeData['description']) ? trim($typeData['description']) : '',
                        'is_critical' => isset($typeData['is_critical']) ? 1 : 0,
                        'is_active' => isset($typeData['is_active']) ? 1 : 0
                    ];
                    
                    // Atualizar tipo de notificação
                    $updateResult = $this->notificationPreferenceModel->updateNotificationType($updateData);
                    if (!$updateResult) {
                        $_SESSION['error'] = 'Erro ao atualizar tipo de notificação.';
                        $result = false;
                    }
                }
            }
        }
        
        // Processar novos canais de notificação
        if (isset($_POST['new_channel']) && is_array($_POST['new_channel'])) {
            $newChannel = $_POST['new_channel'];
            
            if (!empty($newChannel['name']) && !empty($newChannel['code'])) {
                $channelData = [
                    'name' => trim($newChannel['name']),
                    'code' => trim($newChannel['code']),
                    'description' => isset($newChannel['description']) ? trim($newChannel['description']) : '',
                    'supports_frequency' => isset($newChannel['supports_frequency']) ? 1 : 0,
                    'is_active' => isset($newChannel['is_active']) ? 1 : 0
                ];
                
                // Adicionar novo canal
                $addResult = $this->notificationPreferenceModel->addNotificationChannel($channelData);
                if (!$addResult) {
                    $_SESSION['error'] = 'Erro ao adicionar novo canal de notificação.';
                    $result = false;
                }
            }
        }
        
        // Processar edições de canais existentes
        if (isset($_POST['edit_channel']) && is_array($_POST['edit_channel'])) {
            foreach ($_POST['edit_channel'] as $channelId => $channelData) {
                if (!empty($channelData['name']) && !empty($channelData['code'])) {
                    $updateData = [
                        'id' => (int)$channelId,
                        'name' => trim($channelData['name']),
                        'code' => trim($channelData['code']),
                        'description' => isset($channelData['description']) ? trim($channelData['description']) : '',
                        'supports_frequency' => isset($channelData['supports_frequency']) ? 1 : 0,
                        'is_active' => isset($channelData['is_active']) ? 1 : 0
                    ];
                    
                    // Atualizar canal
                    $updateResult = $this->notificationPreferenceModel->updateNotificationChannel($updateData);
                    if (!$updateResult) {
                        $_SESSION['error'] = 'Erro ao atualizar canal de notificação.';
                        $result = false;
                    }
                }
            }
        }
        
        // Mensagem de sucesso se tudo ocorreu bem
        if ($result && !isset($_SESSION['error'])) {
            $_SESSION['success'] = 'Configurações administrativas atualizadas com sucesso!';
        }
        
        // Redirecionar de volta para a página de administração
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
     * Exibe uma página de preview de notificações
     */
    public function preview() {
        // Obter todos os tipos de notificação e canais disponíveis
        $notificationTypes = $this->notificationPreferenceModel->getAllNotificationTypes();
        $notificationChannels = $this->notificationPreferenceModel->getAllNotificationChannels();
        
        // Criar exemplos de notificações para preview
        $previewNotifications = $this->generatePreviewNotifications($notificationTypes);
        
        // Renderizar a view
        $this->view('admin/notification_preview', [
            'notificationTypes' => $notificationTypes,
            'notificationChannels' => $notificationChannels,
            'previewNotifications' => $previewNotifications,
            'title' => 'Preview de Notificações'
        ]);
    }
    
    /**
     * Gera exemplos de notificações para preview
     * 
     * @param array $notificationTypes Tipos de notificação disponíveis
     * @return array Exemplos de notificações
     */
    private function generatePreviewNotifications($notificationTypes) {
        $previewNotifications = [];
        
        // Gerar exemplos para cada tipo de notificação
        foreach ($notificationTypes as $type) {
            $notification = [
                'id' => 'preview_' . $type['id'],
                'type' => $type,
                'title' => 'Exemplo de ' . $type['name'],
                'content' => $this->generatePreviewContent($type['category'], $type['code']),
                'created_at' => date('Y-m-d H:i:s'),
                'image_url' => $this->getPreviewImageUrl($type['category']),
                'action_url' => $this->getPreviewActionUrl($type['category'])
            ];
            
            $previewNotifications[] = $notification;
        }
        
        return $previewNotifications;
    }
    
    /**
     * Gera conteúdo para preview de notificação baseado na categoria e código
     * 
     * @param string $category Categoria da notificação
     * @param string $code Código do tipo de notificação
     * @return string Conteúdo da notificação
     */
    private function generatePreviewContent($category, $code) {
        // Conteúdo específico baseado na categoria e código
        $content = '';
        
        switch ($category) {
            case 'order':
                if (strpos($code, 'new') !== false) {
                    $content = 'Seu pedido #12345 foi recebido e está em processamento. Agradecemos pela sua compra!';
                } else if (strpos($code, 'status') !== false) {
                    $content = 'Seu pedido #12345 teve o status atualizado para "Em produção". Estamos trabalhando nele!';
                } else if (strpos($code, 'shipped') !== false) {
                    $content = 'Seu pedido #12345 foi enviado! Acompanhe a entrega com o código de rastreio AB123456789XYZ.';
                } else {
                    $content = 'Notificação relacionada ao seu pedido #12345.';
                }
                break;
                
            case 'print':
                if (strpos($code, 'queue') !== false) {
                    $content = 'Seu projeto "Miniatura de Dragão" entrou na fila de impressão. Posição atual: 3.';
                } else if (strpos($code, 'started') !== false) {
                    $content = 'A impressão do seu projeto "Miniatura de Dragão" foi iniciada! Tempo estimado: 4h30min.';
                } else if (strpos($code, 'completed') !== false) {
                    $content = 'Seu projeto "Miniatura de Dragão" foi impresso com sucesso e está em fase de acabamento.';
                } else {
                    $content = 'Notificação relacionada à sua impressão 3D do projeto "Miniatura de Dragão".';
                }
                break;
                
            case 'account':
                if (strpos($code, 'login') !== false) {
                    $content = 'Novo acesso à sua conta detectado em 01/04/2025 às 10:35 (São Paulo, Brasil).';
                } else if (strpos($code, 'password') !== false) {
                    $content = 'Sua senha foi alterada em 01/04/2025. Se não foi você, entre em contato imediatamente.';
                } else {
                    $content = 'Notificação relacionada à sua conta na Taverna da Impressão.';
                }
                break;
                
            case 'promotion':
                $content = 'Nova promoção! Aproveite 15% de desconto em todos os modelos 3D da categoria "Fantasia" até 10/04/2025.';
                break;
                
            default:
                $content = 'Exemplo de notificação para demonstração.';
                break;
        }
        
        return $content;
    }
    
    /**
     * Obtém URL de imagem para preview baseado na categoria
     * 
     * @param string $category Categoria da notificação
     * @return string URL da imagem
     */
    private function getPreviewImageUrl($category) {
        // URLs de imagens de exemplo baseadas na categoria
        switch ($category) {
            case 'order':
                return BASE_URL . 'assets/img/previews/order_notification.png';
            case 'print':
                return BASE_URL . 'assets/img/previews/print_notification.png';
            case 'account':
                return BASE_URL . 'assets/img/previews/account_notification.png';
            case 'promotion':
                return BASE_URL . 'assets/img/previews/promotion_notification.png';
            default:
                return BASE_URL . 'assets/img/previews/default_notification.png';
        }
    }
    
    /**
     * Obtém URL de ação para preview baseado na categoria
     * 
     * @param string $category Categoria da notificação
     * @return string URL da ação
     */
    private function getPreviewActionUrl($category) {
        // URLs de ação de exemplo baseadas na categoria
        switch ($category) {
            case 'order':
                return BASE_URL . 'account/orders/12345';
            case 'print':
                return BASE_URL . 'account/print-jobs/67890';
            case 'account':
                return BASE_URL . 'account/security';
            case 'promotion':
                return BASE_URL . 'promocoes/fantasia';
            default:
                return BASE_URL;
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
            } else if (count($parts) >= 4 && $parts[3] === 'preview') {
                return 'preview';
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
