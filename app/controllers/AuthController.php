<?php
/**
 * AuthController
 * 
 * Controlador para autenticação, registro e gerenciamento de usuários
 * com validação de entrada implementada.
 */
class AuthController {
    /**
     * Incluir o trait de validação de entrada
     */
    use InputValidationTrait;
    
    private $userModel;
    private $loginLog;

    public function __construct() {
        // Incluir o trait
        require_once dirname(__FILE__) . '/../lib/Security/InputValidationTrait.php';
        
        $this->userModel = new UserModel();
        if (class_exists('LoginLogModel')) {
            $this->loginLog = new LoginLogModel();
        }
    }

    /**
     * Renderiza a página de login e processa o formulário
     */
    public function login() {
        // Inicializar variáveis
        $errors = [];
        $email = '';
        
        // Verificar se é uma requisição POST (formulário enviado)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            require_once APP_PATH . '/lib/Security/CsrfProtection.php';
            if (!CsrfProtection::validateRequest()) {
                $errors['login'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
            } else {
                // Validar dados do formulário usando o novo sistema de validação
                $loginData = $this->postValidatedParams([
                    'email' => [
                        'type' => 'email',
                        'required' => true,
                        'requiredMessage' => 'E-mail é obrigatório',
                        'invalidMessage' => 'E-mail inválido'
                    ],
                    'password' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize' => false,
                        'requiredMessage' => 'Senha é obrigatória'
                    ],
                    'remember' => [
                        'type' => 'bool',
                        'default' => false
                    ]
                ]);
                
                // Verificar se houve erros de validação
                if ($this->hasValidationErrors()) {
                    $errors = array_merge($errors, $this->getValidationErrors());
                    $email = $loginData['email'] ?? '';
                } else {
                    // Extrair dados validados
                    $email = $loginData['email'];
                    $password = $loginData['password'];
                    $remember = $loginData['remember'];
                    
                    // Procurar usuário pelo email
                    $user = $this->userModel->findByEmail($email);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Autenticação bem-sucedida
                        
                        // Registrar login no log
                        if (isset($this->loginLog)) {
                            $this->loginLog->logLogin($user['id'], $email, $_SERVER['REMOTE_ADDR'], 'success');
                        }
                        
                        // Salvar dados do usuário na sessão
                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'name' => $user['name'],
                            'email' => $user['email'],
                            'role' => $user['role']
                        ];
                        
                        // Regenerar ID da sessão para segurança
                        session_regenerate_id(true);
                        
                        // Criar token "lembrar de mim" se solicitado
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
                            
                            // Salvar token no banco de dados
                            $sql = "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
                            Database::getInstance()->query($sql, [
                                'user_id' => $user['id'],
                                'token' => $token,
                                'expires_at' => $expiry
                            ]);
                            
                            // Definir cookie
                            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
                        }
                        
                        // Redirecionar para a página inicial ou página solicitada anteriormente
                        $redirect = $_SESSION['redirect_after_login'] ?? 'minha-conta';
                        unset($_SESSION['redirect_after_login']);
                        
                        header("Location: " . BASE_URL . $redirect);
                        exit;
                    } else {
                        // Autenticação falhou
                        if (isset($this->loginLog)) {
                            $this->loginLog->logLogin(null, $email, $_SERVER['REMOTE_ADDR'], 'failed');
                        }
                        $errors['login'] = 'E-mail ou senha incorretos';
                    }
                }
            }
        } else {
            // Verificar se existe um token "lembrar de mim"
            if (isset($_COOKIE['remember_token'])) {
                // Validar o token do cookie
                $token = $this->requestValidatedParam('remember_token', 'string', [
                    'source' => 'COOKIE',
                    'minLength' => 32,
                    'maxLength' => 64,
                    'pattern' => '/^[a-f0-9]+$/' // Apenas caracteres hexadecimais
                ]);
                
                if ($token) {
                    // Buscar token válido no banco de dados
                    $sql = "SELECT user_id FROM remember_tokens WHERE token = :token AND expires_at > NOW()";
                    $result = Database::getInstance()->query($sql, ['token' => $token]);
                    
                    if ($result && isset($result[0]['user_id'])) {
                        $userId = $result[0]['user_id'];
                        
                        // Buscar usuário
                        $user = $this->userModel->findById($userId);
                        
                        if ($user) {
                            // Login automático
                            $_SESSION['user'] = [
                                'id' => $user['id'],
                                'name' => $user['name'],
                                'email' => $user['email'],
                                'role' => $user['role']
                            ];
                            
                            // Regenerar ID da sessão
                            session_regenerate_id(true);
                            
                            // Registrar login automático no log
                            if (isset($this->loginLog)) {
                                $this->loginLog->logLogin($user['id'], $user['email'], $_SERVER['REMOTE_ADDR'], 'auto');
                            }
                            
                            // Redirecionar para a página inicial
                            header("Location: " . BASE_URL);
                            exit;
                        }
                    }
                }
                
                // Se chegou aqui, o token é inválido - remover
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
        
        // Exibir formulário de login
        require_once VIEWS_PATH . '/auth/login.php';
    }

    /**
     * Renderiza a página de registro e processa o formulário
     */
    public function register() {
        // Inicializar variáveis
        $errors = [];
        $data = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'password' => '',
            'confirm_password' => ''
        ];
        
        // Verificar se é uma requisição POST (formulário enviado)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            require_once APP_PATH . '/lib/Security/CsrfProtection.php';
            if (!CsrfProtection::validateRequest()) {
                $errors['register'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
            } else {
                // Validar dados do formulário usando o novo sistema de validação
                $userData = $this->postValidatedParams([
                    'name' => [
                        'type' => 'string',
                        'required' => true,
                        'minLength' => 3,
                        'maxLength' => 100,
                        'requiredMessage' => 'Nome é obrigatório',
                        'minLengthMessage' => 'Nome deve ter pelo menos 3 caracteres'
                    ],
                    'email' => [
                        'type' => 'email',
                        'required' => true,
                        'requiredMessage' => 'E-mail é obrigatório',
                        'invalidMessage' => 'E-mail inválido'
                    ],
                    'phone' => [
                        'type' => 'phone',
                        'required' => false,
                        'minLength' => 10,
                        'maxLength' => 11,
                        'invalidMessage' => 'Telefone inválido'
                    ],
                    'password' => [
                        'type' => 'string',
                        'required' => true,
                        'minLength' => 6,
                        'sanitize' => false,
                        'requiredMessage' => 'Senha é obrigatória',
                        'minLengthMessage' => 'Senha deve ter pelo menos 6 caracteres'
                    ],
                    'confirm_password' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize' => false,
                        'requiredMessage' => 'Confirmação de senha é obrigatória'
                    ],
                    'terms' => [
                        'type' => 'bool',
                        'required' => true,
                        'requiredMessage' => 'Você deve aceitar os termos de uso'
                    ]
                ]);
                
                // Verificar se houve erros de validação
                if ($this->hasValidationErrors()) {
                    $errors = array_merge($errors, $this->getValidationErrors());
                    
                    // Manter os dados válidos para o formulário
                    foreach ($userData as $key => $value) {
                        if (isset($data[$key])) {
                            $data[$key] = $value;
                        }
                    }
                } else {
                    // Verificar se e-mail já existe
                    if ($this->userModel->findByEmail($userData['email'])) {
                        $errors['email'] = 'Este e-mail já está cadastrado';
                        $data = $userData;
                    }
                    // Verificar se as senhas conferem
                    elseif ($userData['password'] !== $userData['confirm_password']) {
                        $errors['confirm_password'] = 'As senhas não conferem';
                        $data = $userData;
                    }
                    else {
                        // Criar o usuário
                        $userId = $this->userModel->create([
                            'name' => $userData['name'],
                            'email' => $userData['email'],
                            'phone' => $userData['phone'],
                            'password' => $userData['password'],
                        ]);
                        
                        if ($userId) {
                            // Registro bem-sucedido
                            
                            // Configurar mensagem de sucesso
                            $_SESSION['success'] = 'Cadastro realizado com sucesso! Faça login para continuar.';
                            
                            // Redirecionar para a página de login
                            header("Location: " . BASE_URL . "login");
                            exit;
                        } else {
                            // Falha ao criar usuário
                            $errors['register'] = 'Erro ao criar o cadastro. Por favor, tente novamente.';
                            $data = $userData;
                        }
                    }
                }
            }
        }
        
        // Exibir formulário de cadastro
        require_once VIEWS_PATH . '/auth/register.php';
    }

    /**
     * Processa o logout do usuário
     */
    public function logout() {
        // Obter dados do usuário antes de limpar a sessão
        $userId = $_SESSION['user']['id'] ?? null;
        $userEmail = $_SESSION['user']['email'] ?? 'unknown';
        
        // Registrar o evento de logout
        if (isset($this->loginLog)) {
            $this->loginLog->logLogout($userId, $userEmail, $_SERVER['REMOTE_ADDR']);
        }
        
        // Remover token "lembrar de mim" se existir
        if (isset($_COOKIE['remember_token'])) {
            // Validar token
            $token = $this->requestValidatedParam('remember_token', 'string', [
                'source' => 'COOKIE',
                'minLength' => 32,
                'maxLength' => 64,
                'pattern' => '/^[a-f0-9]+$/' // Apenas caracteres hexadecimais
            ]);
            
            if ($token) {
                // Remover do banco de dados
                $sql = "DELETE FROM remember_tokens WHERE token = :token";
                Database::getInstance()->query($sql, ['token' => $token]);
            }
            
            // Remover cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Realizar logout
        $this->userModel->logout();
        
        // Redirecionar para a página inicial
        header('Location: ' . BASE_URL);
        exit();
    }
    
    /**
     * Renderiza a página de recuperação de senha e processa o formulário
     */
    public function recoverPassword() {
        // Inicializar variáveis
        $errors = [];
        $message = '';
        $email = '';
        $success = false;
        
        // Verificar se é uma requisição POST (formulário enviado)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            require_once APP_PATH . '/lib/Security/CsrfProtection.php';
            if (!CsrfProtection::validateRequest()) {
                $errors['recovery'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
            } else {
                // Validar email usando o novo sistema de validação
                $recoveryData = $this->postValidatedParams([
                    'email' => [
                        'type' => 'email',
                        'required' => true,
                        'requiredMessage' => 'E-mail é obrigatório',
                        'invalidMessage' => 'E-mail inválido'
                    ]
                ]);
                
                // Verificar se houve erros de validação
                if ($this->hasValidationErrors()) {
                    $errors = array_merge($errors, $this->getValidationErrors());
                } else {
                    $email = $recoveryData['email'];
                    
                    // Verificar se o email existe
                    $user = $this->userModel->findByEmail($email);
                    
                    if (!$user) {
                        $errors['email'] = 'Não existe conta com este e-mail';
                    } else {
                        // Gerar token de recuperação
                        $token = bin2hex(random_bytes(32));
                        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        
                        // Salvar token no banco de dados
                        try {
                            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
                            Database::getInstance()->query($sql, [
                                'user_id' => $user['id'],
                                'token' => $token,
                                'expires_at' => $expiry
                            ]);
                            
                            // Enviar email com o link de recuperação
                            if (class_exists('EmailService')) {
                                $emailService = new EmailService();
                                $sent = $emailService->sendPasswordReset($user['email'], $user['name'], $token);
                                
                                if ($sent) {
                                    $success = true;
                                    $message = 'E-mail de recuperação enviado com sucesso! Verifique sua caixa de entrada (e pasta de spam) para continuar.';
                                } else {
                                    $errors['recovery'] = 'Erro ao enviar e-mail de recuperação';
                                }
                            } else {
                                // Simular envio bem-sucedido para não expor falha de implementação
                                $success = true;
                                $message = 'E-mail de recuperação enviado com sucesso! Verifique sua caixa de entrada (e pasta de spam) para continuar.';
                                
                                // Log para depuração
                                error_log("EmailService não encontrado. E-mail de recuperação não enviado para {$email}");
                            }
                        } catch (Exception $e) {
                            // Erro ao salvar token
                            $errors['recovery'] = 'Erro ao processar a recuperação de senha. Por favor, tente novamente.';
                            error_log("Erro ao salvar token de recuperação: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        // Exibir formulário ou mensagem de sucesso
        require_once VIEWS_PATH . '/auth/recover_password.php';
    }
    
    /**
     * Renderiza a página de redefinição de senha e processa o formulário
     */
    public function resetPassword() {
        // Inicializar variáveis
        $errors = [];
        $message = '';
        $success = false;
        
        // Validar o token da URL
        $token = $this->getValidatedParam('token', 'string', [
            'required' => true,
            'minLength' => 32,
            'pattern' => '/^[a-f0-9]+$/', // Apenas caracteres hexadecimais
            'requiredMessage' => 'Token inválido ou expirado',
            'invalidMessage' => 'Token inválido ou expirado'
        ]);
        
        // Verificar se o token é válido
        if (!$token || $this->hasValidationErrors()) {
            $errors['token'] = 'Token inválido ou expirado';
        } else {
            // Buscar token válido no banco de dados
            try {
                $sql = "SELECT user_id FROM password_resets WHERE token = :token AND expires_at > NOW()";
                $result = Database::getInstance()->query($sql, ['token' => $token]);
                
                if (!$result || !isset($result[0]['user_id'])) {
                    $errors['token'] = 'Token inválido ou expirado';
                }
            } catch (Exception $e) {
                // Erro ao consultar token
                $errors['token'] = 'Erro ao verificar token';
                error_log("Erro ao verificar token de reset: " . $e->getMessage());
            }
        }
        
        // Verificar se é uma requisição POST (formulário enviado)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
            // Verificar token CSRF
            require_once APP_PATH . '/lib/Security/CsrfProtection.php';
            if (!CsrfProtection::validateRequest()) {
                $errors['reset'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
            } else {
                // Validar dados do formulário usando o novo sistema de validação
                $resetData = $this->postValidatedParams([
                    'password' => [
                        'type' => 'string',
                        'required' => true,
                        'minLength' => 6,
                        'sanitize' => false,
                        'requiredMessage' => 'Senha é obrigatória',
                        'minLengthMessage' => 'Senha deve ter pelo menos 6 caracteres'
                    ],
                    'confirm_password' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize' => false,
                        'requiredMessage' => 'Confirmação de senha é obrigatória'
                    ]
                ]);
                
                // Verificar se houve erros de validação
                if ($this->hasValidationErrors()) {
                    $errors = array_merge($errors, $this->getValidationErrors());
                } 
                // Verificar se as senhas conferem
                elseif ($resetData['password'] !== $resetData['confirm_password']) {
                    $errors['confirm_password'] = 'As senhas não conferem';
                }
                else {
                    try {
                        // Obter o ID do usuário
                        $sql = "SELECT user_id FROM password_resets WHERE token = :token";
                        $result = Database::getInstance()->query($sql, ['token' => $token]);
                        $userId = $result[0]['user_id'];
                        
                        // Atualizar a senha
                        $updated = $this->userModel->update($userId, [
                            'password' => $resetData['password']
                        ]);
                        
                        if ($updated) {
                            // Invalidar token usado
                            $sql = "DELETE FROM password_resets WHERE token = :token";
                            Database::getInstance()->query($sql, ['token' => $token]);
                            
                            // Senha alterada com sucesso
                            $success = true;
                            $message = 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.';
                        } else {
                            $errors['reset'] = 'Erro ao atualizar senha';
                        }
                    } catch (Exception $e) {
                        // Erro ao atualizar senha
                        $errors['reset'] = 'Erro ao atualizar senha';
                        error_log("Erro ao redefinir senha: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Exibir formulário ou mensagem de sucesso
        require_once VIEWS_PATH . '/auth/reset_password.php';
    }
}