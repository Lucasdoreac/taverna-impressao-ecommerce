<?php
/**
 * Script para testes de carga no sistema
 * 
 * Este script realiza testes de carga básicos em endpoints críticos
 * do sistema da Taverna da Impressão 3D.
 * 
 * @package App\Scripts\Testing
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Classe para testes de carga em endpoints críticos
 */
class LoadTester {
    /** @var string URL base para testes */
    private $baseUrl;
    
    /** @var array Configurações do teste */
    private $config = [
        'users' => 10,           // Número de usuários simultâneos
        'duration' => 60,        // Duração do teste em segundos
        'ramp_up' => 5,          // Tempo para iniciar todos os usuários
        'timeout' => 30,         // Timeout das requisições em segundos
        'endpoints' => [
            // Formato: [url, método, parâmetros, descrição]
            ['/api/products', 'GET', [], 'Listagem de produtos'],
            ['/api/catalog', 'GET', ['category' => 'filaments'], 'Catálogo de filamentos'],
            ['/api/status-check', 'GET', [], 'Verificação de status'],
            ['/api/cart/summary', 'GET', [], 'Resumo do carrinho']
        ]
    ];
    
    /** @var array Resultados dos testes */
    private $results = [];
    
    /** @var array Worker threads */
    private $workers = [];
    
    /** @var bool Flag para controle de execução */
    private $running = false;
    
    /** @var float Timestamp de início do teste */
    private $startTime;
    
    /**
     * Construtor
     * 
     * @param string $baseUrl URL base para testes
     * @param array $config Configurações de teste (opcional)
     */
    public function __construct($baseUrl, array $config = []) {
        $this->baseUrl = $baseUrl;
        
        // Mesclar configurações fornecidas com as padrões
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        
        // Inicializar estrutura de resultados
        foreach ($this->config['endpoints'] as $endpoint) {
            $this->results[$endpoint[0]] = [
                'requests' => 0,
                'success' => 0,
                'failures' => 0,
                'response_times' => [],
                'min_time' => PHP_INT_MAX,
                'max_time' => 0,
                'avg_time' => 0,
                'p50' => 0,
                'p90' => 0,
                'p95' => 0,
                'errors' => []
            ];
        }
    }
    
    /**
     * Executa o teste de carga
     * 
     * @return array Resultados dos testes
     */
    public function run() {
        $this->running = true;
        $this->startTime = microtime(true);
        
        echo "Iniciando teste de carga com {$this->config['users']} usuários por {$this->config['duration']} segundos...\n\n";
        
        // Inicializar controle de progresso
        $progressInterval = 5; // Exibir progresso a cada 5 segundos
        $lastProgressTime = $this->startTime;
        
        // Criar e iniciar workers
        $this->startWorkers();
        
        // Loop principal do teste
        $endTime = $this->startTime + $this->config['duration'];
        
        while (microtime(true) < $endTime && $this->running) {
            // Exibir progresso
            $currentTime = microtime(true);
            if (($currentTime - $lastProgressTime) >= $progressInterval) {
                $elapsedSeconds = round($currentTime - $this->startTime);
                $remainingSeconds = max(0, $this->config['duration'] - $elapsedSeconds);
                
                // Calcular estatísticas parciais
                $totalRequests = 0;
                $totalSuccess = 0;
                $totalFailures = 0;
                
                foreach ($this->results as $result) {
                    $totalRequests += $result['requests'];
                    $totalSuccess += $result['success'];
                    $totalFailures += $result['failures'];
                }
                
                $rps = round($totalRequests / $elapsedSeconds, 1);
                
                echo "Progresso: " . gmdate('i:s', $elapsedSeconds) . " decorrido, " . 
                     gmdate('i:s', $remainingSeconds) . " restante, $totalRequests requisições ($rps req/s)\n";
                
                $lastProgressTime = $currentTime;
            }
            
            // Pequena pausa para não consumir CPU
            usleep(100000); // 100ms
        }
        
        // Finalizar teste
        $this->running = false;
        
        // Aguardar finalização dos workers
        $this->stopWorkers();
        
        // Calcular estatísticas finais
        $this->calculateStatistics();
        
        // Exibir resultados
        $this->printResults();
        
        return $this->results;
    }
    
