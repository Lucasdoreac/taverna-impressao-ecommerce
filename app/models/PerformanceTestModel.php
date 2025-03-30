<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Modelo para Testes de Performance
 * Responsável por gerenciar os dados de testes de performance,
 * incluindo armazenamento, recuperação e análise de resultados
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceTestModel extends Model {
    protected $table = 'performance_tests';
    protected $primaryKey = 'id';
    protected $fillable = [
        'test_type', 'params', 'results', 'timestamp', 'user_id'
    ];
    
    /**
     * Construtor
     * Inicializa o modelo e verifica se as tabelas necessárias existem
     */
    public function __construct() {
        parent::__construct();
        
        // Verificar se as tabelas necessárias existem
        $this->checkAndCreateTables();
    }
    
    /**
     * Retorna todos os testes de performance
     * 
     * @param int $limit Limite de resultados a retornar
     * @param int $offset Deslocamento para paginação
     * @param string $orderBy Campo para ordenação
     * @param string $orderDir Direção da ordenação (ASC ou DESC)
     * @return array Lista de testes
     */
    public function getTests($limit = 50, $offset = 0, $orderBy = 'timestamp', $orderDir = 'DESC') {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT :offset, :limit";
            
            return $this->db()->select($sql, [
                'offset' => $offset,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            error_log("Erro ao obter testes de performance: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Retorna um teste específico pelo ID
     * 
     * @param int $id ID do teste
     * @return array|null Dados do teste ou null se não encontrado
     */
    public function getTestById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $result = $this->db()->select($sql, ['id' => $id]);
            
            if (empty($result)) {
                return null;
            }
            
            $test = $result[0];
            
            // Decodificar campos JSON
            $test['params'] = json_decode($test['params'], true);
            $test['results'] = json_decode($test['results'], true);
            
            return $test;
        } catch (Exception $e) {
            error_log("Erro ao obter teste por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Salva os resultados de um teste de performance
     * 
     * @param string $type Tipo de teste
     * @param array $params Parâmetros do teste
     * @param array $results Resultados do teste
     * @param int $userId ID do usuário que executou o teste (opcional)
     * @return int|bool ID do teste salvo ou false em caso de erro
     */
    public function saveTestResults($type, $params, $results, $userId = null) {
        try {
            $data = [
                'test_type' => $type,
                'params' => json_encode($params),
                'results' => json_encode($results),
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $userId
            ];
            
            return $this->create($data);
        } catch (Exception $e) {
            error_log("Erro ao salvar resultados de teste: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Salva métricas coletadas no cliente
     * 
     * @param string $pageUrl URL da página
     * @param array $metrics Métricas coletadas
     * @param string $userAgent User agent do cliente
     * @param string $timestamp Data e hora da coleta
     * @return bool True se salvo com sucesso, false caso contrário
     */
    public function saveClientMetrics($pageUrl, $metrics, $userAgent, $timestamp) {
        try {
            $sql = "INSERT INTO performance_metrics 
                    (page_url, metrics, user_agent, timestamp) 
                    VALUES (:page_url, :metrics, :user_agent, :timestamp)";
            
            $params = [
                'page_url' => $pageUrl,
                'metrics' => json_encode($metrics),
                'user_agent' => $userAgent,
                'timestamp' => $timestamp
            ];
            
            $this->db()->execute($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao salvar métricas do cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém um resumo de performance geral do site
     * 
     * @return array Resumo de métricas de performance
     */
    public function getPerformanceSummary() {
        try {
            $summary = [
                'page_load' => $this->getAveragePageLoadTime(),
                'api_response' => $this->getAverageApiResponseTime(),
                'db_query' => $this->getAverageDatabaseQueryTime(),
                'recent_tests' => $this->getRecentTests(5),
                'worst_performing_pages' => $this->getWorstPerformingPages(5),
                'best_performing_pages' => $this->getBestPerformingPages(5)
            ];
            
            return $summary;
        } catch (Exception $e) {
            error_log("Erro ao obter resumo de performance: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém a média de tempo de carregamento de página
     * 
     * @param int $days Número de dias a considerar
     * @return float Tempo médio de carregamento em ms
     */
    public function getAveragePageLoadTime($days = 30) {
        try {
            $sql = "SELECT AVG(CAST(JSON_EXTRACT(results, '$.summary.avg_load_time') AS DECIMAL(10,2))) as avg_time
                    FROM {$this->table}
                    WHERE test_type = 'page_load'
                    AND timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $result = $this->db()->select($sql, ['days' => $days]);
            
            if (empty($result) || $result[0]['avg_time'] === null) {
                return 0;
            }
            
            return round($result[0]['avg_time'], 2);
        } catch (Exception $e) {
            error_log("Erro ao obter tempo médio de carregamento: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém a média de tempo de resposta de API
     * 
     * @param int $days Número de dias a considerar
     * @return float Tempo médio de resposta em ms
     */
    public function getAverageApiResponseTime($days = 30) {
        try {
            $sql = "SELECT AVG(CAST(JSON_EXTRACT(results, '$.summary.avg_response_time') AS DECIMAL(10,2))) as avg_time
                    FROM {$this->table}
                    WHERE test_type = 'api_response'
                    AND timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $result = $this->db()->select($sql, ['days' => $days]);
            
            if (empty($result) || $result[0]['avg_time'] === null) {
                return 0;
            }
            
            return round($result[0]['avg_time'], 2);
        } catch (Exception $e) {
            error_log("Erro ao obter tempo médio de resposta de API: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém a média de tempo de consultas ao banco de dados
     * 
     * @param int $days Número de dias a considerar
     * @return float Tempo médio de consulta em ms
     */
    public function getAverageDatabaseQueryTime($days = 30) {
        try {
            $sql = "SELECT AVG(CAST(JSON_EXTRACT(results, '$.summary.avg_query_time') AS DECIMAL(10,2))) as avg_time
                    FROM {$this->table}
                    WHERE test_type = 'db_query'
                    AND timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $result = $this->db()->select($sql, ['days' => $days]);
            
            if (empty($result) || $result[0]['avg_time'] === null) {
                return 0;
            }
            
            return round($result[0]['avg_time'], 2);
        } catch (Exception $e) {
            error_log("Erro ao obter tempo médio de consulta: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém os testes mais recentes
     * 
     * @param int $limit Número de testes a retornar
     * @return array Lista de testes recentes
     */
    public function getRecentTests($limit = 5) {
        try {
            $sql = "SELECT id, test_type, timestamp, 
                          JSON_EXTRACT(results, '$.summary.performance_rating') as rating
                    FROM {$this->table}
                    ORDER BY timestamp DESC
                    LIMIT :limit";
            
            $tests = $this->db()->select($sql, ['limit' => $limit]);
            
            // Processar resultados
            foreach ($tests as &$test) {
                // Remover aspas duplas dos valores JSON extraídos
                if (isset($test['rating'])) {
                    $test['rating'] = str_replace('"', '', $test['rating']);
                }
            }
            
            return $tests;
        } catch (Exception $e) {
            error_log("Erro ao obter testes recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as páginas com pior desempenho
     * 
     * @param int $limit Número de páginas a retornar
     * @return array Lista de páginas com pior desempenho
     */
    public function getWorstPerformingPages($limit = 5) {
        try {
            $sql = "SELECT 
                        JSON_EXTRACT(params, '$.url') as url,
                        AVG(CAST(JSON_EXTRACT(results, '$.summary.avg_load_time') AS DECIMAL(10,2))) as avg_time
                    FROM {$this->table}
                    WHERE test_type = 'page_load'
                    GROUP BY JSON_EXTRACT(params, '$.url')
                    ORDER BY avg_time DESC
                    LIMIT :limit";
            
            $pages = $this->db()->select($sql, ['limit' => $limit]);
            
            // Processar resultados
            foreach ($pages as &$page) {
                // Remover aspas duplas dos valores JSON extraídos
                $page['url'] = str_replace('"', '', $page['url']);
                $page['avg_time'] = round($page['avg_time'], 2);
            }
            
            return $pages;
        } catch (Exception $e) {
            error_log("Erro ao obter páginas com pior desempenho: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as páginas com melhor desempenho
     * 
     * @param int $limit Número de páginas a retornar
     * @return array Lista de páginas com melhor desempenho
     */
    public function getBestPerformingPages($limit = 5) {
        try {
            $sql = "SELECT 
                        JSON_EXTRACT(params, '$.url') as url,
                        AVG(CAST(JSON_EXTRACT(results, '$.summary.avg_load_time') AS DECIMAL(10,2))) as avg_time
                    FROM {$this->table}
                    WHERE test_type = 'page_load'
                    GROUP BY JSON_EXTRACT(params, '$.url')
                    ORDER BY avg_time ASC
                    LIMIT :limit";
            
            $pages = $this->db()->select($sql, ['limit' => $limit]);
            
            // Processar resultados
            foreach ($pages as &$page) {
                // Remover aspas duplas dos valores JSON extraídos
                $page['url'] = str_replace('"', '', $page['url']);
                $page['avg_time'] = round($page['avg_time'], 2);
            }
            
            return $pages;
        } catch (Exception $e) {
            error_log("Erro ao obter páginas com melhor desempenho: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as configurações de testes de performance
     * 
     * @return array Configurações atuais
     */
    public function getSettings() {
        try {
            $sql = "SELECT * FROM performance_settings LIMIT 1";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Retornar configurações padrão
                return [
                    'automatic_tests' => false,
                    'test_interval' => 24,
                    'pages_to_test' => ['home', 'products', 'product_detail'],
                    'notification_email' => '',
                    'performance_threshold' => 1000
                ];
            }
            
            $settings = $result[0];
            
            // Decodificar campo JSON
            $settings['pages_to_test'] = json_decode($settings['pages_to_test'], true);
            
            return $settings;
        } catch (Exception $e) {
            error_log("Erro ao obter configurações: " . $e->getMessage());
            
            // Retornar configurações padrão em caso de erro
            return [
                'automatic_tests' => false,
                'test_interval' => 24,
                'pages_to_test' => ['home', 'products', 'product_detail'],
                'notification_email' => '',
                'performance_threshold' => 1000
            ];
        }
    }
    
    /**
     * Salva as configurações de testes de performance
     * 
     * @param array $settings Configurações a serem salvas
     * @return bool True se salvo com sucesso, false caso contrário
     */
    public function saveSettings($settings) {
        try {
            // Codificar array para JSON
            if (isset($settings['pages_to_test']) && is_array($settings['pages_to_test'])) {
                $settings['pages_to_test'] = json_encode($settings['pages_to_test']);
            }
            
            // Verificar se já existem configurações
            $sql = "SELECT COUNT(*) as count FROM performance_settings";
            $result = $this->db()->select($sql);
            $count = $result[0]['count'];
            
            if ($count > 0) {
                // Atualizar configurações existentes
                $sql = "UPDATE performance_settings SET 
                            automatic_tests = :automatic_tests,
                            test_interval = :test_interval,
                            pages_to_test = :pages_to_test,
                            notification_email = :notification_email,
                            performance_threshold = :performance_threshold,
                            updated_at = NOW()";
                            
                $this->db()->execute($sql, $settings);
            } else {
                // Inserir novas configurações
                $sql = "INSERT INTO performance_settings 
                        (automatic_tests, test_interval, pages_to_test, notification_email, performance_threshold, created_at, updated_at) 
                        VALUES 
                        (:automatic_tests, :test_interval, :pages_to_test, :notification_email, :performance_threshold, NOW(), NOW())";
                        
                $this->db()->execute($sql, $settings);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao salvar configurações: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executa uma consulta de teste no banco de dados
     * 
     * @param string $query Consulta SQL a ser testada
     * @return array|bool Resultados da consulta ou false em caso de erro
     */
    public function testDatabaseQuery($query) {
        try {
            // Executar a consulta diretamente
            return $this->db()->select($query);
        } catch (Exception $e) {
            error_log("Erro ao executar consulta de teste: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todos os produtos para testes
     * 
     * @return array Lista de produtos
     */
    public function getAllProducts() {
        try {
            $sql = "SELECT * FROM products WHERE is_active = 1";
            return $this->db()->select($sql);
        } catch (Exception $e) {
            error_log("Erro ao obter todos os produtos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém produtos recentes para testes
     * 
     * @param int $limit Número de produtos a retornar
     * @return array Lista de produtos
     */
    public function getRecentProducts($limit = 50) {
        try {
            $sql = "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT :limit";
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao obter produtos recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as categorias com seus produtos para testes
     * 
     * @return array Lista de categorias com produtos
     */
    public function getAllCategoriesWithProducts() {
        try {
            // Obter categorias
            $sql = "SELECT * FROM categories ORDER BY lft";
            $categories = $this->db()->select($sql);
            
            // Para cada categoria, obter seus produtos
            foreach ($categories as &$category) {
                $sql = "SELECT * FROM products WHERE category_id = :category_id AND is_active = 1 LIMIT 10";
                $category['products'] = $this->db()->select($sql, ['category_id' => $category['id']]);
            }
            
            return $categories;
        } catch (Exception $e) {
            error_log("Erro ao obter categorias com produtos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todos os pedidos para testes
     * 
     * @return array Lista de pedidos
     */
    public function getAllOrders() {
        try {
            $sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 100";
            $orders = $this->db()->select($sql);
            
            // Para cada pedido, obter seus itens
            foreach ($orders as &$order) {
                $sql = "SELECT oi.*, p.name as product_name 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = :order_id";
                $order['items'] = $this->db()->select($sql, ['order_id' => $order['id']]);
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Erro ao obter todos os pedidos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se as tabelas necessárias existem e as cria se não existirem
     */
    private function checkAndCreateTables() {
        try {
            // Verificar se a tabela de testes existe
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de testes
                $sql = "CREATE TABLE {$this->table} (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          test_type VARCHAR(50) NOT NULL,
                          params TEXT,
                          results TEXT,
                          timestamp DATETIME,
                          user_id INT,
                          INDEX (test_type),
                          INDEX (timestamp)
                        )";
                $this->db()->execute($sql);
                
                // Registrar no log
                error_log("Tabela {$this->table} criada com sucesso.");
            }
            
            // Verificar se a tabela de métricas existe
            $sql = "SHOW TABLES LIKE 'performance_metrics'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de métricas
                $sql = "CREATE TABLE performance_metrics (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          page_url VARCHAR(255) NOT NULL,
                          metrics TEXT,
                          user_agent VARCHAR(255),
                          timestamp DATETIME,
                          INDEX (page_url),
                          INDEX (timestamp)
                        )";
                $this->db()->execute($sql);
                
                // Registrar no log
                error_log("Tabela performance_metrics criada com sucesso.");
            }
            
            // Verificar se a tabela de configurações existe
            $sql = "SHOW TABLES LIKE 'performance_settings'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de configurações
                $sql = "CREATE TABLE performance_settings (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          automatic_tests BOOLEAN DEFAULT FALSE,
                          test_interval INT DEFAULT 24,
                          pages_to_test TEXT,
                          notification_email VARCHAR(255),
                          performance_threshold INT DEFAULT 1000,
                          created_at DATETIME,
                          updated_at DATETIME
                        )";
                $this->db()->execute($sql);
                
                // Inserir configurações padrão
                $defaultSettings = [
                    'automatic_tests' => false,
                    'test_interval' => 24,
                    'pages_to_test' => json_encode(['home', 'products', 'product_detail']),
                    'notification_email' => '',
                    'performance_threshold' => 1000
                ];
                
                $sql = "INSERT INTO performance_settings 
                        (automatic_tests, test_interval, pages_to_test, notification_email, performance_threshold, created_at, updated_at) 
                        VALUES 
                        (:automatic_tests, :test_interval, :pages_to_test, :notification_email, :performance_threshold, NOW(), NOW())";
                        
                $this->db()->execute($sql, $defaultSettings);
                
                // Registrar no log
                error_log("Tabela performance_settings criada com sucesso e configurações padrão inseridas.");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar tabelas: " . $e->getMessage());
            return false;
        }
    }
}
?>