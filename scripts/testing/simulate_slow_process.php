<?php
/**
 * Script para simular processo assíncrono lento
 * 
 * Este script simula um processo lento/stalled para testar
 * o sistema de alertas de performance.
 * 
 * @package App\Scripts\Testing
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Security\SecurityManager;
use App\Lib\Monitoring\PerformanceAlertingService;
use App\Lib\Performance\PerformanceMonitor;
use App\Lib\Notification\NotificationManager;
use App\Lib\Notification\NotificationThresholds;

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Classe para simular processos assíncronos lentos
 */
class SlowProcessSimulator {
    /** @var PDO Conexão com banco de dados */
    private $db;
    
    /** @var PerformanceAlertingService Serviço de alertas */
    private $alertService;
    
    /** @var array Parâmetros de simulação */
    private $params = [
        'process_type' => 'test_simulation',
        'duration' => 600,    // Duração total em segundos
        'max_duration' => 300, // Duração máxima esperada em segundos
        'stall_point' => 30,  // Percentual onde o processo "trava"
        'stall_duration' => 300, // Duração da "travada" em segundos
        'user_id' => 1
    ];
    
    /**
     * Construtor
     * 
     * @param PDO $db Conexão com banco de dados
     * @param array $params Parâmetros de simulação (opcional)
     */
    public function __construct(PDO $db, array $params = []) {
        $this->db = $db;
        
        // Mesclar parâmetros fornecidos com os padrões
        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }
        
