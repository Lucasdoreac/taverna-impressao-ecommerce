# Checklist de Implantação na Hostinger

## 1. Preparação

- [ ] Backup completo do projeto local
- [ ] Verificar se todos os commits estão sincronizados

## 2. Banco de Dados

- [x] Criar banco de dados MySQL (u135851624_taverna)
- [ ] Importar esquema de tabelas (database/schema.sql)
- [ ] Importar dados iniciais (database/seed_data.sql)
- [ ] Testar conexão com o banco

## 3. Arquivos

- [ ] Fazer upload de todos os arquivos via FTP (recomendado) ou pelo gerenciador de arquivos
- [ ] Manter estrutura de diretórios original
- [ ] Verificar permissões de diretórios:
  - [ ] Diretório de uploads: 755
  - [ ] Diretório de logs: 755
  - [ ] Arquivos .php: 644

## 4. Configuração

- [x] Atualizar app/config/config.php com dados corretos
- [x] Configurar .htaccess para redirecionamento e otimizações
- [ ] Configurar domínio no painel Hostinger
- [ ] Ativar SSL/HTTPS

## 5. Testes Pós-Implantação

- [ ] Verificar página inicial
- [ ] Testar cadastro de usuário
- [ ] Testar login/logout
- [ ] Testar visualização de produtos
- [ ] Testar personalização de produtos
- [ ] Testar adição ao carrinho
- [ ] Testar checkout
- [ ] Testar painel administrativo
- [ ] Testar uploads

## 6. Otimizações

- [ ] Verificar tempos de carregamento
- [ ] Testar em dispositivos móveis
- [ ] Verificar compatibilidade cross-browser

## 7. SEO

- [ ] Verificar meta tags
- [ ] Criar sitemap.xml
- [ ] Criar/verificar robots.txt

## 8. Segurança Final

- [ ] Remover ferramentas de debug
- [ ] Verificar headers de segurança
- [ ] Testar proteção CSRF
- [ ] Verificar permissões de arquivos sensíveis

## 9. Backup Final

- [ ] Backup completo após implantação
- [ ] Configurar backups automáticos

## 10. Lançamento

- [ ] Verificar domínio e DNS
- [ ] Anunciar lançamento da plataforma
- [ ] Monitorar logs iniciais