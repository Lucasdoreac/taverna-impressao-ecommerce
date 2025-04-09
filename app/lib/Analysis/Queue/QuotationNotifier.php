<?php
/**
 * QuotationNotifier
 * 
 * Sistema de notificação para o processamento assíncrono de cotações.
 * Responsável por notificar usuários quando suas cotações assíncronas são concluídas,
 * utilizando múltiplos canais como notificações do sistema, e-mails, etc.
 * 
 * Este componente implementa os guardrails de segurança da Taverna da Impressão 3D,
 * incluindo validação rigorosa de entrada, sanitização de saída e verificação de
 * permissões antes de enviar notificações.
 * 
 * @package App\Lib\Analysis\Queue
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */

require_once __DIR__ . '/../../Security/InputValidationTrait.php';
require_once __DIR__ . '/../../Notification/NotificationManager.php';
require_once __DIR__ . '/QuotationQueue.php';

class QuotationNotifier {
    use InputValidationTrait;
    
    /**
     * Tipos de notificação suportados
     * @var array
     */
    public const NOTIFICATION_TYPES = ['email', 'system', 'none'];
    
    /**
     * Instância do banco de dados
     * @var PDO
     */
    private $db;
    
    /**
     * Instância do gerenciador de notificações
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * Configurações do notificador
     * @var array
     */
    private $config;
    
    /**
     * Construtor
     * 
     * @param array $config Configurações opcionais
     */
    public function __construct(array $config = []) {
        $this->db = Database::getInstance();
        $this->notificationManager = new NotificationManager();
        
        // Configurações padrão
        $defaultConfig = [
            'default_type' => 'system',
            'email_template' => 'quotation_completed',
            'debug_mode' => false,
            'max_retries' => 3,
            'retry_delay' => 300 // 5 minutos
        ];
        
        $this->config = array_merge($defaultConfig, $config);
    }
    
