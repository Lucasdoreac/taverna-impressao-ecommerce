<?php
/**
 * AdminProductController - Controlador para gerenciamento de produtos no painel administrativo
 */
class AdminProductController {
    private $productModel;
    private $categoryModel;
    
    /**
     * Construtor - verifica se o usuário é administrador e inicializa modelos
     */
    public function __construct() {
        // Verificar se o usuário está logado e é administrador
        AdminHelper::checkAdminAccess();
        
        // Inicializar modelos
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Exibe a lista de produtos
     */
    public function index() {
        // Parâmetros de filtro e paginação
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        
        // Filtros
        $filters = [
            'name' => $_GET['name'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'is_active' => isset($_GET['is_active']) ? $_GET['is_active'] : '',
            'is_featured' => isset($_GET['is_featured']) ? $_GET['is_featured'] : '',
            'is_customizable' => isset($_GET['is_customizable']) ? $_GET['is_customizable'] : '',
        ];
        
        // Buscar produtos com paginação e filtros
        $products = $this->productModel->getWithFilters($filters, $page, $limit);
        
        // Buscar categorias para o filtro
        $categories = $this->categoryModel->getFullHierarchy();
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/products/index.php';
    }
    
    /**
     * Exibe o formulário para criar um novo produto
     */
    public function create() {
        // Buscar categorias para o select
        $categories = $this->categoryModel->getFullHierarchy();
        
        // Inicializar dados do produto vazio
        $product = [
            'id' => null,
            'category_id' => '',
            'name' => '',
            'slug' => '',
            'description' => '',
            'short_description' => '',
            'price' => '',
            'sale_price' => '',
            'stock' => 0,
            'weight' => 0,
            'dimensions' => '',
            'sku' => '',
            'is_featured' => 0,
            'is_active' => 1,
            'is_customizable' => 0,
            'images' => [],
            'customization_options' => []
        ];
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/products/form.php';
    }
    
    /**
     * Exibe o formulário para editar um produto existente
     */
    public function edit($params) {
        // Obter ID do produto
        $id = $params['id'] ?? 0;
        
        // Buscar produto
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Buscar imagens do produto
        $product['images'] = $this->productModel->getImages($id);
        
        // Buscar opções de personalização
        $product['customization_options'] = $this->productModel->getCustomizationOptions($id);
        
        // Buscar categorias para o select
        $categories = $this->categoryModel->getFullHierarchy();
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/products/form.php';
    }
    
    /**
     * Processa o formulário para salvar um produto (criar ou atualizar)
     */
    public function save() {
        // Verificar se o formulário foi submetido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Obter dados do formulário
        $id = $_POST['id'] ?? null;
        $categoryId = $_POST['category_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $description = $_POST['description'] ?? '';
        $shortDescription = $_POST['short_description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $salePrice = !empty($_POST['sale_price']) ? $_POST['sale_price'] : null;
        $stock = $_POST['stock'] ?? 0;
        $weight = $_POST['weight'] ?? 0;
        $dimensions = $_POST['dimensions'] ?? '';
        $sku = $_POST['sku'] ?? '';
        
        // Checkboxes
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isCustomizable = isset($_POST['is_customizable']) ? 1 : 0;
        
        // Validação básica
        $errors = [];
        
        if (empty($categoryId)) {
            $errors['category_id'] = 'Selecione uma categoria.';
        }
        
        if (empty($name)) {
            $errors['name'] = 'O nome do produto é obrigatório.';
        }
        
        if (empty($slug)) {
            $slug = AdminHelper::generateSlug($name);
        }
        
        if (empty($price) || !is_numeric($price) || $price <= 0) {
            $errors['price'] = 'Informe um preço válido.';
        }
        
        if (!empty($salePrice) && (!is_numeric($salePrice) || $salePrice <= 0)) {
            $errors['sale_price'] = 'Informe um preço promocional válido.';
        }
        
        // Verificar se o slug já existe para outro produto
        $existingProduct = $this->productModel->findBySlug($slug);
        if ($existingProduct && $existingProduct['id'] != $id) {
            $errors['slug'] = 'Este slug já está em uso por outro produto. Por favor, escolha outro.';
        }
        
        // Se houver erros, redirecionar de volta com mensagens
        if (!empty($errors)) {
            $_SESSION['error'] = 'Existem erros no formulário. Por favor, verifique os campos destacados.';
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            
            if ($id) {
                header('Location: ' . BASE_URL . 'admin/produtos/edit/' . $id);
            } else {
                header('Location: ' . BASE_URL . 'admin/produtos/create');
            }
            exit;
        }
        
        // Preparar dados para salvar
        $productData = [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'short_description' => $shortDescription,
            'price' => $price,
            'sale_price' => $salePrice,
            'stock' => $stock,
            'weight' => $weight,
            'dimensions' => $dimensions,
            'sku' => $sku,
            'is_featured' => $isFeatured,
            'is_active' => $isActive,
            'is_customizable' => $isCustomizable
        ];
        
        // Upload de imagens
        $uploadedImages = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $uploadDir = ROOT_PATH . '/public/uploads/products/';
            
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];
                
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $result = AdminHelper::uploadImage($file, $uploadDir);
                    
                    if ($result['success']) {
                        $uploadedImages[] = $result['filename'];
                    }
                }
            }
        }
        
        // Opções de personalização
        $customizationOptions = [];
        if (isset($_POST['customization']) && is_array($_POST['customization'])) {
            foreach ($_POST['customization'] as $option) {
                if (!empty($option['name']) && !empty($option['type'])) {
                    $customizationOptions[] = [
                        'name' => $option['name'],
                        'description' => $option['description'] ?? '',
                        'type' => $option['type'],
                        'required' => isset($option['required']) ? 1 : 0,
                        'options' => ($option['type'] === 'select' && !empty($option['options'])) ? $option['options'] : null
                    ];
                }
            }
        }
        
        // Salvar produto
        if ($id) {
            // Atualizar produto existente
            $this->productModel->update($id, $productData);
            $productId = $id;
            $message = 'Produto atualizado com sucesso!';
        } else {
            // Criar novo produto
            $productId = $this->productModel->create($productData);
            $message = 'Produto criado com sucesso!';
        }
        
        // Salvar imagens
        if (!empty($uploadedImages)) {
            foreach ($uploadedImages as $index => $image) {
                $isMain = $index === 0 && !$this->productModel->hasMainImage($productId);
                $this->productModel->addImage($productId, $image, $isMain, $index);
            }
        }
        
        // Definir imagem principal se enviada
        if (isset($_POST['main_image']) && !empty($_POST['main_image'])) {
            $this->productModel->setMainImage($productId, $_POST['main_image']);
        }
        
        // Salvar opções de personalização
        if ($productData['is_customizable'] && !empty($customizationOptions)) {
            // Remover opções existentes
            $this->productModel->deleteCustomizationOptions($productId);
            
            // Adicionar novas opções
            foreach ($customizationOptions as $option) {
                $this->productModel->addCustomizationOption($productId, $option);
            }
        } elseif (!$productData['is_customizable']) {
            // Se não for personalizável, remover todas as opções
            $this->productModel->deleteCustomizationOptions($productId);
        }
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = $message;
        header('Location: ' . BASE_URL . 'admin/produtos');
        exit;
    }
    
