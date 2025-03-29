<?php
/**
 * CartController - Controlador para gerenciamento do carrinho de compras
 */
class CartController {
    private $productModel;
    private $cartModel;
    private $filamentModel;
    
    public function __construct() {
        try {
            $this->productModel = new ProductModel();
            $this->cartModel = new CartModel();
            $this->filamentModel = new FilamentModel();
            
            // Inicializar sessão do carrinho se não existir (compatibilidade)
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
                $_SESSION['cart_count'] = 0;
            }
            
            // Verificar se o usuário se logou recentemente e migrar o carrinho
            if (isset($_SESSION['migrate_cart']) && $_SESSION['migrate_cart'] === true) {
                $this->migrateCartAfterLogin();
                unset($_SESSION['migrate_cart']);
            }
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao inicializar CartController");
        }
    }
    
    /**
     * Exibe a página do carrinho de compras
     */
    public function index() {
        try {
            $cart_items = [];
            $subtotal = 0;
            $printing_time = 0;
            $filament_usage = 0;
            
            // Obter carrinho do banco de dados se o usuário estiver logado
            $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
            $sessionId = session_id();
            
            // Obter ou criar carrinho no banco de dados
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            if ($cart) {
                // Obter itens do carrinho do banco de dados
                $db_items = $this->cartModel->getItems($cart['id']);
                
                foreach ($db_items as $item) {
                    // Calcular preço considerando promoções
                    $price = $item['sale_price'] && $item['sale_price'] < $item['price'] 
                            ? $item['sale_price'] 
                            : $item['price'];
                    
                    // Calcular total do item
                    $itemTotal = $price * $item['quantity'];
                    $subtotal += $itemTotal;
                    
                    // Calcular tempo de impressão e uso de filamento para produtos sob encomenda
                    if (isset($item['availability']) && $item['availability'] === 'Sob Encomenda' && isset($item['print_time_hours'])) {
                        $printing_time += $item['print_time_hours'] * $item['quantity'];
                        $filament_usage += $item['filament_usage_grams'] * $item['quantity'];
                    }
                    
                    // Preparar dados de personalização
                    $customization = null;
                    if (!empty($item['customization_data'])) {
                        $customization = json_decode($item['customization_data'], true);
                    }
                    
                    // Adicionar ao array de itens formatados
                    $cart_items[] = [
                        'cart_item_id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'name' => $item['product_name'],
                        'slug' => $item['product_slug'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'image' => $item['image'],
                        'customization' => $customization,
                        'total' => $itemTotal,
                        'availability' => $item['availability'] ?? null,
                        'print_time_hours' => $item['print_time_hours'] ?? null,
                        'filament_type' => $item['filament_type'] ?? null,
                        'filament_usage_grams' => $item['filament_usage_grams'] ?? null,
                        'selected_scale' => $item['selected_scale'] ?? null,
                        'selected_filament' => $item['selected_filament'] ?? null,
                        'selected_color' => $item['selected_color'] ?? null,
                        'color_name' => $item['color_name'] ?? null,
                        'color_hex' => $item['color_hex'] ?? null,
                        'is_db_item' => true // Flag para indicar que é um item do banco
                    ];
                }
                
                // Atualizar contagem de itens no cabeçalho
                $_SESSION['cart_count'] = $this->cartModel->countItems($cart['id']);
            } 
            // Compatibilidade com sistema antigo (itens na sessão)
            else if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    try {
                        $product = $this->productModel->find($item['product_id']);
                        
                        if ($product) {
                            // Calcular preço (considerando promoções)
                            $price = $product['sale_price'] && $product['sale_price'] < $product['price'] 
                                   ? $product['sale_price'] 
                                   : $product['price'];
                            
                            // Obter imagem principal
                            $sql = "SELECT image FROM product_images WHERE product_id = :id AND is_main = 1 LIMIT 1";
                            $imageResult = Database::getInstance()->select($sql, ['id' => $product['id']]);
                            $image = !empty($imageResult) ? $imageResult[0]['image'] : null;
                            
                            // Calcular total do item
                            $itemTotal = $price * $item['quantity'];
                            $subtotal += $itemTotal;
                            
                            // Determinar disponibilidade
                            $availability = null;
                            if (isset($product['is_tested'])) {
                                $availability = ($product['is_tested'] && $product['stock'] > 0) ? 'Pronta Entrega' : 'Sob Encomenda';
                                
                                // Calcular tempo de impressão e uso de filamento para produtos sob encomenda
                                if ($availability === 'Sob Encomenda' && isset($product['print_time_hours'])) {
                                    $printing_time += $product['print_time_hours'] * $item['quantity'];
                                    $filament_usage += $product['filament_usage_grams'] * $item['quantity'];
                                }
                            }
                            
                            // Adicionar ao array de itens formatados
                            $cart_items[] = [
                                'cart_item_id' => $item['cart_item_id'],
                                'product_id' => $product['id'],
                                'name' => $product['name'],
                                'slug' => $product['slug'],
                                'price' => $price,
                                'quantity' => $item['quantity'],
                                'image' => $image,
                                'customization' => $item['customization'] ?? null,
                                'total' => $itemTotal,
                                'availability' => $availability,
                                'print_time_hours' => $product['print_time_hours'] ?? null,
                                'filament_type' => $product['filament_type'] ?? null,
                                'filament_usage_grams' => $product['filament_usage_grams'] ?? null,
                                'selected_scale' => $item['selected_scale'] ?? null,
                                'selected_filament' => $item['selected_filament'] ?? null,
                                'selected_color' => $item['selected_color'] ?? null,
                                'is_db_item' => false // Flag para indicar que é um item da sessão
                            ];
                        }
                    } catch (Exception $e) {
                        // Log do erro, mas continuar processando outros itens
                        error_log("Erro ao processar item do carrinho: " . $e->getMessage());
                    }
                }
            }
            
            // Obter informações de filamento e escalas disponíveis para opções de edição
            $filament_types = [
                'PLA' => 'PLA (Padrão)',
                'PETG' => 'PETG (Maior resistência)',
                'ABS' => 'ABS (Alta resistência)',
                'TPU' => 'TPU (Flexível)',
                'OUTROS' => 'Outros materiais'
            ];
            
            $available_scales = [];
            try {
                $scalesResult = Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'available_scales'");
                if (!empty($scalesResult)) {
                    $available_scales = json_decode($scalesResult[0]['setting_value'], true) ?? [];
                }
            } catch (Exception $e) {
                error_log("Erro ao obter escalas disponíveis: " . $e->getMessage());
            }
            
            // Obter cores de filamento disponíveis
            $filament_colors = [];
            try {
                // Obtém todas as cores disponíveis
                $filament_colors = $this->filamentModel->getAll();
            } catch (Exception $e) {
                error_log("Erro ao obter cores de filamento: " . $e->getMessage());
            }
            
            // Calcular informações de frete e total
            $shipping_methods = [];
            try {
                $shippingMethodsResult = Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'shipping_methods'");
                if (!empty($shippingMethodsResult)) {
                    $shipping_methods = json_decode($shippingMethodsResult[0]['setting_value'], true) ?? [];
                }
            } catch (Exception $e) {
                // Log do erro, mas continuar com array vazio
                error_log("Erro ao obter métodos de envio: " . $e->getMessage());
            }
            
            $shipping_cost = 0; // Será calculado dinamicamente na página
            $total = $subtotal + $shipping_cost;
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/cart.php')) {
                throw new Exception("View cart.php não encontrada");
            }
            
            // Renderizar a view
            require_once VIEWS_PATH . '/cart.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir carrinho");
        }
    }
    
    /**
     * Adiciona um item ao carrinho
     */
    public function add() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            // Obter dados do formulário
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
            $customization = isset($_POST['customization']) ? $_POST['customization'] : null;
            $customization_files = isset($_POST['customization_file']) ? $_POST['customization_file'] : null;
            
            // Opções específicas para impressão 3D
            $selected_scale = isset($_POST['selected_scale']) ? $_POST['selected_scale'] : null;
            $selected_filament = isset($_POST['selected_filament']) ? $_POST['selected_filament'] : null;
            $selected_color = isset($_POST['selected_color']) ? intval($_POST['selected_color']) : null;
            $customer_model_id = isset($_POST['customer_model_id']) ? intval($_POST['customer_model_id']) : null;
            
            // Validar produto
            $product = $this->productModel->find($product_id);
            if (!$product || !$product['is_active']) {
                $_SESSION['error'] = 'Produto não encontrado ou indisponível.';
                header('Location: ' . BASE_URL . 'produtos');
                exit;
            }
            
            // Verificar estoque para produtos testados
            if ($product['is_tested'] && $product['stock'] < $quantity) {
                $_SESSION['error'] = 'Quantidade solicitada não disponível em estoque.';
                header('Location: ' . BASE_URL . 'produto/' . $product['slug']);
                exit;
            }
            
            // Se não for selecionada uma escala, usar a escala padrão do produto
            if (empty($selected_scale) && isset($product['scale'])) {
                $selected_scale = $product['scale'];
            }
            
            // Se não for selecionado um tipo de filamento, usar o tipo padrão do produto
            if (empty($selected_filament) && isset($product['filament_type'])) {
                $selected_filament = $product['filament_type'];
            }
            
            // Combinar dados de personalização e arquivos
            $customizationData = [];
            if ($product['is_customizable']) {
                // Verificar se há opções de personalização
                $sql = "SELECT * FROM customization_options WHERE product_id = :id";
                $options = Database::getInstance()->select($sql, ['id' => $product_id]);
                
                if (!empty($options)) {
                    foreach ($options as $option) {
                        if ($option['required'] && 
                            ((!isset($customization[$option['id']]) || empty($customization[$option['id']])) && 
                             (!isset($customization_files[$option['id']]) || empty($customization_files[$option['id']])))) {
                            $_SESSION['error'] = 'Preencha todas as opções de personalização obrigatórias.';
                            header('Location: ' . BASE_URL . 'personalizar/' . $product['slug']);
                            exit;
                        }
                        
                        // Adicionar dados de personalização
                        if ($option['type'] === 'upload' && isset($customization_files[$option['id']])) {
                            $customizationData[$option['id']] = [
                                'type' => 'file',
                                'name' => $option['name'],
                                'value' => $customization_files[$option['id']]
                            ];
                        } elseif (isset($customization[$option['id']])) {
                            $customizationData[$option['id']] = [
                                'type' => $option['type'],
                                'name' => $option['name'],
                                'value' => $customization[$option['id']]
                            ];
                        }
                    }
                } else {
                    // Se não há opções específicas mas o produto é personalizável
                    if (isset($customization_files['default']) && !empty($customization_files['default'])) {
                        $customizationData['default'] = [
                            'type' => 'file',
                            'name' => 'Arquivo Personalizado',
                            'value' => $customization_files['default']
                        ];
                    }
                    
                    if (isset($customization['notes'])) {
                        $customizationData['notes'] = [
                            'type' => 'text',
                            'name' => 'Instruções Adicionais',
                            'value' => $customization['notes']
                        ];
                    }
                }
            }
            
            // Adicionar ao carrinho usando o CartModel
            $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
            $sessionId = session_id();
            
            // Obter ou criar carrinho
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            if ($cart) {
                // Adicionar item ao carrinho no banco de dados
                $this->cartModel->addItem(
                    $cart['id'], 
                    $product_id, 
                    $quantity, 
                    !empty($customizationData) ? $customizationData : null,
                    $selected_scale,
                    $selected_filament,
                    $selected_color,
                    $customer_model_id
                );
                
                // Atualizar contagem de itens no cabeçalho
                $_SESSION['cart_count'] = $this->cartModel->countItems($cart['id']);
                
                $_SESSION['success'] = 'Produto adicionado ao carrinho!';
            } else {
                // Fallback para sessão se falhar a criação do carrinho
                $cart_item_id = uniqid();
                $_SESSION['cart'][] = [
                    'cart_item_id' => $cart_item_id,
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'customization' => !empty($customizationData) ? $customizationData : null,
                    'selected_scale' => $selected_scale,
                    'selected_filament' => $selected_filament,
                    'selected_color' => $selected_color,
                    'customer_model_id' => $customer_model_id,
                    'added_at' => date('Y-m-d H:i:s')
                ];
                
                // Atualizar contagem de itens no carrinho
                $this->updateCartCountFromSession();
                
                $_SESSION['success'] = 'Produto adicionado ao carrinho! (Modo Sessão)';
            }
            
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao adicionar item ao carrinho");
        }
    }
    
    /**
     * Atualiza a quantidade de um item no carrinho
     */
    public function update() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            $cart_item_id = isset($_POST['cart_item_id']) ? $_POST['cart_item_id'] : null;
            $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
            
            if (!$cart_item_id) {
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            // Verificar se é um item do banco (ID numérico) ou da sessão (string)
            if (is_numeric($cart_item_id)) {
                // Item do banco de dados
                $item = $this->cartModel->findById($cart_item_id);
                if ($item) {
                    // Verificar estoque para produtos testados
                    $product = $this->productModel->find($item['product_id']);
                    if ($product && (!$product['is_tested'] || $product['stock'] >= $quantity)) {
                        $this->cartModel->updateItemQuantity($cart_item_id, $quantity);
                        
                        // Atualizar contagem no cabeçalho
                        $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                        $sessionId = session_id();
                        $cart = $this->cartModel->getOrCreate($userId, $sessionId);
                        $_SESSION['cart_count'] = $this->cartModel->countItems($cart['id']);
                    } else {
                        $_SESSION['error'] = 'Quantidade solicitada não disponível em estoque.';
                    }
                }
            } else {
                // Item da sessão - compatibilidade
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['cart_item_id'] === $cart_item_id) {
                        // Verificar estoque para produtos testados
                        $product = $this->productModel->find($item['product_id']);
                        if ($product && (!$product['is_tested'] || $product['stock'] >= $quantity)) {
                            $item['quantity'] = $quantity;
                        } else {
                            $_SESSION['error'] = 'Quantidade solicitada não disponível em estoque.';
                        }
                        break;
                    }
                }
                
                // Atualizar contagem de itens no carrinho
                $this->updateCartCountFromSession();
            }
            
            // Retornar para o carrinho
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao atualizar item no carrinho");
        }
    }
    
    /**
     * Atualiza as opções de impressão 3D de um item
     */
    public function updateOptions() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            $cart_item_id = isset($_POST['cart_item_id']) ? $_POST['cart_item_id'] : null;
            $selected_scale = isset($_POST['selected_scale']) ? $_POST['selected_scale'] : null;
            $selected_filament = isset($_POST['selected_filament']) ? $_POST['selected_filament'] : null;
            $selected_color = isset($_POST['selected_color']) ? intval($_POST['selected_color']) : null;
            
            if (!$cart_item_id) {
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            // Verificar se é um item do banco (ID numérico) ou da sessão (string)
            if (is_numeric($cart_item_id)) {
                // Item do banco de dados
                $this->cartModel->updatePrintingOptions($cart_item_id, $selected_scale, $selected_filament, $selected_color);
                $_SESSION['success'] = 'Opções de impressão atualizadas com sucesso!';
            } else {
                // Item da sessão - compatibilidade
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['cart_item_id'] === $cart_item_id) {
                        if ($selected_scale !== null) {
                            $item['selected_scale'] = $selected_scale;
                        }
                        if ($selected_filament !== null) {
                            $item['selected_filament'] = $selected_filament;
                        }
                        if ($selected_color !== null) {
                            $item['selected_color'] = $selected_color;
                        }
                        break;
                    }
                }
                $_SESSION['success'] = 'Opções de impressão atualizadas com sucesso! (Modo Sessão)';
            }
            
            // Retornar para o carrinho
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao atualizar opções de impressão 3D");
        }
    }
    
    /**
     * Remove um item do carrinho
     */
    public function remove($params) {
        try {
            $cart_item_id = $params['id'] ?? null;
            
            if (!$cart_item_id) {
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            // Verificar se é um item do banco (ID numérico) ou da sessão (string)
            if (is_numeric($cart_item_id)) {
                // Item do banco de dados
                $this->cartModel->removeItem($cart_item_id);
                
                // Atualizar contagem no cabeçalho
                $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
                $sessionId = session_id();
                $cart = $this->cartModel->getOrCreate($userId, $sessionId);
                if ($cart) {
                    $_SESSION['cart_count'] = $this->cartModel->countItems($cart['id']);
                }
            } else {
                // Item da sessão - compatibilidade
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['cart_item_id'] === $cart_item_id) {
                        unset($_SESSION['cart'][$key]);
                        break;
                    }
                }
                
                // Reindexar array do carrinho
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                
                // Atualizar contagem de itens no carrinho
                $this->updateCartCountFromSession();
            }
            
            $_SESSION['success'] = 'Item removido do carrinho!';
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao remover item do carrinho");
        }
    }
    
    /**
     * Limpa o carrinho
     */
    public function clear() {
        try {
            // Limpar carrinho no banco de dados
            $userId = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
            $sessionId = session_id();
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            if ($cart) {
                $this->cartModel->clearItems($cart['id']);
            }
            
            // Limpar também na sessão (compatibilidade)
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = 0;
            
            $_SESSION['success'] = 'Carrinho esvaziado com sucesso!';
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao limpar carrinho");
        }
    }
    
    /**
     * Migra o carrinho da sessão para o banco de dados após login
     */
    private function migrateCartAfterLogin() {
        try {
            if (empty($_SESSION['cart']) || !isset($_SESSION['user'])) {
                return;
            }
            
            $userId = $_SESSION['user']['id'];
            $sessionId = session_id();
            
            // Obter ou criar carrinho
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            if ($cart) {
                // Migrar itens da sessão para o banco
                $this->cartModel->migrateFromSession($_SESSION['cart'], $cart['id']);
                
                // Limpar carrinho da sessão
                $_SESSION['cart'] = [];
                
                // Atualizar contagem de itens
                $_SESSION['cart_count'] = $this->cartModel->countItems($cart['id']);
            }
        } catch (Exception $e) {
            error_log("Erro na migração do carrinho: " . $e->getMessage());
        }
    }
    
    /**
     * Atualiza a contagem total de itens no carrinho (da sessão)
     */
    private function updateCartCountFromSession() {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
        $_SESSION['cart_count'] = $count;
    }
    
    /**
     * Tratamento de erros centralizado
     */
    private function handleError(Exception $e, $context = '') {
        // Registrar erro no log
        error_log("$context: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Variáveis para a view de erro (visíveis apenas em ambiente de desenvolvimento)
        $error_message = $e->getMessage();
        $error_trace = $e->getTraceAsString();
        
        // Definir mensagem de erro para o usuário
        $_SESSION['error'] = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
        
        // Renderizar página de erro ou redirecionar
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            // Em desenvolvimento, mostrar detalhes do erro
            header("HTTP/1.0 500 Internal Server Error");
            include VIEWS_PATH . '/errors/500.php';
        } else {
            // Em produção, redirecionar para o carrinho
            header('Location: ' . BASE_URL . 'carrinho');
        }
        exit;
    }
}
