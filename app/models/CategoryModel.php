<?php
/**
 * CategoryModel - Modelo para categorias
 */
class CategoryModel extends Model {
    protected $table = 'categories';
    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image', 
        'is_active', 'display_order'
    ];
    
    /**
     * Obter todas as categorias principais (sem parent)
     */
    public function getMainCategories() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id IS NULL AND is_active = 1
                ORDER BY display_order, name";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Obter categoria por slug com subcategorias
     */
    public function getBySlug($slug) {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND is_active = 1";
        $result = $this->db()->select($sql, ['slug' => $slug]);
        
        if (!$result) {
            return null;
        }
        
        $category = $result[0];
        
        // Buscar subcategorias
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id = :id AND is_active = 1
                ORDER BY display_order, name";
        $category['subcategories'] = $this->db()->select($sql, ['id' => $category['id']]);
        
        return $category;
    }
    
    /**
     * Obter subcategorias de uma categoria
     */
    public function getSubcategories($parentId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE parent_id = :parent_id AND is_active = 1
                ORDER BY display_order, name";
        
        return $this->db()->select($sql, ['parent_id' => $parentId]);
    }
    
    /**
     * Obter hierarquia completa de categorias
     */
    public function getFullHierarchy() {
        // Buscar categorias principais
        $mainCategories = $this->getMainCategories();
        
        // Buscar subcategorias para cada categoria principal
        foreach ($mainCategories as &$category) {
            $category['subcategories'] = $this->getSubcategories($category['id']);
        }
        
        return $mainCategories;
    }
    
    /**
     * Obter caminho/breadcrumb para uma categoria
     */
    public function getBreadcrumb($categoryId) {
        $category = $this->find($categoryId);
        if (!$category) {
            return [];
        }
        
        $breadcrumb = [$category];
        
        // Se tem parent, buscar recursivamente
        if (!empty($category['parent_id'])) {
            $parentBreadcrumb = $this->getBreadcrumb($category['parent_id']);
            $breadcrumb = array_merge($parentBreadcrumb, $breadcrumb);
        }
        
        return $breadcrumb;
    }
    
    /**
     * Obter categoria com produtos
     */
    public function getCategoryWithProducts($slug, $page = 1, $limit = 12) {
        // Obter categoria
        $category = $this->getBySlug($slug);
        if (!$category) {
            return null;
        }
        
        // Obter IDs de todas as subcategorias (para incluir produtos das subcategorias)
        $categoryIds = [$category['id']];
        foreach ($category['subcategories'] as $subcategory) {
            $categoryIds[] = $subcategory['id'];
        }
        
        // Converter array de IDs para string para usar na query
        $categoryIdsStr = implode(',', $categoryIds);
        
        // Obter produtos da categoria e subcategorias
        $productModel = new ProductModel();
        $conditions = "category_id IN ({$categoryIdsStr}) AND is_active = 1";
        $products = $productModel->paginate($page, $limit, $conditions);
        
        $category['products'] = $products;
        
        return $category;
    }
}