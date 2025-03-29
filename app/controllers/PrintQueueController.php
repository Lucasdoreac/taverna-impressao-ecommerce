<?php
/**
 * Controlador para gerenciamento da fila de impressão 3D
 * 
 * Este controlador gerencia a fila de impressão 3D, incluindo visualização da fila,
 * atualização de status, priorização, alocação de impressoras e histórico de eventos.
 */
class PrintQueueController extends Controller {
    private $printQueueModel;
    private $orderModel;
    private $authHelper;
    
    public function __construct() {
        $this->printQueueModel = new PrintQueueModel();
        $this->orderModel = new OrderModel();
        $this->authHelper = new AuthHelper();
        
        // Verificar autenticação para todas as ações exceto 'customerTrack'
        $allowedMethods = ['customerTrack'];
        $currentMethod = isset($_GET['action']) ? $_GET['action'] : 'index';
        
        if (!in_array($currentMethod, $allowedMethods)) {
            // Verificar se usuário está logado e é administrador
            if (!$this->authHelper->isLoggedIn()) {
                header('Location: ' . BASE_URL . 'auth/login');
                exit;
            }
            
            $currentUser = $this->authHelper->getUser();
            if ($currentUser['role'] !== 'admin') {
                $this->view('errors/403', [
                    'message' => 'Acesso negado. Somente administradores podem acessar esta área.'
                ]);
                exit;
            }
        }
    }
    
    /**
     * Página principal da fila de impressão - visão geral
     */
    public function index() {
        // Obter filtros da URL ou valores padrão
        $filters = [
            'status' => isset($_GET['status']) ? $_GET['status'] : '',
            'printer_id' => isset($_GET['printer_id']) ? $_GET['printer_id'] : '',
            'order_by' => isset($_GET['order_by']) ? $_GET['order_by'] : 'priority',
            'order_dir' => isset($_GET['order_dir']) ? $_GET['order_dir'] : 'asc'
        ];
        
        // Obter todos os itens da fila aplicando os filtros
        $queueItems = $this->printQueueModel->getQueueItems($filters);
        
        // Obter todas as impressoras para o filtro
        $printers = $this->printQueueModel->getAllPrinters();
        
        // Estatísticas da fila
        $stats = $this->getQueueStats();
        
        // Renderizar a view
        $this->view('admin/print_queue/index', [
            'queueItems' => $queueItems,
            'printers' => $printers,
            'filters' => $filters,
            'stats' => $stats,
            'title' => 'Gerenciamento da Fila de Impressão 3D'
        ]);
    }
    
    /**
     * Exibe detalhes de um item da fila, incluindo histórico
     * 
     * @param int $id ID do item na fila
     */
    public function details($id) {
        // Obter detalhes do item
        $queueItem = $this->printQueueModel->getQueueItemById($id);
        
        if (!$queueItem) {
            $this->view('errors/404', [
                'message' => 'Item da fila não encontrado.'
            ]);
            return;
        }
        
        // Obter o histórico do item
        $history = $this->printQueueModel->getQueueItemHistory($id);
        
        // Obter informações do pedido e do cliente
        $order = $this->orderModel->getOrderById($queueItem['order_id']);
        
        // Obter todas as impressoras para o formulário de atribuição
        $printers = $this->printQueueModel->getAllPrinters();
        
        // Renderizar a view
        $this->view('admin/print_queue/details', [
            'queueItem' => $queueItem,
            'history' => $history,
            'order' => $order,
            'printers' => $printers,
            'title' => 'Detalhes do Item na Fila de Impressão'
        ]);
    }
    
