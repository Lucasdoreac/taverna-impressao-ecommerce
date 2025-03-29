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
     * 
     * @return array Lista de categorias principais
     */
    public function getMainCategories() {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE parent_id IS NULL AND is_active = 1
                    ORDER BY display_order, name";
            
            return $this->db()->select($sql);
        } catch (Exception $e) {
            error_log("Erro ao buscar categorias principais: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter categoria por slug com subcategorias
     * 
     * @param string $slug Slug da categoria
     * @return array|null Dados da categoria ou null se não encontrada
     */
    public function getBySlug($slug) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND is_active = 1";
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                return null;
            }
            
            $category = $result[0];
            
            // Buscar subcategorias
            try {
                $sql = "SELECT * FROM {$this->table} 
                        WHERE parent_id = :id AND is_active = 1
                        ORDER BY display_order, name";
                $category['subcategories'] = $this->db()->select($sql, ['id' => $category['id']]);
            } catch (Exception $e) {
                error_log("Erro ao buscar subcategorias: " . $e->getMessage());
                $category['subcategories'] = [];
            }
            
            return $category;
        } catch (Exception $e) {
            error_log("Erro ao buscar categoria por slug: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obter subcategorias de uma categoria
     * 
     * @param int $parentId ID da categoria pai
     * @return array Lista de subcategorias
     */
    public function getSubcategories($parentId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE parent_id = :parent_id AND is_active = 1
                    ORDER BY display_order, name";
            
            return $this->db()->select($sql, ['parent_id' => $parentId]);
        } catch (Exception $e) {
            error_log("Erro ao buscar subcategorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter hierarquia completa de categorias
     * 
     * @return array Hierarquia de categorias
     */
    public function getFullHierarchy() {
        try {
            // Buscar categorias principais
            $mainCategories = $this->getMainCategories();
            
            // Buscar subcategorias para cada categoria principal
            foreach ($mainCategories as &$category) {
                $category['subcategories'] = $this->getSubcategories($category['id']);
            }
            
            return $mainCategories;
        } catch (Exception $e) {
            error_log("Erro ao buscar hierarquia de categorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter caminho/breadcrumb para uma categoria
     * 
     * @param int $categoryId ID da categoria
     * @return array Caminho de categorias
     */
    public function getBreadcrumb($categoryId) {
        try {
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
        } catch (Exception $e) {
            error_log("Erro ao buscar breadcrumb: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter categoria com produtos
     * 
     * @param string $slug Slug da categoria
     * @param int $page Página atual
     * @param int $limit Produtos por página
     * @return array|null Dados da categoria com produtos ou null se não encontrada
     */
    public function getCategoryWithProducts($slug, $page = 1, $limit = 12) {
        try {
            // Obter categoria
            $category = $this->getBySlug($slug);
            if (!$category) {
                return null;
            }
            
            // Obter IDs de todas as subcategorias (para incluir produtos das subcategorias)
            $categoryIds = [$category['id']];
            
            if (isset($category['subcategories']) && is_array($category['subcategories'])) {
                foreach ($category['subcategories'] as $subcategory) {
                    if (isset($subcategory['id'])) {
                        $categoryIds[] = $subcategory['id'];
                    }
                }
            }
            
            // Verificar se o array de IDs tem elementos
            if (empty($categoryIds)) {
                $category['products'] = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1
                ];
                return $category;
            }
            
            // CORREÇÃO: Em vez de concatenar os IDs na string SQL, vamos usar uma abordagem mais segura
            // criando um array de placeholders e usando parâmetros nomeados
            $placeholders = [];
            $params = [];
            
            foreach ($categoryIds as $index => $id) {
                $paramName = "category_id_" . $index;
                $placeholders[] = ":" . $paramName;
                $params[$paramName] = $id;
            }
            
            // Obter produtos da categoria e subcategorias
            try {
                $productModel = new ProductModel();
                
                // Construir a cláusula WHERE com placeholders
                $placeholdersStr = implode(',', $placeholders);
                $conditions = "category_id IN ({$placeholdersStr}) AND is_active = 1";
                
                // Adicionar parâmetros de paginação ao array de parâmetros
                $params['offset'] = ($page - 1) * $limit;
                $params['limit'] = $limit;
                
                // Contar total
                $countSql = "SELECT COUNT(*) as total FROM {$productModel->getTable()} WHERE {$conditions}";
                $countResult = $this->db()->select($countSql, $params);
                $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
                
                // Buscar produtos
                $sql = "SELECT p.*, pi.image 
                       FROM {$productModel->getTable()} p
                       LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                       WHERE {$conditions}
                       GROUP BY p.id
                       ORDER BY p.name ASC
                       LIMIT :offset, :limit";
                
                $items = $this->db()->select($sql, $params);
                
                $category['products'] = [
                    'items' => $items,
                    'total' => $total,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => ceil($total / $limit)
                ];
            } catch (Exception $e) {
                error_log("Erro ao buscar produtos da categoria: " . $e->getMessage());
                $category['products'] = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1
                ];
            }
            
            return $category;
        } catch (Exception $e) {
            error_log("Erro ao buscar categoria com produtos: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca categorias por termo
     * 
     * @param string $query Termo de busca
     * @return array Lista de categorias
     */
    public function search($query) {
        try {
            $searchTerm = "%{$query}%";
            
            $sql = "SELECT * FROM {$this->table} 
                    WHERE (name LIKE :term OR description LIKE :term) AND is_active = 1
                    ORDER BY display_order, name";
            
            return $this->db()->select($sql, ['term' => $searchTerm]);
        } catch (Exception $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
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