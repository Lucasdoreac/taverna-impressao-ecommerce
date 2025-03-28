<?php
/**
 * AdminCustomizationController - Controlador para gerenciamento de opções de personalização no painel administrativo
 */
class AdminCustomizationController {
    
    /**
     * Exibe a listagem de opções de personalização
     */
    public function index() {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Obter o ID do produto se for filtrado
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        
        // Obter parâmetros de paginação
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        
        // Obter opções de personalização
        $customizationModel = new CustomizationModel();
        
        if ($productId) {
            $options = $customizationModel->getByProduct($productId, $page, $limit);
            
            // Obter informações do produto
            $productModel = new ProductModel();
            $product = $productModel->find($productId);
            
            if (!$product) {
                $_SESSION['error'] = 'Produto não encontrado.';
                header('Location: ' . BASE_URL . 'admin/produtos');
                exit;
            }
        } else {
            $options = $customizationModel->getAll($page, $limit);
        }
        
        // Obter lista de produtos personalizáveis para filtro
        $productModel = new ProductModel();
        $customizableProducts = $productModel->getCustomizableProducts();
        
        // Renderizar a view
        require_once VIEWS_PATH . '/admin/customization/index.php';
    }
    
    /**
     * Exibe o formulário para criar uma nova opção de personalização
     */
    public function create() {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Obter lista de produtos personalizáveis
        $productModel = new ProductModel();
        $products = $productModel->getCustomizableProducts();
        
        // Pré-selecionar produto se passado via GET
        $selectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
        
        // Renderizar a view
        require_once VIEWS_PATH . '/admin/customization/form.php';
    }
    
