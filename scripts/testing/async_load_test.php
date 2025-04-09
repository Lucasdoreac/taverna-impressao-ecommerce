<?php
/**
 * Script para testes de carga específicos do sistema assíncrono
 * 
 * Este script testa o comportamento do sistema assíncrono sob carga,
 * verificando sua capacidade de processar múltiplas tarefas paralelas
 * e sua resiliência a falhas.
 * 
 * @package App\Scripts\Testing
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Classe para testes de carga do sistema assíncrono
 */
class AsyncLoadTester {
    use InputValidationTrait;
    
    /** @var PDO Conexão com banco de dados */
    private $db;
    
    /** @var string URL base para testes */
    private $baseUrl;
    
    /** @var array Configurações do teste */
    private $config = [
        'processes' => 5,       // Número de processos assíncronos a criar
        'concurrent' => 2,      // Número de processos concorrentes máximos
        'process_type' => 'test_async',  // Tipo de processo para teste
        'timeout' => 60,        // Timeout para aguardar conclusão (segundos)
        'file_sizes' => [       // Tamanhos dos arquivos a gerar para teste (KB)
            100, 250, 500, 1000, 2000
        ],
        'user_id' => 1          // ID do usuário para teste
    ];
    
    /** @var array Resultados dos testes */
    private $results = [
        'processes' => [],
        'summary' => [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'timeout' => 0,
            'avg_duration' => 0,
            'max_duration' => 0,
            'min_duration' => PHP_INT_MAX
        ]
    ];
    
    /** @var array Tokens CSRF obtidos por sessão */
    private $csrfTokens = [];
    
    /** @var resource Contexto cURL */
    private $ch;
    
