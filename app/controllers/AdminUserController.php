<?php
/**
 * AdminUserController - Controlador para gerenciamento de usuários no painel administrativo
 */
class AdminUserController {
    private $userModel;
    
    public function __construct() {
        // Verificar se é administrador
        AdminHelper::checkAdminAccess();
        
        // Carregar modelo de usuário
        $this->userModel = new UserModel();
    }
    
    /**
     * Exibe a página de listagem de usuários
     */
    public function index() {
        // Definir paginação
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = 10;
        
        // Obter parâmetros de filtro
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        
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
     */
    public function view($params) {
        $userId = $params['id'] ?? null;
        
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
     */
    public function edit($params) {
        $userId = $params['id'] ?? null;
        
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
        
        // Obter dados do formulário
        $userId = isset($_POST['id']) ? intval($_POST['id']) : null;
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'role' => $_POST['role'] ?? 'customer'
        ];
        
        // Validar dados
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'O nome é obrigatório.';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'O e-mail informado é inválido.';
        }
        
        // Verificar se é um novo usuário ou atualização
        if (!$userId) {
            // Novo usuário - senha obrigatória
            if (empty($_POST['password'])) {
                $errors[] = 'A senha é obrigatória para novos usuários.';
            } elseif (strlen($_POST['password']) < 6) {
                $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
            } else {
                $data['password'] = $_POST['password'];
            }
        } else {
            // Atualização - senha opcional
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 6) {
                    $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
                } else {
                    $data['password'] = $_POST['password'];
                }
            }
        }
        
        // Se houver erros, voltar para o formulário
        if ($errors) {
            $_SESSION['error'] = implode('<br>', $errors);
            
            if ($userId) {
                header('Location: ' . BASE_URL . 'admin/usuarios/edit/' . $userId);
            } else {
                header('Location: ' . BASE_URL . 'admin/usuarios/create');
            }
            exit;
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
     */
    public function toggleStatus($params) {
        $userId = $params['id'] ?? null;
        
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
        
        $userId = isset($_POST['id']) ? intval($_POST['id']) : null;
        
        if (!$userId) {
            $_SESSION['error'] = 'ID de usuário não especificado.';
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
