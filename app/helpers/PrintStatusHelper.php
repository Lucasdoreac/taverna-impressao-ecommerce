<?php
/**
 * PrintStatusHelper - Helper para gerenciamento do status de impressões 3D
 * 
 * Este helper fornece funções utilitárias para trabalhar com o status de impressões 3D,
 * incluindo formatação, cálculos de tempo, simulação para demonstração e integração com APIs de impressoras.
 * 
 * @package Helpers
 */
class PrintStatusHelper {
    
    /**
     * Status de impressão disponíveis com códigos de cor
     * 
     * @var array
     */
    private static $statusColors = [
        'pending' => '#6c757d',    // Cinza
        'preparing' => '#17a2b8',  // Azul claro
        'printing' => '#007bff',   // Azul
        'paused' => '#ffc107',     // Amarelo
        'completed' => '#28a745',  // Verde
        'failed' => '#dc3545',     // Vermelho
        'canceled' => '#6c757d'    // Cinza
    ];
    
    /**
     * Obtém a cor CSS associada a um status
     * 
     * @param string $status Status de impressão
     * @return string Código de cor CSS
     */
    public static function getStatusColor($status) {
        return self::$statusColors[$status] ?? '#6c757d';
    }
    
    /**
     * Obtém a classe de cor Bootstrap para um status
     * 
     * @param string $status Status de impressão
     * @return string Nome da classe de cor Bootstrap
     */
    public static function getStatusBootstrapClass($status) {
        $classes = [
            'pending' => 'secondary',
            'preparing' => 'info',
            'printing' => 'primary',
            'paused' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'canceled' => 'secondary'
        ];
        
        return $classes[$status] ?? 'secondary';
    }
    
    /**
     * Formata o progresso para exibição
     * 
     * @param float $progress Valor do progresso (0-100)
     * @return string Progresso formatado
     */
    public static function formatProgress($progress) {
        return number_format($progress, 1) . '%';
    }
    
    /**
     * Formata um intervalo de tempo em segundos para exibição amigável
     * 
     * @param int $seconds Número de segundos
     * @param bool $shortFormat Usar formato curto (opcional)
     * @return string Tempo formatado
     */
    public static function formatTimeInterval($seconds, $shortFormat = false) {
        if ($seconds === null || $seconds === false) {
            return '-';
        }
        
        $seconds = (int)$seconds;
        
        if ($seconds < 60) {
            return $shortFormat ? "{$seconds}s" : "{$seconds} segundos";
        }
        
        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            
            if ($shortFormat) {
                return "{$minutes}m" . ($secs > 0 ? " {$secs}s" : "");
            }
            
            return "{$minutes} " . ($minutes == 1 ? "minuto" : "minutos") . 
                   ($secs > 0 ? " e {$secs} " . ($secs == 1 ? "segundo" : "segundos") : "");
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($shortFormat) {
            return "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
        }
        
        return "{$hours} " . ($hours == 1 ? "hora" : "horas") . 
               ($minutes > 0 ? " e {$minutes} " . ($minutes == 1 ? "minuto" : "minutos") : "");
    }
    
    /**
     * Formata uma data para exibição amigável
     * 
     * @param string $date Data em formato MySQL (Y-m-d H:i:s)
     * @param bool $includeTime Incluir o horário
     * @return string Data formatada
     */
    public static function formatDate($date, $includeTime = true) {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        
        $timestamp = strtotime($date);
        
        if ($includeTime) {
            return date('d/m/Y H:i', $timestamp);
        }
        
        return date('d/m/Y', $timestamp);
    }
    
    /**
     * Calcula o tempo restante estimado
     * 
     * @param float $progress Progresso atual (0-100)
     * @param int $elapsedSeconds Tempo decorrido em segundos
     * @return int|null Tempo restante estimado em segundos ou null se não for possível calcular
     */
    public static function calculateRemainingTime($progress, $elapsedSeconds) {
        if ($progress <= 0 || $elapsedSeconds <= 0) {
            return null;
        }
        
        // Estimar tempo total com base no progresso e tempo decorrido
        $estimatedTotalSeconds = ($elapsedSeconds / $progress) * 100;
        
        // Calcular tempo restante
        $remainingSeconds = $estimatedTotalSeconds - $elapsedSeconds;
        
        return max(0, round($remainingSeconds));
    }
    
