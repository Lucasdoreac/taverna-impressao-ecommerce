<?php
/**
 * AccessControl - Classe para controle de acesso e autorização
 * 
 * Esta classe fornece métodos para verificar se um usuário tem permissão
 * para acessar ou manipular determinados recursos, prevenindo vulnerabilidades
 * como Insecure Direct Object References (IDOR - CWE-639).
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
class AccessControl {
    
    /**
     * Verifica se o usuário tem permissão para acessar um objeto específico
     * 
     * @param int $userId ID do usuário
     * @param int $objectId ID do objeto a ser acessado
     * @param string $objectType Tipo do objeto (ex: 'order', 'product', etc.)
     * @param string $permissionType Tipo de permissão (ex: 'view', 'edit', 'delete')
     * @return bool Verdadeiro se o usuário tiver permissão
     */
    public static function canUserAccessObject($userId, $objectId, $objectType, $permissionType = 'view') {
        // Se for um admin, permitir acesso total
        if (self::isUserAdmin($userId)) {
            return true;
        }
        
        // Verificar se o usuário tem a permissão específica
        $db = self::getDatabase();
        
        // Consultar se existe uma permissão explícita
        $sql = "SELECT COUNT(*) AS count 
                FROM user_permissions 
                WHERE user_id = :user_id 
                AND object_id = :object_id 
                AND object_type = :object_type
                AND permission_type = :permission_type";
                
        $params = [
            'user_id' => $userId,
            'object_id' => $objectId,
            'object_type' => $objectType,
            'permission_type' => $permissionType
        ];
        
        try {
            $result = $db->select($sql, $params);
            if (!empty($result) && isset($result[0]['count']) && $result[0]['count'] > 0) {
                return true;
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão: " . $e->getMessage());
            // Em caso de erro, negar acesso por segurança
            return false;
        }
        
        // Se não encontrou permissão específica, verificar permissões por tipo de objeto
        switch ($objectType) {
            case 'order':
                return self::canAccessOrder($userId, $objectId, $permissionType);
                
            case 'product':
                return self::canAccessProduct($userId, $objectId, $permissionType);
                
            case 'user':
                return self::canAccessUser($userId, $objectId, $permissionType);
                
            case 'customer_model':
                return self::canAccessCustomerModel($userId, $objectId, $permissionType);
                
            case 'print_job':
                return self::canAccessPrintJob($userId, $objectId, $permissionType);
                
            case 'report':
                return self::canAccessReport($userId, $objectId, $permissionType);
                
            default:
                // Para tipos desconhecidos, negar acesso por segurança
                return false;
        }
    }
    
    /**
     * Verifica se o usuário tem papel de admin
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se o usuário for admin
     */
    public static function isUserAdmin($userId) {
        // Verifica na sessão atual
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            return true;
        }
        
        // Verificar no banco de dados
        $db = self::getDatabase();
        $sql = "SELECT role FROM users WHERE id = :user_id";
        
        try {
            $result = $db->select($sql, ['user_id' => $userId]);
            return !empty($result) && isset($result[0]['role']) && $result[0]['role'] === 'admin';
        } catch (Exception $e) {
            error_log("Erro ao verificar papel do usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário é gerente ou supervisor
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se o usuário for gerente ou supervisor
     */
    public static function isUserManager($userId) {
        // Verifica na sessão atual
        if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'manager' || $_SESSION['user_role'] === 'supervisor')) {
            return true;
        }
        
        // Verificar no banco de dados
        $db = self::getDatabase();
        $sql = "SELECT role FROM users WHERE id = :user_id";
        
        try {
            $result = $db->select($sql, ['user_id' => $userId]);
            if (!empty($result) && isset($result[0]['role'])) {
                $role = $result[0]['role'];
                return $role === 'manager' || $role === 'supervisor';
            }
            return false;
        } catch (Exception $e) {
            error_log("Erro ao verificar papel do usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário pode acessar um pedido específico
     * 
     * @param int $userId ID do usuário
     * @param int $orderId ID do pedido
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se o usuário puder acessar o pedido
     */
    private static function canAccessOrder($userId, $orderId, $permissionType) {
        // Admins e gerentes podem acessar qualquer pedido
        if (self::isUserAdmin($userId) || self::isUserManager($userId)) {
            return true;
        }
        
        // Usuários regulares só podem acessar seus próprios pedidos
        $db = self::getDatabase();
        $sql = "SELECT COUNT(*) AS count FROM orders WHERE id = :order_id AND user_id = :user_id";
        
        try {
            $result = $db->select($sql, ['order_id' => $orderId, 'user_id' => $userId]);
            return !empty($result) && isset($result[0]['count']) && $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Erro ao verificar acesso ao pedido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário pode acessar um produto específico
     * 
     * @param int $userId ID do usuário
     * @param int $productId ID do produto
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se o usuário puder acessar o produto
     */
    private static function canAccessProduct($userId, $productId, $permissionType) {
        // Produtos geralmente são públicos para visualização
        if ($permissionType === 'view') {
            return true;
        }
        
        // Para edição, exclusão, etc., apenas admins e gerentes
        return self::isUserAdmin($userId) || self::isUserManager($userId);
    }
    
    /**
     * Verifica se o usuário pode acessar outro usuário
     * 
     * @param int $userId ID do usuário atual
     * @param int $targetUserId ID do usuário a ser acessado
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se o usuário puder acessar o usuário alvo
     */
    private static function canAccessUser($userId, $targetUserId, $permissionType) {
        // Usuários podem visualizar e editar seus próprios dados
        if ($userId === $targetUserId) {
            return true;
        }
        
        // Apenas admins podem acessar outros usuários
        return self::isUserAdmin($userId);
    }
    
    /**
     * Verifica se o usuário pode acessar um modelo 3D de cliente
     * 
     * @param int $userId ID do usuário
     * @param int $modelId ID do modelo
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se o usuário puder acessar o modelo
     */
    private static function canAccessCustomerModel($userId, $modelId, $permissionType) {
        // Admins e gerentes podem acessar qualquer modelo
        if (self::isUserAdmin($userId) || self::isUserManager($userId)) {
            return true;
        }
        
        // Usuários regulares só podem acessar seus próprios modelos
        $db = self::getDatabase();
        $sql = "SELECT COUNT(*) AS count FROM customer_models WHERE id = :model_id AND user_id = :user_id";
        
        try {
            $result = $db->select($sql, ['model_id' => $modelId, 'user_id' => $userId]);
            return !empty($result) && isset($result[0]['count']) && $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Erro ao verificar acesso ao modelo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário pode acessar um trabalho de impressão
     * 
     * @param int $userId ID do usuário
     * @param int $printJobId ID do trabalho de impressão
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se o usuário puder acessar o trabalho de impressão
     */
    private static function canAccessPrintJob($userId, $printJobId, $permissionType) {
        // Admins, gerentes e operadores de impressora podem acessar qualquer trabalho
        if (self::isUserAdmin($userId) || self::isUserManager($userId)) {
            return true;
        }
        
        // Verificar se o usuário é um operador de impressora
        $db = self::getDatabase();
        $sqlRole = "SELECT role FROM users WHERE id = :user_id";
        
        try {
            $result = $db->select($sqlRole, ['user_id' => $userId]);
            if (!empty($result) && isset($result[0]['role']) && $result[0]['role'] === 'printer_operator') {
                return true;
            }
        } catch (Exception $e) {
            error_log("Erro ao verificar papel do usuário: " . $e->getMessage());
        }
        
        // Usuários regulares só podem acessar seus próprios trabalhos de impressão
        $sql = "SELECT COUNT(*) AS count FROM print_queue 
                WHERE id = :print_job_id 
                AND order_id IN (SELECT id FROM orders WHERE user_id = :user_id)";
        
        try {
            $result = $db->select($sql, ['print_job_id' => $printJobId, 'user_id' => $userId]);
            return !empty($result) && isset($result[0]['count']) && $result[0]['count'] > 0;
        } catch (Exception $e) {
            error_log("Erro ao verificar acesso ao trabalho de impressão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário pode acessar um relatório
     * 
     * @param int $userId ID do usuário
     * @param int $reportId ID do relatório
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se o usuário puder acessar o relatório
     */
    private static function canAccessReport($userId, $reportId, $permissionType) {
        // Apenas admins e gerentes podem acessar relatórios
        return self::isUserAdmin($userId) || self::isUserManager($userId);
    }
    
    /**
     * Obtém a instância do banco de dados
     * 
     * @return object Instância do banco de dados
     */
    private static function getDatabase() {
        // Verificar se Database já foi incluído
        if (!class_exists('Database')) {
            require_once APP_PATH . '/helpers/Database.php';
        }
        
        // Obter instância do banco de dados
        return Database::getInstance();
    }
    
    /**
     * Inicializa as permissões do usuário na sessão
     * 
     * @param int $userId ID do usuário
     * @return void
     */
    public static function initUserPermissions($userId) {
        // Obter papel do usuário
        $db = self::getDatabase();
        $sql = "SELECT role FROM users WHERE id = :user_id";
        
        try {
            $result = $db->select($sql, ['user_id' => $userId]);
            if (!empty($result) && isset($result[0]['role'])) {
                $_SESSION['user_role'] = $result[0]['role'];
            }
        } catch (Exception $e) {
            error_log("Erro ao inicializar permissões do usuário: " . $e->getMessage());
        }
    }
    
    /**
     * Adiciona permissão específica para um usuário
     * 
     * @param int $userId ID do usuário
     * @param int $objectId ID do objeto
     * @param string $objectType Tipo do objeto
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se a permissão foi adicionada com sucesso
     */
    public static function addPermission($userId, $objectId, $objectType, $permissionType) {
        $db = self::getDatabase();
        
        // Verificar se a permissão já existe
        $sql = "SELECT COUNT(*) AS count FROM user_permissions 
                WHERE user_id = :user_id 
                AND object_id = :object_id 
                AND object_type = :object_type
                AND permission_type = :permission_type";
                
        $params = [
            'user_id' => $userId,
            'object_id' => $objectId,
            'object_type' => $objectType,
            'permission_type' => $permissionType
        ];
        
        try {
            $result = $db->select($sql, $params);
            if (!empty($result) && isset($result[0]['count']) && $result[0]['count'] > 0) {
                // Permissão já existe
                return true;
            }
            
            // Adicionar permissão
            $sql = "INSERT INTO user_permissions (user_id, object_id, object_type, permission_type, created_at) 
                    VALUES (:user_id, :object_id, :object_type, :permission_type, NOW())";
                    
            return $db->insert($sql, $params);
        } catch (Exception $e) {
            error_log("Erro ao adicionar permissão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove permissão específica de um usuário
     * 
     * @param int $userId ID do usuário
     * @param int $objectId ID do objeto
     * @param string $objectType Tipo do objeto
     * @param string $permissionType Tipo de permissão
     * @return bool Verdadeiro se a permissão foi removida com sucesso
     */
    public static function removePermission($userId, $objectId, $objectType, $permissionType) {
        $db = self::getDatabase();
        
        $sql = "DELETE FROM user_permissions 
                WHERE user_id = :user_id 
                AND object_id = :object_id 
                AND object_type = :object_type
                AND permission_type = :permission_type";
                
        $params = [
            'user_id' => $userId,
            'object_id' => $objectId,
            'object_type' => $objectType,
            'permission_type' => $permissionType
        ];
        
        try {
            return $db->delete($sql, $params);
        } catch (Exception $e) {
            error_log("Erro ao remover permissão: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todas as permissões de um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Lista de permissões do usuário
     */
    public static function getUserPermissions($userId) {
        $db = self::getDatabase();
        
        $sql = "SELECT object_id, object_type, permission_type, created_at 
                FROM user_permissions 
                WHERE user_id = :user_id";
                
        try {
            return $db->select($sql, ['user_id' => $userId]);
        } catch (Exception $e) {
            error_log("Erro ao obter permissões do usuário: " . $e->getMessage());
            return [];
        }
    }
}
