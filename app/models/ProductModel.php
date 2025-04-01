<?php
/**
 * ProductModel - Modelo para produtos
 */
class ProductModel extends Model {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'short_description', 
        'price', 'sale_price', 'stock', 'dimensions', 'sku',
        'is_featured', 'is_active', 'is_customizable', 
        // Campos específicos para impressão 3D
        'print_time_hours', 'filament_type', 'filament_usage_grams',
        'scale', 'model_file', 'is_tested'
    ];
    
    /**
     * Obtém produtos em destaque
     * 
     * @param int $limit Número máximo de produtos a retornar
     * @return array Lista de produtos
     */
    public function getFeatured($limit = 8) {
        try {
            // Adicionar log de diagnóstico
            error_log("ProductModel::getFeatured - Iniciando busca de produtos em destaque (limit: $limit)");
            
            // CORREÇÃO: Remover dependência de colunas que possam não existir
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock, 
                           pi.image, 
                           CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.is_active = 1
                    ORDER BY p.created_at DESC
                    LIMIT :limit";
            
            $result = $this->db()->select($sql, ['limit' => $limit]);
            
            // Adicionar log com o resultado
            error_log("ProductModel::getFeatured - Encontrados " . count($result) . " produtos em destaque");
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos em destaque: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Obtém produtos testados disponíveis para pronta entrega
     * 
     * @param int $limit Número máximo de produtos a retornar
     * @return array Lista de produtos testados
     */
    public function getTestedProducts($limit = 12) {
        try {
            // Adicionar log de diagnóstico
            error_log("ProductModel::getTestedProducts - Iniciando busca de produtos testados (limit: $limit)");
            
            // CORREÇÃO: Ajustar consulta para não depender de flags
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock,
                           pi.image,
                           'Pronta Entrega' as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.stock > 0 AND p.is_active = 1
                    ORDER BY p.created_at DESC
                    LIMIT :limit";
            
            $result = $this->db()->select($sql, ['limit' => $limit]);
            
            // Adicionar log com o resultado
            error_log("ProductModel::getTestedProducts - Encontrados " . count($result) . " produtos testados");
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos testados: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Obtém produtos disponíveis sob encomenda
     * 
     * @param int $limit Número máximo de produtos a retornar
     * @return array Lista de produtos sob encomenda
     */
    public function getCustomProducts($limit = 12) {
        try {
            // Adicionar log de diagnóstico
            error_log("ProductModel::getCustomProducts - Iniciando busca de produtos sob encomenda (limit: $limit)");
            
            // CORREÇÃO: Mostrar todos os produtos ativos
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock, p.created_at,
                          pi.image,
                          'Sob Encomenda' as availability
                   FROM {$this->table} p
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                   WHERE p.is_active = 1
                   ORDER BY p.created_at DESC
                   LIMIT :limit";
            
            $result = $this->db()->select($sql, ['limit' => $limit]);
            
            // Adicionar log com o resultado
            error_log("ProductModel::getCustomProducts - Encontrados " . count($result) . " produtos sob encomenda");
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos sob encomenda: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Versão personalizada de paginação que inclui suporte a filtro de disponibilidade
     * 
     * @param int $page Página atual
     * @param int $limit Produtos por página
     * @param string $conditions Condições SQL adicionais
     * @param array $params Parâmetros para as condições
     * @param string $availability Filtro de disponibilidade ('all', 'tested', 'custom')
     * @param string $orderBy Ordem dos resultados
     * @return array Produtos e informações de paginação
     */
    public function paginate($page = 1, $limit = 10, $conditions = '1=1', $params = [], $availability = 'all', $orderBy = "created_at DESC") {
        try {
            error_log("ProductModel::paginate - Iniciando paginação (page: $page, limit: $limit, availability: $availability)");
            error_log("ProductModel::paginate - Condições: $conditions");
            error_log("ProductModel::paginate - Parâmetros: " . json_encode($params));
            
            $offset = ($page - 1) * $limit;
            
            // Filtro de disponibilidade - CORREÇÃO: simplificar para depender apenas de stock
            $availabilityFilter = "";
            if ($availability === 'tested') {
                $availabilityFilter = " AND p.stock > 0"; // Pronta entrega = tem estoque
            } else if ($availability === 'custom') {
                $availabilityFilter = " AND p.stock = 0"; // Sob encomenda = não tem estoque
            }
            
            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total 
                        FROM {$this->table} p 
                        WHERE {$conditions}" . $availabilityFilter;
            $countResult = $this->db()->select($countSql, $params);
            $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
            
            error_log("ProductModel::paginate - Total de registros encontrados: $total");
            
            // Buscar registros paginados com imagens e dados de disponibilidade
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock, p.short_description,
                           pi.image,
                           CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE {$conditions}" . $availabilityFilter . "
                    ORDER BY " . $orderBy . "
                    LIMIT :offset, :limit";
            
            $queryParams = array_merge($params, ['offset' => $offset, 'limit' => $limit]);
            $items = $this->db()->select($sql, $queryParams);
            
            error_log("ProductModel::paginate - Encontrados " . count($items) . " itens para a página atual");
            
            return [
                'items' => $items,
                'total' => $total,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => ceil($total / $limit),
                'from' => $offset + 1,
                'to' => min($offset + $limit, $total),
                'availability' => $availability
            ];
        } catch (Exception $e) {
            error_log("Erro na paginação de produtos: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'items' => [],
                'total' => 0,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => 1,
                'from' => 0,
                'to' => 0,
                'availability' => $availability
            ];
        }
    }
    
    /**
     * Obtém produtos por categoria
     * 
     * @param int $categoryId ID da categoria
     * @param int $page Página atual
     * @param int $limit Produtos por página
     * @param string $availability Filtro de disponibilidade ('all', 'tested', 'custom')
     * @return array Produtos e informações de paginação
     */
    public function getByCategory($categoryId, $page = 1, $limit = 12, $availability = 'all') {
        try {
            error_log("ProductModel::getByCategory - Iniciando busca por categoria (categoryId: $categoryId, page: $page, limit: $limit, availability: $availability)");
            
            $offset = ($page - 1) * $limit;
            $params = ['category_id' => $categoryId];
            
            // Filtro de disponibilidade - CORREÇÃO: simplificar para depender apenas de stock
            $availabilityFilter = "";
            if ($availability === 'tested') {
                $availabilityFilter = " AND p.stock > 0"; // Pronta entrega = tem estoque
            } else if ($availability === 'custom') {
                $availabilityFilter = " AND p.stock = 0"; // Sob encomenda = não tem estoque
            }
            
            // Otimização: Usar SQL_CALC_FOUND_ROWS para evitar consulta COUNT(*) separada
            // Buscar produtos
            $sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.stock, p.short_description,
                           pi.image,
                           CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.category_id = :category_id AND p.is_active = 1" . $availabilityFilter . "
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $items = $this->db()->select($sql, $params);
            error_log("ProductModel::getByCategory - SQL executado: " . str_replace(array("\r", "\n"), ' ', $sql));
            error_log("ProductModel::getByCategory - Parâmetros: " . json_encode($params));
            error_log("ProductModel::getByCategory - Encontrados " . count($items) . " itens para a página atual");
            
            // Obter o total de registros encontrados
            $totalResult = $this->db()->select("SELECT FOUND_ROWS() as total");
            $total = isset($totalResult[0]['total']) ? $totalResult[0]['total'] : 0;
            error_log("ProductModel::getByCategory - Total de registros encontrados: $total");
            
            return [
                'items' => $items,
                'total' => $total,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => ceil($total / $limit),
                'availability' => $availability
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos por categoria: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'items' => [],
                'total' => 0,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => 1,
                'availability' => $availability
            ];
        }
    }
    
    /**
     * Obtém produto pelo slug
     * 
     * @param string $slug Slug do produto
     * @return array|null Dados do produto ou null se não encontrado
     */
    public function getBySlug($slug) {
        try {
            error_log("ProductModel::getBySlug - Iniciando busca por slug: $slug");
            
            // CORREÇÃO: Usar consulta dinâmica para verificar quais colunas existem
            $columns = [];
            try {
                $columnsQuery = $this->db()->select("SHOW COLUMNS FROM {$this->table}");
                foreach ($columnsQuery as $column) {
                    $columns[] = $column['Field'];
                }
                error_log("ProductModel::getBySlug - Colunas disponíveis: " . implode(", ", $columns));
            } catch (Exception $e) {
                error_log("ProductModel::getBySlug - Erro ao obter colunas: " . $e->getMessage());
                // Usar colunas mínimas necessárias
                $columns = ['id', 'name', 'slug', 'description', 'short_description', 'price', 'sale_price', 'stock', 'category_id', 'is_active'];
            }
            
            // Construir consulta com colunas disponíveis
            $selectColumns = "p.id, p.name, p.slug, p.description, p.short_description, p.price, p.sale_price, p.stock";
            
            // Adicionar colunas opcionais se existirem
            $optionalColumns = ['dimensions', 'sku', 'is_featured', 'is_active', 'is_customizable', 'print_time_hours', 'filament_type', 'filament_usage_grams', 'scale', 'model_file', 'is_tested'];
            foreach ($optionalColumns as $column) {
                if (in_array($column, $columns)) {
                    $selectColumns .= ", p.$column";
                }
            }
            
            $sql = "SELECT $selectColumns, 
                           c.name as category_name, c.slug as category_slug
                    FROM {$this->table} p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug AND p.is_active = 1";
            
            error_log("ProductModel::getBySlug - SQL: " . str_replace(array("\r", "\n"), ' ', $sql));
            
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            error_log("ProductModel::getBySlug - Resultado da consulta: " . json_encode(!empty($result) ? ['count' => count($result), 'first_id' => $result[0]['id']] : []));
            
            if (empty($result)) {
                error_log("ProductModel::getBySlug - Produto não encontrado para slug: $slug");
                return null;
            }
            
            $product = $result[0];
            error_log("ProductModel::getBySlug - Produto encontrado, ID: " . $product['id']);
            
            // Definir disponibilidade - CORREÇÃO: Simplificar para depender apenas de stock
            $product['availability'] = ($product['stock'] > 0) ? 'Pronta Entrega' : 'Sob Encomenda';
            $product['estimated_delivery'] = ($product['stock'] > 0) ? '2 a 5 dias úteis' : '7 a 15 dias úteis';
            
            // Buscar imagens
            try {
                $sql = "SELECT id, product_id, image, is_main, display_order 
                        FROM product_images 
                        WHERE product_id = :id 
                        ORDER BY is_main DESC, display_order ASC";
                error_log("ProductModel::getBySlug - Buscando imagens para produto ID: " . $product['id']);
                $product['images'] = $this->db()->select($sql, ['id' => $product['id']]);
                error_log("ProductModel::getBySlug - Imagens encontradas: " . count($product['images']));
            } catch (Exception $e) {
                error_log("Erro ao buscar imagens do produto: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $product['images'] = [];
            }
            
            // Buscar opções de personalização apenas se a coluna is_customizable existir e for true
            if (isset($product['is_customizable']) && $product['is_customizable']) {
                try {
                    $customizationTableExists = false;
                    try {
                        $tableCheck = $this->db()->select("SHOW TABLES LIKE 'customization_options'");
                        $customizationTableExists = !empty($tableCheck);
                    } catch (Exception $e) {
                        error_log("ProductModel::getBySlug - Erro ao verificar tabela customization_options: " . $e->getMessage());
                    }
                    
                    if ($customizationTableExists) {
                        $sql = "SELECT id, product_id, name, description, required, type, options_json  
                                FROM customization_options 
                                WHERE product_id = :id";
                        error_log("ProductModel::getBySlug - Buscando opções de personalização para produto ID: " . $product['id']);
                        $product['customization_options'] = $this->db()->select($sql, ['id' => $product['id']]);
                        error_log("ProductModel::getBySlug - Opções de personalização encontradas: " . count($product['customization_options']));
                    } else {
                        $product['customization_options'] = [];
                    }
                } catch (Exception $e) {
                    error_log("Erro ao buscar opções de personalização: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    $product['customization_options'] = [];
                }
            }
            
            // Obter cores de filamento disponíveis para este tipo de produto
            try {
                // Verificar se FilamentModel existe
                $filamentModelExists = false;
                try {
                    $classExists = class_exists('FilamentModel');
                    $filamentModelExists = $classExists;
                } catch (Exception $e) {
                    error_log("ProductModel::getBySlug - Erro ao verificar classe FilamentModel: " . $e->getMessage());
                }
                
                if ($filamentModelExists) {
                    error_log("ProductModel::getBySlug - Tentando obter instância de FilamentModel");
                    $filamentModel = new FilamentModel();
                    error_log("ProductModel::getBySlug - FilamentModel instanciado com sucesso");
                    
                    $filamentType = isset($product['filament_type']) ? $product['filament_type'] : 'PLA';
                    error_log("ProductModel::getBySlug - Buscando cores de filamento para tipo: $filamentType");
                    
                    $product['filament_colors'] = $filamentModel->getColors($filamentType);
                    error_log("ProductModel::getBySlug - Cores de filamento encontradas: " . count($product['filament_colors']));
                } else {
                    $product['filament_colors'] = [];
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar cores de filamento: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $product['filament_colors'] = [];
            }
            
            return $product;
        } catch (Exception $e) {
            error_log("Erro ao buscar produto por slug: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Verifica se o índice FULLTEXT está disponível
     * 
     * @return bool True se o índice FULLTEXT está disponível
     */
    private function hasFulltextIndex() {
        static $hasFulltext = null;
        
        if ($hasFulltext === null) {
            try {
                $showIndexSql = "SHOW INDEX FROM {$this->table} WHERE Key_name = 'ft_products_search'";
                $indexResult = $this->db()->select($showIndexSql);
                $hasFulltext = !empty($indexResult);
            } catch (Exception $e) {
                $hasFulltext = false;
            }
        }
        
        return $hasFulltext;
    }
    
    /**
     * Busca produtos por termo
     * 
     * @param string $query Termo de busca
     * @param int $page Página atual
     * @param int $limit Produtos por página
     * @param string $availability Filtro de disponibilidade ('all', 'tested', 'custom')
     * @return array Produtos e informações de paginação
     */
    public function search($query, $page = 1, $limit = 12, $availability = 'all') {
        try {
            error_log("ProductModel::search - Iniciando busca por termo: $query (page: $page, limit: $limit, availability: $availability)");
            
            $offset = ($page - 1) * $limit;
            $searchTerm = "%{$query}%";
            $params = ['term' => $searchTerm, 'termExact' => $query];
            
            // Filtro de disponibilidade - CORREÇÃO: simplificar para depender apenas de stock
            $availabilityFilter = "";
            if ($availability === 'tested') {
                $availabilityFilter = " AND p.stock > 0"; // Pronta entrega = tem estoque
            } else if ($availability === 'custom') {
                $availabilityFilter = " AND p.stock = 0"; // Sob encomenda = não tem estoque
            }
            
            // Verificar se temos um índice FULLTEXT
            $hasFulltext = $this->hasFulltextIndex();
            error_log("ProductModel::search - Índice FULLTEXT disponível: " . ($hasFulltext ? "Sim" : "Não"));
            
            // Buscar produtos com SQL_CALC_FOUND_ROWS para eliminar a consulta COUNT separada
            if ($hasFulltext) {
                $sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.stock, p.short_description,
                               pi.image,
                               CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability,
                               MATCH(p.name, p.description) AGAINST(:termExact) as relevance
                        FROM {$this->table} p
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                        WHERE MATCH(p.name, p.description) AGAINST(:termExact IN BOOLEAN MODE) 
                        AND p.is_active = 1" . $availabilityFilter . "
                        ORDER BY relevance DESC, p.name ASC
                        LIMIT :offset, :limit";
            } else {
                $sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.stock, p.short_description,
                               pi.image,
                               CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                        FROM {$this->table} p
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                        WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1" . $availabilityFilter . "
                        ORDER BY 
                          CASE WHEN p.name = :termExact THEN 1
                               WHEN p.name LIKE CONCAT(:termExact, '%') THEN 2
                               ELSE 3
                          END,
                          p.name ASC
                        LIMIT :offset, :limit";
            }
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            error_log("ProductModel::search - SQL: " . str_replace(array("\r", "\n"), ' ', $sql));
            error_log("ProductModel::search - Parâmetros: " . json_encode($params));
            
            $items = $this->db()->select($sql, $params);
            error_log("ProductModel::search - Itens encontrados: " . count($items));
            
            // Obter o total de registros encontrados
            $totalResult = $this->db()->select("SELECT FOUND_ROWS() as total");
            $total = isset($totalResult[0]['total']) ? $totalResult[0]['total'] : 0;
            error_log("ProductModel::search - Total de registros encontrados: $total");
            
            return [
                'items' => $items,
                'total' => $total,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => ceil($total / $limit),
                'query' => $query,
                'availability' => $availability
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'items' => [],
                'total' => 0,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => 1,
                'query' => $query,
                'availability' => $availability
            ];
        }
    }
    
    /**
     * Obtém produtos relacionados
     * 
     * @param int $productId ID do produto atual
     * @param int $categoryId ID da categoria
     * @param int $limit Número máximo de produtos a retornar
     * @return array Lista de produtos relacionados
     */
    public function getRelated($productId, $categoryId, $limit = 4) {
        try {
            error_log("ProductModel::getRelated - Iniciando busca de produtos relacionados (productId: $productId, categoryId: $categoryId, limit: $limit)");
            
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock, p.short_description,
                          pi.image,
                          CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                   FROM {$this->table} p
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                   WHERE p.id != :product_id AND p.category_id = :category_id AND p.is_active = 1
                   ORDER BY p.id DESC
                   LIMIT :limit";
            
            // Obter produtos relacionados
            $result = $this->db()->select($sql, [
                'product_id' => $productId,
                'category_id' => $categoryId,
                'limit' => $limit * 2 // Buscar mais produtos para escolher aleatoriamente
            ]);
            
            error_log("ProductModel::getRelated - Produtos relacionados encontrados (pré-filtro): " . count($result));
            
            // Selecionar aleatoriamente alguns dos produtos encontrados
            if (count($result) > $limit) {
                shuffle($result);
                $result = array_slice($result, 0, $limit);
            }
            
            error_log("ProductModel::getRelated - Produtos relacionados retornados: " . count($result));
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos relacionados: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Atualiza o estoque de um produto
     * 
     * @param int $productId ID do produto
     * @param int $quantity Quantidade a ser adicionada ou removida (pode ser negativo)
     * @return bool Sucesso da operação
     */
    public function updateStock($productId, $quantity) {
        try {
            $product = $this->find($productId);
            if (!$product) {
                return false;
            }
            
            $newStock = max(0, $product['stock'] + $quantity);
            return $this->update($productId, ['stock' => $newStock]);
        } catch (Exception $e) {
            error_log("Erro ao atualizar estoque: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Obtém o tempo estimado de impressão para um produto
     * 
     * @param int $productId ID do produto
     * @param int $quantity Quantidade de itens
     * @return float Tempo estimado em horas
     */
    public function getEstimatedPrintTime($productId, $quantity = 1) {
        try {
            $product = $this->find($productId);
            if (!$product || !isset($product['print_time_hours'])) {
                return 0;
            }
            
            return $product['print_time_hours'] * $quantity;
        } catch (Exception $e) {
            error_log("Erro ao calcular tempo de impressão: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0;
        }
    }
    
    /**
     * Obtém o uso estimado de filamento para um produto
     * 
     * @param int $productId ID do produto
     * @param int $quantity Quantidade de itens
     * @return int Uso estimado em gramas
     */
    public function getEstimatedFilamentUsage($productId, $quantity = 1) {
        try {
            $product = $this->find($productId);
            if (!$product || !isset($product['filament_usage_grams'])) {
                return 0;
            }
            
            return $product['filament_usage_grams'] * $quantity;
        } catch (Exception $e) {
            error_log("Erro ao calcular uso de filamento: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0;
        }
    }
    
    /**
     * Obtém o nome da tabela
     * 
     * @return string Nome da tabela
     */
    public function getTable() {
        return $this->table;
    }
    
    /**
     * Obtém produtos personalizáveis
     * 
     * @param int $limit Número máximo de produtos a retornar
     * @return array Lista de produtos personalizáveis
     */
    public function getCustomizableProducts($limit = 12) {
        try {
            error_log("ProductModel::getCustomizableProducts - Iniciando busca de produtos personalizáveis (limit: $limit)");
            
            // CORREÇÃO: Remover dependência de colunas que possam não existir
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock,
                           pi.image,
                           CASE WHEN p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.is_active = 1
                    ORDER BY p.created_at DESC
                    LIMIT :limit";
            
            $result = $this->db()->select($sql, ['limit' => $limit]);
            error_log("ProductModel::getCustomizableProducts - Produtos personalizáveis encontrados: " . count($result));
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos personalizáveis: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }
}