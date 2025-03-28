<?php
/**
 * Database - Classe para operações com banco de dados
 */
class Database {
    private static $instance = null;
    private $connection;
    private $queriesLog = [];
    private $debug = false;
    
    private function __construct() {
        try {
            // Configuração específica para ambiente de produção
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            
            // Se DB_OPTIONS está definido, usar essas opções
            $options = defined('DB_OPTIONS') ? DB_OPTIONS : [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Verificar se a conexão está em UTF-8
            $this->query("SET NAMES utf8mb4");
            
            // Log da conexão bem-sucedida
            if (function_exists('app_log')) {
                app_log("Conexão com o banco de dados estabelecida com sucesso", "info");
            } else {
                error_log("Conexão com o banco de dados estabelecida com sucesso");
            }
            
            // Ativar debug em desenvolvimento
            if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                $this->debug = true;
            }
        } catch (PDOException $e) {
            // Log do erro detalhado
            $this->logError("Erro de conexão com o banco de dados", $e);
            
            // Em produção, exibir mensagem genérica
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                die("Erro de conexão com o banco de dados. Por favor, contate o administrador.");
            } else {
                die("Erro de conexão: " . $e->getMessage());
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        $stmt = null;
        
        try {
            $stmt = $this->connection->prepare($sql);
            
            // Verificar se a preparação foi bem-sucedida
            if ($stmt === false) {
                throw new PDOException("Falha ao preparar a query: " . $sql);
            }
            
            // Verificar e corrigir parâmetros
            $paramsToUse = $this->validateAndAdjustParams($stmt, $params);
            
            $success = $stmt->execute($paramsToUse);
            
            // Verificar se a execução foi bem-sucedida
            if ($success === false) {
                throw new PDOException("Falha ao executar a query: " . $sql);
            }
            
            if ($this->debug) {
                $this->logQuery($sql, $params, microtime(true) - $startTime);
            }
            
            return $stmt;
        } catch (PDOException $e) {
            $this->logError("Erro na query: {$sql}", $e, $params);
            
            // Em desenvolvimento, propagar o erro para tratamento na camada superior
            if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                throw $e;
            } else {
                // Em produção, retornar um resultado vazio ou null, dependendo do tipo esperado
                if (stripos($sql, 'SELECT') === 0) {
                    return new PDOStatement(); // Statement vazio
                } elseif (stripos($sql, 'INSERT') === 0) {
                    return 0; // ID 0
                } else {
                    return false; // Falha
                }
            }
        }
    }
    
    public function select($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Erro no select: {$sql}", $e, $params);
            return [];
        }
    }
    
    public function insert($table, $data) {
        try {
            // Verificar se há dados para inserir
            if (empty($data)) {
                $this->logError("Tentativa de inserir dados vazios na tabela {$table}", new Exception("Dados vazios"));
                return 0;
            }
            
            $columns = array_keys($data);
            $placeholders = array_map(function($item) {
                return ":{$item}";
            }, $columns);
            
            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $this->query($sql, $data);
            return $this->connection->lastInsertId();
        } catch (Exception $e) {
            $this->logError("Erro no insert na tabela {$table}", $e, $data);
            return 0;
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        try {
            // Verificar se há dados para atualizar
            if (empty($data)) {
                $this->logError("Tentativa de atualizar com dados vazios na tabela {$table}", new Exception("Dados vazios"));
                return false;
            }
            
            $setClauses = array_map(function($column) {
                return "{$column} = :{$column}";
            }, array_keys($data));
            
            $sql = "UPDATE {$table} SET " . implode(', ', $setClauses) . " WHERE {$where}";
            
            $params = array_merge($data, $whereParams);
            $this->query($sql, $params);
            return true;
        } catch (Exception $e) {
            $this->logError("Erro no update na tabela {$table}", $e, array_merge($data, $whereParams));
            return false;
        }
    }
    
    public function delete($table, $where, $params = []) {
        try {
            if (empty($where)) {
                $this->logError("Tentativa de delete sem condição WHERE na tabela {$table}", new Exception("WHERE vazio"));
                return false;
            }
            
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $this->query($sql, $params);
            return true;
        } catch (Exception $e) {
            $this->logError("Erro no delete na tabela {$table}", $e, $params);
            return false;
        }
    }
    
    // Método para buscar um único registro
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : null;
        } catch (Exception $e) {
            $this->logError("Erro no fetchOne: {$sql}", $e, $params);
            return null;
        }
    }
    
    // Método para debug em desenvolvimento
    public function setDebug($enable = true) {
        $this->debug = $enable;
        
        if ($enable) {
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }
    }
    
    // Obter log de queries (apenas para debug)
    public function getQueriesLog() {
        return $this->queriesLog;
    }
    
    // Métodos privados para logging e validação
    
    private function logQuery($sql, $params, $executionTime) {
        $this->queriesLog[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if (count($this->queriesLog) > 100) {
            array_shift($this->queriesLog); // Evitar crescimento excessivo
        }
    }
    
    private function logError($message, $exception, $params = []) {
        $errorMessage = $message . ': ' . $exception->getMessage();
        
        // Adicionar informações de parâmetros se disponíveis
        if (!empty($params)) {
            $errorMessage .= ' | Parâmetros: ' . json_encode($params);
        }
        
        // Usar função app_log se disponível
        if (function_exists('app_log')) {
            app_log($errorMessage, 'error');
            app_log('Stack trace: ' . $exception->getTraceAsString(), 'error');
        } else {
            error_log($errorMessage);
            error_log('Stack trace: ' . $exception->getTraceAsString());
        }
    }
    
    private function validateAndAdjustParams($stmt, $params) {
        // Se parâmetros estiverem vazios, retornar como estão
        if (empty($params)) {
            return $params;
        }
        
        // Verificar se é um array associativo ou sequencial
        $isAssoc = array_keys($params) !== range(0, count($params) - 1);
        
        // Para arrays associativos, garantir que todos os parâmetros tenham : no início
        if ($isAssoc) {
            $adjustedParams = [];
            
            foreach ($params as $key => $value) {
                $paramName = (strpos($key, ':') === 0) ? $key : ':' . $key;
                $adjustedParams[$paramName] = $value;
            }
            
            return $adjustedParams;
        }
        
        return $params;
    }
}