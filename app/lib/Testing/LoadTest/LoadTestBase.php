<?php
/**
 * LoadTestBase - Classe base para testes de carga
 * 
 * Fornece a infraestrutura básica para executar testes de carga
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Lib\Testing\LoadTest
 * @version 1.0.0
 * @author Taverna da Impressão
 */
class LoadTestBase {
    /**
     * Configurações do teste
     *
     * @var array
     */
    protected $config = [
        'users' => 10,          // Número de usuários virtuais
        'iterations' => 10,      // Número de iterações por usuário
        'rampup' => 5,           // Tempo de rampa em segundos
        'timeout' => 30,         // Timeout em segundos
        'detailed_log' => true,  // Habilitar logs detalhados
        'metrics' => [           // Métricas a serem coletadas
            'response_time' => true,
            'error_rate' => true,
            'throughput' => true,
            'memory_usage' => true
        ]
    ];

    /**
     * Resultados do teste
     *
     * @var array
     */
    protected $results = [
        'start_time' => null,
        'end_time' => null,
        'total_requests' => 0,
        'successful_requests' => 0,
        'failed_requests' => 0,
        'response_times' => [],
        'errors' => [],
        'memory_usage' => []
    ];

    /**
     * Flag para indicar se o teste está em execução
     *
     * @var bool
     */
    protected $running = false;

    /**
     * Construtor
     *
     * @param array $config Configurações customizadas
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);
        
        // Verificar limites para evitar DoS acidental
        $this->validateConfig();
    }

    /**
     * Valida a configuração para garantir parâmetros seguros
     *
     * @throws \Exception Se a configuração for insegura
     */
    protected function validateConfig() {
        // Limites de segurança para evitar sobrecarga do sistema
        $maxUsers = 100;
        $maxIterations = 1000;
        $minRampup = 1;
        $maxTimeout = 120;

        if ($this->config['users'] > $maxUsers) {
            throw new \Exception("Número de usuários virtuais excede o limite de segurança ($maxUsers)");
        }

        if ($this->config['iterations'] > $maxIterations) {
            throw new \Exception("Número de iterações excede o limite de segurança ($maxIterations)");
        }

        if ($this->config['rampup'] < $minRampup) {
            throw new \Exception("Tempo de rampa deve ser pelo menos $minRampup segundos");
        }

        if ($this->config['timeout'] > $maxTimeout) {
            throw new \Exception("Timeout excede o limite de segurança ($maxTimeout segundos)");
        }
    }

    /**
     * Executa o teste de carga
     *
     * @param callable $testFunction Função a ser executada no teste
     * @return array Resultados do teste
     * @throws \Exception Se ocorrer um erro durante o teste
     */
    public function run(callable $testFunction) {
        if ($this->running) {
            throw new \Exception("Já existe um teste em execução");
        }

        $this->running = true;
        $this->resetResults();
        $this->results['start_time'] = microtime(true);

        try {
            $this->logInfo("Iniciando teste de carga com {$this->config['users']} usuários virtuais e {$this->config['iterations']} iterações");
            
            // Executar teste
            for ($user = 1; $user <= $this->config['users']; $user++) {
                // Rampa de subida (gradualmente aumenta carga)
                if ($this->config['rampup'] > 0 && $this->config['users'] > 1) {
                    $delay = ($this->config['rampup'] / $this->config['users']) * $user;
                    usleep($delay * 1000000);
                }

                $this->logInfo("Iniciando usuário virtual #$user");
                
                for ($i = 1; $i <= $this->config['iterations']; $i++) {
                    $this->runSingleIteration($testFunction, $user, $i);
                }
            }
        } catch (\Exception $e) {
            $this->logError("Erro durante o teste: " . $e->getMessage());
            throw $e;
        } finally {
            $this->results['end_time'] = microtime(true);
            $this->running = false;
            $this->logInfo("Teste de carga concluído");
        }

        return $this->analyzeResults();
    }

