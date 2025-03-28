-- Adicionar tabela para salvar configurações de personalização
CREATE TABLE IF NOT EXISTS saved_customizations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  customization_data TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY user_product_name (user_id, product_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar índices para melhorar a performance
CREATE INDEX idx_saved_customizations_user ON saved_customizations(user_id);
CREATE INDEX idx_saved_customizations_product ON saved_customizations(product_id);

-- Atualizar tabela de produtos para garantir compatibilidade
ALTER TABLE products 
  ADD COLUMN customization_options TEXT NULL AFTER is_customizable,
  ADD COLUMN customization_price_adjustment DECIMAL(10,2) DEFAULT 0 AFTER customization_options;
