<?php
/**
 * Database - Classe para operações com banco de dados
 */
class Database {
    private static $instance = null;
    private $connection;
    
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
            
            // Log da conexão bem-sucedida
            if (function_exists('app_log')) {
                app_log("Conexão com o banco de dados estabelecida com sucesso", "info");
            }
        } catch (PDOException $e) {
            // Log do erro
            if (function_exists('app_log')) {
                app_log("Erro de conexão com o banco de dados: " . $e->getMessage(), "error");
            }
            
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
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log do erro
            if (function_exists('app_log')) {
                app_log("Erro na query: {$sql}, Erro: " . $e->getMessage(), "error");
            }
            throw $e;
        }
    }
    
    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_map(function($item) {
            return ":{$item}";
        }, $columns);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setClauses = array_map(function($column) {
            return "{$column} = :{$column}";
        }, array_keys($data));
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setClauses) . " WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $this->query($sql, $params);
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($sql, $params);
    }
    
    // Método para buscar um único registro
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }
    
    // Método para debug em desenvolvimento
    public function debug($enable = true) {
        if ($enable && defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
        }
    }
}
