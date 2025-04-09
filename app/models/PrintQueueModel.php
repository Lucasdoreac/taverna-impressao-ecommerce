<?php
/**
 * PrintQueueModel - Gerencia a fila de impressão 3D
 * 
 * @package    Taverna da Impressão 3D
 * @author     Claude
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/NotificationModel.php';
require_once __DIR__ . '/CustomerModelModel.php';

class PrintQueueModel {
    use InputValidationTrait;
    
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Adiciona um modelo à fila de impressão
     *
     * @param int $modelId ID do modelo 3D aprovado
     * @param int $userId ID do usuário que solicitou a impressão
     * @param int $priority Prioridade na fila (1-10, 10 sendo a mais alta)
     * @param string $notes Notas adicionais sobre a impressão
     * @param array $printSettings Configurações específicas de impressão
     * @return int|bool ID do item na fila ou false se falhar
     */
    public function saveQueueItem($modelId, $userId, $priority = 5, $notes = '', $printSettings = null) {
        try {
            // Validar parâmetros
            $modelId = (int)$modelId;
            $userId = (int)$userId;
            $priority = max(1, min(10, (int)$priority)); // Garantir que a prioridade esteja entre 1 e 10
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Verificar se o modelo existe e está aprovado
            $customerModelModel = new CustomerModelModel();
            $model = $customerModelModel->getModelById($modelId);
            
            if (!$model || $model['status'] !== 'approved') {
                return false;
            }
            
            // Preparar dados para inserção
            $data = [
                'model_id' => $modelId,
                'user_id' => $userId,
                'status' => 'pending',
                'priority' => $priority,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Adicionar configurações de impressão se fornecidas
            if ($printSettings) {
                $data['print_settings'] = json_encode($printSettings);
            }
            
            // Inserir na tabela print_queue
            $sql = "INSERT INTO print_queue (model_id, user_id, status, priority, notes, print_settings, created_at) 
                    VALUES (:model_id, :user_id, :status, :priority, :notes, :print_settings, :created_at)";
            
            $params = [
                ':model_id' => $data['model_id'],
                ':user_id' => $data['user_id'],
                ':status' => $data['status'],
                ':priority' => $data['priority'],
                ':notes' => $data['notes'],
                ':print_settings' => $data['print_settings'] ?? null,
                ':created_at' => $data['created_at']
            ];
            
            $this->db->execute($sql, $params);
            $queueId = $this->db->lastInsertId();
            
            if ($queueId) {
                // Registrar evento no histórico
                $this->addHistoryEvent($queueId, 'creation', 'Item adicionado à fila de impressão', null, null, $userId);
                
                // Enviar notificação ao usuário
                $this->createNotification(
                    $userId,
                    $queueId,
                    null,
                    'Modelo adicionado à fila de impressão',
                    'Seu modelo foi adicionado à fila de impressão e está aguardando processamento.',
                    'info'
                );
                
                return $queueId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao adicionar item à fila de impressão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de um item na fila
     *
     * @param int $queueId ID do item na fila
     * @param string $status Novo status
     * @param int $userId ID do usuário que realizou a alteração
     * @param string $notes Notas adicionais
     * @return bool True se a operação foi bem-sucedida
     */
    public function updateStatus($queueId, $status, $userId = null, $notes = '') {
        try {
            // Validar parâmetros
            $queueId = (int)$queueId;
            $status = $this->validateString($status, ['allowedValues' => ['pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed']]);
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Obter item atual para comparação e verificação
            $currentItem = $this->getQueueItemById($queueId);
            if (!$currentItem) {
                return false;
            }
            
            $previousStatus = $currentItem['status'];
            
            // Verificar se a transição de status é válida
            if (!$this->isValidStatusTransition($previousStatus, $status)) {
                error_log("Transição de status inválida: {$previousStatus} -> {$status}");
                return false;
            }
            
            // Atualizar o status
            $sql = "UPDATE print_queue SET status = :status, updated_at = NOW() WHERE id = :id";
            $params = [
                ':status' => $status,
                ':id' => $queueId
            ];
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                // Registrar evento no histórico
                $this->addHistoryEvent(
                    $queueId,
                    'status_change',
                    "Status alterado de {$previousStatus} para {$status}" . ($notes ? ": {$notes}" : ""),
                    ['status' => $previousStatus],
                    ['status' => $status],
                    $userId
                );
                
                // Enviar notificação ao usuário
                $this->createStatusNotification($queueId, $status, $notes);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar status do item na fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se a transição de status é válida
     *
     * @param string $currentStatus Status atual
     * @param string $newStatus Novo status
     * @return bool True se a transição é válida
     */
    private function isValidStatusTransition($currentStatus, $newStatus) {
        // Definir transições válidas
        $validTransitions = [
            'pending' => ['assigned', 'cancelled'],
            'assigned' => ['printing', 'pending', 'cancelled'],
            'printing' => ['completed', 'failed', 'cancelled'],
            'completed' => [], // Status final, não permite transição
            'failed' => ['pending'], // Permitir reenvio para a fila
            'cancelled' => ['pending'] // Permitir reativação
        ];
        
        // Verificar se a transição é válida
        if (isset($validTransitions[$currentStatus]) && in_array($newStatus, $validTransitions[$currentStatus])) {
            return true;
        }
        
        // Verificar se é o mesmo status (não é uma transição)
        if ($currentStatus === $newStatus) {
            return true;
        }
        
        // Permitir que administradores façam qualquer transição (para fins de correção)
        // Esta verificação será feita no controller
        
        return false;
    }
    
    /**
     * Atualiza a prioridade de um item na fila
     *
     * @param int $queueId ID do item na fila
     * @param int $priority Nova prioridade (1-10)
     * @param int $userId ID do usuário que realizou a alteração
     * @return bool True se a operação foi bem-sucedida
     */
    public function updatePriority($queueId, $priority, $userId = null) {
        try {
            // Validar parâmetros
            $queueId = (int)$queueId;
            $priority = max(1, min(10, (int)$priority)); // Garantir que a prioridade esteja entre 1 e 10
            
            // Obter item atual para comparação
            $currentItem = $this->getQueueItemById($queueId);
            if (!$currentItem) {
                return false;
            }
            
            $previousPriority = $currentItem['priority'];
            
            // Se a prioridade não mudou, não fazer nada
            if ($previousPriority == $priority) {
                return true;
            }
            
            // Atualizar a prioridade
            $sql = "UPDATE print_queue SET priority = :priority, updated_at = NOW() WHERE id = :id";
            $params = [
                ':priority' => $priority,
                ':id' => $queueId
            ];
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                // Registrar evento no histórico
                $this->addHistoryEvent(
                    $queueId,
                    'priority_change',
                    "Prioridade alterada de {$previousPriority} para {$priority}",
                    ['priority' => $previousPriority],
                    ['priority' => $priority],
                    $userId
                );
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar prioridade do item na fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém um item da fila pelo ID
     *
     * @param int $queueId ID do item na fila
     * @return array|false Dados do item ou false se não encontrado
     */
    public function getQueueItemById($queueId) {
        try {
            $queueId = (int)$queueId;
            
            $sql = "SELECT pq.*, cm.original_name AS model_name, u.name AS user_name, u.email AS user_email
                    FROM print_queue pq
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pq.id = :id";
            
            $params = [':id' => $queueId];
            $result = $this->db->fetchSingle($sql, $params);
            
            if ($result) {
                // Decodificar configurações de impressão se existirem
                if (isset($result['print_settings']) && !empty($result['print_settings'])) {
                    $result['print_settings'] = json_decode($result['print_settings'], true);
                }
                
                return $result;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao obter item da fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém itens da fila de um usuário específico
     *
     * @param int $userId ID do usuário
     * @param string|null $status Filtrar por status (opcional)
     * @return array Lista de itens na fila
     */
    public function getUserQueueItems($userId, $status = null) {
        try {
            $userId = (int)$userId;
            
            $sql = "SELECT pq.*, cm.original_name AS model_name
                    FROM print_queue pq
                    JOIN customer_models cm ON pq.model_id = cm.id
                    WHERE pq.user_id = :user_id";
            
            $params = [':user_id' => $userId];
            
            // Adicionar filtro de status se fornecido
            if ($status !== null) {
                $status = $this->validateString($status, [
                    'allowedValues' => ['pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed']
                ]);
                
                $sql .= " AND pq.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY pq.priority DESC, pq.created_at ASC";
            
            $results = $this->db->fetchAll($sql, $params);
            
            // Processar cada item
            foreach ($results as &$result) {
                if (isset($result['print_settings']) && !empty($result['print_settings'])) {
                    $result['print_settings'] = json_decode($result['print_settings'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Erro ao obter itens da fila do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém itens pendentes na fila
     *
     * @return array Lista de itens pendentes
     */
    public function getPendingQueueItems() {
        try {
            $sql = "SELECT pq.*, cm.original_name AS model_name, u.name AS user_name, u.email AS user_email
                    FROM print_queue pq
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pq.status = 'pending'
                    ORDER BY pq.priority DESC, pq.created_at ASC";
            
            $results = $this->db->fetchAll($sql);
            
            // Processar cada item
            foreach ($results as &$result) {
                if (isset($result['print_settings']) && !empty($result['print_settings'])) {
                    $result['print_settings'] = json_decode($result['print_settings'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Erro ao obter itens pendentes na fila: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todos os itens na fila com filtros opcionais
     *
     * @param array $filters Filtros (status, user_id, etc.)
     * @return array Lista de itens na fila
     */
    public function getAllQueueItems($filters = []) {
        try {
            $sql = "SELECT pq.*, cm.original_name AS model_name, u.name AS user_name, u.email AS user_email
                    FROM print_queue pq
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE 1=1";
            
            $params = [];
            
            // Adicionar filtros
            if (isset($filters['status']) && !empty($filters['status'])) {
                $status = $this->validateString($filters['status'], [
                    'allowedValues' => ['pending', 'assigned', 'printing', 'completed', 'cancelled', 'failed']
                ]);
                
                $sql .= " AND pq.status = :status";
                $params[':status'] = $status;
            }
            
            if (isset($filters['user_id']) && !empty($filters['user_id'])) {
                $userId = (int)$filters['user_id'];
                $sql .= " AND pq.user_id = :user_id";
                $params[':user_id'] = $userId;
            }
            
            if (isset($filters['model_id']) && !empty($filters['model_id'])) {
                $modelId = (int)$filters['model_id'];
                $sql .= " AND pq.model_id = :model_id";
                $params[':model_id'] = $modelId;
            }
            
            // Ordenação
            $orderBy = "pq.priority DESC, pq.created_at ASC";
            if (isset($filters['order_by']) && !empty($filters['order_by'])) {
                $allowedOrderBy = ['priority', 'created_at', 'updated_at', 'status'];
                if (in_array($filters['order_by'], $allowedOrderBy)) {
                    $direction = isset($filters['order_direction']) && strtoupper($filters['order_direction']) === 'ASC' ? 'ASC' : 'DESC';
                    $orderBy = "pq.{$filters['order_by']} {$direction}";
                }
            }
            
            $sql .= " ORDER BY " . $orderBy;
            
            // Paginação
            if (isset($filters['limit']) && isset($filters['offset'])) {
                $limit = (int)$filters['limit'];
                $offset = (int)$filters['offset'];
                $sql .= " LIMIT {$limit} OFFSET {$offset}";
            }
            
            $results = $this->db->fetchAll($sql, $params);
            
            // Processar cada item
            foreach ($results as &$result) {
                if (isset($result['print_settings']) && !empty($result['print_settings'])) {
                    $result['print_settings'] = json_decode($result['print_settings'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Erro ao obter todos os itens da fila: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas da fila de impressão
     *
     * @return array Estatísticas
     */
    public function getQueueStatistics() {
        try {
            $stats = [
                'total' => 0,
                'by_status' => [
                    'pending' => 0,
                    'assigned' => 0,
                    'printing' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'failed' => 0
                ],
                'avg_wait_time' => 0,
                'avg_print_time' => 0
            ];
            
            // Contar totais por status
            $sql = "SELECT status, COUNT(*) as count FROM print_queue GROUP BY status";
            $results = $this->db->fetchAll($sql);
            
            if ($results) {
                foreach ($results as $row) {
                    $stats['by_status'][$row['status']] = (int)$row['count'];
                    $stats['total'] += (int)$row['count'];
                }
            }
            
            // Calcular tempo médio de espera (do status pending para printing)
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, t1.created_at, t2.created_at)) as avg_wait_time
                    FROM print_queue_history t1
                    JOIN print_queue_history t2 ON t1.queue_id = t2.queue_id
                    WHERE t1.event_type = 'status_change'
                    AND JSON_EXTRACT(t1.new_value, '$.status') = 'pending'
                    AND t2.event_type = 'status_change'
                    AND JSON_EXTRACT(t2.new_value, '$.status') = 'printing'
                    AND t1.created_at < t2.created_at";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_wait_time'] !== null) {
                $stats['avg_wait_time'] = round($result['avg_wait_time'], 2);
            }
            
            // Calcular tempo médio de impressão (do status printing para completed)
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, t1.created_at, t2.created_at)) as avg_print_time
                    FROM print_queue_history t1
                    JOIN print_queue_history t2 ON t1.queue_id = t2.queue_id
                    WHERE t1.event_type = 'status_change'
                    AND JSON_EXTRACT(t1.new_value, '$.status') = 'printing'
                    AND t2.event_type = 'status_change'
                    AND JSON_EXTRACT(t2.new_value, '$.status') = 'completed'
                    AND t1.created_at < t2.created_at";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_print_time'] !== null) {
                $stats['avg_print_time'] = round($result['avg_print_time'], 2);
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas da fila: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [
                    'pending' => 0,
                    'assigned' => 0,
                    'printing' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'failed' => 0
                ],
                'avg_wait_time' => 0,
                'avg_print_time' => 0
            ];
        }
    }
    
    /**
     * Adiciona um evento ao histórico da fila
     *
     * @param int $queueId ID do item na fila
     * @param string $eventType Tipo de evento
     * @param string $description Descrição do evento
     * @param mixed $previousValue Valor anterior (opcional)
     * @param mixed $newValue Novo valor (opcional)
     * @param int $userId ID do usuário que gerou o evento (opcional)
     * @return int|false ID do evento ou false se falhar
     */
    public function addHistoryEvent($queueId, $eventType, $description, $previousValue = null, $newValue = null, $userId = null) {
        try {
            // Validar parâmetros
            $queueId = (int)$queueId;
            $eventType = $this->validateString($eventType);
            $description = $this->validateString($description);
            
            $sql = "INSERT INTO print_queue_history (queue_id, event_type, description, previous_value, new_value, created_by, created_at)
                    VALUES (:queue_id, :event_type, :description, :previous_value, :new_value, :created_by, NOW())";
            
            $params = [
                ':queue_id' => $queueId,
                ':event_type' => $eventType,
                ':description' => $description,
                ':previous_value' => $previousValue !== null ? json_encode($previousValue) : null,
                ':new_value' => $newValue !== null ? json_encode($newValue) : null,
                ':created_by' => $userId
            ];
            
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erro ao adicionar evento ao histórico: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma notificação para o usuário
     *
     * @param int $userId ID do usuário destinatário
     * @param int $queueId ID do item na fila
     * @param int|null $jobId ID do trabalho de impressão (opcional)
     * @param string $title Título da notificação
     * @param string $message Mensagem da notificação
     * @param string $type Tipo da notificação (info, warning, success, error)
     * @return int|false ID da notificação ou false se falhar
     */
    public function createNotification($userId, $queueId, $jobId, $title, $message, $type = 'info') {
        try {
            // Validar parâmetros
            $userId = (int)$userId;
            $queueId = (int)$queueId;
            $jobId = $jobId !== null ? (int)$jobId : null;
            $title = $this->validateString($title, ['maxLength' => 255]);
            $message = $this->validateString($message);
            $type = $this->validateString($type, ['allowedValues' => ['info', 'warning', 'success', 'error']]);
            
            $sql = "INSERT INTO print_notifications (user_id, queue_id, job_id, title, message, type, status, created_at)
                    VALUES (:user_id, :queue_id, :job_id, :title, :message, :type, 'unread', NOW())";
            
            $params = [
                ':user_id' => $userId,
                ':queue_id' => $queueId,
                ':job_id' => $jobId,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type
            ];
            
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma notificação específica para mudança de status
     *
     * @param int $queueId ID do item na fila
     * @param string $status Novo status
     * @param string $notes Notas adicionais
     * @return int|false ID da notificação ou false se falhar
     */
    private function createStatusNotification($queueId, $status, $notes = '') {
        try {
            // Obter item da fila
            $queueItem = $this->getQueueItemById($queueId);
            if (!$queueItem) {
                return false;
            }
            
            $userId = $queueItem['user_id'];
            $modelName = $queueItem['model_name'];
            
            // Definir título e mensagem com base no status
            $title = '';
            $message = '';
            $type = 'info';
            
            switch ($status) {
                case 'assigned':
                    $title = 'Modelo atribuído para impressão';
                    $message = "Seu modelo '{$modelName}' foi atribuído para impressão e será processado em breve.";
                    break;
                    
                case 'printing':
                    $title = 'Seu modelo está sendo impresso';
                    $message = "Seu modelo '{$modelName}' está sendo impresso neste momento.";
                    $type = 'info';
                    break;
                    
                case 'completed':
                    $title = 'Impressão concluída com sucesso';
                    $message = "A impressão do seu modelo '{$modelName}' foi concluída com sucesso!";
                    $type = 'success';
                    break;
                    
                case 'failed':
                    $title = 'Falha na impressão';
                    $message = "Houve uma falha na impressão do seu modelo '{$modelName}'.";
                    if ($notes) {
                        $message .= " Detalhes: {$notes}";
                    }
                    $type = 'error';
                    break;
                    
                case 'cancelled':
                    $title = 'Impressão cancelada';
                    $message = "A impressão do seu modelo '{$modelName}' foi cancelada.";
                    if ($notes) {
                        $message .= " Motivo: {$notes}";
                    }
                    $type = 'warning';
                    break;
                    
                default:
                    $title = 'Atualização de status';
                    $message = "O status do seu modelo '{$modelName}' foi atualizado para '{$status}'.";
                    break;
            }
            
            // Criar a notificação
            return $this->createNotification($userId, $queueId, null, $title, $message, $type);
        } catch (Exception $e) {
            error_log('Erro ao criar notificação de status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o histórico de eventos de um item na fila
     *
     * @param int $queueId ID do item na fila
     * @return array Lista de eventos
     */
    public function getQueueItemHistory($queueId) {
        try {
            $queueId = (int)$queueId;
            
            $sql = "SELECT pqh.*, u.name AS user_name
                    FROM print_queue_history pqh
                    LEFT JOIN users u ON pqh.created_by = u.id
                    WHERE pqh.queue_id = :queue_id
                    ORDER BY pqh.created_at DESC";
            
            $params = [':queue_id' => $queueId];
            $results = $this->db->fetchAll($sql, $params);
            
            // Processar cada evento
            foreach ($results as &$result) {
                if (isset($result['previous_value']) && !empty($result['previous_value'])) {
                    $result['previous_value'] = json_decode($result['previous_value'], true);
                }
                
                if (isset($result['new_value']) && !empty($result['new_value'])) {
                    $result['new_value'] = json_decode($result['new_value'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Erro ao obter histórico do item na fila: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exclui um item da fila
     *
     * @param int $queueId ID do item na fila
     * @param int $userId ID do usuário que realizou a exclusão
     * @return bool True se a operação foi bem-sucedida
     */
    public function deleteQueueItem($queueId, $userId) {
        try {
            $queueId = (int)$queueId;
            
            // Verificar se o item existe
            $queueItem = $this->getQueueItemById($queueId);
            if (!$queueItem) {
                return false;
            }
            
            // Registrar evento no histórico (será mantido mesmo após a exclusão)
            $this->addHistoryEvent(
                $queueId,
                'deletion',
                "Item excluído da fila de impressão",
                $queueItem,
                null,
                $userId
            );
            
            // Excluir o item
            $sql = "DELETE FROM print_queue WHERE id = :id";
            $params = [':id' => $queueId];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao excluir item da fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém as configurações da fila de impressão
     *
     * @param string $key Chave da configuração (opcional)
     * @param mixed $default Valor padrão se a configuração não existir
     * @return mixed Valor da configuração ou array de configurações
     */
    public function getSettings($key = null, $default = null) {
        try {
            if ($key !== null) {
                $key = $this->validateString($key);
                
                $sql = "SELECT setting_value FROM print_queue_settings WHERE setting_key = :key";
                $params = [':key' => $key];
                $result = $this->db->fetchSingle($sql, $params);
                
                return $result ? $result['setting_value'] : $default;
            } else {
                $sql = "SELECT setting_key, setting_value, description FROM print_queue_settings";
                $results = $this->db->fetchAll($sql);
                
                $settings = [];
                foreach ($results as $row) {
                    $settings[$row['setting_key']] = [
                        'value' => $row['setting_value'],
                        'description' => $row['description']
                    ];
                }
                
                return $settings;
            }
        } catch (Exception $e) {
            error_log('Erro ao obter configurações da fila: ' . $e->getMessage());
            return $key !== null ? $default : [];
        }
    }
    
    /**
     * Atualiza uma configuração da fila de impressão
     *
     * @param string $key Chave da configuração
     * @param string $value Valor da configuração
     * @param string $description Descrição da configuração (opcional)
     * @return bool True se a operação foi bem-sucedida
     */
    public function updateSetting($key, $value, $description = null) {
        try {
            $key = $this->validateString($key);
            $value = $this->validateString($value);
            
            // Verificar se a configuração já existe
            $sql = "SELECT id FROM print_queue_settings WHERE setting_key = :key";
            $params = [':key' => $key];
            $result = $this->db->fetchSingle($sql, $params);
            
            if ($result) {
                // Atualizar configuração existente
                $sql = "UPDATE print_queue_settings SET setting_value = :value";
                $params = [
                    ':key' => $key,
                    ':value' => $value
                ];
                
                if ($description !== null) {
                    $description = $this->validateString($description);
                    $sql .= ", description = :description";
                    $params[':description'] = $description;
                }
                
                $sql .= " WHERE setting_key = :key";
                
                return $this->db->execute($sql, $params) !== false;
            } else {
                // Inserir nova configuração
                $sql = "INSERT INTO print_queue_settings (setting_key, setting_value, description) 
                        VALUES (:key, :value, :description)";
                
                $params = [
                    ':key' => $key,
                    ':value' => $value,
                    ':description' => $description ?? ''
                ];
                
                $this->db->execute($sql, $params);
                return $this->db->lastInsertId() !== false;
            }
        } catch (Exception $e) {
            error_log('Erro ao atualizar configuração da fila: ' . $e->getMessage());
            return false;
        }
    }
}
