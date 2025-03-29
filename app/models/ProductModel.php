<?php
/**
 * ProductModel - Modelo para produtos
 */
class ProductModel extends Model {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'short_description', 
        'price', 'sale_price', 'stock', 'weight', 'dimensions', 'sku',
        'is_featured', 'is_active', 'is_customizable'
    ];
    
    /**
     * Obtém produtos em destaque
     * 
     * @param int $limit Número máximo de produtos a retornar
     * @return array Lista de produtos
     */
    public function getFeatured($limit = 8) {
        try {
            $sql = "SELECT p.*, pi.image 
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.is_featured = 1 AND p.is_active = 1
                    ORDER BY p.created_at DESC
                    LIMIT :limit";
            
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos em destaque: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém produtos por categoria
     * 
     * @param int $categoryId ID da categoria
     * @param int $page Página atual
     * @param int $limit Produtos por página
     * @return array Produtos e informações de paginação
     */
    public function getByCategory($categoryId, $page = 1, $limit = 12) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Contar total
            $countSql = "SELECT COUNT(*) as total 
                        FROM {$this->table} 
                        WHERE category_id = :category_id AND is_active = 1";
            $countResult = $this->db()->select($countSql, ['category_id' => $categoryId]);
            $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
            
            // Buscar produtos
            $sql = "SELECT p.*, pi.image 
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.category_id = :category_id AND p.is_active = 1
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";
            
            $items = $this->db()->select($sql, [
                'category_id' => $categoryId,
                'offset' => $offset,
                'limit' => $limit
            ]);
            
            return [
                'items' => $items,
                'total' => $total,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => ceil($total / $limit)
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos por categoria: " . $e->getMessage());
            return [
                'items' => [],
                'total' => 0,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => 1
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
            $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                    FROM {$this->table} p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug AND p.is_active = 1";
            
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                return null;
            }
            
            $product = $result[0];
            
            // Buscar imagens
            try {
                $sql = "SELECT * FROM product_images WHERE product_id = :id ORDER BY is_main DESC, display_order ASC";
                $product['images'] = $this->db()->select($sql, ['id' => $product['id']]);
            } catch (Exception $e) {
                error_log("Erro ao buscar imagens do produto: " . $e->getMessage());
                $product['images'] = [];
            }
            
            // Buscar opções de personalização
            if (isset($product['is_customizable']) && $product['is_customizable']) {
                try {
                    $sql = "SELECT * FROM customization_options WHERE product_id = :id";
                    $product['customization_options'] = $this->db()->select($sql, ['id' => $product['id']]);
                } catch (Exception $e) {
                    error_log("Erro ao buscar opções de personalização: " . $e->getMessage());
                    $product['customization_options'] = [];
                }
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
     * @return array Produtos e informações de paginação
     */
    public function search($query, $page = 1, $limit = 12) {
        try {
            $offset = ($page - 1) * $limit;
            $searchTerm = "%{$query}%";
            
            // Contar total
            $countSql = "SELECT COUNT(*) as total 
                        FROM {$this->table} 
                        WHERE (name LIKE :term OR description LIKE :term) AND is_active = 1";
            $countResult = $this->db()->select($countSql, ['term' => $searchTerm]);
            $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
            
            // Buscar produtos
            $sql = "SELECT p.*, pi.image 
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1
                    GROUP BY p.id
                    ORDER BY p.name ASC
                    LIMIT :offset, :limit";
            
            $items = $this->db()->select($sql, [
                'term' => $searchTerm,
                'offset' => $offset,
                'limit' => $limit
            ]);
            
            return [
                'items' => $items,
                'total' => $total,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => ceil($total / $limit),
                'query' => $query
            ];
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            return [
                'items' => [],
                'total' => 0,
                'currentPage' => $page,
                'perPage' => $limit,
                'lastPage' => 1,
                'query' => $query
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
            // CORREÇÃO: Os nomes dos parâmetros na consulta e no array devem corresponder
            $sql = "SELECT p.*, pi.image 
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE p.id != :product_id AND p.category_id = :category_id AND p.is_active = 1
                    GROUP BY p.id
                    ORDER BY RAND()
                    LIMIT :limit";
            
            return $this->db()->select($sql, [
                'product_id' => $productId,
                'category_id' => $categoryId,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            error_log("Erro ao buscar produtos relacionados: " . $e->getMessage());
            return [];
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
}