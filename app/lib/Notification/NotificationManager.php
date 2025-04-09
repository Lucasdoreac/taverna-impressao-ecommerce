<?php
/**
 * NotificationManager - Sistema de gerenciamento e envio de notificações
 * 
 * Gerencia notificações para usuários, incluindo persistência em banco de dados
 * e preparação para envio por múltiplos canais (in-app, e-mail, push).
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Notification
 * @version    1.0.0
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';
require_once __DIR__ . '/../Security/SecurityManager.php';
require_once __DIR__ . '/PushService.php';
require_once __DIR__ . '/NotificationThresholds.php';

class NotificationManager {
    use InputValidationTrait;
    
    /**
     * Instância singleton
     * 
     * @var NotificationManager
     */
    private static $instance;
    
    /**
     * Conexão com o banco de dados
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Serviço de notificações push
     * 
     * @var PushService
     */
    private $pushService;
    
    /**
     * Gerenciador de limiares para notificações de performance
     * 
     * @var NotificationThresholds
     */
    private $thresholds;
    
    /**
     * Tipos válidos de notificação
     * 
     * @var array
     */
    private static $validTypes = ['info', 'warning', 'success', 'error', 'performance'];
    
    /**
     * Canais de entrega válidos
     * 
     * @var array
     */
    private static $validChannels = ['database', 'push', 'email', 'dashboard'];
    
    /**
     * Níveis de severidade para alertas de performance
     * 
     * @var array
     */
    private static $validSeverityLevels = ['low', 'medium', 'high', 'critical'];
    
    /**
     * Construtor privado (padrão singleton)
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->pushService = new PushService();
        $this->thresholds = NotificationThresholds::getInstance();
    }
    
    /**
     * Obtém a instância do NotificationManager
     * 
     * @return NotificationManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Cria uma nova notificação
     * 
     * @param int $userId ID do usuário destinatário
     * @param string $title Título da notificação
     * @param string $message Mensagem da notificação
     * @param string $type Tipo da notificação (info, warning, success, error, performance)
     * @param array $context Dados adicionais de contexto
     * @param array $channels Canais para envio (database, push, email, dashboard)
     * @return int|bool ID da notificação ou false em caso de erro
     */
    public function createNotification($userId, $title, $message, $type = 'info', $context = [], $channels = ['database']) {
        try {
            // Validação dos parâmetros
            $userId = (int)$userId;
            $title = $this->validateString($title, ['maxLength' => 255, 'required' => true]);
            $message = $this->validateString($message, ['required' => true]);
            
            // Validar tipo de notificação
            if (!in_array($type, self::$validTypes)) {
                $type = 'info'; // Valor padrão seguro
            }
            
            // Filtrar canais válidos
            $channels = array_intersect($channels, self::$validChannels);
            if (empty($channels)) {
                $channels = ['database']; // Garantir pelo menos um canal válido
            }
            
            // Sanitizar e validar contexto
            $contextData = null;
            if (!empty($context)) {
                // Remover qualquer dado sensível do contexto
                unset($context['password']);
                unset($context['token']);
                unset($context['csrf']);
                
                $contextData = json_encode($context);
                
                // Validar tamanho do JSON para evitar problemas de armazenamento
                if (strlen($contextData) > 65535) { // Limite de tamanho para TEXT no MySQL
                    $contextData = json_encode(['error' => 'Context data too large']);
                }
            }
            
            // Registrar notificação no banco de dados
            $sql = "INSERT INTO notifications 
                    (user_id, title, message, type, context, channels, status, created_at) 
                    VALUES 
                    (:user_id, :title, :message, :type, :context, :channels, 'unread', NOW())";
            
            $params = [
                ':user_id' => $userId,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':context' => $contextData,
                ':channels' => json_encode($channels)
            ];
            
            $this->db->execute($sql, $params);
            $notificationId = $this->db->lastInsertId();
            
            if (!$notificationId) {
                throw new Exception('Falha ao criar notificação no banco de dados');
            }
            
            // Enviar para os canais apropriados
            $this->dispatchToChannels($notificationId, $userId, $title, $message, $type, $context, $channels);
            
            return $notificationId;
        } catch (Exception $e) {
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Despacha a notificação para os canais especificados
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário
     * @param string $title Título
     * @param string $message Mensagem
     * @param string $type Tipo
     * @param array $context Contexto
     * @param array $channels Canais
     * @return void
     */
    private function dispatchToChannels($notificationId, $userId, $title, $message, $type, $context, $channels) {
        // Database já foi tratado na criação da notificação
        
        // Push notification
        if (in_array('push', $channels)) {
            $this->pushService->sendNotification($userId, [
                'id' => $notificationId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'context' => $context
            ]);
        }
        
        // E-mail (implementação básica)
        if (in_array('email', $channels)) {
            $this->sendEmailNotification($userId, $title, $message, $type, $context);
        }
        
        // Dashboard (para notificações de performance principalmente)
        if (in_array('dashboard', $channels)) {
            $this->addToDashboard($notificationId, $userId, $title, $message, $type, $context);
        }
    }
    
    /**
     * Adiciona a notificação ao dashboard de monitoramento
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário
     * @param string $title Título
     * @param string $message Mensagem
     * @param string $type Tipo
     * @param array $context Contexto
     * @return bool Sucesso da operação
     */
    private function addToDashboard($notificationId, $userId, $title, $message, $type, $context) {
        try {
            // Verificar se é uma notificação de performance
            if ($type !== 'performance' || empty($context['metric'])) {
                // Somente notificações de performance vão para o dashboard
                return false;
            }
            
            // Extrair dados para o dashboard
            $metric = $context['metric'] ?? '';
            $value = $context['value'] ?? 0;
            $threshold = $context['threshold'] ?? 0;
            $component = $context['component'] ?? '';
            $severity = $context['severity'] ?? 'medium';
            
            // Validar severidade
            if (!in_array($severity, self::$validSeverityLevels)) {
                $severity = 'medium';
            }
            
            // Inserir no dashboard de performance
            $sql = "INSERT INTO performance_dashboard 
                    (notification_id, metric, value, threshold, component, severity, created_at) 
                    VALUES 
                    (:notification_id, :metric, :value, :threshold, :component, :severity, NOW())";
            
            $params = [
                ':notification_id' => $notificationId,
                ':metric' => $metric,
                ':value' => $value,
                ':threshold' => $threshold,
                ':component' => $component,
                ':severity' => $severity
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao adicionar notificação ao dashboard: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia notificação por e-mail
     * 
     * @param int $userId ID do usuário
     * @param string $title Título
     * @param string $message Mensagem
     * @param string $type Tipo
     * @param array $context Contexto
     * @return bool
     */
    private function sendEmailNotification($userId, $title, $message, $type, $context) {
        try {
            // Obter e-mail do usuário
            $sql = "SELECT email FROM users WHERE id = :user_id";
            $params = [':user_id' => $userId];
            $result = $this->db->fetchSingle($sql, $params);
            
            if (!$result || empty($result['email'])) {
                error_log("E-mail não encontrado para o usuário ID {$userId}");
                return false;
            }
            
            $email = $result['email'];
            
            // Implementação básica de envio de e-mail
            // Em produção, usaria uma biblioteca como PHPMailer ou implementação SMTP
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Taverna da Impressão 3D <no-reply@tavernaimpressao3d.com>' . "\r\n";
            
            $emailBody = "<html><body>";
            $emailBody .= "<h2>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h2>";
            $emailBody .= "<p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
            
            // Adicionar detalhes específicos para notificações de performance
            if ($type === 'performance' && !empty($context['metric'])) {
                $emailBody .= "<div style='background-color: #f8f8f8; padding: 10px; margin: 10px 0; border-left: 4px solid #e74c3c;'>";
                $emailBody .= "<p><strong>Métrica:</strong> " . htmlspecialchars($context['metric'], ENT_QUOTES, 'UTF-8') . "</p>";
                $emailBody .= "<p><strong>Valor:</strong> " . htmlspecialchars($context['value'], ENT_QUOTES, 'UTF-8') . "</p>";
                $emailBody .= "<p><strong>Limite:</strong> " . htmlspecialchars($context['threshold'], ENT_QUOTES, 'UTF-8') . "</p>";
                $emailBody .= "<p><strong>Componente:</strong> " . htmlspecialchars($context['component'], ENT_QUOTES, 'UTF-8') . "</p>";
                $emailBody .= "<p><strong>Severidade:</strong> " . htmlspecialchars($context['severity'], ENT_QUOTES, 'UTF-8') . "</p>";
                $emailBody .= "</div>";
            }
            
            $emailBody .= "<p>Acesse sua conta para mais detalhes.</p>";
            $emailBody .= "</body></html>";
            
            // Simular envio apenas para fins de demonstração
            // mail($email, $title, $emailBody, $headers);
            
            // Registrar tentativa de envio
            $sql = "INSERT INTO notification_deliveries 
                    (notification_id, channel, status, timestamp, details) 
                    VALUES 
                    ((SELECT id FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1), 
                    'email', 'sent', NOW(), :details)";
            
            $details = json_encode([
                'email' => $email,
                'subject' => $title
            ]);
            
            $params = [
                ':user_id' => $userId,
                ':details' => $details
            ];
            
            $this->db->execute($sql, $params);
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao enviar notificação por e-mail: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca uma notificação como lida
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário (para validação de propriedade)
     * @return bool Sucesso da operação
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $notificationId = (int)$notificationId;
            $userId = (int)$userId;
            
            // Verificar se a notificação pertence ao usuário
            $sql = "SELECT id FROM notifications 
                    WHERE id = :id AND user_id = :user_id AND status = 'unread'";
            
            $params = [
                ':id' => $notificationId,
                ':user_id' => $userId
            ];
            
            $result = $this->db->fetchSingle($sql, $params);
            
            if (!$result) {
                // Notificação não encontrada ou não pertence ao usuário
                return false;
            }
            
            // Atualizar status para lida
            $sql = "UPDATE notifications 
                    SET status = 'read', read_at = NOW() 
                    WHERE id = :id";
            
            $params = [':id' => $notificationId];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao marcar notificação como lida: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca todas as notificações de um usuário como lidas
     * 
     * @param int $userId ID do usuário
     * @return bool Sucesso da operação
     */
    public function markAllAsRead($userId) {
        try {
            $userId = (int)$userId;
            
            $sql = "UPDATE notifications 
                    SET status = 'read', read_at = NOW() 
                    WHERE user_id = :user_id AND status = 'unread'";
            
            $params = [':user_id' => $userId];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao marcar todas as notificações como lidas: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém notificações de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $status Status das notificações (all, unread, read)
     * @param int $limit Limite de notificações a retornar
     * @param int $offset Offset para paginação
     * @return array Lista de notificações
     */
    public function getUserNotifications($userId, $status = 'all', $limit = 10, $offset = 0) {
        try {
            $userId = (int)$userId;
            $limit = max(1, min(100, (int)$limit)); // Limitar entre 1 e 100
            $offset = max(0, (int)$offset);
            
            // Validar status
            $allowedStatus = ['all', 'unread', 'read'];
            if (!in_array($status, $allowedStatus)) {
                $status = 'all';
            }
            
            $sql = "SELECT id, title, message, type, context, status, created_at, read_at 
                    FROM notifications 
                    WHERE user_id = :user_id";
            
            if ($status !== 'all') {
                $sql .= " AND status = :status";
            }
            
            $sql .= " ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $params = [
                ':user_id' => $userId,
                ':limit' => $limit,
                ':offset' => $offset
            ];
            
            if ($status !== 'all') {
                $params[':status'] = $status;
            }
            
            $notifications = $this->db->fetchAll($sql, $params);
            
            // Processar contexto JSON para cada notificação
            foreach ($notifications as &$notification) {
                if (isset($notification['context']) && !empty($notification['context'])) {
                    $notification['context'] = json_decode($notification['context'], true);
                } else {
                    $notification['context'] = null;
                }
            }
            
            return $notifications;
        } catch (Exception $e) {
            error_log('Erro ao obter notificações do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o número de notificações não lidas do usuário
     * 
     * @param int $userId ID do usuário
     * @return int Número de notificações não lidas
     */
    public function countUnreadNotifications($userId) {
        try {
            $userId = (int)$userId;
            
            $sql = "SELECT COUNT(*) as count 
                    FROM notifications 
                    WHERE user_id = :user_id AND status = 'unread'";
            
            $params = [':user_id' => $userId];
            
            $result = $this->db->fetchSingle($sql, $params);
            
            return isset($result['count']) ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            error_log('Erro ao contar notificações não lidas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Exclui uma notificação
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário (para validação de propriedade)
     * @return bool Sucesso da operação
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $notificationId = (int)$notificationId;
            $userId = (int)$userId;
            
            // Verificar se a notificação pertence ao usuário
            $sql = "SELECT id FROM notifications 
                    WHERE id = :id AND user_id = :user_id";
            
            $params = [
                ':id' => $notificationId,
                ':user_id' => $userId
            ];
            
            $result = $this->db->fetchSingle($sql, $params);
            
            if (!$result) {
                // Notificação não encontrada ou não pertence ao usuário
                return false;
            }
            
            // Excluir notificação
            $sql = "DELETE FROM notifications WHERE id = :id";
            $params = [':id' => $notificationId];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao excluir notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria notificação para mudança de status na fila de impressão
     * 
     * @param int $queueId ID do item na fila
     * @param string $status Status atual
     * @param string $previousStatus Status anterior
     * @param int $userId ID do usuário destinatário
     * @param string $notes Notas adicionais
     * @return int|bool ID da notificação ou false
     */
    public function createPrintQueueStatusNotification($queueId, $status, $previousStatus, $userId, $notes = '') {
        try {
            // Validar parâmetros
            $queueId = (int)$queueId;
            $userId = (int)$userId;
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Obter informações do modelo e fila
            $sql = "SELECT pq.id as queue_id, cm.original_name as model_name, pq.priority
                    FROM print_queue pq
                    JOIN customer_models cm ON pq.model_id = cm.id
                    WHERE pq.id = :queue_id";
            
            $params = [':queue_id' => $queueId];
            $queueItem = $this->db->fetchSingle($sql, $params);
            
            if (!$queueItem) {
                return false;
            }
            
            // Definir título e mensagem com base no status
            $title = '';
            $message = '';
            $type = 'info';
            $context = [
                'queue_id' => $queueId,
                'model_name' => $queueItem['model_name'],
                'previous_status' => $previousStatus,
                'current_status' => $status,
                'priority' => $queueItem['priority']
            ];
            
            // Canais de entrega
            $channels = ['database'];
            
            // Determinar título, mensagem e tipo com base na transição de status
            switch ($status) {
                case 'assigned':
                    $title = 'Modelo atribuído para impressão';
                    $message = "Seu modelo '{$queueItem['model_name']}' foi atribuído para impressão e será processado em breve.";
                    $channels[] = 'push'; // Notificação push importante
                    break;
                    
                case 'printing':
                    $title = 'Seu modelo está sendo impresso';
                    $message = "Seu modelo '{$queueItem['model_name']}' está sendo impresso neste momento.";
                    $channels[] = 'push'; // Notificação push importante
                    break;
                    
                case 'completed':
                    $title = 'Impressão concluída com sucesso';
                    $message = "A impressão do seu modelo '{$queueItem['model_name']}' foi concluída com sucesso!";
                    $type = 'success';
                    $channels[] = 'push'; // Notificação push importante
                    $channels[] = 'email'; // Também enviar por e-mail
                    break;
                    
                case 'failed':
                    $title = 'Falha na impressão';
                    $message = "Houve uma falha na impressão do seu modelo '{$queueItem['model_name']}'.";
                    if ($notes) {
                        $message .= " Detalhes: {$notes}";
                    }
                    $type = 'error';
                    $channels[] = 'push'; // Notificação push importante
                    $channels[] = 'email'; // Também enviar por e-mail
                    break;
                    
                case 'cancelled':
                    $title = 'Impressão cancelada';
                    $message = "A impressão do seu modelo '{$queueItem['model_name']}' foi cancelada.";
                    if ($notes) {
                        $message .= " Motivo: {$notes}";
                    }
                    $type = 'warning';
                    $channels[] = 'push'; // Notificação push importante
                    break;
                    
                default:
                    $title = 'Atualização de status';
                    $message = "O status do seu modelo '{$queueItem['model_name']}' foi atualizado para '{$status}'.";
                    break;
            }
            
            // Adicionar notas ao contexto se fornecidas
            if ($notes) {
                $context['notes'] = $notes;
            }
            
            // Criar notificação
            return $this->createNotification($userId, $title, $message, $type, $context, $channels);
        } catch (Exception $e) {
            error_log('Erro ao criar notificação de status da fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria notificação para administradores sobre item de alta prioridade
     * 
     * @param int $queueId ID do item na fila
     * @param int $priority Prioridade do item
     * @return bool Sucesso da operação
     */
    public function notifyHighPriorityItem($queueId, $priority) {
        try {
            // Somente notificar para prioridade >= 8
            if ($priority < 8) {
                return true;
            }
            
            // Obter detalhes do modelo
            $sql = "SELECT pq.id as queue_id, cm.original_name as model_name, u.name as user_name
                    FROM print_queue pq
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pq.id = :queue_id";
            
            $params = [':queue_id' => $queueId];
            $queueItem = $this->db->fetchSingle($sql, $params);
            
            if (!$queueItem) {
                return false;
            }
            
            // Obter lista de administradores
            $sql = "SELECT id FROM users WHERE role = 'admin'";
            $adminUsers = $this->db->fetchAll($sql);
            
            if (empty($adminUsers)) {
                return false;
            }
            
            // Criar notificação para cada administrador
            $title = 'Item de alta prioridade na fila';
            $message = "Um novo item de prioridade {$priority} foi adicionado à fila: '{$queueItem['model_name']}' (usuário: {$queueItem['user_name']})";
            
            $context = [
                'queue_id' => $queueId,
                'model_name' => $queueItem['model_name'],
                'priority' => $priority,
                'user_name' => $queueItem['user_name']
            ];
            
            $channels = ['database', 'push'];
            
            foreach ($adminUsers as $admin) {
                $this->createNotification($admin['id'], $title, $message, 'warning', $context, $channels);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao notificar item de alta prioridade: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma notificação de sistema para todos os usuários
     * 
     * @param string $title Título da notificação
     * @param string $message Mensagem da notificação
     * @param string $type Tipo da notificação
     * @param array $userRoles Roles de usuários que devem receber a notificação
     * @return bool Sucesso da operação
     */
    public function createSystemNotification($title, $message, $type = 'info', $userRoles = ['user', 'admin']) {
        try {
            // Validar parâmetros
            $title = $this->validateString($title, ['maxLength' => 255, 'required' => true]);
            $message = $this->validateString($message, ['required' => true]);
            
            if (!in_array($type, self::$validTypes)) {
                $type = 'info';
            }
            
            // Validar roles
            $allowedRoles = ['user', 'admin', 'manager'];
            $roles = array_intersect($userRoles, $allowedRoles);
            
            if (empty($roles)) {
                $roles = ['user', 'admin']; // Padrão
            }
            
            // Construir a cláusula IN para roles
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            
            // Obter usuários com as roles especificadas
            $sql = "SELECT id FROM users WHERE role IN ({$placeholders})";
            $users = $this->db->fetchAll($sql, $roles);
            
            if (empty($users)) {
                return false;
            }
            
            // Criar contexto
            $context = [
                'system_notification' => true,
                'sent_to_roles' => $roles
            ];
            
            // Canais de entrega
            $channels = ['database'];
            
            // Criar notificação para cada usuário
            foreach ($users as $user) {
                $this->createNotification($user['id'], $title, $message, $type, $context, $channels);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao criar notificação de sistema: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria notificação de alerta de performance com base em métricas coletadas
     * 
     * @param string $metric Nome da métrica
     * @param float $value Valor atual da métrica
     * @param string $component Nome do componente monitorado
     * @param array $recipientRoles Array de roles que devem receber o alerta
     * @return bool Sucesso da operação
     */
    public function createPerformanceAlert($metric, $value, $component, $recipientRoles = ['admin']) {
        try {
            // Validar parâmetros
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            $component = $this->validateString($component, ['maxLength' => 255, 'required' => true]);
            
            // Verificar threshold para esta métrica
            $threshold = $this->thresholds->getThresholdForMetric($metric);
            if ($threshold === null) {
                error_log("Nenhum threshold definido para a métrica {$metric}");
                return false;
            }
            
            // Verificar se o valor excede o threshold
            if (!$this->thresholds->isThresholdExceeded($metric, $value)) {
                // Valor está dentro dos limites aceitáveis
                return true;
            }
            
            // Determinar severidade com base na diferença percentual
            $severity = $this->thresholds->determineSeverity($metric, $value);
            
            // Construir título e mensagem
            $title = "Alerta de Performance: {$component}";
            $message = "A métrica '{$metric}' excedeu o limite aceitável. Valor atual: {$value}, Limite: {$threshold['value']}.";
            
            // Adicionar detalhes com base na severidade
            if ($severity === 'critical') {
                $title = "CRÍTICO: " . $title;
                $message .= " Isso requer atenção imediata!";
            } elseif ($severity === 'high') {
                $message .= " Este é um problema de alta prioridade.";
            }
            
            // Criar contexto
            $context = [
                'metric' => $metric,
                'value' => $value,
                'threshold' => $threshold['value'],
                'component' => $component,
                'severity' => $severity,
                'percent_exceeded' => $this->thresholds->calculatePercentExceeded($metric, $value),
                'timestamp' => time()
            ];
            
            // Determinar canais baseados na severidade
            $channels = ['database', 'dashboard'];
            
            if ($severity === 'critical' || $severity === 'high') {
                $channels[] = 'push';
                $channels[] = 'email';
            } elseif ($severity === 'medium') {
                $channels[] = 'push';
            }
            
            // Buscar usuários com as roles especificadas
            $placeholders = implode(',', array_fill(0, count($recipientRoles), '?'));
            $sql = "SELECT id FROM users WHERE role IN ({$placeholders})";
            $users = $this->db->fetchAll($sql, $recipientRoles);
            
            if (empty($users)) {
                error_log("Nenhum usuário encontrado com as roles especificadas para o alerta de performance");
                return false;
            }
            
            // Criar notificação para cada usuário
            foreach ($users as $user) {
                $this->createNotification(
                    $user['id'],
                    $title,
                    $message,
                    'performance',
                    $context,
                    $channels
                );
            }
            
            // Registrar o alerta no log de performance
            $this->logPerformanceAlert($metric, $value, $component, $severity, $context);
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao criar alerta de performance: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra um alerta de performance no log específico
     * 
     * @param string $metric Nome da métrica
     * @param float $value Valor atual da métrica
     * @param string $component Nome do componente monitorado
     * @param string $severity Nível de severidade
     * @param array $context Dados adicionais de contexto
     * @return bool Sucesso da operação
     */
    private function logPerformanceAlert($metric, $value, $component, $severity, $context) {
        try {
            $sql = "INSERT INTO performance_alerts_log 
                    (metric, value, component, severity, context, created_at) 
                    VALUES 
                    (:metric, :value, :component, :severity, :context, NOW())";
            
            $params = [
                ':metric' => $metric,
                ':value' => $value,
                ':component' => $component,
                ':severity' => $severity,
                ':context' => json_encode($context)
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar alerta de performance no log: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o histórico de alertas de performance de um componente
     * 
     * @param string $component Nome do componente
     * @param int $limit Limite de resultados
     * @param int $offset Offset para paginação
     * @return array Histórico de alertas
     */
    public function getPerformanceAlertHistory($component = null, $limit = 50, $offset = 0) {
        try {
            $limit = max(1, min(200, (int)$limit));
            $offset = max(0, (int)$offset);
            
            $sql = "SELECT id, metric, value, component, severity, context, created_at 
                    FROM performance_alerts_log";
            
            $params = [];
            
            if ($component !== null) {
                $component = $this->validateString($component, ['maxLength' => 255]);
                $sql .= " WHERE component = :component";
                $params[':component'] = $component;
            }
            
            $sql .= " ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
            
            $alerts = $this->db->fetchAll($sql, $params);
            
            // Processar contexto JSON para cada alerta
            foreach ($alerts as &$alert) {
                if (isset($alert['context']) && !empty($alert['context'])) {
                    $alert['context'] = json_decode($alert['context'], true);
                } else {
                    $alert['context'] = null;
                }
            }
            
            return $alerts;
        } catch (Exception $e) {
            error_log('Erro ao obter histórico de alertas de performance: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registra métricas de performance para monitoramento
     * 
     * @param string $component Nome do componente
     * @param array $metrics Array associativo de métricas (chave => valor)
     * @return bool Sucesso da operação
     */
    public function recordPerformanceMetrics($component, $metrics) {
        try {
            if (empty($metrics) || !is_array($metrics)) {
                return false;
            }
            
            $component = $this->validateString($component, ['maxLength' => 255, 'required' => true]);
            
            $timestamp = time();
            $sql = "INSERT INTO performance_metrics 
                    (component, metric_name, metric_value, timestamp) 
                    VALUES 
                    (:component, :metric_name, :metric_value, :timestamp)";
            
            // Verificar cada métrica contra os thresholds e registrar
            foreach ($metrics as $metric => $value) {
                $metricName = $this->validateString($metric, ['maxLength' => 255]);
                
                $params = [
                    ':component' => $component,
                    ':metric_name' => $metricName,
                    ':metric_value' => $value,
                    ':timestamp' => $timestamp
                ];
                
                $this->db->execute($sql, $params);
                
                // Verificar se deve gerar um alerta
                if ($this->thresholds->shouldAlert($metricName, $value)) {
                    $this->createPerformanceAlert($metricName, $value, $component);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao registrar métricas de performance: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém as métricas de performance mais recentes de um componente
     * 
     * @param string $component Nome do componente
     * @param int $timeframe Período em segundos para buscar métricas (padrão: 1 hora)
     * @return array Métricas de performance
     */
    public function getLatestPerformanceMetrics($component, $timeframe = 3600) {
        try {
            $component = $this->validateString($component, ['maxLength' => 255, 'required' => true]);
            $timeframe = max(60, min(86400, (int)$timeframe)); // Entre 1 minuto e 24 horas
            
            $startTime = time() - $timeframe;
            
            $sql = "SELECT metric_name, metric_value, timestamp
                    FROM performance_metrics
                    WHERE component = :component AND timestamp >= :start_time
                    ORDER BY timestamp DESC";
            
            $params = [
                ':component' => $component,
                ':start_time' => $startTime
            ];
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de performance recentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atualiza o threshold para uma métrica específica
     * 
     * @param string $metric Nome da métrica
     * @param float $threshold Novo valor de threshold
     * @param string $operator Operador de comparação ('>', '<', '>=', '<=', '==')
     * @return bool Sucesso da operação
     */
    public function updateMetricThreshold($metric, $threshold, $operator = '>') {
        try {
            return $this->thresholds->updateThreshold($metric, $threshold, $operator);
        } catch (Exception $e) {
            error_log('Erro ao atualizar threshold de métrica: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém os alertas de performance ativos (não resolvidos)
     * 
     * @param string $severity Filtrar por severidade (null para todos)
     * @return array Alertas ativos
     */
    public function getActivePerformanceAlerts($severity = null) {
        try {
            $sql = "SELECT n.id, n.title, n.message, n.created_at, pd.metric, pd.value, 
                           pd.threshold, pd.component, pd.severity
                    FROM notifications n
                    JOIN performance_dashboard pd ON n.id = pd.notification_id
                    WHERE n.type = 'performance' AND pd.resolved = 0";
            
            $params = [];
            
            if ($severity !== null && in_array($severity, self::$validSeverityLevels)) {
                $sql .= " AND pd.severity = :severity";
                $params[':severity'] = $severity;
            }
            
            $sql .= " ORDER BY 
                      CASE pd.severity 
                        WHEN 'critical' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                      END, 
                      n.created_at DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter alertas de performance ativos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marca um alerta de performance como resolvido
     * 
     * @param int $alertId ID do alerta (ID da notificação)
     * @param int $userId ID do usuário que resolveu
     * @param string $resolution Descrição da resolução
     * @return bool Sucesso da operação
     */
    public function resolvePerformanceAlert($alertId, $userId, $resolution = '') {
        try {
            $alertId = (int)$alertId;
            $userId = (int)$userId;
            $resolution = $this->validateString($resolution, ['maxLength' => 1000]);
            
            // Verificar se o alerta existe e é do tipo performance
            $sql = "SELECT n.id, pd.id as dashboard_id
                    FROM notifications n
                    JOIN performance_dashboard pd ON n.id = pd.notification_id
                    WHERE n.id = :alert_id AND n.type = 'performance' AND pd.resolved = 0";
            
            $params = [':alert_id' => $alertId];
            $alert = $this->db->fetchSingle($sql, $params);
            
            if (!$alert) {
                return false;
            }
            
            // Marcar como resolvido
            $sql = "UPDATE performance_dashboard
                    SET resolved = 1, 
                        resolved_at = NOW(), 
                        resolved_by = :user_id, 
                        resolution = :resolution
                    WHERE id = :dashboard_id";
            
            $params = [
                ':dashboard_id' => $alert['dashboard_id'],
                ':user_id' => $userId,
                ':resolution' => $resolution
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao resolver alerta de performance: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma regra de silenciamento para uma métrica específica
     * 
     * @param string $metric Nome da métrica
     * @param string $component Nome do componente (opcional)
     * @param int $duration Duração do silenciamento em segundos
     * @param int $userId ID do usuário que criou a regra
     * @return bool Sucesso da operação
     */
    public function silenceMetric($metric, $component = null, $duration = 3600, $userId = null) {
        try {
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            $duration = max(300, min(86400 * 7, (int)$duration)); // Entre 5 minutos e 7 dias
            
            if ($component !== null) {
                $component = $this->validateString($component, ['maxLength' => 255]);
            }
            
            $expiresAt = time() + $duration;
            
            $sql = "INSERT INTO performance_alert_silencing
                    (metric, component, created_at, expires_at, created_by)
                    VALUES
                    (:metric, :component, NOW(), FROM_UNIXTIME(:expires_at), :created_by)";
            
            $params = [
                ':metric' => $metric,
                ':component' => $component,
                ':expires_at' => $expiresAt,
                ':created_by' => $userId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao criar regra de silenciamento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se uma métrica está silenciada
     * 
     * @param string $metric Nome da métrica
     * @param string $component Nome do componente (opcional)
     * @return bool Verdadeiro se estiver silenciada
     */
    public function isMetricSilenced($metric, $component = null) {
        try {
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            
            $sql = "SELECT id FROM performance_alert_silencing
                    WHERE metric = :metric 
                    AND (component = :component OR component IS NULL)
                    AND expires_at > NOW()";
            
            $params = [
                ':metric' => $metric,
                ':component' => $component
            ];
            
            $result = $this->db->fetchSingle($sql, $params);
            
            return $result !== false;
        } catch (Exception $e) {
            error_log('Erro ao verificar silenciamento de métrica: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove o silenciamento de uma métrica
     * 
     * @param string $metric Nome da métrica
     * @param string $component Nome do componente (opcional)
     * @return bool Sucesso da operação
     */
    public function unsilenceMetric($metric, $component = null) {
        try {
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            
            $sql = "DELETE FROM performance_alert_silencing
                    WHERE metric = :metric";
            
            $params = [':metric' => $metric];
            
            if ($component !== null) {
                $component = $this->validateString($component, ['maxLength' => 255]);
                $sql .= " AND component = :component";
                $params[':component'] = $component;
            }
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao remover silenciamento de métrica: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas agregadas de notificações por período
     * 
     * @param string $period Período de agrupamento (day, week, month)
     * @param int $limit Número de períodos a retornar
     * @return array Estatísticas de notificações
     */
    public function getNotificationStats($period = 'day', $limit = 30) {
        try {
            $allowedPeriods = ['day', 'week', 'month'];
            if (!in_array($period, $allowedPeriods)) {
                $period = 'day';
            }
            
            $limit = max(1, min(90, (int)$limit));
            
            $groupBy = '';
            switch ($period) {
                case 'day':
                    $groupBy = "DATE_FORMAT(created_at, '%Y-%m-%d')";
                    break;
                case 'week':
                    $groupBy = "YEARWEEK(created_at)";
                    break;
                case 'month':
                    $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
                    break;
            }
            
            $sql = "SELECT 
                      {$groupBy} as period,
                      COUNT(*) as total,
                      SUM(CASE WHEN type = 'performance' THEN 1 ELSE 0 END) as performance,
                      SUM(CASE WHEN type = 'info' THEN 1 ELSE 0 END) as info,
                      SUM(CASE WHEN type = 'warning' THEN 1 ELSE 0 END) as warning,
                      SUM(CASE WHEN type = 'success' THEN 1 ELSE 0 END) as success,
                      SUM(CASE WHEN type = 'error' THEN 1 ELSE 0 END) as error
                    FROM notifications
                    GROUP BY period
                    ORDER BY period DESC
                    LIMIT :limit";
            
            $params = [':limit' => $limit];
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de notificações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica e limpa notificações expiradas
     * 
     * @param int $maxAge Idade máxima em dias para manter notificações
     * @return int Número de notificações removidas
     */
    public function cleanupOldNotifications($maxAge = 90) {
        try {
            $maxAge = max(7, min(365, (int)$maxAge)); // Entre 7 dias e 1 ano
            
            // Identificar notificações antigas para backup (opcional)
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$maxAge} days"));
            
            // Excluir notificações antigas
            $sql = "DELETE FROM notifications
                    WHERE created_at < :cutoff_date
                    AND (type != 'performance' OR (
                        type = 'performance' AND id IN (
                            SELECT notification_id 
                            FROM performance_dashboard 
                            WHERE resolved = 1
                        )
                    ))";
            
            $params = [':cutoff_date' => $cutoffDate];
            
            $this->db->execute($sql, $params);
            
            $affectedRows = $this->db->getAffectedRows();
            
            // Registrar a limpeza
            error_log("Limpeza de notificações: {$affectedRows} notificações antigas removidas");
            
            return $affectedRows;
        } catch (Exception $e) {
            error_log('Erro ao limpar notificações antigas: ' . $e->getMessage());
            return 0;
        }
    }
}
