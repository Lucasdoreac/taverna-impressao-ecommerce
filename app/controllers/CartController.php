<?php
/**
 * CartController - Controlador para gerenciamento do carrinho de compras
 */
class CartController {
    private $productModel;
    
    public function __construct() {
        $this->productModel = new ProductModel();
        
        // Inicializar sessão do carrinho se não existir
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = 0;
        }
    }
    
    /**
     * Exibe a página do carrinho de compras
     */
    public function index() {
        $cart_items = [];
        $subtotal = 0;
        
        // Obter detalhes de cada item no carrinho
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
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
                        'total' => $itemTotal
                    ];
                }
            }
        }
        
        // Calcular informações de frete e total
        $shipping_methods = json_decode(Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'shipping_methods'")[0]['setting_value'], true) ?? [];
        $shipping_cost = 0; // Será calculado dinamicamente na página
        $total = $subtotal + $shipping_cost;
        
        // Renderizar a view
        require_once VIEWS_PATH . '/cart.php';
    }
    
    /**
     * Adiciona um item ao carrinho
     */
    public function add() {
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
        
        // Validar produto
        $product = $this->productModel->find($product_id);
        if (!$product || !$product['is_active']) {
            $_SESSION['error'] = 'Produto não encontrado ou indisponível.';
            header('Location: ' . BASE_URL . 'produtos');
            exit;
        }
        
        // Verificar estoque
        if ($product['stock'] < $quantity) {
            $_SESSION['error'] = 'Quantidade solicitada não disponível em estoque.';
            header('Location: ' . BASE_URL . 'produto/' . $product['slug']);
            exit;
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
        
        // Adicionar ao carrinho
        $cart_item_id = uniqid();
        $_SESSION['cart'][] = [
            'cart_item_id' => $cart_item_id,
            'product_id' => $product_id,
            'quantity' => $quantity,
            'customization' => !empty($customizationData) ? $customizationData : null,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        // Atualizar contagem de itens no carrinho
        $this->updateCartCount();
        
        $_SESSION['success'] = 'Produto adicionado ao carrinho!';
        header('Location: ' . BASE_URL . 'carrinho');
        exit;
    }
    
    /**
     * Atualiza a quantidade de um item no carrinho
     */
    public function update() {
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
        
        // Atualizar quantidade no carrinho
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['cart_item_id'] === $cart_item_id) {
                // Verificar estoque
                $product = $this->productModel->find($item['product_id']);
                if ($product && $product['stock'] >= $quantity) {
                    $item['quantity'] = $quantity;
                } else {
                    $_SESSION['error'] = 'Quantidade solicitada não disponível em estoque.';
                }
                break;
            }
        }
        
        // Atualizar contagem de itens no carrinho
        $this->updateCartCount();
        
        // Retornar para o carrinho
        header('Location: ' . BASE_URL . 'carrinho');
        exit;
    }
    
    /**
     * Remove um item do carrinho
     */
    public function remove($params) {
        $cart_item_id = $params['id'] ?? null;
        
        if (!$cart_item_id) {
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        }
        
        // Remover item do carrinho
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['cart_item_id'] === $cart_item_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        
        // Reindexar array do carrinho
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        
        // Atualizar contagem de itens no carrinho
        $this->updateCartCount();
        
        $_SESSION['success'] = 'Item removido do carrinho!';
        header('Location: ' . BASE_URL . 'carrinho');
        exit;
    }
    
    /**
     * Limpa o carrinho
     */
    public function clear() {
        $_SESSION['cart'] = [];
        $_SESSION['cart_count'] = 0;
        
        $_SESSION['success'] = 'Carrinho esvaziado com sucesso!';
        header('Location: ' . BASE_URL . 'carrinho');
        exit;
    }
    
    /**
     * Atualiza a contagem total de itens no carrinho
     */
    private function updateCartCount() {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
        $_SESSION['cart_count'] = $count;
    }
}