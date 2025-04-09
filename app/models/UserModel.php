<?php

class UserModel extends Model {
    protected $table = 'users';
    
    /**
     * Encontra um usuário pelo ID
     * 
     * @param int $id ID do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = Database::getInstance()->query($sql, ['id' => $id]);
        
        // Verificar se o resultado é um PDOStatement (ambiente de produção) ou array (local)
        if ($stmt instanceof PDOStatement) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result[0] ?? null;
        } else {
            // Se já for um array, usar diretamente
            return $stmt[0] ?? null;
        }
    }
    
    /**
     * Encontra um usuário pelo e-mail
     * 
     * @param string $email E-mail do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = Database::getInstance()->query($sql, ['email' => $email]);
        
        // Verificar se o resultado é um PDOStatement (ambiente de produção) ou array (local)
        if ($stmt instanceof PDOStatement) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result[0] ?? null;
        } else {
            // Se já for um array, usar diretamente
            return $stmt[0] ?? null;
        }
    }
    
    /**
     * Cria um novo usuário
     * 
     * @param array $data Dados do usuário
     * @return int|bool ID do usuário criado ou false em caso de falha
     */
    public function create($data) {
        // Verificar se o e-mail já está em uso
        if ($this->findByEmail($data['email'])) {
            return false;
        }
        
        // Hash da senha
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Preparar os dados para inserção
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'phone' => $data['phone'] ?? null,
            'role' => 'customer', // Por padrão, todos os novos usuários são clientes
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
        
        // Preparar a consulta SQL
        $fields = implode(', ', array_keys($userData));
        $placeholders = ':' . implode(', :', array_keys($userData));
        
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        
        try {
            // Executar a consulta
            $result = Database::getInstance()->query($sql, $userData);
            
            // Verificar se a consulta foi bem-sucedida
            if ($result !== false) {
                // Retornar o ID do novo usuário
                return Database::getInstance()->lastInsertId();
            } else {
                return false;
            }
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza os dados de um usuário
     * 
     * @param int $id ID do usuário
     * @param array $data Novos dados do usuário
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function update($id, $data) {
        // Remover campos que não devem ser atualizados
        unset($data['id'], $data['created_at'], $data['role']);
        
        // Se houver senha no array de dados, fazer hash
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Não atualizar senha se estiver vazia
            unset($data['password']);
        }
        
        // Adicionar timestamp de atualização
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Construir a consulta SQL
        $setClauses = [];
        foreach (array_keys($data) as $key) {
            $setClauses[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClauses);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        $data['id'] = $id;
        
        try {
            // Executar a consulta
            $result = Database::getInstance()->query($sql, $data);
            
            // Verificar se a consulta foi bem-sucedida
            return ($result !== false);
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout() {
        // Log security event before clearing session
        if (class_exists('SecurityLog')) {
            $securityLog = new SecurityLog();
            $securityLog->log(
                'logout',
                $_SESSION['user']['id'] ?? null,
                $_SESSION['user']['email'] ?? 'unknown',
                'success',
                $_SERVER['REMOTE_ADDR']
            );
        }
        
        // Clear session data using SessionManager
        if (class_exists('SessionManager')) {
            // Preservar certas chaves da sessão, como flash_messages
            $preserveKeys = ['flash_messages', 'csrf_token'];
            foreach ($_SESSION as $key => $value) {
                if (!in_array($key, $preserveKeys)) {
                    unset($_SESSION[$key]);
                }
            }
            
            // Regenerar ID da sessão
            session_regenerate_id(true);
            
            // Destruir a sessão
            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            session_destroy();
        } else {
            // Fallback para o método tradicional de logout
            // Limpar dados do usuário
            unset($_SESSION['user']);
            // Regenerar ID da sessão
            session_regenerate_id(true);
            // Destruir a sessão
            session_destroy();
        }
    }
}