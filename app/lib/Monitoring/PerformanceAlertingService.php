<?php
/**
 * PerformanceAlertingService - Serviço responsável por monitorar processos 
 * assíncronos e gerar alertas de performance
 * 
 * @package App\Lib\Monitoring
 */

namespace App\Lib\Monitoring;

use App\Lib\Performance\PerformanceMonitor;
use App\Lib\Notification\NotificationManager;
use App\Lib\Notification\NotificationThresholds;
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;
use PDO;
use Exception;

class PerformanceAlertingService {
    use InputValidationTrait;
    
    /** @var PerformanceMonitor Monitor de performance */
    private $performanceMonitor;
    
    /** @var NotificationManager Gerenciador de notificações */
    private $notificationManager;
    
    /** @var NotificationThresholds Limiares de notificação */
    private $thresholds;
    
    /** @var array Processos monitorados [id => ['lastCheck' => timestamp, 'maxDuration' => seconds, 'startTime' => timestamp]] */
    private $monitoredProcesses = [];
    
    /** @var PDO Conexão com o banco de dados */
    private $db;
    
    /** @var \Psr\Log\LoggerInterface Instância do logger */
    private $logger;
    
    /** @var int Intervalo mínimo entre verificações (segundos) */
    private $checkInterval = 60;
    
    /** @var array Cache de informações de processos */
    private $processInfoCache = [];
    
    /**
     * Construtor
     *
     * @param PerformanceMonitor $performanceMonitor Monitor de performance
     * @param NotificationManager $notificationManager Gerenciador de notificações
     * @param NotificationThresholds $thresholds Limiares de notificação
     * @param PDO $db Conexão com o banco de dados
     * @param \Psr\Log\LoggerInterface $logger Logger (opcional)
     */
    public function __construct(
        PerformanceMonitor $performanceMonitor,
        NotificationManager $notificationManager,
        NotificationThresholds $thresholds,
        PDO $db,
        $logger = null
    ) {
        $this->performanceMonitor = $performanceMonitor;
        $this->notificationManager = $notificationManager;
        $this->thresholds = $thresholds;
        $this->db = $db;
        $this->logger = $logger;
        
        // Inicializa processos monitorados a partir do banco de dados
        $this->initializeMonitoredProcesses();
    }
    
