<?php
/**
 * HomeController - Controlador da página inicial
 */
class HomeController {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Exibe a página inicial
     */
    public function index() {
        // Obter produtos em destaque
        $featuredProducts = $this->productModel->getFeatured(6);
        
        // Obter produtos testados disponíveis para pronta entrega
        $testedProducts = $this->productModel->getTestedProducts(4);
        
        // Obter produtos sob encomenda
        $customProducts = $this->productModel->getCustomProducts(4);
        
        // Obter categorias principais
        $mainCategories = $this->categoryModel->getMainCategories();
        
        // Renderizar a view
        require_once VIEWS_PATH . '/home.php';
    }
    
    /**
     * Exibe a página Sobre
     */
    public function about() {
        require_once VIEWS_PATH . '/about.php';
    }
    
    /**
     * Exibe a página de Contato
     */
    public function contact() {
        require_once VIEWS_PATH . '/contact.php';
    }
    
    /**
     * Processa o formulário de contato
     */
    public function sendContact() {
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'contato');
            exit;
        }
        
        // Validar campos obrigatórios
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'O nome é obrigatório.';
        }
        
        if (empty($email)) {
            $errors['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Informe um e-mail válido.';
        }
        
        if (empty($subject)) {
            $errors['subject'] = 'O assunto é obrigatório.';
        }
        
        if (empty($message)) {
            $errors['message'] = 'A mensagem é obrigatória.';
        }
        
        // Se houver erros, voltar para o formulário
        if (!empty($errors)) {
            $_SESSION['contact_errors'] = $errors;
            $_SESSION['contact_data'] = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            ];
            
            header('Location: ' . BASE_URL . 'contato');
            exit;
        }
        
        // Enviar e-mail (implementação básica)
        $to = STORE_EMAIL;
        $email_subject = "Contato - {$subject}";
        $email_body = "Nome: {$name}\n";
        $email_body .= "E-mail: {$email}\n\n";
        $email_body .= "Mensagem:\n{$message}";
        $headers = "From: {$email}\r\n";
        
        // Tentar enviar o e-mail
        $success = mail($to, $email_subject, $email_body, $headers);
        
        if ($success) {
            $_SESSION['contact_success'] = 'Mensagem enviada com sucesso! Em breve entraremos em contato.';
        } else {
            $_SESSION['contact_error'] = 'Ocorreu um erro ao enviar a mensagem. Por favor, tente novamente.';
        }
        
        header('Location: ' . BASE_URL . 'contato');
        exit;
    }
}