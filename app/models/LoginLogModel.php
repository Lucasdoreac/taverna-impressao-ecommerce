<?php

class LoginLogModel extends Model {
    protected $table = 'login_logs';

    public function logLogin($userId, $email, $success, $ipAddress) {
        $data = [
            'user_id' => $userId,
            'email' => $email,
            'action' => 'login',
            'status' => $success ? 'success' : 'failed',
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert($this->table, $data);
    }

    public function logLogout($userId, $email, $ipAddress) {
        $data = [
            'user_id' => $userId,
            'email' => $email,
            'action' => 'logout',
            'status' => 'success',
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert($this->table, $data);
    }

    public function getLoginHistory($userId, $limit = 10) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        return $this->db->select($sql, ['user_id' => $userId, 'limit' => $limit]);
    }
    
    public function getLogs($offset = 0, $limit = 20) {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        return $this->db->select($sql, ['limit' => $limit, 'offset' => $offset]);
    }
    
    public function getTotalLogs() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->select($sql);
        return $result[0]['total'] ?? 0;
    }
    
    public function getAllLogs() {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->select($sql);
    }
}