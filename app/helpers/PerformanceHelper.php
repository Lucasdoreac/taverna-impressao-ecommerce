<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Helper para funções auxiliares de testes de performance
 * Fornece métodos para análise, formatação e processamento de métricas de performance
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceHelper {
    /**
     * Inclui os scripts necessários para coleta de métricas no cliente
     * 
     * @param bool $autoInit Se deve inicializar automaticamente
     * @param array $options Opções adicionais para inicialização
     * @return string HTML com os scripts
     */
    public static function includeMetricsScripts($autoInit = true, $options = []) {
        $baseUrl = isset($_SERVER['REQUEST_SCHEME']) ? 
            $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . BASE_URL : 
            BASE_URL;
            
        $autoInitAttr = $autoInit ? 'true' : 'false';
        
        // Converter opções para JSON
        $optionsJson = !empty($options) ? json_encode($options) : '{}';
        
        $html = '';
        
        // Verificar se o atributo data-performance-auto-init já foi definido
        $html .= '<script>
            if (!document.body.hasAttribute("data-performance-auto-init")) {
                document.body.setAttribute("data-performance-auto-init", "' . $autoInitAttr . '");
            }
        </script>';
        
        // Incluir scripts
        $html .= '<script src="' . $baseUrl . 'assets/js/performance-metrics.js" defer></script>';
        
        // Se houver opções, inicializar com elas
        if (!empty($options)) {
            $html .= '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    if (window.PerformanceMetrics) {
                        window.PerformanceMetrics.init(' . $optionsJson . ');
                    }
                });
            </script>';
        }
        
        return $html;
    }
    
    /**
     * Formata um tempo em milissegundos para exibição
     * 
     * @param float $ms Tempo em milissegundos
     * @param bool $showUnit Se deve mostrar a unidade (ms)
     * @return string Tempo formatado
     */
    public static function formatTime($ms, $showUnit = true) {
        if ($ms === null || $ms === '') {
            return 'N/A';
        }
        
        $unit = $showUnit ? ' ms' : '';
        
        if ($ms < 0.1) {
            return '< 0,1' . $unit;
        }
        
        // Usar vírgula como separador decimal (padrão brasileiro)
        return number_format($ms, 1, ',', '.') . $unit;
    }
    
    /**
     * Formata um tamanho em bytes para exibição
     * 
     * @param int $bytes Tamanho em bytes
     * @return string Tamanho formatado
     */
    public static function formatSize($bytes) {
        if ($bytes === null || $bytes === '') {
            return 'N/A';
        }
        
        if ($bytes === 0) {
            return '0 Bytes';
        }
        
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $base = 1024;
        $i = floor(log($bytes, $base));
        
        // Usar vírgula como separador decimal (padrão brasileiro)
        return number_format($bytes / pow($base, $i), 2, ',', '.') . ' ' . $units[$i];
    }
    
    /**
     * Obtém a classe CSS para um valor de tempo
     * 
     * @param float $value Valor em milissegundos
     * @param string $type Tipo de métrica (page_load, api_response, db_query)
     * @return string Classe CSS (success, warning, danger)
     */
    public static function getPerformanceClass($value, $type = 'page_load') {
        // Valores padrão para cada tipo
        $thresholds = [
            'page_load' => [
                'success' => 1000,  // Menor que 1000ms: bom
                'warning' => 2000   // Menor que 2000ms: regular
            ],
            'api_response' => [
                'success' => 300,   // Menor que 300ms: bom
                'warning' => 500    // Menor que 500ms: regular
            ],
            'db_query' => [
                'success' => 100,   // Menor que 100ms: bom
                'warning' => 200    // Menor que 200ms: regular
            ],
            'ttfb' => [
                'success' => 200,   // Menor que 200ms: bom
                'warning' => 500    // Menor que 500ms: regular
            ],
            'fcp' => [
                'success' => 1800,  // Menor que 1800ms: bom (First Contentful Paint)
                'warning' => 3000   // Menor que 3000ms: regular
            ],
            'lcp' => [
                'success' => 2500,  // Menor que 2500ms: bom (Largest Contentful Paint)
                'warning' => 4000   // Menor que 4000ms: regular
            ]
        ];
        
        // Usar thresholds padrão se o tipo não estiver definido
        $currentThreshold = isset($thresholds[$type]) ? $thresholds[$type] : $thresholds['page_load'];
        
        if ($value < $currentThreshold['success']) {
            return 'success';
        } else if ($value < $currentThreshold['warning']) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    /**
     * Obtém o ícone Bootstrap apropriado para um valor de performance
     * 
     * @param float $value Valor em milissegundos
     * @param string $type Tipo de métrica (page_load, api_response, db_query)
     * @return string Nome do ícone Bootstrap
     */
    public static function getPerformanceIcon($value, $type = 'page_load') {
        $class = self::getPerformanceClass($value, $type);
        
        switch ($class) {
            case 'success':
                return 'check-circle-fill';
            case 'warning':
                return 'exclamation-triangle-fill';
            case 'danger':
                return 'exclamation-circle-fill';
            default:
                return 'question-circle-fill';
        }
    }
    
    /**
     * Obtém o texto de avaliação para um valor de performance
     * 
     * @param float $value Valor em milissegundos
     * @param string $type Tipo de métrica (page_load, api_response, db_query)
     * @return string Texto de avaliação (Excelente, Bom, Regular, Ruim)
     */
    public static function getPerformanceRating($value, $type = 'page_load') {
        // Valores para classificação de desempenho por tipo
        $ratings = [
            'page_load' => [
                'excellent' => 500,   // Menor que 500ms: excelente
                'good' => 1000,       // Menor que 1000ms: bom
                'regular' => 2000     // Menor que 2000ms: regular, acima disso: ruim
            ],
            'api_response' => [
                'excellent' => 100,   // Menor que 100ms: excelente
                'good' => 300,        // Menor que 300ms: bom
                'regular' => 500      // Menor que 500ms: regular, acima disso: ruim
            ],
            'db_query' => [
                'excellent' => 50,    // Menor que 50ms: excelente
                'good' => 100,        // Menor que 100ms: bom
                'regular' => 200      // Menor que 200ms: regular, acima disso: ruim
            ]
        ];
        
        // Usar ratings padrão se o tipo não estiver definido
        $currentRating = isset($ratings[$type]) ? $ratings[$type] : $ratings['page_load'];
        
        if ($value < $currentRating['excellent']) {
            return 'Excelente';
        } else if ($value < $currentRating['good']) {
            return 'Bom';
        } else if ($value < $currentRating['regular']) {
            return 'Regular';
        } else {
            return 'Ruim';
        }
    }
    
    /**
     * Gera recomendações com base em resultados de teste
     * 
     * @param array $testData Dados do teste
     * @return array Lista de recomendações
     */
    public static function generateRecommendations($testData) {
        $recommendations = [];
        
        if (empty($testData) || !isset($testData['test_type'])) {
            return [
                'Não foi possível gerar recomendações com os dados fornecidos.'
            ];
        }
        
        $testType = $testData['test_type'];
        $results = isset($testData['results']) ? json_decode($testData['results'], true) : [];
        
        if (empty($results) || !isset($results['summary'])) {
            return [
                'Dados de teste incompletos para gerar recomendações específicas.'
            ];
        }
        
        $summary = $results['summary'];
        
        // Recomendações específicas por tipo de teste
        switch ($testType) {
            case 'page_load':
                // Tempo de carregamento de página
                if (isset($summary['avg_load_time'])) {
                    $avgTime = $summary['avg_load_time'];
                    
                    if ($avgTime > 2000) {
                        $recommendations[] = 'O tempo de carregamento está muito alto (acima de 2 segundos). Considere otimizar recursos da página.';
                        $recommendations[] = 'Verifique recursos externos que podem estar atrasando o carregamento.';
                        $recommendations[] = 'Utilize técnicas de carregamento assíncrono para scripts não essenciais.';
                        $recommendations[] = 'Implemente lazy loading para imagens abaixo da primeira tela visível.';
                    } else if ($avgTime > 1000) {
                        $recommendations[] = 'O tempo de carregamento está acima do ideal. Verifique oportunidades de otimização.';
                        $recommendations[] = 'Considere compressão adicional para imagens grandes.';
                        $recommendations[] = 'Avalie a possibilidade de agrupar arquivos CSS e JavaScript menores.';
                    }
                }
                
                // Verificar TTFB (Time to First Byte)
                if (isset($results['iterations']) && count($results['iterations']) > 0) {
                    $ttfbSum = 0;
                    $ttfbCount = 0;
                    
                    foreach ($results['iterations'] as $iteration) {
                        if (isset($iteration['time_to_first_byte'])) {
                            $ttfbSum += $iteration['time_to_first_byte'];
                            $ttfbCount++;
                        }
                    }
                    
                    if ($ttfbCount > 0) {
                        $avgTtfb = $ttfbSum / $ttfbCount;
                        
                        if ($avgTtfb > 200) {
                            $recommendations[] = 'O tempo até o primeiro byte (TTFB) está muito alto (acima de 200ms). Considere otimizações no servidor ou no backend.';
                            $recommendations[] = 'Verifique a configuração do servidor web e possíveis gargalos no processamento PHP.';
                            $recommendations[] = 'Implemente caching no nível de aplicação e banco de dados.';
                        } else if ($avgTtfb > 100) {
                            $recommendations[] = 'O tempo até o primeiro byte (TTFB) está acima do ideal (> 100ms). Considere melhorias no backend e cache.';
                        }
                    }
                }
                break;
                
            case 'api_response':
                // Tempo de resposta de API
                if (isset($summary['avg_response_time'])) {
                    $avgTime = $summary['avg_response_time'];
                    
                    if ($avgTime > 500) {
                        $recommendations[] = 'O tempo de resposta da API está muito alto (acima de 500ms). Otimizações significativas são necessárias.';
                        $recommendations[] = 'Verifique a complexidade das consultas ao banco de dados utilizadas pelo endpoint.';
                        $recommendations[] = 'Considere implementar ou melhorar estratégias de cache para dados frequentemente acessados.';
                    } else if ($avgTime > 300) {
                        $recommendations[] = 'O tempo de resposta da API está acima do ideal. Verifique possíveis melhorias.';
                        $recommendations[] = 'Analise o processamento de dados no backend para otimizações potenciais.';
                    }
                }
                break;
                
            case 'db_query':
                // Tempo de consulta ao banco de dados
                if (isset($summary['avg_query_time'])) {
                    $avgTime = $summary['avg_query_time'];
                    
                    if ($avgTime > 200) {
                        $recommendations[] = 'O tempo de consulta está muito alto (acima de 200ms). Otimizações no banco de dados são necessárias.';
                        $recommendations[] = 'Revise a estrutura da consulta e considere adicionar índices apropriados.';
                        $recommendations[] = 'Verifique se há JOINs desnecessários ou subconsultas que podem ser otimizadas.';
                        $recommendations[] = 'Considere implementar cache para resultados de consultas frequentes.';
                    } else if ($avgTime > 100) {
                        $recommendations[] = 'O tempo de consulta está acima do ideal. Revise a estrutura da consulta.';
                        $recommendations[] = 'Verifique a utilização apropriada de índices nas tabelas envolvidas.';
                    }
                }
                break;
                
            case 'render_time':
                // Tempo de renderização no cliente
                if (isset($summary['avg_render_time'])) {
                    $avgTime = $summary['avg_render_time'];
                    
                    if ($avgTime > 100) {
                        $recommendations[] = 'O tempo de renderização está alto. Considere simplificar a estrutura DOM da página.';
                        $recommendations[] = 'Verifique se há operações JavaScript que podem estar bloqueando a renderização.';
                    }
                }
                break;
                
            case 'memory_usage':
                // Uso de memória
                if (isset($summary['memory_used'])) {
                    $memoryUsed = floatval(str_replace(' MB', '', $summary['memory_used']));
                    
                    if ($memoryUsed > 25) {
                        $recommendations[] = 'O uso de memória está muito alto (acima de 25MB). Otimizações são recomendadas.';
                        $recommendations[] = 'Verifique se há objetos grandes sendo mantidos em memória desnecessariamente.';
                        $recommendations[] = 'Considere implementar carregamento sob demanda para dados extensos.';
                    } else if ($memoryUsed > 15) {
                        $recommendations[] = 'O uso de memória está acima do ideal. Verifique possíveis melhorias.';
                    }
                }
                break;
                
            case 'network':
                // Uso de rede
                if (isset($summary['total_transferred'])) {
                    $totalTransferred = floatval(str_replace(' KB', '', $summary['total_transferred']));
                    
                    if ($totalTransferred > 2000) { // 2MB
                        $recommendations[] = 'O total de dados transferidos está muito alto (acima de 2MB). Otimizações são necessárias.';
                        $recommendations[] = 'Verifique o tamanho das imagens e considere formatos mais eficientes (WebP, AVIF).';
                        $recommendations[] = 'Considere compressão adicional para recursos CSS e JavaScript.';
                    } else if ($totalTransferred > 1000) { // 1MB
                        $recommendations[] = 'O total de dados transferidos está acima do ideal. Verifique possíveis melhorias.';
                    }
                }
                break;
                
            case 'full_page':
                // Teste completo
                if (isset($summary['overall_rating'])) {
                    $rating = $summary['overall_rating'];
                    
                    if ($rating === 'Ruim') {
                        $recommendations[] = 'O desempenho geral da página está comprometido. Uma revisão abrangente das otimizações é recomendada.';
                        $recommendations[] = 'Analise cada componente do teste (carregamento, banco de dados, memória) para identificar os gargalos específicos.';
                    } else if ($rating === 'Regular') {
                        $recommendations[] = 'O desempenho da página pode ser melhorado. Verifique os componentes com pior desempenho.';
                    }
                }
                
                // Verificar componentes específicos
                if (isset($summary['page_load']) && isset($summary['page_load']['avg_load_time'])) {
                    $pageLoadTime = $summary['page_load']['avg_load_time'];
                    
                    if ($pageLoadTime > 2000) {
                        $recommendations[] = 'O tempo de carregamento da página está comprometendo o desempenho geral. Priorize otimizações no frontend.';
                    }
                }
                
                if (isset($summary['db_query']) && isset($summary['db_query']['avg_query_time'])) {
                    $dbQueryTime = $summary['db_query']['avg_query_time'];
                    
                    if ($dbQueryTime > 200) {
                        $recommendations[] = 'As consultas ao banco de dados estão lentas e afetam o desempenho geral. Priorize otimizações no banco de dados.';
                    }
                }
                break;
        }
        
        // Recomendações gerais
        if (empty($recommendations)) {
            $recommendations[] = 'O desempenho está dentro dos parâmetros esperados. Continue monitorando regularmente para manter o bom desempenho.';
        }
        
        return $recommendations;
    }
    
    /**
     * Prepara dados para comparações de teste
     * 
     * @param array $testsData Array com dados de múltiplos testes
     * @return array Dados formatados para comparação ou erro
     */
    public static function prepareComparisonData($testsData) {
        if (empty($testsData)) {
            return [
                'error' => 'Nenhum teste fornecido para comparação'
            ];
        }
        
        // Verificar se todos os testes são do mesmo tipo
        $testType = $testsData[0]['test_type'];
        $sameType = true;
        
        foreach ($testsData as $test) {
            if ($test['test_type'] !== $testType) {
                $sameType = false;
                break;
            }
        }
        
        if (!$sameType) {
            return [
                'error' => 'Os testes selecionados são de tipos diferentes. A comparação só é possível entre testes do mesmo tipo.'
            ];
        }
        
        // Inicializar estrutura de dados para comparação
        $comparisonData = [
            'labels' => [],
            'datasets' => [],
            'testType' => $testType,
            'tableData' => []
        ];
        
        // Processar dados específicos por tipo de teste
        switch ($testType) {
            case 'page_load':
                // Preparar dados para tempo de carregamento de página
                $loadTimes = [];
                $ttfbTimes = [];
                $domReadyTimes = [];
                
                foreach ($testsData as $index => $test) {
                    $results = json_decode($test['results'], true);
                    $summary = isset($results['summary']) ? $results['summary'] : [];
                    
                    // Rótulo para o teste
                    $comparisonData['labels'][] = "Teste #" . $test['id'] . " (" . date('d/m/Y H:i', strtotime($test['timestamp'])) . ")";
                    
                    // Coletar métricas
                    $loadTimes[] = isset($summary['avg_load_time']) ? $summary['avg_load_time'] : null;
                    
                    // TTFB (Time to First Byte) se disponível
                    $ttfb = null;
                    if (isset($results['iterations']) && count($results['iterations']) > 0) {
                        $ttfbSum = 0;
                        $ttfbCount = 0;
                        
                        foreach ($results['iterations'] as $iteration) {
                            if (isset($iteration['time_to_first_byte'])) {
                                $ttfbSum += $iteration['time_to_first_byte'];
                                $ttfbCount++;
                            }
                        }
                        
                        if ($ttfbCount > 0) {
                            $ttfb = $ttfbSum / $ttfbCount;
                        }
                    }
                    $ttfbTimes[] = $ttfb;
                    
                    // Adicionar à tabela
                    $comparisonData['tableData'][] = [
                        'id' => $test['id'],
                        'timestamp' => date('d/m/Y H:i', strtotime($test['timestamp'])),
                        'avg_load_time' => isset($summary['avg_load_time']) ? $summary['avg_load_time'] : 'N/A',
                        'ttfb' => $ttfb,
                        'rating' => isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A'
                    ];
                }
                
                // Adicionar datasets para o gráfico
                $comparisonData['datasets'][] = [
                    'label' => 'Tempo Médio de Carregamento (ms)',
                    'data' => $loadTimes,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 1
                ];
                
                // Adicionar TTFB se disponível
                if (count(array_filter($ttfbTimes, function($v) { return $v !== null; })) > 0) {
                    $comparisonData['datasets'][] = [
                        'label' => 'Tempo até Primeiro Byte (ms)',
                        'data' => $ttfbTimes,
                        'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                        'borderColor' => 'rgb(255, 99, 132)',
                        'borderWidth' => 1
                    ];
                }
                break;
                
            case 'api_response':
                // Preparar dados para tempo de resposta de API
                $responseTimes = [];
                
                foreach ($testsData as $index => $test) {
                    $results = json_decode($test['results'], true);
                    $summary = isset($results['summary']) ? $results['summary'] : [];
                    
                    // Rótulo para o teste
                    $comparisonData['labels'][] = "Teste #" . $test['id'] . " (" . date('d/m/Y H:i', strtotime($test['timestamp'])) . ")";
                    
                    // Coletar métricas
                    $responseTimes[] = isset($summary['avg_response_time']) ? $summary['avg_response_time'] : null;
                    
                    // Adicionar à tabela
                    $comparisonData['tableData'][] = [
                        'id' => $test['id'],
                        'timestamp' => date('d/m/Y H:i', strtotime($test['timestamp'])),
                        'endpoint' => isset($summary['endpoint']) ? $summary['endpoint'] : 'N/A',
                        'method' => isset($summary['method']) ? $summary['method'] : 'GET',
                        'avg_response_time' => isset($summary['avg_response_time']) ? $summary['avg_response_time'] : 'N/A',
                        'rating' => isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A'
                    ];
                }
                
                // Adicionar dataset para o gráfico
                $comparisonData['datasets'][] = [
                    'label' => 'Tempo Médio de Resposta (ms)',
                    'data' => $responseTimes,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'borderWidth' => 1
                ];
                break;
                
            case 'db_query':
                // Preparar dados para tempo de consulta ao banco de dados
                $queryTimes = [];
                
                foreach ($testsData as $index => $test) {
                    $results = json_decode($test['results'], true);
                    $summary = isset($results['summary']) ? $results['summary'] : [];
                    
                    // Rótulo para o teste
                    $comparisonData['labels'][] = "Teste #" . $test['id'] . " (" . date('d/m/Y H:i', strtotime($test['timestamp'])) . ")";
                    
                    // Coletar métricas
                    $queryTimes[] = isset($summary['avg_query_time']) ? $summary['avg_query_time'] : null;
                    
                    // Adicionar à tabela
                    $comparisonData['tableData'][] = [
                        'id' => $test['id'],
                        'timestamp' => date('d/m/Y H:i', strtotime($test['timestamp'])),
                        'query_type' => isset($summary['query_type']) ? $summary['query_type'] : 'N/A',
                        'query_description' => isset($summary['query_description']) ? $summary['query_description'] : 'N/A',
                        'avg_query_time' => isset($summary['avg_query_time']) ? $summary['avg_query_time'] : 'N/A',
                        'rating' => isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A'
                    ];
                }
                
                // Adicionar dataset para o gráfico
                $comparisonData['datasets'][] = [
                    'label' => 'Tempo Médio de Consulta (ms)',
                    'data' => $queryTimes,
                    'backgroundColor' => 'rgba(255, 205, 86, 0.5)',
                    'borderColor' => 'rgb(255, 205, 86)',
                    'borderWidth' => 1
                ];
                break;
                
            // Outros tipos de teste podem ser adicionados aqui
            default:
                return [
                    'error' => 'Tipo de teste não suportado para comparação: ' . $testType
                ];
        }
        
        return $comparisonData;
    }
}
