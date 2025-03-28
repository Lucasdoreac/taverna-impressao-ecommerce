<?php
/**
 * ProductModel - Modelo para produtos
 */
class ProductModel extends Model {
    protected $table = 'products';
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'short_description', 
        'price', 'sale_price', 'stock', 'weight', 'dimensions', 'sku',
        'is_featured', 'is_active', 'is_customizable'
    ];
    
    /**
     * Obter produtos em destaque
     */
    public function getFeatured($limit = 8) {
        $sql = "SELECT p.*, pi.image 
                FROM {$this->table} p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.is_featured = 1 AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT {$limit}";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Obter produtos por categoria com paginação
     */
    public function getByCategory($categoryId, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total 
                    FROM {$this->table} 
                    WHERE category_id = :category_id AND is_active = 1";
        $countResult = $this->db()->select($countSql, ['category_id' => $categoryId]);
        $total = $countResult[0]['total'];
        
        // Buscar produtos
        $sql = "SELECT p.*, pi.image 
                FROM {$this->table} p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.category_id = :category_id AND p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT {$offset}, {$limit}";
        
        $items = $this->db()->select($sql, ['category_id' => $categoryId]);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit)
        ];
    }
    
    /**
     * Obter produto por slug com imagens e opções de personalização
     */
    public function getBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM {$this->table} p
                JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug AND p.is_active = 1";
        
        $result = $this->db()->select($sql, ['slug' => $slug]);
        
        if (!$result) {
            return null;
        }
        
        $product = $result[0];
        
        // Buscar imagens
        $sql = "SELECT * FROM product_images WHERE product_id = :id ORDER BY is_main DESC, display_order ASC";
        $product['images'] = $this->db()->select($sql, ['id' => $product['id']]);
        
        // Buscar opções de personalização
        if ($product['is_customizable']) {
            $sql = "SELECT * FROM customization_options WHERE product_id = :id";
            $product['customization_options'] = $this->db()->select($sql, ['id' => $product['id']]);
        }
        
        return $product;
    }
    
    /**
     * Buscar produtos por termo de pesquisa
     */
    public function search($query, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$query}%";
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total 
                    FROM {$this->table} 
                    WHERE (name LIKE :term OR description LIKE :term) AND is_active = 1";
        $countResult = $this->db()->select($countSql, ['term' => $searchTerm]);
        $total = $countResult[0]['total'];
        
        // Buscar produtos
        $sql = "SELECT p.*, pi.image 
                FROM {$this->table} p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1
                ORDER BY p.name ASC
                LIMIT {$offset}, {$limit}";
        
        $items = $this->db()->select($sql, ['term' => $searchTerm]);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit),
            'query' => $query
        ];
    }
    
    /**
     * Obter produtos relacionados
     */
    public function getRelated($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.*, pi.image 
                FROM {$this->table} p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.id != :product_id AND p.category_id = :category_id AND p.is_active = 1
                ORDER BY RAND()
                LIMIT {$limit}";
        
        return $this->db()->select($sql, [
            'product_id' => $productId,
            'category_id' => $categoryId
        ]);
    }
    
    /**
     * Atualizar estoque após compra
     */
    public function updateStock($productId, $quantity) {
        $sql = "UPDATE {$this->table} SET stock = stock - :quantity WHERE id = :id";
        $this->db()->query($sql, [
            'id' => $productId,
            'quantity' => $quantity
        ]);
    }
}