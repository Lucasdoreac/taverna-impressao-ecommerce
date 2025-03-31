-- =========================================================================
-- TAVERNA DA IMPRESSÃO - ESQUEMA UNIFICADO COMPLETO
-- Versão: 1.0.0
-- Data: 31/03/2025
-- =========================================================================
-- Este arquivo contém o esquema completo do banco de dados para o e-commerce
-- Taverna da Impressão, incluindo todas as estruturas e índices necessários.
-- Unifica o esquema básico, recursos de impressão 3D e otimizações.
-- =========================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- =========================================================================
-- DESATIVAR VERIFICAÇÃO DE CHAVES ESTRANGEIRAS
-- =========================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================================
-- REMOÇÃO DE TABELAS EXISTENTES
-- =========================================================================

-- Primeiramente as tabelas dependentes (para evitar erros de chave estrangeira)
DROP TABLE IF EXISTS print_queue_history;
DROP TABLE IF EXISTS print_notifications;
DROP TABLE IF EXISTS print_queue;
DROP TABLE IF EXISTS filament_colors;
DROP TABLE IF EXISTS customer_models;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS customization_options;
DROP TABLE IF EXISTS saved_customizations;

-- Depois as tabelas intermediárias
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS printers;

-- Por último as tabelas principais
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

-- =========================================================================
-- TABELAS PRINCIPAIS
-- =========================================================================

-- Tabela de usuários/clientes
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  role ENUM('customer', 'admin') DEFAULT 'customer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de endereços
CREATE TABLE addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  address VARCHAR(255) NOT NULL,
  number VARCHAR(20) NOT NULL,
  complement VARCHAR(100) DEFAULT NULL,
  neighborhood VARCHAR(100) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(2) NOT NULL,
  zipcode VARCHAR(9) NOT NULL,
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias com suporte a hierarquia
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT DEFAULT NULL,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  left_value INT DEFAULT NULL,
  right_value INT DEFAULT NULL,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- TABELAS PARA PRODUTOS E E-COMMERCE
-- =========================================================================

