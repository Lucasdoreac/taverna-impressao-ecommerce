-- Índices para otimização de consultas SQL - Taverna da Impressão E-commerce
-- Criado em: 31/03/2025

-- Índices para tabela orders
CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders (user_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders (payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders (created_at);
CREATE INDEX IF NOT EXISTS idx_orders_print_start_date ON orders (print_start_date);

-- Índices para tabela order_items
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items (order_id);
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items (product_id);

-- Índice composto para tabela product_images
CREATE INDEX IF NOT EXISTS idx_product_images_product_id_is_main ON product_images (product_id, is_main);

-- Índice para tabela filament_colors
CREATE INDEX IF NOT EXISTS idx_filament_colors_id ON filament_colors (id);

-- Índices compostos para consultas frequentes
CREATE INDEX IF NOT EXISTS idx_orders_status_created_at ON orders (status, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status_created_at ON orders (payment_status, created_at);

-- Documentação de Índices:
--
-- idx_orders_user_id: Melhora a performance ao buscar todos os pedidos de um usuário específico
-- idx_orders_status: Acelera consultas que filtram por status do pedido
-- idx_orders_payment_status: Melhora consultas que filtram por status de pagamento
-- idx_orders_created_at: Otimiza ordenação por data de criação
-- idx_orders_print_start_date: Melhora ordenação de pedidos em impressão
-- idx_order_items_order_id: Acelera a recuperação de itens de um pedido específico
-- idx_order_items_product_id: Melhora junções com a tabela de produtos
-- idx_product_images_product_id_is_main: Melhora performance ao buscar imagem principal de um produto
-- idx_filament_colors_id: Melhora junções com a tabela de cores de filamento
-- idx_orders_status_created_at: Índice composto para consultas que filtram por status e ordenam por data
-- idx_orders_payment_status_created_at: Índice composto para consultas de vendas filtradas por status de pagamento

-- Observações:
--
-- 1. Estes índices foram desenvolvidos para melhorar a performance das consultas SQL 
--    identificadas como críticas ou de uso frequente no OrderModel.
--
-- 2. Impacto esperado: Redução média de 30-60% no tempo de execução das consultas,
--    dependendo do volume de dados e complexidade da consulta.
--
-- 3. Estes índices afetam principalmente:
--    - Listagem de pedidos (geral e por usuário)
--    - Relatórios de vendas por período
--    - Consultas de itens de pedidos
--    - Cálculos de tempo de impressão pendente
--    - Relatórios de vendas por categoria