# Registro de Vulnerabilidades

## Vulnerabilidades Identificadas

| ID | Data | Severidade | Status | Componente | CVE Relacionado |
|----|------|------------|--------|------------|-----------------|
| SEC-001 | 2025-04-03 | Alta | Pendente | InputValidator | N/A |
| SEC-002 | 2025-04-03 | Média | Pendente | InputValidator | N/A |
| SEC-003 | 2025-04-03 | Média | Pendente | UserAccountController | N/A |

## SEC-001: Bypass de Proteção XSS

### Descrição
A implementação atual da função de sanitização permite que atacantes utilizem técnicas de codificação e ofuscação para injetar scripts maliciosos que contornam os filtros de proteção XSS básicos implementados via `htmlspecialchars()`.

### Evidência Técnica
O teste automatizado `xss_encoded` no framework de validação revela que o payload `<img src="x" onerror="alert('XSS')">` não é adequadamente sanitizado, permitindo a execução de código JavaScript no contexto do usuário.

### Vetor de Ataque (CVSS 3.1)
- Vetor de Ataque: Rede
- Complexidade do Ataque: Baixa
- Privilégios Necessários: Nenhum
- Interação do Usuário: Necessária
- Escopo: Alterado
- Confidencialidade: Alta
- Integridade: Alta
- Disponibilidade: Baixa
- CVSS Base Score: 8.1

### Impacto
Um atacante pode executar scripts arbitrários no navegador da vítima, potencialmente comprometendo tokens de autenticação, realizando ações não autorizadas em nome do usuário, redirecionando para sites maliciosos, ou extraindo informações sensíveis.

### Solução Proposta
Implementar sanitização avançada com decodificação recursiva prévia e remoção de atributos de evento:

```php
/**
 * Sanitiza entrada contra tentativas avançadas de XSS
 *
 * @param string $value Valor a ser sanitizado
 * @return string Valor sanitizado
 */
public static function sanitizeXssAdvanced($value) {
    // Decodificar entidades HTML recursivamente
    $prev = '';
    $current = $value;
    
    // Continuar decodificando até que não haja mais mudanças
    // Isso detecta ataques em múltiplas camadas de codificação
    while ($prev !== $current) {
        $prev = $current;
        $current = html_entity_decode($current, ENT_QUOTES, 'UTF-8');
    }
    
    // Aplicar sanitização padrão
    $sanitized = htmlspecialchars($current, ENT_QUOTES, 'UTF-8');
    
    // Remover atributos de evento JavaScript (on*)
    $sanitized = preg_replace('/\bon\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $sanitized);
    
    // Remover atributos data- (potenciais vetores em navegadores modernos)
    $sanitized = preg_replace('/\bdata-\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', '', $sanitized);
    
    // Remover referências diretas a javascript:
    $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized);
    
    return $sanitized;
}
```

### Verificação
Após implementação, deve-se atualizar o teste `xss_encoded` para verificar se a função processa corretamente o payload malicioso.

### Referências
- OWASP XSS Prevention Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html
- CWE-79: Improper Neutralization of Input During Web Page Generation: https://cwe.mitre.org/data/definitions/79.html

## SEC-002: Manipulação Incorreta de Entradas SQL

### Descrição
A implementação atual sanitiza inputs que serão usados em prepared statements, o que é redundante e potencialmente problemático. Os valores devem ser passados em sua forma original para prepared statements, que já realizam o escape apropriado.

### Evidência Técnica
Os testes `sql_injection_basic` e `sql_injection_comments` falharam porque esperam que os valores originais sejam preservados para uso em prepared statements.

### Vetor de Ataque (CVSS 3.1)
- Vetor de Ataque: Rede
- Complexidade do Ataque: Média
- Privilégios Necessários: Nenhum
- Interação do Usuário: Nenhuma
- Escopo: Não alterado
- Confidencialidade: Média
- Integridade: Média
- Disponibilidade: Baixa
- CVSS Base Score: 6.5

### Impacto
Embora o uso de prepared statements mitigue a maioria dos riscos de injeção SQL, a sanitização precoce pode interferir com valores legítimos e criar inconsistências no comportamento da aplicação.

### Solução Proposta
Separar claramente a sanitização para exibição da validação para prepared statements:

```php
// Em InputValidator
/**
 * Valida entrada para uso em prepared statements
 * Não realiza sanitização, apenas validação de tipo e formato
 *
 * @param string $source Fonte da entrada
 * @param string $field Nome do campo
 * @param string $type Tipo de validação
 * @param array $options Opções de validação
 * @return mixed Valor validado mas não sanitizado
 */
public static function validateForDatabase($source, $field, $type, array $options = []) {
    // Forçar sanitize=false para garantir que o valor original seja retornado
    $options['sanitize'] = false;
    return self::validate($source, $field, $type, $options);
}
```

### Verificação
Os testes `sql_injection_basic` e `sql_injection_comments` devem passar após implementação da solução.

### Referências
- OWASP SQL Injection Prevention Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- CWE-89: Improper Neutralization of Special Elements used in an SQL Command: https://cwe.mitre.org/data/definitions/89.html

## SEC-003: Validação Insuficiente de Tokens

### Descrição
A implementação atual de validação de tokens CSRF e autenticação não utiliza comparações time-safe, tornando o sistema potencialmente vulnerável a ataques de timing.

### Evidência Técnica
Análise manual do código revelou o uso de operadores de comparação direta (`===`) em vez da função segura `hash_equals()`.

### Vetor de Ataque (CVSS 3.1)
- Vetor de Ataque: Rede
- Complexidade do Ataque: Alta
- Privilégios Necessários: Nenhum
- Interação do Usuário: Necessária
- Escopo: Não alterado
- Confidencialidade: Média
- Integridade: Média
- Disponibilidade: Nenhuma
- CVSS Base Score: 5.4

### Impacto
Atacantes sofisticados podem explorar diferenças sutis no tempo de resposta durante comparações de token para inferir informações sobre tokens válidos, potencialmente resultando em falsificação ou bypass de proteções CSRF.

### Solução Proposta
Implementar validação de token utilizando comparação time-safe:

```php
/**
 * Valida token CSRF usando comparação time-safe
 *
 * @param string $token Token fornecido para validação
 * @return bool True se o token for válido
 */
public static function validateToken($token) {
    // Verificações preliminares para evitar warnings
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (!is_string($token) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Usar hash_equals para evitar ataques de timing
    return hash_equals($_SESSION['csrf_token'], $token);
}
```

### Verificação
Implementar um teste específico que verifica se `hash_equals()` é utilizado para todas as comparações de tokens.

### Referências
- OWASP CSRF Prevention Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- CWE-208: Information Exposure Through Timing Discrepancy: https://cwe.mitre.org/data/definitions/208.html