    /**
     * Gera um número aleatório dentro de um intervalo com uma tendência
     * 
     * @param float $min Valor mínimo
     * @param float $max Valor máximo
     * @param float $tendency Valor de tendência
     * @param float $strength Força da tendência (0-1)
     * @return float Valor aleatório
     */
    private static function randomWithTendency($min, $max, $tendency, $strength = 0.7) {
        // Valor completamente aleatório
        $random = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        
        // Aplicar tendência
        $value = $random * (1 - $strength) + $tendency * $strength;
        
        // Garantir que o valor esteja dentro dos limites
        return max($min, min($max, $value));
    }
    
    /**
     * Simula uma atualização de progresso para demonstração
     * 
     * @param float $currentProgress Progresso atual
     * @param string $currentStatus Status atual
     * @param int $remainingTimeSeconds Tempo restante estimado em segundos
     * @param int $elapsedTimeSeconds Tempo decorrido em segundos
     * @return array Dados atualizados
     */
    public static function simulateProgressUpdate(
        $currentProgress, 
        $currentStatus, 
        $remainingTimeSeconds, 
        $elapsedTimeSeconds
    ) {
        // Não atualizar se já estiver concluído, falhou ou foi cancelado
        if (in_array($currentStatus, ['completed', 'failed', 'canceled'])) {
            return [
                'progress' => $currentProgress,
                'status' => $currentStatus,
                'remainingTimeSeconds' => $remainingTimeSeconds,
                'elapsedTimeSeconds' => $elapsedTimeSeconds,
                'metrics' => []
            ];
        }
        
        $data = [];
        $data['status'] = $currentStatus;
        $data['elapsedTimeSeconds'] = $elapsedTimeSeconds + mt_rand(10, 30);
        
        // 5% de chance de pausar/retomar se estiver imprimindo/pausado
        if (mt_rand(1, 100) <= 5) {
            if ($currentStatus === 'printing') {
                $data['status'] = 'paused';
            } elseif ($currentStatus === 'paused') {
                $data['status'] = 'printing';
            }
        }
        
        // 2% de chance de falhar se não for completed
        if (mt_rand(1, 100) <= 2 && $currentProgress < 95) {
            $data['status'] = 'failed';
            $data['progress'] = $currentProgress;
            $data['remainingTimeSeconds'] = 0;
            return $data;
        }
        
        // Atualizar progresso apenas se estiver imprimindo
        if ($currentStatus === 'printing') {
            // Calcular incremento de progresso
            $progressIncrement = self::randomWithTendency(0.1, 1.5, 0.7);
            
            // Se estiver perto do fim, diminuir o incremento para simulação mais realista
            if ($currentProgress > 90) {
                $progressIncrement = self::randomWithTendency(0.05, 0.5, 0.2);
            }
            
            $data['progress'] = min(99.9, $currentProgress + $progressIncrement);
            
            // Se chegou a 100%, marcar como concluído
            if ($data['progress'] >= 99.9) {
                $data['progress'] = 100;
                $data['status'] = 'completed';
                $data['remainingTimeSeconds'] = 0;
            } else {
                // Atualizar tempo restante
                if ($remainingTimeSeconds > 30) {
                    // Reduzir o tempo restante, com alguma variação
                    $timeReduction = mt_rand(10, 30);
                    $data['remainingTimeSeconds'] = max(0, $remainingTimeSeconds - $timeReduction);
                } else {
                    $data['remainingTimeSeconds'] = mt_rand(10, 20);
                }
            }
        } else {
            $data['progress'] = $currentProgress;
            $data['remainingTimeSeconds'] = $remainingTimeSeconds;
        }
        
        // Simular algumas métricas
        $data['metrics'] = self::simulateMetrics($data['progress'], $currentStatus);
        
        return $data;
    }
    
