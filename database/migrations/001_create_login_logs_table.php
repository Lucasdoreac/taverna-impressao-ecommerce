<?php

class CreateLoginLogsTable {
    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            email VARCHAR(255),
            action ENUM('login', 'logout') NOT NULL,
            status ENUM('success', 'failed') NOT NULL,
            ip_address VARCHAR(45),
            created_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        Database::getInstance()->query($sql);
    }

    public function down() {
        $sql = "DROP TABLE IF EXISTS login_logs;";
        Database::getInstance()->query($sql);
    }
}