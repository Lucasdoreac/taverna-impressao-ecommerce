<?php
/**
 * AsyncNotificationsController - Controlador para API de notificações de processos assíncronos
 * 
 * Gerencia endpoints de API para integração entre o sistema de processamento assíncrono
 * e o sistema de notificações, incluindo registro, listagem e atualização de notificações.
 * 
 * @package App\Controllers\Api
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */

namespace App\Controllers\Api;

use App\Lib\Controller;
use App\Lib\Security\InputValidationTrait;
use App\Lib\Security\SecurityManager;
use App\Lib\Security\CsrfProtection;
use App\Lib\Http\ApiResponse;
use App\Lib\Security\RateLimiter;
use App\Lib\Notification\NotificationManager;
use App\Lib\Notification\AsyncProcessNotificationHandler;
use App\Models\AsyncProcess\StatusRepository;

class AsyncNotificationsController extends Controller {
    use InputValidationTrait;
    
    /**
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * @var AsyncProcessNotificationHandler
     */
    private $notificationHandler;
    
    /**
     * @var StatusRepository
     */
    private $statusRepository;
    
    /**
     * @var RateLimiter
     */
    private $rateLimiter;
    
    /**
     * @var \PDO
     */
    private $pdo;
    
    /**
     * Constructor
     * 
     * @param \PDO $pdo Conexão com o banco de dados
     */
    public function __construct(\PDO $pdo = null) {
        $this->pdo = $pdo;
        
        // Inicializar componentes
        $this->notificationManager = new NotificationManager();
        $this->statusRepository = new StatusRepository($pdo);
        $this->rateLimiter = new RateLimiter($pdo);
        
        // Inicializar o manipulador de notificações
        $this->notificationHandler = new AsyncProcessNotificationHandler(
            $this->notificationManager,
            $this->statusRepository,
            $pdo
        );
    }
    
