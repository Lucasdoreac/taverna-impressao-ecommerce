-- Tabelas para o sistema de pagamento
-- Data: 2025-04-08

-- Tabela de configurações de gateways
CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 0,
    is_sandbox TINYINT(1) DEFAULT 1,
    config_json LONGTEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de métodos de pagamento
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_id INT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    frontend_template VARCHAR(100),
    config_json LONGTEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY (code),
    FOREIGN KEY (gateway_id) REFERENCES payment_gateways(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de transações de pagamento
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    gateway_name VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'BRL',
    payment_method VARCHAR(50),
    additional_data LONGTEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX (order_id),
    INDEX (transaction_id),
    INDEX (status),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tentativas de pagamento (logs)
CREATE TABLE IF NOT EXISTS payment_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    gateway VARCHAR(50),
    transaction_id VARCHAR(255),
    status VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    success TINYINT(1) DEFAULT 0,
    additional_data LONGTEXT,
    created_at DATETIME NOT NULL,
    INDEX (order_id),
    INDEX (transaction_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de webhooks recebidos
CREATE TABLE IF NOT EXISTS payment_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway VARCHAR(50) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    transaction_id VARCHAR(255),
    request_data LONGTEXT NOT NULL,
    process_result LONGTEXT,
    success TINYINT(1) DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX (gateway),
    INDEX (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de reembolsos
CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(255) NOT NULL,
    refund_id VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    reason TEXT,
    status VARCHAR(50) NOT NULL,
    additional_data LONGTEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX (transaction_id),
    INDEX (refund_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão para o MercadoPago
INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES
('payment.mercadopago.config', '{"active": false, "sandbox": true, "display_name": "MercadoPago", "access_token": "TEST-0000000000000000-000000-00000000000000000000000000000000-000000000", "public_key": "TEST-00000000-0000-0000-0000-000000000000", "payment_methods": ["credit_card", "boleto", "pix"]}', NOW(), NOW());

-- Atualizar tabela de pedidos para incluir campos relacionados a pagamento
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS payment_transaction_id VARCHAR(255) AFTER payment_status,
ADD COLUMN IF NOT EXISTS payment_gateway VARCHAR(50) AFTER payment_transaction_id,
ADD COLUMN IF NOT EXISTS payment_details LONGTEXT AFTER payment_gateway;

-- Adicionar índice para melhor performance
ALTER TABLE orders
ADD INDEX idx_payment_transaction (payment_transaction_id);
