# Guia de Implantação da TAVERNA DA IMPRESSÃO na Hostinger

Este guia contém instruções detalhadas para implantar o e-commerce TAVERNA DA IMPRESSÃO em um servidor compartilhado da Hostinger.

## 1. Pré-requisitos

- Plano de hospedagem compartilhada Hostinger (recomendado Premium ou Business)
- Acesso ao cPanel da Hostinger
- Domínio configurado (exemplo: tavernaimpressao.com.br)
- Conhecimento básico de FTP, MySQL e PHP

## 2. Preparação dos Arquivos

### 2.1. Clone do Repositório

```bash
git clone https://github.com/Lucasdoreac/taverna-impressao-ecommerce.git
cd taverna-impressao-ecommerce
```

### 2.2. Otimização para Produção

Antes de fazer upload, certifique-se de que:

1. Todo o código está em sua versão otimizada (arquivos .min.css e .min.js)
2. Remova arquivos desnecessários para produção:
   - `.git/` (diretório de controle de versão)
   - `.github/` (configurações do GitHub)
   - `docs/` (documentação de desenvolvimento)
   - `project-status.json` (controle de desenvolvimento)
   - Quaisquer arquivos de teste ou rascunho

### 2.3. Configuração do Ambiente

Edite o arquivo `app/config/config.php` para definir as configurações de produção:

```php
// Altere a URL base para o seu domínio
define('BASE_URL', 'https://www.tavernaimpressao.com.br/');

// Habilitar modo de produção (desativa exibição de erros etc.)
define('ENVIRONMENT', 'production');

// As credenciais do banco de dados serão configuradas após a criação do banco
```

## 3. Configuração na Hostinger

### 3.1. Acesso ao cPanel

1. Faça login no painel da Hostinger (hpanel.hostinger.com)
2. Acesse "Hospedagem" > Seu domínio > "Gerenciar" > "Avançado" > "cPanel"

### 3.2. Criação do Banco de Dados MySQL

1. No cPanel, localize a seção "Banco de Dados" e clique em "MySQL Databases"
2. Crie um novo banco de dados:
   - Nome do banco: `username_tavernadb` (substitua username pelo seu nome de usuário no Hostinger)
   - Clique em "Criar Banco de Dados"
3. Crie um novo usuário:
   - Nome de usuário: `username_taverna`
   - Senha: use o gerador de senhas fortes ou crie uma senha segura
   - Clique em "Criar Usuário"
4. Adicione o usuário ao banco de dados:
   - Selecione o banco de dados e o usuário criados
   - Conceda "Todos os Privilégios"
   - Clique em "Adicionar"
5. Anote as credenciais completas:
   - Nome do banco: `username_tavernadb`
   - Nome do usuário: `username_taverna`
   - Senha: a que você definiu
   - Host: geralmente `localhost`

### 3.3. Upload dos Arquivos

#### Opção 1: Via Gerenciador de Arquivos do cPanel

1. No cPanel, clique em "Gerenciador de Arquivos"
2. Navegue até a pasta `public_html` (ou crie uma subpasta, se desejar)
3. Clique em "Upload" e faça o upload de todos os arquivos do projeto

#### Opção 2: Via FTP (Recomendado para muitos arquivos)

1. No cPanel, acesse "FTP Accounts" e crie uma conta FTP ou use a conta principal
2. Use um cliente FTP como FileZilla com as seguintes configurações:
   - Host: ftp.seudominio.com.br (verifique no painel da Hostinger)
   - Usuário: o nome de usuário FTP
   - Senha: a senha da conta FTP
   - Porta: 21
3. Conecte-se e faça upload de todos os arquivos do projeto para a pasta `public_html`

### 3.4. Configuração do Banco de Dados

1. No cPanel, acesse "phpMyAdmin"
2. Selecione o banco de dados criado anteriormente
3. Clique na aba "Importar"
4. Faça upload e execute os arquivos SQL na seguinte ordem:
   - `database/schema.sql`
   - `database/seed_data.sql`

### 3.5. Configuração do Arquivo de Configuração

Edite o arquivo `app/config/config.php` no servidor (ou localmente antes de fazer upload) para atualizar as credenciais do banco de dados:

```php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'username_tavernadb'); // Substitua pelo nome do seu banco
define('DB_USER', 'username_taverna');   // Substitua pelo nome do seu usuário
define('DB_PASS', 'sua_senha_segura');   // Substitua pela senha definida
```

## 4. Configuração de Diretórios e Permissões

Defina as permissões adequadas para os diretórios do sistema:

```bash
# Via SSH (se disponível)
chmod 755 public/
chmod 755 app/
chmod 644 app/config/config.php
chmod -R 755 public/assets/
chmod -R 755 public/uploads/
```

Alternativamente, você pode configurar permissões através do Gerenciador de Arquivos do cPanel:
1. Selecione as pastas/arquivos
2. Clique com o botão direito > "Permissões"
3. Configure os valores conforme necessário

## 5. Configuração do Domínio e HTTPS

### 5.1. Configuração do Domínio

Se você ainda não configurou seu domínio:
1. No painel da Hostinger, acesse "Domínios"
2. Associe seu domínio à hospedagem ou registre um novo domínio

### 5.2. Configuração do SSL (HTTPS)

1. No painel da Hostinger, acesse "SSL/TLS"
2. Clique em "Instalar" para seu domínio
3. Selecione "Let's Encrypt" (gratuito)
4. Complete a instalação do certificado

### 5.3. Configuração do .htaccess

