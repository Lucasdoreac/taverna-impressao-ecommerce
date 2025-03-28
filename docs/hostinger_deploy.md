# Checklist de Implantação na Hostinger - TAVERNA DA IMPRESSÃO

## 1. Preparação

- [ ] Backup completo do projeto local
- [ ] Verificar se todos os commits estão sincronizados
- [ ] Verificar todos os arquivos de configuração (config.php, .htaccess)

## 2. Banco de Dados

- [x] Criar banco de dados MySQL (u135851624_taverna)
- [ ] Importar esquema de tabelas (database/schema.sql)
- [ ] Importar dados iniciais (database/seed_data.sql)
- [ ] Aplicar atualizações do banco (database/updates/add_saved_customizations.sql)
- [ ] Testar conexão com o banco

## 3. Arquivos

- [ ] Fazer upload de todos os arquivos via FTP (recomendado) ou pelo gerenciador de arquivos
- [ ] Manter estrutura de diretórios original
- [ ] Verificar permissões de diretórios:
  - [ ] Diretório de uploads: 755 ou 775
  - [ ] Diretório de logs: 755 ou 775
  - [ ] Diretório de cache (se existir): 755 ou 775
  - [ ] Arquivos .php: 644
  - [ ] Arquivos .htaccess: 644

## 4. Configuração

- [x] Atualizar app/config/config.php com dados corretos
- [x] Verificar .htaccess na raiz para redirecionamento
- [x] Verificar .htaccess na pasta public para gerenciamento de rotas
- [ ] Configurar domínio no painel Hostinger
- [ ] Ativar SSL/HTTPS no painel Hostinger
- [ ] Verificar redirecionamentos HTTPS

## 5. Testes Pós-Implantação

### 5.1 Testes Básicos
- [ ] Verificar página inicial
- [ ] Testar navegação entre categorias
- [ ] Testar busca de produtos
- [ ] Verificar exibição de imagens e assets

### 5.2 Testes de Usuário
- [ ] Testar cadastro de usuário
- [ ] Testar login/logout
- [ ] Testar recuperação de senha (se implementado)
- [ ] Testar perfil do usuário

### 5.3 Testes de Produto
- [ ] Testar visualização de produtos
- [ ] Testar personalização de produtos
- [ ] Testar upload de arquivos para personalização
- [ ] Verificar salvamento de personalizações

### 5.4 Testes de Compra
- [ ] Testar adição ao carrinho
- [ ] Testar atualização de quantidades
- [ ] Testar remoção de itens
- [ ] Testar processo de checkout
- [ ] Testar cálculo de frete (se implementado)
- [ ] Testar aplicação de cupons (se implementado)

### 5.5 Testes Administrativos
- [ ] Testar acesso ao painel administrativo
- [ ] Testar gerenciamento de produtos
- [ ] Testar gerenciamento de categorias
- [ ] Testar gerenciamento de pedidos
- [ ] Testar relatórios

## 6. Otimizações

- [ ] Verificar tempos de carregamento (Google PageSpeed)
- [ ] Testar em dispositivos móveis
- [ ] Verificar compatibilidade cross-browser (Chrome, Firefox, Safari, Edge)
- [ ] Otimizar imagens grandes (se necessário)
- [ ] Verificar compressão GZIP
- [ ] Verificar cache de browser

## 7. SEO e Marketing

- [ ] Verificar meta tags
- [ ] Criar sitemap.xml
- [ ] Criar/verificar robots.txt
- [ ] Configurar Google Analytics (se aplicável)
- [ ] Configurar URLs canônicas

## 8. Segurança Final

- [ ] Remover ferramentas de debug
- [ ] Verificar headers de segurança
- [ ] Testar proteção CSRF
- [ ] Verificar validações de formulários
- [ ] Verificar permissões de arquivos sensíveis
- [ ] Alterar senhas padrão (admin, banco de dados, etc.)
- [ ] Verificar filtros de entrada de dados
- [ ] Verificar sanitização de saída

## 9. Backup e Monitoramento

- [ ] Backup completo após implantação
- [ ] Configurar backups automáticos no painel Hostinger
- [ ] Configurar monitoramento de uptime (se aplicável)
- [ ] Configurar monitoramento de erros (logs)
- [ ] Definir política de análise de logs

## 10. Lançamento

- [ ] Verificar domínio e DNS
- [ ] Fazer teste final completo
- [ ] Anunciar lançamento da plataforma
- [ ] Monitorar logs iniciais

## 11. Documentação

- [ ] Documentar senhas e acessos em local seguro
- [ ] Documentar procedimentos de manutenção
- [ ] Documentar processos de backup/restauração
- [ ] Criar guia rápido de operação para administradores

## Credenciais

- **URL do Site**: https://tavernaimpressao.com.br/
- **URL Admin**: https://tavernaimpressao.com.br/admin
- **Banco de Dados**: u135851624_taverna
- **Usuário BD**: u135851624_taverna
- **Senha BD**: #Taverna
- **Usuário Admin**: admin@tavernaimpressao.com.br
- **Senha Admin**: password (alterar imediatamente após primeiro acesso!)

## Notas de Implementação

- Após a importação do banco de dados, altere imediatamente a senha do administrador.
- Verifique todos os diretórios com permissão de escrita para garantir segurança.
- Teste o processo de checkout completo com diversos métodos de pagamento.
- Configure corretamente o SMTP para o envio de e-mails transacionais.

**Data planejada para lançamento**: [Inserir data]
