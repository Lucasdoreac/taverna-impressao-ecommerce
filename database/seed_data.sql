-- Dados iniciais para o site TAVERNA DA IMPRESSÃO

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

-- Inserir produtos de exemplo
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