    /**
     * Simula métricas de impressão para demonstração
     * 
     * @param float $progress Progresso atual
     * @param string $status Status atual
     * @return array Métricas simuladas
     */
    public static function simulateMetrics($progress, $status) {
        if ($status !== 'printing') {
            return [];
        }
        
        // Total estimado de camadas
        $totalLayers = mt_rand(150, 300);
        
        // Camada atual baseada no progresso
        $currentLayer = max(1, min($totalLayers, round($progress * $totalLayers / 100)));
        
        // Temperatura do hotend variando em torno de 210°C
        $hotendTemp = self::randomWithTendency(205, 215, 210, 0.9);
        
        // Temperatura da mesa variando em torno de 60°C
        $bedTemp = self::randomWithTendency(58, 62, 60, 0.9);
        
        // Outras métricas variáveis
        $speedPercentage = mt_rand(90, 110);
        $fanSpeed = mt_rand(80, 100);
        
        // Filamento usado simulado (crescendo com o progresso)
        $filamentUsed = ($progress / 100) * self::randomWithTendency(5000, 8000, 6500, 0.8);
        
        return [
            'hotend_temp' => round($hotendTemp, 1),
            'bed_temp' => round($bedTemp, 1),
            'speed_percentage' => $speedPercentage,
            'fan_speed_percentage' => $fanSpeed,
            'layer_height' => 0.2,
            'current_layer' => $currentLayer,
            'total_layers' => $totalLayers,
            'filament_used_mm' => round($filamentUsed, 1),
            'additional_data' => [
                'z_height' => round($currentLayer * 0.2, 2),
                'print_time_estimate' => self::formatTimeInterval(($totalLayers - $currentLayer) * mt_rand(30, 60)),
                'filament_used_volume' => round($filamentUsed * 2.4 / 1000, 2) . ' cm³'
            ]
        ];
    }
    
    /**
     * Gera um identificador único para uma impressão
     * 
     * @param int $orderId ID do pedido
     * @param int $productId ID do produto
     * @param int $printQueueId ID na fila de impressão
     * @return string Identificador único
     */
    public static function generatePrintId($orderId, $productId, $printQueueId) {
        $prefix = 'PRINT';
        $timestamp = dechex(time());
        $uniqueId = substr(md5($orderId . $productId . $printQueueId . mt_rand()), 0, 8);
        
        return strtoupper("{$prefix}-{$timestamp}-{$uniqueId}");
    }
    
    /**
     * Obtém uma mensagem de status amigável
     * 
     * @param string $status Status de impressão
     * @param float $progress Progresso atual
     * @param string $productName Nome do produto (opcional)
     * @return string Mensagem amigável
     */
    public static function getFriendlyStatusMessage($status, $progress, $productName = '') {
        $productInfo = !empty($productName) ? " do produto \"$productName\"" : "";
        
        switch ($status) {
            case 'pending':
                return "Aguardando início da impressão" . $productInfo;
                
            case 'preparing':
                return "Preparando para iniciar a impressão" . $productInfo;
                
            case 'printing':
                return "Impressão" . $productInfo . " em andamento - " . self::formatProgress($progress) . " concluído";
                
            case 'paused':
                return "Impressão" . $productInfo . " pausada em " . self::formatProgress($progress);
                
            case 'completed':
                return "Impressão" . $productInfo . " concluída com sucesso";
                
            case 'failed':
                return "Falha na impressão" . $productInfo . " em " . self::formatProgress($progress);
                
            case 'canceled':
                return "Impressão" . $productInfo . " cancelada em " . self::formatProgress($progress);
                
            default:
                return "Status desconhecido da impressão" . $productInfo . " (" . self::formatProgress($progress) . ")";
        }
    }
    
    /**
     * Gera um ícone HTML para o status
     * 
     * @param string $status Status de impressão
     * @return string Código HTML do ícone
     */
    public static function getStatusIcon($status) {
        $icons = [
            'pending' => '<i class="bi bi-hourglass-split"></i>',
            'preparing' => '<i class="bi bi-tools"></i>',
            'printing' => '<i class="bi bi-printer"></i>',
            'paused' => '<i class="bi bi-pause-circle"></i>',
            'completed' => '<i class="bi bi-check-circle"></i>',
            'failed' => '<i class="bi bi-exclamation-triangle"></i>',
            'canceled' => '<i class="bi bi-x-circle"></i>'
        ];
        
        return $icons[$status] ?? '<i class="bi bi-question-circle"></i>';
    }
    
