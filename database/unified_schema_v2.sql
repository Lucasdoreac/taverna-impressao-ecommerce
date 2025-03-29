-- TAVERNA DA IMPRESSÃO - Script SQL Unificado (V2)
-- Este script SQL corrige problemas de chaves estrangeiras e unifica o esquema completo
-- Uso: Execute este arquivo para criar uma nova instalação completa do banco de dados

-- Configurações iniciais para garantir compatibilidade
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- PREPARAÇÃO DO AMBIENTE
-- ------------------------------------------------------------

-- ATENÇÃO: Desativar verificação de chaves estrangeiras para permitir remoção/recriação
SET FOREIGN_KEY_CHECKS = 0;

-- Remover tabelas existentes na ordem correta para evitar erros de integridade
-- Primeiro as tabelas dependentes (filhas)
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS customization_options;
-- Depois as tabelas intermediárias
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS products;
-- Por último as tabelas principais (pais)
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS addresses;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

-- ------------------------------------------------------------
-- CRIAÇÃO DE TABELAS
-- ------------------------------------------------------------

-- 1. Tabelas Base/Principais

-- Tabela de usuários
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
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
  complement VARCHAR(100),
  neighborhood VARCHAR(100) NOT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(2) NOT NULL,
  zipcode VARCHAR(9) NOT NULL,
  is_default BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelas Intermediárias

-- Tabela de produtos
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  short_description VARCHAR(255),
  price DECIMAL(10,2) NOT NULL,
  sale_price DECIMAL(10,2),
  stock INT NOT NULL DEFAULT 0,
  weight DECIMAL(10,2) DEFAULT 0,
  dimensions VARCHAR(50),
  sku VARCHAR(50) UNIQUE,
  is_featured BOOLEAN DEFAULT FALSE,
  is_active BOOLEAN DEFAULT TRUE,
  is_customizable BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de carrinhos
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  session_id VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  order_number VARCHAR(20) NOT NULL UNIQUE,
  status ENUM('pending', 'processing', 'shipped', 'delivered', 'canceled') DEFAULT 'pending',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelas Dependentes (filhas)

-- Tabela de imagens dos produtos
CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image VARCHAR(255) NOT NULL,
  is_main BOOLEAN DEFAULT FALSE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de opções de personalização
CREATE TABLE customization_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  type ENUM('upload', 'text', 'select') NOT NULL,
  required BOOLEAN DEFAULT FALSE,
  options TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do carrinho
CREATE TABLE cart_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  customization_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  customization_data TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelas Auxiliares

-- Tabela de cupons de desconto
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

-- ------------------------------------------------------------
-- ÍNDICES OTIMIZADOS
-- ------------------------------------------------------------

-- Índices para melhorar consultas frequentes
CREATE INDEX idx_products_category_active ON products(category_id, is_active);
CREATE INDEX idx_products_featured_active ON products(is_featured, is_active);
CREATE INDEX idx_products_slug ON products(slug);
CREATE INDEX idx_categories_parent ON categories(parent_id);
CREATE INDEX idx_categories_slug ON categories(slug);
CREATE INDEX idx_cart_items_cart_product ON cart_items(cart_id, product_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_items_product ON order_items(product_id);
CREATE INDEX idx_carts_user_session ON carts(user_id, session_id);
CREATE INDEX idx_product_images_product_main ON product_images(product_id, is_main);
CREATE INDEX idx_addresses_user_default ON addresses(user_id, is_default);

-- ------------------------------------------------------------
-- DADOS INICIAIS
-- ------------------------------------------------------------

-- Inserir usuário administrador
INSERT INTO users (name, email, password, role) VALUES 
('Administrador', 'admin@tavernaimpressao.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Senha é 'password' - altere em produção!

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

(9, 'Mapa de Dungeon - Cripta Ancestral 60x90cm', 'mapa-dungeon-cripta-ancestral', 
'Mapa detalhado de uma cripta ancestral, perfeito para aventuras de exploração. Inclui salas secretas, armadilhas e múltiplos níveis.

Características:
- Tamanho: 60x90cm
- Papel: Couché fosco 150g
- Grid de 1 polegada para uso com miniaturas
- Acabamento laminado', 
'Mapa detalhado de cripta ancestral com múltiplas salas, armadilhas e passagens secretas.', 
45.90, 30, 1, 0),

(9, 'Mapa de Dungeon Personalizado', 'mapa-dungeon-personalizado', 
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
(2, 'Estilo Visual', 'Escolha o estilo visual para sua ficha.', 'select', 1, '{"1":"Clássico Medieval","2":"Élfico","3":"Dracônico","4":"Sombrio","5":"Arcano"}'),
(4, 'Descrição do Mapa', 'Descreva em detalhes o mapa que você deseja, incluindo salas, corredores, armadilhas, etc.', 'text', 1, NULL),
(4, 'Referência Visual', 'Envie um rascunho ou referência visual do seu mapa.', 'upload', 0, NULL);

-- Configurações da loja
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('store_name', 'TAVERNA DA IMPRESSÃO', 'general'),
('store_email', 'contato@tavernaimpressao.com.br', 'general'),
('store_phone', '(21) 98765-4321', 'general'),
('currency', 'BRL', 'general'),
('currency_symbol', 'R$', 'general'),
('meta_title', 'TAVERNA DA IMPRESSÃO - Materiais impressos para RPG', 'seo'),
('meta_description', 'Loja especializada em materiais impressos para RPG, incluindo fichas, mapas e acessórios personalizados.', 'seo'),
('shipping_methods', '[{"name":"PAC","price":15.00},{"name":"SEDEX","price":25.00}]', 'shipping'),
('payment_methods', '[{"id":"credit_card","name":"Cartão de Crédito","active":true},{"id":"boleto","name":"Boleto","active":true},{"id":"pix","name":"PIX","active":true}]', 'payment');

-- Reativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- Finalizar a transação
COMMIT;

-- ------------------------------------------------------------
-- INSTRUÇÕES DE USO
-- ------------------------------------------------------------
-- 1. Execute este arquivo diretamente no phpMyAdmin ou via linha de comando MySQL
-- 2. Para ambientes de desenvolvimento, você pode usar:
--    mysql -u username -p database_name < unified_schema_v2.sql
-- 3. Certifique-se de que o usuário tem permissões para criar e modificar tabelas
-- 4. Após a instalação, altere a senha do administrador para segurança
-- 5. Verifique os logs para qualquer erro durante a execução