    /**
     * Construtor
     * 
     * @param PDO $db Conexão com banco de dados
     * @param string $baseUrl URL base para testes
     * @param array $config Configurações de teste (opcional)
     */
    public function __construct(PDO $db, $baseUrl, array $config = []) {
        $this->db = $db;
        $this->baseUrl = $baseUrl;
        
        // Mesclar configurações fornecidas com as padrões
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        
        // Validar configurações
        $this->validateConfig();
        
        // Inicializar cURL
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, tempnam("/tmp", "cookies"));
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, tempnam("/tmp", "cookies"));
    }
    
    /**
     * Destrutor - Libera recursos
     */
    public function __destruct() {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }
    
    /**
     * Valida as configurações do teste
     */
    private function validateConfig() {
        // Validar número de processos
        $this->config['processes'] = $this->validateInput($this->config['processes'], 'int', [
            'required' => true,
            'min' => 1,
            'max' => 100
        ]);
        
        // Validar concorrência
        $this->config['concurrent'] = $this->validateInput($this->config['concurrent'], 'int', [
            'required' => true,
            'min' => 1,
            'max' => min(20, $this->config['processes'])
        ]);
        
        // Validar timeout
        $this->config['timeout'] = $this->validateInput($this->config['timeout'], 'int', [
            'required' => true,
            'min' => 10,
            'max' => 3600
        ]);
        
        if (!is_array($this->config['file_sizes']) || empty($this->config['file_sizes'])) {
            $this->config['file_sizes'] = [500]; // Fallback para 500KB
        }
    }
    
    /**
     * Executa o teste de carga assíncrono
     * 
     * @return array Resultados dos testes
     */
    public function run() {
        $startTime = microtime(true);
        
        echo "Iniciando teste de carga assíncrono com {$this->config['processes']} processos ";
        echo "(máximo {$this->config['concurrent']} concorrentes)...\n\n";
        
        // Obter token CSRF para requisições
        $this->obtainCsrfToken();
        
        // Criar processos
        $processes = $this->createProcesses();
        
        // Monitorar processos até conclusão
        $this->monitorProcesses($processes);
        
        // Calcular estatísticas
        $this->calculateStatistics();
        
        // Exibir resultados
        $this->printResults();
        
        return $this->results;
    }
    
    /**
     * Obtém token CSRF para requisições
     * 
     * @return bool Sucesso da operação
     */
    private function obtainCsrfToken() {
        echo "Obtendo token CSRF para autenticação...\n";
        
        // Acessar uma página com formulário para obter token
        $url = '/account/profile';
        $response = $this->sendRequest($url, [], 'GET');
        
        // Extrair token CSRF
        if (preg_match('/<input type="hidden" name="csrf_token" value="([^"]+)"/', $response, $matches)) {
            $this->csrfTokens['form'] = $matches[1];
            echo "Token CSRF para formulários obtido: " . substr($this->csrfTokens['form'], 0, 10) . "...\n";
        } else {
            echo "AVISO: Não foi possível obter token CSRF para formulários\n";
        }
        
        // Obter token para AJAX
        $url = '/app/dashboard';
        $response = $this->sendRequest($url, [], 'GET');
        
        if (preg_match('/csrfToken\s*=\s*[\'"]([^\'"]+)[\'"]/', $response, $matches)) {
            $this->csrfTokens['ajax'] = $matches[1];
            echo "Token CSRF para AJAX obtido: " . substr($this->csrfTokens['ajax'], 0, 10) . "...\n";
        } else {
            $this->csrfTokens['ajax'] = $this->csrfTokens['form'] ?? null;
            echo "AVISO: Usando token de formulário para AJAX\n";
        }
        
        return !empty($this->csrfTokens['form']) || !empty($this->csrfTokens['ajax']);
    }
    
    /**
     * Cria os processos assíncronos para teste
     * 
     * @return array Processos criados
     */
    private function createProcesses() {
        echo "\nCriando {$this->config['processes']} processos assíncronos...\n";
        
        $processes = [];
        
        // Determinar o endpoint para criação de processos
        $endpoint = '/api/async/create';
        
        // Criar processos com base na configuração
        for ($i = 0; $i < $this->config['processes']; $i++) {
            // Selecionar um tamanho de arquivo aleatório
            $sizeIndex = $i % count($this->config['file_sizes']);
            $fileSize = $this->config['file_sizes'][$sizeIndex];
            
            // Preparar parâmetros
            $params = [
                'process_type' => $this->config['process_type'],
                'name' => "Teste de carga assíncrono #" . ($i + 1),
                'file_size' => $fileSize,
                'user_id' => $this->config['user_id'],
                'parameters' => json_encode([
                    'test_id' => uniqid('async_test_'),
                    'timestamp' => time(),
                    'complexity' => mt_rand(1, 5)
                ]),
                'csrf_token' => $this->csrfTokens['form'] ?? ''
            ];
            
            // Enviar requisição para criar processo
            $response = $this->sendRequest($endpoint, $params, 'POST');
            
            // Processar resposta
            $result = json_decode($response, true);
            
            if (isset($result['success']) && $result['success'] && isset($result['data']['process_id'])) {
                $processId = $result['data']['process_id'];
                echo "  Processo #" . ($i + 1) . " criado: $processId (arquivo $fileSize KB)\n";
                
                $processes[] = [
                    'id' => $processId,
                    'index' => $i + 1,
                    'file_size' => $fileSize,
                    'created_at' => time(),
                    'status' => 'created'
                ];
                
                // Registrar no array de resultados
                $this->results['processes'][$processId] = [
                    'id' => $processId,
                    'index' => $i + 1,
                    'file_size' => $fileSize,
                    'status' => 'created',
                    'created_at' => time(),
                    'start_time' => null,
                    'end_time' => null,
                    'duration' => null,
                    'error' => null
                ];
            } else {
                $error = isset($result['error']) ? $result['error'] : 'Erro desconhecido';
                echo "  ERRO ao criar processo #" . ($i + 1) . ": $error\n";
                
                // Registrar falha
                $this->results['processes']['failed_' . uniqid()] = [
                    'index' => $i + 1,
                    'file_size' => $fileSize,
                    'status' => 'failed',
                    'created_at' => time(),
                    'error' => $error
                ];
            }
            
            // Pequena pausa para não sobrecarregar
            usleep(200000); // 200ms
        }
        
        echo "Total de " . count($processes) . " processos criados com sucesso.\n";
        
        return $processes;
    }
    
    /**
     * Monitora os processos até conclusão
     * 
     * @param array $processes Lista de processos a monitorar
     */
    private function monitorProcesses($processes) {
        echo "\nIniciando monitoramento de processos...\n";
        
        // Inicializar contadores
        $runningProcesses = count($processes);
        $startedProcesses = 0;
        $completedProcesses = 0;
        
        // Acompanhar estado dos processos
        $processStates = [];
        foreach ($processes as $process) {
            $processStates[$process['id']] = [
                'id' => $process['id'],
                'status' => 'pending',
                'progress' => 0,
                'last_update' => time()
            ];
        }
        
        // Endpoint para verificação de status
        $statusEndpoint = '/api/async/status';
        
        // Limite máximo de processos concorrentes
        $maxConcurrent = $this->config['concurrent'];
        
        // Iniciar processos até o limite de concorrência
        $availableSlots = $maxConcurrent;
        
        // Fila de processos a iniciar
        $pendingProcesses = array_values($processes);
        
        // Monitorar até que todos os processos sejam concluídos ou timeout
        $startTime = time();
        $lastDisplayTime = 0;
        $displayInterval = 5; // Exibir atualização a cada 5 segundos
        
        while ($runningProcesses > 0) {
            $currentTime = time();
            
            // Verificar timeout global
            if (($currentTime - $startTime) > $this->config['timeout']) {
                echo "\nTIMEOUT: Tempo máximo excedido após " . ($currentTime - $startTime) . " segundos.\n";
                
                // Marcar processos não concluídos como timeout
                foreach ($processStates as $processId => $state) {
                    if ($state['status'] !== 'completed' && $state['status'] !== 'failed') {
                        $processStates[$processId]['status'] = 'timeout';
                        $this->results['processes'][$processId]['status'] = 'timeout';
                        $this->results['processes'][$processId]['error'] = 'Timeout global';
                    }
                }
                
                break;
            }
            
            // Iniciar processos pendentes se houver slots disponíveis
            while ($availableSlots > 0 && !empty($pendingProcesses)) {
                $process = array_shift($pendingProcesses);
                $processId = $process['id'];
                
                // Iniciar o processo
                $startParams = [
                    'process_id' => $processId,
                    'csrf_token' => $this->csrfTokens['form'] ?? ''
                ];
                
                $response = $this->sendRequest('/api/async/start', $startParams, 'POST');
                $result = json_decode($response, true);
                
                if (isset($result['success']) && $result['success']) {
                    echo "Iniciado processo #{$process['index']} ($processId)\n";
                    $processStates[$processId]['status'] = 'running';
                    $processStates[$processId]['last_update'] = $currentTime;
                    
                    // Atualizar informações no resultado
                    $this->results['processes'][$processId]['status'] = 'running';
                    $this->results['processes'][$processId]['start_time'] = $currentTime;
                    
                    $startedProcesses++;
                    $availableSlots--;
                } else {
                    $error = isset($result['error']) ? $result['error'] : 'Erro desconhecido';
                    echo "ERRO ao iniciar processo #{$process['index']} ($processId): $error\n";
                    
                    $processStates[$processId]['status'] = 'failed';
                    $processStates[$processId]['error'] = $error;
                    
                    // Atualizar informações no resultado
                    $this->results['processes'][$processId]['status'] = 'failed';
                    $this->results['processes'][$processId]['error'] = $error;
                    
                    $runningProcesses--;
                    // Não reduzir slots disponíveis, pois o processo não iniciou
                }
                
                // Pequena pausa entre inicializações
                usleep(100000); // 100ms
            }
            
            // Verificar status dos processos em execução
            $completed = 0;
            
            foreach ($processStates as $processId => $state) {
                if ($state['status'] === 'running') {
                    // Verificar status
                    $statusParams = [
                        'process_id' => $processId
                    ];
                    
                    $response = $this->sendRequest($statusEndpoint, $statusParams, 'GET');
                    $result = json_decode($response, true);
                    
                    if (isset($result['success']) && $result['success'] && isset($result['data'])) {
                        $status = $result['data']['status'] ?? 'unknown';
                        $progress = $result['data']['progress'] ?? 0;
                        
                        // Atualizar estado
                        $processStates[$processId]['progress'] = $progress;
                        $processStates[$processId]['last_update'] = $currentTime;
                        
                        // Verificar se concluído
                        if ($status === 'completed' || $status === 'failed') {
                            $processStates[$processId]['status'] = $status;
                            
                            // Atualizar informações no resultado
                            $this->results['processes'][$processId]['status'] = $status;
                            $this->results['processes'][$processId]['end_time'] = $currentTime;
                            $this->results['processes'][$processId]['duration'] = 
                                $currentTime - $this->results['processes'][$processId]['start_time'];
                            
                            if ($status === 'failed' && isset($result['data']['error'])) {
                                $this->results['processes'][$processId]['error'] = $result['data']['error'];
                            }
                            
                            echo "Processo #{$this->results['processes'][$processId]['index']} ";
                            echo "($processId) " . ($status === 'completed' ? 'concluído' : 'falhou');
                            echo " após " . $this->results['processes'][$processId]['duration'] . " segundos\n";
                            
                            $completed++;
                            $availableSlots++;
                        }
                    } else {
                        // Verificar timeout para este processo específico
                        $processTimeout = 120; // 2 minutos sem atualização
                        if (($currentTime - $state['last_update']) > $processTimeout) {
                            echo "TIMEOUT: Processo $processId sem atualização por mais de 2 minutos.\n";
                            
                            $processStates[$processId]['status'] = 'timeout';
                            
                            // Atualizar informações no resultado
                            $this->results['processes'][$processId]['status'] = 'timeout';
                            $this->results['processes'][$processId]['end_time'] = $currentTime;
                            $this->results['processes'][$processId]['duration'] = 
                                $currentTime - $this->results['processes'][$processId]['start_time'];
                            $this->results['processes'][$processId]['error'] = 'Timeout de processo';
                            
                            $completed++;
                            $availableSlots++;
                        }
                    }
                }
            }
            
            // Atualizar contadores
            $runningProcesses -= $completed;
            $completedProcesses += $completed;
            
            // Exibir progresso a cada intervalo
            if (($currentTime - $lastDisplayTime) >= $displayInterval) {
                echo "\nStatus: " . 
                     "$startedProcesses iniciados, " . 
                     "$completedProcesses concluídos, " . 
                     "$runningProcesses pendentes ou em execução";
                
                if ($runningProcesses > 0) {
                    echo " (máx. $maxConcurrent concorrentes)";
                }
                
                echo "\n";
                
                $lastDisplayTime = $currentTime;
            }
            
            // Pequena pausa para não sobrecarregar
            usleep(500000); // 500ms
        }
        
        echo "\nTodos os processos concluídos ou em timeout.\n";
    }
    
    /**
     * Calcula estatísticas finais dos testes
     */
    private function calculateStatistics() {
        // Contadores
        $totalProcesses = count($this->results['processes']);
        $successCount = 0;
        $failedCount = 0;
        $timeoutCount = 0;
        $totalDuration = 0;
        $maxDuration = 0;
        $minDuration = PHP_INT_MAX;
        
        foreach ($this->results['processes'] as $processId => $process) {
            if ($process['status'] === 'completed') {
                $successCount++;
                
                // Computar estatísticas de tempo apenas para processos concluídos
                if (isset($process['duration']) && $process['duration'] > 0) {
                    $totalDuration += $process['duration'];
                    $maxDuration = max($maxDuration, $process['duration']);
                    $minDuration = min($minDuration, $process['duration']);
                }
            } elseif ($process['status'] === 'failed') {
                $failedCount++;
            } elseif ($process['status'] === 'timeout') {
                $timeoutCount++;
            }
        }
        
        // Calcular média de duração
        $avgDuration = ($successCount > 0) ? ($totalDuration / $successCount) : 0;
        
        // Atualizar resumo
        $this->results['summary'] = [
            'total' => $totalProcesses,
            'success' => $successCount,
            'failed' => $failedCount,
            'timeout' => $timeoutCount,
            'avg_duration' => $avgDuration,
            'max_duration' => $maxDuration,
            'min_duration' => ($minDuration < PHP_INT_MAX) ? $minDuration : 0
        ];
    }
    
    /**
     * Imprime resultados dos testes
     */
    private function printResults() {
        echo "\n=================================\n";
        echo "RESULTADOS DO TESTE ASSÍNCRONO\n";
        echo "=================================\n\n";
        
        $summary = $this->results['summary'];
        
        echo "Total de processos: {$summary['total']}\n";
        echo "Processos bem-sucedidos: {$summary['success']} (" . 
             number_format(($summary['success'] / $summary['total']) * 100, 1) . "%)\n";
        echo "Processos com falha: {$summary['failed']} (" . 
             number_format(($summary['failed'] / $summary['total']) * 100, 1) . "%)\n";
        echo "Processos em timeout: {$summary['timeout']} (" . 
             number_format(($summary['timeout'] / $summary['total']) * 100, 1) . "%)\n\n";
        
        if ($summary['success'] > 0) {
            echo "Tempo médio de processamento: " . number_format($summary['avg_duration'], 1) . " segundos\n";
            echo "Tempo mínimo: " . number_format($summary['min_duration'], 1) . " segundos\n";
            echo "Tempo máximo: " . number_format($summary['max_duration'], 1) . " segundos\n\n";
        }
        
        // Exibir falhas, se houver
        $hasFailures = false;
        foreach ($this->results['processes'] as $processId => $process) {
            if ($process['status'] === 'failed' && isset($process['error'])) {
                if (!$hasFailures) {
                    echo "Detalhes de falhas:\n";
                    $hasFailures = true;
                }
                
                echo "  Processo #{$process['index']} ($processId): {$process['error']}\n";
            }
        }
        
        if (!$hasFailures) {
            echo "Nenhuma falha específica registrada.\n";
        }
        
        echo "\n";
    }
    
    /**
     * Envia uma requisição HTTP
     * 
     * @param string $endpoint Endpoint da API
     * @param array $params Parâmetros da requisição
     * @param string $method Método HTTP (GET, POST)
     * @param array $headers Headers HTTP adicionais
     * @return string Resposta da requisição
     */
    private function sendRequest($endpoint, array $params = [], $method = 'GET', array $headers = []) {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        // Configurar método
        if ($method === 'POST') {
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
                curl_setopt($this->ch, CURLOPT_URL, $url);
            }
        }
        
        // Configurar headers
        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/html,application/json,application/xhtml+xml'
        ];
        
        // Adicionar header X-CSRF-Token para AJAX, se disponível
        if (!empty($this->csrfTokens['ajax'])) {
            $defaultHeaders[] = 'X-CSRF-Token: ' . $this->csrfTokens['ajax'];
        }
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $allHeaders);
        
        // Executar requisição
        $response = curl_exec($this->ch);
        
        // Verificar erros
        if (curl_errno($this->ch)) {
            throw new Exception('Erro cURL: ' . curl_error($this->ch));
        }
        
        return $response;
    }
}

