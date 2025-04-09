<?php
/**
 * PushNotificationProvider - Provider para notificações push
 * 
 * Implementa o envio de notificações push através do Web Push Protocol,
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Lib\Notification
 * @version 1.0.0
 * @author Taverna da Impressão
 */
class PushNotificationProvider {
    /**
     * Conexão PDO para o banco de dados
     * 
     * @var \PDO
     */
    private $pdo;
    
    /**
     * Configurações do provider
     * 
     * @var array
     */
    private $config;
    
    /**
     * Construtor
     * 
     * @param \PDO $pdo Conexão com o banco de dados
     * @param array $config Configurações do provider
     */
    public function __construct(\PDO $pdo = null, array $config = []) {
        $this->pdo = $pdo;
        $this->config = $config;
    }
    
    /**
     * Envia uma notificação push
     * 
     * @param array $notification Dados da notificação
     * @return bool Sucesso na operação
     */
    public function send($notification) {
        // Verificar se temos a conexão com o banco de dados
        if (!$this->pdo) {
            error_log("Conexão com banco de dados não disponível para envio de push");
            return false;
        }
        
        try {
            // Obter endpoints de push do usuário
            $subscriptions = $this->getUserPushSubscriptions($notification['user_id']);
            
            if (empty($subscriptions)) {
                // Usuário não tem endpoints push registrados
                return false;
            }
            
            // Preparar payload da notificação
            $payload = json_encode([
                'title' => $notification['title'],
                'body' => $notification['message'],
                'icon' => $this->config['icon'] ?? '/images/logo.png',
                'badge' => '/images/badge.png',
                'tag' => $notification['type'],
                'data' => [
                    'url' => $notification['link'] ?? '/',
                    'notification_id' => null, // Será preenchido ao salvar
                    'timestamp' => time()
                ]
            ]);
            
            // Enviar para cada subscription
            $successCount = 0;
            foreach ($subscriptions as $subscription) {
                $result = $this->sendPushToEndpoint(
                    $subscription['endpoint'],
                    $subscription['p256dh_key'],
                    $subscription['auth_key'],
                    $payload
                );
                
                if ($result) {
                    $successCount++;
                } else {
                    // Se falhar, verificar se é um erro de expiração/invalidade
                    $this->handleFailedPush($subscription['id'], $subscription['endpoint']);
                }
            }
            
            return $successCount > 0;
            
        } catch (\Exception $e) {
            error_log("Erro ao enviar notificação push: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém as subscrições push de um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Array de subscriptions
     */
    private function getUserPushSubscriptions($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, endpoint, p256dh_key, auth_key, created_at
                FROM push_subscriptions
                WHERE user_id = ? AND active = 1
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erro ao buscar subscrições push: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Envia notificação push para um endpoint específico
     * 
     * @param string $endpoint URL do endpoint
     * @param string $p256dhKey Chave pública P-256
     * @param string $authKey Chave de autenticação
     * @param string $payload Payload da notificação
     * @return bool Sucesso no envio
     */
    private function sendPushToEndpoint($endpoint, $p256dhKey, $authKey, $payload) {
        // *** NOTA DE IMPLEMENTAÇÃO ***
        // Esta é uma implementação simulada para fins de demonstração
        // Em um ambiente de produção, utilizaríamos uma biblioteca como 
        // web-push-php/web-push para manipular o protocolo Web Push
        
        // Simulação básica de envio
        $endpointParts = parse_url($endpoint);
        if ($endpointParts === false || !isset($endpointParts['host'])) {
            error_log("Endpoint de push inválido: {$endpoint}");
            return false;
        }
        
        try {
            // Validar tamanho do payload
            $payloadLength = strlen($payload);
            if ($payloadLength > 4096) { // Limite recomendado
                error_log("Payload de push muito grande: {$payloadLength} bytes");
                return false;
            }
            
            // Logs para depuração
            error_log("Simulando envio de push para endpoint: {$endpoint}");
            error_log("Payload: {$payload}");
            
            // Em implementação real, aqui realizaríamos:
            // 1. Criptografia do payload usando ECDH e HKDF com as chaves fornecidas
            // 2. Adição de cabeçalhos necessários (TTL, Urgency, Topic)
            // 3. Execução da requisição POST para o endpoint
            // 4. Interpretação da resposta (201 = sucesso, 404/410 = endpoint inválido)
            
            // Simular probabilidade alta de sucesso
            return (mt_rand(1, 100) <= 95);
            
        } catch (\Exception $e) {
            error_log("Erro no envio de push: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gerencia falhas no envio de push
     * 
     * @param int $subscriptionId ID da subscrição
     * @param string $endpoint Endpoint que falhou
     */
    private function handleFailedPush($subscriptionId, $endpoint) {
        try {
            // Verificar se é a primeira falha ou se já temos falhas registradas
            $stmt = $this->pdo->prepare("
                SELECT failure_count, last_failure_at
                FROM push_subscriptions
                WHERE id = ?
            ");
            
            $stmt->execute([$subscriptionId]);
            $subscription = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$subscription) {
                return;
            }
            
            // Incrementar contador de falhas
            $failureCount = $subscription['failure_count'] + 1;
            
            // Se tiver mais de 3 falhas em sequência, desativar o endpoint
            if ($failureCount >= 3) {
                $stmt = $this->pdo->prepare("
                    UPDATE push_subscriptions
                    SET active = 0, failure_count = ?, last_failure_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([$failureCount, $subscriptionId]);
                error_log("Endpoint de push desativado após falhas: {$endpoint}");
            } else {
                // Apenas atualizar contador de falhas
                $stmt = $this->pdo->prepare("
                    UPDATE push_subscriptions
                    SET failure_count = ?, last_failure_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([$failureCount, $subscriptionId]);
            }
            
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status de subscrição push: " . $e->getMessage());
        }
    }
    
    /**
     * Salva uma nova subscrição push
     * 
     * @param int $userId ID do usuário
     * @param string $endpoint URL do endpoint
     * @param string $p256dhKey Chave pública P-256
     * @param string $authKey Chave de autenticação
     * @param string $userAgent User Agent do cliente
     * @return int|bool ID da subscrição ou false em caso de erro
     */
    public function saveSubscription($userId, $endpoint, $p256dhKey, $authKey, $userAgent = '') {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // Verificar se já existe um registro para este endpoint
            $stmt = $this->pdo->prepare("
                SELECT id, active FROM push_subscriptions
                WHERE user_id = ? AND endpoint = ?
            ");
            
            $stmt->execute([$userId, $endpoint]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existing) {
                if ($existing['active']) {
                    // Já existe e está ativo
                    return $existing['id'];
                } else {
                    // Reativar e atualizar chaves
                    $stmt = $this->pdo->prepare("
                        UPDATE push_subscriptions
                        SET 
                            p256dh_key = ?,
                            auth_key = ?,
                            active = 1,
                            failure_count = 0,
                            last_updated = NOW(),
                            user_agent = ?
                        WHERE id = ?
                    ");
                    
                    $stmt->execute([$p256dhKey, $authKey, $userAgent, $existing['id']]);
                    return $existing['id'];
                }
            }
            
            // Criar novo registro
            $stmt = $this->pdo->prepare("
                INSERT INTO push_subscriptions (
                    user_id, endpoint, p256dh_key, auth_key, active, 
                    failure_count, created_at, last_updated, user_agent
                ) VALUES (
                    ?, ?, ?, ?, 1, 0, NOW(), NOW(), ?
                )
            ");
            
            $stmt->execute([$userId, $endpoint, $p256dhKey, $authKey, $userAgent]);
            return $this->pdo->lastInsertId();
            
        } catch (\PDOException $e) {
            error_log("Erro ao salvar subscrição push: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove uma subscrição push
     * 
     * @param int $userId ID do usuário
     * @param string $endpoint URL do endpoint
     * @return bool Sucesso na operação
     */
    public function removeSubscription($userId, $endpoint) {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE push_subscriptions
                SET active = 0, unsubscribed_at = NOW()
                WHERE user_id = ? AND endpoint = ?
            ");
            
            $stmt->execute([$userId, $endpoint]);
            return $stmt->rowCount() > 0;
            
        } catch (\PDOException $e) {
            error_log("Erro ao remover subscrição push: " . $e->getMessage());
            return false;
        }
    }
}