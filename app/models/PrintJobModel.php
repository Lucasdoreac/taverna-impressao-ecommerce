<?php
/**
 * PrintJobModel - Gerencia os trabalhos de impressão 3D
 * 
 * @package    Taverna da Impressão 3D
 * @author     Claude
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/PrintQueueModel.php';
require_once __DIR__ . '/PrinterModel.php';
require_once __DIR__ . '/NotificationModel.php';

class PrintJobModel {
    use InputValidationTrait;
    
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Cria um novo trabalho de impressão
     *
     * @param int $queueId ID do item na fila
     * @param int $printerId ID da impressora
     * @param string|null $scheduledStartTime Horário de início programado (opcional)
     * @param string $notes Notas sobre o trabalho
     * @return int|false ID do trabalho ou false se falhar
     */
    public function saveJob($queueId, $printerId, $scheduledStartTime = null, $notes = '') {
        try {
            // Validar parâmetros
            $queueId = (int)$queueId;
            $printerId = (int)$printerId;
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Verificar se o item na fila existe
            $printQueueModel = new PrintQueueModel();
            $queueItem = $printQueueModel->getQueueItemById($queueId);
            
            if (!$queueItem) {
                return false;
            }
            
            // Verificar se a impressora existe
            $printerModel = new PrinterModel();
            $printer = $printerModel->getPrinterById($printerId);
            
            if (!$printer) {
                return false;
            }
            
            // Verificar se o item já tem um trabalho associado
            $existingJob = $this->getJobByQueueId($queueId);
            if ($existingJob) {
                return false;
            }
            
            // Calcular tempo estimado de conclusão com base nas configurações do item
            $estimatedEndTime = null;
            $startTime = null;
            
            // Tratar scheduledStartTime se fornecido
            if ($scheduledStartTime !== null) {
                $startTime = $scheduledStartTime;
                
                // Calcular estimatedEndTime se houver tempo estimado nas configurações
                if (isset($queueItem['print_settings']['estimated_print_time_hours'])) {
                    $startDateTime = new DateTime($startTime);
                    $estimatedHours = (float)$queueItem['print_settings']['estimated_print_time_hours'];
                    $endDateTime = clone $startDateTime;
                    $endDateTime->add(new DateInterval('PT' . ceil($estimatedHours * 60) . 'M'));
                    $estimatedEndTime = $endDateTime->format('Y-m-d H:i:s');
                }
            }
            
            // Preparar dados para inserção
            $data = [
                'queue_id' => $queueId,
                'printer_id' => $printerId,
                'start_time' => $startTime,
                'estimated_end_time' => $estimatedEndTime,
                'status' => 'pending',
                'progress' => 0,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Inserir na tabela print_jobs
            $sql = "INSERT INTO print_jobs (queue_id, printer_id, start_time, estimated_end_time, status, progress, notes, created_at) 
                    VALUES (:queue_id, :printer_id, :start_time, :estimated_end_time, :status, :progress, :notes, :created_at)";
            
            $params = [
                ':queue_id' => $data['queue_id'],
                ':printer_id' => $data['printer_id'],
                ':start_time' => $data['start_time'],
                ':estimated_end_time' => $data['estimated_end_time'],
                ':status' => $data['status'],
                ':progress' => $data['progress'],
                ':notes' => $data['notes'],
                ':created_at' => $data['created_at']
            ];
            
            $this->db->execute($sql, $params);
            $jobId = $this->db->lastInsertId();
            
            if ($jobId) {
                // Atualizar o status do item na fila para 'assigned'
                $printQueueModel->updateStatus($queueId, 'assigned');
                
                // Atualizar o current_job_id da impressora
                $printerModel->updateCurrentJob($printerId, $jobId);
                
                // Enviar notificação ao usuário
                $this->notifyJobCreated($jobId);
                
                return $jobId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao criar trabalho de impressão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de um trabalho
     *
     * @param int $jobId ID do trabalho
     * @param string $status Novo status
     * @param string $notes Notas adicionais
     * @return bool True se a operação foi bem-sucedida
     */
    public function updateStatus($jobId, $status, $notes = '') {
        try {
            // Validar parâmetros
            $jobId = (int)$jobId;
            $status = $this->validateString($status, ['allowedValues' => ['pending', 'preparing', 'printing', 'post-processing', 'completed', 'failed']]);
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Obter trabalho atual para comparação
            $currentJob = $this->getJobById($jobId);
            if (!$currentJob) {
                return false;
            }
            
            $previousStatus = $currentJob['status'];
            
            // Verificar se a transição de status é válida
            if (!$this->isValidStatusTransition($previousStatus, $status)) {
                error_log("Transição de status inválida para trabalho: {$previousStatus} -> {$status}");
                return false;
            }
            
            // Preparar campos para atualização
            $updateFields = ["status = :status"];
            $params = [
                ':status' => $status,
                ':id' => $jobId
            ];
            
            // Adicionar notas se fornecidas
            if (!empty($notes)) {
                $updateFields[] = "notes = CONCAT(IFNULL(notes, ''), '\n\n', :notes_date, ': ', :notes)";
                $params[':notes_date'] = date('Y-m-d H:i:s');
                $params[':notes'] = $notes;
            }
            
            // Para alterações específicas de status
            switch ($status) {
                case 'printing':
                    // Se estiver iniciando a impressão, definir start_time
                    if ($previousStatus !== 'printing') {
                        $updateFields[] = "start_time = NOW()";
                        
                        // Calcular tempo estimado de conclusão
                        $printQueueModel = new PrintQueueModel();
                        $queueItem = $printQueueModel->getQueueItemById($currentJob['queue_id']);
                        
                        if ($queueItem && isset($queueItem['print_settings']['estimated_print_time_hours'])) {
                            $estimatedHours = (float)$queueItem['print_settings']['estimated_print_time_hours'];
                            $buffer = $printQueueModel->getSettings('estimated_time_buffer', 10) / 100; // Buffer em porcentagem
                            $estimatedHours *= (1 + $buffer); // Adicionar buffer
                            
                            $now = new DateTime();
                            $endDateTime = clone $now;
                            $endDateTime->add(new DateInterval('PT' . ceil($estimatedHours * 60) . 'M'));
                            
                            $updateFields[] = "estimated_end_time = :estimated_end_time";
                            $params[':estimated_end_time'] = $endDateTime->format('Y-m-d H:i:s');
                        }
                    }
                    break;
                    
                case 'completed':
                case 'failed':
                    // Se estiver concluindo ou falhando, definir actual_end_time
                    $updateFields[] = "actual_end_time = NOW()";
                    break;
            }
            
            // Atualizar o trabalho
            $updateFields[] = "updated_at = NOW()";
            $sql = "UPDATE print_jobs SET " . implode(", ", $updateFields) . " WHERE id = :id";
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                // Notificar sobre a alteração de status
                $this->notifyStatusChange($jobId, $status);
                
                // Para status completed ou failed, atualizar o status da impressora
                if (($status === 'completed' || $status === 'failed') && ($previousStatus !== 'completed' && $previousStatus !== 'failed')) {
                    $printerModel = new PrinterModel();
                    $printerModel->updateStatus($currentJob['printer_id'], 'available');
                    $printerModel->updateCurrentJob($currentJob['printer_id'], null);
                }
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar status do trabalho: ' . $e->getMessage());
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
            'pending' => ['preparing', 'printing', 'failed'],
            'preparing' => ['printing', 'failed'],
            'printing' => ['post-processing', 'completed', 'failed'],
            'post-processing' => ['completed', 'failed'],
            'completed' => [], // Status final, não permite transição
            'failed' => ['pending'] // Permitir tentar novamente
        ];
        
        // Verificar se a transição é válida
        if (isset($validTransitions[$currentStatus]) && in_array($newStatus, $validTransitions[$currentStatus])) {
            return true;
        }
        
        // Verificar se é o mesmo status (não é uma transição)
        if ($currentStatus === $newStatus) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Atualiza o progresso de um trabalho
     *
     * @param int $jobId ID do trabalho
     * @param float $progress Progresso (0-100)
     * @return bool True se a operação foi bem-sucedida
     */
    public function updateProgress($jobId, $progress) {
        try {
            // Validar parâmetros
            $jobId = (int)$jobId;
            $progress = max(0, min(100, (float)$progress)); // Garantir que o progresso esteja entre 0 e 100
            
            // Verificar se o trabalho existe
            $job = $this->getJobById($jobId);
            if (!$job) {
                return false;
            }
            
            // Atualizar o progresso
            $sql = "UPDATE print_jobs SET progress = :progress, updated_at = NOW() WHERE id = :id";
            $params = [
                ':progress' => $progress,
                ':id' => $jobId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar progresso do trabalho: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Define o tempo de início de um trabalho
     *
     * @param int $jobId ID do trabalho
     * @param string $startTime Data/hora de início
     * @return bool True se a operação foi bem-sucedida
     */
    public function setStartTime($jobId, $startTime) {
        try {
            // Validar parâmetros
            $jobId = (int)$jobId;
            
            // Verificar se o trabalho existe
            $job = $this->getJobById($jobId);
            if (!$job) {
                return false;
            }
            
            // Atualizar o tempo de início
            $sql = "UPDATE print_jobs SET start_time = :start_time, updated_at = NOW() WHERE id = :id";
            $params = [
                ':start_time' => $startTime,
                ':id' => $jobId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao definir tempo de início do trabalho: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Define o tempo de término de um trabalho
     *
     * @param int $jobId ID do trabalho
     * @param string $endTime Data/hora de término
     * @return bool True se a operação foi bem-sucedida
     */
    public function setEndTime($jobId, $endTime) {
        try {
            // Validar parâmetros
            $jobId = (int)$jobId;
            
            // Verificar se o trabalho existe
            $job = $this->getJobById($jobId);
            if (!$job) {
                return false;
            }
            
            // Atualizar o tempo de término
            $sql = "UPDATE print_jobs SET actual_end_time = :end_time, updated_at = NOW() WHERE id = :id";
            $params = [
                ':end_time' => $endTime,
                ':id' => $jobId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao definir tempo de término do trabalho: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra o uso de material em um trabalho
     *
     * @param int $jobId ID do trabalho
     * @param float $materialUsed Quantidade de material usado (em gramas)
     * @return bool True se a operação foi bem-sucedida
     */
    public function setMaterialUsed($jobId, $materialUsed) {
        try {
            // Validar parâmetros
            $jobId = (int)$jobId;
            $materialUsed = max(0, (float)$materialUsed); // Garantir valor positivo
            
            // Verificar se o trabalho existe
            $job = $this->getJobById($jobId);
            if (!$job) {
                return false;
            }
            
            // Atualizar o uso de material
            $sql = "UPDATE print_jobs SET material_used = :material_used, updated_at = NOW() WHERE id = :id";
            $params = [
                ':material_used' => $materialUsed,
                ':id' => $jobId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar uso de material: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém um trabalho pelo ID
     *
     * @param int $jobId ID do trabalho
     * @return array|false Dados do trabalho ou false se não encontrado
     */
    public function getJobById($jobId) {
        try {
            $jobId = (int)$jobId;
            
            $sql = "SELECT pj.*, 
                    p.name AS printer_name, 
                    pq.status AS queue_status,
                    cm.original_name AS model_name,
                    u.name AS user_name, 
                    u.email AS user_email
                    FROM print_jobs pj
                    JOIN printers p ON pj.printer_id = p.id
                    JOIN print_queue pq ON pj.queue_id = pq.id
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pj.id = :id";
            
            $params = [':id' => $jobId];
            return $this->db->fetchSingle($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter trabalho: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém um trabalho pelo ID do item na fila
     *
     * @param int $queueId ID do item na fila
     * @return array|false Dados do trabalho ou false se não encontrado
     */
    public function getJobByQueueId($queueId) {
        try {
            $queueId = (int)$queueId;
            
            $sql = "SELECT pj.*, 
                    p.name AS printer_name, 
                    pq.status AS queue_status,
                    cm.original_name AS model_name,
                    u.name AS user_name, 
                    u.email AS user_email
                    FROM print_jobs pj
                    JOIN printers p ON pj.printer_id = p.id
                    JOIN print_queue pq ON pj.queue_id = pq.id
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pj.queue_id = :queue_id";
            
            $params = [':queue_id' => $queueId];
            return $this->db->fetchSingle($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter trabalho por ID da fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém trabalhos associados a uma impressora
     *
     * @param int $printerId ID da impressora
     * @param string|null $status Filtrar por status (opcional)
     * @return array Lista de trabalhos
     */
    public function getPrinterJobs($printerId, $status = null) {
        try {
            $printerId = (int)$printerId;
            
            $sql = "SELECT pj.*, 
                    pq.status AS queue_status,
                    cm.original_name AS model_name,
                    u.name AS user_name, 
                    u.email AS user_email
                    FROM print_jobs pj
                    JOIN print_queue pq ON pj.queue_id = pq.id
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pj.printer_id = :printer_id";
            
            $params = [':printer_id' => $printerId];
            
            // Adicionar filtro de status se fornecido
            if ($status !== null) {
                $status = $this->validateString($status, ['allowedValues' => ['pending', 'preparing', 'printing', 'post-processing', 'completed', 'failed']]);
                $sql .= " AND pj.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY pj.created_at DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter trabalhos da impressora: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém trabalhos de um usuário específico
     *
     * @param int $userId ID do usuário
     * @param string|null $status Filtrar por status (opcional)
     * @return array Lista de trabalhos
     */
    public function getUserJobs($userId, $status = null) {
        try {
            $userId = (int)$userId;
            
            $sql = "SELECT pj.*, 
                    p.name AS printer_name, 
                    pq.status AS queue_status,
                    cm.original_name AS model_name
                    FROM print_jobs pj
                    JOIN printers p ON pj.printer_id = p.id
                    JOIN print_queue pq ON pj.queue_id = pq.id
                    JOIN customer_models cm ON pq.model_id = cm.id
                    WHERE pq.user_id = :user_id";
            
            $params = [':user_id' => $userId];
            
            // Adicionar filtro de status se fornecido
            if ($status !== null) {
                $status = $this->validateString($status, ['allowedValues' => ['pending', 'preparing', 'printing', 'post-processing', 'completed', 'failed']]);
                $sql .= " AND pj.status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY pj.created_at DESC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter trabalhos do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém trabalhos atualmente em execução
     *
     * @return array Lista de trabalhos em execução
     */
    public function getCurrentJobs() {
        try {
            $sql = "SELECT pj.*, 
                    p.name AS printer_name, 
                    pq.status AS queue_status,
                    cm.original_name AS model_name,
                    u.name AS user_name, 
                    u.email AS user_email
                    FROM print_jobs pj
                    JOIN printers p ON pj.printer_id = p.id
                    JOIN print_queue pq ON pj.queue_id = pq.id
                    JOIN customer_models cm ON pq.model_id = cm.id
                    JOIN users u ON pq.user_id = u.id
                    WHERE pj.status IN ('preparing', 'printing', 'post-processing')
                    ORDER BY pj.start_time ASC";
            
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log('Erro ao obter trabalhos em execução: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas de trabalhos de impressão
     *
     * @return array Estatísticas
     */
    public function getJobStatistics() {
        try {
            $stats = [
                'total' => 0,
                'by_status' => [
                    'pending' => 0,
                    'preparing' => 0,
                    'printing' => 0,
                    'post-processing' => 0,
                    'completed' => 0,
                    'failed' => 0
                ],
                'avg_print_time' => 0,
                'completion_rate' => 0,
                'total_material_used' => 0
            ];
            
            // Contar totais por status
            $sql = "SELECT status, COUNT(*) as count FROM print_jobs GROUP BY status";
            $results = $this->db->fetchAll($sql);
            
            if ($results) {
                foreach ($results as $row) {
                    $stats['by_status'][$row['status']] = (int)$row['count'];
                    $stats['total'] += (int)$row['count'];
                }
            }
            
            // Calcular tempo médio de impressão para trabalhos concluídos
            $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, start_time, actual_end_time)) as avg_minutes
                    FROM print_jobs
                    WHERE status = 'completed'
                    AND start_time IS NOT NULL
                    AND actual_end_time IS NOT NULL";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_minutes'] !== null) {
                $stats['avg_print_time'] = round($result['avg_minutes'] / 60, 2); // Converter para horas
            }
            
            // Calcular taxa de conclusão
            if ($stats['total'] > 0) {
                $completedJobs = $stats['by_status']['completed'];
                $stats['completion_rate'] = round(($completedJobs / $stats['total']) * 100, 2);
            }
            
            // Calcular uso total de material
            $sql = "SELECT SUM(material_used) as total_material FROM print_jobs WHERE material_used IS NOT NULL";
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['total_material'] !== null) {
                $stats['total_material_used'] = round($result['total_material'], 2);
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de trabalhos: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [
                    'pending' => 0,
                    'preparing' => 0,
                    'printing' => 0,
                    'post-processing' => 0,
                    'completed' => 0,
                    'failed' => 0
                ],
                'avg_print_time' => 0,
                'completion_rate' => 0,
                'total_material_used' => 0
            ];
        }
    }
    
    /**
     * Envia notificação sobre a criação de um trabalho
     *
     * @param int $jobId ID do trabalho
     * @return bool True se a notificação foi enviada
     */
    private function notifyJobCreated($jobId) {
        try {
            // Obter dados do trabalho
            $job = $this->getJobById($jobId);
            if (!$job) {
                return false;
            }
            
            // Obter dados da fila relacionada
            $printQueueModel = new PrintQueueModel();
            $queueItem = $printQueueModel->getQueueItemById($job['queue_id']);
            
            if (!$queueItem) {
                return false;
            }
            
            // Criar notificação para o usuário
            $notificationModel = new NotificationModel();
            return $notificationModel->create([
                'user_id' => $queueItem['user_id'],
                'title' => 'Seu modelo foi agendado para impressão',
                'message' => "Seu modelo '{$job['model_name']}' foi agendado para impressão na impressora '{$job['printer_name']}'.",
                'type' => 'info',
                'related_id' => $jobId,
                'related_type' => 'print_job'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao enviar notificação de criação de trabalho: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia notificação sobre mudança de status de um trabalho
     *
     * @param int $jobId ID do trabalho
     * @param string $status Novo status
     * @return bool True se a notificação foi enviada
     */
    private function notifyStatusChange($jobId, $status) {
        try {
            // Obter dados do trabalho
            $job = $this->getJobById($jobId);
            if (!$job) {
                return false;
            }
            
            // Obter dados da fila relacionada
            $printQueueModel = new PrintQueueModel();
            $queueItem = $printQueueModel->getQueueItemById($job['queue_id']);
            
            if (!$queueItem) {
                return false;
            }
            
            // Definir título e mensagem com base no status
            $title = '';
            $message = '';
            $type = 'info';
            
            switch ($status) {
                case 'preparing':
                    $title = 'Preparando seu modelo para impressão';
                    $message = "Seu modelo '{$job['model_name']}' está sendo preparado para impressão.";
                    break;
                    
                case 'printing':
                    $title = 'Seu modelo está sendo impresso';
                    $message = "Seu modelo '{$job['model_name']}' começou a ser impresso na impressora '{$job['printer_name']}'.";
                    break;
                    
                case 'post-processing':
                    $title = 'Seu modelo está em pós-processamento';
                    $message = "A impressão do seu modelo '{$job['model_name']}' foi concluída e está em fase de pós-processamento.";
                    break;
                    
                case 'completed':
                    $title = 'Impressão concluída com sucesso';
                    $message = "A impressão do seu modelo '{$job['model_name']}' foi concluída com sucesso!";
                    $type = 'success';
                    break;
                    
                case 'failed':
                    $title = 'Falha na impressão';
                    $message = "Houve uma falha na impressão do seu modelo '{$job['model_name']}'. Nossa equipe irá entrar em contato para resolver o problema.";
                    $type = 'error';
                    break;
                    
                default:
                    $title = 'Atualização de status da impressão';
                    $message = "O status da impressão do seu modelo '{$job['model_name']}' foi atualizado para '{$status}'.";
                    break;
            }
            
            // Criar notificação para o usuário
            $notificationModel = new NotificationModel();
            return $notificationModel->create([
                'user_id' => $queueItem['user_id'],
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'related_id' => $jobId,
                'related_type' => 'print_job'
            ]);
        } catch (Exception $e) {
            error_log('Erro ao enviar notificação de mudança de status: ' . $e->getMessage());
            return false;
        }
    }
}
