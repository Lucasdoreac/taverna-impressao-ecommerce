<?php
/**
 * CustomerModelModel - Gerencia os modelos 3D enviados pelos clientes
 */
class CustomerModelModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Salva um novo modelo 3D enviado pelo cliente
     *
     * @param int $userId ID do usuário que enviou o modelo
     * @param string $fileName Nome do arquivo no servidor
     * @param string $originalName Nome original do arquivo
     * @param int $fileSize Tamanho do arquivo em bytes
     * @param string $fileType Tipo do arquivo (ex: 'stl', 'obj')
     * @param string $notes Notas adicionais sobre o modelo
     * @param string $status Status inicial do modelo (padrão: 'pending_validation')
     * @param array $validationResult Resultado da validação do modelo (opcional)
     * @return int|bool ID do modelo inserido ou false em caso de erro
     */
    public function saveModel($userId, $fileName, $originalName, $fileSize, $fileType, $notes = '', $status = 'pending_validation', $validationResult = null) {
        $sql = "INSERT INTO customer_models (user_id, file_name, original_name, file_size, file_type, notes, status, validation_data, created_at) 
                VALUES (:user_id, :file_name, :original_name, :file_size, :file_type, :notes, :status, :validation_data, NOW())";
        
        // Serializar o resultado da validação para armazenamento
        $validationData = null;
        if ($validationResult) {
            $validationData = json_encode($validationResult);
        }
        
        $params = [
            ':user_id' => $userId,
            ':file_name' => $fileName,
            ':original_name' => $originalName,
            ':file_size' => $fileSize,
            ':file_type' => $fileType,
            ':notes' => $notes,
            ':status' => $status,
            ':validation_data' => $validationData
        ];
        
        try {
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erro ao salvar modelo 3D: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de um modelo 3D
     *
     * @param int $modelId ID do modelo
     * @param string $status Novo status (pending_validation, approved, rejected)
     * @param string $notes Notas adicionais sobre a validação
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function updateStatus($modelId, $status, $notes = null) {
        $sql = "UPDATE customer_models 
                SET status = :status, updated_at = NOW()";
        
        $params = [
            ':status' => $status,
            ':id' => $modelId
        ];
        
        if ($notes !== null) {
            $sql .= ", admin_notes = :notes";
            $params[':notes'] = $notes;
        }
        
        $sql .= " WHERE id = :id";
        
        try {
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao atualizar status do modelo 3D: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza as notas de um modelo 3D
     *
     * @param int $modelId ID do modelo
     * @param string $notes Novas notas
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function updateNotes($modelId, $notes) {
        $sql = "UPDATE customer_models SET notes = :notes, updated_at = NOW() WHERE id = :id";
        
        $params = [
            ':notes' => $notes,
            ':id' => $modelId
        ];
        
        try {
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao atualizar notas do modelo 3D: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza os dados de validação de um modelo 3D
     *
     * @param int $modelId ID do modelo
     * @param array $validationData Dados da validação
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function updateValidationData($modelId, $validationData) {
        $sql = "UPDATE customer_models SET validation_data = :validation_data, updated_at = NOW() WHERE id = :id";
        
        $params = [
            ':validation_data' => json_encode($validationData),
            ':id' => $modelId
        ];
        
        try {
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao atualizar dados de validação do modelo 3D: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém um modelo 3D pelo ID
     *
     * @param int $modelId ID do modelo
     * @return array|false Dados do modelo ou false se não encontrado
     */
    public function getModelById($modelId) {
        $sql = "SELECT * FROM customer_models WHERE id = :id";
        
        try {
            $model = $this->db->fetchSingle($sql, [':id' => $modelId]);
            
            // Deserializar os dados de validação, se houver
            if ($model && isset($model['validation_data']) && !empty($model['validation_data'])) {
                $model['validation_data'] = json_decode($model['validation_data'], true);
            }
            
            return $model;
        } catch (Exception $e) {
            error_log('Erro ao obter modelo 3D: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lista todos os modelos 3D de um usuário
     *
     * @param int $userId ID do usuário
     * @param string|null $status Filtrar por status (opcional)
     * @return array Lista de modelos do usuário
     */
    public function getUserModels($userId, $status = null) {
        $sql = "SELECT * FROM customer_models WHERE user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if ($status !== null) {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        try {
            $models = $this->db->fetchAll($sql, $params);
            
            // Deserializar os dados de validação para cada modelo, se houver
            foreach ($models as &$model) {
                if (isset($model['validation_data']) && !empty($model['validation_data'])) {
                    $model['validation_data'] = json_decode($model['validation_data'], true);
                }
            }
            
            return $models;
        } catch (Exception $e) {
            error_log('Erro ao listar modelos 3D do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Lista todos os modelos pendentes de validação
     *
     * @return array Lista de modelos pendentes
     */
    public function getPendingModels() {
        $sql = "SELECT cm.*, u.name as user_name, u.email as user_email 
                FROM customer_models cm
                JOIN users u ON cm.user_id = u.id
                WHERE cm.status = 'pending_validation'
                ORDER BY cm.created_at ASC";
        
        try {
            $models = $this->db->fetchAll($sql);
            
            // Deserializar os dados de validação para cada modelo, se houver
            foreach ($models as &$model) {
                if (isset($model['validation_data']) && !empty($model['validation_data'])) {
                    $model['validation_data'] = json_decode($model['validation_data'], true);
                }
            }
            
            return $models;
        } catch (Exception $e) {
            error_log('Erro ao listar modelos 3D pendentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exclui um modelo 3D
     *
     * @param int $modelId ID do modelo
     * @return bool True se excluído com sucesso, false caso contrário
     */
    public function deleteModel($modelId) {
        // Primeiro, obter informações do modelo para excluir o arquivo físico
        $model = $this->getModelById($modelId);
        if (!$model) {
            return false;
        }
        
        $sql = "DELETE FROM customer_models WHERE id = :id";
        
        try {
            $result = $this->db->execute($sql, [':id' => $modelId]);
            
            // Se a exclusão no banco foi bem sucedida, excluir o arquivo físico
            if ($result) {
                // Determinar o diretório baseado no status
                $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/models/';
                
                switch ($model['status']) {
                    case 'pending_validation':
                        $filePath = $baseDir . 'quarantine/' . $model['file_name'];
                        break;
                    case 'approved':
                        $filePath = $baseDir . 'approved/' . $model['file_name'];
                        break;
                    case 'rejected':
                        $filePath = $baseDir . 'rejected/' . $model['file_name'];
                        break;
                    default:
                        $filePath = $baseDir . 'quarantine/' . $model['file_name'];
                }
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Erro ao excluir modelo 3D: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se um modelo pertence a um usuário específico
     *
     * @param int $modelId ID do modelo
     * @param int $userId ID do usuário
     * @return bool True se o modelo pertence ao usuário, false caso contrário
     */
    public function isModelOwnedByUser($modelId, $userId) {
        $sql = "SELECT COUNT(*) as count FROM customer_models 
                WHERE id = :id AND user_id = :user_id";
        
        try {
            $result = $this->db->fetchSingle($sql, [
                ':id' => $modelId,
                ':user_id' => $userId
            ]);
            
            return ($result && $result['count'] > 0);
        } catch (Exception $e) {
            error_log('Erro ao verificar proprietário do modelo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Conta modelos enviados pelo usuário em um período de tempo
     *
     * @param int $userId ID do usuário
     * @param int $minutes Período em minutos (padrão: 60 minutos = 1 hora)
     * @return int Número de modelos enviados no período
     */
    public function countRecentUploads($userId, $minutes = 60) {
        $sql = "SELECT COUNT(*) as count FROM customer_models 
                WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        
        try {
            $result = $this->db->fetchSingle($sql, [
                ':user_id' => $userId,
                ':minutes' => $minutes
            ]);
            
            return ($result) ? $result['count'] : 0;
        } catch (Exception $e) {
            error_log('Erro ao contar uploads recentes: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Calcula o espaço total usado por modelos de um usuário
     *
     * @param int $userId ID do usuário
     * @return int Total de bytes utilizados
     */
    public function calculateUserStorageUsage($userId) {
        $sql = "SELECT SUM(file_size) as total_size FROM customer_models 
                WHERE user_id = :user_id";
        
        try {
            $result = $this->db->fetchSingle($sql, [':user_id' => $userId]);
            
            return ($result && $result['total_size']) ? $result['total_size'] : 0;
        } catch (Exception $e) {
            error_log('Erro ao calcular uso de armazenamento: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém estatísticas de modelos 3D
     *
     * @return array Estatísticas dos modelos
     */
    public function getModelStatistics() {
        try {
            // Total de modelos
            $sql = "SELECT COUNT(*) as total FROM customer_models";
            $totalResult = $this->db->fetchSingle($sql);
            $total = $totalResult ? $totalResult['total'] : 0;
            
            // Total por status
            $sql = "SELECT status, COUNT(*) as count 
                    FROM customer_models 
                    GROUP BY status";
            $statusCounts = $this->db->fetchAll($sql);
            
            // Formatar contagens por status
            $statusStats = [
                'pending_validation' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
            
            if ($statusCounts) {
                foreach ($statusCounts as $item) {
                    $statusStats[$item['status']] = intval($item['count']);
                }
            }
            
            // Total por tipo de arquivo
            $sql = "SELECT file_type, COUNT(*) as count 
                    FROM customer_models 
                    GROUP BY file_type";
            $typeCounts = $this->db->fetchAll($sql);
            
            // Formatar contagens por tipo
            $typeStats = [];
            if ($typeCounts) {
                foreach ($typeCounts as $item) {
                    $typeStats[$item['file_type']] = intval($item['count']);
                }
            }
            
            // Uso total de armazenamento
            $sql = "SELECT SUM(file_size) as total_size FROM customer_models";
            $sizeResult = $this->db->fetchSingle($sql);
            $totalSize = $sizeResult ? $sizeResult['total_size'] : 0;
            
            // Retornar estatísticas
            return [
                'total' => $total,
                'by_status' => $statusStats,
                'by_type' => $typeStats,
                'total_storage' => $totalSize,
                'total_storage_formatted' => $this->formatFileSize($totalSize)
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de modelos 3D: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [
                    'pending_validation' => 0,
                    'approved' => 0,
                    'rejected' => 0
                ],
                'by_type' => [],
                'total_storage' => 0,
                'total_storage_formatted' => '0 B'
            ];
        }
    }
    
    /**
     * Formata tamanho do arquivo para exibição
     * 
     * @param int $bytes Tamanho em bytes
     * @param int $precision Precisão de casas decimais
     * @return string Tamanho formatado
     */
    private function formatFileSize($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Registra um evento de auditoria para modelo 3D
     *
     * @param int $modelId ID do modelo
     * @param int $userId ID do usuário que realizou a ação
     * @param string $action Ação realizada (upload, review, delete, etc.)
     * @param array $data Dados adicionais sobre a ação
     * @return bool True se registrado com sucesso, false caso contrário
     */
    public function logModelEvent($modelId, $userId, $action, $data = []) {
        $sql = "INSERT INTO audit_log (model_id, user_id, action, action_data, created_at) 
                VALUES (:model_id, :user_id, :action, :action_data, NOW())";
        
        $params = [
            ':model_id' => $modelId,
            ':user_id' => $userId,
            ':action' => $action,
            ':action_data' => json_encode($data)
        ];
        
        try {
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao registrar evento de auditoria: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém eventos de auditoria de um modelo
     *
     * @param int $modelId ID do modelo
     * @return array Lista de eventos
     */
    public function getModelEvents($modelId) {
        $sql = "SELECT al.*, u.name as user_name 
                FROM audit_log al
                JOIN users u ON al.user_id = u.id
                WHERE al.model_id = :model_id
                ORDER BY al.created_at DESC";
        
        try {
            $events = $this->db->fetchAll($sql, [':model_id' => $modelId]);
            
            // Deserializar os dados da ação para cada evento
            foreach ($events as &$event) {
                if (isset($event['action_data']) && !empty($event['action_data'])) {
                    $event['action_data'] = json_decode($event['action_data'], true);
                }
            }
            
            return $events;
        } catch (Exception $e) {
            error_log('Erro ao obter eventos de auditoria: ' . $e->getMessage());
            return [];
        }
    }
}