    /**
     * Processa a criação de uma nova opção de personalização
     */
    public function store() {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Verificar token CSRF
        if (!Security::validateCSRFToken()) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . BASE_URL . 'admin/customization/create');
            exit;
        }
        
        // Validar entrada
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $name = isset($_POST['name']) ? Security::sanitizeInput($_POST['name']) : '';
        $description = isset($_POST['description']) ? Security::sanitizeInput($_POST['description']) : '';
        $type = isset($_POST['type']) ? Security::sanitizeInput($_POST['type']) : '';
        $required = isset($_POST['required']) ? 1 : 0;
        $options = isset($_POST['options']) ? $_POST['options'] : '';
        
        // Validar campos obrigatórios
        if (!$productId || !$name || !$type) {
            $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/customization/create');
            exit;
        }
        
        // Validar tipo
        $allowedTypes = ['upload', 'text', 'select'];
        if (!in_array($type, $allowedTypes)) {
            $_SESSION['error'] = 'Tipo de customização inválido.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/customization/create');
            exit;
        }
        
        // Tratar opções para tipo select
        $optionsArray = [];
        if ($type === 'select' && !empty($options)) {
            $lines = explode("\n", $options);
            foreach ($lines as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    if (!empty($key) && !empty($value)) {
                        $optionsArray[$key] = $value;
                    }
                }
            }
            
            if (empty($optionsArray)) {
                $_SESSION['error'] = 'Opções inválidas para o tipo "select". Use o formato "valor: texto" em cada linha.';
                $_SESSION['form_data'] = $_POST;
                header('Location: ' . BASE_URL . 'admin/customization/create');
                exit;
            }
        }
        
        // Preparar dados para inserção
        $data = [
            'product_id' => $productId,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'required' => $required,
            'options' => $type === 'select' ? json_encode($optionsArray) : null
        ];
        
        // Inserir no banco de dados
        $customizationModel = new CustomizationModel();
        $id = $customizationModel->create($data);
        
        if ($id) {
            // Registrar atividade
            Security::logSecurityActivity(
                'Criar Opção de Personalização',
                "Admin criou nova opção de personalização ID: {$id}, Nome: {$name}",
                'info'
            );
            
            $_SESSION['success'] = 'Opção de personalização criada com sucesso!';
            header('Location: ' . BASE_URL . 'admin/customization');
        } else {
            $_SESSION['error'] = 'Erro ao criar opção de personalização. Tente novamente.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/customization/create');
        }
        
        exit;
    }
    
    /**
     * Exibe o formulário para editar uma opção de personalização
     * @param array $params Parâmetros da URL
     */
    public function edit($params) {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Obter ID da opção
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID da opção de personalização inválido.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Obter dados da opção
        $customizationModel = new CustomizationModel();
        $option = $customizationModel->find($id);
        
        if (!$option) {
            $_SESSION['error'] = 'Opção de personalização não encontrada.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Obter lista de produtos personalizáveis
        $productModel = new ProductModel();
        $products = $productModel->getCustomizableProducts();
        
        // Formatar opções para exibição no formulário
        $formattedOptions = '';
        if ($option['type'] === 'select' && !empty($option['options'])) {
            $optionsArray = json_decode($option['options'], true);
            if (is_array($optionsArray)) {
                foreach ($optionsArray as $key => $value) {
                    $formattedOptions .= "{$key}: {$value}\n";
                }
                $formattedOptions = rtrim($formattedOptions);
            }
        }
        
        // Renderizar a view
        require_once VIEWS_PATH . '/admin/customization/form.php';
    }
    
    /**
     * Processa a atualização de uma opção de personalização
     * @param array $params Parâmetros da URL
     */
    public function update($params) {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Verificar token CSRF
        if (!Security::validateCSRFToken()) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Obter ID da opção
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID da opção de personalização inválido.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Validar entrada
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $name = isset($_POST['name']) ? Security::sanitizeInput($_POST['name']) : '';
        $description = isset($_POST['description']) ? Security::sanitizeInput($_POST['description']) : '';
        $type = isset($_POST['type']) ? Security::sanitizeInput($_POST['type']) : '';
        $required = isset($_POST['required']) ? 1 : 0;
        $options = isset($_POST['options']) ? $_POST['options'] : '';
        
        // Validar campos obrigatórios
        if (!$productId || !$name || !$type) {
            $_SESSION['error'] = 'Por favor, preencha todos os campos obrigatórios.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . "admin/customization/edit/{$id}");
            exit;
        }
        
        // Validar tipo
        $allowedTypes = ['upload', 'text', 'select'];
        if (!in_array($type, $allowedTypes)) {
            $_SESSION['error'] = 'Tipo de customização inválido.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . "admin/customization/edit/{$id}");
            exit;
        }
        
        // Tratar opções para tipo select
        $optionsArray = [];
        if ($type === 'select' && !empty($options)) {
            $lines = explode("\n", $options);
            foreach ($lines as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    if (!empty($key) && !empty($value)) {
                        $optionsArray[$key] = $value;
                    }
                }
            }
            
            if (empty($optionsArray)) {
                $_SESSION['error'] = 'Opções inválidas para o tipo "select". Use o formato "valor: texto" em cada linha.';
                $_SESSION['form_data'] = $_POST;
                header('Location: ' . BASE_URL . "admin/customization/edit/{$id}");
                exit;
            }
        }
        
        // Preparar dados para atualização
        $data = [
            'product_id' => $productId,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'required' => $required,
            'options' => $type === 'select' ? json_encode($optionsArray) : null
        ];
        
        // Atualizar no banco de dados
        $customizationModel = new CustomizationModel();
        $result = $customizationModel->update($id, $data);
        
        if ($result) {
            // Registrar atividade
            Security::logSecurityActivity(
                'Atualizar Opção de Personalização',
                "Admin atualizou opção de personalização ID: {$id}, Nome: {$name}",
                'info'
            );
            
            $_SESSION['success'] = 'Opção de personalização atualizada com sucesso!';
            header('Location: ' . BASE_URL . 'admin/customization');
        } else {
            $_SESSION['error'] = 'Erro ao atualizar opção de personalização. Tente novamente.';
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . "admin/customization/edit/{$id}");
        }
        
        exit;
    }
    
    /**
     * Exibe a página de confirmação para excluir uma opção de personalização
     * @param array $params Parâmetros da URL
     */
    public function confirmDelete($params) {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Obter ID da opção
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID da opção de personalização inválido.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Obter dados da opção
        $customizationModel = new CustomizationModel();
        $option = $customizationModel->find($id);
        
        if (!$option) {
            $_SESSION['error'] = 'Opção de personalização não encontrada.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Renderizar a view
        require_once VIEWS_PATH . '/admin/customization/delete.php';
    }
    
    /**
     * Processa a exclusão de uma opção de personalização
     * @param array $params Parâmetros da URL
     */
    public function delete($params) {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Verificar token CSRF
        if (!Security::validateCSRFToken()) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Obter ID da opção
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID da opção de personalização inválido.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Excluir do banco de dados
        $customizationModel = new CustomizationModel();
        $option = $customizationModel->find($id);
        
        if (!$option) {
            $_SESSION['error'] = 'Opção de personalização não encontrada.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        $result = $customizationModel->delete($id);
        
        if ($result) {
            // Registrar atividade
            Security::logSecurityActivity(
                'Excluir Opção de Personalização',
                "Admin excluiu opção de personalização ID: {$id}, Nome: {$option['name']}",
                'warning'
            );
            
            $_SESSION['success'] = 'Opção de personalização excluída com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir opção de personalização. Tente novamente.';
        }
        
        header('Location: ' . BASE_URL . 'admin/customization');
        exit;
    }
    
    /**
     * Exibe os detalhes de uma opção de personalização
     * @param array $params Parâmetros da URL
     */
    public function show($params) {
        // Verificar permissão
        if (!Security::checkPermission('admin')) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        
        // Obter ID da opção
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        
        if (!$id) {
            $_SESSION['error'] = 'ID da opção de personalização inválido.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Obter dados da opção
        $customizationModel = new CustomizationModel();
        $option = $customizationModel->getDetails($id);
        
        if (!$option) {
            $_SESSION['error'] = 'Opção de personalização não encontrada.';
            header('Location: ' . BASE_URL . 'admin/customization');
            exit;
        }
        
        // Renderizar a view
        require_once VIEWS_PATH . '/admin/customization/show.php';
    }
}