// Ponto de entrada do script
try {
    echo "=================================\n";
    echo "Teste de Carga Assíncrono - Taverna da Impressão 3D\n";
    echo "=================================\n\n";
    
    // Ler parâmetros da linha de comando
    $options = getopt('', ['url:', 'processes:', 'concurrent:']);
    
    // Configuração padrão
    $baseUrl = 'http://localhost:8000';
    $config = [];
    
    // Processar argumentos
    if (isset($options['url'])) {
        $baseUrl = $options['url'];
    }
    
    if (isset($options['processes'])) {
        $config['processes'] = (int)$options['processes'];
    }
    
    if (isset($options['concurrent'])) {
        $config['concurrent'] = (int)$options['concurrent'];
    }
    
    echo "URL base: $baseUrl\n";
    if (isset($config['processes'])) {
        echo "Processos assíncronos: {$config['processes']}\n";
    }
    if (isset($config['concurrent'])) {
        echo "Concorrência máxima: {$config['concurrent']}\n";
    }
    echo "\n";
    
    // Inicializar conexão com o banco de dados
    $pdo = require ROOT_DIR . '/app/config/database.php';
    
    // Executar teste
    $tester = new AsyncLoadTester($pdo, $baseUrl, $config);
    $results = $tester->run();
    
    // Código de saída baseado no sucesso do teste
    $successRate = $results['summary']['success'] / $results['summary']['total'];
    $exitCode = ($successRate >= 0.90) ? 0 : 1; // Sucesso se pelo menos 90% dos processos foram bem-sucedidos
    
    exit($exitCode);
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
