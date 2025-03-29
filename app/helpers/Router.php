<?php
/**
 * Router - Sistema de roteamento básico otimizado para ambiente Hostinger
 */
class Router {
    private $routes = [];
    
    public function __construct() {
        global $routes;
        $this->routes = $routes;
    }
    
    public function dispatch() {
        $uri = $this->getUri();
        
        // Log para depuração
        if (ENVIRONMENT === 'development' || isset($_GET['debug'])) {
            app_log("Roteamento: URI = {$uri}", 'debug');
            app_log("SERVER_NAME: " . $_SERVER['SERVER_NAME'], 'debug');
            app_log("REQUEST_URI: " . $_SERVER['REQUEST_URI'], 'debug');
            app_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'], 'debug');
            app_log("PHP_SELF: " . $_SERVER['PHP_SELF'], 'debug');
            app_log("QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? ''), 'debug');
        }
        
        // Verificar rotas registradas
        foreach ($this->routes as $route => $handler) {
            if ($this->matchRoute($route, $uri, $params)) {
                list($controller, $method) = $handler;
                
                if (ENVIRONMENT === 'development' || isset($_GET['debug'])) {
                    app_log("Rota encontrada: {$route} => {$controller}::{$method}", 'debug');
                    app_log("Parâmetros extraídos: " . json_encode($params), 'debug');
                }
                
                $this->callAction($controller, $method, $params);
                return;
            }
        }
        
        // Rota não encontrada
        app_log("Rota não encontrada: {$uri}", 'warning');
        $this->notFound();
    }
    
    private function getUri() {
        // Modificação para melhor compatibilidade com ambiente Hostinger
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Remover query string se presente
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        
        // Remover diretório base da URI com detecção automática para máxima compatibilidade
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // Se o script não estiver na raiz do servidor, remover o caminho do script
        if ($scriptDir !== '/' && $scriptDir !== '\\') {
            if (strpos($requestUri, $scriptDir) === 0) {
                $requestUri = substr($requestUri, strlen($scriptDir));
            }
        }
        
        // Garantir que a URI comece com /
        $uri = '/' . trim($requestUri, '/');
        
        // Lidar com "/" como uma rota válida
        if ($uri === '//') {
            $uri = '/';
        }
        
        return $uri;
    }
    
    private function matchRoute($route, $uri, &$params) {
        // Converter parâmetros da rota em regex
        $pattern = preg_replace('/:[a-zA-Z0-9]+/', '([^/]+)', $route);
        $pattern = "#^{$pattern}$#";
        
        if (preg_match($pattern, $uri, $matches)) {
            // Extrair parâmetros
            $params = [];
            $paramNames = [];
            
            // CORREÇÃO: Verificar se a rota contém parâmetros antes de tentar extraí-los
            if (preg_match_all('/:([a-zA-Z0-9]+)/', $route, $paramNames)) {
                // Inicializar o array de parâmetros
                for ($i = 0; $i < count($paramNames[1]); $i++) {
                    // Verificar se existe o valor correspondente em $matches
                    if (isset($matches[$i + 1])) {
                        $params[$paramNames[1][$i]] = $matches[$i + 1];
                    } else {
                        // Se não existir, definir como null ou string vazia
                        $params[$paramNames[1][$i]] = '';
                        
                        // Log para depuração
                        if (ENVIRONMENT === 'development' || isset($_GET['debug'])) {
                            app_log("Parâmetro '{$paramNames[1][$i]}' não encontrado na URI", 'warning');
                        }
                    }
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    private function callAction($controller, $method, $params) {
        if (!class_exists($controller)) {
            app_log("Controlador não encontrado: {$controller}", 'error');
            $this->notFound();
            return;
        }
        
        try {
            $controllerObj = new $controller();
            
            if (!method_exists($controllerObj, $method)) {
                app_log("Método não encontrado: {$controller}::{$method}", 'error');
                $this->notFound();
                return;
            }
            
            // CORREÇÃO: Verificar tipo dos parâmetros e reforçar como array associativo
            if (is_array($params)) {
                // Passar os parâmetros como um único array associativo
                app_log("Chamando {$controller}::{$method} com parâmetros: " . json_encode($params), 'debug');
                
                // Chamada do método com um único array
                $controllerObj->$method($params);
            } else {
                // Se não houver parâmetros, chamar sem argumentos
                app_log("Chamando {$controller}::{$method} sem parâmetros", 'debug');
                $controllerObj->$method();
            }
        } catch (Exception $e) {
            app_log("Erro ao chamar controller: " . $e->getMessage(), 'error');
            if (ENVIRONMENT === 'development' || isset($_GET['debug'])) {
                echo "<h1>Erro no Controller</h1>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            } else {
                // Em produção, mostra página de erro genérica
                include VIEWS_PATH . '/errors/500.php';
            }
            exit;
        }
    }
    
    private function notFound() {
        header("HTTP/1.0 404 Not Found");
        include VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
