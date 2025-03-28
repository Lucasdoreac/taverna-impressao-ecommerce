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
     * Busca produtos em destaque
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
     * Busca produtos por categoria
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
     * Busca produto pelo slug
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
     * Busca por produtos
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
     * Conta o total de produtos
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db()->select($sql);
        return $result[0]['total'];
    }
    
    /**
     * Conta produtos por status
     */
    public function countByStatus($isActive = true) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_active = :is_active";
        $result = $this->db()->select($sql, ['is_active' => $isActive ? 1 : 0]);
        return $result[0]['total'];
    }
    
    /**
     * Conta produtos sem estoque
     */
    public function countOutOfStock() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE stock = 0 AND is_active = 1";
        $result = $this->db()->select($sql);
        return $result[0]['total'];
    }
    
    /**
     * Conta produtos com estoque baixo
     */
    public function countLowStock($threshold = 5) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE stock > 0 AND stock <= :threshold AND is_active = 1";
        $result = $this->db()->select($sql, ['threshold' => $threshold]);
        return $result[0]['total'];
    }
    
    /**
     * Busca produtos mais vendidos
     */
    public function getTopSellingProducts($limit = 5) {
        $sql = "SELECT 
                    p.id, 
                    p.name, 
                    p.slug,
                    p.price,
                    p.sale_price,
                    p.stock,
                    c.name as category_name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price) as total_sales,
                    pi.image
                FROM order_items oi
                JOIN {$this->table} p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE o.payment_status = 'paid'
                GROUP BY p.id
                ORDER BY total_quantity DESC
                LIMIT {$limit}";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Gera relatório de vendas de produtos
     */
    public function getProductsSalesReport($startDate, $endDate) {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.stock,
                    c.name as category_name,
                    SUM(oi.quantity) as quantity_sold,
                    SUM(oi.quantity * oi.price) as total_sales
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN :start_date AND :end_date
                    AND o.payment_status = 'paid'
                GROUP BY p.id
                ORDER BY quantity_sold DESC";
        
        return $this->db()->select($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59'
        ]);
    }
    
    /**
     * Adiciona uma imagem ao produto
     */
    public function addImage($productId, $image, $isMain = false) {
        // Se for imagem principal, resetar todas as outras
        if ($isMain) {
            $sql = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
            $this->db()->query($sql, ['product_id' => $productId]);
        }
        
        // Obter a próxima ordem de exibição
        $sql = "SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = :product_id";
        $result = $this->db()->select($sql, ['product_id' => $productId]);
        $displayOrder = ($result && isset($result[0]['max_order'])) ? ($result[0]['max_order'] + 1) : 1;
        
        // Adicionar imagem
        $sql = "INSERT INTO product_images (product_id, image, is_main, display_order)
                VALUES (:product_id, :image, :is_main, :display_order)";
        
        $this->db()->query($sql, [
            'product_id' => $productId,
            'image' => $image,
            'is_main' => $isMain ? 1 : 0,
            'display_order' => $displayOrder
        ]);
        
        return true;
    }
    
    /**
     * Remove uma imagem do produto
     */
    public function removeImage($imageId) {
        // Obter informações da imagem antes de excluir
        $sql = "SELECT * FROM product_images WHERE id = :id";
        $image = $this->db()->select($sql, ['id' => $imageId]);
        
        if (!$image) {
            return false;
        }
        
        // Excluir imagem
        $sql = "DELETE FROM product_images WHERE id = :id";
        $this->db()->query($sql, ['id' => $imageId]);
        
        // Se era a imagem principal, definir outra como principal
        if ($image[0]['is_main']) {
            $sql = "UPDATE product_images SET is_main = 1 
                    WHERE product_id = :product_id 
                    ORDER BY display_order ASC 
                    LIMIT 1";
            $this->db()->query($sql, ['product_id' => $image[0]['product_id']]);
        }
        
        return true;
    }
    
    /**
     * Adiciona uma opção de personalização ao produto
     */
    public function addCustomizationOption($productId, $data) {
        $sql = "INSERT INTO customization_options (product_id, name, description, type, required, options)
                VALUES (:product_id, :name, :description, :type, :required, :options)";
        
        $this->db()->query($sql, [
            'product_id' => $productId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'required' => $data['required'] ? 1 : 0,
            'options' => $data['options'] ?? null
        ]);
        
        return $this->db()->getConnection()->lastInsertId();
    }
    
    /**
     * Remove uma opção de personalização
     */
    public function removeCustomizationOption($optionId) {
        $sql = "DELETE FROM customization_options WHERE id = :id";
        $this->db()->query($sql, ['id' => $optionId]);
        return true;
    }
}