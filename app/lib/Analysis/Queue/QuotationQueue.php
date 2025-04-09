<?php
/**
 * QuotationQueue
 * 
 * Gerenciador de fila para processamento assíncrono de cotações de modelos 3D complexos.
 * Implementa uma fila persistente que permite o processamento em background sem bloquear
 * a interface do usuário durante análises computacionalmente intensivas.
 * 
 * Este componente segue os guardrails de segurança da Taverna da Impressão 3D,
 * implementando validação rigorosa de entradas, sanitização, e controle de acesso
 * para prevenir ataques de DoS ou exploração da fila.
 *
 * @package App\Lib\Analysis\Queue
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */
 
require_once __DIR__ . '/../../Security/InputValidationTrait.php';
 
class QuotationQueue {
    use InputValidationTrait;
    
    /**
     * Status de tarefa: Enfileirada (aguardando processamento)
     * @var string
     */
    public const STATUS_QUEUED = 'queued';
    
    /**
     * Status de tarefa: Em Processamento
     * @var string
     */
    public const STATUS_PROCESSING = 'processing';
    
    /**
     * Status de tarefa: Concluída com sucesso
     * @var string
     */
    public const STATUS_COMPLETED = 'completed';
    
    /**
     * Status de tarefa: Falhou durante processamento
     * @var string
     */
    public const STATUS_FAILED = 'failed';
    
    /**
     * Status de tarefa: Cancelada pelo usuário ou admin
     * @var string
     */
    public const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Prioridade: Baixa (padrão)
     * @var int
     */
    public const PRIORITY_LOW = 0;
    
    /**
     * Prioridade: Normal
     * @var int
     */
    public const PRIORITY_NORMAL = 5;
    
    /**
     * Prioridade: Alta (urgente)
     * @var int
     */
    public const PRIORITY_HIGH = 10;
    
