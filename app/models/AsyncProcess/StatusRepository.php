<?php
/**
 * StatusRepository - Repositório para gerenciamento de status de processos assíncronos
 * 
 * @package App\Models\AsyncProcess
 * @category Security
 * @author Taverna da Impressão 3D Dev Team
 */

namespace App\Models\AsyncProcess;

class StatusRepository
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * Constructor
     * 
     * @param \PDO $db Conexão com o banco de dados
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Obtém o status de um processo assíncrono
     * 
     * @param string $processToken Token único do processo
     * @return array|null Status do processo ou null se não encontrado
     */
    public function getProcessStatus(string $processToken)
    {
        $stmt = $this->db->prepare("
            SELECT 
                p.id, 
                p.status, 
                p.progress_percentage, 
                p.created_at, 
                p.updated_at, 
                p.completed_at,
                p.estimated_completion_time,
                p.current_step,
                p.total_steps,
                p.error_message
            FROM 
                async_processes p
            WHERE 
                p.process_token = ? 
            LIMIT 1
        ");
        
        $stmt->execute([$processToken]);
        $process = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$process) {
            return null;
        }
        
        // Adicionar etapas do processo se disponíveis
        if ($process['id']) {
            $stepsStmt = $this->db->prepare("
                SELECT 
                    step_name, 
                    status, 
                    completed_at
                FROM 
                    async_process_steps
                WHERE 
                    process_id = ?
                ORDER BY 
                    step_order ASC
            ");
            
            $stepsStmt->execute([$process['id']]);
            $process['steps'] = $stepsStmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        return $process;
    }

    /**
     * Verifica se um usuário pode acessar informações de um processo
     * 
     * @param string $processToken Token do processo
     * @param int $userId ID do usuário
     * @return bool True se o usuário pode acessar o processo
     */
    public function userCanAccessProcess(string $processToken, int $userId): bool
    {
        if ($userId <= 0) {
            return false; // Usuário não autenticado
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as count
            FROM 
                async_processes p
            WHERE 
                p.process_token = ? 
                AND (p.user_id = ? OR p.is_public = 1)
        ");
        
        $stmt->execute([$processToken, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return ($result && $result['count'] > 0);
    }

    /**
     * Atualiza o status de um processo assíncrono
     * 
     * @param string $processToken Token do processo
     * @param string $status Novo status
     * @param array $additionalData Dados adicionais para atualização
     * @return bool Sucesso da operação
     */
    public function updateProcessStatus(string $processToken, string $status, array $additionalData = []): bool
    {
        // Iniciar construção da query de atualização
        $sql = "UPDATE async_processes SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        // Adicionar campos adicionais se fornecidos
        foreach ($additionalData as $field => $value) {
            // Verificar se é um campo permitido
            if (in_array($field, [
                'progress_percentage', 
                'completed_at', 
                'estimated_completion_time',
                'current_step',
                'total_steps',
                'error_message'
            ])) {
                $sql .= ", {$field} = ?";
                $params[] = $value;
            }
        }
        
        // Completar a query com a condição WHERE
        $sql .= " WHERE process_token = ?";
        $params[] = $processToken;
        
        // Executar a atualização
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Cria um novo registro de processo assíncrono
     * 
     * @param int $userId ID do usuário
     * @param string $processType Tipo do processo
     * @param bool $isPublic Se o processo é público
     * @return string Token do processo criado
     */
    public function createProcess(int $userId, string $processType, bool $isPublic = false): string
    {
        // Gerar token único
        $processToken = bin2hex(random_bytes(16)); // 32 caracteres
        
        $stmt = $this->db->prepare("
            INSERT INTO async_processes (
                process_token,
                user_id,
                process_type,
                status,
                progress_percentage,
                is_public,
                created_at,
                updated_at
            ) VALUES (
                ?, ?, ?, 'pending', 0, ?, NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            $processToken,
            $userId,
            $processType,
            $isPublic ? 1 : 0
        ]);
        
        return $processToken;
    }
}