-- Tabela de produtos (com suporte a impressão 3D)
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT DEFAULT NULL,
  short_description VARCHAR(255) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL,
  sale_price DECIMAL(10,2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  weight DECIMAL(10,2) DEFAULT 0,
  dimensions VARCHAR(50) DEFAULT NULL,
  sku VARCHAR(50) DEFAULT NULL UNIQUE,
  is_featured TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  is_customizable TINYINT(1) DEFAULT 0,
  customization_options TEXT DEFAULT NULL,
  customization_price_adjustment DECIMAL(10,2) DEFAULT 0,
  is_tested TINYINT(1) DEFAULT 0 COMMENT 'Indica se o produto já foi testado e está disponível para pronta entrega',
  
  -- Campos para produtos de impressão 3D
  print_time_hours DECIMAL(5,2) DEFAULT NULL COMMENT 'Tempo estimado de impressão em horas',
  filament_type ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  filament_usage_grams INT DEFAULT NULL COMMENT 'Quantidade estimada de filamento em gramas',
  scale VARCHAR(50) DEFAULT '28mm' COMMENT 'Escala padrão da miniatura',
  model_file VARCHAR(255) DEFAULT NULL COMMENT 'Caminho para o arquivo STL/OBJ',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens dos produtos
CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  is_main TINYINT(1) DEFAULT 0,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de opções de personalização
CREATE TABLE customization_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL,
  type ENUM('upload', 'text', 'select', 'color', 'scale') NOT NULL,
  required TINYINT(1) DEFAULT 0,
  options TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações salvas de personalização
CREATE TABLE saved_customizations (
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

-- Tabela de carrinhos
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  session_id VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do carrinho
CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  
  -- Campos para impressão 3D
  selected_scale VARCHAR(50) DEFAULT NULL COMMENT 'Escala selecionada pelo cliente',
  selected_filament ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  selected_color VARCHAR(50) DEFAULT NULL COMMENT 'Cor selecionada pelo cliente',
  customer_model_id INT DEFAULT NULL COMMENT 'ID do modelo enviado pelo cliente, se aplicável',
  
  customization_data TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  order_number VARCHAR(20) NOT NULL UNIQUE,
  
  -- Status básico para e-commerce
  status ENUM('pending', 'processing', 'shipped', 'delivered', 'canceled', 
              'validating', 'printing', 'finishing') DEFAULT 'pending',
  
  -- Campos específicos para impressão 3D
  estimated_print_time_hours DECIMAL(5,2) DEFAULT NULL COMMENT 'Tempo total estimado de impressão',
  print_start_date DATETIME DEFAULT NULL COMMENT 'Data/hora de início da impressão',
  print_finish_date DATETIME DEFAULT NULL COMMENT 'Data/hora de término da impressão',
  
  payment_method ENUM('credit_card', 'boleto', 'pix') NOT NULL,
  payment_status ENUM('pending', 'paid', 'refunded', 'canceled') DEFAULT 'pending',
  shipping_address_id INT DEFAULT NULL,
  shipping_method VARCHAR(50) DEFAULT NULL,
  shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  notes TEXT DEFAULT NULL,
  tracking_code VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  
  -- Campos para impressão 3D
  selected_scale VARCHAR(50) DEFAULT NULL COMMENT 'Escala selecionada pelo cliente',
  selected_filament ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  selected_color VARCHAR(50) DEFAULT NULL COMMENT 'Cor selecionada pelo cliente',
  customer_model_id INT DEFAULT NULL COMMENT 'ID do modelo enviado pelo cliente, se aplicável',
  print_time_hours DECIMAL(5,2) DEFAULT NULL COMMENT 'Tempo de impressão deste item',
  is_stock_item TINYINT(1) DEFAULT 0 COMMENT 'Indica se o item vem do estoque ou será impresso sob demanda',
  
  customization_data TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cupons de desconto
CREATE TABLE coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  type ENUM('percentage', 'fixed') NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  min_order_value DECIMAL(10,2) DEFAULT 0,
  starts_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  max_uses INT DEFAULT NULL,
  uses_count INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações
CREATE TABLE settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(50) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  setting_group VARCHAR(50) DEFAULT 'general',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- TABELAS ESPECÍFICAS PARA IMPRESSÃO 3D
-- =========================================================================

-- Tabela de arquivos 3D do cliente
CREATE TABLE customer_models (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_size INT NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  status ENUM('pending_validation', 'approved', 'rejected', 'needs_repair') DEFAULT 'pending_validation' 
         COMMENT 'Status do modelo: pendente de validação, aprovado, rejeitado ou precisa de reparo',
  validation_data TEXT DEFAULT NULL COMMENT 'Dados JSON detalhados da validação do modelo 3D',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cores de filamento disponíveis
CREATE TABLE filament_colors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  hex_code VARCHAR(7) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  filament_type ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- TABELAS PARA SISTEMA DE FILA DE IMPRESSÃO
-- =========================================================================

-- Tabela de impressoras disponíveis
CREATE TABLE printers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  max_width INT NOT NULL COMMENT 'Largura máxima em mm',
  max_depth INT NOT NULL COMMENT 'Profundidade máxima em mm',
  max_height INT NOT NULL COMMENT 'Altura máxima em mm',
  filament_types VARCHAR(255) DEFAULT NULL COMMENT 'Tipos de filamento compatíveis, separados por vírgula',
  status ENUM('available', 'printing', 'maintenance', 'offline') DEFAULT 'available',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens na fila de impressão
CREATE TABLE print_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  order_item_id INT NOT NULL,
  printer_id INT DEFAULT NULL COMMENT 'NULL se ainda não alocado a uma impressora',
  customer_model_id INT DEFAULT NULL COMMENT 'ID do modelo personalizado, se aplicável',
  product_id INT NOT NULL,
  priority INT DEFAULT 5 COMMENT 'Prioridade de 1 (mais alta) a 10 (mais baixa)',
  status ENUM('pending', 'scheduled', 'printing', 'paused', 'completed', 'failed', 'canceled') DEFAULT 'pending',
  estimated_print_time_hours DECIMAL(5,2) NOT NULL COMMENT 'Tempo estimado de impressão em horas',
  actual_print_time_hours DECIMAL(5,2) DEFAULT NULL COMMENT 'Tempo real de impressão em horas',
  filament_type ENUM('PLA', 'PETG', 'ABS', 'TPU', 'OUTROS') DEFAULT 'PLA',
  filament_color_id INT DEFAULT NULL,
  filament_usage_grams INT NOT NULL COMMENT 'Quantidade estimada de filamento em gramas',
  actual_filament_usage_grams INT DEFAULT NULL COMMENT 'Quantidade real de filamento usado em gramas',
  scale VARCHAR(50) NOT NULL COMMENT 'Escala da impressão',
  scheduled_start_date DATETIME DEFAULT NULL COMMENT 'Data/hora programada para início',
  actual_start_date DATETIME DEFAULT NULL COMMENT 'Data/hora real de início',
  actual_end_date DATETIME DEFAULT NULL COMMENT 'Data/hora real de término',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
  FOREIGN KEY (printer_id) REFERENCES printers(id) ON DELETE SET NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
  FOREIGN KEY (customer_model_id) REFERENCES customer_models(id) ON DELETE SET NULL,
  FOREIGN KEY (filament_color_id) REFERENCES filament_colors(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de eventos da fila
CREATE TABLE print_queue_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  print_queue_id INT NOT NULL,
  event_type ENUM('status_change', 'printer_assigned', 'priority_change', 'note_added', 'error', 'start', 'finish', 'pause', 'resume') NOT NULL,
  previous_status VARCHAR(50) DEFAULT NULL,
  new_status VARCHAR(50) DEFAULT NULL,
  previous_printer_id INT DEFAULT NULL,
  new_printer_id INT DEFAULT NULL,
  previous_priority INT DEFAULT NULL,
  new_priority INT DEFAULT NULL,
  description TEXT NOT NULL,
  created_by INT DEFAULT NULL COMMENT 'ID do usuário que causou o evento',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (print_queue_id) REFERENCES print_queue(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de notificações de impressão
CREATE TABLE print_notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  print_queue_id INT NOT NULL,
  notification_type ENUM('status_change', 'scheduled', 'started', 'completed', 'failed', 'general') NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (print_queue_id) REFERENCES print_queue(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- ÍNDICES OTIMIZADOS
-- =========================================================================

-- Índices para tabela categories
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_parent_id (parent_id);
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_slug (slug);
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_is_active (is_active);
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_display_order (display_order);
ALTER TABLE categories ADD INDEX IF NOT EXISTS idx_categories_nested_sets (left_value, right_value);

-- Índices para tabela products
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_category_id (category_id);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_is_active (is_active);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_is_featured (is_featured);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_tested_stock (is_tested, stock);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_price (price, sale_price);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_category_active (category_id, is_active);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_featured_active (is_featured, is_active);
ALTER TABLE products ADD INDEX IF NOT EXISTS idx_products_slug (slug);

-- Índices para tabela users
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Índices para tabela addresses
CREATE INDEX IF NOT EXISTS idx_addresses_user_default ON addresses(user_id, is_default);

-- Índices para tabela cart_items
CREATE INDEX IF NOT EXISTS idx_cart_items_cart_product ON cart_items(cart_id, product_id);

-- Índices para tabela carts
CREATE INDEX IF NOT EXISTS idx_carts_user_session ON carts(user_id, session_id);

-- Índices para tabela orders
CREATE INDEX IF NOT EXISTS idx_orders_user ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);

-- Índices para tabela order_items
CREATE INDEX IF NOT EXISTS idx_order_items_product ON order_items(product_id);

-- Índices para tabela product_images
CREATE INDEX IF NOT EXISTS idx_product_images_product_main ON product_images(product_id, is_main);

-- Índices para tabela saved_customizations
CREATE INDEX IF NOT EXISTS idx_saved_customizations_user ON saved_customizations(user_id);
CREATE INDEX IF NOT EXISTS idx_saved_customizations_product ON saved_customizations(product_id);

-- Índices para tabela customer_models
CREATE INDEX IF NOT EXISTS idx_customer_models_user ON customer_models(user_id);
CREATE INDEX IF NOT EXISTS idx_customer_models_status ON customer_models(status);

-- Índices para tabela print_queue
CREATE INDEX IF NOT EXISTS idx_print_queue_order ON print_queue(order_id);
CREATE INDEX IF NOT EXISTS idx_print_queue_product ON print_queue(product_id);
CREATE INDEX IF NOT EXISTS idx_print_queue_printer ON print_queue(printer_id);
CREATE INDEX IF NOT EXISTS idx_print_queue_status ON print_queue(status);
CREATE INDEX IF NOT EXISTS idx_print_queue_priority ON print_queue(priority);

-- =========================================================================
-- DADOS INICIAIS PARA A LOJA
-- =========================================================================

-- Inserir usuário administrador
INSERT INTO users (name, email, password, role) VALUES 
('Administrador', 'admin@tavernaimpressao.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Senha é 'password' - alterar em produção!

-- Categorias principais para produtos de RPG impressos
INSERT INTO categories (name, slug, description, is_active, display_order) VALUES
('Fichas de Personagem', 'fichas-de-personagem', 'Fichas impressas para seus personagens de RPG, em diversos sistemas e formatos.', 1, 1),
('Mapas de Aventura', 'mapas-de-aventura', 'Mapas detalhados para suas aventuras, impressos em alta qualidade.', 1, 2),
('Livros e Módulos', 'livros-e-modulos', 'Livros de regras, módulos e aventuras impressas para seu jogo de RPG.', 1, 3),
('Telas do Mestre', 'telas-do-mestre', 'Telas impressas para auxiliar o mestre na condução das aventuras.', 1, 4),
('Cards e Tokens', 'cards-e-tokens', 'Cards de referência, tokens e marcadores para facilitar a jogabilidade.', 1, 5),
('Kits Personalizados', 'kits-personalizados', 'Kits completos para sua mesa de RPG, incluindo mapas, fichas e materiais personalizados.', 1, 6);

-- Subcategorias para Fichas de Personagem
INSERT INTO categories (parent_id, name, slug, description, is_active, display_order) VALUES
(1, 'D&D 5ª Edição', 'fichas-dd-5e', 'Fichas para Dungeons & Dragons 5ª Edição, o RPG mais popular do mundo.', 1, 1),
(1, 'Tormenta 20', 'fichas-tormenta-20', 'Fichas para Tormenta 20, o maior RPG brasileiro.', 1, 2),
(1, 'Pathfinder', 'fichas-pathfinder', 'Fichas para Pathfinder 1ª e 2ª edição.', 1, 3),
(1, 'Call of Cthulhu', 'fichas-call-of-cthulhu', 'Fichas para Call of Cthulhu, o RPG de horror cósmico.', 1, 4),
(1, 'Vampiro: A Máscara', 'fichas-vampiro', 'Fichas para Vampiro: A Máscara 5ª edição.', 1, 5);

-- Subcategorias para Mapas de Aventura
INSERT INTO categories (parent_id, name, slug, description, is_active, display_order) VALUES
(2, 'Dungeons', 'mapas-dungeons', 'Mapas de masmorras, cavernas e dungeons.', 1, 1),
(2, 'Cidades', 'mapas-cidades', 'Mapas de cidades, vilas e povoados.', 1, 2),
(2, 'Florestas', 'mapas-florestas', 'Mapas de florestas, bosques e ambientes naturais.', 1, 3),
(2, 'Tavernas', 'mapas-tavernas', 'Mapas de tavernas, estalagens e locais de descanso.', 1, 4);

-- Produtos de exemplo
INSERT INTO products (category_id, name, slug, description, short_description, price, stock, is_active, is_customizable) VALUES
(7, 'Ficha de Personagem D&D 5e - Básica', 'ficha-personagem-dd-5e-basica', 
'Ficha de personagem completa para D&D 5ª Edição, impressa em papel de alta qualidade. Contém todos os campos necessários para registrar informações do seu personagem.

Características:
- Formato A4
- Papel offset 120g/m²
- Impressão frente e verso
- Layout otimizado para jogabilidade

Pacote com 5 fichas.', 
'Conjunto com 5 fichas de personagem para D&D 5e impressas em papel de alta qualidade.', 
15.90, 100, 1, 0),

(7, 'Ficha de Personagem D&D 5e - Premium Personalizada', 'ficha-personagem-dd-5e-premium', 
'Ficha premium personalizada para D&D 5ª Edição com o nome do seu personagem, classe e arte temática.

Características:
- Papel couchê 250g/m²
- Impressão colorida frente e verso
- Acabamento laminado
- Personalização completa', 
'Ficha premium personalizada para D&D 5e com seu nome, classe e arte temática à sua escolha.', 
29.90, 50, 1, 1),

(12, 'Mapa de Dungeon - Cripta Ancestral 60x90cm', 'mapa-dungeon-cripta-ancestral', 
'Mapa detalhado de uma cripta ancestral, perfeito para aventuras de exploração. Inclui salas secretas, armadilhas e múltiplos níveis.

Características:
- Tamanho: 60x90cm
- Papel: Couché fosco 150g
- Grid de 1 polegada para uso com miniaturas
- Acabamento laminado', 
'Mapa detalhado de cripta ancestral com múltiplas salas, armadilhas e passagens secretas.', 
45.90, 30, 1, 0),

(12, 'Mapa de Dungeon Personalizado', 'mapa-dungeon-personalizado', 
'Transforme seu rascunho ou ideia em um mapa profissional para sua aventura. Envie seu esboço ou descrição, e criaremos um mapa personalizado.

Características:
- Tamanho: Até 100x100cm
- Papel: Couché fosco 150g ou lona
- Design personalizado baseado em suas especificações
- Até 3 revisões incluídas', 
'Mapa de dungeon totalmente personalizado com base em seu rascunho ou descrição.', 
119.90, 10, 1, 1);

-- Adicionar imagens aos produtos
INSERT INTO product_images (product_id, image, is_main) VALUES
(1, 'ficha-dd-5e-basica.jpg', 1),
(2, 'ficha-dd-5e-premium.jpg', 1),
(3, 'mapa-cripta-ancestral.jpg', 1),
(4, 'mapa-personalizado-exemplo.jpg', 1);

-- Adicionar opções de personalização
INSERT INTO customization_options (product_id, name, description, type, required, options) VALUES
(2, 'Nome do Personagem', 'Informe o nome do seu personagem para ser impresso na ficha.', 'text', 1, NULL),
(2, 'Classe e Nível', 'Informe a classe e o nível do seu personagem.', 'text', 1, NULL),
(2, 'Raça', 'Informe a raça do seu personagem.', 'text', 1, NULL),
(2, 'Estilo Visual', 'Escolha o estilo visual para sua ficha.', 'select', 1, '{\"1\":\"Clássico Medieval\",\"2\":\"Élfico\",\"3\":\"Dracônico\",\"4\":\"Sombrio\",\"5\":\"Arcano\"}'),
(4, 'Descrição do Mapa', 'Descreva em detalhes o mapa que você deseja, incluindo salas, corredores, armadilhas, etc.', 'text', 1, NULL),
(4, 'Referência Visual', 'Envie um rascunho ou referência visual do seu mapa.', 'upload', 0, NULL);

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

-- Inserir dados de impressoras
INSERT INTO printers (name, model, max_width, max_depth, max_height, filament_types, status, notes) VALUES
('Ender 3 #1', 'Creality Ender 3', 220, 220, 250, 'PLA,PETG,TPU', 'available', 'Impressora principal para miniaturas pequenas'),
('Ender 3 Pro #1', 'Creality Ender 3 Pro', 220, 220, 250, 'PLA,PETG,ABS,TPU', 'available', 'Impressora para peças de precisão'),
('CR-10 #1', 'Creality CR-10', 300, 300, 400, 'PLA,PETG,ABS', 'available', 'Impressora para terrenos grandes e peças de cenário');

-- Configurações da loja
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('store_name', 'TAVERNA DA IMPRESSÃO', 'general'),
('store_email', 'contato@tavernaimpressao.com.br', 'general'),
('store_phone', '(21) 98765-4321', 'general'),
('currency', 'BRL', 'general'),
('currency_symbol', 'R$', 'general'),
('meta_title', 'TAVERNA DA IMPRESSÃO - Materiais impressos para RPG', 'seo'),
('meta_description', 'Loja especializada em materiais impressos para RPG, incluindo fichas, mapas e acessórios personalizados.', 'seo'),
('shipping_methods', '[{\"name\":\"PAC\",\"price\":15.00},{\"name\":\"SEDEX\",\"price\":25.00}]', 'shipping'),
('payment_methods', '[{\"id\":\"credit_card\",\"name\":\"Cartão de Crédito\",\"active\":true},{\"id\":\"boleto\",\"name\":\"Boleto\",\"active\":true},{\"id\":\"pix\",\"name\":\"PIX\",\"active\":true}]', 'payment'),
('printer_settings', '[{\"id\":\"ender3\",\"name\":\"Ender 3\",\"max_dimensions\":\"220x220x250\",\"active\":true}]', '3d_printing'),
('available_scales', '[{\"id\":\"28mm\",\"name\":\"28mm (Padrão)\"},{\"id\":\"32mm\",\"name\":\"32mm (Heroic)\"},{\"id\":\"54mm\",\"name\":\"54mm (Colecionável)\"}]', '3d_printing');

-- =========================================================================
-- REATIVAR VERIFICAÇÃO DE CHAVES ESTRANGEIRAS
-- =========================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- FINALIZAR TRANSAÇÃO
-- =========================================================================
COMMIT;