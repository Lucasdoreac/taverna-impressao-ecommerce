<?php
/**
 * AuthController - Controlador para autenticação de usuários
 */
class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
    }
    
    /**
     * Exibe a página de login
     */
    public function login() {
        // Se já está logado, redireciona para a página inicial
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            // Validar campos
            $errors = [];
            
            if (empty($email)) {
                $errors['email'] = 'O e-mail é obrigatório.';
            }
            
            if (empty($password)) {
                $errors['password'] = 'A senha é obrigatória.';
            }
            
            // Se não houver erros, tenta fazer login
            if (empty($errors)) {
                $success = $this->userModel->login($email, $password);
                
                if ($success) {
                    // Set remember-me cookie if selected
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        setcookie('remember_token', $token, [
                            'expires' => $expires,
                            'path' => '/',
                            'secure' => true,
                            'httponly' => true,
                            'samesite' => 'Strict'
                        ]);
                        
                        // Salvar token no banco de dados
                        $user_id = $_SESSION['user']['id'];
                        $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) 
                                VALUES (:user_id, :token, :expires_at)";
                        
                        Database::getInstance()->query($sql, [
                            'user_id' => $user_id,
                            'token' => $token,
                            'expires_at' => date('Y-m-d H:i:s', $expires)
                        ]);
                    }
                    
                    // Definir flag para migrar carrinho após o login
                    if (!empty($_SESSION['cart'])) {
                        $_SESSION['migrate_cart'] = true;
                    }
                    
                    // Redirecionar após login
                    $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL;
                    unset($_SESSION['redirect_after_login']);
                    
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $errors['login'] = 'E-mail ou senha incorretos.';
                }
            }
            
            // Se chegou aqui, houve erro no login
            $_SESSION['login_errors'] = $errors;
            $_SESSION['login_email'] = $email;
            
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        // Recuperar mensagens de erro da sessão
        $errors = $_SESSION['login_errors'] ?? [];
        $email = $_SESSION['login_email'] ?? '';
        
        // Limpar sessão
        unset($_SESSION['login_errors']);
        unset($_SESSION['login_email']);
        
        // Renderizar view
        require_once VIEWS_PATH . '/auth/login.php';
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout() {
        // Remover remember token se existir
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            // Remover do banco de dados
            $sql = "DELETE FROM remember_tokens WHERE token = :token";
            Database::getInstance()->query($sql, ['token' => $token]);
            
            // Limpar cookie
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        // Realizar logout
        $this->userModel->logout();
        
        // Redirecionar para a página inicial
        header('Location: ' . BASE_URL);
        exit;
    }
    
    /**
     * Exibe a página de registro
     */
    public function register() {
        // Se já está logado, redireciona para a página inicial
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            
            // Validar campos
            $errors = [];
            
            if (empty($name)) {
                $errors['name'] = 'O nome é obrigatório.';
            }
            
            if (empty($email)) {
                $errors['email'] = 'O e-mail é obrigatório.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Informe um e-mail válido.';
            }
            
            if (empty($password)) {
                $errors['password'] = 'A senha é obrigatória.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'A senha deve ter pelo menos 6 caracteres.';
            }
            
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'As senhas não coincidem.';
            }
            
            // Se não houver erros, tenta registrar
            if (empty($errors)) {
                $result = $this->userModel->register([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'phone' => $phone
                ]);
                
                if ($result['success']) {
                    // Definir flag para migrar carrinho após o registro se tiver login automático
                    if (!empty($_SESSION['cart']) && isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
                        $_SESSION['migrate_cart'] = true;
                    }
                    
                    // Redirecionar após registro
                    $_SESSION['success'] = 'Cadastro realizado com sucesso!';
                    header('Location: ' . BASE_URL);
                    exit;
                } else {
                    $errors['register'] = $result['message'];
                }
            }
            
            // Se chegou aqui, houve erro no registro
            $_SESSION['register_errors'] = $errors;
            $_SESSION['register_data'] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ];
            
            header('Location: ' . BASE_URL . 'cadastro');
            exit;
        }
        
        // Recuperar mensagens de erro e dados da sessão
        $errors = $_SESSION['register_errors'] ?? [];
        $data = $_SESSION['register_data'] ?? [
            'name' => '',
            'email' => '',
            'phone' => ''
        ];
        
        // Limpar sessão
        unset($_SESSION['register_errors']);
        unset($_SESSION['register_data']);
        
        // Renderizar view
        require_once VIEWS_PATH . '/auth/register.php';
    }
    
    /**
     * Exibe a página de recuperação de senha
     */
    public function recoverPassword() {
        // Se já está logado, redireciona para a página inicial
        if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            
            // Validar campos
            $errors = [];
            
            if (empty($email)) {
                $errors['email'] = 'O e-mail é obrigatório.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Informe um e-mail válido.';
            }
            
            // Se não houver erros, tenta recuperar a senha
            if (empty($errors)) {
                $result = $this->userModel->initiatePasswordRecovery($email);
                
                $_SESSION['recovery_message'] = $result['message'];
                $_SESSION['recovery_success'] = $result['success'];
                
                header('Location: ' . BASE_URL . 'recuperar-senha');
                exit;
            }
            
            // Se chegou aqui, houve erro na recuperação
            $_SESSION['recovery_errors'] = $errors;
            $_SESSION['recovery_email'] = $email;
            
            header('Location: ' . BASE_URL . 'recuperar-senha');
            exit;
        }
        
        // Verificar se há um token de recuperação
        $token = $_GET['token'] ?? null;
        
        if ($token) {
            return $this->resetPassword($token);
        }
        
        // Recuperar mensagens de erro e dados da sessão
        $errors = $_SESSION['recovery_errors'] ?? [];
        $email = $_SESSION['recovery_email'] ?? '';
        $message = $_SESSION['recovery_message'] ?? '';
        $success = $_SESSION['recovery_success'] ?? false;
        
        // Limpar sessão
        unset($_SESSION['recovery_errors']);
        unset($_SESSION['recovery_email']);
        unset($_SESSION['recovery_message']);
        unset($_SESSION['recovery_success']);
        
        // Renderizar view
        require_once VIEWS_PATH . '/auth/recover_password.php';
    }
    
    /**
     * Processa o formulário de redefinição de senha
     */
    private function resetPassword($token) {
        // Verificar se o token é válido
        $sql = "SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1";
        $result = Database::getInstance()->select($sql, ['token' => $token]);
        
        if (!$result) {
            $_SESSION['reset_error'] = 'Token inválido ou expirado.';
            header('Location: ' . BASE_URL . 'recuperar-senha');
            exit;
        }
        
        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validar campos
            $errors = [];
            
            if (empty($password)) {
                $errors['password'] = 'A senha é obrigatória.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = 'A senha deve ter pelo menos 6 caracteres.';
            }
            
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'As senhas não coincidem.';
            }
            
            // Se não houver erros, redefine a senha
            if (empty($errors)) {
                $result = $this->userModel->resetPassword($token, $password);
                
                if ($result['success']) {
                    $_SESSION['success'] = $result['message'];
                    header('Location: ' . BASE_URL . 'login');
                    exit;
                } else {
                    $errors['reset'] = $result['message'];
                }
            }
            
            // Se chegou aqui, houve erro na redefinição
            $_SESSION['reset_errors'] = $errors;
            
            header('Location: ' . BASE_URL . 'recuperar-senha?token=' . $token);
            exit;
        }
        
        // Recuperar mensagens de erro da sessão
        $errors = $_SESSION['reset_errors'] ?? [];
        
        // Limpar sessão
        unset($_SESSION['reset_errors']);
        
        // Renderizar view
        require_once VIEWS_PATH . '/auth/reset_password.php';
    }
}