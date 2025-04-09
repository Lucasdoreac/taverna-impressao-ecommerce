<?php
/**
 * PageController - Controlador para páginas estáticas
 */
class PageController {
    
    /**
     * Exibe a página Sobre Nós
     */
    public function sobre() {
        require_once 'app/views/pages/sobre.php';
        $pageTitle = 'Sobre Nós';
        $page = 'pages/sobre';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Exibe a página de Contato
     */
    public function contato() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processContact();
            return;
        }
        require_once 'app/views/pages/contato.php';
        $pageTitle = 'Contato';
        $page = 'pages/contato';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Exibe a página de Termos e Condições
     */
    public function termos() {
        $pageTitle = 'Termos e Condições';
        $page = 'pages/termos';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Exibe a página de Política de Privacidade
     */
    public function privacidade() {
        $pageTitle = 'Política de Privacidade';
        $page = 'pages/privacidade';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Exibe a página de Termos para Upload de Modelos 3D
     */
    public function termsModels3d() {
        $pageTitle = 'Termos para Upload de Modelos 3D';
        $page = 'pages/termos_modelos_3d';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Exibe a página de FAQ
     */
    public function faq() {
        $pageTitle = 'Perguntas Frequentes';
        $page = 'pages/faq';
        
        render($page, compact('pageTitle'));
    }
    
    /**
     * Processa o envio do formulário de contato
     */
    public function processContact() {
        // Verificar se é um POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('contato');
        }
        
        // Obter os dados do formulário
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        // Validar os dados
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'O nome é obrigatório.';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido.';
        }
        
        if (empty($subject)) {
            $errors[] = 'O assunto é obrigatório.';
        }
        
        if (empty($message)) {
            $errors[] = 'A mensagem é obrigatória.';
        }
        
        // Se houver erros, volta para o formulário
        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            redirect('contato');
        }
        
        // Envio do e-mail (simulado)
        $success = true;
        
        if ($success) {
            setFlashMessage('success', 'Mensagem enviada com sucesso! Em breve entraremos em contato.');
        } else {
            setFlashMessage('error', 'Erro ao enviar mensagem. Por favor, tente novamente mais tarde.');
        }
        
        redirect('contato');
    }
}
