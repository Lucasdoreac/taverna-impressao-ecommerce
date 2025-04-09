# Documentação de Segurança - Taverna da Impressão 3D

## Visão Geral

Esta documentação descreve as implementações de segurança do sistema Taverna da Impressão 3D. O projeto passou por uma revisão completa de segurança, corrigindo diversas vulnerabilidades e implementando uma biblioteca centralizada de segurança para padronizar proteções em toda a aplicação.

## Componentes de Segurança

### Biblioteca Centralizada de Segurança

A biblioteca de segurança oferece uma abordagem padronizada para proteções em toda a aplicação, incluindo:

- **SecurityManager**: Interface central para acesso às funcionalidades de segurança
- **Validator**: Validação de entrada com regras específicas por tipo de dados
- **Sanitizer**: Sanitização de saída para prevenção de XSS
- **CsrfProtection**: Proteção contra ataques Cross-Site Request Forgery
- **FileUploadManager**: Gerenciamento seguro de uploads de arquivos
- **BruteForceProtection**: Proteção contra ataques de força bruta
- **HeaderManager**: Configuração de cabeçalhos HTTP de segurança
- **AccessControl**: Sistema de controle de acesso baseado em papéis

### Vulnerabilidades Corrigidas

Foram identificadas e corrigidas vulnerabilidades em várias áreas:

| ID | Tipo | CWE | Severidade | Status |
|----|------|-----|------------|--------|
| SEC-001 | Maintaining application stability with graceful PHP exits | CWE-382 | Medium | Resolvido |
| SEC-002 | Cross-site scripting (XSS) | CWE-79 | Critical | Resolvido |
| SEC-003 | Unrestricted File Upload | CWE-434 | High | Resolvido |
| SEC-006 | Cross-site scripting (XSS) | CWE-79 | Critical | Resolvido |
| SEC-007 | Input Validation | CWE-20 | High | Resolvido |
| SEC-009 | Unrestricted File Upload | CWE-434 | High | Resolvido |
| SEC-010 | SQL Injection | CWE-89 | Critical | Resolvido |
| SEC-012 | Insecure Direct Object Reference | CWE-639 | High | Resolvido |

## Guias de Implementação

- [Access Control](./access-control.md)
- [CSRF Protection](./csrf-protection.md)
- [Input Validation](./input-validation.md)
- [Secure Headers](./secure-headers.md)
- [File Upload Security](./file-upload-security.md)

## Regras de Desenvolvimento Seguro

### SEMPRE

- SEMPRE sanitize toda entrada de usuário antes de processá-la ou exibi-la
- SEMPRE use prepared statements para consultas SQL
- SEMPRE valide tipos de arquivo e tamanho antes de permitir uploads
- SEMPRE use CSRF tokens em todos os formulários
- SEMPRE valide todas as entradas de dados (tipo, formato, tamanho)
- SEMPRE limite permissões de arquivos e diretórios ao mínimo necessário

### NUNCA

- NUNCA confie em dados provenientes do cliente (incluindo cookies e headers)
- NUNCA armazene senhas em texto plano
- NUNCA concatene entrada de usuário diretamente em consultas SQL
- NUNCA use eval() ou funções similares com entrada de usuário
- NUNCA use exit() ou die() em controllers
- NUNCA exiba mensagens de erro detalhadas para o usuário final

## Testes de Segurança

Para verificar a eficácia das implementações de segurança, foram desenvolvidos testes automatizados e manuais:

- Testes de CSRF
- Testes de XSS
- Testes de SQL Injection
- Testes de IDOR

Consulte [Testes de Segurança](./security-testing.md) para mais detalhes.

## Monitoramento de Segurança

O sistema implementa monitoramento contínuo através de:

- Logging de eventos de segurança
- Detecção de comportamentos suspeitos
- Alertas para tentativas de acesso não autorizado

## Referências

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [CWE Top 25](https://cwe.mitre.org/top25/)
- [PHP Security Best Practices](https://phpsecurity.readthedocs.io/en/latest/)