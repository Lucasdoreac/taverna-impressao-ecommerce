<?php
/**
 * PrintMonitorController - Controlador para monitoramento de impressões 3D
 * 
 * Este controlador gerencia a interface de usuário para clientes visualizarem
 * o status de suas impressões 3D, oferecendo atualizações em tempo real.
 * 
 * @package Controllers
 */
class PrintMonitorController extends Controller {
    
    /**
     * Propriedades do controlador
     */
    protected $printStatusModel;
    protected $orderModel;
    protected $printHelpers = [];
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        
        // Carregar modelos necessários
        $this->printStatusModel = new PrintStatusModel();
        $this->orderModel = new OrderModel();
        
        // Carregar helpers
        $this->printHelpers['status'] = new PrintStatusHelper();
        
        // Verificar autenticação para todas as ações exceto API
        $this->middleware('auth', ['except' => ['apiStatus']]);
    }
    
    /**
     * Ação padrão - Lista de impressões em andamento para o usuário atual
     */
    public function index() {
        // Obter ID do usuário atual
        $userId = $this->session->get('user_id');
        
        if (!$userId) {
            $this->redirect('login');
            return;
        }
        
        // Obter todos os pedidos do usuário
        $orders = $this->orderModel->getUserOrders($userId);
        
        // Coletar IDs de todos os pedidos
        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = $order['id'];
        }
        
        // Obter impressões ativas relacionadas a esses pedidos
        $activePrints = $this->getPrintsByOrderIds($orderIds, true);
        
        // Obter impressões concluídas recentes
        $completedPrints = $this->getPrintsByOrderIds($orderIds, false, true);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Monitoramento de Impressões',
            'activePrints' => $activePrints,
            'completedPrints' => $completedPrints,
            'css' => PrintStatusHelper::includeCSS(),
            'js' => PrintStatusHelper::includeJavaScript(30) // Atualizar a cada 30 segundos
        ];
        
        // Carregar a view
        $this->view->render('print_monitor/index', $data);
    }
    
    /**
     * Exibe os detalhes de um status de impressão específico
     * 
     * @param int $id ID do status de impressão
     */
    public function details($id) {
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->redirect('print-monitor');
            return;
        }
        
        // Obter ID do usuário atual
        $userId = $this->session->get('user_id');
        
        if (!$userId) {
            $this->redirect('login');
            return;
        }
        
        // Obter detalhes do status
        $printStatus = $this->printStatusModel->getDetailedStatus($id);
        
        // Verificar se o status existe
        if (!$printStatus) {
            $this->session->setFlash('error', 'Status de impressão não encontrado.');
            $this->redirect('print-monitor');
            return;
        }
        
        // Verificar se o usuário tem permissão para ver este status
        if (!$this->userOwnsOrder($userId, $printStatus['order_id'])) {
            $this->session->setFlash('error', 'Você não tem permissão para acessar este status de impressão.');
            $this->redirect('print-monitor');
            return;
        }
        
        // Obter métricas para gráficos
        $metrics = $this->printStatusModel->getRecentMetrics($id, 50);
        $preparedMetrics = $this->prepareMetricsForChart($metrics);
        
        // Obter histórico de mensagens visíveis para o cliente
        $messages = $this->printStatusModel->getStatusMessages($id, true, 30);
        
        // Obter histórico de atualizações
        $updates = $this->printStatusModel->getStatusUpdates($id, 20);
        
        // Carregar dados para a view
        $data = [
            'title' => 'Detalhes da Impressão',
            'printStatus' => $printStatus,
            'metrics' => $preparedMetrics,
            'messages' => $messages,
            'updates' => $updates,
            'css' => PrintStatusHelper::includeCSS(),
            'js' => PrintStatusHelper::includeJavaScript(15) // Atualizar a cada 15 segundos
        ];
        
        // Renderizar a view
        $this->view->render('print_monitor/details', $data);
    }
    
    /**
     * Exibe os status de impressão para um pedido específico
     * 
     * @param int $orderId ID do pedido
     */
    public function order($orderId) {
        // Validar ID
        $orderId = intval($orderId);
        if ($orderId <= 0) {
            $this->redirect('account/orders');
            return;
        }
        
        // Obter ID do usuário atual
        $userId = $this->session->get('user_id');
        
        if (!$userId) {
            $this->redirect('login');
            return;
        }
        
        // Verificar se o usuário tem permissão para ver este pedido
        if (!$this->userOwnsOrder($userId, $orderId)) {
            $this->session->setFlash('error', 'Você não tem permissão para acessar este pedido.');
            $this->redirect('account/orders');
            return;
        }
        
        // Obter detalhes do pedido
        $order = $this->orderModel->find($orderId);
        
        // Obter status de impressão para este pedido
        $printStatuses = $this->printStatusModel->getByOrderId($orderId);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Status de Impressão do Pedido #' . $orderId,
            'order' => $order,
            'printStatuses' => $printStatuses,
            'css' => PrintStatusHelper::includeCSS(),
            'js' => PrintStatusHelper::includeJavaScript(30) // Atualizar a cada 30 segundos
        ];
        
        // Renderizar a view
        $this->view->render('print_monitor/order', $data);
    }
    
    /**
     * Endpoint da API para obter status de impressão em formato JSON
     * 
     * @param int $id ID do status de impressão
     */
    public function apiStatus($id) {
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
            return;
        }
        
        // Verificar se é uma requisição AJAX
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Obter status
        $printStatus = $this->printStatusModel->getDetailedStatus($id);
        
        // Verificar se o status existe
        if (!$printStatus) {
            $this->jsonResponse(['error' => 'Status não encontrado'], 404);
            return;
        }
        
        // Verificar autenticação para clientes
        $userId = $this->session->get('user_id');
        if ($userId && !$this->isAdmin()) {
            // Verificar se o usuário tem permissão para ver este status
            if (!$this->userOwnsOrder($userId, $printStatus['order_id'])) {
                $this->jsonResponse(['error' => 'Acesso negado'], 403);
                return;
            }
        } else if (!$userId && !$this->isPublicAccessAllowed($id)) {
            // Verificar se o acesso público é permitido
            $this->jsonResponse(['error' => 'Autenticação necessária'], 401);
            return;
        }
        
        // Preparar dados para resposta
        $response = [
            'id' => $printStatus['id'],
            'status' => $printStatus['status'],
            'formatted_status' => $printStatus['formatted_status'],
            'progress' => $printStatus['progress_percentage'],
            'started_at' => $printStatus['started_at'],
            'estimated_completion' => $printStatus['estimated_completion'],
            'completed_at' => $printStatus['completed_at'],
            'elapsed_time' => $printStatus['elapsed_time_formatted'],
            'remaining_time' => $printStatus['remaining_time_formatted'] ?? null,
            'product_name' => $printStatus['product']['name'] ?? 'Produto',
            'friendly_message' => PrintStatusHelper::getFriendlyStatusMessage(
                $printStatus['status'], 
                $printStatus['progress_percentage'], 
                $printStatus['product']['name'] ?? ''
            ),
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        // Adicionar métricas recentes se disponíveis
        if (!empty($printStatus['latest_metrics'])) {
            $response['metrics'] = $printStatus['latest_metrics'];
        }
        
        // Gerar HTML para elementos de interface
        $response['badge'] = PrintStatusHelper::getStatusBadge(
            $printStatus['status'], 
            $printStatus['progress_percentage']
        );
        
        $response['progressBar'] = PrintStatusHelper::getProgressBar(
            $printStatus['progress_percentage'], 
            $printStatus['status']
        );
        
        $response['dashboard'] = PrintStatusHelper::renderMiniDashboard($printStatus, false);
        
        // Enviar resposta
        $this->jsonResponse($response);
    }
    
    /**
     * Endpoint da API para adicionar uma mensagem a um status de impressão
     */
    public function apiAddMessage() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Verificar autenticação
        $userId = $this->session->get('user_id');
        if (!$userId) {
            $this->jsonResponse(['error' => 'Autenticação necessária'], 401);
            return;
        }
        
        // Validar dados
        $printStatusId = filter_input(INPUT_POST, 'print_status_id', FILTER_VALIDATE_INT);
        $message = trim(filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING));
        
        if (!$printStatusId || empty($message)) {
            $this->jsonResponse(['error' => 'Dados inválidos'], 400);
            return;
        }
        
        // Obter o status de impressão
        $printStatus = $this->printStatusModel->find($printStatusId);
        
        // Verificar se o status existe
        if (!$printStatus) {
            $this->jsonResponse(['error' => 'Status não encontrado'], 404);
            return;
        }
        
        // Verificar se o usuário tem permissão para este status
        if (!$this->userOwnsOrder($userId, $printStatus['order_id']) && !$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Acesso negado'], 403);
            return;
        }
        
        // Adicionar a mensagem
        $isAdmin = $this->isAdmin();
        $username = $this->session->get('username') ?? ($isAdmin ? 'Administrador' : 'Cliente');
        
        $messagePrefix = $isAdmin ? '[Admin] ' : '[Cliente] ';
        $fullMessage = $messagePrefix . $username . ': ' . $message;
        
        $messageId = $this->printStatusModel->addStatusMessage(
            $printStatusId, 
            $fullMessage, 
            'info', 
            true // Visível para o cliente
        );
        
        if (!$messageId) {
            $this->jsonResponse(['error' => 'Erro ao adicionar mensagem'], 500);
            return;
        }
        
        // Retornar sucesso
        $this->jsonResponse([
            'success' => true,
            'message_id' => $messageId,
            'message' => $fullMessage,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Verifica se a requisição atual é AJAX
     * 
     * @return bool True se for uma requisição AJAX
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Verifica se o usuário atual é administrador
     * 
     * @return bool True se o usuário for administrador
     */
    protected function isAdmin() {
        return $this->session->get('user_role') === 'admin';
    }
    
    /**
     * Verifica se o usuário é dono do pedido
     * 
     * @param int $userId ID do usuário
     * @param int $orderId ID do pedido
     * @return bool True se o usuário for dono do pedido
     */
    protected function userOwnsOrder($userId, $orderId) {
        // Administradores têm acesso a todos os pedidos
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verificar se o usuário é dono do pedido
        $order = $this->orderModel->find($orderId);
        return $order && $order['user_id'] == $userId;
    }
    
    /**
     * Verifica se o acesso público é permitido para um status de impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @return bool True se o acesso público for permitido
     */
    protected function isPublicAccessAllowed($printStatusId) {
        // Verificar se o link público está ativado para este status
        // Implementação futura: verificar tokens de acesso público
        return false;
    }
    
    /**
     * Envia uma resposta JSON
     * 
     * @param array $data Dados para enviar como JSON
     * @param int $statusCode Código de status HTTP (opcional)
     */
    protected function jsonResponse($data, $statusCode = 200) {
        // Definir código de status HTTP
        http_response_code($statusCode);
        
        // Definir cabeçalhos para JSON
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        // Enviar resposta
        echo json_encode($data);
        exit;
    }
    
    /**
     * Obtém impressões por IDs de pedidos
     * 
     * @param array $orderIds Array de IDs de pedidos
     * @param bool $activeOnly Apenas impressões ativas
     * @param bool $completedOnly Apenas impressões concluídas
     * @return array Lista de impressões
     */
    protected function getPrintsByOrderIds($orderIds, $activeOnly = false, $completedOnly = false) {
        if (empty($orderIds)) {
            return [];
        }
        
        try {
            // Construir consulta SQL
            $db = $this->printStatusModel->db();
            
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $sql = "SELECT ps.*, 
                           o.order_number, 
                           p.name as product_name,
                           p.model_file
                    FROM print_status ps
                    LEFT JOIN orders o ON ps.order_id = o.id
                    LEFT JOIN products p ON ps.product_id = p.id
                    WHERE ps.order_id IN ($placeholders)";
            
            $params = $orderIds;
            
            if ($activeOnly) {
                $sql .= " AND ps.status IN ('pending', 'preparing', 'printing', 'paused')";
            }
            
            if ($completedOnly) {
                $sql .= " AND ps.status IN ('completed', 'failed', 'canceled')";
                $sql .= " AND ps.completed_at >= ?";
                $params[] = date('Y-m-d H:i:s', strtotime('-30 days')); // Últimos 30 dias
            }
            
            $sql .= " ORDER BY ps.last_updated DESC";
            
            $results = $db->select($sql, $params);
            
            // Adicionar status formatado
            $availableStatuses = PrintStatusModel::getAvailableStatuses();
            foreach ($results as &$status) {
                $status['formatted_status'] = $availableStatuses[$status['status']] ?? $status['status'];
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Erro ao obter impressões por IDs de pedidos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Prepara métricas para exibição em gráficos
     * 
     * @param array $metrics Array de métricas
     * @return array Métricas preparadas para gráficos
     */
    protected function prepareMetricsForChart($metrics) {
        if (empty($metrics)) {
            return [
                'times' => [],
                'temperatures' => [],
                'progress' => []
            ];
        }
        
        // Inverter para ordem cronológica
        $metrics = array_reverse($metrics);
        
        $times = [];
        $temperatures = [];
        $progress = [];
        
        foreach ($metrics as $metric) {
            $time = date('H:i:s', strtotime($metric['recorded_at']));
            
            // Dados de temperatura
            if (isset($metric['hotend_temp']) && isset($metric['bed_temp'])) {
                $temperatures[] = [
                    'time' => $time,
                    'hotend' => $metric['hotend_temp'],
                    'bed' => $metric['bed_temp']
                ];
            }
            
            // Dados de progresso
            if (isset($metric['current_layer']) && isset($metric['total_layers'])) {
                $progress[] = [
                    'time' => $time,
                    'layer' => $metric['current_layer'],
                    'totalLayers' => $metric['total_layers'],
                    'percentage' => ($metric['current_layer'] / $metric['total_layers']) * 100
                ];
            }
            
            $times[] = $time;
        }
        
        return [
            'times' => $times,
            'temperatures' => $temperatures,
            'progress' => $progress
        ];
    }
}
