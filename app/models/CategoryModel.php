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
            $sql = "SELECT * FROM {$this->table} 
                    WHERE parent_id IS NULL AND is_active = 1
                    ORDER BY display_order, name";
            
            $categories = $this->db()->select($sql);
            
            if ($withSubcategories && !empty($categories)) {
                foreach ($categories as &$category) {
                    $category['subcategories'] = $this->getSubcategories($category['id'], true);
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
            $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND is_active = 1";
            $result = $this->db()->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                return null;
            }
            
            $category = $result[0];
            
            // Buscar subcategorias
            try {
                if ($recursiveSubcategories) {
                    // Buscar subcategorias recursivamente
                    $category['subcategories'] = $this->getSubcategoriesRecursive($category['id']);
                } else {
                    // Buscar apenas subcategorias diretas
                    $sql = "SELECT * FROM {$this->table} 
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
                return $this->getSubcategoriesRecursive($parentId);
            }
            
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
     * Obter subcategorias de forma recursiva
     * 
     * @param int $parentId ID da categoria pai
     * @return array Lista de subcategorias com suas subcategorias
     */
    public function getSubcategoriesRecursive($parentId) {
        try {
            $sql = "SELECT * FROM {$this->table} 
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
     * Obter hierarquia completa de categorias
     * 
     * @return array Hierarquia de categorias
     */
    public function getFullHierarchy() {
        try {
            // Buscar categorias principais
            $mainCategories = $this->getMainCategories();
            
            // Buscar subcategorias para cada categoria principal (de forma recursiva)
            foreach ($mainCategories as &$category) {
                $category['subcategories'] = $this->getSubcategoriesRecursive($category['id']);
            }
            
            return $mainCategories;
        } catch (Exception $e) {
            error_log("Erro ao buscar hierarquia de categorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter hierarquia de categorias em formato plano com nível de indentação
     * 
     * @return array Lista de categorias com nível de indentação
     */
    public function getFlatHierarchy() {
        try {
            $sql = "SELECT c.*, 
                          COALESCE((
                              SELECT COUNT(*) 
                              FROM {$this->table} p 
                              WHERE 
                                FIND_IN_SET(p.id, GetCategoryAncestors(c.id)) > 0
                          ), 0) as depth
                   FROM {$this->table} c
                   WHERE c.is_active = 1
                   ORDER BY c.left_value";
            
            $categories = $this->db()->select($sql);
            
            // Se não tiver left_value e right_value, fazer o processamento via PHP
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
            $sql = "SELECT parent.* 
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
     * @return array|null Dados da categoria com produtos ou null se não encontrada
     */
    public function getCategoryWithProducts($slug, $page = 1, $limit = 12, $includeSubcategories = true) {
        try {
            // Obter categoria
            $category = $this->getBySlug($slug);
            if (!$category) {
                return null;
            }
            
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
                $sql = "SELECT p.*, pi.image, 
                               CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability
                       FROM {$productModel->getTable()} p
                       LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                       WHERE {$conditions}
                       GROUP BY p.id
                       ORDER BY p.is_tested DESC, p.name ASC
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
