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
     * Buscar um produto pelo slug
     */
    public function findBySlug($slug) {
        return $this->findBy('slug', $slug);
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
    
    /**
     * Buscar produtos com filtros para o painel administrativo
     */
    public function getWithFilters($filters = [], $page = 1, $limit = 20) {
        $where = '1=1';
        $params = [];
        
        // Filtro por nome
        if (!empty($filters['name'])) {
            $where .= ' AND name LIKE :name';
            $params['name'] = '%' . $filters['name'] . '%';
        }
        
        // Filtro por categoria
        if (!empty($filters['category_id'])) {
            $where .= ' AND category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        
        // Filtro por status ativo
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where .= ' AND is_active = :is_active';
            $params['is_active'] = $filters['is_active'];
        }
        
        // Filtro por status de destaque
        if (isset($filters['is_featured']) && $filters['is_featured'] !== '') {
            $where .= ' AND is_featured = :is_featured';
            $params['is_featured'] = $filters['is_featured'];
        }
        
        // Filtro por status de personalização
        if (isset($filters['is_customizable']) && $filters['is_customizable'] !== '') {
            $where .= ' AND is_customizable = :is_customizable';
            $params['is_customizable'] = $filters['is_customizable'];
        }
        
        // Buscar com paginação
        $offset = ($page - 1) * $limit;
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $countResult = $this->db()->select($countSql, $params);
        $total = $countResult[0]['total'];
        
        // Buscar produtos
        $sql = "SELECT p.*, c.name as category_name, 
                    (SELECT image FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as main_image 
                FROM {$this->table} p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$where} 
                ORDER BY p.created_at DESC
                LIMIT {$offset}, {$limit}";
        
        $items = $this->db()->select($sql, $params);
        
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
     * Obtém as imagens de um produto
     */
    public function getImages($productId) {
        $sql = "SELECT * FROM product_images 
                WHERE product_id = :product_id 
                ORDER BY is_main DESC, display_order ASC";
        
        return $this->db()->select($sql, ['product_id' => $productId]);
    }
    
    /**
     * Adiciona uma imagem a um produto
     */
    public function addImage($productId, $image, $isMain = false, $displayOrder = 0) {
        $sql = "INSERT INTO product_images (product_id, image, is_main, display_order) 
                VALUES (:product_id, :image, :is_main, :display_order)";
        
        return $this->db()->query($sql, [
            'product_id' => $productId,
            'image' => $image,
            'is_main' => $isMain ? 1 : 0,
            'display_order' => $displayOrder
        ]);
    }
    
    /**
     * Define a imagem principal de um produto
     */
    public function setMainImage($productId, $imageId) {
        // Primeiro, remover o status de principal de todas as imagens do produto
        $sql = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
        $this->db()->query($sql, ['product_id' => $productId]);
        
        // Depois, definir a nova imagem principal
        $sql = "UPDATE product_images SET is_main = 1 WHERE id = :id AND product_id = :product_id";
        return $this->db()->query($sql, [
            'id' => $imageId,
            'product_id' => $productId
        ]);
    }
    
    /**
     * Exclui uma imagem de um produto
     */
    public function deleteImage($imageId) {
        $sql = "DELETE FROM product_images WHERE id = :id";
        return $this->db()->query($sql, ['id' => $imageId]);
    }
    
    /**
     * Verifica se um produto já tem uma imagem principal
     */
    public function hasMainImage($productId) {
        $sql = "SELECT COUNT(*) as count FROM product_images 
                WHERE product_id = :product_id AND is_main = 1";
        
        $result = $this->db()->select($sql, ['product_id' => $productId]);
        return $result[0]['count'] > 0;
    }
    
    /**
     * Obtém as opções de personalização de um produto
     */
    public function getCustomizationOptions($productId) {
        $sql = "SELECT * FROM customization_options 
                WHERE product_id = :product_id 
                ORDER BY id ASC";
        
        return $this->db()->select($sql, ['product_id' => $productId]);
    }
    
    /**
     * Adiciona uma opção de personalização a um produto
     */
    public function addCustomizationOption($productId, $option) {
        $sql = "INSERT INTO customization_options 
                (product_id, name, description, type, required, options) 
                VALUES 
                (:product_id, :name, :description, :type, :required, :options)";
        
        return $this->db()->query($sql, [
            'product_id' => $productId,
            'name' => $option['name'],
            'description' => $option['description'],
            'type' => $option['type'],
            'required' => $option['required'],
            'options' => $option['options']
        ]);
    }
    
    /**
     * Exclui todas as opções de personalização de um produto
     */
    public function deleteCustomizationOptions($productId) {
        $sql = "DELETE FROM customization_options WHERE product_id = :product_id";
        return $this->db()->query($sql, ['product_id' => $productId]);
    }
}