    /**
     * Processa um alerta individual
     *
     * @param string $alertType Tipo de alerta (performance, timeout, error)
     * @param array $data Dados do alerta
     * @param string $severity Severidade (info, warning, error, critical)
     * @return bool Sucesso do processamento
     */
    public function processAlert($alertType, array $data, $severity = 'warning') {
        try {
            // Validar entrada
            $alertType = $this->validateInput($alertType, 'string', ['required' => true]);
            $severity = $this->validateInput($severity, 'string', [
                'required' => true,
                'allowed' => ['info', 'warning', 'error', 'critical']
            ]);
            
            if ($alertType === null || $severity === null) {
                $this->logError('Invalid alert parameters', ['type' => $alertType, 'severity' => $severity]);
                return false;
            }
            
            // Determinar canais com base na severidade
            $channels = ['database'];
            if ($severity === 'warning' || $severity === 'error') {
                $channels[] = 'push';
            }
            if ($severity === 'critical' || $severity === 'error') {
                $channels[] = 'email';
            }
            
            // Sanitizar e validar dados do alerta
            $sanitizedData = $this->sanitizeAlertData($data);
            
            // Determinar destinatários
            $recipientGroups = ['admin'];
            if (isset($sanitizedData['user_id'])) {
                $userId = (int)$sanitizedData['user_id'];
                
                // Registrar notificação para o usuário específico
                $this->notificationManager->createNotification(
                    $userId,
                    $this->getAlertTitle($alertType, $severity),
                    $this->getAlertMessage($alertType, $sanitizedData, $severity),
                    $this->mapSeverityToNotificationType($severity),
                    $sanitizedData,
                    $channels
                );
            } else {
                // Notificação para administradores
                $this->notificationManager->createSystemNotification(
                    $this->getAlertTitle($alertType, $severity),
                    $this->getAlertMessage($alertType, $sanitizedData, $severity),
                    $this->mapSeverityToNotificationType($severity),
                    $recipientGroups,
                    $channels
                );
            }
            
            // Registrar alerta no log
            $this->logAlert($alertType, $sanitizedData, $severity);
            
            return true;
        } catch (Exception $e) {
            $this->logError('Error processing alert', [
                'type' => $alertType,
                'severity' => $severity,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Processa uma medição de performance
     *
     * @param string $context Contexto da medição
     * @param array $metrics Métricas coletadas
     * @return bool Sucesso do processamento
     */
    public function processPerformanceMeasurement($context, array $metrics) {
        try {
            // Validar entrada
            $context = $this->validateInput($context, 'string', ['required' => true]);
            
            if ($context === null) {
                $this->logError('Invalid performance measurement parameters', ['context' => $context]);
                return false;
            }
            
            // Verificar métricas contra limiares configurados
            $alerts = [];
            
            // Verificar tempo de execução
            if (isset($metrics['execution_time'])) {
                $threshold = $this->thresholds->getThreshold($context, 'execution_time');
                if ($threshold && $metrics['execution_time'] > $threshold) {
                    $alerts[] = [
                        'type' => 'performance',
                        'metric' => 'execution_time',
                        'value' => $metrics['execution_time'],
                        'threshold' => $threshold,
                        'severity' => $this->determineSeverity($metrics['execution_time'], $threshold, 1.5, 3)
                    ];
                }
            }
            
            // Verificar uso de memória
            if (isset($metrics['memory_usage'])) {
                $threshold = $this->thresholds->getThreshold($context, 'memory_usage');
                if ($threshold && $metrics['memory_usage'] > $threshold) {
                    $alerts[] = [
                        'type' => 'performance',
                        'metric' => 'memory_usage',
                        'value' => $metrics['memory_usage'],
                        'threshold' => $threshold,
                        'severity' => $this->determineSeverity($metrics['memory_usage'], $threshold, 1.2, 2)
                    ];
                }
            }
            
            // Verificar consultas de banco de dados
            if (isset($metrics['database_queries'])) {
                $threshold = $this->thresholds->getThreshold($context, 'database_queries');
                if ($threshold && $metrics['database_queries'] > $threshold) {
                    $alerts[] = [
                        'type' => 'performance',
                        'metric' => 'database_queries',
                        'value' => $metrics['database_queries'],
                        'threshold' => $threshold,
                        'severity' => $this->determineSeverity($metrics['database_queries'], $threshold, 1.5, 2.5)
                    ];
                }
            }
            
            // Processar os alertas identificados
            foreach ($alerts as $alert) {
                $alertData = array_merge($metrics, [
                    'context' => $context,
                    'metric' => $alert['metric'],
                    'value' => $alert['value'],
                    'threshold' => $alert['threshold']
                ]);
                
                $this->processAlert('performance', $alertData, $alert['severity']);
            }
            
            return true;
        } catch (Exception $e) {
            $this->logError('Error processing performance measurement', [
                'context' => $context,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Verifica os processos monitorados para detectar timeouts ou atrasos
     *
     * @return array Resultados das verificações: [sucesso => bool, alertas => int]
     */
    public function checkMonitoredProcesses() {
        $now = time();
        $alertCount = 0;
        $successCount = 0;
        
        try {
            foreach ($this->monitoredProcesses as $processId => $processInfo) {
                // Verificar se já passou tempo suficiente desde a última verificação
                if (isset($processInfo['lastCheck']) && 
                    ($now - $processInfo['lastCheck']) < $this->checkInterval) {
                    continue;
                }
                
                // Obter informações atualizadas do processo
                $processDetails = $this->getProcessDetails($processId);
                if (!$processDetails) {
                    // Processo não encontrado ou já finalizado
                    unset($this->monitoredProcesses[$processId]);
                    continue;
                }
                
                // Atualizar timestamp da última verificação
                $this->monitoredProcesses[$processId]['lastCheck'] = $now;
                
                // Verificar se o processo excedeu o tempo máximo de execução
                $elapsedTime = $now - $processInfo['startTime'];
                $maxDuration = $processInfo['maxDuration'];
                
                if ($elapsedTime > $maxDuration) {
                    // Determinar severidade com base no atraso
                    $overageRatio = $elapsedTime / $maxDuration;
                    $severity = $this->determineSeverity($overageRatio, 1, 1.5, 2);
                    
                    // Processar alerta de timeout
                    $alertData = [
                        'process_id' => $processId,
                        'process_type' => $processDetails['type'],
                        'process_name' => $processDetails['name'],
                        'current_status' => $processDetails['status'],
                        'elapsed_time' => $elapsedTime,
                        'max_duration' => $maxDuration,
                        'overage_ratio' => $overageRatio,
                        'started_at' => date('Y-m-d H:i:s', $processInfo['startTime']),
                        'user_id' => $processDetails['user_id'] ?? null
                    ];
                    
                    $success = $this->processAlert('timeout', $alertData, $severity);
                    if ($success) {
                        $alertCount++;
                    }
                }
                
                // Verificar status e progresso
                $progressThreshold = $this->thresholds->getThreshold('async_process', 'min_progress_rate');
                if ($progressThreshold && isset($processDetails['progress'])) {
                    $expectedProgress = ($elapsedTime / $maxDuration) * 100;
                    $actualProgress = $processDetails['progress'];
                    
                    // Se o progresso real for significativamente menor que o esperado
                    if (($expectedProgress - $actualProgress) > $progressThreshold) {
                        $alertData = [
                            'process_id' => $processId,
                            'process_type' => $processDetails['type'],
                            'process_name' => $processDetails['name'],
                            'current_status' => $processDetails['status'],
                            'expected_progress' => $expectedProgress,
                            'actual_progress' => $actualProgress,
                            'progress_gap' => $expectedProgress - $actualProgress,
                            'elapsed_time' => $elapsedTime,
                            'max_duration' => $maxDuration,
                            'user_id' => $processDetails['user_id'] ?? null
                        ];
                        
                        $success = $this->processAlert('slow_progress', $alertData, 'warning');
                        if ($success) {
                            $alertCount++;
                        }
                    }
                }
                
                $successCount++;
            }
            
            // Persistir alterações nos processos monitorados
            $this->saveMonitoredProcesses();
            
            // Registrar estatísticas de verificação
            $this->performanceMonitor->recordMetric('monitored_processes_check', [
                'checked_count' => count($this->monitoredProcesses),
                'alert_count' => $alertCount,
                'success_count' => $successCount,
                'timestamp' => $now
            ]);
            
            return [
                'success' => true,
                'checked' => count($this->monitoredProcesses),
                'alerts' => $alertCount,
                'successful_checks' => $successCount
            ];
            
        } catch (Exception $e) {
            $this->logError('Error checking monitored processes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'checked' => count($this->monitoredProcesses),
                'alerts' => $alertCount,
                'successful_checks' => $successCount,
                'error' => SecurityManager::isInDebugMode() ? $e->getMessage() : 'Erro interno ao verificar processos'
            ];
        }
    }
    
    /**
     * Adiciona um processo para monitoramento contínuo
     *
     * @param string $processId ID do processo
     * @param int $maxDuration Duração máxima esperada em segundos
     * @param int $startTime Timestamp de início (opcional, padrão = atual)
     * @return bool Sucesso da operação
     */
    public function monitorAsyncProcess($processId, $maxDuration, $startTime = null) {
        try {
            // Validar entrada
            $processId = $this->validateInput($processId, 'string', ['required' => true]);
            $maxDuration = $this->validateInput($maxDuration, 'int', [
                'required' => true,
                'min' => 1
            ]);
            
            if ($processId === null || $maxDuration === null) {
                $this->logError('Invalid monitor parameters', [
                    'process_id' => $processId,
                    'max_duration' => $maxDuration
                ]);
                return false;
            }
            
            // Definir timestamp de início
            $startTime = $startTime ?? time();
            
            // Adicionar à lista de processos monitorados
            $this->monitoredProcesses[$processId] = [
                'startTime' => $startTime,
                'maxDuration' => $maxDuration,
                'lastCheck' => time()
            ];
            
            // Persistir alterações
            $this->saveMonitoredProcesses();
            
            return true;
        } catch (Exception $e) {
            $this->logError('Error adding monitored process', [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Remove um processo do monitoramento
     *
     * @param string $processId ID do processo
     * @return bool Sucesso da operação
     */
    public function stopMonitoringProcess($processId) {
        try {
            // Validar entrada
            $processId = $this->validateInput($processId, 'string', ['required' => true]);
            
            if ($processId === null) {
                $this->logError('Invalid process ID for monitoring removal', [
                    'process_id' => $processId
                ]);
                return false;
            }
            
            // Remover da lista de processos monitorados
            if (isset($this->monitoredProcesses[$processId])) {
                unset($this->monitoredProcesses[$processId]);
                
                // Persistir alterações
                $this->saveMonitoredProcesses();
            }
            
            return true;
        } catch (Exception $e) {
            $this->logError('Error removing monitored process', [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Inicializa a lista de processos monitorados a partir do banco de dados
     *
     * @return void
     */
    private function initializeMonitoredProcesses() {
        try {
            $stmt = $this->db->prepare("
                SELECT process_id, start_time, max_duration, last_check 
                FROM monitored_processes 
                WHERE active = 1
            ");
            $stmt->execute();
            
            $this->monitoredProcesses = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->monitoredProcesses[$row['process_id']] = [
                    'startTime' => $row['start_time'],
                    'maxDuration' => $row['max_duration'],
                    'lastCheck' => $row['last_check']
                ];
            }
        } catch (Exception $e) {
            $this->logError('Error initializing monitored processes', [
                'error' => $e->getMessage()
            ]);
            $this->monitoredProcesses = [];
        }
    }
    
    /**
     * Persiste a lista de processos monitorados no banco de dados
     *
     * @return bool Sucesso da operação
     */
    private function saveMonitoredProcesses() {
        try {
            $this->db->beginTransaction();
            
            // Atualizar status para inativo para todos os processos
            $stmt = $this->db->prepare("
                UPDATE monitored_processes 
                SET active = 0
            ");
            $stmt->execute();
            
            // Inserir ou atualizar cada processo monitorado
            $stmt = $this->db->prepare("
                INSERT INTO monitored_processes
                (process_id, start_time, max_duration, last_check, active) 
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                start_time = VALUES(start_time),
                max_duration = VALUES(max_duration),
                last_check = VALUES(last_check),
                active = 1
            ");
            
            foreach ($this->monitoredProcesses as $processId => $processInfo) {
                $stmt->execute([
                    $processId,
                    $processInfo['startTime'],
                    $processInfo['maxDuration'],
                    $processInfo['lastCheck']
                ]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            $this->logError('Error saving monitored processes', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Obtém informações detalhadas de um processo
     *
     * @param string $processId ID do processo
     * @return array|null Detalhes do processo ou null se não encontrado
     */
    private function getProcessDetails($processId) {
        try {
            // Verificar cache
            if (isset($this->processInfoCache[$processId])) {
                return $this->processInfoCache[$processId];
            }
            
            // Consultar banco de dados
            $stmt = $this->db->prepare("
                SELECT 
                    ap.id, 
                    ap.type, 
                    ap.name, 
                    ap.status, 
                    ap.progress, 
                    ap.user_id,
                    ap.created_at,
                    ap.updated_at
                FROM async_processes ap
                WHERE ap.id = ? AND ap.status NOT IN ('completed', 'failed', 'cancelled')
            ");
            $stmt->execute([$processId]);
            
            $details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($details) {
                // Armazenar em cache
                $this->processInfoCache[$processId] = $details;
                return $details;
            }
            
            return null;
        } catch (Exception $e) {
            $this->logError('Error getting process details', [
                'process_id' => $processId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Determina a severidade com base na proporção valor/limiar
     *
     * @param float $value Valor atual
     * @param float $threshold Limiar base
     * @param float $warningMultiplier Multiplicador para warning (padrão = 1.5)
     * @param float $errorMultiplier Multiplicador para error (padrão = 3)
     * @return string Severidade (info, warning, error, critical)
     */
    private function determineSeverity($value, $threshold, $warningMultiplier = 1.5, $errorMultiplier = 3) {
        $ratio = $value / $threshold;
        
        if ($ratio >= $errorMultiplier) {
            return 'critical';
        } elseif ($ratio >= $warningMultiplier) {
            return 'error';
        } elseif ($ratio >= 1) {
            return 'warning';
        } else {
            return 'info';
        }
    }
    
    /**
     * Mapeia severidade para tipo de notificação
     *
     * @param string $severity Severidade (info, warning, error, critical)
     * @return string Tipo de notificação (info, warning, error, success)
     */
    private function mapSeverityToNotificationType($severity) {
        switch ($severity) {
            case 'critical':
            case 'error':
                return 'error';
            case 'warning':
                return 'warning';
            case 'info':
            default:
                return 'info';
        }
    }
    
    /**
     * Gera título para o alerta
     *
     * @param string $alertType Tipo de alerta
     * @param string $severity Severidade
     * @return string Título formatado
     */
    private function getAlertTitle($alertType, $severity) {
        $prefix = $severity === 'critical' ? '[CRÍTICO] ' : '';
        
        switch ($alertType) {
            case 'performance':
                return $prefix . 'Alerta de Performance';
            case 'timeout':
                return $prefix . 'Processo Excedeu Tempo Máximo';
            case 'slow_progress':
                return $prefix . 'Processo com Progresso Lento';
            case 'error':
                return $prefix . 'Erro em Processo Assíncrono';
            default:
                return $prefix . 'Alerta do Sistema';
        }
    }
    
    /**
     * Gera mensagem para o alerta
     *
     * @param string $alertType Tipo de alerta
     * @param array $data Dados do alerta
     * @param string $severity Severidade
     * @return string Mensagem formatada
     */
    private function getAlertMessage($alertType, $data, $severity) {
        switch ($alertType) {
            case 'performance':
                return sprintf(
                    'A métrica "%s" no contexto "%s" atingiu %s, excedendo o limite de %s.',
                    $data['metric'] ?? 'desconhecida',
                    $data['context'] ?? 'desconhecido',
                    $this->formatMetricValue($data['metric'] ?? '', $data['value'] ?? 0),
                    $this->formatMetricValue($data['metric'] ?? '', $data['threshold'] ?? 0)
                );
                
            case 'timeout':
                return sprintf(
                    'O processo "%s" (ID: %s) está em execução há %s, excedendo o limite máximo de %s.',
                    $data['process_name'] ?? 'desconhecido',
                    $data['process_id'] ?? 'desconhecido',
                    $this->formatDuration($data['elapsed_time'] ?? 0),
                    $this->formatDuration($data['max_duration'] ?? 0)
                );
                
            case 'slow_progress':
                return sprintf(
                    'O processo "%s" (ID: %s) está progredindo lentamente. Progresso atual: %d%%, esperado: %d%%.',
                    $data['process_name'] ?? 'desconhecido',
                    $data['process_id'] ?? 'desconhecido',
                    $data['actual_progress'] ?? 0,
                    $data['expected_progress'] ?? 0
                );
                
            case 'error':
                return sprintf(
                    'Ocorreu um erro no processo "%s" (ID: %s): %s',
                    $data['process_name'] ?? 'desconhecido',
                    $data['process_id'] ?? 'desconhecido',
                    $data['error_message'] ?? 'Erro desconhecido'
                );
                
            default:
                return 'Alerta do sistema detectado. Verifique o painel de administração para mais detalhes.';
        }
    }
    
    /**
     * Formata o valor de uma métrica com base em seu tipo
     *
     * @param string $metric Nome da métrica
     * @param mixed $value Valor
     * @return string Valor formatado
     */
    private function formatMetricValue($metric, $value) {
        switch ($metric) {
            case 'execution_time':
                return $this->formatDuration($value);
                
            case 'memory_usage':
                return $this->formatBytes($value);
                
            case 'database_queries':
                return $value . ' consultas';
                
            default:
                return $value;
        }
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
    
    /**
     * Formata bytes para formato legível
     *
     * @param int $bytes Tamanho em bytes
     * @return string Tamanho formatado
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Sanitiza dados de alerta para evitar injeção XSS
     *
     * @param array $data Dados do alerta
     * @return array Dados sanitizados
     */
    private function sanitizeAlertData(array $data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            // Para valores escalares, sanitize a entrada
            if (is_scalar($value)) {
                // Converter para string e aplicar htmlspecialchars
                if (is_string($value)) {
                    $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                } else {
                    $sanitized[$key] = $value;
                }
            } 
            // Para arrays, sanitize recursivamente
            elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeAlertData($value);
            }
            // Ignora outros tipos de dados
        }
        
        return $sanitized;
    }
    
    /**
     * Registra um alerta no log
     *
     * @param string $alertType Tipo de alerta
     * @param array $data Dados do alerta
     * @param string $severity Severidade
     * @return void
     */
    private function logAlert($alertType, $data, $severity) {
        try {
            // Registrar no banco de dados
            $stmt = $this->db->prepare("
                INSERT INTO performance_alerts 
                (alert_type, alert_data, severity, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $alertType,
                json_encode($data),
                $severity
            ]);
            
            // Registrar no logger, se disponível
            if ($this->logger) {
                $this->logger->info('Performance alert triggered', [
                    'type' => $alertType,
                    'severity' => $severity,
                    'data' => $data
                ]);
            }
        } catch (Exception $e) {
            // Falha silenciosa, apenas log de erro
            error_log('Erro ao registrar alerta no log: ' . $e->getMessage());
        }
    }
    
    /**
     * Registra um erro no log
     *
     * @param string $message Mensagem de erro
     * @param array $context Contexto do erro
     * @return void
     */
    private function logError($message, array $context = []) {
        // Registrar no logger, se disponível
        if ($this->logger) {
            $this->logger->error($message, $context);
        }
        
        // Registrar via error_log
        error_log($message . ': ' . json_encode($context));
    }
}