    /**
     * Atualiza o status de um item na fila
     */
    public function updateStatus() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Validar dados
        if ($queueId === 0 || empty($status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Obter ID do usuário atual
        $currentUser = $this->authHelper->getUser();
        $userId = $currentUser['id'];
        
        // Atualizar status
        $result = $this->printQueueModel->updateStatus($queueId, $status, $userId, $notes);
        
        if ($result) {
            // Se o status é 'printing' e o item tem uma impressora atribuída,
            // atualizar o status da impressora
            if ($status === 'printing') {
                $queueItem = $this->printQueueModel->getQueueItemById($queueId);
                if ($queueItem && !empty($queueItem['printer_id'])) {
                    $this->printQueueModel->updatePrinterStatus($queueItem['printer_id'], 'printing');
                }
            }
            // Se o status é 'completed' ou 'failed' e o item tem uma impressora atribuída,
            // tornar a impressora disponível novamente
            else if ($status === 'completed' || $status === 'failed') {
                $queueItem = $this->printQueueModel->getQueueItemById($queueId);
                if ($queueItem && !empty($queueItem['printer_id'])) {
                    $this->printQueueModel->updatePrinterStatus($queueItem['printer_id'], 'available');
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status']);
        }
        exit;
    }
    
    /**
     * Atribui uma impressora a um item na fila
     */
    public function assignPrinter() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
        $printerId = isset($_POST['printer_id']) ? (int)$_POST['printer_id'] : 0;
        
        // Validar dados
        if ($queueId === 0 || $printerId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Obter ID do usuário atual
        $currentUser = $this->authHelper->getUser();
        $userId = $currentUser['id'];
        
        // Atribuir impressora
        $result = $this->printQueueModel->assignPrinter($queueId, $printerId, $userId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Impressora atribuída com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atribuir impressora']);
        }
        exit;
    }
    
    /**
     * Atualiza a prioridade de um item na fila
     */
    public function updatePriority() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
        $priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
        
        // Validar dados
        if ($queueId === 0 || $priority < 1 || $priority > 10) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Obter ID do usuário atual
        $currentUser = $this->authHelper->getUser();
        $userId = $currentUser['id'];
        
        // Atualizar prioridade
        $result = $this->printQueueModel->updatePriority($queueId, $priority, $userId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Prioridade atualizada com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar prioridade']);
        }
        exit;
    }
    
    /**
     * Adiciona um novo item à fila de impressão a partir de um item de pedido
     */
    public function addToQueue() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $orderItemId = isset($_POST['order_item_id']) ? (int)$_POST['order_item_id'] : 0;
        
        // Validar dados
        if ($orderId === 0 || $orderItemId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Verificar se o item já está na fila
        $existingItems = $this->printQueueModel->getQueueItems([
            'order_item_id' => $orderItemId
        ]);
        
        if (!empty($existingItems)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Este item já está na fila de impressão']);
            exit;
        }
        
        // Obter detalhes do item do pedido
        $orderItem = $this->orderModel->getOrderItemById($orderItemId);
        
        if (!$orderItem) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item do pedido não encontrado']);
            exit;
        }
        
        // Obter detalhes do produto
        $productModel = new ProductModel();
        $product = $productModel->getProductById($orderItem['product_id']);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
            exit;
        }
        
        // Obter ID do usuário atual
        $currentUser = $this->authHelper->getUser();
        $userId = $currentUser['id'];
        
        // Preparar dados para a fila
        $queueData = [
            'order_id' => $orderId,
            'order_item_id' => $orderItemId,
            'product_id' => $orderItem['product_id'],
            'estimated_print_time_hours' => $orderItem['print_time_hours'] ?? $product['print_time_hours'],
            'filament_type' => $orderItem['selected_filament'] ?? $product['filament_type'],
            'filament_usage_grams' => $product['filament_usage_grams'],
            'scale' => $orderItem['selected_scale'] ?? $product['scale'],
            'customer_model_id' => $orderItem['customer_model_id'],
            'priority' => 5, // Prioridade média por padrão
            'created_by' => $userId
        ];
        
        // Verificar se existe uma cor de filamento selecionada
        if (!empty($orderItem['selected_color'])) {
            // Buscar ID da cor de filamento pelo nome
            $filamentModel = new FilamentModel();
            $filamentColor = $filamentModel->getFilamentColorByName($orderItem['selected_color']);
            
            if ($filamentColor) {
                $queueData['filament_color_id'] = $filamentColor['id'];
            }
        }
        
        // Adicionar à fila
        $result = $this->printQueueModel->addToQueue($queueData);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Item adicionado à fila com sucesso', 'queue_id' => $result]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar item à fila']);
        }
        exit;
    }
    
    /**
     * Gerencia as impressoras disponíveis
     */
    public function printers() {
        // Obter todas as impressoras
        $printers = $this->printQueueModel->getAllPrinters();
        
        // Renderizar a view
        $this->view('admin/print_queue/printers', [
            'printers' => $printers,
            'title' => 'Gerenciamento de Impressoras 3D'
        ]);
    }
    
    /**
     * Atualiza o status de uma impressora
     */
    public function updatePrinterStatus() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $printerId = isset($_POST['printer_id']) ? (int)$_POST['printer_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        
        // Validar dados
        if ($printerId === 0 || empty($status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Atualizar status
        $result = $this->printQueueModel->updatePrinterStatus($printerId, $status);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Status da impressora atualizado com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status da impressora']);
        }
        exit;
    }
    
    /**
     * Adiciona uma nova impressora
     */
    public function addPrinter() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $model = isset($_POST['model']) ? $_POST['model'] : '';
        $maxWidth = isset($_POST['max_width']) ? (int)$_POST['max_width'] : 0;
        $maxDepth = isset($_POST['max_depth']) ? (int)$_POST['max_depth'] : 0;
        $maxHeight = isset($_POST['max_height']) ? (int)$_POST['max_height'] : 0;
        $filamentTypes = isset($_POST['filament_types']) ? $_POST['filament_types'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Validar dados
        if (empty($name) || empty($model) || $maxWidth <= 0 || $maxDepth <= 0 || $maxHeight <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Preparar dados da impressora
        $printerData = [
            'name' => $name,
            'model' => $model,
            'max_width' => $maxWidth,
            'max_depth' => $maxDepth,
            'max_height' => $maxHeight,
            'filament_types' => $filamentTypes,
            'status' => 'available',
            'notes' => $notes
        ];
        
        // Adicionar impressora
        $result = $this->printQueueModel->addPrinter($printerData);
        
        if ($result) {
            // Redirecionar para a página de impressoras
            header('Location: ' . BASE_URL . 'print_queue/printers?success=1');
            exit;
        } else {
            // Redirecionar com erro
            header('Location: ' . BASE_URL . 'print_queue/printers?error=1');
            exit;
        }
    }
    
    /**
     * Lista de trabalhos pendentes para um cliente
     */
    public function customerJobs() {
        // Obter ID do usuário atual
        $currentUser = $this->authHelper->getUser();
        $userId = $currentUser['id'];
        
        // Obter pedidos do cliente
        $orders = $this->orderModel->getOrdersByUserId($userId);
        
        // Preparar array de IDs de pedidos
        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order['id'];
        }
        
        // Obter itens da fila para os pedidos do cliente
        $queueItems = [];
        if (!empty($orderIds)) {
            $queueItems = $this->printQueueModel->getQueueItems([
                'customer_id' => $userId
            ]);
        }
        
        // Obter notificações não lidas
        $notifications = $this->printQueueModel->getUnreadNotifications($userId);
        
        // Renderizar a view
        $this->view('customer/print_jobs', [
            'orders' => $orders,
            'queueItems' => $queueItems,
            'notifications' => $notifications,
            'title' => 'Meus Trabalhos de Impressão 3D'
        ]);
    }
    
    /**
     * Página de rastreamento para clientes (acessível sem login)
     * 
     * @param string $orderNumber Número do pedido
     * @param string $email Email do cliente
     */
    public function customerTrack($orderNumber = null, $email = null) {
        // Verificar se os parâmetros foram fornecidos via GET
        if (!$orderNumber && isset($_GET['order_number'])) {
            $orderNumber = $_GET['order_number'];
        }
        
        if (!$email && isset($_GET['email'])) {
            $email = $_GET['email'];
        }
        
        // Se não houver parâmetros, mostrar o formulário de rastreamento
        if (!$orderNumber || !$email) {
            $this->view('customer/track_form', [
                'title' => 'Rastrear Impressão 3D'
            ]);
            return;
        }
        
        // Verificar se o pedido existe e pertence ao email fornecido
        $order = $this->orderModel->getOrderByNumberAndEmail($orderNumber, $email);
        
        if (!$order) {
            $this->view('customer/track_form', [
                'title' => 'Rastrear Impressão 3D',
                'error' => 'Pedido não encontrado ou email incorreto.'
            ]);
            return;
        }
        
        // Obter itens da fila para o pedido
        $queueItems = $this->printQueueModel->getQueueItemsByOrderId($order['id']);
        
        // Renderizar a view
        $this->view('customer/track_result', [
            'order' => $order,
            'queueItems' => $queueItems,
            'title' => 'Status da Impressão 3D - Pedido #' . $orderNumber
        ]);
    }
    
    /**
     * Marca uma notificação como lida
     */
    public function markNotificationRead() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }
        
        // Obter dados da requisição
        $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        
        // Validar dados
        if ($notificationId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }
        
        // Obter ID do usuário atual
        $currentUser = $this->authHelper->getUser();
        $userId = $currentUser['id'];
        
        // Marcar como lida
        $result = $this->printQueueModel->markNotificationAsRead($notificationId, $userId);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Notificação marcada como lida']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao marcar notificação como lida']);
        }
        exit;
    }
    
    /**
     * Calcula estatísticas da fila de impressão
     * 
     * @return array Estatísticas da fila
     */
    private function getQueueStats() {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'scheduled' => 0,
            'printing' => 0,
            'completed' => 0,
            'failed' => 0,
            'canceled' => 0,
            'printers_total' => 0,
            'printers_available' => 0,
            'printers_printing' => 0,
            'printers_maintenance' => 0,
            'estimated_time_total' => 0,
            'estimated_filament_total' => 0
        ];
        
        // Obter todos os itens da fila
        $queueItems = $this->printQueueModel->getQueueItems();
        
        // Calcular estatísticas
        $stats['total'] = count($queueItems);
        
        foreach ($queueItems as $item) {
            // Contagem por status
            if (isset($stats[$item['status']])) {
                $stats[$item['status']]++;
            }
            
            // Tempo estimado total
            if (isset($item['estimated_print_time_hours'])) {
                $stats['estimated_time_total'] += $item['estimated_print_time_hours'];
            }
            
            // Uso estimado de filamento
            if (isset($item['filament_usage_grams'])) {
                $stats['estimated_filament_total'] += $item['filament_usage_grams'];
            }
        }
        
        // Obter estatísticas de impressoras
        $printers = $this->printQueueModel->getAllPrinters();
        
        $stats['printers_total'] = count($printers);
        
        foreach ($printers as $printer) {
            if ($printer['status'] === 'available') {
                $stats['printers_available']++;
            } else if ($printer['status'] === 'printing') {
                $stats['printers_printing']++;
            } else if ($printer['status'] === 'maintenance') {
                $stats['printers_maintenance']++;
            }
        }
        
        return $stats;
    }
}
