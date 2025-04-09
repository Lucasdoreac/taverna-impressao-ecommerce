<?php
/**
 * AdminCategoryController - Controlador para gerenciamento de categorias no painel administrativo
 */
class AdminCategoryController {
    private $categoryModel;
    
    /**
     * Construtor - verifica se o usuário é administrador e inicializa modelos
     */
    public function __construct() {
        // Verificar se o usuário está logado e é administrador
        AdminHelper::checkAdminAccess();
        
        // Inicializar modelos
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Exibe a lista de categorias
     */
    public function index() {
        // Buscar todas as categorias com hierarquia
        $categories = $this->categoryModel->getFullHierarchy();
        
        // Renderizar view
        $view_path = VIEWS_PATH . '/admin/categories/index.php';
        if (file_exists($view_path) && is_file($view_path)) {
            require_once $view_path;
        } else {
            throw new Exception("View file not found or is not a regular file.");
        }
    }

    
    /**
     * Exibe o formulário para criar uma nova categoria
     */
    public function create() {
        // Buscar categorias para o select de categoria pai
        $parentCategories = $this->categoryModel->getMainCategories();
        
        // Inicializar dados da categoria vazia
        $category = [
            'id' => null,
            'parent_id' => null,
            'name' => '',
            'slug' => '',
            'description' => '',
            'image' => '',
            'is_active' => 1,
            'display_order' => 0
        ];
        
        // Renderizar view
        $viewPath = VIEWS_PATH . '/admin/categories/form.php';
        if (file_exists($viewPath) && is_file($viewPath)) {
            require_once $viewPath;
        } else {
            throw new Exception('View file not found');
        }
    }

    
    /**
     * Exibe o formulário para editar uma categoria existente
     */
    public function edit($params) {
        // Obter ID da categoria
        $id = $params['id'] ?? 0;
        
        // Buscar categoria
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            $_SESSION['error'] = 'Categoria não encontrada.';
            header('Location: ' . BASE_URL . 'admin/categorias');
            return;
        }
        
        // Buscar categorias para o select de categoria pai

        // Excluindo a própria categoria e suas subcategorias para evitar ciclos
        $parentCategories = $this->categoryModel->getMainCategories();
        
        // Filtrar categorias que não podem ser pai desta categoria (ela mesma e suas subcategorias)
        $excludeIds = $this->getSubcategoryIds($id);
        $excludeIds[] = $id;
        
        $filteredParentCategories = array_filter($parentCategories, function($parent) use ($excludeIds) {
            return !in_array($parent['id'], $excludeIds);
        });
        
        // Renderizar view
        $safeViewPath = realpath(VIEWS_PATH . '/admin/categories/form.php');
        if ($safeViewPath !== false && strpos($safeViewPath, VIEWS_PATH) === 0) {
            require_once $safeViewPath;
        } else {
            throw new Exception('Invalid view path');
        }
    }

    
    /**
     * Processa o formulário para salvar uma categoria (criar ou atualizar)
     */
    public function save() {
        // Verificar se o formulário foi submetido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/categorias');
            exit;
        }
        
        // Obter dados do formulário
        $id = $_POST['id'] ?? null;
        $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $description = $_POST['description'] ?? '';
        $displayOrder = $_POST['display_order'] ?? 0;
        
