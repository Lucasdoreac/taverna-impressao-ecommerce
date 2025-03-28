<?php
/**
 * Router - Sistema de roteamento básico
 */
class Router {
    private $routes = [];
    
    public function __construct() {
        global $routes;
        $this->routes = $routes;
    }
    
    public function dispatch() {
        $uri = $this->getUri();
        
        foreach ($this->routes as $route => $handler) {
            if ($this->matchRoute($route, $uri, $params)) {
                list($controller, $method) = $handler;
                $this->callAction($controller, $method, $params);
                return;
            }
        }
        
        // Rota não encontrada
        $this->notFound();
    }
    
    private function getUri() {
        $uri = $_SERVER['REQUEST_URI'];
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Remover diretório base da URI
        if ($baseDir !== '/') {
            $uri = substr($uri, strlen($baseDir));
        }
        
        // Remover query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return '/' . trim($uri, '/');
    }
    
    private function matchRoute($route, $uri, &$params) {
        // Converter parâmetros da rota em regex
        $pattern = preg_replace('/:[a-zA-Z0-9]+/', '([^/]+)', $route);
        $pattern = "#^{$pattern}$#";
        
        if (preg_match($pattern, $uri, $matches)) {
            // Extrair parâmetros
            $params = [];
            $paramNames = [];
            preg_match_all('/:([a-zA-Z0-9]+)/', $route, $paramNames);
            
            for ($i = 0; $i < count($paramNames[1]); $i++) {
                $params[$paramNames[1][$i]] = $matches[$i + 1];
            }
            
            return true;
        }
        
        return false;
    }
    
    private function callAction($controller, $method, $params) {
        if (class_exists($controller)) {
            $controllerObj = new $controller();
            
            if (method_exists($controllerObj, $method)) {
                call_user_func_array([$controllerObj, $method], $params);
                return;
            }
        }
        
        // Método não encontrado
        $this->notFound();
    }
    
    private function notFound() {
        header("HTTP/1.0 404 Not Found");
        include VIEWS_PATH . '/errors/404.php';
        exit;
    }
}