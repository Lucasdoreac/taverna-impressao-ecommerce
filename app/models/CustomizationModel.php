<?php
/**
 * CustomizationModel - Modelo para gerenciamento de personalizações
 * 
 * Gerencia o acesso a dados relacionados a personalizações de produtos,
 * incluindo opções de personalização, configurações salvas e uploads.
 */
class CustomizationModel extends Model {
    protected $table = 'customization_options';
    protected $fillable = [
        'product_id', 'name', 'description', 'type', 'required', 'options'
    ];
    
    /**
     * Obtém as opções de personalização de um produto
     * 
     * @param int $productId ID do produto
     * @return array Opções de personalização
     */
    public function getOptions($productId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id = :product_id 
                ORDER BY id ASC";
                
        return $this->db()->select($sql, ['product_id' => $productId]);
    }
    
    /**
     * Salva uma configuração de personalização para uso futuro
     * 
     * @param int $userId ID do usuário
     * @param int $productId ID do produto
     * @param string $configName Nome da configuração
     * @param string $customizationData Dados de personalização em JSON
     * @return bool Verdadeiro se salvo com sucesso
     */
    public function saveConfig($userId, $productId, $configName, $customizationData) {
        // Verificar se já existe uma configuração com esse nome
        $existingConfig = $this->db()->select(
            "SELECT id FROM saved_customizations 
             WHERE user_id = :user_id AND product_id = :product_id AND name = :name",
            [
                'user_id' => $userId,
                'product_id' => $productId,
                'name' => $configName
            ]
        );
        
        if (!empty($existingConfig)) {
            // Atualizar configuração existente
            $this->db()->update(
                'saved_customizations',
                [
                    'customization_data' => $customizationData,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $existingConfig[0]['id']]
            );
            
            return true;
        } else {
            // Criar nova configuração
            return $this->db()->insert('saved_customizations', [
                'user_id' => $userId,
                'product_id' => $productId,
                'name' => $configName,
                'customization_data' => $customizationData,
                'created_at' => date('Y-m-d H:i:s')
            ]) > 0;
        }
    }
    
    /**
     * Obtém configurações salvas de um usuário para um produto
     * 
     * @param int $userId ID do usuário
     * @param int $productId ID do produto
     * @return array Configurações salvas
     */
    public function getConfigs($userId, $productId) {
        $sql = "SELECT id, name, created_at, updated_at 
                FROM saved_customizations 
                WHERE user_id = :user_id AND product_id = :product_id 
                ORDER BY updated_at DESC, created_at DESC";
                
        return $this->db()->select($sql, [
            'user_id' => $userId,
            'product_id' => $productId
        ]);
    }
    
    /**
     * Obtém uma configuração salva específica
     * 
     * @param int $userId ID do usuário
     * @param int $configId ID da configuração
     * @return array|null Configuração salva ou null se não encontrada
     */
    public function getConfig($userId, $configId) {
        $sql = "SELECT * FROM saved_customizations 
                WHERE id = :id AND user_id = :user_id";
                
        $result = $this->db()->select($sql, [
            'id' => $configId,
            'user_id' => $userId
        ]);
        
        return $result ? $result[0] : null;
    }
    
    /**
     * Obtém a última configuração salva de um usuário para um produto
     * 
     * @param int $userId ID do usuário
     * @param int $productId ID do produto
     * @return array|null Configuração salva ou null se não encontrada
     */
    public function getSavedConfig($userId, $productId) {
        $sql = "SELECT * FROM saved_customizations 
                WHERE user_id = :user_id AND product_id = :product_id 
                ORDER BY updated_at DESC, created_at DESC 
                LIMIT 1";
                
        $result = $this->db()->select($sql, [
            'user_id' => $userId,
            'product_id' => $productId
        ]);
        
        return $result ? $result[0] : null;
    }
    
    /**
     * Remove uma configuração salva
     * 
     * @param int $userId ID do usuário
     * @param int $configId ID da configuração
     * @return bool Verdadeiro se removido com sucesso
     */
    public function removeConfig($userId, $configId) {
        return $this->db()->delete(
            'saved_customizations',
            'id = :id AND user_id = :user_id',
            [
                'id' => $configId,
                'user_id' => $userId
            ]
        );
    }
    
