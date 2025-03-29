# Configuração do Banco de Dados - TAVERNA DA IMPRESSÃO

Este documento explica como configurar o banco de dados para o e-commerce TAVERNA DA IMPRESSÃO. Inclui instruções para a instalação inicial e também para a recuperação em caso de problemas.

## Problema Identificado

Foi detectado que o banco de dados não possui as tabelas necessárias, o que causa erros 500 nas páginas de produtos e categorias. A ferramenta de diagnóstico (`database_debug.php`) confirmou que:

- A conexão com o banco está funcionando corretamente
- As credenciais estão configuradas corretamente
- Nenhuma das tabelas necessárias existe no banco de dados

## Solução: Reinicialização do Banco de Dados

O arquivo unificado `database/unified_schema.sql` foi criado para resolver este problema. Este arquivo:

1. Remove todas as tabelas existentes (se houver)
2. Cria todas as tabelas necessárias com a estrutura correta
3. Adiciona índices para melhorar a performance
4. Insere dados iniciais, como categorias e produtos
5. Configura chaves estrangeiras para garantir a integridade dos dados

## Instruções de Implementação

### Método 1: Usando a Interface do phpMyAdmin

1. Acesse o phpMyAdmin através do painel de controle da Hostinger
2. Selecione o banco de dados `u135851624_taverna`
3. Clique na guia "SQL"
4. Copie e cole o conteúdo do arquivo `database/unified_schema.sql`
5. Clique em "Executar"

### Método 2: Usando a Linha de Comando (SSH)

Se você tiver acesso SSH:

```bash
# Conecte-se ao servidor via SSH
ssh u135851624@seu-servidor-hostinger

# Acesse o MySQL
mysql -u u135851624_teverna -p u135851624_taverna

# Digite sua senha quando solicitado

# Execute o script SQL (substitua pelo caminho real)
source /caminho/para/unified_schema.sql
```

### Método 3: Importar Arquivo via phpMyAdmin

1. Acesse o phpMyAdmin através do painel de controle da Hostinger
2. Selecione o banco de dados `u135851624_taverna`
3. Clique na guia "Importar"
4. Clique em "Escolher arquivo" e selecione o arquivo `unified_schema.sql`
5. Deixe as configurações padrão e clique em "Importar"

## Verificação da Instalação

Após executar o script:

1. Acesse o URL: `https://darkblue-cattle-647559.hostingersite.com/database_debug.php`
2. Verifique se todas as tabelas estão listadas e marcadas como existentes
3. Verifique se os índices estão presentes
4. Confirme que os dados iniciais foram inseridos

## Problemas Conhecidos e Soluções

### Erro de Permissão

Se você encontrar erros de permissão ao executar o script:

1. Verifique se o usuário `u135851624_teverna` tem privilégios suficientes
2. No phpMyAdmin, vá para "Privilégios" e verifique os privilégios do usuário
3. O usuário precisa ter permissões de SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER e DROP

### Erro de Chave Estrangeira

Se houver problemas com chaves estrangeiras durante a importação:

1. Certifique-se de que o parâmetro `FOREIGN_KEY_CHECKS` está definido como 0 no início do script
2. Verifique se o script define `FOREIGN_KEY_CHECKS` como 1 novamente ao final

## Manutenção Futura

Após resolver o problema inicial, é recomendado:

1. Fazer backups regulares do banco de dados
2. Manter os arquivos SQL atualizados no repositório
3. Considerar a implementação de migrações para futuras alterações do esquema

## Notas Adicionais

- A senha do usuário administrador no arquivo SQL é 'password' - mude para uma senha forte em produção
- As imagens dos produtos precisam ser carregadas manualmente no diretório `public/uploads/products/`
- Para melhor desempenho, considere otimizar as consultas mais frequentes através de índices adicionais

## Próximos Passos Após a Configuração do Banco

1. Verificar todas as páginas do site para garantir que os erros 500 foram resolvidos
2. Testar o fluxo completo de compra
3. Quando tudo estiver funcionando, alterar `ENVIRONMENT` para 'production' em `app/config/config.php`
4. Remover ou proteger com senha os arquivos de diagnóstico (`*_debug.php`) em produção
