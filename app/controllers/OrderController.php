<?php
/**
 * OrderController - Controlador para gerenciamento de pedidos
 */
class OrderController {
    private $userModel;
    private $orderModel;
    private $productModel;
    private $printQueueModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->printQueueModel = new PrintQueueModel();
        
        // Verificar se o usuário está logado para todas as ações exceto success
        if ($this->getCurrentAction() !== 'success') {
            $this->checkAuthentication();
        }
        
        // Verificar permissões de admin para ações administrativas
        $adminActions = ['viewPrintQueue', 'addAllItemsToQueue', 'updateOrderStatusFromQueue'];
        if (in_array($this->getCurrentAction(), $adminActions)) {
            $this->checkAdminPermission();
        }
    }
    
    /**
     * Exibe a página de sucesso após finalização do pedido
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function success($params) {
        try {
            $orderNumber = $params['id'] ?? '';
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Verificar se o usuário está logado
            if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Obter dados do pedido
            $userId = $_SESSION['user']['id'];
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            
            if (empty($order) || $order['user_id'] != $userId) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Verificar se há itens sob encomenda no pedido
            $has_custom_order = false;
            foreach ($items as $item) {
                if (!$item['is_stock_item']) {
                    $has_custom_order = true;
                    break;
                }
            }
            
            // Calcular data estimada de entrega para produtos sob encomenda
            $estimated_delivery_date = null;
            if ($has_custom_order && $order['estimated_print_time_hours'] > 0) {
                $estimated_delivery_date = $this->orderModel->calculateEstimatedCompletionDate($order['id']);
                if ($estimated_delivery_date) {
                    $date = new DateTime($estimated_delivery_date);
                    $estimated_delivery_date = $date->format('d/m/Y');
                }
            }
            
            // Obter endereço de entrega
            $sql = "SELECT * FROM addresses WHERE id = :id";
            $address = Database::getInstance()->select($sql, ['id' => $order['shipping_address_id']]);
            $address = !empty($address) ? $address[0] : null;
            
            // Renderizar view
            require_once VIEWS_PATH . '/order_success.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao exibir página de sucesso do pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página inicial
            $_SESSION['error'] = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
            header('Location: ' . BASE_URL);
            exit;
        }
    }
    
    /**
     * Lista todos os pedidos do usuário logado
     */
    public function index() {
        try {
            $userId = $_SESSION['user']['id'];
            
            // Obter todos os pedidos do usuário
            $orders = $this->orderModel->getOrdersByUser($userId);
            
            // Status traduzidos para exibição
            $statusLabels = [
                'pending' => 'Aguardando Envio',
                'validating' => 'Validando Modelo 3D',
                'printing' => 'Em Impressão',
                'finishing' => 'Em Acabamento',
                'shipped' => 'Enviado',
                'delivered' => 'Entregue',
                'canceled' => 'Cancelado'
            ];
            
            // Renderizar view
            require_once VIEWS_PATH . '/orders.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao listar pedidos: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página inicial
            $_SESSION['error'] = 'Ocorreu um erro ao carregar seus pedidos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta');
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
            $orderNumber = $params['id'] ?? '';
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $userId = $_SESSION['user']['id'];
            
            // Obter dados do pedido
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            
            if (empty($order) || $order['user_id'] != $userId) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Verificar se há itens sob encomenda no pedido
            $has_custom_order = false;
            foreach ($items as $item) {
                if (!$item['is_stock_item']) {
                    $has_custom_order = true;
                    break;
                }
            }
            
            // Status traduzidos para exibição
            $statusLabels = [
                'pending' => 'Aguardando Envio',
                'validating' => 'Validando Modelo 3D',
                'printing' => 'Em Impressão',
                'finishing' => 'Em Acabamento',
                'shipped' => 'Enviado',
                'delivered' => 'Entregue',
                'canceled' => 'Cancelado'
            ];
            
            // Calcular progresso da impressão se o pedido estiver sendo impresso
            $printing_progress = 0;
            $remaining_time = null;
            if ($order['status'] === 'printing' && $order['print_start_date']) {
                $start_time = new DateTime($order['print_start_date']);
                $now = new DateTime();
                $elapsed_seconds = $now->getTimestamp() - $start_time->getTimestamp();
                $total_seconds = $order['estimated_print_time_hours'] * 3600;
                
                if ($total_seconds > 0) {
                    $printing_progress = min(100, round(($elapsed_seconds / $total_seconds) * 100));
                    $remaining_seconds = max(0, $total_seconds - $elapsed_seconds);
                    $remaining_hours = floor($remaining_seconds / 3600);
                    $remaining_minutes = floor(($remaining_seconds % 3600) / 60);
                    $remaining_time = sprintf("%02d:%02d", $remaining_hours, $remaining_minutes);
                }
            }
            
            // Calcular data estimada de entrega para produtos sob encomenda
            $estimated_delivery_date = null;
            if ($has_custom_order && $order['estimated_print_time_hours'] > 0) {
                $estimated_delivery_date = $this->orderModel->calculateEstimatedCompletionDate($order['id']);
                if ($estimated_delivery_date) {
                    $date = new DateTime($estimated_delivery_date);
                    $estimated_delivery_date = $date->format('d/m/Y');
                }
            }
            
            // Obter endereço de entrega
            $sql = "SELECT * FROM addresses WHERE id = :id";
            $address = Database::getInstance()->select($sql, ['id' => $order['shipping_address_id']]);
            $address = !empty($address) ? $address[0] : null;
            
            // Obter histórico do pedido
            $notes = $this->orderModel->getNotes($order['id']);
            
            // Renderizar view
            require_once VIEWS_PATH . '/order_details.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao exibir detalhes do pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a lista de pedidos
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os detalhes do pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            exit;
        }
    }
    
    /**
     * Cancela um pedido (se permitido pelo status atual)
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function cancel($params) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $orderNumber = $params['id'] ?? '';
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $userId = $_SESSION['user']['id'];
            $reason = $_POST['reason'] ?? 'Cancelado pelo cliente';
            
            // Obter dados do pedido
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            
            if (empty($order) || $order['user_id'] != $userId) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            // Verificar se o pedido pode ser cancelado
            $cancelableStatuses = ['pending', 'validating']; // Apenas pedidos pendentes ou em validação podem ser cancelados
            
            if (!in_array($order['status'], $cancelableStatuses)) {
                $_SESSION['error'] = 'Este pedido não pode ser cancelado devido ao seu status atual.';
                header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
                exit;
            }
            
            // Obter itens do pedido para retornar ao estoque
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Cancelar o pedido
            $this->orderModel->cancelOrder($order['id'], $reason);
            
            // Retornar os itens ao estoque (apenas itens de pronta entrega)
            foreach ($items as $item) {
                if ($item['is_stock_item']) {
                    $product = $this->productModel->find($item['product_id']);
                    
                    if ($product) {
                        $newStock = $product['stock'] + $item['quantity'];
                        $this->productModel->update($item['product_id'], ['stock' => $newStock]);
                    }
                }
            }
            
            // Cancelar itens na fila de impressão se existirem
            $this->printQueueModel->cancelQueueItemsByOrderId($order['id'], $reason);
            
            $_SESSION['success'] = 'Pedido cancelado com sucesso.';
            header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao cancelar pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página do pedido
            $_SESSION['error'] = 'Ocorreu um erro ao cancelar o pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
            exit;
        }
    }
    
    /**
     * Visualiza itens na fila de impressão relacionados a um pedido específico (para administradores)
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function viewPrintQueue($params) {
        try {
            $orderId = $params['id'] ?? 0;
            
            if (empty($orderId)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            // Obter dados do pedido
            $order = $this->orderModel->getOrderById($orderId);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($orderId);
            
            // Obter itens na fila de impressão relacionados ao pedido
            $queueItems = $this->printQueueModel->getQueueItemsByOrderId($orderId);
            
            // Obter impressoras disponíveis
            $printers = $this->printQueueModel->getAllPrinters();
            
            // Renderizar view
            require_once VIEWS_PATH . '/admin/orders/print_queue.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao visualizar fila de impressão do pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a lista de pedidos
            $_SESSION['error'] = 'Ocorreu um erro ao carregar a fila de impressão do pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
    }
    
    /**
     * Adiciona todos os itens de um pedido à fila de impressão (para administradores)
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function addAllItemsToQueue($params) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $orderId = $params['id'] ?? 0;
            
            if (empty($orderId)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            // Obter dados do pedido
            $order = $this->orderModel->getOrderById($orderId);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            // Obter itens do pedido que precisam ser impressos (não são de estoque)
            $items = $this->orderModel->getOrderItems($orderId);
            $itemsToQueue = array_filter($items, function($item) {
                return !$item['is_stock_item'];
            });
            
            if (empty($itemsToQueue)) {
                $_SESSION['info'] = 'Este pedido não possui itens que precisam ser impressos sob demanda.';
                header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
                exit;
            }
            
            // Obter ID do usuário atual (administrador)
            $adminId = $_SESSION['user']['id'];
            
            // Adicionar cada item à fila
            $addedCount = 0;
            foreach ($itemsToQueue as $item) {
                // Verificar se o item já está na fila
                $existingItems = $this->printQueueModel->getQueueItems([
                    'order_item_id' => $item['id']
                ]);
                
                if (!empty($existingItems)) {
                    continue; // Item já está na fila, pular
                }
                
                // Obter detalhes do produto
                $product = $this->productModel->getProductById($item['product_id']);
                
                if (!$product) {
                    continue; // Produto não encontrado, pular
                }
                
                // Preparar dados para a fila
                $queueData = [
                    'order_id' => $orderId,
                    'order_item_id' => $item['id'],
                    'product_id' => $item['product_id'],
                    'estimated_print_time_hours' => $item['print_time_hours'] ?? $product['print_time_hours'],
                    'filament_type' => $item['selected_filament'] ?? $product['filament_type'],
                    'filament_usage_grams' => $product['filament_usage_grams'],
                    'scale' => $item['selected_scale'] ?? $product['scale'],
                    'customer_model_id' => $item['customer_model_id'],
                    'priority' => 5, // Prioridade média por padrão
                    'created_by' => $adminId
                ];
                
                // Verificar se existe uma cor de filamento selecionada
                if (!empty($item['selected_color'])) {
                    // Buscar ID da cor de filamento pelo nome
                    $filamentModel = new FilamentModel();
                    $filamentColor = $filamentModel->getFilamentColorByName($item['selected_color']);
                    
                    if ($filamentColor) {
                        $queueData['filament_color_id'] = $filamentColor['id'];
                    }
                }
                
                // Adicionar à fila
                $result = $this->printQueueModel->addToQueue($queueData);
                
                if ($result) {
                    $addedCount++;
                }
            }
            
            if ($addedCount > 0) {
                // Atualizar status do pedido para 'validating' se ainda estiver em 'pending'
                if ($order['status'] === 'pending') {
                    $this->orderModel->updateOrderStatus($orderId, 'validating', "Iniciada validação dos modelos para impressão 3D");
                }
                
                $_SESSION['success'] = $addedCount . ' item(s) adicionado(s) à fila de impressão com sucesso.';
            } else {
                $_SESSION['info'] = 'Não foi possível adicionar novos itens à fila de impressão. Os itens já estão na fila ou não são válidos.';
            }
            
            header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao adicionar itens à fila de impressão: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página de fila do pedido
            $_SESSION['error'] = 'Ocorreu um erro ao adicionar itens à fila de impressão. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
            exit;
        }
    }
    
    /**
     * Atualiza o status do pedido com base no status dos itens na fila de impressão (para administradores)
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function updateOrderStatusFromQueue($params) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            $orderId = $params['id'] ?? 0;
            
            if (empty($orderId)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            // Obter dados do pedido
            $order = $this->orderModel->getOrderById($orderId);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pedidos');
                exit;
            }
            
            // Obter itens do pedido que precisam ser impressos (não são de estoque)
            $items = $this->orderModel->getOrderItems($orderId);
            $itemsToCheck = array_filter($items, function($item) {
                return !$item['is_stock_item'];
            });
            
            if (empty($itemsToCheck)) {
                // Se todos os itens são de estoque, o pedido está pronto para envio
                if ($order['status'] !== 'shipped' && $order['status'] !== 'delivered' && $order['status'] !== 'canceled') {
                    $this->orderModel->updateOrderStatus($orderId, 'pending', "Todos os itens estão disponíveis em estoque, pronto para envio");
                }
                
                $_SESSION['info'] = 'Este pedido não possui itens que precisam ser impressos sob demanda.';
                header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
                exit;
            }
            
            // Obter itens na fila de impressão relacionados ao pedido
            $queueItems = $this->printQueueModel->getQueueItemsByOrderId($orderId);
            
            if (empty($queueItems)) {
                $_SESSION['info'] = 'Este pedido não possui itens na fila de impressão.';
                header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
                exit;
            }
            
            // Verificar status dos itens na fila
            $allCompleted = true;
            $anyPrinting = false;
            $anyValidating = false;
            $anyFailed = false;
            $anyCanceled = false;
            
            foreach ($queueItems as $item) {
                if ($item['status'] !== 'completed') {
                    $allCompleted = false;
                }
                
                if ($item['status'] === 'printing') {
                    $anyPrinting = true;
                } else if ($item['status'] === 'pending' || $item['status'] === 'validating') {
                    $anyValidating = true;
                } else if ($item['status'] === 'failed') {
                    $anyFailed = true;
                } else if ($item['status'] === 'canceled') {
                    $anyCanceled = true;
                }
            }
            
            // Determinar o novo status do pedido
            $newStatus = null;
            $statusNote = '';
            
            if ($allCompleted) {
                $newStatus = 'finishing';
                $statusNote = 'Todos os itens impressos com sucesso, em fase de acabamento';
            } else if ($anyPrinting) {
                $newStatus = 'printing';
                $statusNote = 'Itens em processo de impressão 3D';
            } else if ($anyValidating) {
                $newStatus = 'validating';
                $statusNote = 'Validando modelos para impressão 3D';
            } else if ($anyFailed && !$anyCanceled) {
                // Se há falhas mas o pedido não foi cancelado, manter o status atual
                $_SESSION['warning'] = 'Alguns itens na fila de impressão apresentaram falhas. Verifique e tome as providências necessárias.';
                header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
                exit;
            } else if ($anyCanceled) {
                // Se o pedido foi cancelado, não mudar o status
                $_SESSION['info'] = 'Este pedido foi cancelado.';
                header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
                exit;
            }
            
            // Atualizar status do pedido se necessário
            if ($newStatus && $newStatus !== $order['status']) {
                $this->orderModel->updateOrderStatus($orderId, $newStatus, $statusNote);
                
                // Se status for 'printing', atualizar a data de início da impressão
                if ($newStatus === 'printing' && !$order['print_start_date']) {
                    $this->orderModel->update($orderId, [
                        'print_start_date' => date('Y-m-d H:i:s')
                    ]);
                }
                
                $_SESSION['success'] = 'Status do pedido atualizado para "' . $newStatus . '" com base na fila de impressão.';
            } else {
                $_SESSION['info'] = 'O status do pedido não precisou ser atualizado.';
            }
            
            header('Location: ' . BASE_URL . 'admin/pedidos/' . $orderId . '/fila');
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao atualizar status do pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página de fila do pedido
            $_SESSION['error'] = 'Ocorreu um erro ao atualizar o status do pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/pedidos/' . $params['id'] . '/fila');
            exit;
        }
    }
    
    /**
     * Obtém a ação atual da requisição
     */
    private function getCurrentAction() {
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', trim($uri, '/'));
        
        // Verificar se é a ação 'success'
        if (count($parts) >= 2 && $parts[0] === 'pedido' && $parts[1] === 'sucesso') {
            return 'success';
        }
        
        // Verificar se é uma ação administrativa relacionada à fila de impressão
        if (count($parts) >= 4 && $parts[0] === 'admin' && $parts[1] === 'pedidos') {
            if ($parts[3] === 'fila') {
                return 'viewPrintQueue';
            } else if ($parts[3] === 'adicionar-a-fila') {
                return 'addAllItemsToQueue';
            } else if ($parts[2] === 'atualizar-status-do-pedido') {
                return 'updateOrderStatusFromQueue';
            }
        }
        
        return '';
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuthentication() {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'É necessário fazer login para acessar seus pedidos.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }
    
    /**
     * Verifica se o usuário tem permissões de administrador
     */
    private function checkAdminPermission() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['error'] = 'Você não tem permissão para acessar esta página.';
            header('Location: ' . BASE_URL);
            exit;
        }
    }
}