-- Esquema do banco de dados atualizado para TAVERNA DA IMPRESSÃO (Impressão 3D)
  
-- Tabela de usuários (mantida da versão anterior)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  role ENUM('customer', 'admin') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de endereços (mantida da versão anterior)
CREATE TABLE addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  address VARCHAR(255) NOT NULL,
  number VARCHAR(20) NOT NULL,
  complement VARCHAR(100),
  neighborhood VARCHAR(100) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(2) NOT NULL,
  zipcode VARCHAR(9) NOT NULL,
  is_default BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de categorias (adaptada para impressão 3D)
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  image VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tabela de produtos (adaptada para impressão 3D)
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  short_description VARCHAR(255),
  price DECIMAL(10,2) NOT NULL,
  sale_price DECIMAL(10,2),
  print_time_hours DECIMAL(5,2) NOT NULL COMMENT 'Tempo estimado de impressão em horas',
  filament_type ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  filament_usage_grams INT NOT NULL COMMENT 'Quantidade estimada de filamento em gramas',
  dimensions VARCHAR(50) COMMENT 'Dimensões do produto impresso (AxLxP em mm)',
  scale VARCHAR(50) DEFAULT '28mm' COMMENT 'Escala padrão da miniatura',
  sku VARCHAR(50) UNIQUE,
  model_file VARCHAR(255) COMMENT 'Caminho para o arquivo STL/OBJ',
  is_featured BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  is_customizable BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Tabela de imagens dos produtos (mantida da versão anterior)
CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  is_main BOOLEAN DEFAULT FALSE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabela de opções de personalização (adaptada para impressão 3D)
CREATE TABLE customization_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  type ENUM('upload', 'text', 'select', 'color', 'scale') NOT NULL,
  required BOOLEAN DEFAULT FALSE,
  options TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Tabela de arquivos 3D do cliente (NOVA)
CREATE TABLE customer_models (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_size INT NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  status ENUM('pending_validation', 'approved', 'rejected') DEFAULT 'pending_validation',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela de carrinhos (mantida da versão anterior)
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  session_id VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabela de itens do carrinho (adaptada para impressão 3D)
CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  selected_scale VARCHAR(50) COMMENT 'Escala selecionada pelo cliente',
  selected_filament ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  selected_color VARCHAR(50) COMMENT 'Cor selecionada pelo cliente',
  customer_model_id INT NULL COMMENT 'ID do modelo enviado pelo cliente, se aplicável',
  customization_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (customer_model_id) REFERENCES customer_models(id) ON DELETE SET NULL
);

-- Tabela de pedidos (adaptada para impressão 3D)
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  order_number VARCHAR(20) NOT NULL UNIQUE,
  status ENUM('pending', 'validating', 'printing', 'finishing', 'shipped', 'delivered', 'canceled') DEFAULT 'pending',
  estimated_print_time_hours DECIMAL(5,2) COMMENT 'Tempo total estimado de impressão',
  print_start_date DATETIME COMMENT 'Data/hora de início da impressão',
  print_finish_date DATETIME COMMENT 'Data/hora de término da impressão',
  payment_method ENUM('credit_card', 'boleto', 'pix') NOT NULL,
  payment_status ENUM('pending', 'paid', 'refunded', 'canceled') DEFAULT 'pending',
  shipping_address_id INT NULL,
  shipping_method VARCHAR(50),
  shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  notes TEXT,
  tracking_code VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE SET NULL
);

-- Tabela de itens do pedido (adaptada para impressão 3D)
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  selected_scale VARCHAR(50) COMMENT 'Escala selecionada pelo cliente',
  selected_filament ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  selected_color VARCHAR(50) COMMENT 'Cor selecionada pelo cliente',
  customer_model_id INT NULL COMMENT 'ID do modelo enviado pelo cliente, se aplicável',
  print_time_hours DECIMAL(5,2) COMMENT 'Tempo de impressão deste item',
  customization_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (customer_model_id) REFERENCES customer_models(id) ON DELETE SET NULL
);

-- Tabela de cores de filamento disponíveis (NOVA)
CREATE TABLE filament_colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  hex_code VARCHAR(7) NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  filament_type ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabelas mantidas da versão anterior
CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  type ENUM('percentage', 'fixed') NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  min_order_value DECIMAL(10,2) DEFAULT 0,
  starts_at DATETIME,
  expires_at DATETIME,
  max_uses INT DEFAULT NULL,
  uses_count INT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(50) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  setting_group VARCHAR(50) DEFAULT 'general',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('store_name', 'TAVERNA DA IMPRESSÃO', 'general'),
