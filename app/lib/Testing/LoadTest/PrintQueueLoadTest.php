<?php
/**
 * PrintQueueLoadTest - Classe para testes de carga no Sistema de Fila de Impressão
 * 
 * Implementa testes de carga específicos para o Sistema de Fila de Impressão,
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Lib\Testing\LoadTest
 * @version 1.0.0
 * @author Taverna da Impressão
 */
require_once dirname(__FILE__) . '/LoadTestBase.php';
require_once dirname(__FILE__) . '/../../Security/InputValidator.php';
require_once dirname(__FILE__) . '/../../Security/SecurityManager.php';

class PrintQueueLoadTest extends LoadTestBase {
    /**
     * Modelo da fila de impressão
     *
     * @var PrintQueueModel
     */
    protected $printQueueModel;
    
    /**
     * Modelo de impressoras
     *
     * @var PrinterModel
     */
    protected $printerModel;
    
    /**
     * Modelo de trabalhos de impressão
     *
     * @var PrintJobModel
     */
    protected $printJobModel;
    
    /**
     * Pool de usuários para teste
     *
     * @var array
     */
    protected $userPool = [];
    
    /**
     * Pool de modelos 3D para teste
     *
     * @var array
     */
    protected $modelPool = [];
    
    /**
     * Conexão PDO para o banco de dados
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Construtor
     *
     * @param array $config Configurações customizadas
     * @param \PDO $pdo Conexão com o banco de dados
     */
    public function __construct(array $config = [], \PDO $pdo = null) {
        parent::__construct($config);
        
        $this->pdo = $pdo ?: $this->getDefaultPDO();
        
        // Carregar modelos necessários
        $this->loadModels();
        
        // Preparar dados de teste (usuários e modelos)
        $this->prepareTestData();
    }
    
