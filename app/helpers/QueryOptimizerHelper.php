<?php
/**
 * QueryOptimizerHelper - Helper para análise e otimização de consultas SQL
 * 
 * Este helper fornece funções para:
 * - Analisar logs de consultas SQL lentas
 * - Recomendar otimizações para consultas
 * - Medir o tempo de execução de consultas
 * - Verificar a efetividade de índices
 * - Gerar relatórios de performance de consultas
 */
class QueryOptimizerHelper {
    // Tempo limite para considerar uma consulta lenta (em segundos)
    const SLOW_QUERY_THRESHOLD = 0.5;
    
    // Caminho para o diretório de logs
    private $logPath;
    
    // Conexão com o banco de dados
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->logPath = APP_PATH . '/logs/';
        
        // Garantir que o diretório de logs existe
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        // Obter conexão com o banco de dados
        $this->db = Database::getInstance();
    }
    
    /**
     * Mede o tempo de execução de uma consulta
     * 
     * @param string $query Consulta SQL a ser executada
     * @param array $params Parâmetros para a consulta (opcional)
     * @return array Resultado da consulta e informações de performance
     */
    public function measureQueryTime($query, $params = []) {
        // Iniciar cronômetro
        $startTime = microtime(true);
        
        // Executar a consulta
        $result = $this->db->select($query, $params);
        
        // Calcular o tempo decorrido
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Registrar se for uma consulta lenta
        if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
            $this->logSlowQuery($query, $params, $executionTime);
        }
        
        return [
            'result' => $result,
            'execution_time' => $executionTime,
            'is_slow' => $executionTime > self::SLOW_QUERY_THRESHOLD,
            'row_count' => count($result),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Registra uma consulta lenta no log
     * 
     * @param string $query Consulta SQL
     * @param array $params Parâmetros da consulta
     * @param float $executionTime Tempo de execução em segundos
     * @return void
     */
    public function logSlowQuery($query, $params, $executionTime) {
        $logFile = $this->logPath . 'slow_queries_' . date('Y-m-d') . '.log';
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'query' => $query,
            'params' => json_encode($params),
            'execution_time' => $executionTime,
            'backtrace' => $this->getQueryOrigin()
        ];
        
        $logLine = json_encode($logEntry) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }
    
    /**
     * Obtém a origem da consulta (arquivo e linha)
     * 
     * @return string Informação sobre a origem da consulta
     */
    private function getQueryOrigin() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $origin = '';
        
        // Ignorar os primeiros frames (próprio helper)
        for ($i = 2; $i < count($backtrace); $i++) {
            if (isset($backtrace[$i]['file']) && 
                !strpos($backtrace[$i]['file'], 'QueryOptimizerHelper.php') &&
                !strpos($backtrace[$i]['file'], 'Database.php')) {
                
                $origin = $backtrace[$i]['file'] . ':' . $backtrace[$i]['line'];
                break;
            }
        }
        
        return $origin ?: 'unknown';
    }
    
    /**
     * Analisa logs de consultas lentas e gera um relatório
     * 
     * @param string $date Data no formato Y-m-d (opcional, padrão é hoje)
     * @return array Relatório de consultas lentas
     */
    public function analyzeSlowQueries($date = null) {
        $date = $date ?: date('Y-m-d');
        $logFile = $this->logPath . 'slow_queries_' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [
                'date' => $date,
                'queries' => [],
                'total_count' => 0,
                'average_time' => 0,
                'max_time' => 0,
                'common_patterns' => []
            ];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $queries = [];
        $queryPatterns = [];
        $totalTime = 0;
        $maxTime = 0;
        
        foreach ($lines as $line) {
            $data = json_decode($line, true);
            
            if ($data) {
                // Extrair o tipo de consulta (SELECT, INSERT, etc.)
                preg_match('/^\\s*(\\w+)/', $data['query'], $matches);
                $queryType = isset($matches[1]) ? strtoupper($matches[1]) : 'UNKNOWN';
                
                // Simplificar a consulta para encontrar padrões
                $simplifiedQuery = $this->simplifyQuery($data['query']);
                
                if (isset($queryPatterns[$simplifiedQuery])) {
                    $queryPatterns[$simplifiedQuery]['count']++;
                    $queryPatterns[$simplifiedQuery]['total_time'] += $data['execution_time'];
                    
                    if ($data['execution_time'] > $queryPatterns[$simplifiedQuery]['max_time']) {
                        $queryPatterns[$simplifiedQuery]['max_time'] = $data['execution_time'];
                        $queryPatterns[$simplifiedQuery]['slowest_example'] = $data['query'];
                    }
                } else {
                    $queryPatterns[$simplifiedQuery] = [
                        'count' => 1,
                        'type' => $queryType,
                        'total_time' => $data['execution_time'],
                        'max_time' => $data['execution_time'],
                        'slowest_example' => $data['query'],
                        'file_origin' => $data['backtrace']
                    ];
                }
                
                $queries[] = $data;
                $totalTime += $data['execution_time'];
                $maxTime = max($maxTime, $data['execution_time']);
            }
        }
        
        // Ordenar padrões por tempo total (do mais lento para o mais rápido)
        uasort($queryPatterns, function($a, $b) {
            return $b['total_time'] <=> $a['total_time'];
        });
        
        // Preparar relatório
        $commonPatterns = [];
        foreach ($queryPatterns as $pattern => $info) {
            $commonPatterns[] = [
                'pattern' => $pattern,
                'count' => $info['count'],
                'type' => $info['type'],
                'total_time' => $info['total_time'],
                'average_time' => $info['total_time'] / $info['count'],
                'max_time' => $info['max_time'],
                'slowest_example' => $info['slowest_example'],
                'file_origin' => $info['file_origin'],
                'optimization_suggestions' => $this->suggestOptimizations($info['slowest_example'], $info['type'])
            ];
        }
        
        return [
            'date' => $date,
            'queries' => $queries,
            'total_count' => count($queries),
            'average_time' => $queries ? $totalTime / count($queries) : 0,
            'max_time' => $maxTime,
            'common_patterns' => $commonPatterns
        ];
    }
    
    /**
     * Simplifica uma consulta SQL para encontrar padrões
     * 
     * @param string $query Consulta SQL
     * @return string Consulta simplificada para identificação de padrões
     */
    private function simplifyQuery($query) {
        // Remover espaços em excesso
        $query = preg_replace('/\\s+/', ' ', trim($query));
        
        // Substituir valores literais
        $query = preg_replace('/"[^"]*"/', '"..."', $query);
        $query = preg_replace("/\\'[^\\']*\\'/", "'...'", $query);
        
        // Substituir números
        $query = preg_replace('/\\b\\d+\\b/', 'N', $query);
        
        // Simplificar listas IN
        $query = preg_replace('/IN\\s*\\([^)]+\\)/', 'IN (...)', $query);
        
        return $query;
    }
    
    /**
     * Sugere otimizações para uma consulta
     * 
     * @param string $query Consulta SQL
     * @param string $queryType Tipo da consulta (SELECT, INSERT, etc.)
     * @return array Lista de sugestões de otimização
     */
    public function suggestOptimizations($query, $queryType = null) {
        if ($queryType === null) {
            // Extrair o tipo de consulta
            preg_match('/^\\s*(\\w+)/', $query, $matches);
            $queryType = isset($matches[1]) ? strtoupper($matches[1]) : 'UNKNOWN';
        }
        
        $suggestions = [];
        
        if ($queryType === 'SELECT') {
            // Verificar se está usando SELECT *
            if (preg_match('/SELECT\\s+\\*\\s+FROM/i', $query)) {
                $suggestions[] = "Evite usar 'SELECT *'. Selecione apenas as colunas necessárias.";
            }
            
            // Verificar junções sem condições explícitas
            if (preg_match('/\\bJOIN\\b/i', $query) && !preg_match('/\\bON\\b/i', $query)) {
                $suggestions[] = "Verifique suas junções. Use sempre condições explícitas com 'ON'.";
            }
            
            // Verificar uso de GROUP BY sem índices
            if (preg_match('/\\bGROUP\\s+BY\\b/i', $query)) {
                $suggestions[] = "Certifique-se de que as colunas em GROUP BY estão indexadas.";
            }
            
            // Verificar ORDER BY em consultas com LIMIT
            if (preg_match('/\\bORDER\\s+BY\\b/i', $query) && preg_match('/\\bLIMIT\\b/i', $query)) {
                $suggestions[] = "Certifique-se de que as colunas em ORDER BY estão indexadas para consultas paginadas.";
            }
            
            // Verificar condições WHERE usando funções
            if (preg_match('/\\bWHERE\\b.*?\\b\\w+\\([^)]*\\)/i', $query)) {
                $suggestions[] = "Evite usar funções nas condições WHERE, pois isso impede o uso de índices.";
            }
            
            // Verificar subconsultas
            if (preg_match('/\\(\\s*SELECT/i', $query)) {
                $suggestions[] = "Considere substituir subconsultas por JOINs quando possível.";
            }
            
            // Verificar LIKE com curingas no início
            if (preg_match('/\\bLIKE\\s+([\'"])%/i', $query)) {
                $suggestions[] = "Evite usar LIKE com curingas no início da string (ex: '%palavra'), pois isso impede o uso de índices.";
            }
        } elseif ($queryType === 'INSERT' || $queryType === 'UPDATE') {
            // Verificar múltiplos inserts ou updates
            if ($queryType === 'INSERT' && !preg_match('/\\bVALUES\\s*\\(.*\\),\\s*\\(/i', $query)) {
                $suggestions[] = "Para múltiplos registros, use INSERT com múltiplos VALUES para melhor performance.";
            }
            
            // Verificar condições em updates
            if ($queryType === 'UPDATE' && !preg_match('/\\bWHERE\\b/i', $query)) {
                $suggestions[] = "Adicione uma condição WHERE específica em seus UPDATEs para evitar atualizar todos os registros.";
            }
        }
        
        // Sugestões gerais
        if (strlen($query) > 500) {
            $suggestions[] = "Esta consulta é muito complexa. Considere simplificá-la ou dividi-la em partes menores.";
        }
        
        return $suggestions;
    }
    
    /**
     * Verifica a eficiência dos índices para uma consulta
     * 
     * @param string $query Consulta SQL
     * @param string $table Nome da tabela principal
     * @return array Informações sobre o uso de índices
     */
    public function analyzeIndexUsage($query, $table) {
        // Executar EXPLAIN para ver como a consulta está sendo executada
        $explainQuery = "EXPLAIN " . $query;
        
        try {
            $result = $this->db->select($explainQuery);
            
            $indexInfo = [];
            $usesIndex = false;
            $possibleIndexes = [];
            
            // Percorrer resultados do EXPLAIN
            foreach ($result as $row) {
                $indexInfo[] = $row;
                
                // Verificar se está usando algum índice
                if (isset($row['key']) && $row['key']) {
                    $usesIndex = true;
                }
                
                // Identificar possíveis índices que poderiam ser usados
                if (isset($row['possible_keys'])) {
                    $possibleIndexes = array_merge(
                        $possibleIndexes, 
                        explode(',', $row['possible_keys'])
                    );
                }
            }
            
            // Obter os índices existentes na tabela
            $tableIndices = $this->getTableIndices($table);
            
            // Encontrar colunas na cláusula WHERE que poderiam ser indexadas
            preg_match('/\\bWHERE\\b\\s+(.+?)(?:\\bGROUP BY\\b|\\bORDER BY\\b|\\bLIMIT\\b|$)/is', $query, $matches);
            $whereClause = isset($matches[1]) ? $matches[1] : '';
            
            // Extrair colunas da cláusula WHERE
            preg_match_all('/\\b(\\w+)\\b\\s*(?:=|>|<|>=|<=|<>|!=|LIKE|IN)/', $whereClause, $matches);
            $whereColumns = $matches[1] ?? [];
            
            // Encontrar colunas em ORDER BY
            preg_match('/\\bORDER BY\\b\\s+(.+?)(?:\\bLIMIT\\b|$)/is', $query, $matches);
            $orderByClause = isset($matches[1]) ? $matches[1] : '';
            
            // Extrair colunas da cláusula ORDER BY
            preg_match_all('/\\b(\\w+)\\b/', $orderByClause, $matches);
            $orderByColumns = $matches[1] ?? [];
            
            // Colunas que poderiam se beneficiar de índices
            $candidateColumns = array_unique(array_merge($whereColumns, $orderByColumns));
            
            // Colunas candidatas que não têm índices
            $nonIndexedCandidates = array_diff($candidateColumns, array_keys($tableIndices));
            
            return [
                'explain_result' => $indexInfo,
                'uses_index' => $usesIndex,
                'possible_indexes' => array_unique($possibleIndexes),
                'existing_indexes' => $tableIndices,
                'where_columns' => $whereColumns,
                'order_by_columns' => $orderByColumns,
                'candidate_columns' => $candidateColumns,
                'non_indexed_candidates' => $nonIndexedCandidates,
                'recommendations' => $this->generateIndexRecommendations($nonIndexedCandidates, $whereColumns, $orderByColumns, $table)
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'query' => $explainQuery
            ];
        }
    }
    
    /**
     * Obtém os índices existentes em uma tabela
     * 
     * @param string $table Nome da tabela
     * @return array Índices da tabela
     */
    public function getTableIndices($table) {
        $query = "SHOW INDEX FROM " . $table;
        
        try {
            $result = $this->db->select($query);
            
            $indices = [];
            foreach ($result as $row) {
                $column = $row['Column_name'];
                $indexName = $row['Key_name'];
                
                if (!isset($indices[$column])) {
                    $indices[$column] = [];
                }
                
                $indices[$column][] = $indexName;
            }
            
            return $indices;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Gera recomendações para criação de índices
     * 
     * @param array $nonIndexedCandidates Colunas candidatas sem índices
     * @param array $whereColumns Colunas na cláusula WHERE
     * @param array $orderByColumns Colunas na cláusula ORDER BY
     * @param string $table Nome da tabela
     * @return array Recomendações de índices
     */
    private function generateIndexRecommendations($nonIndexedCandidates, $whereColumns, $orderByColumns, $table) {
        $recommendations = [];
        
        if (empty($nonIndexedCandidates)) {
            return ["Todas as colunas relevantes já possuem índices."];
        }
        
        // Colunas na cláusula WHERE que não têm índices
        $whereWithoutIndex = array_intersect($nonIndexedCandidates, $whereColumns);
        if (!empty($whereWithoutIndex)) {
            foreach ($whereWithoutIndex as $column) {
                $recommendations[] = [
                    'description' => "Criar índice para a coluna '$column' usada em condições WHERE",
                    'priority' => 'alta',
                    'sql' => "ALTER TABLE `$table` ADD INDEX `idx_{$table}_{$column}` (`$column`);"
                ];
            }
        }
        
        // Colunas em ORDER BY que não têm índices
        $orderByWithoutIndex = array_intersect($nonIndexedCandidates, $orderByColumns);
        if (!empty($orderByWithoutIndex)) {
            foreach ($orderByWithoutIndex as $column) {
                if (!in_array($column, $whereWithoutIndex)) {
                    $recommendations[] = [
                        'description' => "Criar índice para a coluna '$column' usada em ORDER BY",
                        'priority' => 'média',
                        'sql' => "ALTER TABLE `$table` ADD INDEX `idx_{$table}_{$column}` (`$column`);"
                    ];
                }
            }
        }
        
        // Se temos múltiplas colunas em WHERE, considerar um índice composto
        if (count($whereWithoutIndex) > 1) {
            $columnList = implode("`, `", $whereWithoutIndex);
            $indexName = "idx_{$table}_" . implode("_", $whereWithoutIndex);
            
            $recommendations[] = [
                'description' => "Considerar um índice composto para as colunas usadas juntas em WHERE",
                'priority' => 'média',
                'sql' => "ALTER TABLE `$table` ADD INDEX `$indexName` (`$columnList`);"
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Analisa todas as consultas no ProductModel e sugere otimizações
     * 
     * @return array Sugestões de otimização para ProductModel
     */
    public function analyzeProductModel() {
        $modelFile = APP_PATH . '/models/ProductModel.php';
        
        if (!file_exists($modelFile)) {
            return [
                'error' => 'Arquivo do modelo não encontrado',
                'file' => $modelFile
            ];
        }
        
        $content = file_get_contents($modelFile);
        
        // Extrair todas as consultas SQL
        preg_match_all('/\\$sql\\s*=\\s*"([^"]+)"/s', $content, $matches);
        $queries = $matches[1] ?? [];
        
        // Adicionar consultas com aspas simples
        preg_match_all('/\\$sql\\s*=\\s*\'([^\']+)\'/s', $content, $matches);
        $queries = array_merge($queries, $matches[1] ?? []);
        
        $analysis = [];
        
        foreach ($queries as $index => $query) {
            // Extrair o método onde a consulta está sendo usada
            preg_match('/public\\s+function\\s+(\\w+)[^{]*{[^}]*\\$sql\\s*=\\s*["\']' . preg_quote(substr($query, 0, 50), '/') . '/s', $content, $methodMatches);
            $method = isset($methodMatches[1]) ? $methodMatches[1] : "unknown_method_$index";
            
            // Analisar a consulta
            $suggestions = $this->suggestOptimizations($query);
            
            // Extrair a tabela principal da consulta
            preg_match('/FROM\\s+(\\w+)/i', $query, $tableMatches);
            $table = isset($tableMatches[1]) ? str_replace(['{', '}', '$this->table'], 'products', $tableMatches[1]) : '';
            
            $indexAnalysis = $table ? $this->analyzeIndexUsage($query, $table) : null;
            
            $analysis[$method] = [
                'query' => $query,
                'optimization_suggestions' => $suggestions,
                'index_analysis' => $indexAnalysis
            ];
        }
        
        return [
            'file' => $modelFile,
            'query_count' => count($queries),
            'analysis' => $analysis
        ];
    }
    
    /**
     * Gera um relatório HTML com análise de consultas SQL
     * 
     * @param array $analysis Resultado da análise
     * @return string HTML do relatório
     */
    public function generateReportHtml($analysis) {
        $html = '<div class="sql-optimization-report">';
        $html .= '<h2>Relatório de Otimização de Consultas SQL</h2>';
        
        if (isset($analysis['file'])) {
            $html .= '<p><strong>Arquivo analisado:</strong> ' . htmlspecialchars($analysis['file']) . '</p>';
            $html .= '<p><strong>Número de consultas:</strong> ' . htmlspecialchars($analysis['query_count']) . '</p>';
            
            foreach ($analysis['analysis'] as $method => $info) {
                $html .= '<div class="query-analysis">';
                $html .= '<h3>Método: ' . htmlspecialchars($method) . '</h3>';
                
                $html .= '<div class="query"><pre>' . htmlspecialchars($info['query']) . '</pre></div>';
                
                $html .= '<h4>Sugestões de Otimização:</h4>';
                $html .= '<ul class="suggestions">';
                if (!empty($info['optimization_suggestions'])) {
                    foreach ($info['optimization_suggestions'] as $suggestion) {
                        $html .= '<li>' . htmlspecialchars($suggestion) . '</li>';
                    }
                } else {
                    $html .= '<li>Nenhuma sugestão de otimização.</li>';
                }
                $html .= '</ul>';
                
                if (!empty($info['index_analysis'])) {
                    $html .= '<h4>Análise de Índices:</h4>';
                    
                    $html .= '<p><strong>Usa índice:</strong> ' 
                          . ($info['index_analysis']['uses_index'] ? 'Sim' : 'Não') . '</p>';
                    
                    if (!empty($info['index_analysis']['recommendations'])) {
                        $html .= '<h5>Recomendações de Índices:</h5>';
                        $html .= '<ul class="index-recommendations">';
                        foreach ($info['index_analysis']['recommendations'] as $rec) {
                            if (is_array($rec)) {
                                $html .= '<li>';
                                $html .= '<strong>' . htmlspecialchars($rec['description']) . '</strong>';
                                $html .= ' <span class="priority">Prioridade: ' . htmlspecialchars($rec['priority']) . '</span>';
                                $html .= '<pre>' . htmlspecialchars($rec['sql']) . '</pre>';
                                $html .= '</li>';
                            } else {
                                $html .= '<li>' . htmlspecialchars($rec) . '</li>';
                            }
                        }
                        $html .= '</ul>';
                    }
                }
                
                $html .= '</div>';
            }
        } else {
            $html .= '<p>Nenhuma análise de arquivo disponível.</p>';
            
            if (isset($analysis['date'])) {
                // Relatório de consultas lentas
                $html .= '<h3>Relatório de Consultas Lentas - ' . htmlspecialchars($analysis['date']) . '</h3>';
                $html .= '<p><strong>Total de consultas lentas:</strong> ' . htmlspecialchars($analysis['total_count']) . '</p>';
                $html .= '<p><strong>Tempo médio de execução:</strong> ' 
                      . round(htmlspecialchars($analysis['average_time']) * 1000, 2) . ' ms</p>';
                $html .= '<p><strong>Tempo máximo de execução:</strong> ' 
                      . round(htmlspecialchars($analysis['max_time']) * 1000, 2) . ' ms</p>';
                
                if (!empty($analysis['common_patterns'])) {
                    $html .= '<h4>Padrões Comuns de Consultas Lentas:</h4>';
                    
                    foreach ($analysis['common_patterns'] as $pattern) {
                        $html .= '<div class="query-pattern">';
                        $html .= '<h5>Padrão: ' . htmlspecialchars($pattern['type']) . '</h5>';
                        $html .= '<p><strong>Ocorrências:</strong> ' . htmlspecialchars($pattern['count']) . '</p>';
                        $html .= '<p><strong>Tempo total:</strong> ' 
                              . round(htmlspecialchars($pattern['total_time']) * 1000, 2) . ' ms</p>';
                        $html .= '<p><strong>Tempo médio:</strong> ' 
                              . round(htmlspecialchars($pattern['average_time']) * 1000, 2) . ' ms</p>';
                        $html .= '<p><strong>Origem:</strong> ' . htmlspecialchars($pattern['file_origin']) . '</p>';
                        
                        $html .= '<div class="query-example"><pre>' 
                              . htmlspecialchars($pattern['slowest_example']) . '</pre></div>';
                        
                        $html .= '<h6>Sugestões de Otimização:</h6>';
                        $html .= '<ul class="suggestions">';
                        if (!empty($pattern['optimization_suggestions'])) {
                            foreach ($pattern['optimization_suggestions'] as $suggestion) {
                                $html .= '<li>' . htmlspecialchars($suggestion) . '</li>';
                            }
                        } else {
                            $html .= '<li>Nenhuma sugestão de otimização.</li>';
                        }
                        $html .= '</ul>';
                        
                        $html .= '</div>';
                    }
                }
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}