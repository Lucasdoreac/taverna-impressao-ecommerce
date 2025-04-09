<?php
/**
 * PushService - Serviço de envio de notificações push
 * 
 * Implementa o envio de notificações push via WebSockets e Service Workers.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Notification
 * @version    1.0.0
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';

class PushService {
    use InputValidationTrait;
    
    /**
     * Conexão com o banco de dados
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Chave privada VAPID para assinatura de mensagens
     * Em produção, isto seria armazenado de forma segura e não no código
     * 
     * @var string
     */
    private $vapidPrivateKey;
    
    /**
     * Chave pública VAPID para identificação do servidor
     * 
     * @var string
     */
    private $vapidPublicKey;
    
    /**
     * Contato para notificações VAPID
     * 
     * @var string
     */
    private $vapidContact;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Em produção, estas chaves seriam carregadas de um armazenamento seguro
        // Estas são apenas para demonstração e não devem ser usadas em produção
        $this->vapidPrivateKey = 'CHAVE_PRIVADA_SIMULADA_APENAS_PARA_DEMONSTRACAO';
        $this->vapidPublicKey = 'CHAVE_PUBLICA_SIMULADA_APENAS_PARA_DEMONSTRACAO';
        $this->vapidContact = 'mailto:admin@tavernaimpressao3d.com';
    }
    
    /**
     * Envia notificação push para um usuário
     * 
     * @param int $userId ID do usuário
     * @param array $payload Dados da notificação
     * @return bool Sucesso da operação
     */
    public function sendNotification($userId, $payload) {
        try {
            // Validar userId
            $userId = (int)$userId;
            
            // Obter as inscrições do usuário
            $subscriptions = $this->getUserSubscriptions($userId);
            
            if (empty($subscriptions)) {
                // Usuário não tem inscrições push
                return false;
            }
            
            // Preparar payload
            $sanitizedPayload = $this->sanitizePayload($payload);
            $encodedPayload = json_encode($sanitizedPayload);
            
            // Verificar tamanho do payload (4KB é o limite recomendado)
            if (strlen($encodedPayload) > 4000) {
                // Reduzir tamanho do payload
                if (isset($sanitizedPayload['message']) && strlen($sanitizedPayload['message']) > 100) {
                    $sanitizedPayload['message'] = substr($sanitizedPayload['message'], 0, 97) . '...';
                }
                
                // Remover dados de contexto se necessário
                if (isset($sanitizedPayload['context'])) {
                    $sanitizedPayload['context'] = ['note' => 'Context data omitted due to size constraints'];
                }
                
                $encodedPayload = json_encode($sanitizedPayload);
            }
            
            // Registrar sucesso global (será atualizado se alguma entrega falhar)
            $allSuccess = true;
            
            // Enviar notificação para cada inscrição
            foreach ($subscriptions as $subscription) {
                $subscriptionData = json_decode($subscription['subscription_data'], true);
                
                // Em produção, enviaria notificação real via web-push
                // Nesta implementação, simulamos o envio
                $success = $this->simulatePushDelivery($subscriptionData, $encodedPayload);
                
                if (!$success) {
                    $allSuccess = false;
                    
                    // Verificar se a inscrição está expirada ou inválida
                    if ($this->isSubscriptionExpired($subscriptionData)) {
                        $this->deleteSubscription($subscription['id']);
                    }
                }
                
                // Registrar tentativa de envio
                $this->logDeliveryAttempt(
                    $userId, 
                    $subscription['id'], 
                    isset($payload['id']) ? $payload['id'] : null, 
                    $success
                );
            }
            
            return $allSuccess;
        } catch (Exception $e) {
            error_log('Erro ao enviar notificação push: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as inscrições push de um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Lista de inscrições
     */
    private function getUserSubscriptions($userId) {
        try {
            $userId = (int)$userId;
            
            $sql = "SELECT id, subscription_data, created_at, last_used 
                    FROM push_subscriptions 
                    WHERE user_id = :user_id AND active = 1";
            
            $params = [':user_id' => $userId];
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter inscrições push do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sanitiza o payload da notificação
     * 
     * @param array $payload Payload original
     * @return array Payload sanitizado
     */
    private function sanitizePayload($payload) {
        // Garantir campos obrigatórios
        $sanitized = [
            'title' => isset($payload['title']) ? $this->validateString($payload['title'], ['maxLength' => 255]) : 'Nova notificação',
            'message' => isset($payload['message']) ? $this->validateString($payload['message']) : '',
            'timestamp' => time(),
            'icon' => '/assets/images/logo-notification.png'
        ];
        
        // Adicionar tipo se fornecido
        if (isset($payload['type']) && in_array($payload['type'], ['info', 'warning', 'success', 'error'])) {
            $sanitized['type'] = $payload['type'];
        } else {
            $sanitized['type'] = 'info';
        }
        
        // Adicionar URL de redirecionamento
        if (isset($payload['id']) && (int)$payload['id'] > 0) {
            $sanitized['url'] = '/notifications/' . (int)$payload['id'];
        } else {
            $sanitized['url'] = '/notifications';
        }
        
        // Adicionar dados de contexto (limitados)
        if (isset($payload['context']) && is_array($payload['context'])) {
            // Filtrar e limitar contexto
            $context = [];
            
            // Somente incluir campos permitidos no contexto
            $allowedContextFields = ['queue_id', 'model_name', 'priority', 'status'];
            
            foreach ($allowedContextFields as $field) {
                if (isset($payload['context'][$field])) {
                    $context[$field] = $payload['context'][$field];
                }
            }
            
            if (!empty($context)) {
                $sanitized['context'] = $context;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Simula envio de notificação push
     * 
     * Em produção, implementaria a entrega real usando web-push
     * 
     * @param array $subscription Dados da inscrição
     * @param string $payload Payload codificado
     * @return bool Sucesso simulado
     */
    private function simulatePushDelivery($subscription, $payload) {
        // Simulação: 95% de sucesso
        $success = (rand(1, 100) <= 95);
        
        if ($success) {
            // Simular envio bem-sucedido
            // Em produção, usaria biblioteca web-push para envio real
            
            // Exemplo pseudo-código do que seria feito em produção:
            /*
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $this->vapidContact,
                    'publicKey' => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                ]
            ]);
            
            $report = $webPush->sendNotification(
                Subscription::create($subscription),
                $payload
            );
            
            return $report->isSuccess();
            */
        }
        
        return $success;
    }
    
    /**
     * Verifica se uma inscrição está expirada ou inválida
     * 
     * @param array $subscription Dados da inscrição
     * @return bool Verdadeiro se expirada
     */
    private function isSubscriptionExpired($subscription) {
        // Na implementação completa, verificaria a resposta do servidor push
        // Esta é uma simulação simplificada
        return false;
    }
    
    /**
     * Registra tentativa de entrega de notificação
     * 
     * @param int $userId ID do usuário
     * @param int $subscriptionId ID da inscrição
     * @param int|null $notificationId ID da notificação
     * @param bool $success Sucesso da entrega
     * @return bool
     */
    private function logDeliveryAttempt($userId, $subscriptionId, $notificationId, $success) {
        try {
            $sql = "INSERT INTO push_delivery_log 
                    (user_id, subscription_id, notification_id, success, timestamp) 
                    VALUES 
                    (:user_id, :subscription_id, :notification_id, :success, NOW())";
            
            $params = [
                ':user_id' => $userId,
                ':subscription_id' => $subscriptionId,
                ':notification_id' => $notificationId,
                ':success' => $success ? 1 : 0
            ];
            
            $this->db->execute($sql, $params);
            
            if ($success) {
                // Atualizar timestamp de último uso da inscrição
                $sql = "UPDATE push_subscriptions 
                        SET last_used = NOW() 
                        WHERE id = :id";
                
                $params = [':id' => $subscriptionId];
                
                $this->db->execute($sql, $params);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao registrar tentativa de entrega push: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma nova inscrição push
     * 
     * @param int $userId ID do usuário
     * @param array $subscription Dados da inscrição
     * @param string $userAgent User-Agent do cliente
     * @return int|bool ID da inscrição ou false
     */
    public function registerSubscription($userId, $subscription, $userAgent) {
        try {
            // Validar userId
            $userId = (int)$userId;
            
            // Validar dados da inscrição
            if (!isset($subscription['endpoint']) || !is_string($subscription['endpoint'])) {
                throw new Exception('Dados de inscrição inválidos');
            }
            
            // Verificar se já existe inscrição com este endpoint
            $sql = "SELECT id FROM push_subscriptions WHERE endpoint = :endpoint AND user_id = :user_id";
            $params = [
                ':endpoint' => $subscription['endpoint'],
                ':user_id' => $userId
            ];
            
            $existing = $this->db->fetchSingle($sql, $params);
            
            if ($existing) {
                // Atualizar inscrição existente
                $sql = "UPDATE push_subscriptions 
                        SET subscription_data = :data, last_used = NOW(), active = 1, user_agent = :user_agent
                        WHERE id = :id";
                
                $params = [
                    ':data' => json_encode($subscription),
                    ':user_agent' => $this->validateString($userAgent, ['maxLength' => 255]),
                    ':id' => $existing['id']
                ];
                
                $this->db->execute($sql, $params);
                return $existing['id'];
            } else {
                // Criar nova inscrição
                $sql = "INSERT INTO push_subscriptions 
                        (user_id, endpoint, subscription_data, user_agent, created_at, last_used, active) 
                        VALUES 
                        (:user_id, :endpoint, :data, :user_agent, NOW(), NOW(), 1)";
                
                $params = [
                    ':user_id' => $userId,
                    ':endpoint' => $subscription['endpoint'],
                    ':data' => json_encode($subscription),
                    ':user_agent' => $this->validateString($userAgent, ['maxLength' => 255])
                ];
                
                $this->db->execute($sql, $params);
                return $this->db->lastInsertId();
            }
        } catch (Exception $e) {
            error_log('Erro ao registrar inscrição push: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma inscrição push
     * 
     * @param int $subscriptionId ID da inscrição
     * @return bool Sucesso da operação
     */
    public function deleteSubscription($subscriptionId) {
        try {
            $subscriptionId = (int)$subscriptionId;
            
            // Desativar inscrição em vez de excluir (para manter histórico)
            $sql = "UPDATE push_subscriptions SET active = 0 WHERE id = :id";
            $params = [':id' => $subscriptionId];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao excluir inscrição push: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém a chave pública VAPID para registro de clientes
     * 
     * @return string Chave pública VAPID
     */
    public function getVapidPublicKey() {
        return $this->vapidPublicKey;
    }
}
