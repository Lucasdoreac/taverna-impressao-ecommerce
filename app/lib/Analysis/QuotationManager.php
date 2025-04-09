<?php
/**
 * QuotationManager - Gerenciador de cotações para impressão 3D
 * 
 * Esta classe gerencia o armazenamento, recuperação e manipulação de cotações
 * e configurações do sistema de cotação automatizada.
 * 
 * @package     App\Lib\Analysis
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
require_once __DIR__ . '/ModelComplexityAnalyzer.php';
require_once __DIR__ . '/QuotationCalculator.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';

class QuotationManager {
    use InputValidationTrait;
    
    /**
     * Instância do banco de dados
     * @var PDO
     */
    private $db;
    
    /**
     * Instância do analisador de complexidade
     * @var ModelComplexityAnalyzer
     */
    private $complexityAnalyzer;
    
    /**
     * Instância do calculador de cotações
     * @var QuotationCalculator
     */
    private $quotationCalculator;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->complexityAnalyzer = new ModelComplexityAnalyzer();
        $this->quotationCalculator = new QuotationCalculator($this->loadQuotationParameters());
    }
    
    /**
     * Gera uma cotação para um modelo 3D
     * 
     * @param int $modelId ID do modelo
     * @param string $material Material selecionado
     * @param array $options Opções adicionais
     * @return array Resultado da cotação
     */
    public function generateQuotation($modelId, $material = QuotationCalculator::MATERIAL_PLA, $options = []) {
        // Validar entrada
        $modelId = intval($modelId);
        if ($modelId <= 0) {
            return [
                'error' => 'ID de modelo inválido',
                'success' => false
            ];
        }
        
        // Carregar dados do modelo
        $modelData = $this->getModelData($modelId);
        if (!$modelData) {
            return [
                'error' => 'Modelo não encontrado',
                'success' => false
            ];
        }
        
        // Verificar se o modelo está aprovado
        if ($modelData['status'] !== 'approved') {
            return [
                'error' => 'Apenas modelos aprovados podem ser cotados',
                'success' => false
            ];
        }
        
        // Determinar caminho do arquivo
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/models/approved/' . $modelData['file_name'];
        
        // Analisar complexidade do modelo
        $complexityAnalysis = $this->complexityAnalyzer->analyzeModel($modelData, $filePath);
        
        // Calcular cotação
        $quotation = $this->quotationCalculator->calculateQuotation($complexityAnalysis, $material, $options);
        
        // Adicionar informações do modelo
        $quotation['model_id'] = $modelId;
        $quotation['model_name'] = $modelData['original_name'];
        $quotation['complexity_analysis'] = $complexityAnalysis;
        $quotation['success'] = true;
        
        // Salvar cotação no banco de dados se solicitado
        if (isset($options['save']) && $options['save']) {
            $quotationId = $this->saveQuotation($modelId, $quotation);
            $quotation['quotation_id'] = $quotationId;
        }
        
        return $quotation;
    }
    
    /**
     * Salva uma cotação no banco de dados
     * 
     * @param int $modelId ID do modelo
     * @param array $quotation Dados da cotação
     * @return int|bool ID da cotação ou false em caso de erro
     */
    public function saveQuotation($modelId, $quotation) {
        // Preparar dados para inserção
        $modelId = intval($modelId);
        $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        $material = $quotation['material'] ?? QuotationCalculator::MATERIAL_PLA;
        $totalCost = $quotation['total_cost'] ?? 0;
        $materialAmount = $quotation['material_amount'] ?? 0;
        $printTime = $quotation['print_time_minutes'] ?? 0;
        $complexityLevel = $quotation['complexity_level'] ?? ModelComplexityAnalyzer::COMPLEXITY_SIMPLE;
        $isUrgent = isset($quotation['is_urgent']) && $quotation['is_urgent'] ? 1 : 0;
        $deliveryDays = $quotation['estimated_delivery_days'] ?? 1;
        $quotationData = json_encode($quotation);
        
        // Preparar consulta SQL com prepared statement
        $sql = "INSERT INTO model_quotations (
                    model_id, 
                    user_id, 
                    material_type, 
                    total_cost, 
                    material_amount, 
                    print_time_minutes,
                    complexity_level,
                    is_urgent,
                    estimated_delivery_days,
                    quotation_data,
                    created_at
                ) VALUES (
                    :model_id,
                    :user_id,
                    :material_type,
                    :total_cost,
                    :material_amount,
                    :print_time_minutes,
                    :complexity_level,
                    :is_urgent,
                    :estimated_delivery_days,
                    :quotation_data,
                    NOW()
                )";
                
        $params = [
            ':model_id' => $modelId,
            ':user_id' => $userId,
            ':material_type' => $material,
            ':total_cost' => $totalCost,
            ':material_amount' => $materialAmount,
            ':print_time_minutes' => $printTime,
            ':complexity_level' => $complexityLevel,
            ':is_urgent' => $isUrgent,
            ':estimated_delivery_days' => $deliveryDays,
            ':quotation_data' => $quotationData
        ];
        
        try {
            $this->db->execute($sql, $params);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erro ao salvar cotação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma cotação pelo ID
     * 
     * @param int $quotationId ID da cotação
     * @return array|false Dados da cotação ou false se não encontrada
     */
    public function getQuotation($quotationId) {
        $quotationId = intval($quotationId);
        
        if ($quotationId <= 0) {
            return false;
        }
        
        $sql = "SELECT * FROM model_quotations WHERE id = :id";
        
        try {
            $quotation = $this->db->fetchSingle($sql, [':id' => $quotationId]);
            
            if ($quotation && isset($quotation['quotation_data'])) {
                $quotationData = json_decode($quotation['quotation_data'], true);
                if (is_array($quotationData)) {
                    $quotation = array_merge($quotation, $quotationData);
                }
            }
            
            return $quotation;
        } catch (Exception $e) {
            error_log('Erro ao buscar cotação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lista cotações com filtros
     * 
     * @param array $filters Filtros de busca
     * @param int $limit Limite de resultados
     * @param int $offset Deslocamento para paginação
     * @return array Lista de cotações
     */
    public function listQuotations($filters = [], $limit = 50, $offset = 0) {
        // Construir consulta base
        $sql = "SELECT q.*, m.original_name as model_name, u.name as user_name
                FROM model_quotations q
                LEFT JOIN customer_models m ON q.model_id = m.id
                LEFT JOIN users u ON q.user_id = u.id";
        
        // Inicializar arrays para condições WHERE e parâmetros
        $conditions = [];
        $params = [];
        
        // Aplicar filtros
        if (isset($filters['user_id']) && intval($filters['user_id']) > 0) {
            $userId = intval($filters['user_id']);
            $conditions[] = "q.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        if (isset($filters['model_id']) && intval($filters['model_id']) > 0) {
            $modelId = intval($filters['model_id']);
            $conditions[] = "q.model_id = :model_id";
            $params[':model_id'] = $modelId;
        }
        
        if (isset($filters['material']) && in_array($filters['material'], array_keys($this->quotationCalculator->getAvailableMaterials()))) {
            $material = $filters['material'];
            $conditions[] = "q.material_type = :material";
            $params[':material'] = $material;
        }
        
        if (isset($filters['min_cost']) && is_numeric($filters['min_cost'])) {
            $minCost = floatval($filters['min_cost']);
            $conditions[] = "q.total_cost >= :min_cost";
            $params[':min_cost'] = $minCost;
        }
        
        if (isset($filters['max_cost']) && is_numeric($filters['max_cost'])) {
            $maxCost = floatval($filters['max_cost']);
            $conditions[] = "q.total_cost <= :max_cost";
            $params[':max_cost'] = $maxCost;
        }
        
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'];
            $conditions[] = "q.created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $dateTo = $filters['date_to'];
            $conditions[] = "q.created_at <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        // Adicionar condições WHERE se houver
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        // Adicionar ordenação
        $sql .= " ORDER BY q.created_at DESC";
        
        // Adicionar limite e deslocamento
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = intval($limit);
        $params[':offset'] = intval($offset);
        
        try {
            $quotations = $this->db->fetchAll($sql, $params);
            
            // Processar dados de cotação
            foreach ($quotations as &$quotation) {
                if (isset($quotation['quotation_data'])) {
                    $quotationData = json_decode($quotation['quotation_data'], true);
                    if (is_array($quotationData)) {
                        // Mesclar apenas os campos principais para evitar duplicação
                        foreach (['complexity_level', 'breakdown', 'print_considerations'] as $field) {
                            if (isset($quotationData[$field])) {
                                $quotation[$field] = $quotationData[$field];
                            }
                        }
                    }
                }
            }
            
            return $quotations;
        } catch (Exception $e) {
            error_log('Erro ao listar cotações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém os parâmetros de cotação do banco de dados
     * 
     * @return array Parâmetros de cotação
     */
    public function loadQuotationParameters() {
        try {
            $sql = "SELECT * FROM quotation_parameters WHERE id = 1";
            $result = $this->db->fetchSingle($sql);
            
            if ($result && isset($result['parameters'])) {
                $parameters = json_decode($result['parameters'], true);
                if (is_array($parameters)) {
                    return $parameters;
                }
            }
            
            // Retornar parâmetros padrão se não encontrados
            return [];
            
        } catch (Exception $e) {
            error_log('Erro ao carregar parâmetros de cotação: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Salva os parâmetros de cotação no banco de dados
     * 
     * @param array $parameters Novos parâmetros
     * @return bool True se salvo com sucesso
     */
    public function saveQuotationParameters($parameters) {
        // Validar parâmetros de entrada
        if (!is_array($parameters)) {
            return false;
        }
        
        // Atualizar calculador primeiro
        $updated = $this->quotationCalculator->updateParameters($parameters);
        
        if (!$updated) {
            return false;
        }
        
        // Obter parâmetros atualizados
        $parameters = $this->quotationCalculator->getParameters();
        $parametersJson = json_encode($parameters);
        
        try {
            // Verificar se já existe um registro
            $sql = "SELECT COUNT(*) as count FROM quotation_parameters WHERE id = 1";
            $result = $this->db->fetchSingle($sql);
            
            if ($result && $result['count'] > 0) {
                // Atualizar registro existente
                $sql = "UPDATE quotation_parameters SET 
                        parameters = :parameters,
                        updated_at = NOW()
                        WHERE id = 1";
            } else {
                // Inserir novo registro
                $sql = "INSERT INTO quotation_parameters (id, parameters, created_at, updated_at)
                        VALUES (1, :parameters, NOW(), NOW())";
            }
            
            $this->db->execute($sql, [':parameters' => $parametersJson]);
            return true;
            
        } catch (Exception $e) {
            error_log('Erro ao salvar parâmetros de cotação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém dados do modelo pelo ID
     * 
     * @param int $modelId ID do modelo
     * @return array|false Dados do modelo ou false se não encontrado
     */
    private function getModelData($modelId) {
        $sql = "SELECT * FROM customer_models WHERE id = :id";
        
        try {
            $model = $this->db->fetchSingle($sql, [':id' => $modelId]);
            
            if ($model && isset($model['metadata']) && !empty($model['metadata'])) {
                // Deserializar metadados
                $model['metadata'] = json_decode($model['metadata'], true);
            }
            
            return $model;
        } catch (Exception $e) {
            error_log('Erro ao carregar dados do modelo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas das cotações geradas
     * 
     * @param array $filters Filtros opcionais
     * @return array Estatísticas de cotações
     */
    public function getQuotationStatistics($filters = []) {
        // Inicializar arrays para condições WHERE e parâmetros
        $conditions = [];
        $params = [];
        
        // Aplicar filtros
        if (isset($filters['user_id']) && intval($filters['user_id']) > 0) {
            $userId = intval($filters['user_id']);
            $conditions[] = "user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $dateFrom = $filters['date_from'];
            $conditions[] = "created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $dateTo = $filters['date_to'];
            $conditions[] = "created_at <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        // Construir consulta base
        $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
        
        try {
            // Total de cotações
            $sql = "SELECT COUNT(*) as total FROM model_quotations" . $whereClause;
            $totalResult = $this->db->fetchSingle($sql, $params);
            $total = $totalResult ? $totalResult['total'] : 0;
            
            // Valor médio de cotação
            $sql = "SELECT AVG(total_cost) as avg_cost FROM model_quotations" . $whereClause;
            $avgResult = $this->db->fetchSingle($sql, $params);
            $avgCost = $avgResult ? $avgResult['avg_cost'] : 0;
            
            // Distribuição por material
            $sql = "SELECT material_type, COUNT(*) as count 
                    FROM model_quotations" . $whereClause . 
                   " GROUP BY material_type";
            $materialDistribution = $this->db->fetchAll($sql, $params);
            
            // Distribuição por nível de complexidade
            $sql = "SELECT complexity_level, COUNT(*) as count 
                    FROM model_quotations" . $whereClause . 
                   " GROUP BY complexity_level";
            $complexityDistribution = $this->db->fetchAll($sql, $params);
            
            // Retornar estatísticas
            return [
                'total_quotations' => $total,
                'average_cost' => $avgCost,
                'material_distribution' => $materialDistribution,
                'complexity_distribution' => $complexityDistribution
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao calcular estatísticas de cotações: ' . $e->getMessage());
            return [
                'total_quotations' => 0,
                'average_cost' => 0,
                'material_distribution' => [],
                'complexity_distribution' => []
            ];
        }
    }
}