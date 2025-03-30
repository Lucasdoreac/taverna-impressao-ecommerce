<?php
/**
 * CategoryModel - Modelo para categorias
 */
class CategoryModel extends Model {
    protected $table = 'categories';
    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image', 
        'is_active', 'display_order', 'level', 'left_value', 'right_value'
    ];
    
    /**
     * Obter todas as categorias principais (sem parent)
     * 
     * @param bool $withSubcategories Se deve incluir subcategorias
     * @return array Lista de categorias principais
     */
    public function getMainCategories($withSubcategories = false) {
        try {
            // Selecionar apenas as colunas necessárias em vez de *
            $sql = "SELECT id, name, slug, description, image, display_order, left_value, right_value 
                    FROM {$this->table} 
                    WHERE parent_id IS NULL AND is_active = 1
                    ORDER BY display_order, name";
            
            $categories = $this->db()->select($sql);
            
            if ($withSubcategories && !empty($categories)) {
                // Evitar N+1 consultas carregando todas as subcategorias de uma vez
                $categoryIds = array_column($categories, 'id');
                $subcategories = $this->getAllSubcategoriesByParentIds($categoryIds);
                
                foreach ($categories as &$category) {
                    $category['subcategories'] = isset($subcategories[$category['id']]) 
                        ? $subcategories[$category['id']] 
                        : [];
                }
            }
            
            return $categories;
        } catch (Exception $e) {
            error_log("Erro ao buscar categorias principais: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter categoria por slug com subcategorias
     * 
     * @param string $slug Slug da categoria
     * @param bool $recursiveSubcategories Se deve incluir subcategorias recursivamente
     * @return array|null Dados da categoria ou null se não encontrada
     */
    public function getBySlug($slug, $recursiveSubcategories = false) {
        try {
            // Selecionar apenas as colunas necessárias
            $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value, is_active, display_order 
                    FROM {$this->table} 
                    WHERE slug = :slug AND is_active = 1";
            
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                return null;
            }
            
            $category = $result[0];
            
            // Buscar subcategorias
            try {
                if ($recursiveSubcategories) {
                    // Usar método otimizado em vez de recursivo
                    $category['subcategories'] = $this->getSubcategoriesAll($category['id']);
                } else {
                    // Buscar apenas subcategorias diretas
                    $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value, display_order 
                            FROM {$this->table} 
                            WHERE parent_id = :id AND is_active = 1
                            ORDER BY display_order, name";
                    $category['subcategories'] = $this->db()->select($sql, ['id' => $category['id']]);
                }
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
     * @param bool $recursive Se deve buscar subcategorias de forma recursiva
     * @return array Lista de subcategorias
     */
    public function getSubcategories($parentId, $recursive = false) {
        try {
            if ($recursive) {
                return $this->getSubcategoriesAll($parentId);
            }
            
            $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value, display_order 
                    FROM {$this->table} 
                    WHERE parent_id = :parent_id AND is_active = 1
                    ORDER BY display_order, name";
            
            return $this->db()->select($sql, ['parent_id' => $parentId]);
        } catch (Exception $e) {
            error_log("Erro ao buscar subcategorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter subcategorias de forma recursiva (método original mantido para compatibilidade)
     * 
     * @param int $parentId ID da categoria pai
     * @return array Lista de subcategorias com suas subcategorias
     * @deprecated Use getSubcategoriesAll em vez deste método
     */
    public function getSubcategoriesRecursive($parentId) {
        try {
            $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value, display_order 
                    FROM {$this->table} 
                    WHERE parent_id = :parent_id AND is_active = 1
                    ORDER BY display_order, name";
            
            $subcategories = $this->db()->select($sql, ['parent_id' => $parentId]);
            
            foreach ($subcategories as &$subcategory) {
                $subcategory['subcategories'] = $this->getSubcategoriesRecursive($subcategory['id']);
            }
            
            return $subcategories;
        } catch (Exception $e) {
            error_log("Erro ao buscar subcategorias recursivas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as subcategorias de uma categoria pai (método otimizado não recursivo)
     * 
     * @param int $parentId ID da categoria pai
     * @param bool $useNestedSets Se deve usar algoritmo Nested Sets (mais eficiente)
     * @return array Lista de subcategorias organizadas em hierarquia
     */
    public function getSubcategoriesAll($parentId, $useNestedSets = true) {
        try {
            if ($useNestedSets) {
                // Verificar se a categoria existe e obter seus valores left/right
                $parent = $this->find($parentId);
                if (!$parent || !isset($parent['left_value']) || !isset($parent['right_value'])) {
                    $useNestedSets = false;
                }
            }
            
            if ($useNestedSets) {
                // Método eficiente usando Nested Sets - uma única consulta
                $sql = "SELECT child.* 
                        FROM {$this->table} parent
                        JOIN {$this->table} child ON child.left_value > parent.left_value 
                                               AND child.right_value < parent.right_value
                        WHERE parent.id = :parent_id AND child.is_active = 1
                        ORDER BY child.left_value";
                
                $allSubcategories = $this->db()->select($sql, ['parent_id' => $parentId]);
                
                // Organizar em hierarquia
                return $this->buildHierarchy($allSubcategories);
            } else {
                // Método alternativo usando estrutura de adjacência
                // Ainda melhor que chamar recursivamente várias consultas SQL
                $sql = "WITH RECURSIVE category_tree AS (
                          SELECT * FROM {$this->table} WHERE id = :parent_id AND is_active = 1
                          UNION ALL
                          SELECT c.* FROM {$this->table} c
                          JOIN category_tree ct ON c.parent_id = ct.id
                          WHERE c.is_active = 1
                        )
                        SELECT * FROM category_tree WHERE id != :parent_id
                        ORDER BY display_order, name";
                
                // Se o banco não suportar CTE, voltar ao método antigo
                try {
                    $allSubcategories = $this->db()->select($sql, ['parent_id' => $parentId]);
                    return $this->buildHierarchy($allSubcategories);
                } catch (Exception $e) {
                    error_log("Banco de dados não suporta CTE. Usando método recursivo: " . $e->getMessage());
                    return $this->getSubcategoriesRecursive($parentId);
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar subcategorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Método auxiliar para carregar subcategorias em massa a partir de múltiplos pais
     * 
     * @param array $parentIds IDs das categorias pai
     * @return array Subcategorias agrupadas por parent_id
     */
    private function getAllSubcategoriesByParentIds(array $parentIds) {
        if (empty($parentIds)) {
            return [];
        }
        
        // Criar placeholders para os IDs
        $placeholders = [];
        $params = [];
        
        foreach ($parentIds as $index => $id) {
            $paramName = "id{$index}";
            $placeholders[] = ":{$paramName}";
            $params[$paramName] = $id;
        }
        
        $placeholdersStr = implode(',', $placeholders);
        
        // Carregando todas as subcategorias diretas de uma vez
        $sql = "SELECT c.id, c.name, c.slug, c.description, c.image, c.parent_id, 
                       c.left_value, c.right_value, c.display_order
                FROM {$this->table} c
                WHERE c.parent_id IN ({$placeholdersStr}) AND c.is_active = 1
                ORDER BY c.display_order, c.name";
        
        $result = $this->db()->select($sql, $params);
        
        // Organizar por parent_id
        $grouped = [];
        foreach ($result as $row) {
            $parentId = $row['parent_id'];
            if (!isset($grouped[$parentId])) {
                $grouped[$parentId] = [];
            }
            $grouped[$parentId][] = $row;
        }
        
        return $grouped;
    }
    
    /**
     * Organiza uma lista plana de categorias em uma estrutura hierárquica
     * 
     * @param array $flatList Lista plana de categorias
     * @return array Categorias organizadas em hierarquia
     */
    private function buildHierarchy($flatList) {
        // Indexar categorias por ID para referência rápida
        $indexed = [];
        foreach ($flatList as $category) {
            $category['subcategories'] = [];
            $indexed[$category['id']] = $category;
        }
        
        // Organizar em hierarquia
        $hierarchy = [];
        foreach ($indexed as $id => $category) {
            if (isset($category['parent_id']) && isset($indexed[$category['parent_id']])) {
                // Se o pai está na lista, adicionar como subcategoria
                $indexed[$category['parent_id']]['subcategories'][] = &$indexed[$id];
            } else {
                // Se o pai não está na lista, adicionar ao nível principal
                $hierarchy[] = &$indexed[$id];
            }
        }
        
        return $hierarchy;
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
            
            // Usar Nested Sets para buscar todas as categorias exceto os nós raiz em uma única consulta
            $sql = "SELECT id, name, slug, description, image, parent_id, 
                           left_value, right_value, display_order
                    FROM {$this->table}
                    WHERE parent_id IS NOT NULL AND is_active = 1
                    ORDER BY left_value";
            
            $allSubcategories = $this->db()->select($sql);
            
            // Organizar subcategorias por parent_id
            $subcategoriesByParent = [];
            foreach ($allSubcategories as $category) {
                $parentId = $category['parent_id'];
                if (!isset($subcategoriesByParent[$parentId])) {
                    $subcategoriesByParent[$parentId] = [];
                }
                $subcategoriesByParent[$parentId][] = $category;
            }
            
            // Associar subcategorias às categorias principais
            foreach ($mainCategories as &$category) {
                $category['subcategories'] = $this->buildHierarchyRecursive($category['id'], $subcategoriesByParent);
            }
            
            return $mainCategories;
        } catch (Exception $e) {
            error_log("Erro ao buscar hierarquia de categorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Constrói hierarquia recursivamente a partir de um mapa de subcategorias
     * 
     * @param int $parentId ID da categoria pai
     * @param array $subcategoriesByParent Mapa de subcategorias por parent_id
     * @return array Subcategorias organizadas em hierarquia
     */
    private function buildHierarchyRecursive($parentId, &$subcategoriesByParent) {
        if (!isset($subcategoriesByParent[$parentId])) {
            return [];
        }
        
        $result = $subcategoriesByParent[$parentId];
        
        foreach ($result as &$category) {
            $category['subcategories'] = $this->buildHierarchyRecursive($category['id'], $subcategoriesByParent);
        }
        
        return $result;
    }
    
    /**
     * Obter hierarquia de categorias em formato plano com nível de indentação
     * 
     * @return array Lista de categorias com nível de indentação
     */
    public function getFlatHierarchy() {
        try {
            // Usar Nested Sets para obter a hierarquia de forma eficiente em uma única consulta
            // Evitar o uso de funções SQL como FIND_IN_SET e GetCategoryAncestors
            $sql = "SELECT c.id, c.name, c.slug, c.description, c.image, c.parent_id, 
                          c.left_value, c.right_value, c.display_order,
                          (SELECT COUNT(*) 
                           FROM {$this->table} p 
                           WHERE c.left_value > p.left_value AND c.left_value < p.right_value) as depth
                   FROM {$this->table} c
                   WHERE c.is_active = 1
                   ORDER BY c.left_value";
            
            $categories = $this->db()->select($sql);
            
            // Se não tiver left_value e right_value, usar o método alternativo
            if (empty($categories) || !isset($categories[0]['left_value'])) {
                return $this->getFlatHierarchyViaPhp();
            }
            
            return $categories;
        } catch (Exception $e) {
            error_log("Erro ao buscar hierarquia plana via SQL: " . $e->getMessage());
            error_log("Tentando método alternativo via PHP");
            return $this->getFlatHierarchyViaPhp();
        }
    }
    
    /**
     * Obter hierarquia de categorias em formato plano via PHP (fallback)
     * 
     * @return array Lista de categorias com nível de indentação
     */
    private function getFlatHierarchyViaPhp() {
        try {
            $result = [];
            $mainCategories = $this->getMainCategories();
            
            $this->buildFlatHierarchy($result, $mainCategories, 0);
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar hierarquia plana via PHP: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Constrói a hierarquia plana recursivamente
     * 
     * @param array &$result Array para armazenar o resultado
     * @param array $categories Categorias a processar
     * @param int $depth Profundidade atual
     */
    private function buildFlatHierarchy(&$result, $categories, $depth) {
        foreach ($categories as $category) {
            $category['depth'] = $depth;
            $result[] = $category;
            
            // Obter subcategorias
            $subcategories = $this->getSubcategories($category['id']);
            
            if (!empty($subcategories)) {
                $this->buildFlatHierarchy($result, $subcategories, $depth + 1);
            }
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
            // Tentar usar o algoritmo de Nested Sets primeiro (mais eficiente)
            $sql = "SELECT parent.id, parent.name, parent.slug, parent.description, parent.image, 
                           parent.parent_id, parent.left_value, parent.right_value
                    FROM {$this->table} node, {$this->table} parent 
                    WHERE node.left_value BETWEEN parent.left_value AND parent.right_value 
                    AND node.id = :id 
                    ORDER BY parent.left_value";
            
            $result = $this->db()->select($sql, ['id' => $categoryId]);
            
            // Se não tivermos resultados ou não estiver usando Nested Sets,
            // fallback para o método recursivo
            if (empty($result)) {
                return $this->getBreadcrumbRecursive($categoryId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar breadcrumb via Nested Sets: " . $e->getMessage());
            return $this->getBreadcrumbRecursive($categoryId);
        }
    }
    
    /**
     * Obter caminho/breadcrumb recursivamente (método alternativo)
     * 
     * @param int $categoryId ID da categoria
     * @return array Caminho de categorias
     */
    private function getBreadcrumbRecursive($categoryId) {
        try {
            $category = $this->find($categoryId);
            if (!$category) {
                return [];
            }
            
            $breadcrumb = [$category];
            
            // Se tem parent, buscar recursivamente
            if (!empty($category['parent_id'])) {
                $parentBreadcrumb = $this->getBreadcrumbRecursive($category['parent_id']);
                $breadcrumb = array_merge($parentBreadcrumb, $breadcrumb);
            }
            
            return $breadcrumb;
        } catch (Exception $e) {
            error_log("Erro ao buscar breadcrumb recursivo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter categoria com produtos
     * 
     * @param string $slug Slug da categoria
     * @param int $page Página atual
     * @param int $limit Produtos por página
     * @param bool $includeSubcategories Se deve incluir produtos das subcategorias
     * @param string $orderBy Campo e direção de ordenação (ex: "price ASC")
     * @param array $filters Filtros adicionais (ex: ['price_min' => 10, 'price_max' => 100])
     * @return array|null Dados da categoria com produtos ou null se não encontrada
     */
    public function getCategoryWithProducts($slug, $page = 1, $limit = 12, $includeSubcategories = true, $orderBy = "p.is_tested DESC, p.name ASC", $filters = []) {
        try {
            // Obter categoria
            $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value 
                    FROM {$this->table} WHERE slug = :slug AND is_active = 1";
                    
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                return null;
            }
            
            $category = $result[0];
            
            // Obter IDs de todas as subcategorias (para incluir produtos das subcategorias)
            $categoryIds = [$category['id']];
            
            if ($includeSubcategories && isset($category['left_value']) && isset($category['right_value'])) {
                // Usar Nested Sets para obter todas as subcategorias de forma eficiente
                $sql = "SELECT id FROM {$this->table} 
                        WHERE left_value > :left_value AND right_value < :right_value AND is_active = 1";
                
                $subcategoryRows = $this->db()->select($sql, [
                    'left_value' => $category['left_value'],
                    'right_value' => $category['right_value']
                ]);
                
                if (!empty($subcategoryRows)) {
                    foreach ($subcategoryRows as $row) {
                        $categoryIds[] = $row['id'];
                    }
                }
            } elseif ($includeSubcategories && isset($category['subcategories']) && is_array($category['subcategories'])) {
                // Método alternativo: obter IDs de subcategorias recursivamente
                $this->collectSubcategoryIds($category['subcategories'], $categoryIds);
            } elseif ($includeSubcategories) {
                // Se não temos subcategorias ou valores nested sets, tentar obtê-las diretamente
                $subcategories = $this->getSubcategoriesAll($category['id']);
                $this->collectAllSubcategoryIds($subcategories, $categoryIds);
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
            
            // Criar um array de placeholders e parâmetros para os IDs de categoria
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
                
                // Construir a cláusula WHERE com placeholders para categorias
                $placeholdersStr = implode(',', $placeholders);
                $conditions = "category_id IN ({$placeholdersStr}) AND is_active = 1";
                
                // Adicionar condições baseadas nos filtros
                if (!empty($filters)) {
                    // Filtro de preço mínimo
                    if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
                        $conditions .= " AND (
                            (p.sale_price > 0 AND p.sale_price >= :price_min) OR
                            (p.sale_price = 0 AND p.price >= :price_min)
                        )";
                        $params['price_min'] = $filters['price_min'];
                    }
                    
                    // Filtro de preço máximo
                    if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
                        $conditions .= " AND (
                            (p.sale_price > 0 AND p.sale_price <= :price_max) OR
                            (p.sale_price = 0 AND p.price <= :price_max)
                        )";
                        $params['price_max'] = $filters['price_max'];
                    }
                    
                    // Filtro de disponibilidade
                    if (isset($filters['availability'])) {
                        if ($filters['availability'] === 'in_stock') {
                            $conditions .= " AND p.is_tested = 1 AND p.stock > 0";
                        } elseif ($filters['availability'] === 'custom_order') {
                            $conditions .= " AND (p.is_tested = 0 OR p.stock = 0)";
                        }
                    }
                    
                    // Filtro para produtos personalizáveis
                    if (isset($filters['customizable']) && $filters['customizable']) {
                        $conditions .= " AND p.is_customizable = 1";
                    }
                    
                    // Filtro para produtos em oferta
                    if (isset($filters['on_sale']) && $filters['on_sale']) {
                        $conditions .= " AND p.sale_price > 0 AND p.sale_price < p.price";
                    }
                }
                
                // Adicionar parâmetros de paginação ao array de parâmetros
                $params['offset'] = ($page - 1) * $limit;
                $params['limit'] = $limit;
                
                // Contar total
                $countSql = "SELECT COUNT(*) as total FROM {$productModel->getTable()} p WHERE {$conditions}";
                $countResult = $this->db()->select($countSql, $params);
                $total = isset($countResult[0]['total']) ? $countResult[0]['total'] : 0;
                
                // Validar e processar a ordenação
                $validOrderColumns = [
                    'name' => 'p.name',
                    'price' => 'CASE WHEN p.sale_price > 0 THEN p.sale_price ELSE p.price END',
                    'newest' => 'p.created_at',
                    'availability' => 'p.is_tested'
                ];
                
                $orderByParts = explode(' ', $orderBy);
                $orderColumn = $orderByParts[0] ?? 'p.is_tested';
                $orderDirection = strtoupper($orderByParts[1] ?? 'DESC');
                
                // Verificar se a direção de ordenação é válida
                if (!in_array($orderDirection, ['ASC', 'DESC'])) {
                    $orderDirection = 'DESC';
                }
                
                // Verificar se a coluna de ordenação está na lista de colunas válidas
                if (isset($validOrderColumns[$orderColumn])) {
                    $orderSql = "{$validOrderColumns[$orderColumn]} {$orderDirection}";
                } elseif (strpos($orderColumn, 'p.') === 0) {
                    // Se for uma coluna direta da tabela de produtos
                    $orderSql = "{$orderColumn} {$orderDirection}";
                } else {
                    // Ordenação padrão caso não seja válida
                    $orderSql = "p.is_tested DESC, p.name ASC";
                }
                
                // Buscar produtos com colunas selecionadas explicitamente
                $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, 
                               p.short_description, p.category_id, pi.image,
                               CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' 
                                    ELSE 'Sob Encomenda' END as availability
                       FROM {$productModel->getTable()} p
                       LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                       WHERE {$conditions}
                       GROUP BY p.id
                       ORDER BY {$orderSql}
                       LIMIT :offset, :limit";
                
                $items = $this->db()->select($sql, $params);
                
                $category['products'] = [
                    'items' => $items,
                    'total' => $total,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => ceil($total / $limit),
                    'orderBy' => $orderSql,
                    'filters' => $filters
                ];
            } catch (Exception $e) {
                error_log("Erro ao buscar produtos da categoria: " . $e->getMessage());
                $category['products'] = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1,
                    'orderBy' => $orderBy,
                    'filters' => $filters
                ];
            }
            
            return $category;
        } catch (Exception $e) {
            error_log("Erro ao buscar categoria com produtos: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Coleta IDs de subcategorias recursivamente
     * 
     * @param array $subcategories Lista de subcategorias
     * @param array &$categoryIds Array para armazenar os IDs
     */
    private function collectSubcategoryIds($subcategories, &$categoryIds) {
        foreach ($subcategories as $subcategory) {
            if (isset($subcategory['id'])) {
                $categoryIds[] = $subcategory['id'];
            }
            
            if (isset($subcategory['subcategories']) && is_array($subcategory['subcategories'])) {
                $this->collectSubcategoryIds($subcategory['subcategories'], $categoryIds);
            }
        }
    }
    
    /**
     * Coleta todos os IDs de subcategorias a partir de um array hierárquico
     * 
     * @param array $subcategories Lista hierárquica de subcategorias
     * @param array &$ids Array para armazenar os IDs
     */
    private function collectAllSubcategoryIds($subcategories, &$ids) {
        if (!is_array($subcategories)) {
            return;
        }
        
        foreach ($subcategories as $subcategory) {
            if (isset($subcategory['id'])) {
                $ids[] = $subcategory['id'];
            }
            
            if (isset($subcategory['subcategories'])) {
                $this->collectAllSubcategoryIds($subcategory['subcategories'], $ids);
            }
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
            
            $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value 
                    FROM {$this->table} 
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
    
    /**
     * Reorganiza uma categoria dentro da árvore
     * 
     * @param int $categoryId ID da categoria a ser movida
     * @param int|null $newParentId ID do novo pai (null para tornar categoria principal)
     * @param int $position Posição dentro do nível (order)
     * @return bool Sucesso da operação
     */
    public function moveCategory($categoryId, $newParentId = null, $position = 0) {
        try {
            // Verificar se a categoria existe
            $category = $this->find($categoryId);
            if (!$category) {
                return false;
            }
            
            // Se utilizar Nested Sets, recalcular valores
            if (isset($category['left_value']) && isset($category['right_value'])) {
                return $this->moveCategoryNestedSets($categoryId, $newParentId, $position);
            }
            
            // Método simples usando apenas parent_id e display_order
            $data = [
                'parent_id' => $newParentId,
                'display_order' => $position
            ];
            
            return $this->update($categoryId, $data);
        } catch (Exception $e) {
            error_log("Erro ao mover categoria: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reorganiza uma categoria usando o algoritmo de Nested Sets
     * 
     * @param int $categoryId ID da categoria a ser movida
     * @param int|null $newParentId ID do novo pai (null para tornar categoria principal)
     * @param int $position Posição dentro do nível (order)
     * @return bool Sucesso da operação
     */
    private function moveCategoryNestedSets($categoryId, $newParentId = null, $position = 0) {
        try {
            // A implementação completa do Nested Sets é complexa e requer transações
            // Esta é uma versão simplificada
            $this->db()->beginTransaction();
            
            // 1. Obter valores atuais
            $category = $this->find($categoryId);
            $left = $category['left_value'];
            $right = $category['right_value'];
            $width = $right - $left + 1;
            
            // 2. Remover temporariamente a subárvore (usando valores negativos)
            $sql = "UPDATE {$this->table} SET 
                    left_value = -left_value, 
                    right_value = -right_value 
                    WHERE left_value >= :left AND right_value <= :right";
            
            $this->db()->execute($sql, ['left' => $left, 'right' => $right]);
            
            // 3. Fechar o buraco deixado na árvore
            $sql = "UPDATE {$this->table} SET 
                    left_value = CASE 
                        WHEN left_value > :right THEN left_value - :width 
                        ELSE left_value 
                    END,
                    right_value = right_value - :width 
                    WHERE right_value > :right AND right_value > 0";
            
            $this->db()->execute($sql, ['right' => $right, 'width' => $width]);
            
            // 4. Encontrar a posição para inserção
            if ($newParentId === null) {
                // Tornar categoria raiz
                $sql = "SELECT COALESCE(MAX(right_value), 0) + 1 as new_left FROM {$this->table} WHERE parent_id IS NULL AND right_value > 0";
                $result = $this->db()->select($sql);
                $newLeft = isset($result[0]['new_left']) ? $result[0]['new_left'] : 1;
            } else {
                // Subcategoria
                $parent = $this->find($newParentId);
                if (!$parent) {
                    throw new Exception("Categoria pai não encontrada");
                }
                
                $parentRight = $parent['right_value'];
                
                // Ajustar posição baseado em siblings
                if ($position > 0) {
                    $sql = "SELECT right_value FROM {$this->table} 
                            WHERE parent_id = :parent_id AND right_value > 0 
                            ORDER BY display_order, name
                            LIMIT :position, 1";
                    
                    $result = $this->db()->select($sql, [
                        'parent_id' => $newParentId,
                        'position' => $position - 1
                    ]);
                    
                    if (!empty($result)) {
                        $newLeft = $result[0]['right_value'] + 1;
                    } else {
                        $newLeft = $parentRight;
                    }
                } else {
                    $newLeft = $parent['left_value'] + 1;
                }
            }
            
            // 5. Abrir espaço para a subárvore
            $sql = "UPDATE {$this->table} SET 
                    left_value = CASE 
                        WHEN left_value >= :new_left THEN left_value + :width 
                        ELSE left_value 
                    END,
                    right_value = CASE 
                        WHEN right_value >= :new_left THEN right_value + :width 
                        ELSE right_value 
                    END
                    WHERE right_value > 0";
            
            $this->db()->execute($sql, ['new_left' => $newLeft, 'width' => $width]);
            
            // 6. Reposicionar a subárvore (voltando a usar valores positivos)
            $sql = "UPDATE {$this->table} SET 
                    left_value = :new_left - left_value - :left + 1,
                    right_value = :new_left - right_value + :right + :width - 1,
                    parent_id = CASE 
                        WHEN id = :id THEN :new_parent_id 
                        ELSE parent_id 
                    END,
                    display_order = CASE 
                        WHEN id = :id THEN :position 
                        ELSE display_order 
                    END
                    WHERE left_value < 0";
            
            $this->db()->execute($sql, [
                'new_left' => $newLeft,
                'left' => $left,
                'right' => $right,
                'width' => $width,
                'id' => $categoryId,
                'new_parent_id' => $newParentId,
                'position' => $position
            ]);
            
            // Commit da transação
            $this->db()->commit();
            
            return true;
        } catch (Exception $e) {
            $this->db()->rollback();
            error_log("Erro ao mover categoria usando Nested Sets: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recalcula os valores left_value e right_value para o algoritmo de Nested Sets
     * 
     * @return bool Sucesso da operação
     */
    public function rebuildTree() {
        try {
            // Verificar se a tabela está usando Nested Sets
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'left_value'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                return false; // Não está usando Nested Sets
            }
            
            $this->db()->beginTransaction();
            
            // Passo 1: Limpar valores
            $sql = "UPDATE {$this->table} SET left_value = 0, right_value = 0";
            $this->db()->execute($sql);
            
            // Passo 2: Reconstruir a árvore
            $counter = 1;
            $this->rebuildBranch(null, $counter);
            
            $this->db()->commit();
            
            return true;
        } catch (Exception $e) {
            $this->db()->rollback();
            error_log("Erro ao reconstruir árvore de categorias: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recalcula os valores left_value e right_value para um ramo da árvore
     * 
     * @param int|null $parentId ID da categoria pai
     * @param int &$counter Contador para os valores left_value e right_value
     * @return int Valor right_value do ramo
     */
    private function rebuildBranch($parentId, &$counter) {
        try {
            // Obter nós deste nível
            $sql = "SELECT id FROM {$this->table} 
                    WHERE " . ($parentId === null ? "parent_id IS NULL" : "parent_id = :parent_id") . " 
                    ORDER BY display_order, name";
            
            $params = $parentId === null ? [] : ['parent_id' => $parentId];
            $nodes = $this->db()->select($sql, $params);
            
            foreach ($nodes as $node) {
                // Definir left_value
                $leftValue = $counter++;
                
                // Processar filhos
                $rightValue = $this->rebuildBranch($node['id'], $counter);
                
                // Atualizar nó
                $sql = "UPDATE {$this->table} SET left_value = :left, right_value = :right WHERE id = :id";
                $this->db()->execute($sql, [
                    'left' => $leftValue,
                    'right' => $rightValue,
                    'id' => $node['id']
                ]);
            }
            
            // Se não houver nós neste nível, retornar counter
            if (empty($nodes)) {
                return $counter;
            }
            
            // Retornar rightValue do último nó processado
            return $counter++;
        } catch (Exception $e) {
            error_log("Erro ao reconstruir ramo da árvore: " . $e->getMessage());
            throw $e; // Propagar exceção para ser capturada em rebuildTree
        }
    }
}