    /**
     * Envia notificação de conclusão de uma cotação
     * 
     * @param array $task Dados da tarefa
     * @param array $result Resultado da cotação
     * @return bool Sucesso da operação
     */
    public function sendCompletionNotification(array $task, array $result): bool {
        try {
            // Verificar se a tarefa existe e é válida
            if (empty($task['task_id'])) {
                throw new Exception('ID de tarefa inválido');
            }
            
            // Determinar tipo de notificação
            $notificationType = $this->validateNotificationType(
                $task['notification_type'] ?? $this->config['default_type']
            );
            
            // Se tipo for "none", não enviar notificação
            if ($notificationType === 'none') {
                $this->log("Notificação desativada para tarefa {$task['task_id']}");
                return true;
            }
            
            // Obter dados do usuário (se aplicável)
            $userData = null;
            if (!empty($task['user_id'])) {
                $userData = $this->getUserData($task['user_id']);
            }
            
            // Preparar dados da notificação
            $notificationData = $this->prepareNotificationData($task, $result, $userData);
            
            // Enviar notificação com base no tipo
            switch ($notificationType) {
                case 'email':
                    return $this->sendEmailNotification($notificationData);
                    
                case 'system':
                default:
                    return $this->sendSystemNotification($notificationData);
            }
        } catch (Exception $e) {
            $this->log("Erro ao enviar notificação de conclusão: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Envia notificação de erro
     * 
     * @param array $task Dados da tarefa
     * @param string $errorMessage Mensagem de erro
     * @return bool Sucesso da operação
     */
    public function sendErrorNotification(array $task, string $errorMessage): bool {
        try {
            // Verificar se a tarefa existe e é válida
            if (empty($task['task_id'])) {
                throw new Exception('ID de tarefa inválido');
            }
            
            // Determinar tipo de notificação
            $notificationType = $this->validateNotificationType(
                $task['notification_type'] ?? $this->config['default_type']
            );
            
            // Se tipo for "none", não enviar notificação
            if ($notificationType === 'none') {
                $this->log("Notificação desativada para tarefa {$task['task_id']}");
                return true;
            }
            
            // Obter dados do usuário (se aplicável)
            $userData = null;
            if (!empty($task['user_id'])) {
                $userData = $this->getUserData($task['user_id']);
            }
            
            // Preparar dados da notificação de erro
            $notificationData = $this->prepareErrorNotificationData($task, $errorMessage, $userData);
            
            // Enviar notificação com base no tipo
            switch ($notificationType) {
                case 'email':
                    return $this->sendEmailNotification($notificationData, true);
                    
                case 'system':
                default:
                    return $this->sendSystemNotification($notificationData, true);
            }
        } catch (Exception $e) {
            $this->log("Erro ao enviar notificação de erro: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Prepara os dados para a notificação de conclusão
     * 
     * @param array $task Dados da tarefa
     * @param array $result Resultado da cotação
     * @param array|null $userData Dados do usuário
     * @return array Dados formatados para notificação
     */
    private function prepareNotificationData(array $task, array $result, ?array $userData): array {
        // Obter informações do modelo (se disponível)
        $modelInfo = null;
        if (!empty($task['model_id'])) {
            $modelInfo = $this->getModelInfo($task['model_id']);
        }
        
        // Formatar valores monetários
        $totalCost = number_format($result['total_cost'] ?? 0, 2, ',', '.');
        $materialCost = number_format($result['material_cost'] ?? 0, 2, ',', '.');
        $printingCost = number_format($result['printing_cost'] ?? 0, 2, ',', '.');
        
        // Formatar tempo de impressão estimado
        $printTimeMinutes = $result['estimated_print_time_minutes'] ?? 0;
        $printTimeFormatted = $this->formatTime($printTimeMinutes);
        
        // Formatar pontuação de complexidade
        $complexityScore = $result['complexity_score'] ?? 0;
        $complexityText = $this->getComplexityText($complexityScore);
        
        // Sanitizar todos os dados para uso seguro em notificações
        return [
            'subject' => 'Cotação Concluída - Taverna da Impressão 3D',
            'task_id' => htmlspecialchars($task['task_id'], ENT_QUOTES, 'UTF-8'),
            'user' => $userData ? [
                'id' => intval($userData['id']),
                'name' => htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($userData['email'], FILTER_SANITIZE_EMAIL)
            ] : null,
            'model' => $modelInfo ? [
                'id' => intval($modelInfo['id']),
                'name' => htmlspecialchars($modelInfo['original_name'], ENT_QUOTES, 'UTF-8'),
                'preview_url' => $this->getModelPreviewUrl($modelInfo['id'])
            ] : [
                'name' => !empty($task['file_path']) ? basename($task['file_path']) : 'Modelo personalizado'
            ],
            'quotation' => [
                'total_cost' => $totalCost,
                'material_cost' => $materialCost,
                'printing_cost' => $printingCost,
                'print_time' => $printTimeFormatted,
                'complexity' => $complexityText,
                'complexity_score' => $complexityScore,
                'is_estimated' => !empty($result['is_estimated']),
                'material' => htmlspecialchars($result['material'] ?? 'pla', ENT_QUOTES, 'UTF-8')
            ],
            'view_url' => $this->generateQuotationUrl($task['task_id']),
            'checkout_url' => $this->generateCheckoutUrl($task['task_id']),
            'timestamp' => date('d/m/Y H:i:s'),
            'is_error' => false
        ];
    }
    
    /**
     * Prepara os dados para a notificação de erro
     * 
     * @param array $task Dados da tarefa
     * @param string $errorMessage Mensagem de erro
     * @param array|null $userData Dados do usuário
     * @return array Dados formatados para notificação
     */
    private function prepareErrorNotificationData(array $task, string $errorMessage, ?array $userData): array {
        // Obter informações do modelo (se disponível)
        $modelInfo = null;
        if (!empty($task['model_id'])) {
            $modelInfo = $this->getModelInfo($task['model_id']);
        }
        
        // Sanitizar a mensagem de erro (remover informações técnicas detalhadas para o usuário)
        $userFriendlyError = $this->sanitizeErrorMessage($errorMessage);
        
        // Sanitizar todos os dados para uso seguro em notificações
        return [
            'subject' => 'Problema na Cotação - Taverna da Impressão 3D',
            'task_id' => htmlspecialchars($task['task_id'], ENT_QUOTES, 'UTF-8'),
            'user' => $userData ? [
                'id' => intval($userData['id']),
                'name' => htmlspecialchars($userData['name'], ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($userData['email'], FILTER_SANITIZE_EMAIL)
            ] : null,
            'model' => $modelInfo ? [
                'id' => intval($modelInfo['id']),
                'name' => htmlspecialchars($modelInfo['original_name'], ENT_QUOTES, 'UTF-8')
            ] : [
                'name' => !empty($task['file_path']) ? basename($task['file_path']) : 'Modelo personalizado'
            ],
            'error' => [
                'message' => htmlspecialchars($userFriendlyError, ENT_QUOTES, 'UTF-8')
            ],
            'help_url' => '/ajuda/cotacao',
            'support_email' => 'suporte@tavernada3d.com.br',
            'timestamp' => date('d/m/Y H:i:s'),
            'is_error' => true
        ];
    }
    
    /**
     * Envia notificação por e-mail
     * 
     * @param array $data Dados da notificação
     * @param bool $isError Se é uma notificação de erro
     * @return bool Sucesso da operação
     */
    private function sendEmailNotification(array $data, bool $isError = false): bool {
        try {
            // Verificar se temos dados de usuário
            if (empty($data['user']) || empty($data['user']['email'])) {
                throw new Exception('Dados de usuário insuficientes para enviar e-mail');
            }
            
            // Selecionar template adequado
            $template = $isError ? 'quotation_error' : $this->config['email_template'];
            
            // Enviar e-mail através do gerenciador de notificações
            $success = $this->notificationManager->sendEmail(
                $data['user']['email'],
                $data['subject'],
                $template,
                $data
            );
            
            if ($success) {
                $this->log("E-mail enviado para {$data['user']['email']} sobre a tarefa {$data['task_id']}");
            } else {
                $this->log("Falha ao enviar e-mail para {$data['user']['email']}", true);
            }
            
            // Registrar o envio da notificação
            $this->recordNotification($data['task_id'], 'email', $success, $data['user']['id'] ?? null);
            
            return $success;
        } catch (Exception $e) {
            $this->log("Erro ao enviar notificação por e-mail: " . $e->getMessage(), true);
            
            // Registrar a falha
            $this->recordNotification($data['task_id'] ?? 'unknown', 'email', false, $data['user']['id'] ?? null, $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Envia notificação pelo sistema
     * 
     * @param array $data Dados da notificação
     * @param bool $isError Se é uma notificação de erro
     * @return bool Sucesso da operação
     */
    private function sendSystemNotification(array $data, bool $isError = false): bool {
        try {
            // Verificar se temos ID de usuário
            if (empty($data['user']) || empty($data['user']['id'])) {
                throw new Exception('ID de usuário não disponível para notificação do sistema');
            }
            
            // Preparar conteúdo da notificação
            $title = $isError ? 'Problema na Cotação' : 'Cotação Concluída';
            $message = $isError 
                ? "Encontramos um problema ao processar sua cotação para \"{$data['model']['name']}\". " . $data['error']['message']
                : "Sua cotação para \"{$data['model']['name']}\" foi concluída. Valor total: R$ {$data['quotation']['total_cost']}";
            
            $linkText = $isError ? 'Ver detalhes' : 'Ver cotação';
            $linkUrl = $isError ? $data['help_url'] : $data['view_url'];
            
            // Tipo de notificação
            $type = $isError ? 'warning' : 'success';
            
            // Enviar notificação através do gerenciador de notificações
            $success = $this->notificationManager->sendUserNotification(
                $data['user']['id'],
                $title,
                $message,
                $linkUrl,
                $linkText,
                $type
            );
            
            if ($success) {
                $this->log("Notificação enviada para usuário ID {$data['user']['id']} sobre a tarefa {$data['task_id']}");
            } else {
                $this->log("Falha ao enviar notificação para usuário ID {$data['user']['id']}", true);
            }
            
            // Registrar o envio da notificação
            $this->recordNotification($data['task_id'], 'system', $success, $data['user']['id']);
            
            return $success;
        } catch (Exception $e) {
            $this->log("Erro ao enviar notificação do sistema: " . $e->getMessage(), true);
            
            // Registrar a falha
            $this->recordNotification($data['task_id'] ?? 'unknown', 'system', false, $data['user']['id'] ?? null, $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Obtém dados do usuário
     * 
     * @param int $userId ID do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    private function getUserData(int $userId): ?array {
        try {
            $sql = "SELECT id, name, email, role FROM users WHERE id = :id";
            $user = $this->db->fetchSingle($sql, [':id' => $userId]);
            
            return $user ?: null;
        } catch (Exception $e) {
            $this->log("Erro ao obter dados do usuário: " . $e->getMessage(), true);
            return null;
        }
    }
    
    /**
     * Obtém informações sobre um modelo
     * 
     * @param int $modelId ID do modelo
     * @return array|null Informações do modelo ou null se não encontrado
     */
    private function getModelInfo(int $modelId): ?array {
        try {
            $sql = "SELECT id, user_id, original_name, file_name, status, created_at 
                   FROM customer_models 
                   WHERE id = :id";
            
            $model = $this->db->fetchSingle($sql, [':id' => $modelId]);
            
            return $model ?: null;
        } catch (Exception $e) {
            $this->log("Erro ao obter informações do modelo: " . $e->getMessage(), true);
            return null;
        }
    }
    
    /**
     * Registra uma notificação enviada
     * 
     * @param string $taskId ID da tarefa
     * @param string $channel Canal de notificação
     * @param bool $success Sucesso do envio
     * @param int|null $userId ID do usuário (opcional)
     * @param string|null $errorMessage Mensagem de erro (opcional)
     * @return bool Sucesso da operação
     */
    private function recordNotification(
        string $taskId, 
        string $channel, 
        bool $success, 
        ?int $userId = null, 
        ?string $errorMessage = null
    ): bool {
        try {
            // Verificar se a tabela existe
            $this->ensureNotificationLogTableExists();
            
            // Inserir registro
            $sql = "INSERT INTO quotation_notification_log 
                    (task_id, channel, success, user_id, error_message) 
                    VALUES (:task_id, :channel, :success, :user_id, :error_message)";
            
            $params = [
                ':task_id' => $taskId,
                ':channel' => $channel,
                ':success' => $success ? 1 : 0,
                ':user_id' => $userId,
                ':error_message' => $errorMessage
            ];
            
            $this->db->execute($sql, $params);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao registrar notificação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Garante que a tabela de log de notificações existe
     */
    private function ensureNotificationLogTableExists(): void {
        try {
            // Verificar se a tabela existe
            $sql = "SHOW TABLES LIKE 'quotation_notification_log'";
            $result = $this->db->fetchAll($sql);
            
            if (empty($result)) {
                // Criar a tabela
                $sql = "CREATE TABLE quotation_notification_log (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    task_id VARCHAR(64) NOT NULL,
                    channel VARCHAR(20) NOT NULL,
                    success TINYINT(1) NOT NULL DEFAULT 0,
                    user_id INT UNSIGNED,
                    error_message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (task_id),
                    INDEX (channel),
                    INDEX (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                $this->db->execute($sql);
            }
        } catch (Exception $e) {
            // Apenas registrar erro, não interromper execução
            error_log('Erro ao verificar/criar tabela de log de notificações: ' . $e->getMessage());
        }
    }
    
    /**
     * Valida o tipo de notificação
     * 
     * @param string $type Tipo de notificação
     * @return string Tipo validado
     */
    private function validateNotificationType(string $type): string {
        if (!in_array($type, self::NOTIFICATION_TYPES)) {
            return 'system';
        }
        
        return $type;
    }
    
    /**
     * Gera URL para visualização da cotação
     * 
     * @param string $taskId ID da tarefa
     * @return string URL completa
     */
    private function generateQuotationUrl(string $taskId): string {
        return '/cotacao/visualizar/' . urlencode($taskId);
    }
    
    /**
     * Gera URL para finalizar a compra
     * 
     * @param string $taskId ID da tarefa
     * @return string URL completa
     */
    private function generateCheckoutUrl(string $taskId): string {
        return '/cotacao/checkout/' . urlencode($taskId);
    }
    
    /**
     * Obtém URL de visualização prévia do modelo
     * 
     * @param int $modelId ID do modelo
     * @return string URL completa
     */
    private function getModelPreviewUrl(int $modelId): string {
        return '/modelos/visualizar/' . $modelId;
    }
    
    /**
     * Formata o tempo de impressão em formato legível
     * 
     * @param int $minutes Tempo em minutos
     * @return string Tempo formatado
     */
    private function formatTime(int $minutes): string {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
        }
        
        return $mins . ' minutos';
    }
    
    /**
     * Converte pontuação de complexidade em texto descritivo
     * 
     * @param int $score Pontuação de complexidade (0-100)
     * @return string Descrição textual
     */
    private function getComplexityText(int $score): string {
        if ($score >= 80) {
            return 'Muito Alta';
        } elseif ($score >= 60) {
            return 'Alta';
        } elseif ($score >= 40) {
            return 'Média';
        } elseif ($score >= 20) {
            return 'Baixa';
        } else {
            return 'Muito Baixa';
        }
    }
    
    /**
     * Sanitiza mensagem de erro para apresentação ao usuário
     * 
     * @param string $errorMessage Mensagem original de erro
     * @return string Mensagem sanitizada
     */
    private function sanitizeErrorMessage(string $errorMessage): string {
        // Remover stack traces ou informações técnicas detalhadas
        $patterns = [
            '/in\s+\/.*?\.php\s+on\s+line\s+\d+/' => '',  // Remove path references
            '/Stack trace:(.|\n)*$/' => '',               // Remove stack traces
            '/\[.*?\]/' => '',                            // Remove square brackets content
            '/Exception: /' => '',                        // Remove "Exception:" prefix
            '/Error: /' => '',                            // Remove "Error:" prefix
        ];
        
        $sanitized = preg_replace(array_keys($patterns), array_values($patterns), $errorMessage);
        $sanitized = trim($sanitized);
        
        // Se a mensagem ficar muito curta ou vazia após sanitização, usar mensagem genérica
        if (strlen($sanitized) < 10) {
            return 'Ocorreu um problema durante o processamento do seu modelo. Nossa equipe técnica foi notificada.';
        }
        
        return $sanitized;
    }
    
    /**
     * Registra uma mensagem de log
     * 
     * @param string $message Mensagem a ser registrada
     * @param bool $isError Se é um erro
     */
    private function log(string $message, bool $isError = false): void {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isError ? '[ERRO]' : '[INFO]';
        
        $logMessage = "[{$timestamp}] {$prefix} [QuotationNotifier] {$message}";
        
        // Se em modo de depuração, exibir na saída
        if ($this->config['debug_mode']) {
            echo $logMessage . PHP_EOL;
        }
        
        // Registrar no log do sistema
        error_log($logMessage);
    }
}