    /**
     * Notifica sobre mudança de status em um processo assíncrono
     * 
     * @return void
     */
    public function notifyStatusChange() {
        // Aplicar rate limiting para evitar abusos
        if (!$this->rateLimiter->check('async_notifications_api', 60, 30)) {
            ApiResponse::error('Limite de solicitações excedido. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Verificar token de segurança interno (usado por workers)
        $apiKey = $this->getRequestHeader('X-API-Key');
        if (!$this->validateApiKey($apiKey)) {
            // Alternativamente, verificar autenticação de usuário e token CSRF
            if (!$this->validateUserAuth()) {
                ApiResponse::error('Não autorizado', 401);
                return;
            }
        }
        
        // Validar parâmetros obrigatórios
        $processToken = $this->postValidatedParam('process_token', 'string', [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        $oldStatus = $this->postValidatedParam('old_status', 'string', [
            'required' => true,
            'maxLength' => 50
        ]);
        
        $newStatus = $this->postValidatedParam('new_status', 'string', [
            'required' => true,
            'maxLength' => 50
        ]);
        
        $userId = $this->postValidatedParam('user_id', 'int', [
            'required' => true,
            'min' => 1
        ]);
        
        // Verificar se todos os parâmetros são válidos
        if ($processToken === null || $oldStatus === null || $newStatus === null || $userId === null) {
            ApiResponse::error('Parâmetros inválidos', 400);
            return;
        }
        
        // Obter contexto adicional (opcional)
        $context = $this->postValidatedParam('context', 'array', [
            'default' => []
        ]);
        
        try {
            // Verificar se o processo existe e pertence ao usuário
            if (!$this->statusRepository->processExists($processToken)) {
                ApiResponse::error('Processo não encontrado', 404);
                return;
            }
            
            if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
                ApiResponse::error('Usuário não tem acesso ao processo', 403);
                return;
            }
            
            // Processar notificação
            $success = $this->notificationHandler->handleStatusChange(
                $processToken,
                $oldStatus,
                $newStatus,
                $userId,
                $context
            );
            
            if ($success) {
                ApiResponse::success([
                    'message' => 'Notificação de mudança de status enviada com sucesso',
                    'process_token' => $processToken,
                    'new_status' => $newStatus
                ]);
            } else {
                ApiResponse::error('Falha ao enviar notificação', 500);
            }
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao processar notificação de status: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Notifica sobre progresso de um processo assíncrono
     * 
     * @return void
     */
    public function notifyProgress() {
        // Aplicar rate limiting para evitar abusos
        if (!$this->rateLimiter->check('async_notifications_api', 60, 30)) {
            ApiResponse::error('Limite de solicitações excedido. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Verificar token de segurança interno (usado por workers)
        $apiKey = $this->getRequestHeader('X-API-Key');
        if (!$this->validateApiKey($apiKey)) {
            // Alternativamente, verificar autenticação de usuário e token CSRF
            if (!$this->validateUserAuth()) {
                ApiResponse::error('Não autorizado', 401);
                return;
            }
        }
        
        // Validar parâmetros obrigatórios
        $processToken = $this->postValidatedParam('process_token', 'string', [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        $percentComplete = $this->postValidatedParam('percent_complete', 'int', [
            'required' => true,
            'min' => 0,
            'max' => 100
        ]);
        
        $userId = $this->postValidatedParam('user_id', 'int', [
            'required' => true,
            'min' => 1
        ]);
        
        // Verificar se todos os parâmetros são válidos
        if ($processToken === null || $percentComplete === null || $userId === null) {
            ApiResponse::error('Parâmetros inválidos', 400);
            return;
        }
        
        // Obter contexto adicional (opcional)
        $context = $this->postValidatedParam('context', 'array', [
            'default' => []
        ]);
        
        try {
            // Verificar se o processo existe e pertence ao usuário
            if (!$this->statusRepository->processExists($processToken)) {
                ApiResponse::error('Processo não encontrado', 404);
                return;
            }
            
            if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
                ApiResponse::error('Usuário não tem acesso ao processo', 403);
                return;
            }
            
            // Processar notificação
            $success = $this->notificationHandler->handleProgressUpdate(
                $processToken,
                $percentComplete,
                $userId,
                $context
            );
            
            if ($success) {
                ApiResponse::success([
                    'message' => 'Notificação de progresso enviada com sucesso',
                    'process_token' => $processToken,
                    'percent_complete' => $percentComplete
                ]);
            } else {
                ApiResponse::error('Falha ao enviar notificação', 500);
            }
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao processar notificação de progresso: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Notifica sobre resultados disponíveis em um processo assíncrono
     * 
     * @return void
     */
    public function notifyResultsAvailable() {
        // Aplicar rate limiting para evitar abusos
        if (!$this->rateLimiter->check('async_notifications_api', 60, 30)) {
            ApiResponse::error('Limite de solicitações excedido. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Verificar token de segurança interno (usado por workers)
        $apiKey = $this->getRequestHeader('X-API-Key');
        if (!$this->validateApiKey($apiKey)) {
            // Alternativamente, verificar autenticação de usuário e token CSRF
            if (!$this->validateUserAuth()) {
                ApiResponse::error('Não autorizado', 401);
                return;
            }
        }
        
        // Validar parâmetros obrigatórios
        $processToken = $this->postValidatedParam('process_token', 'string', [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        $userId = $this->postValidatedParam('user_id', 'int', [
            'required' => true,
            'min' => 1
        ]);
        
        // Verificar se todos os parâmetros são válidos
        if ($processToken === null || $userId === null) {
            ApiResponse::error('Parâmetros inválidos', 400);
            return;
        }
        
        // Obter resultados e contexto adicional 
        $results = $this->postValidatedParam('results', 'array', [
            'required' => true
        ]);
        
        $context = $this->postValidatedParam('context', 'array', [
            'default' => []
        ]);
        
        try {
            // Verificar se o processo existe e pertence ao usuário
            if (!$this->statusRepository->processExists($processToken)) {
                ApiResponse::error('Processo não encontrado', 404);
                return;
            }
            
            if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
                ApiResponse::error('Usuário não tem acesso ao processo', 403);
                return;
            }
            
            // Processar notificação
            $success = $this->notificationHandler->handleResultsAvailable(
                $processToken,
                $userId,
                $results,
                $context
            );
            
            if ($success) {
                ApiResponse::success([
                    'message' => 'Notificação de resultados enviada com sucesso',
                    'process_token' => $processToken
                ]);
            } else {
                ApiResponse::error('Falha ao enviar notificação', 500);
            }
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao processar notificação de resultados: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Notifica sobre expiração iminente de um processo
     * 
     * @return void
     */
    public function notifyExpirationWarning() {
        // Aplicar rate limiting para evitar abusos
        if (!$this->rateLimiter->check('async_notifications_api', 60, 30)) {
            ApiResponse::error('Limite de solicitações excedido. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Verificar token de segurança interno (usado por workers)
        $apiKey = $this->getRequestHeader('X-API-Key');
        if (!$this->validateApiKey($apiKey)) {
            // Alternativamente, verificar autenticação de usuário e token CSRF
            if (!$this->validateUserAuth()) {
                ApiResponse::error('Não autorizado', 401);
                return;
            }
        }
        
        // Validar parâmetros obrigatórios
        $processToken = $this->postValidatedParam('process_token', 'string', [
            'required' => true,
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        $userId = $this->postValidatedParam('user_id', 'int', [
            'required' => true,
            'min' => 1
        ]);
        
        $expiresAt = $this->postValidatedParam('expires_at', 'string', [
            'required' => true,
            'pattern' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'
        ]);
        
        // Verificar se todos os parâmetros são válidos
        if ($processToken === null || $userId === null || $expiresAt === null) {
            ApiResponse::error('Parâmetros inválidos', 400);
            return;
        }
        
        try {
            // Converter string de data para objeto DateTime
            $expirationDate = new \DateTime($expiresAt);
            
            // Verificar se o processo existe e pertence ao usuário
            if (!$this->statusRepository->processExists($processToken)) {
                ApiResponse::error('Processo não encontrado', 404);
                return;
            }
            
            if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
                ApiResponse::error('Usuário não tem acesso ao processo', 403);
                return;
            }
            
            // Processar notificação
            $success = $this->notificationHandler->handleExpirationWarning(
                $processToken,
                $userId,
                $expirationDate
            );
            
            if ($success) {
                ApiResponse::success([
                    'message' => 'Aviso de expiração enviado com sucesso',
                    'process_token' => $processToken,
                    'expires_at' => $expiresAt
                ]);
            } else {
                ApiResponse::error('Falha ao enviar aviso de expiração', 500);
            }
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao processar aviso de expiração: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Obtém notificações de processos assíncronos para um usuário
     * 
     * @return void
     */
    public function getUserProcessNotifications() {
        // Verificar autenticação e CSRF
        if (!$this->validateUserAuth()) {
            ApiResponse::error('Não autorizado', 401);
            return;
        }
        
        // Obter ID do usuário autenticado
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            ApiResponse::error('Sessão de usuário inválida', 401);
            return;
        }
        
        // Validar parâmetros opcionais
        $limit = $this->getValidatedParam('limit', 'int', [
            'default' => 10,
            'min' => 1,
            'max' => 50
        ]);
        
        $page = $this->getValidatedParam('page', 'int', [
            'default' => 1,
            'min' => 1
        ]);
        
        $processToken = $this->getValidatedParam('process_token', 'string', [
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        try {
            // Calcular offset para paginação
            $offset = ($page - 1) * $limit;
            
            // Obter notificações
            $notifications = $this->notificationManager->getUserNotifications(
                $userId,
                [
                    'types' => [
                        'process_status', 'process_progress', 'process_completed', 
                        'process_failed', 'process_results', 'process_expiration'
                    ],
                    'process_token' => $processToken,
                    'limit' => $limit,
                    'offset' => $offset
                ]
            );
            
            // Obter contagem total para paginação
            $totalCount = $this->notificationManager->countUserNotifications(
                $userId,
                [
                    'types' => [
                        'process_status', 'process_progress', 'process_completed', 
                        'process_failed', 'process_results', 'process_expiration'
                    ],
                    'process_token' => $processToken
                ]
            );
            
            // Sanitizar notificações para saída
            $sanitizedNotifications = $this->sanitizeNotifications($notifications);
            
            ApiResponse::success([
                'notifications' => $sanitizedNotifications,
                'pagination' => [
                    'total' => $totalCount,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalCount / $limit)
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao obter notificações de processo: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Marca uma notificação de processo como lida
     * 
     * @return void
     */
    public function markProcessNotificationRead() {
        // Verificar autenticação e CSRF
        if (!$this->validateUserAuth()) {
            ApiResponse::error('Não autorizado', 401);
            return;
        }
        
        // Obter ID do usuário autenticado
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            ApiResponse::error('Sessão de usuário inválida', 401);
            return;
        }
        
        // Validar parâmetros obrigatórios
        $notificationId = $this->postValidatedParam('notification_id', 'int', [
            'required' => true,
            'min' => 1
        ]);
        
        if ($notificationId === null) {
            ApiResponse::error('ID de notificação inválido', 400);
            return;
        }
        
        try {
            // Marcar notificação como lida
            $success = $this->notificationManager->markAsRead($notificationId, $userId);
            
            if ($success) {
                ApiResponse::success([
                    'message' => 'Notificação marcada como lida',
                    'notification_id' => $notificationId
                ]);
            } else {
                ApiResponse::error('Notificação não encontrada ou acesso negado', 404);
            }
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao marcar notificação como lida: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Marca todas as notificações de processo de um usuário como lidas
     * 
     * @return void
     */
    public function markAllProcessNotificationsRead() {
        // Verificar autenticação e CSRF
        if (!$this->validateUserAuth()) {
            ApiResponse::error('Não autorizado', 401);
            return;
        }
        
        // Obter ID do usuário autenticado
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            ApiResponse::error('Sessão de usuário inválida', 401);
            return;
        }
        
        // Validar parâmetro opcional de process_token
        $processToken = $this->postValidatedParam('process_token', 'string', [
            'pattern' => '/^[a-zA-Z0-9]{32}$/'
        ]);
        
        try {
            // Marcar todas as notificações como lidas
            $count = $this->notificationManager->markAllAsRead($userId, [
                'types' => [
                    'process_status', 'process_progress', 'process_completed', 
                    'process_failed', 'process_results', 'process_expiration'
                ],
                'process_token' => $processToken
            ]);
            
            ApiResponse::success([
                'message' => "{$count} notificações marcadas como lidas",
                'count' => $count
            ]);
            
        } catch (\Exception $e) {
            // Log detalhado para depuração interna
            error_log('Erro ao marcar todas notificações como lidas: ' . $e->getMessage());
            
            // Mensagem genérica para o cliente
            ApiResponse::error('Erro interno ao processar a solicitação', 500);
        }
    }
    
    /**
     * Sanitiza notificações para saída
     * 
     * @param array $notifications Array de notificações
     * @return array Notificações sanitizadas
     */
    private function sanitizeNotifications(array $notifications) {
        $sanitized = [];
        
        foreach ($notifications as $notification) {
            // Sanitizar campos sensíveis
            $context = $notification['context'] ?? [];
            
            // Remover informações sensíveis do contexto
            unset($context['internal_log']);
            unset($context['debug_info']);
            unset($context['auth_token']);
            
            $sanitized[] = [
                'id' => (int)$notification['id'],
                'title' => htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'),
                'message' => htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'),
                'type' => htmlspecialchars($notification['type'], ENT_QUOTES, 'UTF-8'),
                'status' => htmlspecialchars($notification['status'], ENT_QUOTES, 'UTF-8'),
                'created_at' => htmlspecialchars($notification['created_at'], ENT_QUOTES, 'UTF-8'),
                'read_at' => $notification['read_at'] ? htmlspecialchars($notification['read_at'], ENT_QUOTES, 'UTF-8') : null,
                'context' => $context,
                'url' => isset($context['url']) ? htmlspecialchars($context['url'], ENT_QUOTES, 'UTF-8') : null,
                'priority' => isset($context['priority']) ? htmlspecialchars($context['priority'], ENT_QUOTES, 'UTF-8') : 'normal',
                'process_token' => isset($context['process_token']) ? htmlspecialchars($context['process_token'], ENT_QUOTES, 'UTF-8') : null
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Valida a chave API para comunicações seguras entre serviços
     * 
     * @param string $apiKey Chave API a ser validada
     * @return bool Verdadeiro se a chave for válida
     */
    private function validateApiKey($apiKey) {
        if (empty($apiKey)) {
            return false;
        }
        
        // Verificar a chave com um hash seguro
        $validKey = getenv('ASYNC_NOTIFICATIONS_API_KEY');
        if (empty($validKey)) {
            // Fallback para uma configuração em arquivo
            $validKey = '5d4c5a4e3b2a1c0d9e8f7b6a5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f';
        }
        
        return hash_equals($validKey, $apiKey);
    }
    
    /**
     * Valida a autenticação do usuário e token CSRF
     * 
     * @return bool Verdadeiro se autenticado e validado
     */
    private function validateUserAuth() {
        // Verificar autenticação
        if (!SecurityManager::checkAuthentication()) {
            return false;
        }
        
        // Verificar token CSRF
        $csrfToken = $this->getRequestHeader('X-CSRF-Token');
        if (!$csrfToken) {
            $csrfToken = $_POST['csrf_token'] ?? null;
        }
        
        return $csrfToken && CsrfProtection::validateToken($csrfToken, false);
    }
    
    /**
     * Obtém um cabeçalho HTTP da requisição
     * 
     * @param string $name Nome do cabeçalho
     * @return string|null Valor do cabeçalho ou null
     */
    private function getRequestHeader($name) {
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        return $_SERVER[$headerName] ?? null;
    }
}