    /**
     * Exibe detalhes de um produto
     */
    public function view($params) {
        // Obter ID do produto
        $id = $params['id'] ?? 0;
        
        // Buscar produto
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Buscar categoria
        $category = $this->categoryModel->find($product['category_id']);
        
        // Buscar imagens
        $product['images'] = $this->productModel->getImages($id);
        
        // Buscar opções de personalização
        $product['customization_options'] = $this->productModel->getCustomizationOptions($id);
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/products/view.php';
    }
    
    /**
     * Exclui um produto
     */
    public function delete($params) {
        // Obter ID do produto
        $id = $params['id'] ?? 0;
        
        // Buscar produto
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Excluir produto
        $this->productModel->delete($id);
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Produto excluído com sucesso!';
        header('Location: ' . BASE_URL . 'admin/produtos');
        exit;
    }
    
    /**
     * Altera o status de destaque de um produto
     */
    public function toggleFeatured($params) {
        // Obter ID do produto
        $id = $params['id'] ?? 0;
        
        // Buscar produto
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Inverter o status de destaque
        $isFeatured = $product['is_featured'] ? 0 : 1;
        
        // Atualizar produto
        $this->productModel->update($id, ['is_featured' => $isFeatured]);
        
        // Redirecionar com mensagem de sucesso
        $message = $isFeatured ? 'Produto destacado com sucesso!' : 'Produto removido dos destaques!';
        $_SESSION['success'] = $message;
        header('Location: ' . BASE_URL . 'admin/produtos');
        exit;
    }
    
    /**
     * Altera o status de ativação de um produto
     */
    public function toggleActive($params) {
        // Obter ID do produto
        $id = $params['id'] ?? 0;
        
        // Buscar produto
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Inverter o status de ativação
        $isActive = $product['is_active'] ? 0 : 1;
        
        // Atualizar produto
        $this->productModel->update($id, ['is_active' => $isActive]);
        
        // Redirecionar com mensagem de sucesso
        $message = $isActive ? 'Produto ativado com sucesso!' : 'Produto desativado com sucesso!';
        $_SESSION['success'] = $message;
        header('Location: ' . BASE_URL . 'admin/produtos');
        exit;
    }
    
    /**
     * Exclui uma imagem de um produto
     */
    public function deleteImage($params) {
        // Obter IDs
        $productId = $params['product_id'] ?? 0;
        $imageId = $params['image_id'] ?? 0;
        
        // Verificar se o produto existe
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Excluir imagem
        $this->productModel->deleteImage($imageId);
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Imagem excluída com sucesso!';
        header('Location: ' . BASE_URL . 'admin/produtos/edit/' . $productId);
        exit;
    }
    
    /**
     * Define a imagem principal de um produto
     */
    public function setMainImage($params) {
        // Obter IDs
        $productId = $params['product_id'] ?? 0;
        $imageId = $params['image_id'] ?? 0;
        
        // Verificar se o produto existe
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            $_SESSION['error'] = 'Produto não encontrado.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Definir imagem principal
        $this->productModel->setMainImage($productId, $imageId);
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Imagem principal definida com sucesso!';
        header('Location: ' . BASE_URL . 'admin/produtos/edit/' . $productId);
        exit;
    }
}
