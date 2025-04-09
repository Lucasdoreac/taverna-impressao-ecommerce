<?php
/**
 * NotificationsApiController - Controlador para APIs de notificações
 * 
 * Gerencia endpoints REST para interação com o sistema de notificações,
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Controllers
 * @version 1.0.0
 * @author Taverna da Impressão
 */
require_once dirname(__FILE__) . '/../lib/Controller.php';
require_once dirname(__FILE__) . '/../lib/Security/InputValidationTrait.php';
require_once dirname(__FILE__) . '/../lib/Security/SecurityManager.php';
require_once dirname(__FILE__) . '/../lib/Security/CsrfProtection.php';
require_once dirname(__FILE__) . '/../lib/Notification/NotificationManager.php';
require_once dirname(__FILE__) . '/../lib/Notification/PushNotificationProvider.php';

class NotificationsApiController extends Controller {
    use InputValidationTrait;
    
    /**
     * Gerenciador de notificações
     * 
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * Provider de notificações push
     * 
     * @var PushNotificationProvider
     */
    private $pushProvider;
    
    /**
     * Conexão PDO para o banco de dados
     * 
     * @var \PDO
     */
    private $pdo;
    
    /**
     * Construtor
     * 
     * @param \PDO $pdo Conexão com o banco de dados
     */
    public function __construct($pdo = null) {
        parent::__construct();
        
        $this->pdo = $pdo;
        
        // Inicializar gerenciador de notificações
        $this->notificationManager = NotificationManager::getInstance($pdo);
        
        // Inicializar provider de push
        $pushConfig = [
            'vapid_public_key' => getenv('VAPID_PUBLIC_KEY') ?: '',
            'vapid_private_key' => getenv('VAPID_PRIVATE_KEY') ?: '',
            'icon' => '/images/notification-icon.png'
        ];
        $this->pushProvider = new PushNotificationProvider($pdo, $pushConfig);
    }
    
    /**
     * Obtém notificações não lidas para o usuário atual
     * 
     * @return void Retorna JSON com notificações
     */
    public function getUnreadNotifications() {
        // Verificar autenticação
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Validar parâmetros
        $limit = $this->getValidatedParam('limit', 'int', [
            'default' => 10,
            'min' => 1,
            'max' => 50
        ]);
        
        try {
            // Obter notificações não lidas
            $notifications = $this->notificationManager->getUnreadNotifications($userId, $limit);
            
            $this->jsonResponse([
                'notifications' => $notifications,
                'count' => count($notifications),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Marca uma notificação como lida
     * 
     * @return void Retorna JSON com status
     */
    public function markAsRead() {
        // Verificar autenticação
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Validar parâmetros
        $notificationId = $this->postValidatedParam('notification_id', 'int', [
            'required' => true
        ]);
        
        if ($notificationId === null) {
            $this->jsonResponse(['error' => 'ID de notificação inválido'], 400);
            return;
        }
        
        try {
            // Marcar como lida
            $success = $this->notificationManager->markAsRead($notificationId, $userId);
            
            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Notificação marcada como lida']);
            } else {
                $this->jsonResponse(['error' => 'Notificação não encontrada ou não pertence ao usuário'], 404);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Marca todas as notificações do usuário como lidas
     * 
     * @return void Retorna JSON com status
     */
    public function markAllAsRead() {
        // Verificar autenticação
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        try {
            // Marcar todas como lidas
            $count = $this->notificationManager->markAllAsRead($userId);
            
            $this->jsonResponse([
                'success' => true,
                'count' => $count,
                'message' => "{$count} notificações marcadas como lidas"
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Registra uma nova subscrição de push
     * 
     * @return void Retorna JSON com status
     */
    public function subscribeNotifications() {
        // Verificar autenticação
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Validar parâmetros do JSON
        $subscription = $this->jsonValidatedParam('subscription', 'array', [
            'required' => true
        ]);
        
        if ($subscription === null) {
            $this->jsonResponse(['error' => 'Dados de subscrição inválidos'], 400);
            return;
        }
        
        // Validar campos necessários
        if (!isset($subscription['endpoint']) || 
            !isset($subscription['keys']['p256dh']) ||
            !isset($subscription['keys']['auth'])) {
            
            $this->jsonResponse(['error' => 'Dados de subscrição incompletos'], 400);
            return;
        }
        
        $endpoint = $subscription['endpoint'];
        $p256dhKey = $subscription['keys']['p256dh'];
        $authKey = $subscription['keys']['auth'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            // Salvar subscrição
            $result = $this->pushProvider->saveSubscription(
                $userId,
                $endpoint,
                $p256dhKey,
                $authKey,
                $userAgent
            );
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'subscription_id' => $result,
                    'message' => 'Subscrição de notificações push registrada com sucesso'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Falha ao registrar subscrição push'], 500);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Remove uma subscrição de push
     * 
     * @return void Retorna JSON com status
     */
    public function unsubscribeNotifications() {
        // Verificar autenticação
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Obter ID do usuário
        $userId = $_SESSION['user_id'];
        
        // Validar parâmetros do JSON
        $subscription = $this->jsonValidatedParam('subscription', 'array', [
            'required' => true
        ]);
        
        if ($subscription === null || !isset($subscription['endpoint'])) {
            $this->jsonResponse(['error' => 'Endpoint de subscrição inválido'], 400);
            return;
        }
        
        $endpoint = $subscription['endpoint'];
        
        try {
            // Remover subscrição
            $success = $this->pushProvider->removeSubscription($userId, $endpoint);
            
            if ($success) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Subscrição de notificações push removida com sucesso'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Subscrição não encontrada'], 404);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Obtém chave pública VAPID
     * 
     * @return void Retorna JSON com chave pública
     */
    public function getVapidPublicKey() {
        // Não requer autenticação, mas validamos CSRF para evitar CSRF em outros endpoints
        if (!CsrfProtection::validateRequest(false)) {
            $this->jsonResponse(['error' => 'Invalid CSRF token'], 403);
            return;
        }
        
        // Retornar chave pública
        $publicKey = getenv('VAPID_PUBLIC_KEY') ?: '';
        
        if (empty($publicKey)) {
            $this->jsonResponse(['error' => 'VAPID keys not configured'], 500);
            return;
        }
        
        $this->jsonResponse([
            'publicKey' => $publicKey
        ]);
    }
    
    /**
     * Valida uma requisição de API
     * Verifica autenticação e token CSRF para APIs
     * 
     * @return bool True se a requisição for válida
     */
    private function validateApiRequest() {
        // Verificar autenticação
        if (!SecurityManager::checkAuthentication()) {
            return false;
        }
        
        // Verificar token CSRF
        // Para APIs REST estamos usando o cabeçalho X-CSRF-Token
        $csrfToken = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null;
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken, false)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Envia resposta JSON com os cabeçalhos apropriados
     * 
     * @param array $data Dados a serem retornados como JSON
     * @param int $status Código de status HTTP
     * @return void
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}