    /**
     * Adiciona opção de personalização a um produto
     * 
     * @param array $data Dados da opção
     * @return int ID da opção criada
     */
    public function addOption($data) {
        // Garantir que apenas campos permitidos sejam inseridos
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        // Converter array de opções para JSON, se aplicável
        if (isset($filteredData['options']) && is_array($filteredData['options'])) {
            $filteredData['options'] = json_encode($filteredData['options']);
        }
        
        return $this->db()->insert($this->table, $filteredData);
    }
    
    /**
     * Atualiza opção de personalização
     * 
     * @param int $optionId ID da opção
     * @param array $data Novos dados da opção
     * @return bool Verdadeiro se atualizado com sucesso
     */
    public function updateOption($optionId, $data) {
        // Garantir que apenas campos permitidos sejam atualizados
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        
        // Converter array de opções para JSON, se aplicável
        if (isset($filteredData['options']) && is_array($filteredData['options'])) {
            $filteredData['options'] = json_encode($filteredData['options']);
        }
        
        return $this->db()->update(
            $this->table,
            $filteredData,
            'id = :id',
            ['id' => $optionId]
        );
    }
    
    /**
     * Remove opção de personalização
     * 
     * @param int $optionId ID da opção
     * @return bool Verdadeiro se removido com sucesso
     */
    public function removeOption($optionId) {
        return $this->db()->delete(
            $this->table,
            'id = :id',
            ['id' => $optionId]
        );
    }
    
    /**
     * Obtém dados de personalização de um item do carrinho
     * 
     * @param int $cartItemId ID do item do carrinho
     * @return array Dados de personalização estruturados
     */
    public function getCartItemCustomizationData($cartItemId) {
        $sql = "SELECT customization_data FROM cart_items WHERE id = :id";
        $result = $this->db()->select($sql, ['id' => $cartItemId]);
        
        if (!$result || empty($result[0]['customization_data'])) {
            return [];
        }
        
        $customizationData = json_decode($result[0]['customization_data'], true);
        
        if (!is_array($customizationData)) {
            return [];
        }
        
        // Estruturar dados com informações da opção
        $structuredData = [];
        
        foreach ($customizationData as $optionId => $value) {
            // Buscar informações da opção
            $option = $this->find($optionId);
            
            if ($option) {
                $structuredData[] = [
                    'option' => $option,
                    'value' => $value
                ];
            }
        }
        
        return $structuredData;
    }
    
    /**
     * Obtém dados de personalização de um item do pedido
     * 
     * @param int $orderItemId ID do item do pedido
     * @return array Dados de personalização estruturados
     */
    public function getOrderItemCustomizationData($orderItemId) {
        $sql = "SELECT customization_data FROM order_items WHERE id = :id";
        $result = $this->db()->select($sql, ['id' => $orderItemId]);
        
        if (!$result || empty($result[0]['customization_data'])) {
            return [];
        }
        
        $customizationData = json_decode($result[0]['customization_data'], true);
        
        if (!is_array($customizationData)) {
            return [];
        }
        
        // Estruturar dados
        $structuredData = [];
        
        foreach ($customizationData as $optionId => $value) {
            // Buscar informações da opção
            $option = $this->find($optionId);
            
            if ($option) {
                $structuredData[] = [
                    'option' => $option,
                    'value' => $value
                ];
            }
        }
        
        return $structuredData;
    }
    
    /**
     * Obtém uma string amigável com resumo dos dados de personalização
     * 
     * @param array|string $customizationData Dados de personalização (array ou JSON)
     * @return string Resumo da personalização
     */
    public function getCustomizationSummary($customizationData) {
        if (is_string($customizationData)) {
            $customizationData = json_decode($customizationData, true);
        }
        
        if (!is_array($customizationData) || empty($customizationData)) {
            return 'Não personalizado';
        }
        
        $summary = [];
        
        foreach ($customizationData as $optionId => $value) {
            // Buscar informações da opção
            $option = $this->find($optionId);
            
            if ($option) {
                $optionName = $option['name'];
                
                // Formatar valor com base no tipo de opção
                $formattedValue = $value;
                
                if ($option['type'] === 'select' && !empty($option['options'])) {
                    $options = json_decode($option['options'], true);
                    $formattedValue = $options[$value] ?? $value;
                } elseif ($option['type'] === 'upload' && !empty($value)) {
                    $formattedValue = 'Arquivo enviado';
                }
                
                $summary[] = "{$optionName}: {$formattedValue}";
            }
        }
        
        return !empty($summary) ? implode(', ', $summary) : 'Personalização básica';
    }
}