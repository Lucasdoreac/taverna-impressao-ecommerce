<?php
/**
 * SQLOptimizationHelper - Helper para aplicação de otimizações SQL identificadas pelo QueryOptimizerHelper
 * 
 * Este helper fornece funções para aplicar as otimizações recomendadas pelo QueryOptimizerHelper
 * incluindo a criação de índices, verificação de melhorias de performance e aplicação de otimizações.
 */
class SQLOptimizationHelper {
    // Conexão com o banco de dados
    private $db;
    
    // QueryOptimizerHelper para análise
    private $queryOptimizer;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->queryOptimizer = new QueryOptimizerHelper();
    }
    
    /**
     * Aplica os índices recomendados
     * 
     * @return array Resultado da aplicação de índices
     */
    public function applyRecommendedIndices() {
        $results = [
            'products' => $this->applyProductIndices(),
            'product_images' => $this->applyProductImagesIndices(),
            'categories' => $this->applyCategoryIndices(),
            'orders' => $this->applyOrderIndices(),
            'order_items' => $this->applyOrderItemsIndices(),
        ];
        
        return $results;
    }
    
    /**
     * Aplica índices para a tabela products
     * 
     * @return array Resultado da aplicação
     */
    private function applyProductIndices() {
        $result = ['success' => true, 'applied' => [], 'errors' => []];
        
        $indices = [
            'idx_products_is_featured' => 'ALTER TABLE products ADD INDEX idx_products_is_featured (is_featured)',
            'idx_products_is_tested' => 'ALTER TABLE products ADD INDEX idx_products_is_tested (is_tested)',
            'idx_products_is_active' => 'ALTER TABLE products ADD INDEX idx_products_is_active (is_active)',
            'idx_products_created_at' => 'ALTER TABLE products ADD INDEX idx_products_created_at (created_at)',
            'idx_products_category_id' => 'ALTER TABLE products ADD INDEX idx_products_category_id (category_id)',
            'idx_products_slug' => 'ALTER TABLE products ADD INDEX idx_products_slug (slug)',
            'idx_products_is_customizable' => 'ALTER TABLE products ADD INDEX idx_products_is_customizable (is_customizable)',
            'idx_products_stock' => 'ALTER TABLE products ADD INDEX idx_products_stock (stock)',
            'idx_products_availability' => 'ALTER TABLE products ADD INDEX idx_products_availability (is_tested, stock, is_active)',
            'ft_products_search' => 'ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, description)'
        ];
        
        foreach ($indices as $indexName => $sql) {
            try {
                // Verificar se o índice já existe
                $checkSql = "SHOW INDEX FROM products WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $indexName]);
                
                if (empty($indexExists)) {
                    // Aplicar índice
                    $this->db->execute($sql);
                    $result['applied'][] = $indexName;
                } else {
                    // Índice já existe
                    $result['applied'][] = $indexName . ' (já existente)';
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'index' => $indexName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Aplica índices para a tabela product_images
     * 
     * @return array Resultado da aplicação
     */
    private function applyProductImagesIndices() {
        $result = ['success' => true, 'applied' => [], 'errors' => []];
        
        $indices = [
            'idx_product_images_product_id' => 'ALTER TABLE product_images ADD INDEX idx_product_images_product_id (product_id)',
            'idx_product_images_is_main' => 'ALTER TABLE product_images ADD INDEX idx_product_images_is_main (is_main)',
            'idx_product_images_product_main' => 'ALTER TABLE product_images ADD INDEX idx_product_images_product_main (product_id, is_main)'
        ];
        
        foreach ($indices as $indexName => $sql) {
            try {
                // Verificar se o índice já existe
                $checkSql = "SHOW INDEX FROM product_images WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $indexName]);
                
                if (empty($indexExists)) {
                    // Aplicar índice
                    $this->db->execute($sql);
                    $result['applied'][] = $indexName;
                } else {
                    // Índice já existe
                    $result['applied'][] = $indexName . ' (já existente)';
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'index' => $indexName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Aplica índices para a tabela categories
     * 
     * @return array Resultado da aplicação
     */
    private function applyCategoryIndices() {
        $result = ['success' => true, 'applied' => [], 'errors' => []];
        
        $indices = [
            'idx_categories_parent_id' => 'ALTER TABLE categories ADD INDEX idx_categories_parent_id (parent_id)',
            'idx_categories_is_active' => 'ALTER TABLE categories ADD INDEX idx_categories_is_active (is_active)',
            'idx_categories_display_order' => 'ALTER TABLE categories ADD INDEX idx_categories_display_order (display_order)',
            'idx_categories_slug' => 'ALTER TABLE categories ADD INDEX idx_categories_slug (slug)',
            'idx_categories_left_value' => 'ALTER TABLE categories ADD INDEX idx_categories_left_value (left_value)',
            'idx_categories_right_value' => 'ALTER TABLE categories ADD INDEX idx_categories_right_value (right_value)',
            'ft_categories_search' => 'ALTER TABLE categories ADD FULLTEXT INDEX ft_categories_search (name, description)'
        ];
        
        foreach ($indices as $indexName => $sql) {
            try {
                // Verificar se o índice já existe
                $checkSql = "SHOW INDEX FROM categories WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $indexName]);
                
                if (empty($indexExists)) {
                    // Aplicar índice
                    $this->db->execute($sql);
                    $result['applied'][] = $indexName;
                } else {
                    // Índice já existe
                    $result['applied'][] = $indexName . ' (já existente)';
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'index' => $indexName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Aplica índices para a tabela orders
     * 
     * @return array Resultado da aplicação
     */
    private function applyOrderIndices() {
        $result = ['success' => true, 'applied' => [], 'errors' => []];
        
        $indices = [
            'idx_orders_user_id' => 'ALTER TABLE orders ADD INDEX idx_orders_user_id (user_id)',
            'idx_orders_status' => 'ALTER TABLE orders ADD INDEX idx_orders_status (status)',
            'idx_orders_payment_status' => 'ALTER TABLE orders ADD INDEX idx_orders_payment_status (payment_status)',
            'idx_orders_created_at' => 'ALTER TABLE orders ADD INDEX idx_orders_created_at (created_at)',
            'idx_orders_print_start_date' => 'ALTER TABLE orders ADD INDEX idx_orders_print_start_date (print_start_date)',
            'idx_orders_status_created_at' => 'ALTER TABLE orders ADD INDEX idx_orders_status_created_at (status, created_at)',
            'idx_orders_payment_status_created_at' => 'ALTER TABLE orders ADD INDEX idx_orders_payment_status_created_at (payment_status, created_at)'
        ];
        
        foreach ($indices as $indexName => $sql) {
            try {
                // Verificar se o índice já existe
                $checkSql = "SHOW INDEX FROM orders WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $indexName]);
                
                if (empty($indexExists)) {
                    // Aplicar índice
                    $this->db->execute($sql);
                    $result['applied'][] = $indexName;
                } else {
                    // Índice já existe
                    $result['applied'][] = $indexName . ' (já existente)';
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'index' => $indexName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Aplica índices para a tabela order_items
     * 
     * @return array Resultado da aplicação
     */
    private function applyOrderItemsIndices() {
        $result = ['success' => true, 'applied' => [], 'errors' => []];
        
        $indices = [
            'idx_order_items_order_id' => 'ALTER TABLE order_items ADD INDEX idx_order_items_order_id (order_id)',
            'idx_order_items_product_id' => 'ALTER TABLE order_items ADD INDEX idx_order_items_product_id (product_id)',
            'idx_order_items_is_stock_item' => 'ALTER TABLE order_items ADD INDEX idx_order_items_is_stock_item (is_stock_item)'
        ];
        
        foreach ($indices as $indexName => $sql) {
            try {
                // Verificar se o índice já existe
                $checkSql = "SHOW INDEX FROM order_items WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $indexName]);
                
                if (empty($indexExists)) {
                    // Aplicar índice
                    $this->db->execute($sql);
                    $result['applied'][] = $indexName;
                } else {
                    // Índice já existe
                    $result['applied'][] = $indexName . ' (já existente)';
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['errors'][] = [
                    'index' => $indexName,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Aplica os índices definidos em um arquivo SQL
     * 
     * @param string $filePath Caminho para o arquivo SQL
     * @return array Resultado da aplicação
     */
    public function applyIndicesFromFile($filePath = null) {
        if ($filePath === null) {
            $filePath = APP_PATH . '/../database/migrations/indices_otimizacao_sql.sql';
        }
        
        $result = ['success' => true, 'applied' => [], 'errors' => []];
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            $result['success'] = false;
            $result['errors'][] = [
                'error' => 'Arquivo de índices não encontrado: ' . $filePath
            ];
            return $result;
        }
        
        // Ler o arquivo
        $sql = file_get_contents($filePath);
        
        // Dividir em comandos individuais
        $commands = explode(';', $sql);
        
        // Executar cada comando
        foreach ($commands as $command) {
            $command = trim($command);
            
            // Ignorar linhas vazias e comentários
            if (empty($command) || strpos($command, '--') === 0) {
                continue;
            }
            
            try {
                // Extrair nome do índice
                if (preg_match('/CREATE\s+INDEX\s+.*?(\w+)\s+ON\s+(\w+)/i', $command, $matches)) {
                    $indexName = $matches[1];
                    $tableName = $matches[2];
                    
                    // Verificar se o índice já existe
                    $checkSql = "SHOW INDEX FROM {$tableName} WHERE Key_name = :index_name";
                    $indexExists = $this->db->select($checkSql, ['index_name' => $indexName]);
                    
                    if (empty($indexExists)) {
                        // Aplicar índice
                        $this->db->execute($command);
                        $result['applied'][] = "{$indexName} ({$tableName})";
                    } else {
                        // Índice já existe
                        $result['applied'][] = "{$indexName} ({$tableName}) - já existente";
                    }
                }
            } catch (Exception $e) {
                $result['errors'][] = [
                    'command' => $command,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Verificar se houve erros
        if (!empty($result['errors'])) {
            $result['success'] = false;
        }
        
        return $result;
    }
    
    /**
     * Testa a performance das consultas otimizadas no OrderModel
     * 
     * @return array Resultados dos testes
     */
    public function testOrderModelPerformance() {
        $orderModel = new OrderModel();
        $results = [];
        $iterations = 5;
        
        // Teste 1: getOrdersByUser
        $test1Times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $orderModel->getOrdersByUser(1); // assumindo que existe um usuário com ID 1
            $test1Times[] = microtime(true) - $startTime;
        }
        $results['getOrdersByUser'] = [
            'avg_time' => array_sum($test1Times) / count($test1Times),
            'min_time' => min($test1Times),
            'max_time' => max($test1Times)
        ];
        
        // Teste 2: getOrderItems
        $test2Times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $orderModel->getOrderItems(1); // assumindo que existe um pedido com ID 1
            $test2Times[] = microtime(true) - $startTime;
        }
        $results['getOrderItems'] = [
            'avg_time' => array_sum($test2Times) / count($test2Times),
            'min_time' => min($test2Times),
            'max_time' => max($test2Times)
        ];
        
        // Teste 3: getSalesByCategory
        $test3Times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $orderModel->getSalesByCategory();
            $test3Times[] = microtime(true) - $startTime;
        }
        $results['getSalesByCategory'] = [
            'avg_time' => array_sum($test3Times) / count($test3Times),
            'min_time' => min($test3Times),
            'max_time' => max($test3Times)
        ];
        
        // Teste 4: getCurrentlyPrintingOrders
        $test4Times = [];
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $orderModel->getCurrentlyPrintingOrders();
            $test4Times[] = microtime(true) - $startTime;
        }
        $results['getCurrentlyPrintingOrders'] = [
            'avg_time' => array_sum($test4Times) / count($test4Times),
            'min_time' => min($test4Times),
            'max_time' => max($test4Times)
        ];
        
        return $results;
    }
    
    /**
     * Testa a performance antes e depois das otimizações
     * 
     * @param array $tests Lista de testes a executar
     * @return array Resultados dos testes
     */
    public function testPerformance($tests = null) {
        // Testes padrão se não forem especificados
        if ($tests === null) {
            $tests = [
                [
                    'name' => 'Busca de produtos em destaque',
                    'model' => 'ProductModel',
                    'method' => 'getFeatured',
                    'args' => [8]
                ],
                [
                    'name' => 'Busca de produtos por categoria',
                    'model' => 'ProductModel',
                    'method' => 'getByCategory',
                    'args' => [1, 1, 12, 'all']
                ],
                [
                    'name' => 'Busca de produtos por termo',
                    'model' => 'ProductModel',
                    'method' => 'search',
                    'args' => ['teste', 1, 12, 'all']
                ],
                [
                    'name' => 'Obter produto por slug',
                    'model' => 'ProductModel',
                    'method' => 'getBySlug',
                    'args' => ['produto-teste']
                ],
                [
                    'name' => 'Busca de categorias principais',
                    'model' => 'CategoryModel',
                    'method' => 'getMainCategories',
                    'args' => [false]
                ],
                [
                    'name' => 'Obter categoria com produtos',
                    'model' => 'CategoryModel',
                    'method' => 'getCategoryWithProducts',
                    'args' => ['categoria-teste', 1, 12, true]
                ],
                [
                    'name' => 'Pedidos de um usuário',
                    'model' => 'OrderModel',
                    'method' => 'getOrdersByUser',
                    'args' => [1]
                ],
                [
                    'name' => 'Itens de um pedido',
                    'model' => 'OrderModel',
                    'method' => 'getOrderItems',
                    'args' => [1]
                ],
                [
                    'name' => 'Vendas por categoria',
                    'model' => 'OrderModel',
                    'method' => 'getSalesByCategory',
                    'args' => []
                ]
            ];
        }
        
        // Resultados
        $results = [];
        
        // Testar cada caso
        foreach ($tests as $test) {
            $modelName = $test['model'];
            $methodName = $test['method'];
            $args = $test['args'];
            
            // Instanciar modelo
            $model = new $modelName();
            
            // Executar método várias vezes para obter média
            $times = [];
            $iterations = 5;
            
            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                
                try {
                    call_user_func_array([$model, $methodName], $args);
                } catch (Exception $e) {
                    // Ignorar erros para fins de teste de performance
                }
                
                $endTime = microtime(true);
                $times[] = $endTime - $startTime;
            }
            
            // Calcular média e outros dados estatísticos
            $avgTime = array_sum($times) / count($times);
            $minTime = min($times);
            $maxTime = max($times);
            
            // Adicionar ao resultado
            $results[] = [
                'name' => $test['name'],
                'model' => $modelName,
                'method' => $methodName,
                'avg_time' => $avgTime,
                'min_time' => $minTime,
                'max_time' => $maxTime,
                'iterations' => $iterations
            ];
        }
        
        return $results;
    }
    
    /**
     * Gera relatório HTML com resultados de performance
     * 
     * @param array $before Resultados antes das otimizações
     * @param array $after Resultados após as otimizações
     * @return string HTML do relatório
     */
    public function generatePerformanceReport($before, $after) {
        $html = '<div class="performance-report">';
        $html .= '<h2>Relatório de Performance após Otimizações SQL</h2>';
        
        $html .= '<table class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Teste</th>';
        $html .= '<th>Modelo</th>';
        $html .= '<th>Método</th>';
        $html .= '<th>Tempo Antes (ms)</th>';
        $html .= '<th>Tempo Depois (ms)</th>';
        $html .= '<th>Melhoria</th>';
        $html .= '<th>Melhoria %</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($before as $index => $beforeTest) {
            $afterTest = $after[$index] ?? null;
            
            if ($afterTest) {
                $timeBefore = $beforeTest['avg_time'] * 1000; // em ms
                $timeAfter = $afterTest['avg_time'] * 1000; // em ms
                $improvement = $timeBefore - $timeAfter;
                $percentImprovement = ($timeBefore > 0) ? ($improvement / $timeBefore * 100) : 0;
                
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($beforeTest['name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($beforeTest['model']) . '</td>';
                $html .= '<td>' . htmlspecialchars($beforeTest['method']) . '</td>';
                $html .= '<td>' . number_format($timeBefore, 2) . ' ms</td>';
                $html .= '<td>' . number_format($timeAfter, 2) . ' ms</td>';
                $html .= '<td>' . number_format($improvement, 2) . ' ms</td>';
                
                // Cor baseada na melhoria percentual
                $percentClass = $percentImprovement >= 20 ? 'text-success' : ($percentImprovement >= 5 ? 'text-primary' : 'text-danger');
                $html .= '<td class="' . $percentClass . '">' . number_format($percentImprovement, 2) . '%</td>';
                
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Conclusões gerais
        $totalBefore = array_sum(array_column($before, 'avg_time'));
        $totalAfter = array_sum(array_column($after, 'avg_time'));
        $totalImprovement = $totalBefore - $totalAfter;
        $totalPercentImprovement = ($totalBefore > 0) ? ($totalImprovement / $totalBefore * 100) : 0;
        
        $html .= '<div class="alert alert-info">';
        $html .= '<h4>Conclusão</h4>';
        $html .= '<p>Melhoria total: ' . number_format($totalPercentImprovement, 2) . '%</p>';
        $html .= '<p>Tempo total antes: ' . number_format($totalBefore * 1000, 2) . ' ms</p>';
        $html .= '<p>Tempo total depois: ' . number_format($totalAfter * 1000, 2) . ' ms</p>';
        
        // Recomendações com base nos resultados
        $html .= '<h4>Recomendações Adicionais</h4>';
        $html .= '<ul>';
        
        if ($totalPercentImprovement < 10) {
            $html .= '<li>As otimizações tiveram impacto limitado. Considerar análise mais profunda do esquema e consultas.</li>';
        }
        
        $html .= '<li>Considerar implementação de cache para consultas frequentes</li>';
        $html .= '<li>Avaliar oportunidades para materialização de visões para relatórios complexos</li>';
        $html .= '<li>Monitorar performance em ambiente de produção para identificar gargalos adicionais</li>';
        $html .= '</ul>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Verifica se todos os índices recomendados foram aplicados
     * 
     * @return bool True se todos os índices foram aplicados
     */
    public function checkAllIndicesApplied() {
        $allApplied = true;
        
        // Índices de produtos
        $productIndices = [
            'idx_products_is_featured',
            'idx_products_is_tested',
            'idx_products_is_active',
            'idx_products_created_at',
            'idx_products_category_id',
            'idx_products_slug',
            'idx_products_is_customizable',
            'idx_products_stock',
            'idx_products_availability',
            'ft_products_search'
        ];
        
        foreach ($productIndices as $index) {
            $checkSql = "SHOW INDEX FROM products WHERE Key_name = :index_name";
            $indexExists = $this->db->select($checkSql, ['index_name' => $index]);
            
            if (empty($indexExists)) {
                $allApplied = false;
                break;
            }
        }
        
        // Se ainda todos estão aplicados, verificar imagens de produto
        if ($allApplied) {
            $imageIndices = [
                'idx_product_images_product_id',
                'idx_product_images_is_main',
                'idx_product_images_product_main'
            ];
            
            foreach ($imageIndices as $index) {
                $checkSql = "SHOW INDEX FROM product_images WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $index]);
                
                if (empty($indexExists)) {
                    $allApplied = false;
                    break;
                }
            }
        }
        
        // Se ainda todos estão aplicados, verificar categorias
        if ($allApplied) {
            $categoryIndices = [
                'idx_categories_parent_id',
                'idx_categories_is_active',
                'idx_categories_display_order',
                'idx_categories_slug',
                'idx_categories_left_value',
                'idx_categories_right_value',
                'ft_categories_search'
            ];
            
            foreach ($categoryIndices as $index) {
                $checkSql = "SHOW INDEX FROM categories WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $index]);
                
                if (empty($indexExists)) {
                    $allApplied = false;
                    break;
                }
            }
        }
        
        // Se ainda todos estão aplicados, verificar pedidos
        if ($allApplied) {
            $orderIndices = [
                'idx_orders_user_id',
                'idx_orders_status',
                'idx_orders_payment_status',
                'idx_orders_created_at',
                'idx_orders_print_start_date'
            ];
            
            foreach ($orderIndices as $index) {
                $checkSql = "SHOW INDEX FROM orders WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $index]);
                
                if (empty($indexExists)) {
                    $allApplied = false;
                    break;
                }
            }
        }
        
        // Se ainda todos estão aplicados, verificar items de pedidos
        if ($allApplied) {
            $orderItemIndices = [
                'idx_order_items_order_id',
                'idx_order_items_product_id'
            ];
            
            foreach ($orderItemIndices as $index) {
                $checkSql = "SHOW INDEX FROM order_items WHERE Key_name = :index_name";
                $indexExists = $this->db->select($checkSql, ['index_name' => $index]);
                
                if (empty($indexExists)) {
                    $allApplied = false;
                    break;
                }
            }
        }
        
        return $allApplied;
    }
}