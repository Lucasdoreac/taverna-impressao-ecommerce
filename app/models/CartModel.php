<?php
/**
 * CartModel - Modelo para carrinhos de compras
 *
 * Este modelo gerencia a persistência de carrinhos de compras no banco de dados para usuários logados,
 * permitindo que eles continuem a compra em diferentes sessões ou dispositivos.
 */
class CartModel extends Model {
    protected $table = 'carts';
    protected $fillable = [
        'user_id', 'session_id', 'created_at', 'updated_at'
    ];
    
    /**
     * Obtém ou cria um carrinho para o usuário/sessão atual
     *
     * @param int|null $userId ID do usuário se estiver logado
     * @param string $sessionId ID da sessão atual
     * @return array Dados do carrinho
     */
    public function getOrCreate($userId = null, $sessionId) {
        // Primeiro, tenta encontrar por usuário (se estiver logado)
        if ($userId) {
            $cart = $this->findByUser($userId);
            if ($cart) {
                return $cart;
            }
        }
        
        // Se não encontrou por usuário, tenta encontrar por sessão
        $cart = $this->findBySession($sessionId);
        if ($cart) {
            // Se o usuário está logado agora, mas o carrinho não estava vinculado a ele
            if ($userId && $cart['user_id'] === null) {
                // Vincular o carrinho ao usuário
                $this->update($cart['id'], [
                    'user_id' => $userId,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $cart['user_id'] = $userId;
            }
            
            return $cart;
        }
        
        // Se não encontrou nenhum carrinho, cria um novo
        $cartId = $this->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->find($cartId);
    }
    
    /**
     * Busca um carrinho pelo ID do usuário
     *
     * @param int $userId ID do usuário
     * @return array|null Dados do carrinho ou null se não encontrado
     */
    public function findByUser($userId) {
        return $this->findBy('user_id', $userId);
    }
    
    /**
     * Busca um carrinho pelo ID da sessão
     *
     * @param string $sessionId ID da sessão
     * @return array|null Dados do carrinho ou null se não encontrado
     */
    public function findBySession($sessionId) {
        return $this->findBy('session_id', $sessionId);
    }
    
    /**
     * Obtém os itens de um carrinho específico
     *
     * @param int $cartId ID do carrinho
     * @return array Itens do carrinho com dados de produto
     */
    public function getItems($cartId) {
        $sql = "SELECT ci.*, p.name as product_name, p.slug as product_slug, p.price, p.sale_price,
                (SELECT image FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = :cart_id
                ORDER BY ci.created_at DESC";
        
        return $this->db()->select($sql, ['cart_id' => $cartId]);
    }
    
    /**
     * Busca um item do carrinho pelo ID
     *
     * @param int $itemId ID do item
     * @return array|null Item do carrinho ou null se não encontrado
     */
    public function findById($itemId) {
        $sql = "SELECT ci.*, p.name as product_name, p.slug as product_slug, p.price, p.sale_price,
                (SELECT image FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.id = :id
                LIMIT 1";
        
        $result = $this->db()->select($sql, ['id' => $itemId]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Adiciona um item ao carrinho
     *
     * @param int $cartId ID do carrinho
     * @param int $productId ID do produto
     * @param int $quantity Quantidade
     * @param array|null $customizationData Dados de personalização
     * @return int ID do item adicionado
     */
    public function addItem($cartId, $productId, $quantity = 1, $customizationData = null) {
        // Verificar se o item já existe no carrinho
        $sql = "SELECT * FROM cart_items 
                WHERE cart_id = :cart_id AND product_id = :product_id 
                AND customization_data " . ($customizationData ? "= :customization_data" : "IS NULL");
        
        $params = [
            'cart_id' => $cartId,
            'product_id' => $productId
        ];
        
        if ($customizationData) {
            $params['customization_data'] = json_encode($customizationData);
        }
        
        $existingItem = $this->db()->select($sql, $params);
        
        if ($existingItem) {
            // Se o item já existe, aumenta a quantidade
            $itemId = $existingItem[0]['id'];
            $newQuantity = $existingItem[0]['quantity'] + $quantity;
            
            $this->db()->update(
                'cart_items',
                ['quantity' => $newQuantity, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $itemId]
            );
            
            return $itemId;
        } else {
            // Se o item não existe, adiciona um novo
            return $this->db()->insert('cart_items', [
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'customization_data' => $customizationData ? json_encode($customizationData) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Atualiza a quantidade de um item no carrinho
     *
     * @param int $itemId ID do item
     * @param int $quantity Nova quantidade
     * @return bool Sucesso da operação
     */
    public function updateItemQuantity($itemId, $quantity) {
        $this->db()->update(
            'cart_items',
            ['quantity' => $quantity, 'updated_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $itemId]
        );
        
        return true;
    }
    
    /**
     * Remove um item do carrinho
     *
     * @param int $itemId ID do item
     * @return bool Sucesso da operação
     */
    public function removeItem($itemId) {
        $this->db()->delete('cart_items', 'id = :id', ['id' => $itemId]);
        return true;
    }
    
    /**
     * Remove todos os itens de um carrinho
     *
     * @param int $cartId ID do carrinho
     * @return bool Sucesso da operação
     */
    public function clearItems($cartId) {
        $this->db()->delete('cart_items', 'cart_id = :cart_id', ['cart_id' => $cartId]);
        return true;
    }
    
    /**
     * Calcula o subtotal de um carrinho
     *
     * @param int $cartId ID do carrinho
     * @return float Valor subtotal
     */
    public function calculateSubtotal($cartId) {
        $items = $this->getItems($cartId);
        $subtotal = 0;
        
        foreach ($items as $item) {
            // Usar preço de venda se disponível e menor que o preço normal
            $price = ($item['sale_price'] && $item['sale_price'] < $item['price'])
                ? $item['sale_price']
                : $item['price'];
            
            $subtotal += $price * $item['quantity'];
        }
        
        return $subtotal;
    }
    
    /**
     * Migrando o carrinho de sessão para o banco de dados
     *
     * @param array $sessionCart Carrinho na sessão
     * @param int $cartId ID do carrinho no banco
     * @return bool Sucesso da operação
     */
    public function migrateFromSession($sessionCart, $cartId) {
        // Limpar itens existentes
        $this->clearItems($cartId);
        
        // Adicionar itens da sessão
        foreach ($sessionCart as $item) {
            $this->addItem(
                $cartId,
                $item['product_id'],
                $item['quantity'],
                $item['customization'] ?? null
            );
        }
        
        return true;
    }
    
    /**
     * Obtém o número total de itens em um carrinho
     *
     * @param int $cartId ID do carrinho
     * @return int Número total de itens
     */
    public function countItems($cartId) {
        $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = :cart_id";
        $result = $this->db()->select($sql, ['cart_id' => $cartId]);
        
        return (int)($result[0]['total'] ?? 0);
    }
}