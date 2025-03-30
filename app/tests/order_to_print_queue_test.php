<?php
/**
 * order_to_print_queue_test.php
 * 
 * Script de teste para verificar a integração entre o sistema de pedidos
 * e a fila de impressão 3D.
 * 
 * Este teste garante que o fluxo de trabalho entre os sistemas está funcionando
 * corretamente, desde a criação do pedido até a finalização da impressão.
 */

// Carregar configurações e dependências
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../models/PrintQueueModel.php';
require_once __DIR__ . '/../models/ProductModel.php';

class OrderToPrintQueueTest {
    private $orderModel;
    private $printQueueModel;
    private $productModel;
    private $testResults = [];
    private $testOrderIds = [];
    private $testPrintJobIds = [];
    private $isTestMode = true;
    
    /**
     * Construtor da classe de teste
     */
    public function __construct() {
        // Inicializar modelos
        $this->orderModel = new OrderModel();
        $this->printQueueModel = new PrintQueueModel();
        $this->productModel = new ProductModel();
        
        // Configurar modo de teste para evitar alterações em produção
        $this->configureTestMode();
    }
    
    /**
     * Configura o modo de teste
     */
    private function configureTestMode() {
        if ($this->isTestMode) {
            // Adicionar prefixo de teste a todas as operações
            app_log("TESTE: Inicializando testes de integração em modo de teste");
        } else {
            app_log("ALERTA: Executando testes de integração em ambiente de PRODUÇÃO");
        }
    }
    
    /**
     * Executa todos os testes de integração
     * 
     * @return array Resultados dos testes
     */
    public function runAllTests() {
        try {
            // Teste 1: Criar pedido com produtos sob demanda e verificar criação automática na fila
            $this->testOrderCreationTriggersPrintJobs();
            
            // Teste 2: Verificar se mudanças de status em pedidos refletem em jobs de impressão
            $this->testOrderStatusChangesTriggerPrintJobsUpdates();
            
            // Teste 3: Verificar se finalizações de jobs de impressão atualizam pedidos
            $this->testPrintJobStatusUpdatesOrderStatus();
            
            // Teste 4: Verificar se cancelamentos propagam corretamente
            $this->testCancellationPropagatesBetweenSystems();
            
            // Teste 5: Verificar fluxo de trabalho completo
            $this->testCompleteWorkflow();
            
            // Limpar dados de teste
            $this->cleanupTestData();
            
            return $this->testResults;
        } catch (Exception $e) {
            app_log("ERRO nos testes de integração: " . $e->getMessage());
            $this->testResults["erro_geral"] = [
                "passed" => false,
                "details" => "Erro geral: " . $e->getMessage(),
                "exception" => $e->getTraceAsString()
            ];
            
            // Tentar limpar dados mesmo com erro
            $this->cleanupTestData();
            
            return $this->testResults;
        }
    }
    
