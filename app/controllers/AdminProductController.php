<?php
/**
 * AdminProductController - Controlador para gerenciamento de produtos no painel administrativo
 * 
 * @version     1.2.0
 * @author      Taverna da Impressão
 */
class AdminProductController {
    // Implementação do trait de validação de entrada
    use InputValidationTrait;
    
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
        
        // Carregar bibliotecas de segurança
        require_once APP_PATH . '/lib/Security/CsrfProtection.php';
        require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
    }
    
    /**
     * Exibe a lista de produtos
     */
    public function index() {
        // Parâmetros de filtro e paginação usando InputValidationTrait
        $page = $this->getValidatedParam('page', 'int', [
            'required' => false,
            'default' => 1,
            'min' => 1
        ]);
        
        $limit = 20;
        
        // Filtros com validação
        $filters = [
            'name' => $this->getValidatedParam('name', 'string', [
                'required' => false,
                'default' => '',
                'maxLength' => 200
            ]),
            'category_id' => $this->getValidatedParam('category_id', 'int', [
                'required' => false,
                'default' => '',
                'min' => 0
            ]),
            'is_active' => $this->getValidatedParam('is_active', 'bool', [
                'required' => false,
                'default' => ''
            ]),
            'is_featured' => $this->getValidatedParam('is_featured', 'bool', [
                'required' => false,
                'default' => ''
            ]),
            'is_customizable' => $this->getValidatedParam('is_customizable', 'bool', [
                'required' => false,
                'default' => ''
            ]),
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
        // Validar ID do produto usando InputValidationTrait
        $id = $this->requestValidatedParam('id', 'int', [
            'required' => true,
            'default' => isset($params['id']) ? (int)$params['id'] : 0,
            'min' => 1
        ]);
        
        if (!$id) {
            $_SESSION['error'] = 'ID de produto inválido.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
        
        // Validar token CSRF
        if (!CsrfProtection::validateRequest()) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Limpar qualquer erro de validação anterior
        $this->clearValidationErrors();
        
        // Validar dados do formulário usando InputValidationTrait
        $id = $this->postValidatedParam('id', 'int', [
            'required' => false,
            'min' => 1
        ]);
        
        $categoryId = $this->postValidatedParam('category_id', 'int', [
            'required' => true,
            'min' => 1
        ]);
        
        $name = $this->postValidatedParam('name', 'string', [
            'required' => true,
            'minLength' => 1,
            'maxLength' => 200
        ]);
        
        $slug = $this->postValidatedParam('slug', 'slug', [
            'required' => false,
            'maxLength' => 200,
            'pattern' => '/^[a-z0-9-]*$/'
        ]);
        
        $description = $this->postValidatedParam('description', 'string', [
            'required' => false,
            'default' => ''
        ]);
        
        $shortDescription = $this->postValidatedParam('short_description', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 500
        ]);
        
        $price = $this->postValidatedParam('price', 'float', [
            'required' => true,
            'min' => 0.01
        ]);
        
        $salePrice = $this->postValidatedParam('sale_price', 'float', [
            'required' => false,
            'min' => 0
        ]);
        
        $stock = $this->postValidatedParam('stock', 'int', [
            'required' => false,
            'default' => 0,
            'min' => 0
        ]);
        
        $weight = $this->postValidatedParam('weight', 'float', [
            'required' => false,
            'default' => 0,
            'min' => 0
        ]);
        
        $dimensions = $this->postValidatedParam('dimensions', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 100
        ]);
        
        $sku = $this->postValidatedParam('sku', 'string', [
            'required' => false,
            'default' => '',
            'maxLength' => 50
        ]);
        
        // Checkboxes
        $isFeatured = $this->postValidatedParam('is_featured', 'bool', [
            'required' => false,
            'default' => false
        ]) ? 1 : 0;
        
        $isActive = $this->postValidatedParam('is_active', 'bool', [
            'required' => false,
            'default' => true
        ]) ? 1 : 0;
        
        $isCustomizable = $this->postValidatedParam('is_customizable', 'bool', [
            'required' => false,
            'default' => false
        ]) ? 1 : 0;
        
        // Gerar slug a partir do nome se não fornecido
        if (empty($slug) && !empty($name)) {
            $slug = AdminHelper::generateSlug($name);
        }
        
        // Verificar se o slug já existe para outro produto
        if (!empty($slug)) {
            $existingProduct = $this->productModel->findBySlug($slug);
            if ($existingProduct && $existingProduct['id'] != $id) {
                InputValidator::addError('slug', 'Este slug já está em uso por outro produto. Por favor, escolha outro.');
            }
        }
        
        // Verificar erros de validação
        $errors = [];
        
        if ($this->hasValidationErrors()) {
            $validationErrors = $this->getValidationErrors();
            foreach ($validationErrors as $field => $fieldErrors) {
                $errors[$field] = implode(', ', $fieldErrors);
            }
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
                    // Usar validação de arquivos com maior segurança
                    $options = [
                        'maxSize' => 5 * 1024 * 1024, // 5MB
                        'allowedExtensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                        'allowedMimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
                    ];
                    
                    try {
                        $result = AdminHelper::uploadImage($file, $uploadDir);
                        
                        if ($result['success']) {
                            $uploadedImages[] = $result['filename'];
                        }
                    } catch (Exception $e) {
                        $_SESSION['warning'] = 'Alguns arquivos não puderam ser carregados: ' . $e->getMessage();
                    }
                }
            }
        }
        
        // Opções de personalização
        $customizationOptions = [];
        if (isset($_POST['customization']) && is_array($_POST['customization'])) {
            foreach ($_POST['customization'] as $option) {
                // Validar cada opção individualmente
                if (!empty($option['name']) && !empty($option['type'])) {
                    $customizationOptions[] = [
                        'name' => htmlspecialchars($option['name']),
                        'description' => isset($option['description']) ? htmlspecialchars($option['description']) : '',
                        'type' => htmlspecialchars($option['type']),
                        'required' => isset($option['required']) ? 1 : 0,
                        'options' => ($option['type'] === 'select' && !empty($option['options'])) ? htmlspecialchars($option['options']) : null
                    ];
                }
            }
        }
        
        // Salvar produto
        try {
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
            $mainImage = $this->postValidatedParam('main_image', 'int', [
                'required' => false,
                'min' => 1
            ]);
            
            if ($mainImage) {
                $this->productModel->setMainImage($productId, $mainImage);
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
            
        } catch (Exception $e) {
            // Tratar exceções
            $this->handleError($e, "Erro ao salvar produto");
        }
    }
    
    /**
     * Exibe detalhes de um produto
     */
    public function view($params) {
        // Validar ID do produto
        $id = $this->requestValidatedParam('id', 'int', [
            'required' => true,
            'default' => isset($params['id']) ? (int)$params['id'] : 0,
            'min' => 1
        ]);
        
        if (!$id) {
            $_SESSION['error'] = 'ID de produto inválido.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
        // Verificar token CSRF (para links, deve ser incluído na URL)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Validar ID do produto
        $id = $this->requestValidatedParam('id', 'int', [
            'required' => true,
            'default' => isset($params['id']) ? (int)$params['id'] : 0,
            'min' => 1
        ]);
        
        if (!$id) {
            $_SESSION['error'] = 'ID de produto inválido.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
        // Verificar token CSRF (para links, deve ser incluído na URL)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Validar ID do produto
        $id = $this->requestValidatedParam('id', 'int', [
            'required' => true,
            'default' => isset($params['id']) ? (int)$params['id'] : 0,
            'min' => 1
        ]);
        
        if (!$id) {
            $_SESSION['error'] = 'ID de produto inválido.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
        // Verificar token CSRF (para links, deve ser incluído na URL)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Validar ID do produto
        $id = $this->requestValidatedParam('id', 'int', [
            'required' => true,
            'default' => isset($params['id']) ? (int)$params['id'] : 0,
            'min' => 1
        ]);
        
        if (!$id) {
            $_SESSION['error'] = 'ID de produto inválido.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
        // Verificar token CSRF (para links, deve ser incluído na URL)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Validar IDs
        $productId = $this->requestValidatedParam('product_id', 'int', [
            'required' => true,
            'default' => isset($params['product_id']) ? (int)$params['product_id'] : 0,
            'min' => 1
        ]);
        
        $imageId = $this->requestValidatedParam('image_id', 'int', [
            'required' => true,
            'default' => isset($params['image_id']) ? (int)$params['image_id'] : 0,
            'min' => 1
        ]);
        
        if (!$productId || !$imageId) {
            $_SESSION['error'] = 'IDs inválidos.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
        // Verificar token CSRF (para links, deve ser incluído na URL)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', [
            'required' => true
        ]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
        // Validar IDs
        $productId = $this->requestValidatedParam('product_id', 'int', [
            'required' => true,
            'default' => isset($params['product_id']) ? (int)$params['product_id'] : 0,
            'min' => 1
        ]);
        
        $imageId = $this->requestValidatedParam('image_id', 'int', [
            'required' => true,
            'default' => isset($params['image_id']) ? (int)$params['image_id'] : 0,
            'min' => 1
        ]);
        
        if (!$productId || !$imageId) {
            $_SESSION['error'] = 'IDs inválidos.';
            header('Location: ' . BASE_URL . 'admin/produtos');
            exit;
        }
        
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
    
    /**
     * Tratamento de erros centralizado
     * 
     * @param mixed $e Exceção ou erro
     * @param string $message Mensagem amigável para o usuário
     */
    protected function handleError($e, $message = "Ocorreu um erro") {
        // Registrar erro no log
        error_log("$message: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Salvar sessão com erro
        $_SESSION['error'] = ENVIRONMENT === 'development' ? 
            $message . ': ' . $e->getMessage() : 
            'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
        
        // Redirecionar para lista de produtos
        header('Location: ' . BASE_URL . 'admin/produtos');
        exit;
    }
}