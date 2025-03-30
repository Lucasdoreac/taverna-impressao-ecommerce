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
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, 
                           pi.image, 
                           CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.is_featured = 1 AND p.is_active = 1
                    ORDER BY p.is_tested DESC, p.created_at DESC
                    LIMIT :limit";
            
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos em destaque: " . $e->getMessage());
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
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock,
                           pi.image,
                           'Pronta Entrega' as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.is_tested = 1 AND p.stock > 0 AND p.is_active = 1
                    ORDER BY p.created_at DESC
                    LIMIT :limit";
            
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos testados: " . $e->getMessage());
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
            // Otimização: Usar UNION ALL para combinar as consultas em vez de fazer duas separadas e combinar no PHP
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.created_at,
                          pi.image,
                          'Sob Encomenda' as availability
                   FROM {$this->table} p
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                   WHERE p.is_tested = 0 AND p.is_active = 1
                   
                   UNION ALL
                   
                   SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.created_at,
                          pi.image,
                          'Sob Encomenda' as availability
                   FROM {$this->table} p
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                   WHERE p.stock = 0 AND p.is_tested = 1 AND p.is_active = 1
                   
                   ORDER BY created_at DESC
                   LIMIT :limit";
            
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos sob encomenda: " . $e->getMessage());
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
            $offset = ($page - 1) * $limit;
            
            // Filtro de disponibilidade
            $availabilityFilter = "";
            if ($availability === 'tested') {
                $availabilityFilter = " AND p.is_tested = 1 AND p.stock > 0";
            } else if ($availability === 'custom') {
                $availabilityFilter = " AND (p.is_tested = 0 OR p.stock = 0)";
            }
            
            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total 
                        FROM {$this->table} p 
                        WHERE {$conditions}" . $availabilityFilter;
            $countResult = $this->db()->select($countSql, $params);
            $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
            
            // Buscar registros paginados com imagens e dados de disponibilidade
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
                           pi.image,
                           CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE {$conditions}" . $availabilityFilter . "
                    ORDER BY " . ($availability === 'tested' ? "p.is_tested DESC, " : "") . $orderBy . "
                    LIMIT :offset, :limit";
            
            $queryParams = array_merge($params, ['offset' => $offset, 'limit' => $limit]);
            $items = $this->db()->select($sql, $queryParams);
            
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
            $offset = ($page - 1) * $limit;
            $params = ['category_id' => $categoryId];
            
            // Filtro de disponibilidade
            $availabilityFilter = "";
            if ($availability === 'tested') {
                $availabilityFilter = " AND p.is_tested = 1 AND p.stock > 0";
            } else if ($availability === 'custom') {
                $availabilityFilter = " AND (p.is_tested = 0 OR p.stock = 0)";
            }
            
            // Otimização: Usar SQL_CALC_FOUND_ROWS para evitar consulta COUNT(*) separada
            // Buscar produtos
            $sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
                           pi.image,
                           CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.category_id = :category_id AND p.is_active = 1" . $availabilityFilter . "
                    ORDER BY p.is_tested DESC, p.created_at DESC
                    LIMIT :offset, :limit";
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $items = $this->db()->select($sql, $params);
            
            // Obter o total de registros encontrados
            $totalResult = $this->db()->select("SELECT FOUND_ROWS() as total");
            $total = isset($totalResult[0]['total']) ? $totalResult[0]['total'] : 0;
            
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
            $sql = "SELECT p.id, p.name, p.slug, p.description, p.short_description, 
                           p.price, p.sale_price, p.stock, p.dimensions, p.sku, 
                           p.is_featured, p.is_active, p.is_customizable, 
                           p.print_time_hours, p.filament_type, p.filament_usage_grams, 
                           p.scale, p.model_file, p.is_tested, 
                           c.name as category_name, c.slug as category_slug
                    FROM {$this->table} p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug AND p.is_active = 1";
            
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                return null;
            }
            
            $product = $result[0];
            
            // Definir disponibilidade
            $product['availability'] = (isset($product['is_tested']) && $product['is_tested'] && $product['stock'] > 0) ? 'Pronta Entrega' : 'Sob Encomenda';
            $product['estimated_delivery'] = (isset($product['is_tested']) && $product['is_tested'] && $product['stock'] > 0) ? '2 a 5 dias úteis' : '7 a 15 dias úteis';
            
            // Buscar imagens
            try {
                $sql = "SELECT id, product_id, image, is_main, display_order 
                        FROM product_images 
                        WHERE product_id = :id 
                        ORDER BY is_main DESC, display_order ASC";
                $product['images'] = $this->db()->select($sql, ['id' => $product['id']]);
            } catch (Exception $e) {
                error_log("Erro ao buscar imagens do produto: " . $e->getMessage());
                $product['images'] = [];
            }
            
            // Buscar opções de personalização
            if (isset($product['is_customizable']) && $product['is_customizable']) {
                try {
                    $sql = "SELECT id, product_id, name, description, required, type, options_json  
                            FROM customization_options 
                            WHERE product_id = :id";
                    $product['customization_options'] = $this->db()->select($sql, ['id' => $product['id']]);
                } catch (Exception $e) {
                    error_log("Erro ao buscar opções de personalização: " . $e->getMessage());
                    $product['customization_options'] = [];
                }
            }
            
            // Obter cores de filamento disponíveis para este tipo de produto
            try {
                $filamentModel = new FilamentModel();
                $filamentType = isset($product['filament_type']) ? $product['filament_type'] : 'PLA';
                $product['filament_colors'] = $filamentModel->getColors($filamentType);
            } catch (Exception $e) {
                error_log("Erro ao buscar cores de filamento: " . $e->getMessage());
                $product['filament_colors'] = [];
            }
            
            return $product;
        } catch (Exception $e) {
            error_log("Erro ao buscar produto por slug: " . $e->getMessage());
            return null;
        }
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
            $offset = ($page - 1) * $limit;
            $searchTerm = "%{$query}%";
            $params = ['term' => $searchTerm];
            
            // Filtro de disponibilidade
            $availabilityFilter = "";
            if ($availability === 'tested') {
                $availabilityFilter = " AND p.is_tested = 1 AND p.stock > 0";
            } else if ($availability === 'custom') {
                $availabilityFilter = " AND (p.is_tested = 0 OR p.stock = 0)";
            }
            
            // Verificar se temos um índice FULLTEXT
            $hasFulltext = false;
            try {
                $showIndexSql = "SHOW INDEX FROM {$this->table} WHERE Key_name = 'ft_products_search'";
                $indexResult = $this->db()->select($showIndexSql);
                $hasFulltext = !empty($indexResult);
            } catch (Exception $e) {
                // Se ocorrer erro, assumir que não tem FULLTEXT
                $hasFulltext = false;
            }
            
            // Contar total
            $countSql = null;
            if ($hasFulltext) {
                $countSql = "SELECT COUNT(*) as total 
                            FROM {$this->table} p
                            WHERE MATCH(p.name, p.description) AGAINST(:term IN BOOLEAN MODE) 
                            AND p.is_active = 1" . $availabilityFilter;
            } else {
                $countSql = "SELECT COUNT(*) as total 
                            FROM {$this->table} p
                            WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1" . $availabilityFilter;
            }
            
            $countResult = $this->db()->select($countSql, $params);
            $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
            
            // Buscar produtos
            $sql = null;
            if ($hasFulltext) {
                $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
                               pi.image,
                               CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                        FROM {$this->table} p
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                        WHERE MATCH(p.name, p.description) AGAINST(:term IN BOOLEAN MODE) 
                        AND p.is_active = 1" . $availabilityFilter . "
                        ORDER BY p.is_tested DESC, p.name ASC
                        LIMIT :offset, :limit";
            } else {
                $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
                               pi.image,
                               CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                        FROM {$this->table} p
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                        WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1" . $availabilityFilter . "
                        ORDER BY p.is_tested DESC, p.name ASC
                        LIMIT :offset, :limit";
            }
            
            $params['offset'] = $offset;
            $params['limit'] = $limit;
            
            $items = $this->db()->select($sql, $params);
            
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
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
                          pi.image,
                          CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                   FROM {$this->table} p
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                   WHERE p.id != :product_id AND p.category_id = :category_id AND p.is_active = 1
                   ORDER BY p.is_tested DESC, p.id DESC
                   LIMIT :limit";
            
            // Obter produtos relacionados
            $result = $this->db()->select($sql, [
                'product_id' => $productId,
                'category_id' => $categoryId,
                'limit' => $limit * 2 // Buscar mais produtos para escolher aleatoriamente
            ]);
            
            // Selecionar aleatoriamente alguns dos produtos encontrados
            if (count($result) > $limit) {
                shuffle($result);
                $result = array_slice($result, 0, $limit);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos relacionados: " . $e->getMessage());
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
            $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock,
                           pi.image,
                           CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.is_customizable = 1 AND p.is_active = 1
                    ORDER BY p.is_tested DESC, p.created_at DESC
                    LIMIT :limit";
            
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos personalizáveis: " . $e->getMessage());
            return [];
        }
    }
}