    /**
     * Inicia os worker threads para simular usuários
     */
    private function startWorkers() {
        // Dividir endpoints entre workers
        $endpointsPerWorker = ceil(count($this->config['endpoints']) / $this->config['users']);
        
        for ($i = 0; $i < $this->config['users']; $i++) {
            // Cálculo do atraso de início para rampa
            $startDelay = ($i / $this->config['users']) * $this->config['ramp_up'];
            
            // Determinar endpoints para este worker
            $workerEndpoints = array_slice(
                $this->config['endpoints'],
                ($i * $endpointsPerWorker) % count($this->config['endpoints']),
                $endpointsPerWorker
            );
            
            // Se não houver endpoints suficientes, repetir do início
            if (empty($workerEndpoints)) {
                $workerEndpoints = array_slice(
                    $this->config['endpoints'],
                    0,
                    $endpointsPerWorker
                );
            }
            
            // Fork do processo (simulado em PHP)
            $pid = $this->forkWorker($i, $startDelay, $workerEndpoints);
            $this->workers[] = $pid;
            
            echo "Iniciado worker #$i (Delay de início: " . number_format($startDelay, 1) . "s)\n";
        }
    }
    
    /**
     * Simula fork de um worker process
     * 
     * Nota: Em uma implementação real, isso utilizaria pcntl_fork ou similar
     * para executar os workers em processos separados ou implementaria workers 
     * em threads.
     * 
     * @param int $workerId ID do worker
     * @param float $startDelay Delay de início em segundos
     * @param array $endpoints Endpoints para este worker
     * @return int PID simulado
     */
    private function forkWorker($workerId, $startDelay, $endpoints) {
        // Em uma implementação real, isso seria um fork ou thread
        // Nesta simulação, apenas iniciamos uma execução sequencial
        
        // Simular atraso de início
        if ($startDelay > 0) {
            usleep($startDelay * 1000000);
        }
        
        // Iniciar worker loop em thread separada (simulado)
        $this->workerLoop($workerId, $endpoints);
        
        // Retornar PID simulado
        return 10000 + $workerId;
    }
    
    /**
     * Loop principal do worker
     * 
     * @param int $workerId ID do worker
     * @param array $endpoints Endpoints para este worker
     */
    private function workerLoop($workerId, $endpoints) {
        // Criar instância de cURL para o worker
        $ch = curl_init();
        
        // Configurações de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_COOKIEJAR, tempnam("/tmp", "cookies_$workerId"));
        curl_setopt($ch, CURLOPT_COOKIEFILE, tempnam("/tmp", "cookies_$workerId"));
        
        // Enquanto o teste estiver em execução
        while ($this->running) {
            // Selecionar um endpoint aleatório
            $endpointIndex = array_rand($endpoints);
            $endpoint = $endpoints[$endpointIndex];
            
            // Extrair informações do endpoint
            [$url, $method, $params, $description] = $endpoint;
            
            // Executar requisição
            $result = $this->executeRequest($ch, $url, $method, $params);
            
            // Registrar resultado (thread-safe usando locks - simulado)
            $this->updateResults($url, $result);
            
            // Pequena pausa aleatória entre requisições (100-500ms)
            usleep(mt_rand(100000, 500000));
        }
        
        // Liberar recursos
        curl_close($ch);
    }
    