    /**
     * Gera um badge HTML para o status
     * 
     * @param string $status Status de impressão
     * @param float $progress Progresso (opcional)
     * @return string Código HTML do badge
     */
    public static function getStatusBadge($status, $progress = null) {
        $class = 'badge bg-' . self::getStatusBootstrapClass($status);
        $icon = self::getStatusIcon($status);
        $label = ucfirst(PrintStatusModel::getAvailableStatuses()[$status] ?? $status);
        
        $progressInfo = ($progress !== null && in_array($status, ['printing', 'paused'])) 
            ? ' ' . self::formatProgress($progress) 
            : '';
        
        return "<span class=\"{$class}\">{$icon} {$label}{$progressInfo}</span>";
    }
    
    /**
     * Gera uma barra de progresso HTML
     * 
     * @param float $progress Progresso atual
     * @param string $status Status atual
     * @param string $size Tamanho da barra (sm, md, lg)
     * @return string Código HTML da barra de progresso
     */
    public static function getProgressBar($progress, $status, $size = '') {
        $sizeClass = !empty($size) ? "progress-$size" : '';
        $colorClass = 'bg-' . self::getStatusBootstrapClass($status);
        
        // Ajustar valores extremos para visualização
        $displayProgress = max(1, min(100, $progress));
        
        $html = '<div class="progress ' . $sizeClass . '">';
        $html .= '<div class="progress-bar ' . $colorClass . '" role="progressbar" ';
        $html .= 'style="width: ' . $displayProgress . '%" ';
        $html .= 'aria-valuenow="' . $displayProgress . '" ';
        $html .= 'aria-valuemin="0" aria-valuemax="100">';
        
        // Mostrar texto se a barra não for pequena
        if ($size !== 'sm') {
            $html .= self::formatProgress($progress);
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    /**
     * Gera um elemento de linha do tempo para visualização de histórico
     * 
     * @param array $updates Lista de atualizações de status
     * @return string Código HTML da linha do tempo
     */
    public static function renderTimeline($updates) {
        if (empty($updates)) {
            return '<p class="text-muted">Nenhuma atualização disponível.</p>';
        }
        
        $html = '<div class="timeline">';
        
        foreach ($updates as $update) {
            $statusClass = 'timeline-status-' . self::getStatusBootstrapClass($update['new_status']);
            $icon = self::getStatusIcon($update['new_status']);
            $date = self::formatDate($update['created_at']);
            
            $previousStatus = !empty($update['previous_status']) 
                ? PrintStatusModel::getAvailableStatuses()[$update['previous_status']] 
                : 'N/A';
            
            $newStatus = PrintStatusModel::getAvailableStatuses()[$update['new_status']] ?? $update['new_status'];
            
            $message = !empty($update['message']) 
                ? $update['message'] 
                : "Status alterado de \"$previousStatus\" para \"$newStatus\"";
            
            if (!empty($update['previous_progress']) && !empty($update['new_progress'])) {
                $progressInfo = "Progresso: " . self::formatProgress($update['previous_progress']) . 
                                " → " . self::formatProgress($update['new_progress']);
                $message .= "<div class=\"text-muted small mt-1\">$progressInfo</div>";
            }
            
            $updatedBy = !empty($update['updated_by']) && $update['updated_by'] !== 'system'
                ? "<div class=\"text-muted small\">Atualizado por: {$update['updated_by']}</div>"
                : "";
            
            $html .= '<div class="timeline-item">';
            $html .= '<div class="timeline-marker ' . $statusClass . '">' . $icon . '</div>';
            $html .= '<div class="timeline-content">';
            $html .= '<div class="timeline-heading">';
            $html .= '<span class="timeline-title">' . $newStatus . '</span>';
            $html .= '<span class="timeline-date">' . $date . '</span>';
            $html .= '</div>';
            $html .= '<div class="timeline-body">' . $message . '</div>';
            $html .= $updatedBy;
            $html .= '</div></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Gera o código HTML para um mini dashboard de impressão
     * 
     * @param array $printStatus Dados do status de impressão
     * @param bool $includeActions Incluir botões de ação
     * @return string Código HTML do mini dashboard
     */
    public static function renderMiniDashboard($printStatus, $includeActions = false) {
        if (empty($printStatus)) {
            return '<div class="alert alert-warning">Dados de impressão não disponíveis.</div>';
        }
        
        $status = $printStatus['status'];
        $progress = $printStatus['progress_percentage'];
        $productName = $printStatus['product']['name'] ?? 'Produto';
        
        $html = '<div class="card print-status-dashboard">';
        $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
        $html .= '<h5 class="mb-0">' . self::getStatusIcon($status) . ' ' . $productName . '</h5>';
        $html .= self::getStatusBadge($status, $progress);
        $html .= '</div>';
        
        $html .= '<div class="card-body">';
        
        // Barra de progresso
        $html .= '<div class="mb-3">';
        $html .= self::getProgressBar($progress, $status);
        $html .= '</div>';
        
        // Métricas rápidas
        $html .= '<div class="row mb-3">';
        
        // Tempo decorrido
        $html .= '<div class="col-6 col-md-3">';
        $html .= '<div class="metric-item">';
        $html .= '<div class="metric-label">Tempo decorrido</div>';
        $html .= '<div class="metric-value">';
        $html .= self::formatTimeInterval($printStatus['elapsed_print_time_seconds'] ?? 0, true);
        $html .= '</div></div></div>';
        
        // Tempo restante
        $html .= '<div class="col-6 col-md-3">';
        $html .= '<div class="metric-item">';
        $html .= '<div class="metric-label">Tempo restante</div>';
        $html .= '<div class="metric-value">';
        
        if ($status === 'completed' || $status === 'failed' || $status === 'canceled') {
            $html .= '-';
        } else if (isset($printStatus['remaining_time_seconds'])) {
            $html .= self::formatTimeInterval($printStatus['remaining_time_seconds'], true);
        } else {
            $html .= 'Calculando...';
        }
        
        $html .= '</div></div></div>';
        
        // Iniciado em
        $html .= '<div class="col-6 col-md-3">';
        $html .= '<div class="metric-item">';
        $html .= '<div class="metric-label">Iniciado em</div>';
        $html .= '<div class="metric-value">';
        $html .= self::formatDate($printStatus['started_at'] ?? null);
        $html .= '</div></div></div>';
        
        // Conclusão prevista
        $html .= '<div class="col-6 col-md-3">';
        $html .= '<div class="metric-item">';
        $html .= '<div class="metric-label">Conclusão prevista</div>';
        $html .= '<div class="metric-value">';
        
        if ($status === 'completed') {
            $html .= self::formatDate($printStatus['completed_at'] ?? null);
        } else if ($status === 'failed' || $status === 'canceled') {
            $html .= '-';
        } else if (isset($printStatus['estimated_completion'])) {
            $html .= self::formatDate($printStatus['estimated_completion']);
        } else {
            $html .= 'Calculando...';
        }
        
        $html .= '</div></div></div>';
        
        $html .= '</div>'; // Fim da row de métricas
        
        // Métricas técnicas se disponíveis
        if (!empty($printStatus['latest_metrics'])) {
            $metrics = $printStatus['latest_metrics'];
            
            $html .= '<div class="row tech-metrics mb-3">';
            
            // Temperatura do hotend
            if (isset($metrics['hotend_temp'])) {
                $html .= '<div class="col-6 col-md-3">';
                $html .= '<div class="metric-item">';
                $html .= '<div class="metric-label">Temp. Extrusor</div>';
                $html .= '<div class="metric-value">' . $metrics['hotend_temp'] . '°C</div>';
                $html .= '</div></div>';
            }
            
            // Temperatura da mesa
            if (isset($metrics['bed_temp'])) {
                $html .= '<div class="col-6 col-md-3">';
                $html .= '<div class="metric-item">';
                $html .= '<div class="metric-label">Temp. Mesa</div>';
                $html .= '<div class="metric-value">' . $metrics['bed_temp'] . '°C</div>';
                $html .= '</div></div>';
            }
            
            // Camada atual
            if (isset($metrics['current_layer'], $metrics['total_layers'])) {
                $html .= '<div class="col-6 col-md-3">';
                $html .= '<div class="metric-item">';
                $html .= '<div class="metric-label">Camada</div>';
                $html .= '<div class="metric-value">' . $metrics['current_layer'] . ' / ' . $metrics['total_layers'] . '</div>';
                $html .= '</div></div>';
            }
            
            // Filamento usado
            if (isset($metrics['filament_used_mm'])) {
                $html .= '<div class="col-6 col-md-3">';
                $html .= '<div class="metric-item">';
                $html .= '<div class="metric-label">Filamento usado</div>';
                $html .= '<div class="metric-value">' . round($metrics['filament_used_mm'] / 1000, 2) . 'm</div>';
                $html .= '</div></div>';
            }
            
            $html .= '</div>'; // Fim da row de métricas técnicas
        }
        
        // Mensagens recentes
        if (!empty($printStatus['recent_messages'])) {
            $html .= '<div class="mt-3">';
            $html .= '<h6>Mensagens recentes</h6>';
            $html .= '<div class="alert-messages">';
            
            foreach (array_slice($printStatus['recent_messages'], 0, 3) as $message) {
                $alertClass = 'alert-' . ($message['type'] ?? 'info');
                $html .= '<div class="alert ' . $alertClass . ' py-2 mb-2">';
                $html .= $message['message'];
                $html .= '<div class="small text-muted mt-1">' . self::formatDate($message['created_at']) . '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div></div>';
        }
        
        // Botões de ação para administradores
        if ($includeActions) {
            $html .= '<div class="mt-3 d-flex justify-content-end">';
            
            // Botões específicos baseados no status
            if ($status === 'printing') {
                $html .= '<button class="btn btn-warning btn-sm me-2" onclick="pausePrint(' . $printStatus['id'] . ')">';
                $html .= '<i class="bi bi-pause-fill"></i> Pausar</button>';
            } elseif ($status === 'paused') {
                $html .= '<button class="btn btn-primary btn-sm me-2" onclick="resumePrint(' . $printStatus['id'] . ')">';
                $html .= '<i class="bi bi-play-fill"></i> Continuar</button>';
            }
            
            if (in_array($status, ['pending', 'preparing', 'printing', 'paused'])) {
                $html .= '<button class="btn btn-danger btn-sm" onclick="cancelPrint(' . $printStatus['id'] . ')">';
                $html .= '<i class="bi bi-x-circle"></i> Cancelar</button>';
            }
            
            // Botão de detalhes sempre visível
            $html .= '<a href="' . BASE_URL . 'admin/impressao/' . $printStatus['id'] . '" ';
            $html .= 'class="btn btn-outline-primary btn-sm ms-2">';
            $html .= '<i class="bi bi-info-circle"></i> Detalhes</a>';
            
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Fim card-body
        $html .= '</div>'; // Fim card
        
        return $html;
    }
    
    /**
     * Inclui o CSS necessário para os elementos de status
     * 
     * @return string Tag <style> com CSS
     */
    public static function includeCSS() {
        $css = '
        <style>
            /* Estilos da linha do tempo */
            .timeline {
                position: relative;
                margin: 0 0 2rem;
                padding: 0;
                list-style: none;
            }
            
            .timeline::before {
                content: "";
                position: absolute;
                top: 0;
                bottom: 0;
                left: 31px;
                width: 4px;
                background: #ddd;
            }
            
            .timeline-item {
                position: relative;
                margin-bottom: 15px;
                display: flex;
            }
            
            .timeline-marker {
                position: absolute;
                width: 24px;
                height: 24px;
                left: 18px;
                top: 0;
                border-radius: 50%;
                text-align: center;
                line-height: 24px;
                color: #fff;
                z-index: 2;
            }
            
            .timeline-content {
                margin-left: 60px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
                width: 100%;
            }
            
            .timeline-heading {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }
            
            .timeline-title {
                font-weight: bold;
            }
            
            .timeline-date {
                color: #6c757d;
                font-size: 0.85rem;
            }
            
            /* Cores para status de impressão */
            .timeline-status-primary { background-color: #007bff; }
            .timeline-status-success { background-color: #28a745; }
            .timeline-status-warning { background-color: #ffc107; }
            .timeline-status-danger { background-color: #dc3545; }
            .timeline-status-info { background-color: #17a2b8; }
            .timeline-status-secondary { background-color: #6c757d; }
            
            /* Estilos para métricas */
            .metric-item {
                text-align: center;
                padding: 8px;
                border-radius: 4px;
                background-color: #f8f9fa;
                height: 100%;
            }
            
            .metric-label {
                font-size: 0.8rem;
                color: #6c757d;
                margin-bottom: 3px;
            }
            
            .metric-value {
                font-weight: bold;
                font-size: 1rem;
            }
            
            .tech-metrics .metric-item {
                background-color: #e9ecef;
            }
            
            /* Print status dashboard */
            .print-status-dashboard {
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                margin-bottom: 20px;
            }
        </style>';
        
        return $css;
    }
    
    /**
     * Inclui o JavaScript necessário para as funcionalidades de atualização em tempo real
     * 
     * @param int $refreshInterval Intervalo de atualização em segundos
     * @return string Tag <script> com JavaScript
     */
    public static function includeJavaScript($refreshInterval = 30) {
        $js = '
        <script>
            // Temporizadores para atualização automática
            const statusUpdateTimers = {};
            
            // Função para atualizar o status de uma impressão
            function updatePrintStatus(printStatusId, elementId = null) {
                const url = `' . BASE_URL . 'api/print-status/${printStatusId}`;
                
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erro ao buscar status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Se um elementId foi fornecido, atualizar apenas aquele elemento
                        if (elementId) {
                            const element = document.getElementById(elementId);
                            if (element) {
                                element.innerHTML = data.html;
                            }
                        }
                        
                        // Atualizar elementos com a classe específica para este printStatusId
                        const elements = document.querySelectorAll(`.print-status-${printStatusId}`);
                        elements.forEach(el => {
                            // Verificar o tipo de elemento a ser atualizado
                            if (el.classList.contains("print-status-badge")) {
                                el.outerHTML = data.badge;
                            } else if (el.classList.contains("print-status-progress")) {
                                el.outerHTML = data.progressBar;
                            } else if (el.classList.contains("print-status-dashboard")) {
                                el.outerHTML = data.dashboard;
                            }
                        });
                        
                        // Continuar atualizando se ainda não estiver concluído
                        if (["completed", "failed", "canceled"].indexOf(data.status) === -1) {
                            scheduleUpdate(printStatusId, elementId);
                        } else {
                            // Parar de atualizar se estiver concluído
                            if (statusUpdateTimers[printStatusId]) {
                                clearTimeout(statusUpdateTimers[printStatusId]);
                                delete statusUpdateTimers[printStatusId];
                            }
                        }
                    })
                    .catch(error => {
                        console.error("Erro ao atualizar status:", error);
                        // Tentar novamente após intervalo maior em caso de erro
                        setTimeout(() => {
                            updatePrintStatus(printStatusId, elementId);
                        }, ' . ($refreshInterval * 2000) . ');
                    });
            }
            
            // Função para agendar próxima atualização
            function scheduleUpdate(printStatusId, elementId = null) {
                // Limpar temporizador existente, se houver
                if (statusUpdateTimers[printStatusId]) {
                    clearTimeout(statusUpdateTimers[printStatusId]);
                }
                
                // Configurar novo temporizador
                statusUpdateTimers[printStatusId] = setTimeout(() => {
                    updatePrintStatus(printStatusId, elementId);
                }, ' . ($refreshInterval * 1000) . ');
            }
            
            // Função para iniciar atualização automática
            function startAutoUpdate(printStatusId, elementId = null) {
                // Fazer primeira atualização imediatamente
                updatePrintStatus(printStatusId, elementId);
            }
            
            // Funções para ações administrativas
            function pausePrint(printStatusId) {
                if (confirm("Deseja pausar esta impressão?")) {
                    updatePrintAction(printStatusId, "pause");
                }
            }
            
            function resumePrint(printStatusId) {
                if (confirm("Deseja continuar esta impressão?")) {
                    updatePrintAction(printStatusId, "resume");
                }
            }
            
            function cancelPrint(printStatusId) {
                if (confirm("Tem certeza que deseja cancelar esta impressão? Esta ação não pode ser desfeita.")) {
                    updatePrintAction(printStatusId, "cancel");
                }
            }
            
            function updatePrintAction(printStatusId, action) {
                const url = `' . BASE_URL . 'admin/impressoes/action`;
                const formData = new FormData();
                formData.append("print_status_id", printStatusId);
                formData.append("action", action);
                formData.append("csrf_token", document.querySelector("meta[name=\'csrf-token\']").getAttribute("content"));
                
                fetch(url, {
                    method: "POST",
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro ao executar ação: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Atualizar a visualização imediatamente
                        updatePrintStatus(printStatusId);
                    } else {
                        alert(`Erro: ${data.message}`);
                    }
                })
                .catch(error => {
                    console.error("Erro ao executar ação:", error);
                    alert("Ocorreu um erro ao executar a ação. Por favor, tente novamente.");
                });
            }
        </script>';
        
        return $js;
    }
}
