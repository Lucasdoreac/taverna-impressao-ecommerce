# Guia de Deploy - Taverna da Impressão 3D

Este documento fornece instruções detalhadas para o deploy do projeto na hospedagem compartilhada Hostinger.

## Pré-requisitos

- Plano de hospedagem compartilhada na Hostinger com:
  - PHP 7.4 ou superior
  - MySQL 5.7 ou superior
  - Suporte a mod_rewrite

## 1. Preparação do Ambiente Local

Antes de fazer o upload, prepare seu ambiente local:

```bash
# 1. Certifique-se de que todas as mudanças estão finalizadas
# 2. Copie o arquivo de configuração de exemplo e configure para produção
cp app/config/config.sample.php app/config/config.php
# 3. Edite app/config/config.php e defina ENVIRONMENT como 'production'
# 4. Configure credenciais de banco de dados e outras configurações específicas
```

## 2. Preparando os Arquivos

Crie um pacote de deploy contendo apenas os arquivos necessários para produção:

### Arquivos e Diretórios para Upload

```
/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   ├── lib/
│   └── config/
│       └── config.php       # Versão configurada para produção
├── public/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/             # Certificar-se de que tem permissões de escrita
├── index.php                # Ponto de entrada
├── .htaccess                # Regras de reescrita e segurança
└── robots.txt
```

### Arquivos que NÃO devem ser enviados para produção

- `project-status.json`
- `PLANNING.md`, `TASK.md`
- `continuity-guide.md`
- `utils/` (diretório inteiro)
- `tests/` (diretório inteiro)
- `README.md` (a menos que queira exibi-lo publicamente)
- `.git/` e arquivos de controle de versão
- `init.js`

## 3. Upload para o Servidor Hostinger

### Método 1: Via FTP

1. Acesse o painel de controle da Hostinger
2. Vá para "Arquivos" > "Gerenciador de Arquivos" ou use um cliente FTP como FileZilla
3. Credenciais FTP:
   - Servidor: ftp.seudominio.com
   - Usuário: fornecido pela Hostinger
   - Senha: fornecido pela Hostinger
   - Porta: 21
4. Faça upload de todos os arquivos para a pasta `public_html/`
5. Certifique-se de que os diretórios `uploads/`, `cache/` e `logs/` tenham permissões de escrita (CHMOD 755 ou 775)

### Método 2: Via Arquivo ZIP

1. Comprima todos os arquivos em um único arquivo ZIP
2. Acesse o painel de controle da Hostinger
3. Vá para "Arquivos" > "Gerenciador de Arquivos"
4. Faça upload do arquivo ZIP
5. Extraia o arquivo dentro da pasta `public_html/`
6. Defina permissões adequadas

## 4. Configuração do Banco de Dados

1. Acesse o painel de controle da Hostinger
2. Vá para "Banco de Dados" > "MySQL Databases"
3. Crie um novo banco de dados e anote:
   - Nome do banco de dados
   - Nome de usuário
   - Senha
4. Importe a estrutura do banco de dados:
   - Use o phpMyAdmin disponível no painel da Hostinger
   - Importe o arquivo SQL com a estrutura do banco de dados
   - Verifique se a importação foi bem-sucedida

## 5. Configurações finais

1. Atualize o arquivo `app/config/config.php` com as credenciais corretas do banco de dados
2. Verifique se o arquivo `.htaccess` está funcionando corretamente
3. Teste todas as URLs do site para garantir que a reescrita de URL esteja funcionando
4. Verifique se os uploads de arquivos estão funcionando corretamente
5. Teste o formulário de contato e outras funcionalidades críticas

## 6. Verificações de Segurança Pós-Deploy

Após o deploy, realize estas verificações de segurança:

1. **Teste de proteção CSRF**:
   - Tente submeter um formulário sem o token CSRF
   - Confirme que a submissão é rejeitada

2. **Verificação de Headers HTTP**:
   - Use [Security Headers](https://securityheaders.com/) para verificar os headers HTTP
   - Certifique-se de que todos os headers de segurança estão configurados corretamente

3. **Validação de Input**:
   - Teste formulários com dados maliciosos para garantir que a validação funciona
   - Verifique se a sanitização de saída está ativa em todas as páginas

4. **Uploads de Arquivos**:
   - Tente fazer upload de arquivos não permitidos para verificar se a validação funciona
   - Confirme que os arquivos enviados são armazenados com segurança

5. **Controle de Acesso**:
   - Verifique se áreas protegidas realmente exigem autenticação
   - Teste rotas administrativas para garantir que usuários normais não têm acesso

## 7. Manutenção e Atualizações

Para atualizar o site após alterações:

1. Teste todas as alterações localmente
2. Prepare um novo pacote de deploy apenas com os arquivos alterados
3. Faça backup dos arquivos que serão substituídos no servidor
4. Faça upload dos novos arquivos
5. Teste o site após as atualizações

## 8. Solução de Problemas Comuns

### Erro 500 - Internal Server Error
- Verifique o log de erros do PHP no painel da Hostinger
- Confirme se as configurações do PHP estão corretas
- Verifique se o arquivo `.htaccess` está compatível com as restrições da Hostinger

### Erro 404 - Página não encontrada
- Verifique se o mod_rewrite está ativado
- Teste se o `.htaccess` está funcionando com um arquivo de teste
- Confirme se as rotas estão configuradas corretamente

### Problemas de Banco de Dados
- Verifique se as credenciais no `config.php` estão corretas
- Confirme se o banco de dados está acessível
- Verifique se as tabelas foram criadas corretamente

### Uploads não funcionam
- Verifique as permissões do diretório de uploads
- Confirme os limites de upload no PHP e no `.htaccess`

## 9. Recursos da Hostinger

- **Painel de Controle**: https://hpanel.hostinger.com/
- **Suporte Técnico**: https://www.hostinger.com.br/contato
- **Base de Conhecimento**: https://www.hostinger.com.br/tutoriais/

## 10. Checklist Final

- [ ] Arquivos enviados para o servidor
- [ ] Banco de dados configurado
- [ ] Arquivo de configuração atualizado
- [ ] Permissões de diretórios definidas
- [ ] URLs e reescrita funcionando
- [ ] Formulários funcionando
- [ ] Uploads funcionando
- [ ] Segurança verificada
- [ ] Site testado em diferentes dispositivos
