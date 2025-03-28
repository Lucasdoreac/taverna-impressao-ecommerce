<?php
/**
 * CustomizationModel - Modelo para opções de personalização
 */
class CustomizationModel extends Model {
    protected $table = 'customization_options';
    protected $primaryKey = 'id';
    protected $fillable = [
        'product_id', 'name', 'description', 'type', 'required', 'options'
    ];
    
    /**
     * Obtém todas as opções de personalização com paginação
     * @param int $page Número da página
     * @param int $limit Limite de itens por página
     * @return array Opções de personalização e metadados de paginação
     */
    public function getAll($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}";
        $countResult = $this->db()->select($countSql);
        $total = $countResult[0]['total'];
        
        // Buscar opções com informações do produto associado
        $sql = "SELECT co.*, p.name as product_name, p.slug as product_slug
                FROM {$this->table} co
                JOIN products p ON co.product_id = p.id
                ORDER BY co.id DESC
                LIMIT {$offset}, {$limit}";
        
        $items = $this->db()->select($sql);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit),
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total)
        ];
    }
    
    /**
     * Obtém opções de personalização de um produto específico com paginação
     * @param int $productId ID do produto
     * @param int $page Número da página
     * @param int $limit Limite de itens por página
     * @return array Opções de personalização e metadados de paginação
     */
    public function getByProduct($productId, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE product_id = :product_id";
        $countResult = $this->db()->select($countSql, ['product_id' => $productId]);
        $total = $countResult[0]['total'];
        
        // Buscar opções com informações do produto associado
        $sql = "SELECT co.*, p.name as product_name, p.slug as product_slug
                FROM {$this->table} co
                JOIN products p ON co.product_id = p.id
                WHERE co.product_id = :product_id
                ORDER BY co.id DESC
                LIMIT {$offset}, {$limit}";
        
        $items = $this->db()->select($sql, ['product_id' => $productId]);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit),
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total),
            'productId' => $productId
        ];
    }
    
    /**
     * Obtém detalhes completos de uma opção de personalização
     * @param int $id ID da opção
     * @return array|null Detalhes da opção ou null se não encontrada
     */
    public function getDetails($id) {
        $sql = "SELECT co.*, p.name as product_name, p.slug as product_slug
                FROM {$this->table} co
                JOIN products p ON co.product_id = p.id
                WHERE co.id = :id";
        
        $result = $this->db()->select($sql, ['id' => $id]);
        
        if (!$result) {
            return null;
        }
        
        $option = $result[0];
        
        // Formatar opções para exibição se for tipo select
        if ($option['type'] === 'select' && !empty($option['options'])) {
            $option['parsed_options'] = json_decode($option['options'], true);
        }
        
        // Buscar estatísticas de uso
        $option['usage_stats'] = $this->getUsageStatistics($id);
        
        return $option;
    }
    
    /**
     * Obtém estatísticas de uso de uma opção de personalização
     * @param int $id ID da opção
     * @return array Estatísticas de uso
     */
    public function getUsageStatistics($id) {
        // Contar número de pedidos que usam esta opção
        $sql = "SELECT COUNT(DISTINCT oi.order_id) as order_count
                FROM order_items oi
                WHERE oi.customization_data LIKE :pattern";
        
        $pattern = '%"option_' . $id . '"%';
        $result = $this->db()->select($sql, ['pattern' => $pattern]);
        
        $orderCount = $result[0]['order_count'] ?? 0;
        
        // Contar número de itens de carrinho que usam esta opção
        $sql = "SELECT COUNT(*) as cart_count
                FROM cart_items
                WHERE customization_data LIKE :pattern";
        
        $result = $this->db()->select($sql, ['pattern' => $pattern]);
        
        $cartCount = $result[0]['cart_count'] ?? 0;
        
        return [
            'order_count' => $orderCount,
            'cart_count' => $cartCount,
            'total_usage' => $orderCount + $cartCount
        ];
    }
    
    /**
     * Obtém todas as opções de personalização de um produto
     * @param int $productId ID do produto
     * @return array Opções de personalização
     */
    public function getOptionsByProduct($productId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id = :product_id
                ORDER BY id ASC";
        
        return $this->db()->select($sql, ['product_id' => $productId]);
    }
    
    /**
     * Verifica se há conflitos com pedidos ou carrinhos existentes antes de excluir
     * @param int $id ID da opção
     * @return bool True se houver conflitos, false caso contrário
     */
    public function hasConflicts($id) {
        $stats = $this->getUsageStatistics($id);
        return $stats['total_usage'] > 0;
    }
    
    /**
     * Obtém opções de personalização para vários produtos
     * @param array $productIds IDs dos produtos
     * @return array Opções agrupadas por ID do produto
     */
    public function getOptionsForProducts($productIds) {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id IN ({$placeholders})
                ORDER BY product_id, id";
        
        $options = $this->db()->select($sql, $productIds);
        
        // Agrupar opções por produto
        $groupedOptions = [];
        foreach ($options as $option) {
            $productId = $option['product_id'];
            if (!isset($groupedOptions[$productId])) {
                $groupedOptions[$productId] = [];
            }
            $groupedOptions[$productId][] = $option;
        }
        
        return $groupedOptions;
    }
    
    /**
     * Valida dados de personalização enviados pelo usuário
     * @param int $productId ID do produto
     * @param array $customizationData Dados de personalização
     * @return array Resultado da validação (success, errors)
     */
    public function validateCustomizationData($productId, $customizationData) {
        $options = $this->getOptionsByProduct($productId);
        $errors = [];
        
        foreach ($options as $option) {
            $optionId = $option['id'];
            $optionName = $option['name'];
            $isRequired = (bool)$option['required'];
            
            // Verificar opções obrigatórias
            if ($isRequired) {
                // Para upload, verificar se há um arquivo enviado
                if ($option['type'] === 'upload') {
                    $fileKey = "customization_file_{$optionId}";
                    if (!isset($customizationData[$fileKey]) || empty($customizationData[$fileKey])) {
                        $errors[] = "O campo '{$optionName}' é obrigatório.";
                    }
                }
                // Para outros tipos, verificar se há valor
                else {
                    $valueKey = "customization_{$optionId}";
                    if (!isset($customizationData[$valueKey]) || $customizationData[$valueKey] === '') {
                        $errors[] = "O campo '{$optionName}' é obrigatório.";
                    }
                }
            }
            
            // Validar tipo select
            if ($option['type'] === 'select' && isset($customizationData["customization_{$optionId}"]) && $customizationData["customization_{$optionId}"] !== '') {
                $selectedValue = $customizationData["customization_{$optionId}"];
                $allowedValues = [];
                
                if (!empty($option['options'])) {
                    $optionsArray = json_decode($option['options'], true);
                    if (is_array($optionsArray)) {
                        $allowedValues = array_keys($optionsArray);
                    }
                }
                
                if (!empty($allowedValues) && !in_array($selectedValue, $allowedValues)) {
                    $errors[] = "Valor inválido para o campo '{$optionName}'.";
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Formata dados de personalização para armazenamento
     * @param int $productId ID do produto
     * @param array $rawData Dados brutos do formulário
     * @return string JSON formatado para armazenamento
     */
    public function formatCustomizationDataForStorage($productId, $rawData) {
        $options = $this->getOptionsByProduct($productId);
        $formattedData = [];
        
        foreach ($options as $option) {
            $optionId = $option['id'];
            
            // Para upload, armazenar nome do arquivo
            if ($option['type'] === 'upload') {
                $fileKey = "customization_file_{$optionId}";
                if (isset($rawData[$fileKey]) && !empty($rawData[$fileKey])) {
                    $formattedData["option_{$optionId}"] = [
                        'type' => 'upload',
                        'name' => $option['name'],
                        'value' => $rawData[$fileKey],
                        'required' => (bool)$option['required']
                    ];
                }
            }
            // Para outros tipos, armazenar valor
            else {
                $valueKey = "customization_{$optionId}";
                if (isset($rawData[$valueKey])) {
                    $value = Security::sanitizeInput($rawData[$valueKey]);
                    
                    // Para select, armazenar chave e texto
                    if ($option['type'] === 'select' && !empty($option['options']) && $value !== '') {
                        $optionsArray = json_decode($option['options'], true);
                        $displayValue = isset($optionsArray[$value]) ? $optionsArray[$value] : $value;
                        
                        $formattedData["option_{$optionId}"] = [
                            'type' => 'select',
                            'name' => $option['name'],
                            'value' => $value,
                            'display_value' => $displayValue,
                            'required' => (bool)$option['required']
                        ];
                    }
                    // Para text, armazenar como string
                    else if ($option['type'] === 'text') {
                        $formattedData["option_{$optionId}"] = [
                            'type' => 'text',
                            'name' => $option['name'],
                            'value' => $value,
                            'required' => (bool)$option['required']
                        ];
                    }
                }
            }
        }
        
        return json_encode($formattedData);
    }
    
    /**
     * Obtém todas as personalizações ativas agrupadas por categoria
     * @return array Personalizações agrupadas
     */
    public function getCustomizationsByCategory() {
        $sql = "SELECT co.*, p.name as product_name, p.slug as product_slug, 
                       c.name as category_name, c.slug as category_slug
                FROM {$this->table} co
                JOIN products p ON co.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                ORDER BY c.name, p.name, co.name";
        
        $options = $this->db()->select($sql);
        
        // Agrupar por categoria
        $groupedOptions = [];
        foreach ($options as $option) {
            $categoryName = $option['category_name'];
            if (!isset($groupedOptions[$categoryName])) {
                $groupedOptions[$categoryName] = [
                    'slug' => $option['category_slug'],
                    'products' => []
                ];
            }
            
            $productName = $option['product_name'];
            if (!isset($groupedOptions[$categoryName]['products'][$productName])) {
                $groupedOptions[$categoryName]['products'][$productName] = [
                    'id' => $option['product_id'],
                    'slug' => $option['product_slug'],
                    'options' => []
                ];
            }
            
            $groupedOptions[$categoryName]['products'][$productName]['options'][] = $option;
        }
        
        return $groupedOptions;
    }
}