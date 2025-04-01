<?php
/**
 * Router Core - Classe bridge para compatibilidade
 * 
 * Esta classe é um wrapper para o Router principal que está em app/helpers/Router.php
 * Serve para manter compatibilidade com código existente que possa estar referenciando Router de app/core
 */
class Router {
    private $actualRouter;
    
    /**
     * Construtor
     * 
     * Instancia o Router real e cria referência para ele
     */
    public function __construct() {
        // Verificar se o Router helpers já foi carregado
        if (!class_exists('\\Router') && file_exists(APP_PATH . '/helpers/Router.php')) {
            // Incluir o Router helpers usando caminho absoluto
            require_once APP_PATH . '/helpers/Router.php';
            
            // Renomear a classe original para evitar conflitos
            if (class_exists('\\Router')) {
                class_alias('\\Router', 'HelpersRouter');
            }
        }
        
        // Instanciar o Router helpers
        if (class_exists('HelpersRouter')) {
            $this->actualRouter = new HelpersRouter();
        } else {
            // Implementação mínima para caso o Router original não seja encontrado
            error_log("ALERTA: Router original não encontrado. Usando implementação mínima do bridge.");
            $this->actualRouter = null;
        }
    }
    
    /**
     * Método mágico para encaminhar chamadas de método para o Router real
     */
    public function __call($method, $args) {
        if ($this->actualRouter !== null && method_exists($this->actualRouter, $method)) {
            return call_user_func_array([$this->actualRouter, $method], $args);
        } else {
            error_log("ERRO: Método {$method} não encontrado no Router.");
            return null;
        }
    }
    
    /**
     * Dispatch - Método principal para processamento da rota
     * Implementação mínima para casos onde o Router original não está disponível
     */
    public function dispatch() {
        // Se temos o Router real, usar ele
        if ($this->actualRouter !== null && method_exists($this->actualRouter, 'dispatch')) {
            return $this->actualRouter->dispatch();
        }
        
        // Implementação mínima de fallback
        error_log("ALERTA: Usando implementação mínima de Router::dispatch()");
        
        // Extrair a URI da requisição
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Carregar o controlador padrão
        if ($uri === '/' || $uri === '') {
            // Rota padrão - home
            if (class_exists('HomeController')) {
                $controller = new HomeController();
                if (method_exists($controller, 'index')) {
                    return $controller->index();
                }
            }
        } else {
            // Tentar determinar o controlador e método a partir da URI
            $parts = explode('/', trim($uri, '/'));
            
            // Se a estrutura da URI for padrão (controller/método)
            if (count($parts) >= 1) {
                $controllerName = ucfirst($parts[0]) . 'Controller';
                $methodName = count($parts) >= 2 ? $parts[1] : 'index';
                
                if (class_exists($controllerName)) {
                    $controller = new $controllerName();
                    if (method_exists($controller, $methodName)) {
                        return $controller->$methodName();
                    }
                }
            }
        }
        
        // Página não encontrada
        header("HTTP/1.0 404 Not Found");
        if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . '/errors/404.php')) {
            include VIEWS_PATH . '/errors/404.php';
        } else {
            echo "<h1>404 - Página não encontrada</h1>";
            echo "<p>A página solicitada não pôde ser encontrada.</p>";
        }
        exit;
    }
}