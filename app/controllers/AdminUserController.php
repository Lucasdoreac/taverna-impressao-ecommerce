<?php
/**
 * AdminUserController - Controlador para gerenciamento de usuários no painel administrativo
 * 
 * @package Taverna\Controllers
 * @author Taverna da Impressão
 * @version 1.1.0
 */
class AdminUserController {
    // Implementação do trait de validação
    use InputValidationTrait;
    
    private $userModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Verificar se é administrador
        AdminHelper::checkAdminAccess();
        
        // Carregar modelo de usuário
        $this->userModel = new UserModel();
        
        // Carregar InputValidationTrait
        require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
    }
    
    /**
     * Exibe a página de listagem de usuários
     */
    public function index() {
        // Definir paginação com validação
        $page = $this->getValidatedParam('page', 'int', ['default' => 1, 'min' => 1]);
        $limit = 10;
        
        // Obter parâmetros de filtro com validação
        $search = $this->getValidatedParam('search', 'string', ['allowEmpty' => true]);
        $role = $this->getValidatedParam('role', 'string', ['allowEmpty' => true]);
        
        // Buscar usuários
        $usersData = $this->userModel->getAdminList($page, $limit, $search, $role);
        
        // Dados para a view
        $data = [
            'users' => $usersData['items'],
            'pagination' => [
                'total' => $usersData['total'],
                'currentPage' => $usersData['currentPage'],
                'lastPage' => $usersData['lastPage'],
                'perPage' => $usersData['perPage'],
                'from' => $usersData['from'],
                'to' => $usersData['to'],
            ],
            'search' => $search,
            'role' => $role
        ];
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/users/index.php';
    }
    
    /**
     * Exibe a página de detalhes de um usuário
     * 
     * @param array $params Parâmetros da rota
     */
    public function view($params) {
        $userId = isset($params['id']) ? (int)$params['id'] : null;
        
        if (!$userId) {
            $_SESSION['error'] = 'ID de usuário não especificado.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Buscar usuário
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Buscar endereços do usuário
        $addressModel = new AddressModel();
        $addresses = $addressModel->getAddressesByUser($userId);
        
        // Buscar pedidos do usuário
        $orderModel = new OrderModel();
        $orders = $orderModel->getOrdersByUser($userId);
        
        // Dados para a view
        $data = [
            'user' => $user,
            'addresses' => $addresses,
            'orders' => $orders
        ];
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/users/view.php';
    }
    
    /**
     * Exibe o formulário para adicionar um novo usuário
     */
    public function create() {
        // Renderizar view
        require_once VIEWS_PATH . '/admin/users/form.php';
    }
    
    /**
     * Exibe o formulário para editar um usuário existente
     * 
     * @param array $params Parâmetros da rota
     */
    public function edit($params) {
        $userId = isset($params['id']) ? (int)$params['id'] : null;
        
        if (!$userId) {
            $_SESSION['error'] = 'ID de usuário não especificado.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Buscar usuário
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/users/form.php';
    }
    
    /**
     * Processa o formulário para salvar um usuário (novo ou existente)
     */
    public function save() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Validar token CSRF
        if (!CsrfProtection::validateRequest()) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Obter dados do formulário com validação
        $userId = $this->postValidatedParam('id', 'int', ['default' => null]);
        
        // Validar campos do formulário
        $validations = [
            'name' => ['type' => 'string', 'required' => true, 'minLength' => 3, 'maxLength' => 100],
            'email' => ['type' => 'email', 'required' => true, 'maxLength' => 255],
            'phone' => ['type' => 'string', 'required' => false, 'maxLength' => 20],
            'role' => ['type' => 'string', 'required' => true, 'options' => ['customer', 'admin', 'editor']]
        ];
        
        $data = $this->postValidatedParams($validations);
        
        // Se não há dados validados ou há erros, retornar erro
        if (!$data || $this->hasValidationErrors()) {
            $_SESSION['error'] = implode('<br>', $this->getValidationErrors());
            
            if ($userId) {
                header('Location: ' . BASE_URL . 'admin/usuarios/edit/' . $userId);
            } else {
                header('Location: ' . BASE_URL . 'admin/usuarios/create');
            }
            exit;
        }
        
        // Verificar status ativo
        $data['is_active'] = $this->postValidatedParam('is_active', 'bool', ['default' => false]) ? 1 : 0;
        
        // Verificar se é um novo usuário ou atualização
        if (!$userId) {
            // Novo usuário - senha obrigatória
            $password = $this->postValidatedParam('password', 'string', ['required' => true, 'minLength' => 6]);
            
            if (!$password || $this->hasValidationErrors()) {
                $_SESSION['error'] = implode('<br>', $this->getValidationErrors());
                header('Location: ' . BASE_URL . 'admin/usuarios/create');
                exit;
            }
            
            $data['password'] = $password;
        } else {
            // Atualização - senha opcional
            $password = $this->postValidatedParam('password', 'string', ['required' => false]);
            
            if ($password) {
                // Validar comprimento mínimo apenas se senha foi fornecida
                $passwordLength = $this->postValidatedParam('password', 'string', ['minLength' => 6]);
                
                if (!$passwordLength || $this->hasValidationErrors()) {
                    $_SESSION['error'] = implode('<br>', $this->getValidationErrors());
                    header('Location: ' . BASE_URL . 'admin/usuarios/edit/' . $userId);
                    exit;
                }
                
                $data['password'] = $password;
            }
        }
        
        // Salvar usuário
        if ($userId) {
            // Atualizar usuário existente
            $result = $this->userModel->updateProfile($userId, $data);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Usuário atualizado com sucesso.';
                header('Location: ' . BASE_URL . 'admin/usuarios/view/' . $userId);
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                header('Location: ' . BASE_URL . 'admin/usuarios/edit/' . $userId);
                exit;
            }
        } else {
            // Criar novo usuário
            $result = $this->userModel->register($data);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Usuário criado com sucesso.';
                header('Location: ' . BASE_URL . 'admin/usuarios/view/' . $result['user_id']);
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                header('Location: ' . BASE_URL . 'admin/usuarios/create');
                exit;
            }
        }
    }
    
    /**
     * Altera o status (ativo/inativo) de um usuário
     * 
     * @param array $params Parâmetros da rota
     */
    public function toggleStatus($params) {
        $userId = isset($params['id']) ? (int)$params['id'] : null;
        
        if (!$userId) {
            $_SESSION['error'] = 'ID de usuário não especificado.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Validar token CSRF (para links, é recomendado usar o token na URL)
        $csrfToken = $this->getValidatedParam('csrf_token', 'string', ['required' => true]);
        
        if (!$csrfToken || !CsrfProtection::validateToken($csrfToken)) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Buscar usuário
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Não permitir desativar o próprio usuário
        if ($userId == $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Você não pode desativar seu próprio usuário.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Alternar status
        $newStatus = $user['is_active'] ? 0 : 1;
        $this->userModel->update($userId, ['is_active' => $newStatus]);
        
        $_SESSION['success'] = $newStatus ? 'Usuário ativado com sucesso.' : 'Usuário desativado com sucesso.';
        header('Location: ' . BASE_URL . 'admin/usuarios');
        exit;
    }
    
    /**
     * Exclui um usuário
     */
    public function delete() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Validar token CSRF
        if (!CsrfProtection::validateRequest()) {
            $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Validar ID do usuário
        $userId = $this->postValidatedParam('id', 'int', ['required' => true]);
        
        if (!$userId || $this->hasValidationErrors()) {
            $_SESSION['error'] = 'ID de usuário inválido.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Não permitir excluir o próprio usuário
        if ($userId == $_SESSION['user']['id']) {
            $_SESSION['error'] = 'Você não pode excluir seu próprio usuário.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Verificar se o usuário tem pedidos
        $orderModel = new OrderModel();
        $orders = $orderModel->getOrdersByUser($userId);
        
        if (count($orders) > 0) {
            $_SESSION['error'] = 'Não é possível excluir este usuário pois ele possui pedidos associados.';
            header('Location: ' . BASE_URL . 'admin/usuarios');
            exit;
        }
        
        // Excluir endereços do usuário
        $addressModel = new AddressModel();
        $addressModel->deleteByUser($userId);
        
        // Excluir usuário
        $this->userModel->delete($userId);
        
        $_SESSION['success'] = 'Usuário excluído com sucesso.';
        header('Location: ' . BASE_URL . 'admin/usuarios');
        exit;
    }
}