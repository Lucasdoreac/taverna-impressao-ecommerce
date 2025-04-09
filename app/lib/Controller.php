<?php
/**
 * Controller Base
 * 
 * Classe base para todos os controllers da aplicação.
 * Implementa métodos comuns que serão utilizados por controllers específicos.
 */
class Controller {
    /**
     * Define uma mensagem flash para ser exibida na próxima requisição
     * 
     * @param string $type Tipo da mensagem (success, error, warning, info)
     * @param string $message Conteúdo da mensagem
     * @return void
     */
    protected function setFlashMessage($type, $message) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Obtém as mensagens flash e as remove da sessão
     * 
     * @return array Mensagens flash
     */
    protected function getFlashMessages() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        
        return $messages;
    }
    
    /**
     * Renderiza uma view com os dados fornecidos
     * 
     * @param string $view Nome da view
     * @param array $data Dados para a view
     * @return void
     */
    protected function render($view, $data = []) {
        // Extrair variáveis para o escopo
        extract($data);
        
        // Obter mensagens flash
        $flashMessages = $this->getFlashMessages();
        
        // Incluir o arquivo de view
        include_once __DIR__ . "/../views/{$view}.php";
    }
    
    /**
     * Redireciona para uma URL específica
     * 
     * @param string $url URL para redirecionamento
     * @return void
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Verifica se o usuário está autenticado, redireciona para login se não estiver
     * 
     * @param string $redirectUrl URL para redirecionamento se não autenticado
     * @return bool True se autenticado, false caso contrário
     */
    protected function requireAuthentication($redirectUrl = '/login') {
        // Importar SecurityManager para usar métodos de autenticação
        require_once __DIR__ . "/Security/SecurityManager.php";
        
        if (!SecurityManager::checkAuthentication()) {
            $this->setFlashMessage('error', 'Você precisa estar logado para acessar esta página.');
            $this->redirect($redirectUrl);
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida e obtém um parâmetro de requisição
     * 
     * @param string $name Nome do parâmetro
     * @param string $type Tipo do parâmetro (int, float, string, email, url, bool, array)
     * @param array $options Opções adicionais
     * @return mixed Valor validado do parâmetro
     */
    protected function getValidatedParam($name, $type, array $options = []) {
        // Definir opções padrão
        $source = isset($options['source']) ? strtoupper($options['source']) : 'GET';
        $required = isset($options['required']) ? (bool)$options['required'] : false;
        $default = isset($options['default']) ? $options['default'] : null;
        
        // Obter valor do parâmetro da fonte correta
        $value = null;
        switch ($source) {
            case 'POST':
                $value = isset($_POST[$name]) ? $_POST[$name] : null;
                break;
            case 'GET':
                $value = isset($_GET[$name]) ? $_GET[$name] : null;
                break;
            case 'REQUEST':
                $value = isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
                break;
        }
        
        // Verificar se é obrigatório
        if ($required && $value === null) {
            throw new Exception("O parâmetro '$name' é obrigatório");
        }
        
        // Usar valor padrão se necessário
        if ($value === null) {
            return $default;
        }
        
        // Validar e sanitizar de acordo com o tipo
        switch ($type) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    throw new Exception("O parâmetro '$name' deve ser um número inteiro");
                }
                return (int)$value;
                
            case 'float':
                if (!is_numeric($value)) {
                    throw new Exception("O parâmetro '$name' deve ser um número");
                }
                return (float)$value;
                
            case 'string':
                return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
                
            case 'email':
                $email = filter_var(trim((string)$value), FILTER_SANITIZE_EMAIL);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("O parâmetro '$name' não é um email válido");
                }
                return $email;
                
            case 'url':
                $url = filter_var(trim((string)$value), FILTER_SANITIZE_URL);
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new Exception("O parâmetro '$name' não é uma URL válida");
                }
                return $url;
                
            case 'bool':
                return (bool)$value;
                
            case 'array':
                if (!is_array($value)) {
                    throw new Exception("O parâmetro '$name' deve ser um array");
                }
                return $value;
                
            default:
                throw new Exception("Tipo de validação '$type' não suportado");
        }
    }
}