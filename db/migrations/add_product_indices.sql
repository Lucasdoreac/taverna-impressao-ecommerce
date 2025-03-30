-- Adicionar índices para melhorar a performance de consultas SQL

-- Índices para a tabela products
ALTER TABLE products ADD INDEX idx_products_is_featured (is_featured);
ALTER TABLE products ADD INDEX idx_products_is_tested (is_tested);
ALTER TABLE products ADD INDEX idx_products_is_active (is_active);
ALTER TABLE products ADD INDEX idx_products_created_at (created_at);
ALTER TABLE products ADD INDEX idx_products_category_id (category_id);
ALTER TABLE products ADD INDEX idx_products_slug (slug);
ALTER TABLE products ADD INDEX idx_products_is_customizable (is_customizable);
ALTER TABLE products ADD INDEX idx_products_stock (stock);

-- Índice composto para filtragem por disponibilidade (muito usada no sistema)
ALTER TABLE products ADD INDEX idx_products_availability (is_tested, stock, is_active);

-- Adicionar FULLTEXT para pesquisa de texto
ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, description);

-- Índices para tabela product_images
ALTER TABLE product_images ADD INDEX idx_product_images_product_id (product_id);
ALTER TABLE product_images ADD INDEX idx_product_images_is_main (is_main);

-- Índice composto para a busca de imagens principais de produtos
ALTER TABLE product_images ADD INDEX idx_product_images_product_main (product_id, is_main);

-- Índices para a tabela categories
ALTER TABLE categories ADD INDEX idx_categories_parent_id (parent_id);
ALTER TABLE categories ADD INDEX idx_categories_is_active (is_active);
ALTER TABLE categories ADD INDEX idx_categories_display_order (display_order);
ALTER TABLE categories ADD INDEX idx_categories_slug (slug);
ALTER TABLE categories ADD INDEX idx_categories_left_value (left_value);
ALTER TABLE categories ADD INDEX idx_categories_right_value (right_value);

-- Adicionar FULLTEXT para pesquisa de texto em categorias
ALTER TABLE categories ADD FULLTEXT INDEX ft_categories_search (name, description);
