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
            $sql = "SELECT p.*, pi.image, 
                           p.is_tested, 
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
            $sql = "SELECT p.*, pi.image,
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
            $sql = "SELECT p.*, pi.image,
                           'Sob Encomenda' as availability
                    FROM {$this->table} p
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                    WHERE (p.is_tested = 0 OR p.stock = 0) AND p.is_active = 1
                    ORDER BY p.created_at DESC
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
    public function paginate($page = 1, $limit = 10, $conditions = '1=1', $params = [], $availability = 'all', $orderBy = 'created_at DESC') {
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
            $sql = "SELECT p.*, pi.image,
                           p.is_tested, p.stock,
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
            
            // Contar total
            $countSql = "SELECT COUNT(*) as total 
                        FROM {$this->table} p 
                        WHERE p.category_id = :category_id AND p.is_active = 1" . $availabilityFilter;
            $countResult = $this->db()->select($countSql, $params);
            $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
            
            // Buscar produtos
            $sql = "SELECT p.*, pi.image,
                           p.is_tested, p.stock,
                           CASE WHEN p.is_tested =