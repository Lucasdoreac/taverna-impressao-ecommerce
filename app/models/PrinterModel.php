<?php
/**
 * PrinterModel - Gerencia as impressoras disponíveis para o sistema de fila de impressão
 * 
 * @package    Taverna da Impressão 3D
 * @author     Claude
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';

class PrinterModel {
    use InputValidationTrait;
    
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Adiciona uma nova impressora
     *
     * @param string $name Nome da impressora
     * @param string $model Modelo da impressora
     * @param array $capabilities Capacidades da impressora (materiais, tamanho, etc.)
     * @param string $notes Notas sobre a impressora
     * @return int|false ID da impressora ou false se falhar
     */
    public function savePrinter($name, $model, $capabilities = null, $notes = '') {
        try {
            // Validar parâmetros
            $name = $this->validateString($name, ['required' => true, 'maxLength' => 100]);
            $model = $this->validateString($model, ['required' => true, 'maxLength' => 100]);
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Preparar dados para inserção
            $data = [
                'name' => $name,
                'model' => $model,
                'status' => 'available',
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Adicionar capacidades se fornecidas
            if ($capabilities !== null) {
                $data['capabilities'] = json_encode($capabilities);
            }
            
            // Inserir na tabela printers
            $sql = "INSERT INTO printers (name, model, status, capabilities, notes, created_at) 
                    VALUES (:name, :model, :status, :capabilities, :notes, :created_at)";
            
            $params = [
                ':name' => $data['name'],
                ':model' => $data['model'],
                ':status' => $data['status'],
                ':capabilities' => $data['capabilities'] ?? null,
                ':notes' => $data['notes'],
                ':created_at' => $data['created_at']
            ];
            
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erro ao adicionar impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de uma impressora
     *
     * @param int $printerId ID da impressora
     * @param string $status Novo status
     * @return bool True se a operação foi bem-sucedida
     */
    public function updateStatus($printerId, $status) {
        try {
            // Validar parâmetros
            $printerId = (int)$printerId;
            $status = $this->validateString($status, ['allowedValues' => ['available', 'busy', 'maintenance', 'offline']]);
            
            // Atualizar o status
            $sql = "UPDATE printers SET status = :status, updated_at = NOW() WHERE id = :id";
            $params = [
                ':status' => $status,
                ':id' => $printerId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar status da impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o trabalho atual de uma impressora
     *
     * @param int $printerId ID da impressora
     * @param int|null $jobId ID do trabalho atual (null para limpar)
     * @return bool True se a operação foi bem-sucedida
     */
    public function updateCurrentJob($printerId, $jobId) {
        try {
            // Validar parâmetros
            $printerId = (int)$printerId;
            $jobId = $jobId !== null ? (int)$jobId : null;
            
            // Atualizar o trabalho atual
            $sql = "UPDATE printers SET current_job_id = :job_id, updated_at = NOW() WHERE id = :id";
            $params = [
                ':job_id' => $jobId,
                ':id' => $printerId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar trabalho atual da impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma manutenção na impressora
     *
     * @param int $printerId ID da impressora
     * @param string $notes Notas sobre a manutenção
     * @return bool True se a operação foi bem-sucedida
     */
    public function registerMaintenance($printerId, $notes = '') {
        try {
            // Validar parâmetros
            $printerId = (int)$printerId;
            $notes = $this->validateString($notes, ['maxLength' => 1000]);
            
            // Atualizar status e data de manutenção
            $sql = "UPDATE printers SET status = 'maintenance', last_maintenance = NOW(), 
                    notes = CONCAT(notes, '\n\nManutenção em ', NOW(), ': ', :maintenance_notes), 
                    updated_at = NOW() 
                    WHERE id = :id";
            $params = [
                ':maintenance_notes' => $notes,
                ':id' => $printerId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar manutenção da impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma impressora pelo ID
     *
     * @param int $printerId ID da impressora
     * @return array|false Dados da impressora ou false se não encontrada
     */
    public function getPrinterById($printerId) {
        try {
            $printerId = (int)$printerId;
            
            $sql = "SELECT * FROM printers WHERE id = :id";
            $params = [':id' => $printerId];
            $result = $this->db->fetchSingle($sql, $params);
            
            if ($result) {
                // Decodificar capacidades se existirem
                if (isset($result['capabilities']) && !empty($result['capabilities'])) {
                    $result['capabilities'] = json_decode($result['capabilities'], true);
                }
                
                return $result;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erro ao obter impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém impressoras disponíveis
     *
     * @return array Lista de impressoras disponíveis
     */
    public function getAvailablePrinters() {
        try {
            $sql = "SELECT * FROM printers WHERE status = 'available' ORDER BY name ASC";
            $results = $this->db->fetchAll($sql);
            
            // Processar cada impressora
            foreach ($results as &$result) {
                if (isset($result['capabilities']) && !empty($result['capabilities'])) {
                    $result['capabilities'] = json_decode($result['capabilities'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Erro ao obter impressoras disponíveis: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as impressoras
     *
     * @param array $filters Filtros (status, etc.)
     * @return array Lista de todas as impressoras
     */
    public function getAllPrinters($filters = []) {
        try {
            $sql = "SELECT * FROM printers WHERE 1=1";
            $params = [];
            
            // Adicionar filtros
            if (isset($filters['status']) && !empty($filters['status'])) {
                $status = $this->validateString($filters['status'], ['allowedValues' => ['available', 'busy', 'maintenance', 'offline']]);
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }
            
            $sql .= " ORDER BY name ASC";
            
            $results = $this->db->fetchAll($sql, $params);
            
            // Processar cada impressora
            foreach ($results as &$result) {
                if (isset($result['capabilities']) && !empty($result['capabilities'])) {
                    $result['capabilities'] = json_decode($result['capabilities'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log('Erro ao obter todas as impressoras: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas de impressoras
     *
     * @return array Estatísticas
     */
    public function getPrinterStatistics() {
        try {
            $stats = [
                'total' => 0,
                'by_status' => [
                    'available' => 0,
                    'busy' => 0,
                    'maintenance' => 0,
                    'offline' => 0
                ],
                'by_model' => [],
                'total_prints' => 0,
                'avg_prints_per_printer' => 0
            ];
            
            // Contar totais por status
            $sql = "SELECT status, COUNT(*) as count FROM printers GROUP BY status";
            $results = $this->db->fetchAll($sql);
            
            if ($results) {
                foreach ($results as $row) {
                    $stats['by_status'][$row['status']] = (int)$row['count'];
                    $stats['total'] += (int)$row['count'];
                }
            }
            
            // Contar por modelo
            $sql = "SELECT model, COUNT(*) as count FROM printers GROUP BY model";
            $results = $this->db->fetchAll($sql);
            
            if ($results) {
                foreach ($results as $row) {
                    $stats['by_model'][$row['model']] = (int)$row['count'];
                }
            }
            
            // Contar total de impressões (pela tabela print_jobs)
            $sql = "SELECT COUNT(*) as count FROM print_jobs";
            $result = $this->db->fetchSingle($sql);
            if ($result) {
                $stats['total_prints'] = (int)$result['count'];
                if ($stats['total'] > 0) {
                    $stats['avg_prints_per_printer'] = round($stats['total_prints'] / $stats['total'], 2);
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de impressoras: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [
                    'available' => 0,
                    'busy' => 0,
                    'maintenance' => 0,
                    'offline' => 0
                ],
                'by_model' => [],
                'total_prints' => 0,
                'avg_prints_per_printer' => 0
            ];
        }
    }
    
    /**
     * Exclui uma impressora
     *
     * @param int $printerId ID da impressora
     * @return bool True se a operação foi bem-sucedida
     */
    public function deletePrinter($printerId) {
        try {
            $printerId = (int)$printerId;
            
            // Verificar se a impressora existe
            $printer = $this->getPrinterById($printerId);
            if (!$printer) {
                return false;
            }
            
            // Verificar se a impressora não está ocupada
            if ($printer['status'] === 'busy') {
                return false;
            }
            
            // Excluir a impressora
            $sql = "DELETE FROM printers WHERE id = :id";
            $params = [':id' => $printerId];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao excluir impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza informações de uma impressora
     *
     * @param int $printerId ID da impressora
     * @param array $data Dados a serem atualizados
     * @return bool True se a operação foi bem-sucedida
     */
    public function updatePrinter($printerId, $data) {
        try {
            $printerId = (int)$printerId;
            
            // Verificar se a impressora existe
            $printer = $this->getPrinterById($printerId);
            if (!$printer) {
                return false;
            }
            
            // Preparar campos a serem atualizados
            $updateFields = [];
            $params = [':id' => $printerId];
            
            // Nome
            if (isset($data['name'])) {
                $name = $this->validateString($data['name'], ['maxLength' => 100]);
                $updateFields[] = "name = :name";
                $params[':name'] = $name;
            }
            
            // Modelo
            if (isset($data['model'])) {
                $model = $this->validateString($data['model'], ['maxLength' => 100]);
                $updateFields[] = "model = :model";
                $params[':model'] = $model;
            }
            
            // Status
            if (isset($data['status'])) {
                $status = $this->validateString($data['status'], ['allowedValues' => ['available', 'busy', 'maintenance', 'offline']]);
                $updateFields[] = "status = :status";
                $params[':status'] = $status;
            }
            
            // Capacidades
            if (isset($data['capabilities'])) {
                $updateFields[] = "capabilities = :capabilities";
                $params[':capabilities'] = json_encode($data['capabilities']);
            }
            
            // Notas
            if (isset($data['notes'])) {
                $notes = $this->validateString($data['notes'], ['maxLength' => 1000]);
                $updateFields[] = "notes = :notes";
                $params[':notes'] = $notes;
            }
            
            // Se não há campos para atualizar, retornar verdadeiro
            if (empty($updateFields)) {
                return true;
            }
            
            // Adicionar campo de atualização
            $updateFields[] = "updated_at = NOW()";
            
            // Construir SQL
            $sql = "UPDATE printers SET " . implode(", ", $updateFields) . " WHERE id = :id";
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca por impressoras compatíveis com determinadas capacidades
     *
     * @param array $requiredCapabilities Capacidades necessárias
     * @return array Lista de impressoras compatíveis
     */
    public function findCompatiblePrinters($requiredCapabilities) {
        try {
            // Obter todas as impressoras disponíveis
            $availablePrinters = $this->getAvailablePrinters();
            $compatiblePrinters = [];
            
            foreach ($availablePrinters as $printer) {
                $isCompatible = true;
                
                // Verificar se a impressora tem capacidades definidas
                if (!isset($printer['capabilities']) || empty($printer['capabilities'])) {
                    continue;
                }
                
                // Verificar cada capacidade requerida
                foreach ($requiredCapabilities as $capability => $value) {
                    // Se a impressora não tiver esta capacidade, não é compatível
                    if (!isset($printer['capabilities'][$capability])) {
                        $isCompatible = false;
                        break;
                    }
                    
                    // Para capacidades numéricas (como tamanho máximo), verificar se é suficiente
                    if (is_numeric($value) && is_numeric($printer['capabilities'][$capability])) {
                        if ($printer['capabilities'][$capability] < $value) {
                            $isCompatible = false;
                            break;
                        }
                    }
                    // Para capacidades de lista (como materiais suportados), verificar se contém
                    else if (is_array($printer['capabilities'][$capability])) {
                        if (!in_array($value, $printer['capabilities'][$capability])) {
                            $isCompatible = false;
                            break;
                        }
                    }
                    // Para capacidades booleanas, verificar se é verdadeiro
                    else if (is_bool($value) && is_bool($printer['capabilities'][$capability])) {
                        if ($value && !$printer['capabilities'][$capability]) {
                            $isCompatible = false;
                            break;
                        }
                    }
                    // Para outros tipos, verificar igualdade
                    else if ($printer['capabilities'][$capability] != $value) {
                        $isCompatible = false;
                        break;
                    }
                }
                
                if ($isCompatible) {
                    $compatiblePrinters[] = $printer;
                }
            }
            
            return $compatiblePrinters;
        } catch (Exception $e) {
            error_log('Erro ao buscar impressoras compatíveis: ' . $e->getMessage());
            return [];
        }
    }
}
