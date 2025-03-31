<?php
/**
 * Controller - Classe base para controladores
 */
abstract class Controller {
    /**
     * Método para renderizar uma view
     * 
     * @param string $view Nome da view a ser renderizada
     * @param array $data Dados a serem passados para a view
     * @return void
     */
    protected function render($view, $data = []) {
        // Extrair dados para torná-los disponíveis na view
        extract($data);
        
        $viewFile = VIEWS_PATH . '/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            throw new Exception("View {$view} não encontrada.");
        }
    }
    
    /**
     * Método para obter instância do banco de dados
     * 
     * @return Database Instância do banco de dados
     */
    protected function db() {
        return Database::getInstance();
    }
    
    /**
     * Método para redirecionar o usuário
     * 
     * @param string $url URL para redirecionamento
     * @return void
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Método para retornar uma resposta JSON
     * 
     * @param mixed $data Dados a serem convertidos para JSON
     * @param int $statusCode Código de status HTTP
     * @return void
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Método para lidar com erros
     * 
     * @param Exception $e Exceção capturada
     * @param string $message Mensagem amigável para o usuário
     * @param bool $isJson Se verdadeiro, retorna erro como JSON
     * @return void
     */
    protected function handleError($e, $message = "Ocorreu um erro", $isJson = false) {
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
        
        if ($isJson) {
            $this->json([
                'status' => 'error',
                'message' => $message,
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null
            ], 500);
        } else {
            $_SESSION['error_message'] = $message;
            $this->redirect("/error");
        }
    }
    
    /**
     * Verifica se o usuário está autenticado
     * 
     * @param bool $redirect Se verdadeiro, redireciona para a página de login
     * @return bool Se o usuário está autenticado
     */
    protected function isAuthenticated($redirect = true) {
        if (!isset($_SESSION['user_id'])) {
            if ($redirect) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                $this->redirect("/login");
            }
            return false;
        }
        return true;
    }
    
    /**
     * Verifica se o usuário tem a permissão necessária
     * 
     * @param string $role Função/papel necessário (admin, customer, etc)
     * @param bool $redirect Se verdadeiro, redireciona para página de acesso negado
     * @return bool Se o usuário tem a permissão
     */
    protected function hasRole($role, $redirect = true) {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
            if ($redirect) {
                $this->redirect("/access-denied");
            }
            return false;
        }
        return true;
    }
}