    /**
     * Executa uma requisição HTTP
     * 
     * @param resource $ch Instância cURL
     * @param string $url Endpoint a ser testado
     * @param string $method Método HTTP
     * @param array $params Parâmetros da requisição
     * @return array Resultado da requisição
     */
    private function executeRequest($ch, $url, $method, $params) {
        $fullUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
        $startTime = microtime(true);
        
        // Configurações específicas do método
        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            if (!empty($params)) {
                $fullUrl .= '?' . http_build_query($params);
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        
        // Configurar URL
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        
        // Executar requisição
        $response = curl_exec($ch);
        $endTime = microtime(true);
        
        // Verificar erros
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = ($endTime - $startTime) * 1000; // em ms
        
        // Determinar sucesso
        $success = empty($error) && $httpCode >= 200 && $httpCode < 400;
        
        return [
            'success' => $success,
            'response_time' => $responseTime,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
    
    /**
     * Atualiza resultados dos testes
     * 
     * @param string $url Endpoint
     * @param array $result Resultado da requisição
     */
    private function updateResults($url, $result) {
        // Em uma implementação real, isso usaria locks para acesso thread-safe
        
        // Incrementar contadores
        $this->results[$url]['requests']++;
        
        if ($result['success']) {
            $this->results[$url]['success']++;
        } else {
            $this->results[$url]['failures']++;
            
            // Registrar erro
            $errorKey = $result['error'] ?: 'HTTP ' . $result['http_code'];
            if (!isset($this->results[$url]['errors'][$errorKey])) {
                $this->results[$url]['errors'][$errorKey] = 0;
            }
            $this->results[$url]['errors'][$errorKey]++;
        }
        
        // Registrar tempo de resposta
        $this->results[$url]['response_times'][] = $result['response_time'];
        
        // Atualizar tempos mínimo e máximo
        $this->results[$url]['min_time'] = min($this->results[$url]['min_time'], $result['response_time']);
        $this->results[$url]['max_time'] = max($this->results[$url]['max_time'], $result['response_time']);
    }
    
    /**
     * Interrompe os worker threads
     */
    private function stopWorkers() {
        // Em uma implementação real, isso enviaria sinais para os processos
        $this->running = false;
        
        echo "Aguardando finalização dos workers...\n";
        
        // Simular espera por finalização
        sleep(1);
        
        echo "Todos os workers finalizados.\n";
    }
    
    /**
     * Calcula estatísticas finais dos testes
     */
    private function calculateStatistics() {
        foreach ($this->results as $url => &$result) {
            // Calcular média
            if (!empty($result['response_times'])) {
                $result['avg_time'] = array_sum($result['response_times']) / count($result['response_times']);
                
                // Ordenar tempos para cálculo de percentis
                sort($result['response_times']);
                
                // Calcular percentis
                $count = count($result['response_times']);
                $result['p50'] = $this->getPercentile($result['response_times'], 50);
                $result['p90'] = $this->getPercentile($result['response_times'], 90);
                $result['p95'] = $this->getPercentile($result['response_times'], 95);
            }
            
            // Se não houve requisições, ajustar mínimo
            if ($result['min_time'] == PHP_INT_MAX) {
                $result['min_time'] = 0;
            }
        }
        
        // Calcular estatísticas globais
        $this->results['GLOBAL'] = [
            'requests' => 0,
            'success' => 0,
            'failures' => 0,
            'min_time' => PHP_INT_MAX,
            'max_time' => 0,
            'avg_time' => 0,
            'p50' => 0,
            'p90' => 0,
            'p95' => 0,
            'response_times' => [],
            'errors' => []
        ];
        
        $allTimes = [];
        
        foreach ($this->results as $url => $result) {
            if ($url === 'GLOBAL') continue;
            
            $this->results['GLOBAL']['requests'] += $result['requests'];
            $this->results['GLOBAL']['success'] += $result['success'];
            $this->results['GLOBAL']['failures'] += $result['failures'];
            
            // Coletar todos os tempos
            $allTimes = array_merge($allTimes, $result['response_times']);
            
            // Atualizar mínimo e máximo global
            if ($result['min_time'] < $this->results['GLOBAL']['min_time']) {
                $this->results['GLOBAL']['min_time'] = $result['min_time'];
            }
            
            if ($result['max_time'] > $this->results['GLOBAL']['max_time']) {
                $this->results['GLOBAL']['max_time'] = $result['max_time'];
            }
            
            // Agregar erros
            foreach ($result['errors'] as $error => $count) {
                if (!isset($this->results['GLOBAL']['errors'][$error])) {
                    $this->results['GLOBAL']['errors'][$error] = 0;
                }
                $this->results['GLOBAL']['errors'][$error] += $count;
            }
        }
        
        // Calcular estatísticas globais
        if (!empty($allTimes)) {
            $this->results['GLOBAL']['response_times'] = $allTimes;
            $this->results['GLOBAL']['avg_time'] = array_sum($allTimes) / count($allTimes);
            
            // Ordenar para percentis
            sort($allTimes);
            $this->results['GLOBAL']['p50'] = $this->getPercentile($allTimes, 50);
            $this->results['GLOBAL']['p90'] = $this->getPercentile($allTimes, 90);
            $this->results['GLOBAL']['p95'] = $this->getPercentile($allTimes, 95);
        }
        
        // Se não houve requisições, ajustar mínimo global
        if ($this->results['GLOBAL']['min_time'] == PHP_INT_MAX) {
            $this->results['GLOBAL']['min_time'] = 0;
        }
    }
    
    /**
     * Calcula percentil para um array ordenado
     * 
     * @param array $values Array de valores ordenados
     * @param int $percentile Percentil a calcular (0-100)
     * @return float Valor do percentil
     */
    private function getPercentile(array $values, $percentile) {
        if (empty($values)) {
            return 0;
        }
        
        $count = count($values);
        $index = ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));
        
        return $values[$index];
    }
    
    /**
     * Imprime resultados dos testes
     */
    private function printResults() {
        $totalTime = microtime(true) - $this->startTime;
        $rps = $this->results['GLOBAL']['requests'] / $totalTime;
        
        echo "\n=================================\n";
        echo "RESULTADOS DO TESTE DE CARGA\n";
        echo "=================================\n\n";
        
        echo "Duração total: " . number_format($totalTime, 2) . " segundos\n";
        echo "Usuários simultâneos: {$this->config['users']}\n";
        echo "Requisições totais: {$this->results['GLOBAL']['requests']}\n";
        echo "Requisições bem-sucedidas: {$this->results['GLOBAL']['success']} (" . 
             number_format(($this->results['GLOBAL']['success'] / $this->results['GLOBAL']['requests']) * 100, 1) . "%)\n";
        echo "Requisições com falha: {$this->results['GLOBAL']['failures']} (" . 
             number_format(($this->results['GLOBAL']['failures'] / $this->results['GLOBAL']['requests']) * 100, 1) . "%)\n";
        echo "Requisições por segundo: " . number_format($rps, 1) . "\n\n";
        
        echo "Tempos de resposta (ms):\n";
        echo "  Min: " . number_format($this->results['GLOBAL']['min_time'], 1) . "\n";
        echo "  Avg: " . number_format($this->results['GLOBAL']['avg_time'], 1) . "\n";
        echo "  P50: " . number_format($this->results['GLOBAL']['p50'], 1) . "\n";
        echo "  P90: " . number_format($this->results['GLOBAL']['p90'], 1) . "\n";
        echo "  P95: " . number_format($this->results['GLOBAL']['p95'], 1) . "\n";
        echo "  Max: " . number_format($this->results['GLOBAL']['max_time'], 1) . "\n\n";
        
        // Exibir erros, se houver
        if (!empty($this->results['GLOBAL']['errors'])) {
            echo "Erros encontrados:\n";
            foreach ($this->results['GLOBAL']['errors'] as $error => $count) {
                echo "  $error: $count\n";
            }
            echo "\n";
        }
        
        // Exibir resultados por endpoint
        echo "Resultados por endpoint:\n";
        echo str_repeat('-', 100) . "\n";
        echo sprintf("%-30s %10s %10s %10s %10s %10s %10s\n", 
            "Endpoint", "Requests", "Success", "Failures", "Avg(ms)", "P95(ms)", "Max(ms)");
        echo str_repeat('-', 100) . "\n";
        
        foreach ($this->config['endpoints'] as $endpoint) {
            $url = $endpoint[0];
            $result = $this->results[$url];
            
            echo sprintf("%-30s %10d %10d %10d %10.1f %10.1f %10.1f\n",
                $url,
                $result['requests'],
                $result['success'],
                $result['failures'],
                $result['avg_time'],
                $result['p95'],
                $result['max_time']
            );
        }
        
        echo str_repeat('-', 100) . "\n";
        echo sprintf("%-30s %10d %10d %10d %10.1f %10.1f %10.1f\n",
            "TOTAL",
            $this->results['GLOBAL']['requests'],
            $this->results['GLOBAL']['success'],
            $this->results['GLOBAL']['failures'],
            $this->results['GLOBAL']['avg_time'],
            $this->results['GLOBAL']['p95'],
            $this->results['GLOBAL']['max_time']
        );
        
        echo "\n";
    }
}

// Ponto de entrada do script
try {
    echo "=================================\n";
    echo "Teste de Carga - Taverna da Impressão 3D\n";
    echo "=================================\n\n";
    
    // Ler parâmetros da linha de comando
    $options = getopt('', ['url:', 'users:', 'duration:']);
    
    // Configuração padrão
    $baseUrl = 'http://localhost:8000';
    $config = [];
    
    // Processar argumentos
    if (isset($options['url'])) {
        $baseUrl = $options['url'];
    }
    
    if (isset($options['users'])) {
        $config['users'] = (int)$options['users'];
    }
    
    if (isset($options['duration'])) {
        $config['duration'] = (int)$options['duration'];
    }
    
    echo "URL base: $baseUrl\n";
    if (isset($config['users'])) {
        echo "Usuários simultâneos: {$config['users']}\n";
    }
    if (isset($config['duration'])) {
        echo "Duração: {$config['duration']} segundos\n";
    }
    echo "\n";
    
    // Executar teste
    $tester = new LoadTester($baseUrl, $config);
    $results = $tester->run();
    
    // Código de saída baseado no sucesso do teste
    $successRate = $results['GLOBAL']['success'] / $results['GLOBAL']['requests'];
    $exitCode = ($successRate >= 0.95) ? 0 : 1; // Sucesso se pelo menos 95% das requisições foram bem-sucedidas
    
    exit($exitCode);
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
