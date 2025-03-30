<?php
/**
 * IntegrationController - Controlador para gerenciar a integração entre pedidos e fila de impressão
 * 
 * Este controlador é responsável por fornecer interfaces e funcionalidades para
 * monitorar e gerenciar a integração entre o sistema de pedidos e a fila de impressão 3D,
 * incluindo logs, estatísticas e ferramentas de diagnóstico.
 */
class IntegrationController extends AdminController {
    
    private $integrationLogModel;
    private $orderModel;
    private $printQueueModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Verificar autenticação de admin via parent constructor
        parent::__construct();
        
        // Carregar modelos
        require_once APP_PATH . '/models/IntegrationLogModel.php';
        require_once APP_PATH . '/models/OrderModel.php';
        require_once APP_PATH . '/models/PrintQueueModel.php';
        
        $this->integrationLogModel = new IntegrationLogModel();
        $this->orderModel = new OrderModel();
        $this->printQueueModel = new PrintQueueModel();
        
        // Verificar se a tabela de logs existe
        $this->integrationLogModel->createTableIfNotExists();
    }
    
    /**
     * Exibe o dashboard de integração
     */
    public function dashboard() {
        try {
            // Obter estatísticas para o dashboard
            $stats = $this->getIntegrationStats();
            
            // Obter eventos recentes
            $recentEvents = $this->integrationLogModel->getRecentEvents(10);
            
            // Obter jobs órfãos
            $orphanedJobs = $this->integrationLogModel->findOrphanedJobs(7);
            $orphanedJobs = array_slice($orphanedJobs, 0, 5); // Limitar a 5 para o dashboard
            
            // Obter fluxos incompletos
            $incompleteFlows = $this->integrationLogModel->findIncompleteIntegrationFlows(7);
            $incompleteFlows = array_slice($incompleteFlows, 0, 5); // Limitar a 5 para o dashboard
            
            // Preparar dados para o gráfico de atividade
            $chartData = $this->prepareChartData();
            
            // Renderizar a view do dashboard
            require_once VIEWS_PATH . '/admin/integration_dashboard.php';
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro ao carregar dashboard de integração: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar o dashboard de integração. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/dashboard');
            exit;
        }
    }
    
    /**
     * Exibe os logs de integração com opções de filtragem
     */
    public function logs() {
        try {
            // Parâmetros de filtragem
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $limit = min($limit, 500); // Limitar a 500 para performance
            
            // Obter logs filtrados
            if ($status) {
                $logs = $this->integrationLogModel->getEventsByStatus($status, $limit);
            } else {
                $logs = $this->integrationLogModel->getRecentEvents($limit);
            }
            
            // Estatísticas de eventos
            $eventStats = $this->integrationLogModel->getEventsStatistics();
            
            // Renderizar a view de logs
            require_once VIEWS_PATH . '/admin/integration_logs.php';
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro ao carregar logs de integração: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os logs de integração. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/integration/dashboard');
            exit;
        }
    }
    
    /**
     * Exibe jobs de impressão órfãos
     */
    public function orphaned() {
        try {
            // Parâmetros de filtragem
            $daysBack = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $daysBack = min($daysBack, 30); // Limitar a 30 dias para performance
            
            // Obter todos os jobs órfãos
            $orphanedJobs = $this->integrationLogModel->findOrphanedJobs($daysBack);
            
            // Renderizar a view de jobs órfãos
            require_once VIEWS_PATH . '/admin/integration_orphaned_jobs.php';
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro ao carregar jobs órfãos: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os jobs órfãos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/integration/dashboard');
            exit;
        }
    }
    
    /**
     * Exibe fluxos de integração incompletos
     */
    public function incomplete() {
        try {
            // Parâmetros de filtragem
            $daysBack = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $daysBack = min($daysBack, 30); // Limitar a 30 dias para performance
            
            // Obter todos os fluxos incompletos
            $incompleteFlows = $this->integrationLogModel->findIncompleteIntegrationFlows($daysBack);
            
            // Renderizar a view de fluxos incompletos
            require_once VIEWS_PATH . '/admin/integration_incomplete_flows.php';
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro ao carregar fluxos incompletos: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os fluxos incompletos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/integration/dashboard');
            exit;
        }
    }
    
    /**
     * Ferramenta para reparar problemas de integração
     */
    public function repair() {
        try {
            $repaired = false;
            $repairStats = [];
            
            // Processar reparo se o formulário foi enviado
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                // Validar token CSRF
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    throw new Exception("Token de segurança inválido");
                }
                
                // Obter configurações de reparo
                $repairOrphaned = isset($_POST['repair_orphaned']) && $_POST['repair_orphaned'] == '1';
                $repairIncomplete = isset($_POST['repair_incomplete']) && $_POST['repair_incomplete'] == '1';
                $daysBack = isset($_POST['days_back']) ? (int)$_POST['days_back'] : 7;
                
                // Executar reparos
                if ($repairOrphaned) {
                    $repairStats['orphaned'] = $this->repairOrphanedJobs($daysBack);
                }
                
                if ($repairIncomplete) {
                    $repairStats['incomplete'] = $this->repairIncompleteFlows($daysBack);
                }
                
                $repaired = true;
                
                // Registrar no log de integração
                $this->integrationLogModel->logEvent(
                    null, 
                    null, 
                    "Reparo automático executado", 
                    'info', 
                    [
                        'repair_orphaned' => $repairOrphaned,
                        'repair_incomplete' => $repairIncomplete,
                        'days_back' => $daysBack,
                        'stats' => $repairStats
                    ]
                );
            }
            
            // Gerar token CSRF para o formulário
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Estatísticas para a view
            $orphanedCount = count($this->integrationLogModel->findOrphanedJobs(7));
            $incompleteCount = count($this->integrationLogModel->findIncompleteIntegrationFlows(7));
            
            // Renderizar a view de reparo
            require_once VIEWS_PATH . '/admin/integration_repair.php';
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro na ferramenta de reparo: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro na ferramenta de reparo: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'admin/integration/dashboard');
            exit;
        }
    }
    
    /**
     * Tenta reparar um job órfão específico
     * 
     * @param int $jobId ID do job de impressão
     */
    public function fixJob($jobId) {
        try {
            // Validar ID
            $jobId = (int)$jobId;
            if ($jobId <= 0) {
                throw new Exception("ID de job inválido");
            }
            
            // Obter dados do job
            $job = $this->printQueueModel->getJob($jobId);
            if (!$job) {
                throw new Exception("Job de impressão não encontrado");
            }
            
            // Verificar se o job está vinculado a um pedido
            if (!$job['order_id']) {
                // Tentar encontrar pedido relacionado
                // Lógica específica de reparo aqui...
                $_SESSION['warning'] = 'Job não está vinculado a nenhum pedido. Reparo manual necessário.';
                header('Location: ' . BASE_URL . 'admin/print-jobs/view/' . $jobId);
                exit;
            }
            
            // Verificar status do job e do pedido
            $order = $this->orderModel->getOrder($job['order_id']);
            if (!$order) {
                throw new Exception("Pedido #{$job['order_id']} não encontrado");
            }
            
            // Reparar com base no status do pedido
            $repaired = $this->synchronizeJobWithOrder($job, $order);
            
            if ($repaired) {
                $_SESSION['success'] = 'Job de impressão reparado com sucesso!';
                
                // Registrar no log de integração
                $this->integrationLogModel->logEvent(
                    $job['order_id'],
                    $jobId,
                    "Job de impressão reparado manualmente",
                    'success',
                    [
                        'admin_id' => $_SESSION['user']['id'],
                        'admin_name' => $_SESSION['user']['name'],
                        'old_status' => $job['status'],
                        'new_status' => $this->printQueueModel->getJob($jobId)['status']
                    ]
                );
            } else {
                $_SESSION['info'] = 'O job não precisava de reparo ou não foi possível repará-lo automaticamente.';
            }
            
            header('Location: ' . BASE_URL . 'admin/print-jobs/view/' . $jobId);
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro ao reparar job: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao reparar o job: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'admin/integration/orphaned');
            exit;
        }
    }
    
    /**
     * Tenta reparar um pedido com problemas de integração
     * 
     * @param int $orderId ID do pedido
     */
    public function fixOrder($orderId) {
        try {
            // Validar ID
            $orderId = (int)$orderId;
            if ($orderId <= 0) {
                throw new Exception("ID de pedido inválido");
            }
            
            // Obter dados do pedido
            $order = $this->orderModel->getOrder($orderId);
            if (!$order) {
                throw new Exception("Pedido não encontrado");
            }
            
            // Obter jobs relacionados
            $jobs = $this->printQueueModel->getJobsByOrderId($orderId);
            
            // Verificar se existem jobs para todos os itens que necessitam impressão
            $orderItems = $this->orderModel->getOrderItems($orderId);
            $customPrintItems = array_filter($orderItems, function($item) {
                return strpos(strtolower($item['product_name']), 'sob encomenda') !== false;
            });
            
            $repaired = false;
            
            // Se não há jobs mas há itens sob encomenda, criar jobs
            if (empty($jobs) && !empty($customPrintItems)) {
                foreach ($customPrintItems as $item) {
                    $this->printQueueModel->createJob([
                        'order_id' => $orderId,
                        'order_item_id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'status' => $this->mapOrderStatusToPrintJobStatus($order['status']),
                        'quantity' => $item['quantity'],
                        'options' => $item['options'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'notes' => 'Criado automaticamente pela ferramenta de reparo'
                    ]);
                }
                
                $repaired = true;
                $_SESSION['success'] = 'Jobs de impressão criados com sucesso para os itens sob encomenda!';
                
                // Registrar no log de integração
                $this->integrationLogModel->logEvent(
                    $orderId,
                    null,
                    "Jobs de impressão criados manualmente para pedido",
                    'success',
                    [
                        'admin_id' => $_SESSION['user']['id'],
                        'admin_name' => $_SESSION['user']['name'],
                        'items_count' => count($customPrintItems)
                    ]
                );
            } 
            // Se há jobs, verificar e sincronizar status
            elseif (!empty($jobs)) {
                $updatedCount = 0;
                foreach ($jobs as $job) {
                    if ($this->synchronizeJobWithOrder($job, $order)) {
                        $updatedCount++;
                    }
                }
                
                if ($updatedCount > 0) {
                    $repaired = true;
                    $_SESSION['success'] = "{$updatedCount} jobs de impressão foram sincronizados com o status do pedido!";
                    
                    // Registrar no log de integração
                    $this->integrationLogModel->logEvent(
                        $orderId,
                        null,
                        "Status dos jobs de impressão sincronizados manualmente",
                        'success',
                        [
                            'admin_id' => $_SESSION['user']['id'],
                            'admin_name' => $_SESSION['user']['name'],
                            'updated_count' => $updatedCount
                        ]
                    );
                }
            }
            
            if (!$repaired) {
                $_SESSION['info'] = 'O pedido não precisava de reparo ou não foi possível repará-lo automaticamente.';
            }
            
            header('Location: ' . BASE_URL . 'admin/orders/view/' . $orderId);
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            app_log("Erro ao reparar pedido: " . $e->getMessage(), 'error');
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao reparar o pedido: ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'admin/integration/incomplete');
            exit;
        }
    }
    
    /**
     * Obtém estatísticas gerais para o dashboard de integração
     * 
     * @return array Estatísticas de integração
     */
    private function getIntegrationStats() {
        // Integrações bem-sucedidas (últimos 7 dias)
        $successEvents = $this->integrationLogModel->getEventsByStatus('success', 100);
        $successfulIntegrations = count($successEvents);
        
        // Erros de integração (últimos 7 dias)
        $errorEvents = $this->integrationLogModel->getEventsByStatus('error', 100);
        $integrationErrors = count($errorEvents);
        
        // Jobs em andamento
        $pendingJobs = $this->printQueueModel->countJobsByStatus(['pending', 'in_queue', 'preparing', 'printing']);
        
        // Jobs órfãos
        $orphanedJobs = count($this->integrationLogModel->findOrphanedJobs(7));
        
        return [
            'successful_integrations' => $successfulIntegrations,
            'integration_errors' => $integrationErrors,
            'pending_jobs' => $pendingJobs,
            'orphaned_jobs' => $orphanedJobs
        ];
    }
    
    /**
     * Prepara dados para o gráfico de atividade
     * 
     * @return array Dados formatados para o gráfico
     */
    private function prepareChartData() {
        // Obter eventos dos últimos 7 dias agrupados por dia e status
        $chartData = [
            'labels' => [],
            'success' => [],
            'warning' => [],
            'error' => []
        ];
        
        // Gerar os últimos 7 dias
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $chartData['labels'][] = date('d/m', strtotime($date));
            
            // Inicializar contadores para este dia
            $chartData['success'][] = 0;
            $chartData['warning'][] = 0;
            $chartData['error'][] = 0;
        }
        
        // Obter eventos dos últimos 7 dias
        $startDate = date('Y-m-d H:i:s', strtotime("-7 days"));
        $sql = "SELECT DATE(created_at) as event_date, status, COUNT(*) as count 
                FROM integration_logs 
                WHERE created_at >= :start_date 
                GROUP BY DATE(created_at), status 
                ORDER BY event_date";
        
        $db = new Database();
        $events = $db->query($sql, ['start_date' => $startDate])->fetchAll();
        
        // Preencher dados do gráfico
        foreach ($events as $event) {
            $dayIndex = array_search(date('d/m', strtotime($event['event_date'])), $chartData['labels']);
            if ($dayIndex !== false) {
                $chartData[$event['status']][$dayIndex] = (int)$event['count'];
            }
        }
        
        return $chartData;
    }
    
    /**
     * Repara jobs órfãos
     * 
     * @param int $daysBack Número de dias para analisar
     * @return array Estatísticas de reparo
     */
    private function repairOrphanedJobs($daysBack) {
        $orphanedJobs = $this->integrationLogModel->findOrphanedJobs($daysBack);
        $stats = [
            'found' => count($orphanedJobs),
            'repaired' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($orphanedJobs as $job) {
            try {
                // Verificar se o job está vinculado a um pedido
                if (!$job['order_id']) {
                    // Não pode reparar jobs sem pedido vinculado
                    $stats['failed']++;
                    $stats['details'][] = "Job #{$job['id']}: Não está vinculado a nenhum pedido";
                    continue;
                }
                
                // Obter o pedido relacionado
                $order = $this->orderModel->getOrder($job['order_id']);
                if (!$order) {
                    $stats['failed']++;
                    $stats['details'][] = "Job #{$job['id']}: Pedido #{$job['order_id']} não encontrado";
                    continue;
                }
                
                // Sincronizar status
                if ($this->synchronizeJobWithOrder($job, $order)) {
                    $stats['repaired']++;
                    $stats['details'][] = "Job #{$job['id']}: Status sincronizado com o pedido #{$job['order_id']}";
                } else {
                    $stats['failed']++;
                    $stats['details'][] = "Job #{$job['id']}: Não foi possível sincronizar com o pedido #{$job['order_id']}";
                }
                
            } catch (Exception $e) {
                $stats['failed']++;
                $stats['details'][] = "Job #{$job['id']}: Erro: " . $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * Repara fluxos de integração incompletos
     * 
     * @param int $daysBack Número de dias para analisar
     * @return array Estatísticas de reparo
     */
    private function repairIncompleteFlows($daysBack) {
        $incompleteFlows = $this->integrationLogModel->findIncompleteIntegrationFlows($daysBack);
        $stats = [
            'found' => count($incompleteFlows),
            'repaired' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        foreach ($incompleteFlows as $order) {
            try {
                // Obter itens do pedido que necessitam impressão 3D
                $orderItems = $this->orderModel->getOrderItems($order['id']);
                $customPrintItems = array_filter($orderItems, function($item) {
                    return strpos(strtolower($item['product_name']), 'sob encomenda') !== false;
                });
                
                if (empty($customPrintItems)) {
                    $stats['failed']++;
                    $stats['details'][] = "Pedido #{$order['id']}: Não possui itens sob encomenda";
                    continue;
                }
                
                // Verificar jobs existentes
                $existingJobs = $this->printQueueModel->getJobsByOrderId($order['id']);
                
                // Criar jobs faltantes
                $createdCount = 0;
                foreach ($customPrintItems as $item) {
                    // Verificar se já existe um job para este item
                    $jobExists = false;
                    foreach ($existingJobs as $job) {
                        if ($job['order_item_id'] == $item['id']) {
                            $jobExists = true;
                            break;
                        }
                    }
                    
                    // Criar job se não existir
                    if (!$jobExists) {
                        $this->printQueueModel->createJob([
                            'order_id' => $order['id'],
                            'order_item_id' => $item['id'],
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'],
                            'status' => $this->mapOrderStatusToPrintJobStatus($order['status']),
                            'quantity' => $item['quantity'],
                            'options' => $item['options'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'notes' => 'Criado automaticamente pela ferramenta de reparo'
                        ]);
                        
                        $createdCount++;
                    }
                }
                
                if ($createdCount > 0) {
                    $stats['repaired']++;
                    $stats['details'][] = "Pedido #{$order['id']}: Criados {$createdCount} jobs de impressão";
                } else {
                    $stats['failed']++;
                    $stats['details'][] = "Pedido #{$order['id']}: Não foi necessário criar novos jobs";
                }
                
            } catch (Exception $e) {
                $stats['failed']++;
                $stats['details'][] = "Pedido #{$order['id']}: Erro: " . $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * Sincroniza o status de um job com o status do pedido
     * 
     * @param array $job Dados do job de impressão
     * @param array $order Dados do pedido
     * @return bool Verdadeiro se o job foi sincronizado
     */
    private function synchronizeJobWithOrder($job, $order) {
        // Mapear status do pedido para status do job
        $expectedJobStatus = $this->mapOrderStatusToPrintJobStatus($order['status']);
        
        // Verificar se o status já está sincronizado
        if ($job['status'] == $expectedJobStatus) {
            return false; // Já está sincronizado
        }
        
        // Atualizar status do job
        return $this->printQueueModel->updateJobStatus($job['id'], $expectedJobStatus);
    }
    
    /**
     * Mapeia status do pedido para status do job de impressão
     * 
     * @param string $orderStatus Status do pedido
     * @return string Status equivalente do job de impressão
     */
    private function mapOrderStatusToPrintJobStatus($orderStatus) {
        switch ($orderStatus) {
            case 'pending':
                return 'pending';
            case 'processing':
                return 'preparing';
            case 'in_production':
                return 'printing';
            case 'completed':
            case 'delivered':
                return 'completed';
            case 'cancelled':
                return 'cancelled';
            default:
                return 'pending';
        }
    }
}
