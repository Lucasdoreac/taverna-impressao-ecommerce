<?php
/**
 * UserModel - Modelo para usuários
 */
class UserModel extends Model {
    protected $table = 'users';
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'role', 'is_active'
    ];
    
    /**
     * Busca um usuário pelo e-mail
     */
    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }
    
    /**
     * Faz o login do usuário
     */
    public function login($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        // Verificar se o usuário está ativo
        if (isset($user['is_active']) && !$user['is_active']) {
            return false;
        }
        
        // Remover senha da sessão
        unset($user['password']);
        
        // Guardar na sessão
        $_SESSION['user'] = $user;
        $_SESSION['user_logged_in'] = true;
        
        return true;
    }
    
    /**
     * Registra um novo usuário
     */
    public function register($data) {
        // Verificar se e-mail já existe
        $existingUser = $this->findByEmail($data['email']);
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Este e-mail já está cadastrado.'
            ];
        }
        
        // Hash da senha
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Definir papel como 'customer' (cliente) se não for especificado
        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }

        // Definir como ativo por padrão
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        
        // Inserir no banco
        $userId = $this->create($data);
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => 'Erro ao cadastrar usuário. Tente novamente.'
            ];
        }
        
        // Obter usuário cadastrado
        $user = $this->find($userId);
        
        // Se a requisição veio do painel administrativo, não fazer login
        if (!isset($data['admin_request'])) {
            // Remover senha da sessão
            unset($user['password']);
            
            // Guardar na sessão
            $_SESSION['user'] = $user;
            $_SESSION['user_logged_in'] = true;
        }
        
        return [
            'success' => true,
            'message' => 'Cadastro realizado com sucesso!',
            'user_id' => $userId
        ];
    }
    
    /**
     * Atualiza o perfil do usuário
     */
    public function updateProfile($userId, $data) {
        // Se estiver alterando e-mail, verificar se já existe
        if (isset($data['email'])) {
            $existingUser = $this->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Este e-mail já está sendo usado por outro usuário.'
                ];
            }
        }
        
        // Se estiver alterando senha
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Se não estiver alterando senha, remover do array
            unset($data['password']);
        }
        
        // Atualizar dados
        $this->update($userId, $data);
        
        // Atualizar dados da sessão
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userId) {
            $user = $this->find($userId);
            unset($user['password']);
            $_SESSION['user'] = $user;
        }
        
        return [
            'success' => true,
            'message' => 'Perfil atualizado com sucesso!'
        ];
    }
    
    /**
     * Verifica se o usuário tem permissão de administrador
     */
    public function isAdmin($userId = null) {
        if (!$userId && isset($_SESSION['user'])) {
            return $_SESSION['user']['role'] === 'admin';
        }
        
        if ($userId) {
            $user = $this->find($userId);
            return $user && $user['role'] === 'admin';
        }
        
        return false;
    }
    
    /**
     * Faz logout do usuário
     */
    public function logout() {
        unset($_SESSION['user']);
        unset($_SESSION['user_logged_in']);
        
        // Regenerar ID da sessão por segurança
        session_regenerate_id(true);
    }
    
    /**
     * Inicia processo de recuperação de senha
     */
    public function initiatePasswordRecovery($email) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'E-mail não encontrado em nossa base de dados.'
            ];
        }
        
        // Gerar token de recuperação
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Salvar token no banco
        $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
        Database::getInstance()->query($sql, [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires
        ]);
        
        // Construir link de recuperação
        $recoveryLink = BASE_URL . 'recuperar-senha/' . $token;
        
        // Enviar e-mail (implementação básica)
        $to = $user['email'];
        $subject = "Recuperação de Senha - " . STORE_NAME;
        $message = "Olá {$user['name']},\n\n";
        $message .= "Recebemos uma solicitação de recuperação de senha para sua conta.\n";
        $message .= "Para definir uma nova senha, clique no link abaixo:\n\n";
        $message .= $recoveryLink . "\n\n";
        $message .= "Este link é válido por 1 hora.\n\n";
        $message .= "Se você não solicitou esta mudança, por favor ignore este e-mail.\n\n";
        $message .= "Atenciosamente,\n";
        $message .= STORE_NAME;
        
        $headers = "From: " . STORE_EMAIL . "\r\n";
        
        mail($to, $subject, $message, $headers);
        
        return [
            'success' => true,
            'message' => 'Um e-mail com instruções para recuperação de senha foi enviado.'
        ];
    }
    
    /**
     * Redefine a senha usando token de recuperação
     */
    public function resetPassword($token, $password) {
        // Verificar token
        $sql = "SELECT * FROM password_resets WHERE token = :token AND expires_at > NOW() LIMIT 1";
        $result = Database::getInstance()->select($sql, ['token' => $token]);
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Token inválido ou expirado.'
            ];
        }
        
        $userId = $result[0]['user_id'];
        
        // Atualizar senha
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->update($userId, ['password' => $hashedPassword]);
        
        // Invalidar token
        $sql = "DELETE FROM password_resets WHERE token = :token";
        Database::getInstance()->query($sql, ['token' => $token]);
        
        return [
            'success' => true,
            'message' => 'Senha redefinida com sucesso. Você já pode fazer login com sua nova senha.'
        ];
    }

    /**
     * Obtém a lista de usuários para o painel administrativo
     */
    public function getAdminList($page = 1, $limit = 10, $search = '', $role = '') {
        $offset = ($page - 1) * $limit;
        
        // Construir condição SQL
        $conditions = [];
        $params = [];
        
        if ($search) {
            $conditions[] = "(name LIKE :search OR email LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        if ($role) {
            $conditions[] = "role = :role";
            $params['role'] = $role;
        }
        
        $whereClause = count($conditions) > 0 ? implode(' AND ', $conditions) : '1=1';
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $countResult = $this->db()->select($countSql, $params);
        $total = $countResult[0]['total'];
        
        // Buscar registros paginados
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY name ASC LIMIT {$offset}, {$limit}";
        $items = $this->db()->select($sql, $params);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit),
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total)
        ];
    }

    /**
     * Obtém estatísticas de usuários para o dashboard
     */
    public function getStats() {
        $stats = [
            'total' => 0,
            'admins' => 0,
            'customers' => 0,
            'active' => 0,
            'inactive' => 0,
            'recent' => []
        ];
        
        // Total de usuários
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db()->select($sql);
        $stats['total'] = $result[0]['total'];
        
        // Usuários por papel
        $sql = "SELECT role, COUNT(*) as count FROM {$this->table} GROUP BY role";
        $result = $this->db()->select($sql);
        
        foreach ($result as $row) {
            if ($row['role'] == 'admin') {
                $stats['admins'] = $row['count'];
            } else if ($row['role'] == 'customer') {
                $stats['customers'] = $row['count'];
            }
        }
        
        // Usuários ativos/inativos
        $sql = "SELECT is_active, COUNT(*) as count FROM {$this->table} GROUP BY is_active";
        $result = $this->db()->select($sql);
        
        foreach ($result as $row) {
            if ($row['is_active']) {
                $stats['active'] = $row['count'];
            } else {
                $stats['inactive'] = $row['count'];
            }
        }
        
        // Usuários recentes
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT 5";
        $stats['recent'] = $this->db()->select($sql);
        
        return $stats;
    }
}