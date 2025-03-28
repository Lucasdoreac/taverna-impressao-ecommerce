# Tarefas Pós-Implantação TAVERNA DA IMPRESSÃO

Este documento lista as tarefas críticas a serem executadas após a finalização da implantação do e-commerce TAVERNA DA IMPRESSÃO no servidor Hostinger.

## Tarefas de Segurança Críticas

### 1. Remoção de Arquivos de Diagnóstico
- [ ] Remover arquivo `public/phpinfo.php` 
- [ ] Remover qualquer arquivo temporário de teste de banco de dados
- [ ] Remover arquivos de testes de rotas

### 2. Configurações de Ambiente
- [x] Configurar ENVIRONMENT para 'production' em config.php
- [x] Desativar DISPLAY_ERRORS em config.php
- [x] Verificar se LOG_ERRORS está ativado
- [ ] Remover headers de debug no .htaccess da pasta public

### 3. Verificação de Permissões
- [ ] Diretório `/public/uploads`: 755
- [ ] Diretório `/logs`: 755
- [ ] Arquivos PHP: 644
- [ ] Arquivos `.htaccess`: 644
- [ ] Garantir que nenhum arquivo tenha permissão 777

### 4. Proteção de Diretórios
- [ ] Adicionar proteção ao diretório `/app` via .htaccess
- [ ] Adicionar proteção ao diretório `/database` via .htaccess
- [ ] Adicionar proteção ao diretório `/vendor` via .htaccess
- [ ] Adicionar proteção ao diretório `/logs` via .htaccess

## Otimizações e Monitoramento

### 1. Backups Automáticos
- [ ] Configurar backup diário do banco de dados
- [ ] Configurar backup semanal dos arquivos
- [ ] Testar processo de restauração de backup

### 2. Monitoramento
- [ ] Implementar monitoramento de uptime
- [ ] Configurar alertas para erros críticos
- [ ] Implementar monitoramento de desempenho

### 3. Otimizações
- [ ] Minificar arquivos CSS e JavaScript
- [ ] Otimizar imagens
- [ ] Configurar cache de navegador para recursos estáticos
- [ ] Implementar compressão GZIP

## Testes Finais

### 1. Testes de Funcionalidade
- [ ] Testar registro de usuário
- [ ] Testar login
- [ ] Testar recuperação de senha
- [ ] Testar navegação por categorias
- [ ] Testar busca de produtos
- [ ] Testar adição ao carrinho
- [ ] Testar processo de checkout
- [ ] Testar personalização de produtos
- [ ] Testar painel administrativo

### 2. Testes de Compatibilidade
- [ ] Testar em Chrome, Firefox, Safari e Edge
- [ ] Testar em dispositivos móveis
- [ ] Testar em diferentes resoluções

## Lista de Verificação Final

- [ ] Todos os logs estão sendo registrados corretamente
- [ ] Não há erros 404 ou 500 inesperados
- [ ] Todas as rotas principais funcionam
- [ ] A aplicação está estável sob carga
- [ ] Certificado SSL está funcionando corretamente
- [ ] Os redirecionamentos de HTTP para HTTPS funcionam
- [ ] As imagens e uploads funcionam corretamente
- [ ] Emails são enviados corretamente

---

**IMPORTANTE**: Este documento deve ser mantido atualizado com o status das tarefas. Marque cada item como concluído à medida que for implementado e testado.

Última atualização: 28/03/2025