Crie ou edite o arquivo `.htaccess` na raiz do seu site (pasta `public_html`) para forçar HTTPS e configurar URLs amigáveis:

```apache
# Redirecionar para HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remoção de www (opcional)
# RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
# RewriteRule ^(.*)$ https://%1/$1 [R=301,L]

# URLs amigáveis
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Proteção do diretório app
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^app/(.*)$ - [F,L]
</IfModule>

# Definir PHP timezone
php_value date.timezone America/Sao_Paulo

# Proteção contra acesso direto a arquivos PHP em uploads
<FilesMatch "\.php$">
    <If "%{REQUEST_URI} =~ m#^/public/uploads/#">
        Order Allow,Deny
        Deny from all
    </If>
</FilesMatch>

# Compressão GZIP para melhor performance
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/x-javascript text/javascript
</IfModule>

# Cache de navegador para arquivos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresDefault "access plus 2 days"
</IfModule>
```

## 6. Configuração de E-mail

### 6.1. Configuração de Contas de E-mail

1. No cPanel, acesse "Contas de E-mail"
2. Crie contas de e-mail necessárias para o sistema:
   - contato@seudominio.com.br
   - vendas@seudominio.com.br
   - suporte@seudominio.com.br

### 6.2. Configuração de SMTP no Sistema

Atualize as configurações SMTP no arquivo `app/config/config.php`:

```php
// Configurações de e-mail
define('SMTP_HOST', 'mail.seudominio.com.br');
define('SMTP_USER', 'vendas@seudominio.com.br');
define('SMTP_PASS', 'sua_senha_email');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('MAIL_FROM', 'vendas@seudominio.com.br');
define('MAIL_FROM_NAME', 'TAVERNA DA IMPRESSÃO');
```

## 7. Configuração de Cron Jobs (tarefas agendadas)

Se o sistema precisa executar tarefas automáticas:

1. No cPanel, acesse "Cron Jobs"
2. Adicione as tarefas necessárias, por exemplo:
   ```
   # Processamento diário de relatórios às 1:00 AM
   0 1 * * * php /home/username/public_html/app/cron/process_reports.php
   
   # Limpeza semanal de arquivos temporários aos domingos às 2:00 AM
   0 2 * * 0 php /home/username/public_html/app/cron/cleanup_temp.php
   ```

## 8. Testes Pós-Implantação

Execute estes testes para garantir que tudo está funcionando corretamente:

1. **Teste de Acesso**: Verifique se o site está acessível pelo domínio
2. **Teste de HTTPS**: Confirme que o site carrega com HTTPS
3. **Teste de Navegação**: Navegue por diferentes páginas do site
4. **Teste de Cadastro**: Tente criar uma nova conta
5. **Teste de Login**: Tente fazer login com a conta criada
6. **Teste de Produto**: Verifique se os produtos estão sendo exibidos corretamente
7. **Teste de Carrinho**: Adicione produtos ao carrinho e verifique se o total está correto
8. **Teste de Checkout**: Complete um pedido de teste
9. **Teste de Upload**: Verifique se o sistema de personalização permite uploads corretamente
10. **Teste de Admin**: Acesse o painel administrativo e verifique as funcionalidades

## 9. Otimização Adicional

### 9.1. Habilitando Cache PHP OPcache

1. No cPanel, acesse "PHP Select Version"
2. Habilite a extensão "OPcache"
3. Configure valores recomendados:
   - opcache.enable=1
   - opcache.memory_consumption=128
   - opcache.interned_strings_buffer=8
   - opcache.max_accelerated_files=4000
   - opcache.revalidate_freq=60
   - opcache.fast_shutdown=1

### 9.2. Configuração de CDN (Opcional)

Para melhorar a performance global:
1. Considere usar o Cloudflare:
   - No painel da Hostinger, acesse "Cloudflare"
   - Siga as instruções para habilitar

## 10. Monitoramento e Manutenção

### 10.1. Monitoramento

- Configure o Google Analytics para monitorar o tráfego
- Configure ferramentas de monitoramento de performance
- Configure alertas para downtime (UptimeRobot, Pingdom, etc.)

### 10.2. Backup Regular

- No cPanel, acesse "Backup"
- Configure backups automáticos semanais
- Faça download manual de backups periodicamente

### 10.3. Atualizações

Estabeleça um cronograma para:
- Verificação e aplicação de atualizações de segurança
- Atualização de plugins e dependências
- Revisão de logs de erros

## 11. Troubleshooting

### 11.1. Página em Branco / 500 Internal Server Error

1. Verifique logs de erro:
   - No cPanel, acesse "Error Log"
   - Ou verifique `/home/username/logs/error.log`
2. Problemas comuns:
   - Permissões incorretas
   - Configuração incorreta no php.ini
   - Erros no código PHP

### 11.2. Problemas com Banco de Dados

1. Verifique se as credenciais no `config.php` estão corretas
2. Verifique se o usuário do banco tem permissões adequadas
3. Verifique se o banco de dados está online e acessível

### 11.3. Problemas com Upload de Arquivos

1. Verifique as permissões das pastas de upload
2. Verifique as configurações de `upload_max_filesize` e `post_max_size` no PHP
3. Verifique limites de memória e timeout

## 12. Contatos e Suporte

- Suporte Hostinger: support.hostinger.com
- Documentação PHP: php.net/docs.php
- Suporte do Sistema: [Seu contato de suporte]

---

**Nota Importante**: Mantenha este documento atualizado conforme forem feitas alterações no sistema ou no processo de implantação. Este documento deve ser armazenado em local seguro e compartilhado apenas com pessoal autorizado.