    /**
     * Tempo máximo de processamento (minutos)
     * @var int
     */
    public const MAX_PROCESSING_TIME = 30;
    
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
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->ensureQueueTableExists();
    }
    
    /**
     * Garante que a tabela de fila existe no banco de dados
     */
    private function ensureQueueTableExists(): void {
        try {
            // Verificar se a tabela existe
            $sql = "SHOW TABLES LIKE 'quotation_queue'";
            $result = $this->db->fetchAll($sql);
            
            if (empty($result)) {
                // Criar a tabela se não existir
                $sql = "CREATE TABLE quotation_queue (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    task_id VARCHAR(64) NOT NULL UNIQUE,
                    user_id INT UNSIGNED,
                    model_id INT UNSIGNED,
                    file_path VARCHAR(255),
                    file_hash VARCHAR(64),
                    parameters TEXT,
                    priority TINYINT UNSIGNED DEFAULT 0,
                    status VARCHAR(20) NOT NULL,
                    progress TINYINT UNSIGNED DEFAULT 0,
                    result_data MEDIUMTEXT,
                    error_message TEXT,
                    notification_type VARCHAR(20) DEFAULT 'system',
                    notification_target VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    started_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    locked_by VARCHAR(64),
                    locked_until TIMESTAMP NULL,
                    INDEX (status),
                    INDEX (user_id),
                    INDEX (priority)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                $this->db->execute($sql);
            }
        } catch (Exception $e) {
            // Registrar erro, mas não interromper execução
            error_log('Erro ao verificar/criar tabela de fila: ' . $e->getMessage());
        }
    }
    
    /**
     * Adiciona uma tarefa à fila de cotação
     * 
     * @param array $taskData Dados da tarefa (model_id ou file_path obrigatório)
     * @param array $parameters Parâmetros de cotação (material, opções, etc.)
     * @param int $priority Prioridade da tarefa
     * @param array $notificationOptions Opções de notificação
     * @return string ID único da tarefa ou false em caso de erro
     */
    public function enqueue(array $taskData, array $parameters = [], int $priority = self::PRIORITY_NORMAL, array $notificationOptions = []): string {
        try {
            // Validar dados da tarefa
            $this->validateTaskData($taskData);
            
            // Gerar ID único para a tarefa
            $taskId = $this->generateTaskId($taskData);
            
            // Preparar dados para inserção
            $data = [
                'task_id' => $taskId,
                'user_id' => isset($taskData['user_id']) ? intval($taskData['user_id']) : null,
                'model_id' => isset($taskData['model_id']) ? intval($taskData['model_id']) : null,
                'file_path' => isset($taskData['file_path']) ? $this->sanitizePath($taskData['file_path']) : null,
                'file_hash' => isset($taskData['file_hash']) ? $taskData['file_hash'] : 
                    (isset($taskData['file_path']) ? md5_file($taskData['file_path']) : null),
                'parameters' => json_encode($parameters),
                'priority' => $this->validatePriority($priority),
                'status' => self::STATUS_QUEUED,
                'progress' => 0,
                'notification_type' => $this->validateNotificationType($notificationOptions['type'] ?? 'system'),
                'notification_target' => $notificationOptions['target'] ?? null
            ];
            
            // Inserir na tabela de fila
            $sql = "INSERT INTO quotation_queue 
                    (task_id, user_id, model_id, file_path, file_hash, parameters, priority, 
                     status, progress, notification_type, notification_target)
                    VALUES 
                    (:task_id, :user_id, :model_id, :file_path, :file_hash, :parameters, :priority,
                     :status, :progress, :notification_type, :notification_target)";
            
            $this->db->execute($sql, $data);
            
            // Registrar atividade
            $this->logActivity('enqueue', $taskId, "Tarefa adicionada à fila. Prioridade: {$priority}");
            
            return $taskId;
        } catch (Exception $e) {
            error_log('Erro ao enfileirar cotação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida os dados da tarefa antes de enfileirar
     * 
     * @param array $taskData Dados da tarefa
     * @throws Exception Se os dados forem inválidos
     */
    private function validateTaskData(array $taskData): void {
        // Verificar se pelo menos model_id ou file_path está presente
        if (empty($taskData['model_id']) && empty($taskData['file_path'])) {
            throw new Exception('É necessário fornecer model_id ou file_path para a cotação');
        }
        
        // Validar model_id
        if (!empty($taskData['model_id'])) {
            $modelId = intval($taskData['model_id']);
            if ($modelId <= 0) {
                throw new Exception('model_id inválido');
            }
            
            // Verificar se o modelo existe e está aprovado
            $sql = "SELECT status FROM customer_models WHERE id = :id";
            $model = $this->db->fetchSingle($sql, [':id' => $modelId]);
            
            if (!$model) {
                throw new Exception('Modelo não encontrado');
            }
            
            if ($model['status'] !== 'approved') {
                throw new Exception('Apenas modelos aprovados podem ser cotados');
            }
        }
        
        // Validar file_path
        if (!empty($taskData['file_path'])) {
            $filePath = $taskData['file_path'];
            
            // Normalizar caminho
            $realPath = realpath($filePath);
            
            // Verificar se o arquivo existe
            if (!$realPath || !file_exists($realPath)) {
                throw new Exception('Arquivo não encontrado');
            }
            
            // Verificar extensão de arquivo
            $allowedExtensions = ['stl', 'obj', '3mf', 'gcode'];
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception('Tipo de arquivo não suportado');
            }
            
            // Verificação de diretório (prevenção contra path traversal)
            $allowedDirs = [
                realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads/models'),
                realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads/customer_models')
            ];
            
            $isAllowed = false;
            foreach ($allowedDirs as $dir) {
                if ($dir && strpos($realPath, $dir) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                throw new Exception('Acesso negado ao arquivo em diretório não autorizado');
            }
        }
        
        // Validar user_id
        if (isset($taskData['user_id'])) {
            $userId = intval($taskData['user_id']);
            if ($userId <= 0) {
                throw new Exception('user_id inválido');
            }
            
            // Verificar se o usuário existe
            $sql = "SELECT id FROM users WHERE id = :id";
            $user = $this->db->fetchSingle($sql, [':id' => $userId]);
            
            if (!$user) {
                throw new Exception('Usuário não encontrado');
            }
        }
    }
    
    /**
     * Sanitiza um caminho de arquivo para evitar path traversal
     * 
     * @param string $path Caminho a ser sanitizado
     * @return string Caminho sanitizado
     */
    private function sanitizePath(string $path): string {
        // Converter para caminho real normalizado
        $realPath = realpath($path);
        
        if (!$realPath) {
            return '';
        }
        
        // Retornar caminho normalizado
        return $realPath;
    }
    
    /**
     * Valida a prioridade da tarefa
     * 
     * @param int $priority Prioridade a ser validada
     * @return int Prioridade validada
     */
    private function validatePriority(int $priority): int {
        // Limitar a valores válidos
        if ($priority < self::PRIORITY_LOW) {
            return self::PRIORITY_LOW;
        }
        
        if ($priority > self::PRIORITY_HIGH) {
            return self::PRIORITY_HIGH;
        }
        
        return $priority;
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
     * Gera um ID único para a tarefa
     * 
     * @param array $taskData Dados da tarefa
     * @return string ID único
     */
    private function generateTaskId(array $taskData): string {
        // Combinar informações para criar um ID único
        $baseData = [
            'model_id' => $taskData['model_id'] ?? null,
            'file_path' => $taskData['file_path'] ?? null,
            'user_id' => $taskData['user_id'] ?? null,
            'timestamp' => microtime(true),
            'random' => random_bytes(8)
        ];
        
        // Serializar e fazer hash
        $serialized = serialize($baseData);
        $hash = md5($serialized);
        
        // Adicionar timestamp para garantir unicidade
        $prefix = 'q-' . dechex(time()) . '-';
        
        return $prefix . $hash;
    }
    
    /**
     * Obtém a próxima tarefa da fila para processamento
     * 
     * @param string $workerId ID do worker que está solicitando a tarefa
     * @param int $lockTime Tempo de bloqueio em segundos
     * @return array|null Dados da tarefa ou null se não houver tarefas
     */
    public function getNextTask(string $workerId, int $lockTime = 300): ?array {
        try {
            // Iniciar transação para garantir atomicidade
            $this->db->beginTransaction();
            
            // Primeiro, liberar tarefas travadas por muito tempo
            $this->releaseTimedOutTasks();
            
            // Encontrar a próxima tarefa com base na prioridade
            $sql = "SELECT * FROM quotation_queue
                    WHERE status = :status
                    AND (locked_by IS NULL OR locked_until < NOW())
                    ORDER BY priority DESC, created_at ASC
                    LIMIT 1";
            
            $task = $this->db->fetchSingle($sql, [':status' => self::STATUS_QUEUED]);
            
            if (!$task) {
                $this->db->commit();
                return null;
            }
            
            // Calcular timestamp de bloqueio
            $lockedUntil = date('Y-m-d H:i:s', time() + $lockTime);
            
            // Bloquear a tarefa para este worker
            $sql = "UPDATE quotation_queue
                    SET status = :processing,
                        locked_by = :worker_id,
                        locked_until = :locked_until,
                        started_at = NOW()
                    WHERE id = :id AND status = :queued";
            
            $params = [
                ':processing' => self::STATUS_PROCESSING,
                ':worker_id' => $workerId,
                ':locked_until' => $lockedUntil,
                ':id' => $task['id'],
                ':queued' => self::STATUS_QUEUED
            ];
            
            $affected = $this->db->execute($sql, $params);
            
            if ($affected === 0) {
                // Outra instância já obteve esta tarefa
                $this->db->commit();
                return $this->getNextTask($workerId, $lockTime); // Recursão para tentar novamente
            }
            
            // Confirmar alterações
            $this->db->commit();
            
            // Decodificar parâmetros
            $task['parameters'] = json_decode($task['parameters'], true) ?: [];
            
            // Registrar atividade
            $this->logActivity('dequeue', $task['task_id'], "Tarefa obtida para processamento por {$workerId}");
            
            return $task;
        } catch (Exception $e) {
            // Reverter alterações em caso de erro
            $this->db->rollBack();
            error_log('Erro ao obter próxima tarefa: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Libera tarefas que excederam o tempo de bloqueio
     */
    private function releaseTimedOutTasks(): void {
        $sql = "UPDATE quotation_queue
                SET status = :queued,
                    locked_by = NULL,
                    locked_until = NULL,
                    error_message = CONCAT(IFNULL(error_message, ''), '\nTarefa liberada após timeout de processamento.')
                WHERE status = :processing
                AND locked_until < NOW()";
        
        $params = [
            ':queued' => self::STATUS_QUEUED,
            ':processing' => self::STATUS_PROCESSING
        ];
        
        $affected = $this->db->execute($sql, $params);
        
        if ($affected > 0) {
            error_log("Liberadas {$affected} tarefas que excederam o tempo de bloqueio");
        }
    }
    
    /**
     * Atualiza o status de uma tarefa
     * 
     * @param string $taskId ID da tarefa
     * @param string $status Novo status
     * @param int $progress Porcentagem de progresso (0-100)
     * @param array $result Resultados da tarefa (para status completed)
     * @param string $errorMessage Mensagem de erro (para status failed)
     * @param string $workerId ID do worker que está atualizando a tarefa
     * @return bool Sucesso da operação
     */
    public function updateTaskStatus(
        string $taskId, 
        string $status, 
        int $progress = null, 
        array $result = null, 
        string $errorMessage = null,
        string $workerId = null
    ): bool {
        try {
            // Validar status
            $validStatuses = [self::STATUS_QUEUED, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED];
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Status inválido');
            }
            
            // Validar progresso
            if ($progress !== null) {
                $progress = max(0, min(100, intval($progress)));
            }
            
            // Verificar se a tarefa existe e está bloqueada pelo worker correto
            $task = $this->getTaskById($taskId);
            
            if (!$task) {
                throw new Exception('Tarefa não encontrada');
            }
            
            // Se um workerId foi fornecido, verificar se a tarefa está bloqueada por este worker
            if ($workerId !== null && $task['locked_by'] !== $workerId && $task['status'] === self::STATUS_PROCESSING) {
                throw new Exception('Tarefa está bloqueada por outro worker');
            }
            
            // Preparar dados para atualização
            $data = [
                ':task_id' => $taskId,
                ':status' => $status
            ];
            
            // Iniciar construção da query
            $sql = "UPDATE quotation_queue SET status = :status";
            
            // Adicionar campos opcionais
            if ($progress !== null) {
                $sql .= ", progress = :progress";
                $data[':progress'] = $progress;
            }
            
            if ($result !== null) {
                $sql .= ", result_data = :result_data";
                $data[':result_data'] = json_encode($result);
            }
            
            if ($errorMessage !== null) {
                $sql .= ", error_message = :error_message";
                $data[':error_message'] = $errorMessage;
            }
            
            // Definir campos com base no status
            if ($status === self::STATUS_COMPLETED || $status === self::STATUS_FAILED) {
                $sql .= ", completed_at = NOW(), locked_by = NULL, locked_until = NULL";
            } elseif ($status === self::STATUS_PROCESSING) {
                // Estender o bloqueio
                $sql .= ", locked_until = :locked_until";
                $data[':locked_until'] = date('Y-m-d H:i:s', time() + 300); // +5 minutos
            }
            
            // Finalizar query
            $sql .= " WHERE task_id = :task_id";
            
            // Executar atualização
            $affected = $this->db->execute($sql, $data);
            
            if ($affected === 0) {
                throw new Exception('Nenhuma tarefa foi atualizada');
            }
            
            // Registrar atividade
            $this->logActivity('update', $taskId, "Status atualizado para {$status}" . 
                ($progress !== null ? ", progresso: {$progress}%" : ""));
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao atualizar status da tarefa: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o status de uma tarefa pelo ID
     * 
     * @param string $taskId ID da tarefa
     * @return array|null Dados da tarefa ou null se não encontrada
     */
    public function getTaskById(string $taskId): ?array {
        try {
            $sql = "SELECT * FROM quotation_queue WHERE task_id = :task_id";
            $task = $this->db->fetchSingle($sql, [':task_id' => $taskId]);
            
            if ($task && isset($task['parameters'])) {
                $task['parameters'] = json_decode($task['parameters'], true) ?: [];
            }
            
            if ($task && isset($task['result_data'])) {
                $task['result_data'] = json_decode($task['result_data'], true) ?: [];
            }
            
            return $task ?: null;
        } catch (Exception $e) {
            error_log('Erro ao buscar tarefa: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lista tarefas com filtros
     * 
     * @param array $filters Filtros (status, user_id, etc.)
     * @param int $limit Limite de resultados
     * @param int $offset Deslocamento para paginação
     * @return array Lista de tarefas
     */
    public function listTasks(array $filters = [], int $limit = 50, int $offset = 0): array {
        try {
            // Construir consulta base
            $sql = "SELECT * FROM quotation_queue";
            
            // Inicializar arrays para condições WHERE e parâmetros
            $conditions = [];
            $params = [];
            
            // Aplicar filtros
            if (isset($filters['status']) && !empty($filters['status'])) {
                if (is_array($filters['status'])) {
                    $statusPlaceholders = [];
                    foreach ($filters['status'] as $i => $status) {
                        $placeholder = ":status{$i}";
                        $statusPlaceholders[] = $placeholder;
                        $params[$placeholder] = $status;
                    }
                    $conditions[] = "status IN (" . implode(", ", $statusPlaceholders) . ")";
                } else {
                    $conditions[] = "status = :status";
                    $params[':status'] = $filters['status'];
                }
            }
            
            if (isset($filters['user_id']) && $filters['user_id'] > 0) {
                $conditions[] = "user_id = :user_id";
                $params[':user_id'] = intval($filters['user_id']);
            }
            
            if (isset($filters['model_id']) && $filters['model_id'] > 0) {
                $conditions[] = "model_id = :model_id";
                $params[':model_id'] = intval($filters['model_id']);
            }
            
            if (isset($filters['priority'])) {
                $conditions[] = "priority = :priority";
                $params[':priority'] = intval($filters['priority']);
            }
            
            if (isset($filters['created_after'])) {
                $conditions[] = "created_at >= :created_after";
                $params[':created_after'] = $filters['created_after'];
            }
            
            if (isset($filters['created_before'])) {
                $conditions[] = "created_at <= :created_before";
                $params[':created_before'] = $filters['created_before'];
            }
            
            // Adicionar condições WHERE se houver
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            // Adicionar ordenação
            $sql .= " ORDER BY ";
            
            if (isset($filters['order_by']) && !empty($filters['order_by'])) {
                $allowedFields = ['created_at', 'updated_at', 'priority', 'status'];
                $orderBy = in_array($filters['order_by'], $allowedFields) ? $filters['order_by'] : 'created_at';
                $orderDir = (isset($filters['order_dir']) && strtoupper($filters['order_dir']) === 'ASC') ? 'ASC' : 'DESC';
                $sql .= "{$orderBy} {$orderDir}";
            } else {
                $sql .= "priority DESC, created_at DESC";
            }
            
            // Adicionar limite e deslocamento
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = intval($limit);
            $params[':offset'] = intval($offset);
            
            // Executar consulta
            $tasks = $this->db->fetchAll($sql, $params);
            
            // Decodificar parâmetros e resultados
            foreach ($tasks as &$task) {
                if (isset($task['parameters'])) {
                    $task['parameters'] = json_decode($task['parameters'], true) ?: [];
                }
                
                if (isset($task['result_data'])) {
                    $task['result_data'] = json_decode($task['result_data'], true) ?: [];
                }
            }
            
            return $tasks;
        } catch (Exception $e) {
            error_log('Erro ao listar tarefas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o número de tarefas com filtros
     * 
     * @param array $filters Filtros (status, user_id, etc.)
     * @return int Número de tarefas
     */
    public function countTasks(array $filters = []): int {
        try {
            // Construir consulta base
            $sql = "SELECT COUNT(*) as count FROM quotation_queue";
            
            // Inicializar arrays para condições WHERE e parâmetros
            $conditions = [];
            $params = [];
            
            // Aplicar filtros (mesmo código que listTasks)
            if (isset($filters['status']) && !empty($filters['status'])) {
                if (is_array($filters['status'])) {
                    $statusPlaceholders = [];
                    foreach ($filters['status'] as $i => $status) {
                        $placeholder = ":status{$i}";
                        $statusPlaceholders[] = $placeholder;
                        $params[$placeholder] = $status;
                    }
                    $conditions[] = "status IN (" . implode(", ", $statusPlaceholders) . ")";
                } else {
                    $conditions[] = "status = :status";
                    $params[':status'] = $filters['status'];
                }
            }
            
            if (isset($filters['user_id']) && $filters['user_id'] > 0) {
                $conditions[] = "user_id = :user_id";
                $params[':user_id'] = intval($filters['user_id']);
            }
            
            if (isset($filters['model_id']) && $filters['model_id'] > 0) {
                $conditions[] = "model_id = :model_id";
                $params[':model_id'] = intval($filters['model_id']);
            }
            
            if (isset($filters['priority'])) {
                $conditions[] = "priority = :priority";
                $params[':priority'] = intval($filters['priority']);
            }
            
            if (isset($filters['created_after'])) {
                $conditions[] = "created_at >= :created_after";
                $params[':created_after'] = $filters['created_after'];
            }
            
            if (isset($filters['created_before'])) {
                $conditions[] = "created_at <= :created_before";
                $params[':created_before'] = $filters['created_before'];
            }
            
            // Adicionar condições WHERE se houver
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            // Executar consulta
            $result = $this->db->fetchSingle($sql, $params);
            
            return $result ? intval($result['count']) : 0;
        } catch (Exception $e) {
            error_log('Erro ao contar tarefas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Cancela uma tarefa
     * 
     * @param string $taskId ID da tarefa
     * @param int $userId ID do usuário que está cancelando (para verificação de permissão)
     * @param string $reason Motivo do cancelamento
     * @return bool Sucesso da operação
     */
    public function cancelTask(string $taskId, int $userId = null, string $reason = ''): bool {
        try {
            // Verificar se a tarefa existe
            $task = $this->getTaskById($taskId);
            
            if (!$task) {
                throw new Exception('Tarefa não encontrada');
            }
            
            // Verificar permissão (se userId fornecido)
            if ($userId !== null && $task['user_id'] !== null && $task['user_id'] != $userId) {
                // Verificar se é admin
                $isAdmin = $this->checkUserIsAdmin($userId);
                
                if (!$isAdmin) {
                    throw new Exception('Permissão negada: apenas o proprietário da tarefa ou um administrador pode cancelá-la');
                }
            }
            
            // Verificar se a tarefa pode ser cancelada
            if ($task['status'] === self::STATUS_COMPLETED || $task['status'] === self::STATUS_FAILED) {
                throw new Exception('Não é possível cancelar uma tarefa já concluída ou falha');
            }
            
            // Preparar mensagem
            $message = 'Tarefa cancelada';
            if (!empty($reason)) {
                $message .= ': ' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
            }
            
            // Atualizar status
            $sql = "UPDATE quotation_queue
                    SET status = :status,
                        error_message = :message,
                        completed_at = NOW(),
                        locked_by = NULL,
                        locked_until = NULL
                    WHERE task_id = :task_id";
            
            $params = [
                ':status' => self::STATUS_CANCELLED,
                ':message' => $message,
                ':task_id' => $taskId
            ];
            
            $affected = $this->db->execute($sql, $params);
            
            if ($affected === 0) {
                throw new Exception('Nenhuma tarefa foi cancelada');
            }
            
            // Registrar atividade
            $this->logActivity('cancel', $taskId, "Tarefa cancelada" . 
                ($userId ? " por usuário ID {$userId}" : "") . 
                (!empty($reason) ? ": {$reason}" : ""));
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao cancelar tarefa: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um usuário é administrador
     * 
     * @param int $userId ID do usuário
     * @return bool true se for admin
     */
    private function checkUserIsAdmin(int $userId): bool {
        try {
            $sql = "SELECT role FROM users WHERE id = :id";
            $user = $this->db->fetchSingle($sql, [':id' => $userId]);
            
            return ($user && $user['role'] === 'admin');
        } catch (Exception $e) {
            error_log('Erro ao verificar permissão de admin: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpa tarefas antigas
     * 
     * @param int $daysToKeep Dias para manter tarefas
     * @return int Número de tarefas removidas
     */
    public function cleanupOldTasks(int $daysToKeep = 30): int {
        try {
            // Verificar tarefas antigas em status final
            $sql = "DELETE FROM quotation_queue
                    WHERE (status = :completed OR status = :failed OR status = :cancelled)
                    AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $params = [
                ':completed' => self::STATUS_COMPLETED,
                ':failed' => self::STATUS_FAILED,
                ':cancelled' => self::STATUS_CANCELLED,
                ':days' => $daysToKeep
            ];
            
            $affected = $this->db->execute($sql, $params);
            
            // Registrar atividade
            if ($affected > 0) {
                $this->logActivity('cleanup', 'system', "Removidas {$affected} tarefas antigas com mais de {$daysToKeep} dias");
            }
            
            return $affected;
        } catch (Exception $e) {
            error_log('Erro ao limpar tarefas antigas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Registra atividade do sistema de fila
     * 
     * @param string $action Ação realizada
     * @param string $taskId ID da tarefa ou "system"
     * @param string $message Mensagem descritiva
     */
    private function logActivity(string $action, string $taskId, string $message): void {
        try {
            // Verificar se a tabela de log existe
            $tableExists = $this->db->fetchSingle(
                "SHOW TABLES LIKE 'quotation_queue_log'"
            );
            
            if (!$tableExists) {
                // Criar tabela de log
                $this->db->execute(
                    "CREATE TABLE quotation_queue_log (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        action VARCHAR(20) NOT NULL,
                        task_id VARCHAR(64) NOT NULL,
                        message TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX (task_id),
                        INDEX (action)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
                );
            }
            
            // Inserir log
            $this->db->execute(
                "INSERT INTO quotation_queue_log (action, task_id, message)
                 VALUES (:action, :task_id, :message)",
                [
                    ':action' => $action,
                    ':task_id' => $taskId,
                    ':message' => $message
                ]
            );
        } catch (Exception $e) {
            // Apenas registrar erro, não interromper execução
            error_log('Erro ao registrar log de atividade: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém estatísticas da fila
     * 
     * @return array Estatísticas da fila
     */
    public function getQueueStats(): array {
        try {
            // Estatísticas por status
            $sql = "SELECT status, COUNT(*) as count FROM quotation_queue GROUP BY status";
            $statusStats = $this->db->fetchAll($sql);
            
            // Formatar como array associativo
            $stats = [
                'total' => 0,
                'by_status' => [],
                'waiting_time' => 0,
                'processing_time' => 0,
                'success_rate' => 0
            ];
            
            foreach ($statusStats as $stat) {
                $stats['by_status'][$stat['status']] = intval($stat['count']);
                $stats['total'] += intval($stat['count']);
            }
            
            // Tempo médio de espera (de criação até início do processamento)
            $sql = "SELECT AVG(TIME_TO_SEC(TIMEDIFF(started_at, created_at))) as avg_wait_time
                    FROM quotation_queue
                    WHERE started_at IS NOT NULL";
            $waitTimeResult = $this->db->fetchSingle($sql);
            
            if ($waitTimeResult && $waitTimeResult['avg_wait_time'] !== null) {
                $stats['waiting_time'] = round($waitTimeResult['avg_wait_time'] / 60, 1); // Em minutos
            }
            
            // Tempo médio de processamento (de início até conclusão)
            $sql = "SELECT AVG(TIME_TO_SEC(TIMEDIFF(completed_at, started_at))) as avg_proc_time
                    FROM quotation_queue
                    WHERE completed_at IS NOT NULL AND started_at IS NOT NULL";
            $procTimeResult = $this->db->fetchSingle($sql);
            
            if ($procTimeResult && $procTimeResult['avg_proc_time'] !== null) {
                $stats['processing_time'] = round($procTimeResult['avg_proc_time'] / 60, 1); // Em minutos
            }
            
            // Taxa de sucesso
            $sql = "SELECT 
                    COUNT(CASE WHEN status = :completed THEN 1 END) as successful,
                    COUNT(CASE WHEN status = :failed THEN 1 END) as failed
                    FROM quotation_queue
                    WHERE status IN (:completed, :failed)";
            
            $successResult = $this->db->fetchSingle($sql, [
                ':completed' => self::STATUS_COMPLETED,
                ':failed' => self::STATUS_FAILED
            ]);
            
            if ($successResult) {
                $total = intval($successResult['successful']) + intval($successResult['failed']);
                if ($total > 0) {
                    $stats['success_rate'] = round((intval($successResult['successful']) / $total) * 100, 1);
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas da fila: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}