    /**
     * Teste 1: Verifica se a criação de um pedido com produtos sob demanda
     * cria automaticamente entradas na fila de impressão
     */
    private function testOrderCreationTriggersPrintJobs() {
        app_log("TESTE: Iniciando teste de criação de pedido e verificação de jobs de impressão");
        
        try {
            // 1. Criar pedido de teste com produtos sob demanda
            $testOrderData = $this->createTestOrder();
            $orderId = $testOrderData['order_id'];
            $this->testOrderIds[] = $orderId;
            
            // 2. Verificar se entradas foram criadas na fila de impressão
            $printJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            
            // 3. Verificar se número de jobs corresponde ao número de itens sob demanda
            $expectedJobCount = $testOrderData['custom_print_items_count'];
            $actualJobCount = is_array($printJobs) ? count($printJobs) : 0;
            
            $passed = ($actualJobCount == $expectedJobCount);
            
            // 4. Registrar jobs de teste para limpeza posterior
            if (is_array($printJobs)) {
                foreach ($printJobs as $job) {
                    $this->testPrintJobIds[] = $job['id'];
                }
            }
            
            // 5. Registrar resultado do teste
            $this->testResults["order_creation_triggers_print_jobs"] = [
                "passed" => $passed,
                "details" => "Esperados {$expectedJobCount} jobs, encontrados {$actualJobCount}",
                "order_id" => $orderId,
                "print_jobs" => $printJobs
            ];
            
            app_log("TESTE: " . ($passed ? "PASSOU" : "FALHOU") . " - Teste de criação de pedido");
            
        } catch (Exception $e) {
            app_log("ERRO no teste de criação de pedido: " . $e->getMessage());
            $this->testResults["order_creation_triggers_print_jobs"] = [
                "passed" => false,
                "details" => "Erro: " . $e->getMessage(),
                "exception" => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Teste 2: Verifica se mudanças de status em pedidos refletem
     * corretamente nos jobs de impressão associados
     */
    private function testOrderStatusChangesTriggerPrintJobsUpdates() {
        app_log("TESTE: Iniciando teste de propagação de mudanças de status de pedidos para jobs");
        
        try {
            // Se não temos um pedido de teste da etapa anterior, criar um novo
            if (empty($this->testOrderIds)) {
                $testOrderData = $this->createTestOrder();
                $orderId = $testOrderData['order_id'];
                $this->testOrderIds[] = $orderId;
                
                // Registrar jobs de teste
                $printJobs = $this->printQueueModel->getJobsByOrderId($orderId);
                if (is_array($printJobs)) {
                    foreach ($printJobs as $job) {
                        $this->testPrintJobIds[] = $job['id'];
                    }
                }
            } else {
                $orderId = $this->testOrderIds[0];
            }
            
            // Status iniciais
            $initialPrintJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            $initialStatuses = [];
            foreach ($initialPrintJobs as $job) {
                $initialStatuses[$job['id']] = $job['status'];
            }
            
            // 1. Mudar status do pedido para "Em Processamento"
            $this->orderModel->updateOrderStatus($orderId, 'processing');
            
            // 2. Aguardar propagação (simulada em ambiente de teste)
            sleep(1);
            
            // 3. Verificar se status dos jobs foram atualizados
            $updatedPrintJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            $allJobsUpdated = true;
            $statusDetails = [];
            
            foreach ($updatedPrintJobs as $job) {
                // Em ambiente real, os jobs seriam movidos para "in_queue" ou "preparing"
                $expectedStatus = 'preparing'; // Status esperado após pedido ir para "Em Processamento"
                $jobUpdated = ($job['status'] == $expectedStatus);
                $allJobsUpdated = $allJobsUpdated && $jobUpdated;
                
                $statusDetails[] = [
                    "job_id" => $job['id'],
                    "initial_status" => $initialStatuses[$job['id']] ?? 'unknown',
                    "expected_status" => $expectedStatus,
                    "actual_status" => $job['status'],
                    "updated_correctly" => $jobUpdated
                ];
            }
            
            // 4. Registrar resultado do teste
            $this->testResults["order_status_changes_trigger_print_jobs_updates"] = [
                "passed" => $allJobsUpdated,
                "details" => $allJobsUpdated 
                    ? "Todos os jobs foram atualizados corretamente" 
                    : "Alguns jobs não foram atualizados corretamente",
                "order_id" => $orderId,
                "status_changes" => $statusDetails
            ];
            
            app_log("TESTE: " . ($allJobsUpdated ? "PASSOU" : "FALHOU") . " - Teste de propagação de status de pedido para jobs");
            
        } catch (Exception $e) {
            app_log("ERRO no teste de propagação de status: " . $e->getMessage());
            $this->testResults["order_status_changes_trigger_print_jobs_updates"] = [
                "passed" => false,
                "details" => "Erro: " . $e->getMessage(),
                "exception" => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Teste 3: Verifica se finalizações de jobs de impressão atualizam
     * corretamente o status dos pedidos associados
     */
    private function testPrintJobStatusUpdatesOrderStatus() {
        app_log("TESTE: Iniciando teste de atualização de pedido a partir de mudanças em jobs");
        
        try {
            // Se não temos um pedido de teste das etapas anteriores, criar um novo
            if (empty($this->testOrderIds)) {
                $testOrderData = $this->createTestOrder();
                $orderId = $testOrderData['order_id'];
                $this->testOrderIds[] = $orderId;
                
                // Registrar jobs de teste
                $printJobs = $this->printQueueModel->getJobsByOrderId($orderId);
                if (is_array($printJobs)) {
                    foreach ($printJobs as $job) {
                        $this->testPrintJobIds[] = $job['id'];
                    }
                }
            } else {
                $orderId = $this->testOrderIds[0];
            }
            
            // Status inicial do pedido
            $initialOrder = $this->orderModel->getOrder($orderId);
            $initialOrderStatus = $initialOrder['status'];
            
            // 1. Obter jobs de impressão associados ao pedido
            $printJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            
            // 2. Atualizar todos os jobs para "completed" (concluído)
            foreach ($printJobs as $job) {
                $this->printQueueModel->updateJobStatus($job['id'], 'completed');
            }
            
            // 3. Aguardar propagação (simulada em ambiente de teste)
            sleep(1);
            
            // 4. Verificar se status do pedido foi atualizado
            $updatedOrder = $this->orderModel->getOrder($orderId);
            
            // Em ambiente real, o pedido seria movido para "completed" ou para o próximo estado lógico
            // dependendo do fluxo de trabalho específico da loja
            $expectedOrderStatus = 'completed'; // Status esperado após todos os jobs serem concluídos
            $orderStatusUpdated = ($updatedOrder['status'] == $expectedOrderStatus);
            
            // 5. Registrar resultado do teste
            $this->testResults["print_job_status_updates_order_status"] = [
                "passed" => $orderStatusUpdated,
                "details" => $orderStatusUpdated 
                    ? "Status do pedido foi atualizado corretamente após conclusão dos jobs" 
                    : "Status do pedido não foi atualizado corretamente após conclusão dos jobs",
                "order_id" => $orderId,
                "initial_order_status" => $initialOrderStatus,
                "expected_order_status" => $expectedOrderStatus,
                "actual_order_status" => $updatedOrder['status']
            ];
            
            app_log("TESTE: " . ($orderStatusUpdated ? "PASSOU" : "FALHOU") . " - Teste de atualização de pedido a partir de jobs");
            
        } catch (Exception $e) {
            app_log("ERRO no teste de atualização de pedido: " . $e->getMessage());
            $this->testResults["print_job_status_updates_order_status"] = [
                "passed" => false,
                "details" => "Erro: " . $e->getMessage(),
                "exception" => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Teste 4: Verifica se cancelamentos propagam corretamente entre os sistemas
     */
    private function testCancellationPropagatesBetweenSystems() {
        app_log("TESTE: Iniciando teste de propagação de cancelamento");
        
        try {
            // Criar um novo pedido para este teste específico
            $testOrderData = $this->createTestOrder();
            $orderId = $testOrderData['order_id'];
            $this->testOrderIds[] = $orderId;
            
            // Registrar jobs de teste
            $printJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            $jobIds = [];
            if (is_array($printJobs)) {
                foreach ($printJobs as $job) {
                    $this->testPrintJobIds[] = $job['id'];
                    $jobIds[] = $job['id'];
                }
            }
            
            // 1. Cancelar o pedido
            $this->orderModel->updateOrderStatus($orderId, 'cancelled');
            
            // 2. Aguardar propagação (simulada em ambiente de teste)
            sleep(1);
            
            // 3. Verificar se todos os jobs associados foram cancelados
            $updatedPrintJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            $allJobsCancelled = true;
            $jobDetails = [];
            
            foreach ($updatedPrintJobs as $job) {
                $jobCancelled = ($job['status'] == 'cancelled');
                $allJobsCancelled = $allJobsCancelled && $jobCancelled;
                
                $jobDetails[] = [
                    "job_id" => $job['id'],
                    "status" => $job['status'],
                    "cancelled" => $jobCancelled
                ];
            }
            
            // 4. Registrar resultado do teste
            $this->testResults["cancellation_propagates_between_systems"] = [
                "passed" => $allJobsCancelled,
                "details" => $allJobsCancelled 
                    ? "Todos os jobs foram cancelados corretamente após cancelamento do pedido" 
                    : "Alguns jobs não foram cancelados corretamente após cancelamento do pedido",
                "order_id" => $orderId,
                "job_statuses" => $jobDetails
            ];
            
            app_log("TESTE: " . ($allJobsCancelled ? "PASSOU" : "FALHOU") . " - Teste de propagação de cancelamento");
            
            // Teste reverso: cancelar um job e verificar impacto no pedido
            if (!empty($jobIds)) {
                // Criar um novo pedido para o teste reverso
                $testOrderData = $this->createTestOrder();
                $reverseOrderId = $testOrderData['order_id'];
                $this->testOrderIds[] = $reverseOrderId;
                
                // Obter jobs do novo pedido
                $reverseJobs = $this->printQueueModel->getJobsByOrderId($reverseOrderId);
                if (is_array($reverseJobs) && !empty($reverseJobs)) {
                    $reverseJobId = $reverseJobs[0]['id'];
                    $this->testPrintJobIds[] = $reverseJobId;
                    
                    // Cancelar um job
                    $this->printQueueModel->updateJobStatus($reverseJobId, 'cancelled');
                    
                    // Aguardar propagação
                    sleep(1);
                    
                    // Verificar impacto no pedido
                    $updatedReverseOrder = $this->orderModel->getOrder($reverseOrderId);
                    
                    // O comportamento esperado pode variar: 
                    // - Pode cancelar todo o pedido
                    // - Pode manter o pedido e apenas remover o item específico
                    // Para este teste, consideramos que só cancelaria o pedido se todos os jobs fossem cancelados
                    
                    $remainingActiveJobs = $this->printQueueModel->countActiveJobsByOrderId($reverseOrderId);
                    $expectedOrderStatus = ($remainingActiveJobs == 0) ? 'cancelled' : 'processing';
                    $correctOrderStatus = ($updatedReverseOrder['status'] == $expectedOrderStatus);
                    
                    // Registrar resultado do teste reverso
                    $this->testResults["job_cancellation_impacts_order"] = [
                        "passed" => $correctOrderStatus,
                        "details" => $correctOrderStatus 
                            ? "Status do pedido foi atualizado corretamente após cancelamento de job" 
                            : "Status do pedido não foi atualizado corretamente após cancelamento de job",
                        "order_id" => $reverseOrderId,
                        "cancelled_job_id" => $reverseJobId,
                        "remaining_active_jobs" => $remainingActiveJobs,
                        "expected_order_status" => $expectedOrderStatus,
                        "actual_order_status" => $updatedReverseOrder['status']
                    ];
                    
                    app_log("TESTE: " . ($correctOrderStatus ? "PASSOU" : "FALHOU") . " - Teste de impacto de cancelamento de job no pedido");
                }
            }
            
        } catch (Exception $e) {
            app_log("ERRO no teste de cancelamento: " . $e->getMessage());
            $this->testResults["cancellation_propagates_between_systems"] = [
                "passed" => false,
                "details" => "Erro: " . $e->getMessage(),
                "exception" => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Teste 5: Verifica o fluxo de trabalho completo
     */
    private function testCompleteWorkflow() {
        app_log("TESTE: Iniciando teste de fluxo de trabalho completo");
        
        try {
            // Criar um novo pedido para este teste específico
            $testOrderData = $this->createTestOrder();
            $orderId = $testOrderData['order_id'];
            $this->testOrderIds[] = $orderId;
            
            // Registrar jobs iniciais
            $initialJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            if (is_array($initialJobs)) {
                foreach ($initialJobs as $job) {
                    $this->testPrintJobIds[] = $job['id'];
                }
            }
            
            // Array para rastrear cada etapa do workflow
            $workflowSteps = [];
            
            // 1. Pedido criado - verificar jobs iniciais
            $workflowSteps['order_created'] = [
                "status" => true,
                "details" => "Pedido #{$orderId} criado com sucesso",
                "jobs_count" => is_array($initialJobs) ? count($initialJobs) : 0
            ];
            
            // 2. Pedido em processamento
            $this->orderModel->updateOrderStatus($orderId, 'processing');
            sleep(1);
            $processingJobs = $this->printQueueModel->getJobsByOrderId($orderId);
            $allJobsProcessing = true;
            foreach ($processingJobs as $job) {
                if ($job['status'] != 'preparing') {
                    $allJobsProcessing = false;
                    break;
                }
            }
            $workflowSteps['order_processing'] = [
                "status" => $allJobsProcessing,
                "details" => $allJobsProcessing 
                    ? "Todos os jobs passaram para 'preparing'" 
                    : "Nem todos os jobs passaram para 'preparing'",
                "jobs_status" => array_map(function($job) { return $job['status']; }, $processingJobs)
            ];
            
            // 3. Início da impressão
            foreach ($processingJobs as $job) {
                $this->printQueueModel->updateJobStatus($job['id'], 'printing');
            }
            sleep(1);
            $printingOrder = $this->orderModel->getOrder($orderId);
            $orderStatusInProduction = ($printingOrder['status'] == 'in_production');
            $workflowSteps['printing_started'] = [
                "status" => $orderStatusInProduction,
                "details" => $orderStatusInProduction 
                    ? "Status do pedido foi atualizado para 'in_production'" 
                    : "Status do pedido não foi atualizado para 'in_production'",
                "order_status" => $printingOrder['status']
            ];
            
            // 4. Finalização da impressão
            foreach ($processingJobs as $job) {
                $this->printQueueModel->updateJobStatus($job['id'], 'completed');
            }
            sleep(1);
            $completedOrder = $this->orderModel->getOrder($orderId);
            $orderStatusCompleted = ($completedOrder['status'] == 'completed');
            $workflowSteps['printing_completed'] = [
                "status" => $orderStatusCompleted,
                "details" => $orderStatusCompleted 
                    ? "Status do pedido foi atualizado para 'completed'" 
                    : "Status do pedido não foi atualizado para 'completed'",
                "order_status" => $completedOrder['status']
            ];
            
            // 5. Verificar fluxo completo
            $workflowPassed = $workflowSteps['order_created']['status'] &&
                             $workflowSteps['order_processing']['status'] &&
                             $workflowSteps['printing_started']['status'] &&
                             $workflowSteps['printing_completed']['status'];
            
            // Registrar resultado do teste
            $this->testResults["complete_workflow"] = [
                "passed" => $workflowPassed,
                "details" => $workflowPassed 
                    ? "Fluxo de trabalho completo executado com sucesso" 
                    : "Falha em pelo menos uma etapa do fluxo de trabalho",
                "order_id" => $orderId,
                "workflow_steps" => $workflowSteps
            ];
            
            app_log("TESTE: " . ($workflowPassed ? "PASSOU" : "FALHOU") . " - Teste de fluxo de trabalho completo");
            
        } catch (Exception $e) {
            app_log("ERRO no teste de fluxo de trabalho: " . $e->getMessage());
            $this->testResults["complete_workflow"] = [
                "passed" => false,
                "details" => "Erro: " . $e->getMessage(),
                "exception" => $e->getTraceAsString()
            ];
        }
    }
    
    /**
     * Cria um pedido de teste com produtos sob demanda
     * 
     * @return array Dados do pedido de teste criado
     */
    private function createTestOrder() {
        // 1. Obter produtos sob demanda para o teste
        $productsOnDemand = $this->productModel->getProductsByAvailability('Sob Encomenda', 3);
        
        if (empty($productsOnDemand)) {
            throw new Exception("Não há produtos sob demanda disponíveis para o teste");
        }
        
        // 2. Criar dados do pedido
        $orderData = [
            'user_id' => 1, // ID de usuário de teste
            'status' => 'pending',
            'total' => 0,
            'payment_method' => 'test',
            'shipping_method' => 'test',
            'shipping_cost' => 0,
            'notes' => 'Pedido de teste - Integração entre Pedidos e Fila de Impressão',
            'created_at' => date('Y-m-d H:i:s'),
            'test_order' => 1 // Marcar como pedido de teste
        ];
        
        // 3. Adicionar itens ao pedido
        $orderItems = [];
        $totalAmount = 0;
        
        foreach ($productsOnDemand as $product) {
            $price = $product['sale_price'] ? $product['sale_price'] : $product['price'];
            $quantity = 1;
            $subtotal = $price * $quantity;
            $totalAmount += $subtotal;
            
            $orderItems[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'options' => json_encode([
                    'color' => 'blue',
                    'scale' => '28mm',
                    'custom_options' => 'Test options'
                ])
            ];
        }
        
        // Atualizar total do pedido
        $orderData['total'] = $totalAmount;
        
        // 4. Criar o pedido no banco de dados
        $orderId = $this->orderModel->createOrder($orderData, $orderItems);
        
        if (!$orderId) {
            throw new Exception("Falha ao criar pedido de teste");
        }
        
        return [
            'order_id' => $orderId,
            'custom_print_items_count' => count($orderItems),
            'total_amount' => $totalAmount
        ];
    }
    
    /**
     * Limpa dados de teste criados durante os testes
     */
    private function cleanupTestData() {
        if ($this->isTestMode) {
            app_log("TESTE: Limpando dados de teste");
            
            // Limpar pedidos de teste
            foreach ($this->testOrderIds as $orderId) {
                try {
                    // Em produção, basta marcar como "teste_concluído" em vez de excluir
                    $this->orderModel->updateOrderNotes($orderId, "TESTE CONCLUÍDO - " . date('Y-m-d H:i:s'));
                    app_log("TESTE: Marcado pedido #{$orderId} como teste concluído");
                } catch (Exception $e) {
                    app_log("ERRO ao limpar pedido de teste #{$orderId}: " . $e->getMessage());
                }
            }
            
            // Limpar jobs de impressão de teste
            foreach ($this->testPrintJobIds as $jobId) {
                try {
                    // Em produção, basta marcar como "teste_concluído" em vez de excluir
                    $this->printQueueModel->updateJobNotes($jobId, "TESTE CONCLUÍDO - " . date('Y-m-d H:i:s'));
                    app_log("TESTE: Marcado job de impressão #{$jobId} como teste concluído");
                } catch (Exception $e) {
                    app_log("ERRO ao limpar job de teste #{$jobId}: " . $e->getMessage());
                }
            }
        } else {
            app_log("ALERTA: Limpeza de dados ignorada (não estamos em modo de teste)");
        }
    }
}

// Executar testes apenas se este arquivo for chamado diretamente
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    // Configurar cabeçalhos para HTML limpo
    header('Content-Type: text/html; charset=utf-8');
    
    // Início da página HTML
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teste de Integração: Pedidos → Fila de Impressão</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { padding: 20px; }
            .test-result { margin-bottom: 20px; border-left: 5px solid #eee; padding-left: 15px; }
            .test-passed { border-left-color: #198754; }
            .test-failed { border-left-color: #dc3545; }
            .result-details { font-family: monospace; white-space: pre-wrap; }
            .debug-info { background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1 class="mb-4">Teste de Integração: Sistema de Pedidos → Fila de Impressão</h1>';
    
    // Instanciar e executar testes
    try {
        $tester = new OrderToPrintQueueTest();
        $results = $tester->runAllTests();
        
        // Exibir resultados
        $passedCount = 0;
        $failedCount = 0;
        
        foreach ($results as $testName => $result) {
            $statusClass = $result['passed'] ? 'test-passed' : 'test-failed';
            $statusBadge = $result['passed'] ? 
                '<span class="badge bg-success">PASSOU</span>' : 
                '<span class="badge bg-danger">FALHOU</span>';
            
            if ($result['passed']) {
                $passedCount++;
            } else {
                $failedCount++;
            }
            
            echo '<div class="test-result ' . $statusClass . '">';
            echo '<h3>' . ucwords(str_replace('_', ' ', $testName)) . ' ' . $statusBadge . '</h3>';
            echo '<p>' . $result['details'] . '</p>';
            
            // Exibir dados detalhados do teste
            if (isset($result['exception'])) {
                echo '<div class="alert alert-danger mt-2">';
                echo '<h5>Exceção:</h5>';
                echo '<pre>' . $result['exception'] . '</pre>';
                echo '</div>';
            } else {
                echo '<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#details-' . $testName . '">
                    Ver Detalhes
                </button>';
                
                echo '<div class="collapse mt-2" id="details-' . $testName . '">';
                echo '<div class="card card-body debug-info">';
                echo '<h5>Dados do Teste:</h5>';
                echo '<pre class="result-details">' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                echo '</div>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Exibir resumo dos resultados
        echo '<div class="alert ' . ($failedCount == 0 ? 'alert-success' : 'alert-warning') . ' mt-4">';
        echo '<h4>Resumo:</h4>';
        echo '<p>' . $passedCount . ' teste(s) passaram, ' . $failedCount . ' teste(s) falharam.</p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<h4>Erro ao executar testes:</h4>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
        echo '</div>';
    }
    
    // Fim da página HTML
    echo '
            <hr>
            <div class="text-center mt-3 mb-5">
                <a href="../admin/tests" class="btn btn-primary">Voltar para Testes</a>
                <a href="../admin/dashboard" class="btn btn-secondary ms-2">Dashboard Admin</a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>';
}
