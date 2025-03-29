<?php
/**
 * AdminOrderController - Controlador para gestão de pedidos no painel administrativo
 */
class AdminOrderController extends AdminController {
    
    /**
     * Lista todos os pedidos com filtros e paginação
     */
    public function index() {
        try {
            $db = Database::getInstance();
            
            // Parâmetros de filtro e paginação
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $itemsPerPage = 20;
            $offset = ($page - 1) * $itemsPerPage;
            
            // Construir a consulta SQL com filtros
            $where = [];
            $params = [];
            
            if (!empty($status)) {
                $where[] = "status = :status";
                $params['status'] = $status;
            }
            
            if (!empty($search)) {
                $where[] = "(order_number LIKE :search OR id = :search_id)";
                $params['search'] = "%{$search}%";
                $params['search_id'] = is_numeric($search) ? $search : 0;
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Contar total de registros para paginação
            $countSql = "SELECT COUNT(*) as total FROM orders {$whereClause}";
            $countResult = $db->select($countSql, $params);
            $totalItems = $countResult[0]['total'];
            
            // Obter pedidos com paginação
            $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    {$whereClause}
                    ORDER BY o.created_at DESC
                    LIMIT {$offset}, {$itemsPerPage}";
                    
            $orders = $db->select($sql, $params);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalItems / $itemsPerPage);
            $pagination = [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'itemsPerPage' => $itemsPerPage,
                'hasNextPage' => $page < $totalPages,
                'hasPrevPage' => $page > 1
            ];
            
            // Obter contagem por status para o filtro
            $statusCounts = [
                'all' => $totalItems,
                'pending' => 0,
                'processing' => 0,
                'shipped' => 0,
                'delivered' => 0,
                'canceled' => 0
            ];
            
            $statusCountSql = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
            $statusCountResult = $db->select($statusCountSql);
            
            foreach ($statusCountResult as $row) {
                $statusCounts[$row['status']] = $row['count'];
            }
            
            // Renderizar a view
            $viewData = [
                'orders' => $orders,
                'pagination' => $pagination,
                'statusCounts' => $statusCounts,
                'currentStatus' => $status,
                'search' => $search,
                'title' => 'Gerenciamento de Pedidos'
            ];
            
            $this->renderAdminView(VIEWS_PATH . '/admin/orders/index.php', $viewData);
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao listar pedidos no admin: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar a lista de pedidos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    
    /**
     * Exibe detalhes de um pedido específico
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function view($params) {
        try {
            $orderId = $params['id'] ?? '';
            
            if (empty($orderId)) {
                $_SESSION['error'] = 'ID do pedido não fornecido.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $db = Database::getInstance();
            
            // Obter dados do pedido
            $sql = "SELECT o.*, u.name as customer_name, u.email as customer_email
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.id = :id OR o.order_number = :order_number
                    LIMIT 1";
                    
            $order = $db->select($sql, [
                'id' => is_numeric($orderId) ? $orderId : 0,
                'order_number' => $orderId
            ]);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $order = $order[0];
            
            // Obter itens do pedido
            $sql = "SELECT oi.*, p.slug as product_slug, p.sku as product_sku
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = :order_id";
                    
            $items = $db->select($sql, ['order_id' => $order['id']]);
            
            // Obter endereço de entrega
            $sql = "SELECT * FROM addresses WHERE id = :id";
            $address = $db->select($sql, ['id' => $order['shipping_address_id']]);
            $address = !empty($address) ? $address[0] : null;
            
            // Histórico de status do pedido
            $sql = "SELECT * FROM order_history WHERE order_id = :order_id ORDER BY created_at DESC";
            $statusHistory = $db->select($sql, ['order_id' => $order['id']]);
            
            // Renderizar a view
            $viewData = [
                'order' => $order,
                'items' => $items,
                'address' => $address,
                'statusHistory' => $statusHistory,
                'title' => 'Detalhes do Pedido #' . $order['order_number']
            ];
            
            $this->renderAdminView(VIEWS_PATH . '/admin/orders/view.php', $viewData);
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao exibir detalhes do pedido no admin: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os detalhes do pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
    }
    
    /**
     * Atualiza o status de um pedido
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function updateStatus($params) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $orderId = $params['id'] ?? '';
            
            if (empty($orderId)) {
                $_SESSION['error'] = 'ID do pedido não fornecido.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $newStatus = $_POST['status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if (empty($newStatus)) {
                $_SESSION['error'] = 'Status não fornecido.';
                header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
                exit;
            }
            
            $db = Database::getInstance();
            
            // Obter dados do pedido
            $sql = "SELECT * FROM orders WHERE id = :id OR order_number = :order_number LIMIT 1";
            $order = $db->select($sql, [
                'id' => is_numeric($orderId) ? $orderId : 0,
                'order_number' => $orderId
            ]);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $order = $order[0];
            
            // Validar transição de status
            $validTransitions = [
                'pending' => ['processing', 'canceled'],
                'processing' => ['shipped', 'canceled'],
                'shipped' => ['delivered', 'canceled'],
                'delivered' => [],
                'canceled' => []
            ];
            
            if (!isset($validTransitions[$order['status']]) || !in_array($newStatus, $validTransitions[$order['status']])) {
                $_SESSION['error'] = 'Transição de status inválida.';
                header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
                exit;
            }
            
            // Atualizar status do pedido
            $db->update(
                'orders',
                [
                    'status' => $newStatus,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $order['id']]
            );
            
            // Registrar histórico de status
            $db->insert('order_history', [
                'order_id' => $order['id'],
                'status' => $newStatus,
                'notes' => $notes,
                'user_id' => $_SESSION['user']['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Se for cancelamento, retornar os itens ao estoque
            if ($newStatus === 'canceled') {
                $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
                $items = $db->select($sql, ['order_id' => $order['id']]);
                
                $productModel = new ProductModel();
                
                foreach ($items as $item) {
                    $product = $productModel->find($item['product_id']);
                    
                    if ($product) {
                        $newStock = $product['stock'] + $item['quantity'];
                        $productModel->update($item['product_id'], ['stock' => $newStock]);
                    }
                }
            }
            
            // Enviar notificação por email ao cliente
            $this->sendStatusUpdateEmail($order, $newStatus, $notes);
            
            $_SESSION['success'] = 'Status do pedido atualizado com sucesso.';
            header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao atualizar status do pedido no admin: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao atualizar o status do pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
            exit;
        }
    }
    
    /**
     * Exporta pedidos para CSV
     */
    public function export() {
        try {
            // Verificar permissões
            if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
                $_SESSION['error'] = 'Você não tem permissão para exportar pedidos.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $db = Database::getInstance();
            
            // Parâmetros de filtro
            $status = $_GET['status'] ?? '';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';
            
            // Construir a consulta SQL com filtros
            $where = [];
            $params = [];
            
            if (!empty($status)) {
                $where[] = "o.status = :status";
                $params['status'] = $status;
            }
            
            if (!empty($startDate)) {
                $where[] = "o.created_at >= :start_date";
                $params['start_date'] = $startDate . ' 00:00:00';
            }
            
            if (!empty($endDate)) {
                $where[] = "o.created_at <= :end_date";
                $params['end_date'] = $endDate . ' 23:59:59';
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Obter pedidos
            $sql = "SELECT o.id, o.order_number, o.created_at, o.status, o.payment_method, o.payment_status, 
                           o.shipping_method, o.shipping_cost, o.subtotal, o.discount, o.total,
                           u.name as customer_name, u.email as customer_email,
                           a.address, a.number, a.neighborhood, a.city, a.state, a.zipcode
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN addresses a ON o.shipping_address_id = a.id
                    {$whereClause}
                    ORDER BY o.created_at DESC";
                    
            $orders = $db->select($sql, $params);
            
            // Configurar cabeçalhos para download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="pedidos_' . date('Y-m-d') . '.csv"');
            
            // Criar arquivo CSV
            $output = fopen('php://output', 'w');
            
            // UTF-8 BOM para Excel reconhecer acentuação
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalhos do CSV
            $headers = [
                'ID', 'Número do Pedido', 'Data', 'Status', 'Método de Pagamento', 'Status do Pagamento',
                'Método de Envio', 'Custo de Envio', 'Subtotal', 'Desconto', 'Total',
                'Cliente', 'Email', 'Endereço', 'Número', 'Bairro', 'Cidade', 'Estado', 'CEP'
            ];
            
            fputcsv($output, $headers);
            
            // Dados
            foreach ($orders as $order) {
                $row = [
                    $order['id'],
                    $order['order_number'],
                    $order['created_at'],
                    $this->translateStatus($order['status']),
                    $this->translatePaymentMethod($order['payment_method']),
                    $this->translatePaymentStatus($order['payment_status']),
                    $order['shipping_method'],
                    'R$ ' . number_format($order['shipping_cost'], 2, ',', '.'),
                    'R$ ' . number_format($order['subtotal'], 2, ',', '.'),
                    'R$ ' . number_format($order['discount'], 2, ',', '.'),
                    'R$ ' . number_format($order['total'], 2, ',', '.'),
                    $order['customer_name'],
                    $order['customer_email'],
                    $order['address'],
                    $order['number'],
                    $order['neighborhood'],
                    $order['city'],
                    $order['state'],
                    $order['zipcode']
                ];
                
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao exportar pedidos: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao exportar os pedidos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
    }
    
    /**
     * Enviar email de notificação sobre atualização de status
     * 
     * @param array $order Dados do pedido
     * @param string $newStatus Novo status
     * @param string $notes Notas adicionais
     */
    private function sendStatusUpdateEmail($order, $newStatus, $notes) {
        // Implementação futura do sistema de email
        // Por enquanto, apenas registramos no log
        error_log("Email de atualização de status seria enviado para o pedido {$order['order_number']}, status: {$newStatus}");
    }
    
    /**
     * Traduz o status do pedido para português
     * 
     * @param string $status Status em inglês
     * @return string Status traduzido
     */
    private function translateStatus($status) {
        $translations = [
            'pending' => 'Pendente',
            'processing' => 'Em Processamento',
            'shipped' => 'Enviado',
            'delivered' => 'Entregue',
            'canceled' => 'Cancelado'
        ];
        
        return $translations[$status] ?? $status;
    }
    
    /**
     * Traduz o método de pagamento para português
     * 
     * @param string $method Método em inglês
     * @return string Método traduzido
     */
    private function translatePaymentMethod($method) {
        $translations = [
            'credit_card' => 'Cartão de Crédito',
            'boleto' => 'Boleto',
            'pix' => 'PIX'
        ];
        
        return $translations[$method] ?? $method;
    }
    
    /**
     * Traduz o status do pagamento para português
     * 
     * @param string $status Status em inglês
     * @return string Status traduzido
     */
    private function translatePaymentStatus($status) {
        $translations = [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'refunded' => 'Reembolsado',
            'canceled' => 'Cancelado'
        ];
        
        return $translations[$status] ?? $status;
    }
}