        // Checkboxes
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validação básica
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'O nome da categoria é obrigatório.';
        }
        
        if (empty($slug)) {
            $slug = AdminHelper::generateSlug($name);
        }
        
        // Verificar se o slug já existe para outra categoria
        $existingCategory = $this->categoryModel->findBySlug($slug);
        if ($existingCategory && $existingCategory['id'] != $id) {
            $errors['slug'] = 'Este slug já está em uso por outra categoria. Por favor, escolha outro.';
        }
        
        // Se houver erros, redirecionar de volta com mensagens
        if (!empty($errors)) {
            $_SESSION['error'] = 'Existem erros no formulário. Por favor, verifique os campos destacados.';
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            
            if ($id) {
                header('Location: ' . BASE_URL . 'admin/categorias/edit/' . $id);
            } else {
                header('Location: ' . BASE_URL . 'admin/categorias/create');
            }
            exit;
        }
        
        // Upload de imagem
        $imageName = null;
        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadDir = ROOT_PATH . '/public/uploads/categories/';
            $result = AdminHelper::uploadImage($_FILES['image'], $uploadDir);
            
            if ($result['success']) {
                $imageName = $result['filename'];
            }
        }
        
        // Preparar dados para salvar
        $categoryData = [
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => $isActive,
            'display_order' => $displayOrder
        ];
        
        // Adicionar imagem se houver upload
        if ($imageName) {
            $categoryData['image'] = $imageName;
        }
        
        // Salvar categoria
        if ($id) {
            // Atualizar categoria existente
            $this->categoryModel->update($id, $categoryData);
            $message = 'Categoria atualizada com sucesso!';
        } else {
            // Criar nova categoria
            $id = $this->categoryModel->create($categoryData);
            $message = 'Categoria criada com sucesso!';
        }
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = $message;
        header('Location: ' . BASE_URL . 'admin/categorias');
        exit;
    }
    
    /**
     * Exclui uma categoria
     */
    public function delete($params) {
        // Obter ID da categoria
        $id = $params['id'] ?? 0;
        
        // Buscar categoria
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            $_SESSION['error'] = 'Categoria não encontrada.';
            header('Location: ' . BASE_URL . 'admin/categorias');
            exit;
        }
        
        // Verificar se existem subcategorias
        $subcategories = $this->categoryModel->getSubcategories($id);
        if (!empty($subcategories)) {
            $_SESSION['error'] = 'Não é possível excluir esta categoria porque ela possui subcategorias.';
            header('Location: ' . BASE_URL . 'admin/categorias');
            exit;
        }
        
        // Verificar se existem produtos nesta categoria
        $productModel = new ProductModel();
        $products = $productModel->getByCategory($id);
        if (!empty($products['items'])) {
            $_SESSION['error'] = 'Não é possível excluir esta categoria porque existem produtos associados a ela.';
            header('Location: ' . BASE_URL . 'admin/categorias');
            exit;
        }
        
        // Excluir categoria
        $this->categoryModel->delete($id);
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Categoria excluída com sucesso!';
        header('Location: ' . BASE_URL . 'admin/categorias');
        exit;
    }
    
    /**
     * Altera o status de ativação de uma categoria
     */
    public function toggleActive($params) {
        // Obter ID da categoria
        $id = $params['id'] ?? 0;
        
        // Buscar categoria
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            $_SESSION['error'] = 'Categoria não encontrada.';
            header('Location: ' . BASE_URL . 'admin/categorias');
            exit;
        }
        
        // Inverter o status de ativação
        $isActive = $category['is_active'] ? 0 : 1;
        
        // Atualizar categoria
        $this->categoryModel->update($id, ['is_active' => $isActive]);
        
        // Redirecionar com mensagem de sucesso
        $message = $isActive ? 'Categoria ativada com sucesso!' : 'Categoria desativada com sucesso!';
        $_SESSION['success'] = $message;
        header('Location: ' . BASE_URL . 'admin/categorias');
        exit;
    }
    
    /**
     * Obtém todos os IDs de subcategorias (recursivamente)
     */
    private function getSubcategoryIds($categoryId) {
        $ids = [];
        
        // Buscar subcategorias diretas
        $subcategories = $this->categoryModel->getSubcategories($categoryId);
        
        foreach ($subcategories as $subcategory) {
            $ids[] = $subcategory['id'];
            
            // Buscar subcategorias de nível mais profundo
            $subIds = $this->getSubcategoryIds($subcategory['id']);
            $ids = array_merge($ids, $subIds);
        }
        
        return $ids;
    }
}
