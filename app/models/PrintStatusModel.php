<?php
/**
 * PrintStatusModel - Modelo para gerenciamento do status de impressões 3D
 * 
 * Este modelo gerencia o status das impressões 3D em tempo real, incluindo 
 * atualizações de progresso, métricas e mensagens relacionadas.
 * 
 * @package Models
 */
class PrintStatusModel extends Model {
    protected $table = 'print_status';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id', 'product_id', 'print_queue_id', 'status',
        'progress_percentage', 'started_at', 'estimated_completion',
        'completed_at', 'total_print_time_seconds', 'elapsed_print_time_seconds',
        'printer_id', 'notes'
    ];
    
    // Status de impressão
    const STATUS_PENDING = 'pending';
    const STATUS_PREPARING = 'preparing';
    const STATUS_PRINTING = 'printing';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELED = 'canceled';
    
    /**
     * Obtém todos os status de impressão disponíveis
     * 
     * @return array Lista de status de impressão
     */
    public static function getAvailableStatuses() {
        return [
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_PREPARING => 'Preparando',
            self::STATUS_PRINTING => 'Imprimindo',
            self::STATUS_PAUSED => 'Pausado',
            self::STATUS_COMPLETED => 'Concluído',
            self::STATUS_FAILED => 'Falha',
            self::STATUS_CANCELED => 'Cancelado'
        ];
    }
    
    /**
     * Cria um novo registro de status de impressão
     * 
     * @param int $orderId ID do pedido
     * @param int $productId ID do produto
     * @param int $printQueueId ID na fila de impressão
     * @param string $printerId ID da impressora (opcional)
     * @param array $additionalData Dados adicionais (opcional)
     * @return int|false ID do registro criado ou false em caso de erro
     */
    public function createStatus($orderId, $productId, $printQueueId, $printerId = null, $additionalData = []) {
        try {
            // Preparar dados básicos
            $data = [
                'order_id' => $orderId,
                'product_id' => $productId,
                'print_queue_id' => $printQueueId,
                'status' => self::STATUS_PENDING,
                'progress_percentage' => 0.00,
                'printer_id' => $printerId
            ];
            
            // Adicionar dados adicionais, se fornecidos
            if (!empty($additionalData)) {
                $data = array_merge($data, $additionalData);
            }
            
            // Inserir no banco de dados
            $printStatusId = $this->insert($data);
            
            if ($printStatusId) {
                // Registrar criação no histórico de atualizações
                $this->logStatusUpdate($printStatusId, null, self::STATUS_PENDING, null, 0.00, 'Status de impressão criado');
            }
            
            return $printStatusId;
        } catch (Exception $e) {
            error_log("Erro ao criar status de impressão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de uma impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @param string $newStatus Novo status
     * @param float $newProgress Novo progresso (opcional)
     * @param string $message Mensagem de atualização (opcional)
     * @param string $updatedBy Identificador de quem atualizou (opcional)
     * @return bool Sucesso da operação
     */
    public function updateStatus($printStatusId, $newStatus, $newProgress = null, $message = '', $updatedBy = 'system') {
        try {
            // Obter o status atual
            $currentStatus = $this->find($printStatusId);
            if (!$currentStatus) {
                error_log("Status de impressão não encontrado: $printStatusId");
                return false;
            }
            
            // Preparar dados para atualização
            $updateData = ['status' => $newStatus];
            
            // Se o progresso foi fornecido, atualizar
            if ($newProgress !== null) {
                $updateData['progress_percentage'] = $newProgress;
            } else {
                $newProgress = $currentStatus['progress_percentage'];
            }
            
            // Atualizar campos específicos com base no novo status
            switch ($newStatus) {
                case self::STATUS_PRINTING:
                    if ($currentStatus['status'] !== self::STATUS_PRINTING) {
                        // Se estiver iniciando a impressão pela primeira vez
                        if (!$currentStatus['started_at']) {
                            $updateData['started_at'] = date('Y-m-d H:i:s');
                            
                            // Estimar tempo de conclusão com base no tempo total estimado
                            if (!empty($currentStatus['total_print_time_seconds'])) {
                                $estimatedCompletionTime = time() + $currentStatus['total_print_time_seconds'];
                                $updateData['estimated_completion'] = date('Y-m-d H:i:s', $estimatedCompletionTime);
                            }
                        }
                    }
                    break;
                    
                case self::STATUS_COMPLETED:
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                    $updateData['progress_percentage'] = 100.00;
                    $newProgress = 100.00;
                    break;
                    
                case self::STATUS_FAILED:
                case self::STATUS_CANCELED:
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                    break;
            }
            
            // Atualizar o registro no banco de dados
            $updated = $this->update($printStatusId, $updateData);
            
            if ($updated) {
                // Registrar atualização no histórico
                $this->logStatusUpdate(
                    $printStatusId,
                    $currentStatus['status'],
                    $newStatus,
                    $currentStatus['progress_percentage'],
                    $newProgress,
                    $message,
                    $updatedBy
                );
                
                // Se houver uma mensagem e não for vazia, registrá-la
                if (!empty($message)) {
                    $this->addStatusMessage($printStatusId, $message, $this->getMessageTypeFromStatus($newStatus));
                }
                
                // Emitir notificação se necessário
                if ($this->shouldSendNotification($currentStatus['status'], $newStatus, $currentStatus['progress_percentage'], $newProgress)) {
                    $this->triggerNotification($printStatusId, $newStatus, $newProgress);
                }
            }
            
            return $updated;
        } catch (Exception $e) {
            error_log("Erro ao atualizar status de impressão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma atualização de status no histórico
     * 
     * @param int $printStatusId ID do status de impressão
     * @param string|null $previousStatus Status anterior
     * @param string $newStatus Novo status
     * @param float|null $previousProgress Progresso anterior
     * @param float $newProgress Novo progresso
     * @param string $message Mensagem (opcional)
     * @param string $updatedBy Atualizador (opcional)
     * @return int|false ID do registro de atualização ou false em caso de erro
     */
    public function logStatusUpdate($printStatusId, $previousStatus, $newStatus, $previousProgress, $newProgress, $message = '', $updatedBy = 'system') {
        try {
            // Obter instância do modelo de atualizações
            $db = $this->db();
            
            // Dados para inserção
            $data = [
                'print_status_id' => $printStatusId,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'previous_progress' => $previousProgress,
                'new_progress' => $newProgress,
                'updated_by' => $updatedBy,
                'message' => $message
            ];
            
            // Inserir no banco de dados
            $sql = "INSERT INTO print_status_updates 
                    (print_status_id, previous_status, new_status, previous_progress, new_progress, updated_by, message)
                    VALUES (:print_status_id, :previous_status, :new_status, :previous_progress, :new_progress, :updated_by, :message)";
            
            return $db->execute($sql, $data);
        } catch (Exception $e) {
            error_log("Erro ao registrar atualização de status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adiciona uma mensagem de status
     * 
     * @param int $printStatusId ID do status de impressão
     * @param string $message Mensagem
     * @param string $type Tipo de mensagem (info, warning, error, success)
     * @param bool $visibleToCustomer Visível para o cliente
     * @return int|false ID da mensagem ou false em caso de erro
     */
    public function addStatusMessage($printStatusId, $message, $type = 'info', $visibleToCustomer = true) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            // Dados para inserção
            $data = [
                'print_status_id' => $printStatusId,
                'message' => $message,
                'type' => $type,
                'is_visible_to_customer' => $visibleToCustomer ? 1 : 0
            ];
            
            // Inserir no banco de dados
            $sql = "INSERT INTO status_messages 
                    (print_status_id, message, type, is_visible_to_customer)
                    VALUES (:print_status_id, :message, :type, :is_visible_to_customer)";
            
            return $db->execute($sql, $data);
        } catch (Exception $e) {
            error_log("Erro ao adicionar mensagem de status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra métricas de impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @param array $metrics Dados de métricas
     * @return int|false ID do registro de métricas ou false em caso de erro
     */
    public function recordMetrics($printStatusId, $metrics) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            // Verificar se foram fornecidos dados adicionais em formato JSON
            $additionalData = null;
            if (isset($metrics['additional_data'])) {
                $additionalData = json_encode($metrics['additional_data']);
                unset($metrics['additional_data']);
            }
            
            // Preparar dados base para inserção
            $data = ['print_status_id' => $printStatusId];
            
            // Adicionar métricas válidas
            $validFields = [
                'hotend_temp', 'bed_temp', 'speed_percentage', 'fan_speed_percentage',
                'layer_height', 'current_layer', 'total_layers', 'filament_used_mm',
                'print_time_remaining_seconds'
            ];
            
            foreach ($validFields as $field) {
                if (isset($metrics[$field])) {
                    $data[$field] = $metrics[$field];
                }
            }
            
            // Adicionar dados adicionais se existirem
            if ($additionalData !== null) {
                $data['additional_data'] = $additionalData;
            }
            
            // Construir consulta SQL
            $fields = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO print_metrics ($fields) VALUES ($placeholders)";
            
            // Executar inserção
            $insertId = $db->execute($sql, $data);
            
            // Atualizar cálculos de progresso e tempo estimado se necessário
            if ($insertId && isset($metrics['current_layer'], $metrics['total_layers'], $metrics['print_time_remaining_seconds'])) {
                $this->updateProgressFromMetrics($printStatusId, $metrics);
            }
            
            return $insertId;
        } catch (Exception $e) {
            error_log("Erro ao registrar métricas de impressão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o progresso e tempo estimado com base nas métricas
     * 
     * @param int $printStatusId ID do status de impressão
     * @param array $metrics Dados de métricas
     * @return bool Sucesso da operação
     */
    protected function updateProgressFromMetrics($printStatusId, $metrics) {
        try {
            // Calcular progresso se tivermos informações de camada
            $progress = null;
            if (isset($metrics['current_layer'], $metrics['total_layers']) && $metrics['total_layers'] > 0) {
                $progress = ($metrics['current_layer'] / $metrics['total_layers']) * 100;
                $progress = min(99.99, max(0, $progress)); // Limitar entre 0 e 99.99
            }
            
            // Calcular tempo estimado de conclusão se tivermos informações de tempo restante
            $estimatedCompletion = null;
            if (isset($metrics['print_time_remaining_seconds']) && $metrics['print_time_remaining_seconds'] > 0) {
                $estimatedCompletion = date('Y-m-d H:i:s', time() + $metrics['print_time_remaining_seconds']);
            }
            
            // Calcular tempo decorrido
            $currentStatus = $this->find($printStatusId);
            $elapsedTime = null;
            if ($currentStatus && $currentStatus['started_at']) {
                $startTime = strtotime($currentStatus['started_at']);
                $elapsedTime = time() - $startTime;
                $elapsedTime = max(0, $elapsedTime); // Garantir valor não negativo
            }
            
            // Atualizar registro de status
            $updateData = [];
            
            if ($progress !== null) {
                $updateData['progress_percentage'] = $progress;
            }
            
            if ($estimatedCompletion !== null) {
                $updateData['estimated_completion'] = $estimatedCompletion;
            }
            
            if ($elapsedTime !== null) {
                $updateData['elapsed_print_time_seconds'] = $elapsedTime;
            }
            
            if (!empty($updateData)) {
                return $this->update($printStatusId, $updateData);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar progresso a partir de métricas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o histórico de atualizações de um status de impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @param int $limit Limite de registros
     * @return array Lista de atualizações
     */
    public function getStatusUpdates($printStatusId, $limit = 50) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT * FROM print_status_updates 
                    WHERE print_status_id = :print_status_id 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            
            return $db->select($sql, [
                'print_status_id' => $printStatusId,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            error_log("Erro ao obter atualizações de status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as mensagens de status de uma impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @param bool $visibleToCustomerOnly Apenas mensagens visíveis para o cliente
     * @param int $limit Limite de registros
     * @return array Lista de mensagens
     */
    public function getStatusMessages($printStatusId, $visibleToCustomerOnly = false, $limit = 50) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT * FROM status_messages 
                    WHERE print_status_id = :print_status_id";
            
            $params = ['print_status_id' => $printStatusId];
            
            if ($visibleToCustomerOnly) {
                $sql .= " AND is_visible_to_customer = 1";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit";
            $params['limit'] = $limit;
            
            return $db->select($sql, $params);
        } catch (Exception $e) {
            error_log("Erro ao obter mensagens de status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as métricas recentes de uma impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @param int $limit Limite de registros
     * @return array Lista de métricas
     */
    public function getRecentMetrics($printStatusId, $limit = 100) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT * FROM print_metrics 
                    WHERE print_status_id = :print_status_id 
                    ORDER BY recorded_at DESC 
                    LIMIT :limit";
            
            return $db->select($sql, [
                'print_status_id' => $printStatusId,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            error_log("Erro ao obter métricas recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém dados detalhados de uma impressão incluindo status, métricas e mensagens
     * 
     * @param int $printStatusId ID do status de impressão
     * @return array|null Dados detalhados ou null se não encontrado
     */
    public function getDetailedStatus($printStatusId) {
        try {
            // Obter dados básicos do status
            $status = $this->find($printStatusId);
            if (!$status) {
                return null;
            }
            
            // Obter dados relacionados de outros modelos/tabelas
            $orderModel = new OrderModel();
            $productModel = new ProductModel();
            $queueModel = new PrintQueueModel();
            
            // Obter detalhes do pedido, produto e fila
            $status['order'] = $orderModel->find($status['order_id']);
            $status['product'] = $productModel->find($status['product_id']);
            $status['queue_item'] = $queueModel->find($status['print_queue_id']);
            
            // Obter impressora se houver
            if (!empty($status['printer_id'])) {
                $status['printer'] = $this->getPrinterDetails($status['printer_id']);
            } else {
                $status['printer'] = null;
            }
            
            // Obter métricas mais recentes
            $status['latest_metrics'] = $this->getLatestMetrics($printStatusId);
            
            // Obter as últimas 10 atualizações de status
            $status['recent_updates'] = $this->getStatusUpdates($printStatusId, 10);
            
            // Obter as últimas 10 mensagens
            $status['recent_messages'] = $this->getStatusMessages($printStatusId, false, 10);
            
            // Calcular estatísticas adicionais
            $status['formatted_status'] = self::getAvailableStatuses()[$status['status']] ?? $status['status'];
            $status['elapsed_time_formatted'] = $this->formatTimeInterval($status['elapsed_print_time_seconds'] ?? 0);
            
            if (!empty($status['started_at']) && !empty($status['estimated_completion'])) {
                $status['remaining_time_seconds'] = max(0, strtotime($status['estimated_completion']) - time());
                $status['remaining_time_formatted'] = $this->formatTimeInterval($status['remaining_time_seconds']);
            }
            
            return $status;
        } catch (Exception $e) {
            error_log("Erro ao obter status detalhado: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém as métricas mais recentes de uma impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @return array|null Métricas mais recentes ou null se não houver
     */
    public function getLatestMetrics($printStatusId) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT * FROM print_metrics 
                    WHERE print_status_id = :print_status_id 
                    ORDER BY recorded_at DESC 
                    LIMIT 1";
            
            $result = $db->select($sql, ['print_status_id' => $printStatusId]);
            
            if (!empty($result)) {
                $metrics = $result[0];
                
                // Descodificar dados adicionais JSON se existirem
                if (isset($metrics['additional_data'])) {
                    $metrics['additional_data'] = json_decode($metrics['additional_data'], true);
                }
                
                return $metrics;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erro ao obter métricas mais recentes: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém detalhes de uma impressora
     * 
     * @param string $printerId ID da impressora
     * @return array|null Detalhes da impressora ou null se não encontrada
     */
    public function getPrinterDetails($printerId) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT * FROM printer_settings 
                    WHERE printer_id = :printer_id 
                    LIMIT 1";
            
            $result = $db->select($sql, ['printer_id' => $printerId]);
            
            if (!empty($result)) {
                $printer = $result[0];
                
                // Descodificar filamentos disponíveis se existirem
                if (isset($printer['available_filaments'])) {
                    $printer['available_filaments'] = json_decode($printer['available_filaments'], true);
                }
                
                return $printer;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erro ao obter detalhes da impressora: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém impressões em andamento
     * 
     * @param int $limit Limite de registros
     * @param int $offset Deslocamento para paginação
     * @return array Lista de impressões em andamento
     */
    public function getActivePrints($limit = 10, $offset = 0) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT ps.*, 
                           o.customer_name, 
                           p.name as product_name,
                           p.model_file,
                           pq.priority,
                           pq.scheduled_date
                    FROM {$this->table} ps
                    LEFT JOIN orders o ON ps.order_id = o.id
                    LEFT JOIN products p ON ps.product_id = p.id
                    LEFT JOIN print_queue pq ON ps.print_queue_id = pq.id
                    WHERE ps.status IN ('preparing', 'printing', 'paused')
                    ORDER BY 
                        CASE ps.status
                            WHEN 'printing' THEN 1
                            WHEN 'preparing' THEN 2
                            WHEN 'paused' THEN 3
                            ELSE 4
                        END,
                        pq.priority DESC,
                        ps.started_at ASC
                    LIMIT :limit OFFSET :offset";
            
            return $db->select($sql, [
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (Exception $e) {
            error_log("Erro ao obter impressões ativas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém impressões concluídas recentemente
     * 
     * @param int $days Número de dias para consultar
     * @param int $limit Limite de registros
     * @return array Lista de impressões concluídas
     */
    public function getRecentlyCompletedPrints($days = 7, $limit = 20) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            // Calcular data limite
            $dateCutoff = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            $sql = "SELECT ps.*, 
                           o.customer_name, 
                           p.name as product_name,
                           p.model_file
                    FROM {$this->table} ps
                    LEFT JOIN orders o ON ps.order_id = o.id
                    LEFT JOIN products p ON ps.product_id = p.id
                    WHERE ps.status = 'completed'
                      AND ps.completed_at >= :date_cutoff
                    ORDER BY ps.completed_at DESC
                    LIMIT :limit";
            
            return $db->select($sql, [
                'date_cutoff' => $dateCutoff,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            error_log("Erro ao obter impressões concluídas recentemente: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém status de impressão de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de status de impressão relacionados ao pedido
     */
    public function getByOrderId($orderId) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT ps.*, 
                           p.name as product_name,
                           p.model_file
                    FROM {$this->table} ps
                    LEFT JOIN products p ON ps.product_id = p.id
                    WHERE ps.order_id = :order_id
                    ORDER BY ps.id ASC";
            
            $printStatuses = $db->select($sql, ['order_id' => $orderId]);
            
            // Adicionar status formatado
            $availableStatuses = self::getAvailableStatuses();
            foreach ($printStatuses as &$status) {
                $status['formatted_status'] = $availableStatuses[$status['status']] ?? $status['status'];
            }
            
            return $printStatuses;
        } catch (Exception $e) {
            error_log("Erro ao obter status de impressão por pedido: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém status de impressão por ID da fila
     * 
     * @param int $queueId ID da fila
     * @return array|null Status de impressão ou null se não encontrado
     */
    public function getByQueueId($queueId) {
        try {
            // Obter instância do banco de dados
            $db = $this->db();
            
            $sql = "SELECT * FROM {$this->table} 
                    WHERE print_queue_id = :queue_id 
                    LIMIT 1";
            
            $result = $db->select($sql, ['queue_id' => $queueId]);
            
            return !empty($result) ? $result[0] : null;
        } catch (Exception $e) {
            error_log("Erro ao obter status de impressão por ID da fila: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtem o tipo de mensagem com base no status
     * 
     * @param string $status Status de impressão
     * @return string Tipo de mensagem
     */
    protected function getMessageTypeFromStatus($status) {
        switch ($status) {
            case self::STATUS_COMPLETED:
                return 'success';
            case self::STATUS_FAILED:
                return 'error';
            case self::STATUS_PAUSED:
            case self::STATUS_CANCELED:
                return 'warning';
            default:
                return 'info';
        }
    }
    
    /**
     * Verifica se deve enviar uma notificação com base nas alterações
     * 
     * @param string $oldStatus Status anterior
     * @param string $newStatus Novo status
     * @param float $oldProgress Progresso anterior
     * @param float $newProgress Novo progresso
     * @return bool True se deve enviar notificação
     */
    protected function shouldSendNotification($oldStatus, $newStatus, $oldProgress, $newProgress) {
        // Sempre notificar mudanças de status
        if ($oldStatus !== $newStatus) {
            return true;
        }
        
        // Notificar a cada 25% de progresso
        if ($oldProgress < 25 && $newProgress >= 25) return true;
        if ($oldProgress < 50 && $newProgress >= 50) return true;
        if ($oldProgress < 75 && $newProgress >= 75) return true;
        if ($oldProgress < 99 && $newProgress >= 99) return true;
        
        return false;
    }
    
    /**
     * Emite uma notificação para o cliente e admin
     * 
     * @param int $printStatusId ID do status de impressão
     * @param string $status Status atual
     * @param float $progress Progresso atual
     * @return bool Sucesso da operação
     */
    protected function triggerNotification($printStatusId, $status, $progress) {
        try {
            // Obter detalhes do status
            $printStatus = $this->find($printStatusId);
            if (!$printStatus) {
                return false;
            }
            
            // Obter dados relacionados necessários
            $orderModel = new OrderModel();
            $productModel = new ProductModel();
            $order = $orderModel->find($printStatus['order_id']);
            $product = $productModel->find($printStatus['product_id']);
            
            if (!$order || !$product) {
                return false;
            }
            
            // Verificar preferências de notificação do usuário
            $notifySettings = $this->getUserNotificationSettings($order['user_id']);
            
            // Preparar dados para notificação
            $notificationData = [
                'print_status_id' => $printStatusId,
                'order_id' => $printStatus['order_id'],
                'product_name' => $product['name'],
                'status' => $status,
                'formatted_status' => self::getAvailableStatuses()[$status] ?? $status,
                'progress' => $progress,
                'message' => $this->getNotificationMessage($status, $progress, $product['name'])
            ];
            
            // Chamar o NotificationHelper para enviar a notificação
            if (class_exists('NotificationHelper')) {
                $notificationHelper = new NotificationHelper();
                
                // Determinar tipo de notificação
                $notificationType = 'print_progress';
                if ($status === self::STATUS_COMPLETED) {
                    $notificationType = 'print_completed';
                } elseif ($status === self::STATUS_FAILED) {
                    $notificationType = 'print_failed';
                }
                
                // Enviar para o cliente
                if ($this->shouldNotifyClient($status, $progress, $notifySettings)) {
                    $notificationHelper->sendNotification(
                        $order['user_id'],
                        $notificationType,
                        $notificationData
                    );
                }
                
                // Enviar para administradores (sempre)
                $notificationHelper->sendAdminNotification(
                    $notificationType,
                    $notificationData
                );
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém configurações de notificação do usuário
     * 
     * @param int $userId ID do usuário
     * @return array Configurações de notificação
     */
    protected function getUserNotificationSettings($userId) {
        try {
            // Verificar se existe configuração personalizada
            $db = $this->db();
            
            $sql = "SELECT * FROM print_notification_settings 
                    WHERE user_id = :user_id 
                    LIMIT 1";
            
            $result = $db->select($sql, ['user_id' => $userId]);
            
            if (!empty($result)) {
                return $result[0];
            }
            
            // Retornar configurações padrão
            return [
                'notify_on_start' => 1,
                'notify_on_complete' => 1,
                'notify_on_failure' => 1,
                'notify_on_pause' => 0,
                'notify_on_resume' => 0,
                'notify_on_progress' => 0,
                'progress_notification_interval' => 25,
                'email_notifications' => 1,
                'sms_notifications' => 0,
                'web_notifications' => 1
            ];
        } catch (Exception $e) {
            error_log("Erro ao obter configurações de notificação: " . $e->getMessage());
            
            // Retornar configurações padrão em caso de erro
            return [
                'notify_on_start' => 1,
                'notify_on_complete' => 1,
                'notify_on_failure' => 1,
                'notify_on_pause' => 0,
                'notify_on_resume' => 0,
                'notify_on_progress' => 0,
                'progress_notification_interval' => 25,
                'email_notifications' => 1,
                'sms_notifications' => 0,
                'web_notifications' => 1
            ];
        }
    }
    
    /**
     * Verifica se deve notificar o cliente com base nas configurações e estado atual
     * 
     * @param string $status Status atual
     * @param float $progress Progresso atual
     * @param array $settings Configurações de notificação
     * @return bool True se deve notificar o cliente
     */
    protected function shouldNotifyClient($status, $progress, $settings) {
        // Verificar com base no status
        switch ($status) {
            case self::STATUS_PRINTING:
                if ($progress < 5) {
                    return isset($settings['notify_on_start']) && $settings['notify_on_start'];
                }
                break;
                
            case self::STATUS_COMPLETED:
                return isset($settings['notify_on_complete']) && $settings['notify_on_complete'];
                
            case self::STATUS_FAILED:
                return isset($settings['notify_on_failure']) && $settings['notify_on_failure'];
                
            case self::STATUS_PAUSED:
                return isset($settings['notify_on_pause']) && $settings['notify_on_pause'];
        }
        
        // Verificar notificações de progresso
        if (isset($settings['notify_on_progress']) && $settings['notify_on_progress']) {
            $interval = $settings['progress_notification_interval'] ?? 25;
            
            // Notificar nos intervalos configurados
            $threshold = floor($progress / $interval) * $interval;
            $previousThreshold = floor(($progress - 1) / $interval) * $interval;
            
            return $threshold !== $previousThreshold;
        }
        
        return false;
    }
    
    /**
     * Obtém mensagem de notificação com base no status
     * 
     * @param string $status Status atual
     * @param float $progress Progresso atual
     * @param string $productName Nome do produto
     * @return string Mensagem de notificação
     */
    protected function getNotificationMessage($status, $progress, $productName) {
        switch ($status) {
            case self::STATUS_PRINTING:
                if ($progress < 5) {
                    return "A impressão de \"$productName\" foi iniciada.";
                }
                return "A impressão de \"$productName\" está {$progress}% concluída.";
                
            case self::STATUS_COMPLETED:
                return "A impressão de \"$productName\" foi concluída com sucesso!";
                
            case self::STATUS_FAILED:
                return "Ocorreu um problema com a impressão de \"$productName\". Nossa equipe já foi notificada.";
                
            case self::STATUS_PAUSED:
                return "A impressão de \"$productName\" foi pausada temporariamente.";
                
            case self::STATUS_PREPARING:
                return "Estamos preparando a impressão de \"$productName\".";
                
            case self::STATUS_CANCELED:
                return "A impressão de \"$productName\" foi cancelada.";
                
            default:
                return "Atualização de status da impressão de \"$productName\": "
                     . (self::getAvailableStatuses()[$status] ?? $status)
                     . " ({$progress}% concluído).";
        }
    }
    
    /**
     * Formata um intervalo de tempo em segundos para uma string legível
     * 
     * @param int $seconds Número de segundos
     * @return string Tempo formatado
     */
    protected function formatTimeInterval($seconds) {
        if ($seconds < 60) {
            return "$seconds segundos";
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return "$minutes min" . ($secs > 0 ? " $secs seg" : "");
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "$hours h" . ($minutes > 0 ? " $minutes min" : "");
    }
}
