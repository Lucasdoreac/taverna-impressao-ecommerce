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
     * @return int|bool ID do modelo inserido ou false em caso de erro
     */
    public function saveModel($userId, $fileName, $originalName, $fileSize, $fileType, $notes = '') {
        $sql = "INSERT INTO customer_models (user_id, file_name, original_name, file_size, file_type, notes) 
                VALUES (:user_id, :file_name, :original_name, :file_size, :file_type, :notes)";
        
        $params = [
            ':user_id' => $userId,
            ':file_name' => $fileName,
            ':original_name' => $originalName,
            ':file_size' => $fileSize,
            ':file_type' => $fileType,
            ':notes' => $notes
        ];
        
        try {
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            app_log('Erro ao salvar modelo 3D: ' . $e->getMessage());
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
                SET status = :status";
        
        $params = [
            ':status' => $status,
            ':id' => $modelId
        ];
        
        if ($notes !== null) {
            $sql .= ", notes = :notes";
            $params[':notes'] = $notes;
        }
        
        $sql .= " WHERE id = :id";
        
        try {
            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            app_log('Erro ao atualizar status do modelo 3D: ' . $e->getMessage());
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
            return $this->db->fetchSingle($sql, [':id' => $modelId]);
        } catch (Exception $e) {
            app_log('Erro ao obter modelo 3D: ' . $e->getMessage());
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
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            app_log('Erro ao listar modelos 3D do usuário: ' . $e->getMessage());
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
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            app_log('Erro ao listar modelos 3D pendentes: ' . $e->getMessage());
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
                $filePath = UPLOAD_PATH . '/3d_models/' . $model['file_name'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            return $result;
        } catch (Exception $e) {
            app_log('Erro ao excluir modelo 3D: ' . $e->getMessage());
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
            app_log('Erro ao verificar proprietário do modelo: ' . $e->getMessage());
            return false;
        }