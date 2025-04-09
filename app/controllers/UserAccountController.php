<?php
/**
 * UserAccountController - Controlador para a área de usuário
 * 
 * Este controlador gerencia todas as funcionalidades da área de usuário,
 * incluindo visualização de perfil, histórico de compras, gerenciamento
 * de endereços e recuperação de senha.
 * 
 * @package Taverna\Controllers
 * @author Taverna da Impressão
 * @version 1.0.0
 */

class UserAccountController {
    // Implementação do trait de validação
    use InputValidationTrait;
    
    private $userModel;
    private $addressModel;
    private $orderModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar InputValidationTrait
        require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
        
        // Carregar modelos necessários
        require_once APP_PATH . '/models/UserModel.php';
        require_once APP_PATH . '/models/AddressModel.php';
        require_once APP_PATH . '/models/OrderModel.php';
        
        $this->userModel = new UserModel();
        $this->addressModel = new AddressModel();
        $this->orderModel = new OrderModel();
        
        // Verificar autenticação
        $this->checkAuth();
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuth() {
        // Se não estiver logado, redirecionar para login
        if (!SecurityManager::isUserLoggedIn()) {
            // Salvar URL atual para redirecionamento após login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Definir mensagem
            $_SESSION['info'] = 'Por favor, faça login para acessar sua conta.';
            
            // Redirecionar para página de login
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }
    
    /**
     * Página principal da área de usuário
     */
    public function index() {
        // Obter dados do usuário
        $userId = $_SESSION['user']['id'];
        $userData = $this->userModel->findById($userId);
        
        if (!$userData) {
            $_SESSION['error'] = 'Erro ao carregar dados do usuário.';
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Verificar se existem mensagens de sucesso/erro na sessão
        $success = $_SESSION['success'] ?? null;
        $error = $_SESSION['error'] ?? null;
        
        // Limpar mensagens da sessão
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Dados para a view
        $data = [
            'user' => $userData,
            'success' => $success,
            'error' => $error
        ];
        
        // Renderizar view
        $this->renderView('index', $data);
    }
    
    /**
     * Exibe e processa o formulário de edição de perfil
     */
    public function profile() {
        $userId = $_SESSION['user']['id'];
        $userData = $this->userModel->findById($userId);
        
        // Se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'minha-conta/perfil');
                exit;
            }
            
            // Validar dados do formulário
            $validations = [
                'name' => ['type' => 'string', 'required' => true, 'minLength' => 3, 'maxLength' => 100],
                'email' => ['type' => 'email', 'required' => true, 'maxLength' => 255],
                'phone' => ['type' => 'string', 'required' => false, 'maxLength' => 20]
            ];
            
            $data = $this->postValidatedParams($validations);
            
            // Se a validação falhar
            if (!$data || $this->hasValidationErrors()) {
                $_SESSION['error'] = implode('<br>', $this->getValidationErrors());
                header('Location: ' . BASE_URL . 'minha-conta/perfil');
                exit;
            }
            
            // Verificar se o email está sendo alterado e se já existe
            if ($data['email'] !== $userData['email']) {
                $existingUser = $this->userModel->findByEmail($data['email']);
                if ($existingUser) {
                    $_SESSION['error'] = 'Este e-mail já está em uso por outra conta.';
                    header('Location: ' . BASE_URL . 'minha-conta/perfil');
                    exit;
                }
            }
            
            // Verificar se a senha atual foi fornecida (necessário para alterações)
            $currentPassword = $this->postValidatedParam('current_password', 'string', ['required' => true]);
            
            if (!$currentPassword || !password_verify($currentPassword, $userData['password'])) {
                $_SESSION['error'] = 'Senha atual incorreta.';
                header('Location: ' . BASE_URL . 'minha-conta/perfil');
                exit;
            }
            
            // Verificar se há nova senha
            $newPassword = $this->postValidatedParam('new_password', 'string', ['required' => false]);
            $confirmPassword = $this->postValidatedParam('confirm_password', 'string', ['required' => false]);
            
            // Se uma nova senha foi fornecida
            if ($newPassword) {
                // Validar tamanho mínimo
                if (strlen($newPassword) < 6) {
                    $_SESSION['error'] = 'A nova senha deve ter pelo menos 6 caracteres.';
                    header('Location: ' . BASE_URL . 'minha-conta/perfil');
                    exit;
                }
                
                // Verificar se as senhas conferem
                if ($newPassword !== $confirmPassword) {
                    $_SESSION['error'] = 'As senhas não conferem.';
                    header('Location: ' . BASE_URL . 'minha-conta/perfil');
                    exit;
                }
                
                // Adicionar senha aos dados para atualização
                $data['password'] = $newPassword;
            }
            
            // Atualizar dados do usuário
            $updated = $this->userModel->update($userId, $data);
            
            if ($updated) {
                // Atualizar dados na sessão
                $_SESSION['user']['name'] = $data['name'];
                $_SESSION['user']['email'] = $data['email'];
                
                $_SESSION['success'] = 'Perfil atualizado com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao atualizar perfil. Por favor, tente novamente.';
            }
            
            header('Location: ' . BASE_URL . 'minha-conta/perfil');
            exit;
        }
        
        // Dados para a view
        $data = [
            'user' => $userData,
            'csrfToken' => CsrfProtection::getFormField(), // Corrigido para usar o método correto
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        // Limpar mensagens da sessão
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Renderizar view
        $this->renderView('profile', $data);
    }
    
    /**
     * Exibe o histórico de compras do usuário
     */
    public function orders() {
        $userId = $_SESSION['user']['id'];
        
        // Obter parâmetros de paginação
        $page = $this->getValidatedParam('page', 'int', ['default' => 1, 'min' => 1]);
        $limit = 10;
        
        // Obter pedidos do usuário
        $ordersData = $this->orderModel->getUserOrders($userId, $page, $limit);
        
        // Dados para a view
        $data = [
            'orders' => $ordersData['items'],
            'pagination' => [
                'total' => $ordersData['total'],
                'currentPage' => $ordersData['currentPage'],
                'lastPage' => $ordersData['lastPage'],
                'perPage' => $ordersData['perPage']
            ],
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        // Limpar mensagens da sessão
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Renderizar view
        $this->renderView('orders', $data);
    }
    
    /**
     * Exibe os detalhes de um pedido específico
     * 
     * @param array $params Parâmetros da rota, incluindo o ID do pedido
     */
    public function orderDetails($params) {
        $userId = $_SESSION['user']['id'];
        $orderId = isset($params['id']) ? (int)$params['id'] : 0;
        
        // Validar ID do pedido
        if (!$orderId) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            exit;
        }
        
        // Obter detalhes do pedido
        $order = $this->orderModel->getOrder($orderId);
        
        // Verificar se o pedido existe e pertence ao usuário
        if (!$order || $order['user_id'] != $userId) {
            $_SESSION['error'] = 'Pedido não encontrado ou não autorizado.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            exit;
        }
        
        // Obter itens do pedido
        $orderItems = $this->orderModel->getOrderItems($orderId);
        
        // Dados para a view
        $data = [
            'order' => $order,
            'items' => $orderItems,
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        // Limpar mensagens da sessão
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Renderizar view
        $this->renderView('order_details', $data);
    }
    
    /**
     * Exibe e gerencia os endereços do usuário
     */
    public function addresses() {
        $userId = $_SESSION['user']['id'];
        
        // Obter endereços do usuário
        $addresses = $this->addressModel->getUserAddresses($userId);
        
        // Dados para a view
        $data = [
            'addresses' => $addresses,
            'csrfToken' => CsrfProtection::getFormField(), // Corrigido para usar o método correto
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        // Limpar mensagens da sessão
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Renderizar view
        $this->renderView('addresses', $data);
    }
    
    /**
     * Exibe e processa o formulário para adicionar/editar endereço
     * 
     * @param array $params Parâmetros da rota, incluindo o ID do endereço (para edição)
     */
    public function addressForm($params = []) {
        $userId = $_SESSION['user']['id'];
        $addressId = isset($params['id']) ? (int)$params['id'] : 0;
        $address = null;
        
        // Se for edição, carregar dados do endereço
        if ($addressId) {
            $address = $this->addressModel->getAddress($addressId);
            
            // Verificar se o endereço existe e pertence ao usuário
            if (!$address || $address['user_id'] != $userId) {
                $_SESSION['error'] = 'Endereço não encontrado ou não autorizado.';
                header('Location: ' . BASE_URL . 'minha-conta/enderecos');
                exit;
            }
        }
        
        // Se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'minha-conta/enderecos');
                exit;
            }
            
            // Validar dados do formulário
            $validations = [
                'street' => ['type' => 'string', 'required' => true, 'maxLength' => 100],
                'number' => ['type' => 'string', 'required' => true, 'maxLength' => 20],
                'complement' => ['type' => 'string', 'required' => false, 'maxLength' => 100],
                'neighborhood' => ['type' => 'string', 'required' => true, 'maxLength' => 100],
                'city' => ['type' => 'string', 'required' => true, 'maxLength' => 100],
                'state' => ['type' => 'string', 'required' => true, 'maxLength' => 2],
                'postal_code' => ['type' => 'string', 'required' => true, 'maxLength' => 10],
                'is_default' => ['type' => 'bool', 'required' => false, 'default' => false]
            ];
            
            $data = $this->postValidatedParams($validations);
            
            // Se a validação falhar
            if (!$data || $this->hasValidationErrors()) {
                $_SESSION['error'] = implode('<br>', $this->getValidationErrors());
                
                if ($addressId) {
                    header('Location: ' . BASE_URL . 'minha-conta/enderecos/editar/' . $addressId);
                } else {
                    header('Location: ' . BASE_URL . 'minha-conta/enderecos/adicionar');
                }
                exit;
            }
            
            // Adicionar ID do usuário
            $data['user_id'] = $userId;
            
            // Se for endereço padrão, atualizar os outros endereços
            if ($data['is_default']) {
                $this->addressModel->resetDefaultAddress($userId);
            }
            
            // Salvar endereço
            if ($addressId) {
                // Atualizar endereço existente
                $result = $this->addressModel->update($addressId, $data);
                
                if ($result) {
                    $_SESSION['success'] = 'Endereço atualizado com sucesso!';
                } else {
                    $_SESSION['error'] = 'Erro ao atualizar endereço. Por favor, tente novamente.';
                }
            } else {
                // Criar novo endereço
                $result = $this->addressModel->create($data);
                
                if ($result) {
                    $_SESSION['success'] = 'Endereço adicionado com sucesso!';
                } else {
                    $_SESSION['error'] = 'Erro ao adicionar endereço. Por favor, tente novamente.';
                }
            }
            
            header('Location: ' . BASE_URL . 'minha-conta/enderecos');
            exit;
        }
        
        // Dados para a view
        $data = [
            'address' => $address,
            'isEdit' => $addressId > 0,
            'csrfToken' => CsrfProtection::getFormField(), // Corrigido para usar o método correto
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ];
        
        // Limpar mensagens da sessão
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Renderizar view
        $this->renderView('address_form', $data);
    }
    
    /**
     * Exclui um endereço
     * 
     * @param array $params Parâmetros da rota, incluindo o ID do endereço
     */
    public function deleteAddress($params) {
        $userId = $_SESSION['user']['id'];
        $addressId = isset($params['id']) ? (int)$params['id'] : 0;
        
        // Validar ID do endereço
        if (!$addressId) {
            $_SESSION['error'] = 'Endereço não encontrado.';
            header('Location: ' . BASE_URL . 'minha-conta/enderecos');
            exit;
        }
        
        // Validar token CSRF (via URL para links de exclusão)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', ['required' => true]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta/enderecos');
            exit;
        }
        
        // Verificar se o endereço existe e pertence ao usuário
        $address = $this->addressModel->getAddress($addressId);
        
        if (!$address || $address['user_id'] != $userId) {
            $_SESSION['error'] = 'Endereço não encontrado ou não autorizado.';
            header('Location: ' . BASE_URL . 'minha-conta/enderecos');
            exit;
        }
        
        // Não permitir excluir o único endereço ou o endereço padrão
        $userAddresses = $this->addressModel->getUserAddresses($userId);
        
        if (count($userAddresses) <= 1) {
            $_SESSION['error'] = 'Você não pode excluir seu único endereço.';
            header('Location: ' . BASE_URL . 'minha-conta/enderecos');
            exit;
        }
        
        if ($address['is_default']) {
            $_SESSION['error'] = 'Você não pode excluir seu endereço padrão. Defina outro endereço como padrão primeiro.';
            header('Location: ' . BASE_URL . 'minha-conta/enderecos');
            exit;
        }
        
        // Excluir endereço
        $result = $this->addressModel->delete($addressId);
        
        if ($result) {
            $_SESSION['success'] = 'Endereço excluído com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir endereço. Por favor, tente novamente.';
        }
        
        header('Location: ' . BASE_URL . 'minha-conta/enderecos');
        exit;
    }
    
    /**
     * Renderiza uma view da área do usuário
     * 
     * @param string $view Nome da view
     * @param array $data Dados para a view
     */
    private function renderView($view, $data = []) {
        // Token CSRF
        $data['csrfToken'] = $data['csrfToken'] ?? CsrfProtection::getFormField(); // Corrigido para usar o método correto
        
        // Extrair variáveis para a view
        extract($data);
        
        // Verificar se a view existe
        $viewPath = VIEWS_PATH . '/user_account/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            require_once VIEWS_PATH . '/header.php';
            require_once $viewPath;
            require_once VIEWS_PATH . '/footer.php';
        } else {
            // View não encontrada, renderizar erro
            $_SESSION['error'] = 'Página não encontrada.';
            header('Location: ' . BASE_URL . 'minha-conta');
            exit;
        }
    }
}