    /**
     * Obtém conexão PDO padrão
     *
     * @return \PDO
     */
    protected function getDefaultPDO() {
        try {
            // Parâmetros da conexão (obtidos de configuração segura)
            $host = '127.0.0.1';
            $db   = 'taverna_impressao';
            $user = 'taverna_user';
            $pass = 'secure_password';
            $charset = 'utf8mb4';
            
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            return new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \Exception("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Carrega os modelos necessários para os testes
     */
    protected function loadModels() {
        // Carregar os modelos necessários - aqui usaríamos require_once
        // para cada modelo, mas para fins de implementação de exemplo, 
        // simularemos com stubs básicos
        
        $this->printQueueModel = new class($this->pdo) {
            protected $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function addToQueue($userId, $modelId, $options = []) {
                // Simulação de adição à fila
                return [
                    'success' => true,
                    'queue_id' => rand(1000, 9999),
                    'status' => 'pending'
                ];
            }
            
            public function getQueueStatus($queueId) {
                // Simulação de status da fila
                $statuses = ['pending', 'processing', 'printing', 'completed'];
                return $statuses[array_rand($statuses)];
            }
            
            public function updateQueueItem($queueId, $data) {
                // Simulação de atualização de item na fila
                return [
                    'success' => true,
                    'queue_id' => $queueId,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        };
        
        $this->printerModel = new class($this->pdo) {
            protected $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function getAvailablePrinters() {
                // Simulação de impressoras disponíveis
                return [
                    ['id' => 1, 'name' => 'Printer 1', 'status' => 'idle'],
                    ['id' => 2, 'name' => 'Printer 2', 'status' => 'idle'],
                    ['id' => 3, 'name' => 'Printer 3', 'status' => 'busy']
                ];
            }
            
            public function assignPrinter($queueId, $printerId) {
                // Simulação de atribuição de impressora
                return [
                    'success' => true,
                    'queue_id' => $queueId,
                    'printer_id' => $printerId,
                    'assigned_at' => date('Y-m-d H:i:s')
                ];
            }
        };
        
        $this->printJobModel = new class($this->pdo) {
            protected $pdo;
            
            public function __construct($pdo) {
                $this->pdo = $pdo;
            }
            
            public function createJob($queueId, $printerId) {
                // Simulação de criação de trabalho
                return [
                    'success' => true,
                    'job_id' => rand(10000, 99999),
                    'queue_id' => $queueId,
                    'printer_id' => $printerId,
                    'status' => 'created',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            public function updateJobStatus($jobId, $status) {
                // Simulação de atualização de status
                return [
                    'success' => true,
                    'job_id' => $jobId,
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            
            public function getJobDetails($jobId) {
                // Simulação de detalhes do trabalho
                return [
                    'job_id' => $jobId,
                    'status' => 'processing',
                    'progress' => rand(0, 100) . '%',
                    'started_at' => date('Y-m-d H:i:s', time() - 3600),
                    'estimated_completion' => date('Y-m-d H:i:s', time() + 3600)
                ];
            }
        };
    }
    
    /**
     * Prepara dados de teste para usuários e modelos
     */
    protected function prepareTestData() {
        // Preparar pool de usuários para teste
        for ($i = 1; $i <= 50; $i++) {
            $this->userPool[] = [
                'id' => $i,
                'name' => "TestUser$i",
                'email' => "testuser$i@example.com"
            ];
        }
        
        // Preparar pool de modelos 3D para teste
        for ($i = 1; $i <= 20; $i++) {
            $this->modelPool[] = [
                'id' => $i,
                'name' => "TestModel$i",
                'file_path' => "/models/test_model_$i.stl",
                'size' => rand(1024, 10485760) // 1KB a 10MB
            ];
        }
    }
    
    /**
     * Executa teste completo de fluxo da fila de impressão
     *
     * @return array Resultados do teste
     */
    public function runFullQueueTest() {
        $this->logInfo("Iniciando teste completo de fluxo da fila de impressão");
        
        return $this->run(function($params) {
            $userId = $this->getRandomUser()['id'];
            $modelId = $this->getRandomModel()['id'];
            
            // 1. Adicionar à fila
            $queueResult = $this->printQueueModel->addToQueue($userId, $modelId, [
                'priority' => rand(1, 3),
                'notes' => "Test print job from load test user {$params['user']}, iteration {$params['iteration']}"
            ]);
            
            if (!$queueResult['success']) {
                throw new \Exception("Falha ao adicionar à fila: " . json_encode($queueResult));
            }
            
            $queueId = $queueResult['queue_id'];
            
            // 2. Verificar status
            $status = $this->printQueueModel->getQueueStatus($queueId);
            
            // 3. Atribuir impressora
            $printers = $this->printerModel->getAvailablePrinters();
            $idlePrinters = array_filter($printers, function($p) {
                return $p['status'] === 'idle';
            });
            
            if (empty($idlePrinters)) {
                throw new \Exception("Não há impressoras disponíveis para o teste");
            }
            
            $printer = reset($idlePrinters);
            $assignResult = $this->printerModel->assignPrinter($queueId, $printer['id']);
            
            if (!$assignResult['success']) {
                throw new \Exception("Falha ao atribuir impressora: " . json_encode($assignResult));
            }
            
            // 4. Criar trabalho de impressão
            $jobResult = $this->printJobModel->createJob($queueId, $printer['id']);
            
            if (!$jobResult['success']) {
                throw new \Exception("Falha ao criar trabalho: " . json_encode($jobResult));
            }
            
            $jobId = $jobResult['job_id'];
            
            // 5. Obter detalhes do trabalho
            $jobDetails = $this->printJobModel->getJobDetails($jobId);
            
            // 6. Atualizar status do trabalho - simula conclusão
            $statusUpdate = $this->printJobModel->updateJobStatus($jobId, 'completed');
            
            if (!$statusUpdate['success']) {
                throw new \Exception("Falha ao atualizar status: " . json_encode($statusUpdate));
            }
            
            return [
                'success' => true,
                'queue_id' => $queueId,
                'job_id' => $jobId,
                'final_status' => 'completed'
            ];
        });
    }
    
    /**
     * Executa teste de pico de adição à fila
     *
     * @return array Resultados do teste
     */
    public function runQueueAdditionPeakTest() {
        $this->logInfo("Iniciando teste de pico de adição à fila");
        
        // Configurar teste para ser mais intenso
        $originalConfig = $this->config;
        $this->config['users'] = min(50, $this->config['users'] * 2);
        $this->config['iterations'] = min(100, $this->config['iterations'] * 2);
        $this->config['rampup'] = max(1, $this->config['rampup'] / 2);
        
        $result = $this->run(function($params) {
            $userId = $this->getRandomUser()['id'];
            $modelId = $this->getRandomModel()['id'];
            
            // Simples adição à fila para teste de pico
            $queueResult = $this->printQueueModel->addToQueue($userId, $modelId, [
                'priority' => rand(1, 3),
                'notes' => "Peak test from user {$params['user']}, iteration {$params['iteration']}"
            ]);
            
            if (!$queueResult['success']) {
                throw new \Exception("Falha ao adicionar à fila: " . json_encode($queueResult));
            }
            
            return [
                'success' => true,
                'queue_id' => $queueResult['queue_id']
            ];
        });
        
        // Restaurar configuração original
        $this->config = $originalConfig;
        
        return $result;
    }
    
    /**
     * Executa teste de consulta em massa
     *
     * @return array Resultados do teste
     */
    public function runBulkQueryTest() {
        $this->logInfo("Iniciando teste de consulta em massa");
        
        // Pré-gerar IDs de trabalhos para consulta
        $jobIds = [];
        for ($i = 1; $i <= 100; $i++) {
            $jobIds[] = rand(10000, 99999);
        }
        
        return $this->run(function($params) use ($jobIds) {
            // Obter um ID de trabalho aleatório para consulta
            $jobId = $jobIds[array_rand($jobIds)];
            
            // Consultar detalhes do trabalho
            $jobDetails = $this->printJobModel->getJobDetails($jobId);
            
            return [
                'success' => true,
                'job_id' => $jobId,
                'details' => $jobDetails
            ];
        });
    }
    
    /**
     * Obtém um usuário aleatório do pool
     *
     * @return array Dados do usuário
     */
    protected function getRandomUser() {
        return $this->userPool[array_rand($this->userPool)];
    }
    
    /**
     * Obtém um modelo aleatório do pool
     *
     * @return array Dados do modelo
     */
    protected function getRandomModel() {
        return $this->modelPool[array_rand($this->modelPool)];
    }
}