    /**
     * Executa uma única iteração do teste
     *
     * @param callable $testFunction Função a ser executada
     * @param int $user Número do usuário virtual
     * @param int $iteration Número da iteração
     */
    protected function runSingleIteration(callable $testFunction, $user, $iteration) {
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage();
        $error = null;
        $result = null;

        try {
            // Configurar timeout
            set_time_limit($this->config['timeout']);
            
            // Executar a função de teste
            $result = call_user_func($testFunction, [
                'user' => $user,
                'iteration' => $iteration,
                'config' => $this->config
            ]);
            
            $this->results['successful_requests']++;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->results['failed_requests']++;
            $this->results['errors'][] = [
                'user' => $user,
                'iteration' => $iteration,
                'error' => $error,
                'time' => date('Y-m-d H:i:s')
            ];
        }

        $endTime = microtime(true);
        $memoryAfter = memory_get_usage();
        $responseTime = ($endTime - $startTime) * 1000; // em ms

        $this->results['total_requests']++;
        $this->results['response_times'][] = $responseTime;
        $this->results['memory_usage'][] = $memoryAfter - $memoryBefore;

        if ($this->config['detailed_log']) {
            $status = $error ? "ERRO" : "OK";
            $this->logInfo("Usuário #$user, Iteração #$iteration: $status, Tempo: " . number_format($responseTime, 2) . "ms");
            
            if ($error) {
                $this->logError("Usuário #$user, Iteração #$iteration: $error");
            }
        }
    }

    /**
     * Analisa os resultados do teste
     *
     * @return array Resultados analisados
     */
    protected function analyzeResults() {
        $totalTime = $this->results['end_time'] - $this->results['start_time'];
        $responseTimes = $this->results['response_times'];
        
        // Evitar divisão por zero
        $count = count($responseTimes);
        if ($count == 0) $count = 1;
        
        $analysis = [
            'total_time' => $totalTime,
            'total_requests' => $this->results['total_requests'],
            'successful_requests' => $this->results['successful_requests'],
            'failed_requests' => $this->results['failed_requests'],
            'error_rate' => ($this->results['total_requests'] > 0) 
                ? ($this->results['failed_requests'] / $this->results['total_requests'] * 100) 
                : 0,
            'throughput' => $this->results['total_requests'] / $totalTime,
            'average_response_time' => array_sum($responseTimes) / $count,
            'min_response_time' => $count > 0 ? min($responseTimes) : 0,
            'max_response_time' => $count > 0 ? max($responseTimes) : 0,
            'percentiles' => $this->calculatePercentiles($responseTimes),
            'memory' => [
                'average' => array_sum($this->results['memory_usage']) / $count,
                'peak' => $count > 0 ? max($this->results['memory_usage']) : 0
            ]
        ];

        $this->logInfo("Análise concluída: Taxa de erro: " . number_format($analysis['error_rate'], 2) . "%, " .
                      "Tempo médio de resposta: " . number_format($analysis['average_response_time'], 2) . "ms, " .
                      "Throughput: " . number_format($analysis['throughput'], 2) . " req/s");

        return [
            'config' => $this->config,
            'raw_results' => $this->results,
            'analysis' => $analysis
        ];
    }

    /**
     * Calcula os percentis para os tempos de resposta
     *
     * @param array $values Array de valores
     * @return array Percentis calculados (50, 90, 95, 99)
     */
    protected function calculatePercentiles(array $values) {
        if (empty($values)) {
            return [
                'p50' => 0,
                'p90' => 0,
                'p95' => 0,
                'p99' => 0
            ];
        }

        sort($values);
        $count = count($values);

        return [
            'p50' => $this->getPercentile($values, $count, 50),
            'p90' => $this->getPercentile($values, $count, 90),
            'p95' => $this->getPercentile($values, $count, 95),
            'p99' => $this->getPercentile($values, $count, 99)
        ];
    }

    /**
     * Obtém um percentil específico de um array de valores
     *
     * @param array $values Array de valores ordenados
     * @param int $count Número de elementos
     * @param int $percentile Percentil desejado (0-100)
     * @return float Valor do percentil
     */
    protected function getPercentile(array $values, $count, $percentile) {
        $index = ($percentile / 100) * $count;
        
        if (floor($index) == $index) {
            return $values[$index - 1];
        } else {
            $lower = floor($index) - 1;
            $upper = ceil($index) - 1;
            return ($values[$lower] + $values[$upper]) / 2;
        }
    }

    /**
     * Reinicia os resultados do teste
     */
    protected function resetResults() {
        $this->results = [
            'start_time' => null,
            'end_time' => null,
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'response_times' => [],
            'errors' => [],
            'memory_usage' => []
        ];
    }

    /**
     * Registra uma mensagem de informação
     *
     * @param string $message Mensagem a ser logada
     */
    protected function logInfo($message) {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] [LOAD_TEST] [INFO] $message");
    }

    /**
     * Registra uma mensagem de erro
     *
     * @param string $message Mensagem a ser logada
     */
    protected function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] [LOAD_TEST] [ERROR] $message");
    }
}