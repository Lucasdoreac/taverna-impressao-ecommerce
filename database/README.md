# TAVERNA DA IMPRESSÃO - Guia de Implementação do Banco de Dados

Este guia detalha como implementar corretamente o banco de dados para o e-commerce TAVERNA DA IMPRESSÃO.

## Arquivos SQL Disponíveis

O repositório contém os seguintes arquivos SQL:

1. **unified_schema_v2.sql** (RECOMENDADO)
   - Versão mais recente e otimizada
   - Resolve problemas de chaves estrangeiras
   - Adiciona índices para melhor performance
   - Inclui esquema completo + dados iniciais

2. **unified_schema.sql**
   - Versão anterior (não recomendada para novas instalações)
   - Contém esquema completo + dados iniciais

3. **schema.sql**
   - Apenas o esquema do banco de dados sem dados

4. **seed_data.sql**
   - Apenas dados iniciais sem esquema

## Como Implementar

### Método Recomendado (Nova Instalação)

1. Acesse o phpMyAdmin no painel da Hostinger
2. Selecione o banco de dados `u135851624_taverna`
3. Vá para a aba "Importar"
4. Selecione o arquivo `unified_schema_v2.sql`
5. Clique em "Importar" e aguarde a conclusão

### Resolução de Erros de Chave Estrangeira

Se encontrar erros do tipo `#1451 - Não pode apagar uma linha pai: uma restrição de chave estrangeira falhou`, o arquivo `unified_schema_v2.sql` foi projetado para resolver este problema automaticamente:

- Desativa temporariamente a verificação de chaves estrangeiras
- Remove as tabelas na ordem correta para evitar conflitos
- Cria novas tabelas com relações de integridade adequadas
- Reativa a verificação de chaves estrangeiras no final

### Para Atualização da Estrutura Existente

Se já tiver um banco de dados funcionando e deseja apenas atualizar:

```sql
-- Desativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 0;

-- Executar apenas as partes necessárias do arquivo unified_schema_v2.sql
-- Por exemplo, para adicionar índices:

-- Índices para melhorar consultas frequentes
CREATE INDEX idx_products_category_active ON products(category_id, is_active);
CREATE INDEX idx_products_featured_active ON products(is_featured, is_active);
-- (outros índices conforme necessário)

-- Reativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;
```

## Estrutura do Banco de Dados

O esquema segue uma estrutura relacional para um e-commerce de produtos impressos de RPG:

### Tabelas Principais
- `users` - Gerenciamento de usuários e administradores
- `categories` - Categorias e subcategorias de produtos
- `products` - Produtos disponíveis para venda
- `orders` - Pedidos dos clientes

### Tabelas de Relacionamento
- `product_images` - Imagens dos produtos
- `customization_options` - Opções de personalização
- `cart_items` - Itens no carrinho
- `order_items` - Itens nos pedidos

### Tabelas de Suporte
- `addresses` - Endereços dos usuários
- `carts` - Carrinhos de compra
- `coupons` - Cupons de desconto
- `settings` - Configurações do sistema

## Dados Iniciais

O script inclui dados iniciais para começar:

- Usuário administrador (admin@tavernaimpressao.com.br / password)
- Categorias principais e subcategorias para produtos de RPG
- Produtos de exemplo
- Configurações básicas da loja

## Solução de Problemas

### Erro: "Table already exists"
Se aparecer erro indicando que a tabela já existe:

```sql
DROP TABLE IF EXISTS nome_da_tabela;
```

### Erro: "Cannot add foreign key constraint"
Se houver problemas com chaves estrangeiras:

```sql
SET FOREIGN_KEY_CHECKS = 0;
-- Executar suas operações
SET FOREIGN_KEY_CHECKS = 1;
```

### Erro: "Data too long for column X"
Se dados não couberem em uma coluna:

```sql
ALTER TABLE nome_da_tabela MODIFY COLUMN nome_da_coluna VARCHAR(255);
```

## Notas Importantes

1. Sempre faça backup do banco de dados antes de executar scripts SQL
2. Modifique a senha do usuário administrador após a instalação
3. Em ambiente de produção, certifique-se de que o serviço MySQL está configurado corretamente
4. Os índices adicionados melhoram significativamente a performance em tabelas com muitos registros

Para dúvidas ou problemas adicionais, consulte as ferramentas de diagnóstico em `public/database_debug.php`.
