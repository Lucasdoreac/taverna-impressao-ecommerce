<?php
/**
 * AdminPrintMonitorController - Controlador para monitoramento administrativo de impressões 3D
 * 
 * Este controlador gerencia a interface administrativa para monitorar e controlar
 * o status de impressões 3D em tempo real, fornecendo ferramentas para administradores
 * gerenciarem múltiplas impressões simultaneamente.
 * 
 * @package Controllers
 */
class AdminPrintMonitorController extends Controller {
    
    /**
     * Propriedades do controlador
     */
    protected $printStatusModel;
    protected $orderModel;
    protected $queueModel;
    protected $printHelpers = [];
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        
        // Carregar modelos necessários
        $this->printStatusModel = new PrintStatusModel();
        $this->orderModel = new OrderModel();
        $this->queueModel = new PrintQueueModel();
        
        // Carregar helpers
        $this->printHelpers['status'] = new PrintStatusHelper();
        
        // Verificar permissões de administrador
        $this->middleware('admin');
    }
    
    /**
     * Ação padrão - Dashboard de monitoramento de impressões
     */
    public function index() {
        // Obter impressões ativas (em andamento)
        $activePrints = $this->getActivePrints();
        
        // Obter estatísticas gerais
        $statistics = $this->getStatistics();
        
        // Obter impressões recentemente concluídas
        $recentlyCompleted = $this->getRecentlyCompletedPrints();
        
        // Preparar dados para a view
        $data = [
            'title' => 'Dashboard de Monitoramento de Impressões',
            'activePrints' => $activePrints,
            'statistics' => $statistics,
            'recentlyCompleted' => $recentlyCompleted,
            'css' => PrintStatusHelper::includeCSS(),
            'js' => PrintStatusHelper::includeJavaScript(15) // Atualizar a cada 15 segundos
        ];
        
        // Carregar a view
        $this->view->render('admin/print_monitor_dashboard', $data);
    }
    
    /**
     * Exibe detalhes de um status de impressão específico
     * 
     * @param int $id ID do status de impressão
     */
    public function details($id) {
        // Validar ID
        $id = intval($id);
        if ($id <= 0) {
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Obter detalhes do status
        $printStatus = $this->printStatusModel->getDetailedStatus($id);
        
        // Verificar se o status existe
        if (!$printStatus) {
            $this->session->setFlash('error', 'Status de impressão não encontrado.');
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Obter métricas para gráficos
        $metrics = $this->printStatusModel->getRecentMetrics($id, 100);
        $preparedMetrics = $this->prepareMetricsForChart($metrics);
        
        // Obter histórico completo de mensagens
        $messages = $this->printStatusModel->getStatusMessages($id, false, 50);
        
        // Obter histórico completo de atualizações
        $updates = $this->printStatusModel->getStatusUpdates($id, 50);
        
        // Obter informações da fila de impressão
        $queueInfo = $this->queueModel->getQueueItemDetails($printStatus['print_queue_id']);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Detalhes da Impressão #' . $id,
            'printStatus' => $printStatus,
            'metrics' => $preparedMetrics,
            'messages' => $messages,
            'updates' => $updates,
            'queueInfo' => $queueInfo,
            'css' => PrintStatusHelper::includeCSS(),
            'js' => PrintStatusHelper::includeJavaScript(10) // Atualizar a cada 10 segundos
        ];
        
        // Renderizar a view
        $this->view->render('admin/print_monitor_details', $data);
    }
    
    /**
     * Lista todas as impressões com opções de filtragem
     */
    public function list() {
        // Parâmetros de filtro
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?: 'all';
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $perPage = 20;
        
        // Obter impressões com base nos filtros
        $prints = $this->getPrintsByStatus($status, $page, $perPage);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Lista de Impressões',
            'prints' => $prints['items'],
            'pagination' => [
                'totalItems' => $prints['total'],
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($prints['total'] / $perPage)
            ],
            'filters' => [
                'status' => $status,
                'availableStatuses' => PrintStatusModel::getAvailableStatuses()
            ],
            'css' => PrintStatusHelper::includeCSS()
        ];
        
        // Renderizar a view
        $this->view->render('admin/print_monitor_list', $data);
    }
    
    /**
     * Executa ações em lote para várias impressões
     */
    public function batchAction() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/impressoes/list');
            return;
        }
        
        // Obter dados do formulário
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        $selectedIds = filter_input(INPUT_POST, 'selected', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: [];
        
        // Validar ação e seleção
        if (empty($action) || empty($selectedIds)) {
            $this->session->setFlash('error', 'Ação inválida ou nenhuma impressão selecionada.');
            $this->redirect('admin/impressoes/list');
            return;
        }
        
        // Executar ação em lote
        $success = 0;
        $failed = 0;
        
        foreach ($selectedIds as $id) {
            $result = $this->executeAction($id, $action);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        // Feedback para o usuário
        if ($success > 0) {
            $this->session->setFlash('success', "Ação '{$action}' executada com sucesso em {$success} impressões.");
        }
        
        if ($failed > 0) {
            $this->session->setFlash('error', "Falha ao executar ação em {$failed} impressões.");
        }
        
        // Redirecionar de volta para a lista
        $this->redirect('admin/impressoes/list');
    }
    
    /**
     * Endpoint para executar ações em uma impressão específica via AJAX
     */
    public function action() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Verificar se é uma requisição AJAX
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Requisição inválida'], 400);
            return;
        }
        
        // Validar token CSRF
        $csrfToken = filter_input(INPUT_POST, 'csrf_token');
        if (!$this->validateCsrfToken($csrfToken)) {
            $this->jsonResponse(['error' => 'Token de segurança inválido'], 403);
            return;
        }
        
        // Obter dados da requisição
        $printStatusId = filter_input(INPUT_POST, 'print_status_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        // Validar dados
        if (!$printStatusId || empty($action)) {
            $this->jsonResponse(['error' => 'Dados inválidos'], 400);
            return;
        }
        
        // Executar a ação
        $result = $this->executeAction($printStatusId, $action);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Ação executada com sucesso']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Falha ao executar ação']);
        }
    }
    
    /**
     * Adiciona um novo status de impressão ou atualiza um existente
     */
    public function addOrUpdateStatus() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Obter dados do formulário
        $printStatusId = filter_input(INPUT_POST, 'print_status_id', FILTER_VALIDATE_INT);
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $printQueueId = filter_input(INPUT_POST, 'print_queue_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $progress = filter_input(INPUT_POST, 'progress_percentage', FILTER_VALIDATE_FLOAT);
        $printerId = filter_input(INPUT_POST, 'printer_id', FILTER_SANITIZE_STRING);
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
        
        // Validar dados essenciais
        if (empty($printStatusId) && (empty($orderId) || empty($productId) || empty($printQueueId))) {
            $this->session->setFlash('error', 'Dados insuficientes para criar/atualizar o status.');
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Atualizar status existente
        if ($printStatusId) {
            $updateData = [];
            
            if ($status) {
                $updateData['status'] = $status;
            }
            
            if ($progress !== false) {
                $updateData['progress_percentage'] = min(100, max(0, $progress));
            }
            
            if ($printerId) {
                $updateData['printer_id'] = $printerId;
            }
            
            if ($notes !== null) {
                $updateData['notes'] = $notes;
            }
            
            if (!empty($updateData)) {
                $success = $this->printStatusModel->update($printStatusId, $updateData);
                
                if ($success) {
                    // Adicionar mensagem se fornecida
                    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
                    if (!empty($message)) {
                        $this->printStatusModel->addStatusMessage(
                            $printStatusId,
                            $message,
                            'info',
                            filter_input(INPUT_POST, 'visible_to_customer', FILTER_VALIDATE_BOOLEAN) ?: false
                        );
                    }
                    
                    $this->session->setFlash('success', 'Status de impressão atualizado com sucesso.');
                } else {
                    $this->session->setFlash('error', 'Erro ao atualizar status de impressão.');
                }
            }
            
            $this->redirect('admin/impressao/' . $printStatusId);
            return;
        }
        
        // Criar novo status
        $newStatusId = $this->printStatusModel->createStatus(
            $orderId,
            $productId,
            $printQueueId,
            $printerId,
            [
                'status' => $status ?: PrintStatusModel::STATUS_PENDING,
                'progress_percentage' => $progress ?: 0,
                'notes' => $notes
            ]
        );
        
        if ($newStatusId) {
            $this->session->setFlash('success', 'Novo status de impressão criado com sucesso.');
            $this->redirect('admin/impressao/' . $newStatusId);
        } else {
            $this->session->setFlash('error', 'Erro ao criar novo status de impressão.');
            $this->redirect('admin/impressoes');
        }
    }
    
    /**
     * Adiciona uma mensagem a um status de impressão
     */
    public function addMessage() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Obter dados do formulário
        $printStatusId = filter_input(INPUT_POST, 'print_status_id', FILTER_VALIDATE_INT);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?: 'info';
        $visibleToCustomer = filter_input(INPUT_POST, 'visible_to_customer', FILTER_VALIDATE_BOOLEAN) ?: false;
        
        // Validar dados
        if (!$printStatusId || empty($message)) {
            $this->session->setFlash('error', 'ID de impressão ou mensagem inválida.');
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Adicionar prefixo de administrador
        $username = $this->session->get('username') ?: 'Admin';
        $fullMessage = "[Admin] {$username}: {$message}";
        
        // Adicionar mensagem
        $success = $this->printStatusModel->addStatusMessage(
            $printStatusId,
            $fullMessage,
            $type,
            $visibleToCustomer
        );
        
        if ($success) {
            $this->session->setFlash('success', 'Mensagem adicionada com sucesso.');
        } else {
            $this->session->setFlash('error', 'Erro ao adicionar mensagem.');
        }
        
        // Redirecionar para detalhes
        $this->redirect('admin/impressao/' . $printStatusId);
    }
    
    /**
     * Adiciona métricas manuais a um status de impressão
     */
    public function addMetrics() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Obter dados do formulário
        $printStatusId = filter_input(INPUT_POST, 'print_status_id', FILTER_VALIDATE_INT);
        
        // Validar ID
        if (!$printStatusId) {
            $this->session->setFlash('error', 'ID de impressão inválido.');
            $this->redirect('admin/impressoes');
            return;
        }
        
        // Coletar métricas do formulário
        $metrics = [
            'hotend_temp' => filter_input(INPUT_POST, 'hotend_temp', FILTER_VALIDATE_FLOAT),
            'bed_temp' => filter_input(INPUT_POST, 'bed_temp', FILTER_VALIDATE_FLOAT),
            'speed_percentage' => filter_input(INPUT_POST, 'speed_percentage', FILTER_VALIDATE_INT),
            'fan_speed_percentage' => filter_input(INPUT_POST, 'fan_speed_percentage', FILTER_VALIDATE_INT),
            'layer_height' => filter_input(INPUT_POST, 'layer_height', FILTER_VALIDATE_FLOAT),
            'current_layer' => filter_input(INPUT_POST, 'current_layer', FILTER_VALIDATE_INT),
            'total_layers' => filter_input(INPUT_POST, 'total_layers', FILTER_VALIDATE_INT),
            'filament_used_mm' => filter_input(INPUT_POST, 'filament_used_mm', FILTER_VALIDATE_FLOAT),
            'print_time_remaining_seconds' => filter_input(INPUT_POST, 'print_time_remaining', FILTER_VALIDATE_INT) * 60 // Converter minutos para segundos
        ];
        
        // Filtrar valores vazios
        $metrics = array_filter($metrics, function($value) {
            return $value !== false && $value !== null;
        });
        
        // Adicionar dados adicionais se houver
        $additionalData = filter_input(INPUT_POST, 'additional_data', FILTER_SANITIZE_STRING);
        if (!empty($additionalData)) {
            $metrics['additional_data'] = json_decode($additionalData, true) ?: ['notes' => $additionalData];
        }
        
        // Verificar se há métricas para adicionar
        if (empty($metrics)) {
            $this->session->setFlash('error', 'Nenhuma métrica válida fornecida.');
            $this->redirect('admin/impressao/' . $printStatusId);
            return;
        }
        
        // Registrar métricas
        $success = $this->printStatusModel->recordMetrics($printStatusId, $metrics);
        
        if ($success) {
            $this->session->setFlash('success', 'Métricas adicionadas com sucesso.');
        } else {
            $this->session->setFlash('error', 'Erro ao adicionar métricas.');
        }
        
        // Redirecionar para detalhes
        $this->redirect('admin/impressao/' . $printStatusId);
    }
    
    /**
     * Executa uma ação específica em um status de impressão
     * 
     * @param int $printStatusId ID do status de impressão
     * @param string $action Ação a ser executada (pause, resume, cancel)
     * @return bool Sucesso da operação
     */
    protected function executeAction($printStatusId, $action) {
        // Obter status atual
        $printStatus = $this->printStatusModel->find($printStatusId);
        if (!$printStatus) {
            return false;
        }
        
        // Executar ação apropriada
        switch ($action) {
            case 'pause':
                // Só é possível pausar se estiver imprimindo
                if ($printStatus['status'] === PrintStatusModel::STATUS_PRINTING) {
                    return $this->printStatusModel->updateStatus(
                        $printStatusId,
                        PrintStatusModel::STATUS_PAUSED,
                        null,
                        'Impressão pausada pelo administrador',
                        $this->session->get('username') ?: 'admin'
                    );
                }
                break;
                
            case 'resume':
                // Só é possível retomar se estiver pausado
                if ($printStatus['status'] === PrintStatusModel::STATUS_PAUSED) {
                    return $this->printStatusModel->updateStatus(
                        $printStatusId,
                        PrintStatusModel::STATUS_PRINTING,
                        null,
                        'Impressão retomada pelo administrador',
                        $this->session->get('username') ?: 'admin'
                    );
                }
                break;
                
            case 'cancel':
                // Só é possível cancelar se não estiver concluído, falho ou já cancelado
                if (!in_array($printStatus['status'], [
                    PrintStatusModel::STATUS_COMPLETED,
                    PrintStatusModel::STATUS_FAILED,
                    PrintStatusModel::STATUS_CANCELED
                ])) {
                    return $this->printStatusModel->updateStatus(
                        $printStatusId,
                        PrintStatusModel::STATUS_CANCELED,
                        null,
                        'Impressão cancelada pelo administrador',
                        $this->session->get('username') ?: 'admin'
                    );
                }
                break;
                
            case 'start':
                // Só é possível iniciar se estiver pendente ou em preparação
                if (in_array($printStatus['status'], [
                    PrintStatusModel::STATUS_PENDING,
                    PrintStatusModel::STATUS_PREPARING
                ])) {
                    return $this->printStatusModel->updateStatus(
                        $printStatusId,
                        PrintStatusModel::STATUS_PRINTING,
                        0.1, // Iniciar com progresso mínimo
                        'Impressão iniciada pelo administrador',
                        $this->session->get('username') ?: 'admin'
                    );
                }
                break;
                
            case 'complete':
                // Forçar conclusão (apenas para administradores)
                if (!in_array($printStatus['status'], [
                    PrintStatusModel::STATUS_COMPLETED,
                    PrintStatusModel::STATUS_FAILED,
                    PrintStatusModel::STATUS_CANCELED
                ])) {
                    return $this->printStatusModel->updateStatus(
                        $printStatusId,
                        PrintStatusModel::STATUS_COMPLETED,
                        100.0, // Progresso completo
                        'Impressão marcada como concluída pelo administrador',
                        $this->session->get('username') ?: 'admin'
                    );
                }
                break;
                
            case 'fail':
                // Marcar como falha (apenas para administradores)
                if (!in_array($printStatus['status'], [
                    PrintStatusModel::STATUS_COMPLETED,
                    PrintStatusModel::STATUS_FAILED,
                    PrintStatusModel::STATUS_CANCELED
                ])) {
                    return $this->printStatusModel->updateStatus(
                        $printStatusId,
                        PrintStatusModel::STATUS_FAILED,
                        null,
                        'Impressão marcada como falha pelo administrador',
                        $this->session->get('username') ?: 'admin'
                    );
                }
                break;
        }
        
        return false; // Ação não executada
    }
    
    /**
     * Obtém impressões ativas (em andamento)
     * 
     * @param int $limit Limite de registros
     * @return array Lista de impressões ativas
     */
    protected function getActivePrints($limit = 10) {
        return $this->printStatusModel->getActivePrints($limit);
    }
    
    /**
     * Obtém impressões recentemente concluídas
     * 
     * @param int $days Número de dias para consultar
     * @param int $limit Limite de registros
     * @return array Lista de impressões concluídas
     */
    protected function getRecentlyCompletedPrints($days = 7, $limit = 10) {
        return $this->printStatusModel->getRecentlyCompletedPrints($days, $limit);
    }
    
    /**
     * Obtém impressões filtradas por status
     * 
     * @param string $status Status para filtrar (ou 'all' para todos)
     * @param int $page Página atual
     * @param int $perPage Itens por página
     * @return array Impressões e informações de paginação
     */
    protected function getPrintsByStatus($status = 'all', $page = 1, $perPage = 20) {
        try {
            // Obter instância do banco de dados
            $db = $this->printStatusModel->db();
            
            // Parâmetros base
            $params = [];
            $offset = ($page - 1) * $perPage;
            
            // Construir consulta base
            $sql = "SELECT ps.*, 
                           o.order_number, o.customer_name,
                           p.name as product_name
                    FROM print_status ps
                    LEFT JOIN orders o ON ps.order_id = o.id
                    LEFT JOIN products p ON ps.product_id = p.id
                    WHERE 1=1";
            
            // Adicionar filtro de status se necessário
            if ($status !== 'all') {
                $sql .= " AND ps.status = ?";
                $params[] = $status;
            }
            
            // Consulta para contar total
            $countSql = str_replace("SELECT ps.*, ", "SELECT COUNT(*) as total ", $sql);
            $totalResult = $db->select($countSql, $params);
            $total = $totalResult[0]['total'] ?? 0;
            
            // Adicionar ordenação e paginação
            $sql .= " ORDER BY ps.last_updated DESC LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $perPage;
            
            // Executar consulta principal
            $items = $db->select($sql, $params);
            
            // Adicionar status formatado
            $availableStatuses = PrintStatusModel::getAvailableStatuses();
            foreach ($items as &$item) {
                $item['formatted_status'] = $availableStatuses[$item['status']] ?? $item['status'];
            }
            
            return [
                'items' => $items,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Erro ao obter impressões por status: " . $e->getMessage());
            return [
                'items' => [],
                'total' => 0
            ];
        }
    }
    
    /**
     * Obtém estatísticas gerais de impressões
     * 
     * @return array Estatísticas
     */
    protected function getStatistics() {
        try {
            // Obter instância do banco de dados
            $db = $this->printStatusModel->db();
            
            // Estatísticas de status
            $statusSql = "SELECT 
                            status, 
                            COUNT(*) as count,
                            AVG(progress_percentage) as avg_progress
                          FROM print_status
                          GROUP BY status";
                          
            $statusStats = $db->select($statusSql);
            
            // Estatísticas temporais
            $timeSql = "SELECT 
                          COUNT(*) as total_prints,
                          COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                          COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                          COUNT(CASE WHEN status IN ('pending', 'preparing', 'printing', 'paused') THEN 1 END) as active,
                          AVG(CASE WHEN status = 'completed' THEN 
                              TIME_TO_SEC(TIMEDIFF(completed_at, started_at)) 
                          END) as avg_print_time
                        FROM print_status
                        WHERE created_at >= ?";
            
            $lastWeek = date('Y-m-d H:i:s', strtotime('-7 days'));
            $timeStats = $db->select($timeSql, [$lastWeek]);
            
            // Estatísticas por dia da semana
            $dailySql = "SELECT 
                           DAYOFWEEK(started_at) as day_of_week,
                           COUNT(*) as count
                         FROM print_status
                         WHERE started_at IS NOT NULL
                         GROUP BY DAYOFWEEK(started_at)
                         ORDER BY day_of_week";
                         
            $dailyStats = $db->select($dailySql);
            
            // Formatar estatísticas
            $formattedStats = [
                'by_status' => [],
                'temporal' => $timeStats[0] ?? [],
                'by_day' => []
            ];
            
            // Formatar estatísticas de status
            $availableStatuses = PrintStatusModel::getAvailableStatuses();
            foreach ($statusStats as $stat) {
                $formattedStats['by_status'][$stat['status']] = [
                    'count' => $stat['count'],
                    'name' => $availableStatuses[$stat['status']] ?? $stat['status'],
                    'avg_progress' => round($stat['avg_progress'], 1)
                ];
            }
            
            // Formatar estatísticas por dia
            $daysOfWeek = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
            foreach ($dailyStats as $stat) {
                $dayIndex = $stat['day_of_week'] - 1; // MySQL usa 1-7, array usa 0-6
                $formattedStats['by_day'][$daysOfWeek[$dayIndex]] = $stat['count'];
            }
            
            // Formatar estatísticas temporais
            if (isset($formattedStats['temporal']['avg_print_time'])) {
                $avgPrintTimeSeconds = $formattedStats['temporal']['avg_print_time'];
                $formattedStats['temporal']['avg_print_time_formatted'] = $this->formatTimeInterval($avgPrintTimeSeconds);
            }
            
            return $formattedStats;
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return [
                'by_status' => [],
                'temporal' => [],
                'by_day' => []
            ];
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
    
    /**
     * Formata um intervalo de tempo em segundos para uma string legível
     * 
     * @param int $seconds Número de segundos
     * @return string Tempo formatado
     */
    protected function formatTimeInterval($seconds) {
        if (!$seconds) {
            return 'N/A';
        }
        
        $seconds = (int)$seconds;
        
        if ($seconds < 60) {
            return "{$seconds} segundos";
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return "{$minutes} min" . ($secs > 0 ? " {$secs} seg" : "");
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$hours} h" . ($minutes > 0 ? " {$minutes} min" : "");
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
     * Valida um token CSRF
     * 
     * @param string $token Token CSRF a validar
     * @return bool True se o token for válido
     */
    protected function validateCsrfToken($token) {
        return $token === $this->session->get('csrf_token');
    }
}