        // Inicializar serviço de alertas
        $this->initAlertService();
    }
    
    /**
     * Inicializa serviço de alertas de performance
     */
    private function initAlertService() {
        // Inicializar dependências
        $performanceMonitor = new PerformanceMonitor($this->db);
        $notificationManager = new NotificationManager($this->db);
        $thresholds = new NotificationThresholds($this->db);
        
        // Inicializar serviço de alertas
        $this->alertService = new PerformanceAlertingService(
            $performanceMonitor,
            $notificationManager,
            $thresholds,
            $this->db
        );
    }
    
    /**
     * Simula criação de processo assíncrono
     * 
     * @return string ID do processo
     */
    public function createAsyncProcess() {
        $processId = uniqid('test_', true);
        
        // Registrar processo no banco de dados
        $stmt = $this->db->prepare("
            INSERT INTO async_processes
            (id, type, name, status, progress, user_id, created_at, updated_at)
            VALUES (?, ?, ?, 'processing', 0, ?, NOW(), NOW())
        ");
        
        $processName = "Simulação de Processo Lento #{$processId}";
        
        $stmt->execute([
            $processId,
            $this->params['process_type'],
            $processName,
            $this->params['user_id']
        ]);
        
        // Registrar para monitoramento
        $this->alertService->monitorAsyncProcess(
            $processId,
            $this->params['max_duration']
        );
        
        echo "Processo assíncrono criado com ID: $processId\n";
        echo "Duração máxima esperada: {$this->params['max_duration']} segundos\n";
        echo "Duração real simulada: {$this->params['duration']} segundos\n";
        echo "Ponto de travamento: {$this->params['stall_point']}%\n";
        echo "Duração do travamento: {$this->params['stall_duration']} segundos\n\n";
        
        return $processId;
    }
    
    /**
     * Executa simulação do processo
     * 
     * @param string $processId ID do processo
     * @return bool Sucesso da simulação
     */
    public function runSimulation($processId) {
        $startTime = time();
        $totalDuration = $this->params['duration'];
        $stallPoint = $this->params['stall_point'];
        $stallDuration = $this->params['stall_duration'];
        
        echo "Iniciando simulação do processo $processId...\n";
        
        // Calcular intervalos de atualização
        $totalSteps = 20; // 20 atualizações de progresso
        $stepDuration = $totalDuration / $totalSteps;
        
        // Calcular em qual etapa ocorre o travamento
        $stallStep = floor($totalSteps * ($stallPoint / 100));
        
        for ($step = 0; $step <= $totalSteps; $step++) {
            $currentTime = time();
            $elapsedTime = $currentTime - $startTime;
            
            // Calcular progresso esperado
            $expectedProgress = ($step / $totalSteps) * 100;
            
            // Verificar se estamos no ponto de travamento
            if ($step == $stallStep) {
                echo "Simulando travamento em $stallPoint% por $stallDuration segundos...\n";
                
                // Atualizar progresso antes do travamento
                $this->updateProgress($processId, $expectedProgress);
                
                // Simular travamento (não atualizar o progresso por um período)
                sleep($stallDuration);
                
                // Após o "travamento", o tempo total já terá avançado
                continue;
            }
            
            // Atualizar progresso no banco de dados
            $this->updateProgress($processId, $expectedProgress);
            
            echo "Progresso: " . number_format($expectedProgress, 1) . "% (Tempo decorrido: " . $this->formatDuration($elapsedTime) . ")\n";
            
            // Esperar até o próximo passo, se não for o último
            if ($step < $totalSteps) {
                $sleepTime = max(1, $stepDuration - 1); // Garantir pelo menos 1 segundo
                sleep($sleepTime);
            }
        }
        
        // Finalizar processo
        $this->completeProcess($processId);
        
        // Parar monitoramento
        $this->alertService->stopMonitoringProcess($processId);
        
        $totalElapsedTime = time() - $startTime;
        echo "\nSimulação concluída em " . $this->formatDuration($totalElapsedTime) . "\n";
        echo "Duração esperada: " . $this->formatDuration($totalDuration) . "\n";
        
        return true;
    }
    
    /**
     * Atualiza progresso do processo no banco de dados
     * 
     * @param string $processId ID do processo
     * @param float $progress Percentual de progresso
     * @return bool Sucesso da atualização
     */
    private function updateProgress($processId, $progress) {
        $stmt = $this->db->prepare("
            UPDATE async_processes
            SET progress = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$progress, $processId]);
    }
    
    /**
     * Marca processo como concluído
     * 
     * @param string $processId ID do processo
     * @return bool Sucesso da operação
     */
    private function completeProcess($processId) {
        $stmt = $this->db->prepare("
            UPDATE async_processes
            SET progress = 100, status = 'completed', updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$processId]);
    }
    
    /**
     * Formata duração em segundos para formato legível
     * 
     * @param int $seconds Duração em segundos
     * @return string Duração formatada
     */
    private function formatDuration($seconds) {
        if ($seconds < 60) {
            return $seconds . ' segundos';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $sec = $seconds % 60;
            return $minutes . ' minuto' . ($minutes > 1 ? 's' : '') . 
                   ($sec > 0 ? ' e ' . $sec . ' segundo' . ($sec > 1 ? 's' : '') : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' hora' . ($hours > 1 ? 's' : '') . 
                   ($minutes > 0 ? ' e ' . $minutes . ' minuto' . ($minutes > 1 ? 's' : '') : '');
        }
    }
}

// Ponto de entrada do script
try {
    echo "=================================\n";
    echo "Simulador de Processo Lento\n";
    echo "=================================\n\n";
    
    // Ler parâmetros da linha de comando
    $options = getopt('', ['duration:', 'max-duration:', 'stall-point:', 'stall-duration:', 'user-id:']);
    
    $params = [];
    
    if (isset($options['duration'])) {
        $params['duration'] = (int)$options['duration'];
    }
    
    if (isset($options['max-duration'])) {
        $params['max_duration'] = (int)$options['max-duration'];
    }
    
    if (isset($options['stall-point'])) {
        $params['stall_point'] = (int)$options['stall-point'];
    }
    
    if (isset($options['stall-duration'])) {
        $params['stall_duration'] = (int)$options['stall-duration'];
    }
    
    if (isset($options['user-id'])) {
        $params['user_id'] = (int)$options['user-id'];
    }
    
    // Inicializar conexão com o banco de dados
    $pdo = require ROOT_DIR . '/app/config/database.php';
    
    // Criar e executar simulador
    $simulator = new SlowProcessSimulator($pdo, $params);
    $processId = $simulator->createAsyncProcess();
    $simulator->runSimulation($processId);
    
    echo "\nSimulação concluída com sucesso. Verifique os alertas gerados.\n";
    echo "Para visualizar os alertas, consulte a tabela performance_alerts no banco de dados.\n";
    
    exit(0);
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
