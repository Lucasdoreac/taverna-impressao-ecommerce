<?php

class SecurityLog extends Model {
    protected $table = 'login_logs';
    
    public function log($type, $userId, $email, $status, $ipAddress) {
        return $this->db->insert($this->table, [
            'user_id' => $userId,
            'email' => $email,
            'action' => $type,
            'status' => $status,
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getRecentActivity($userId, $limit = 10) {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }
}