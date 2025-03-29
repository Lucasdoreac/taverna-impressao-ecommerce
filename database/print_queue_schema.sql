-- Esquema para o sistema de fila de impressão 3D
-- A ser executado após unified_schema_3d.sql

-- Tabela de impressoras disponíveis
CREATE TABLE IF NOT EXISTS printers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  max_width INT NOT NULL COMMENT 'Largura máxima em mm',
  max_depth INT NOT NULL COMMENT 'Profundidade máxima em mm',
  max_height INT NOT NULL COMMENT 'Altura máxima em mm',
  filament_types VARCHAR(255) COMMENT 'Tipos de filamento compatíveis, separados por vírgula',
  status ENUM('available', 'printing', 'maintenance', 'offline') DEFAULT 'available',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de itens na fila de impressão
CREATE TABLE IF NOT EXISTS print_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  order_item_id INT NOT NULL,
  printer_id INT NULL COMMENT 'NULL se ainda não alocado a uma impressora',
  customer_model_id INT NULL COMMENT 'ID do modelo personalizado, se aplicável',
  product_id INT NOT NULL,
  priority INT DEFAULT 5 COMMENT 'Prioridade de 1 (mais alta) a 10 (mais baixa)',
  status ENUM('pending', 'scheduled', 'printing', 'paused', 'completed', 'failed', 'canceled') DEFAULT 'pending',
  estimated_print_time_hours DECIMAL(5,2) NOT NULL COMMENT 'Tempo estimado de impressão em horas',
  actual_print_time_hours DECIMAL(5,2) NULL COMMENT 'Tempo real de impressão em horas',
  filament_type ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  filament_color_id INT NULL,
  filament_usage_grams INT NOT NULL COMMENT 'Quantidade estimada de filamento em gramas',
  actual_filament_usage_grams INT NULL COMMENT 'Quantidade real de filamento usado em gramas',
  scale VARCHAR(50) NOT NULL COMMENT 'Escala da impressão',
  scheduled_start_date DATETIME NULL COMMENT 'Data/hora programada para início',
  actual_start_date DATETIME NULL COMMENT 'Data/hora real de início',
  actual_end_date DATETIME NULL COMMENT 'Data/hora real de término',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
  FOREIGN KEY (printer_id) REFERENCES printers(id) ON DELETE SET NULL,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (customer_model_id) REFERENCES customer_models(id) ON DELETE SET NULL,
  FOREIGN KEY (filament_color_id) REFERENCES filament_colors(id) ON DELETE SET NULL
);

-- Tabela de histórico de eventos da fila
CREATE TABLE IF NOT EXISTS print_queue_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  print_queue_id INT NOT NULL,
  event_type ENUM('status_change', 'printer_assigned', 'priority_change', 'note_added', 'error', 'start', 'finish', 'pause', 'resume') NOT NULL,
  previous_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NULL,
  previous_printer_id INT NULL,
  new_printer_id INT NULL,
  previous_priority INT NULL,
  new_priority INT NULL,
  description TEXT NOT NULL,
  created_by INT NULL COMMENT 'ID do usuário que causou o evento',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (print_queue_id) REFERENCES print_queue(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de notificações de impressão
CREATE TABLE IF NOT EXISTS print_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  print_queue_id INT NOT NULL,
  notification_type ENUM('status_change', 'scheduled', 'started', 'completed', 'failed', 'general') NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (print_queue_id) REFERENCES print_queue(id) ON DELETE CASCADE
);

-- Inserir dados iniciais para impressoras
INSERT INTO printers (name, model, max_width, max_depth, max_height, filament_types, status, notes) VALUES
('Ender 3 #1', 'Creality Ender 3', 220, 220, 250, 'PLA,PETG,TPU', 'available', 'Impressora principal para miniaturas pequenas'),
('Ender 3 Pro #1', 'Creality Ender 3 Pro', 220, 220, 250, 'PLA,PETG,ABS,TPU', 'available', 'Impressora para peças de precisão'),
('CR-10 #1', 'Creality CR-10', 300, 300, 400, 'PLA,PETG,ABS', 'available', 'Impressora para terrenos grandes e peças de cenário');

-- Alterar tabela de produtos para incluir campo is_tested
ALTER TABLE products 
ADD COLUMN is_tested BOOLEAN DEFAULT FALSE 
COMMENT 'Indica se o produto já foi testado e está disponível para pronta entrega' AFTER is_customizable,
ADD COLUMN stock INT DEFAULT 0 
COMMENT 'Quantidade disponível para pronta entrega (produtos testados)' AFTER is_tested;

-- Alterar a tabela order_items para incluir campo is_stock_item
ALTER TABLE order_items
ADD COLUMN is_stock_item BOOLEAN DEFAULT FALSE
COMMENT 'Indica se o item vem do estoque ou será impresso sob demanda' AFTER print_time_hours;