('store_email', 'contato@tavernaimpressao.com.br', 'general'),
('store_phone', '(00) 0000-0000', 'general'),
('store_address', 'Sua Rua, 123', 'general'),
('store_description', 'Miniaturas e acessórios para RPG e jogos de tabuleiro impressos em 3D sob demanda', 'general'),
('currency', 'BRL', 'general'),
('currency_symbol', 'R$', 'general'),
('meta_title', 'TAVERNA DA IMPRESSÃO - Miniaturas 3D para RPG e Board Games', 'seo'),
('meta_description', 'Loja especializada em miniaturas e acessórios para RPG e jogos de tabuleiro impressos em 3D sob demanda. Personalize suas aventuras!', 'seo'),
('shipping_methods', '[{"name":"PAC","price":15.00},{"name":"SEDEX","price":25.00}]', 'shipping'),
('payment_methods', '[{"id":"credit_card","name":"Cartão de Crédito","active":true},{"id":"boleto","name":"Boleto","active":true},{"id":"pix","name":"PIX","active":true}]', 'payment'),
('printer_settings', '[{"id":"ender3","name":"Ender 3","max_dimensions":"220x220x250","active":true}]', '3d_printing'),
('available_scales', '[{"id":"28mm","name":"28mm (Padrão)"},{"id":"32mm","name":"32mm (Heroic)"},{"id":"54mm","name":"54mm (Colecionável)"}]', '3d_printing');

-- Dados básicos de categorias para impressão 3D
INSERT INTO categories (name, slug, description, is_active, display_order) VALUES
('Miniaturas de RPG', 'miniaturas-rpg', 'Miniaturas impressas em 3D para personagens, monstros e NPCs de RPG', 1, 1),
('Terrenos e Cenários', 'terrenos-cenarios', 'Terrenos modulares e cenários impressos em 3D para suas aventuras', 1, 2),
('Acessórios para Jogos', 'acessorios-jogos', 'Acessórios impressos em 3D para aprimorar seus jogos de tabuleiro e RPG', 1, 3),
('Organizadores', 'organizadores', 'Organizadores e inserts impressos em 3D para caixas de jogos', 1, 4),
('Modelos Personalizados', 'modelos-personalizados', 'Impressão de modelos 3D personalizados fornecidos pelo cliente', 1, 5);

-- Subcategorias para Miniaturas de RPG
INSERT INTO categories (parent_id, name, slug, description, is_active, display_order) VALUES
(1, 'Heróis e Personagens', 'herois-personagens', 'Miniaturas de personagens jogáveis de diversas classes e raças', 1, 1),
(1, 'Monstros e Criaturas', 'monstros-criaturas', 'Miniaturas de monstros, inimigos e criaturas fantásticas', 1, 2),
(1, 'NPCs', 'npcs', 'Miniaturas de personagens não-jogáveis como comerciantes, aldeões e guardas', 1, 3),
(1, 'Grupos e Kits', 'grupos-kits', 'Conjuntos de miniaturas temáticas com desconto', 1, 4);

-- Subcategorias para Terrenos e Cenários
INSERT INTO categories (parent_id, name, slug, description, is_active, display_order) VALUES
(2, 'Masmorras', 'masmorras', 'Peças modulares para criação de masmorras e dungeons', 1, 1),
(2, 'Mobília', 'mobilia', 'Mobílias e decorações para cenários de RPG', 1, 2),
(2, 'Terrenos Naturais', 'terrenos-naturais', 'Terrenos modulares para ambientes naturais como florestas e montanhas', 1, 3),
(2, 'Construções', 'construcoes', 'Edifícios, ruínas e outras construções para cenários', 1, 4);

-- Subcategorias para Acessórios para Jogos
INSERT INTO categories (parent_id, name, slug, description, is_active, display_order) VALUES
(3, 'Porta-dados', 'porta-dados', 'Torres e bandejas para dados de RPG', 1, 1),
(3, 'Porta-cartas', 'porta-cartas', 'Suportes e organizadores de cartas para card games', 1, 2),
(3, 'Tokens e Marcadores', 'tokens-marcadores', 'Tokens, marcadores e contadores para jogos de tabuleiro', 1, 3),
(3, 'Telas de Mestre', 'telas-mestre', 'Suportes e acessórios para telas de mestre de RPG', 1, 4);

-- Cores de filamento básicas
INSERT INTO filament_colors (name, hex_code, filament_type, display_order) VALUES
('Preto', '#000000', 'PLA', 1),
('Branco', '#FFFFFF', 'PLA', 2),
('Cinza', '#808080', 'PLA', 3),
('Vermelho', '#FF0000', 'PLA', 4),
('Azul', '#0000FF', 'PLA', 5),
('Verde', '#008000', 'PLA', 6),
('Amarelo', '#FFFF00', 'PLA', 7),
('Marrom', '#8B4513', 'PLA', 8),
('Dourado', '#FFD700', 'PLA', 9),
('Prateado', '#C0C0C0', 'PLA', 10);