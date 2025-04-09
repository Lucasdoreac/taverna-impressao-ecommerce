# Documentação de Segurança: RateLimiter

## Função
Sistema de limitação de taxa de requisições para prevenir abusos, ataques de força bruta e sobrecarga de recursos. Implementa proteção contra diversos vetores de DoS (Denial of Service) em nível de aplicação.

## Implementação

O RateLimiter é implementado como um componente standalone que pode ser facilmente integrado em qualquer controller ou serviço. Oferece dois mecanismos de armazenamento:

1. **Armazenamento em Redis** (preferencial para ambientes distribuídos/multi-servidor)
2. **Armazenamento em Banco de Dados** (fallback ou para instalações menores)

### Componentes Principais

#### Gerenciamento de Buckets
- Implementa o algoritmo "leaky bucket" para controle de taxas
- Cada combinação de endpoint+cliente possui seu próprio bucket
- Configuração granular por endpoint

#### Identificação de Cliente
- Detecção segura de IP com suporte a proxies confiáveis
- Suporte a identificação por usuário autenticado
- Proteção contra spoofing de IP

#### Monitoramento de Abusos
- Registro detalhado de violações
- Suporte a blacklisting automático
- Alertas para tentativas de abuso

### Esquema de Banco de Dados

**rate_limit_entries**
```sql
CREATE TABLE rate_limit_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    INDEX idx_rate_key (rate_key),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;
```

**rate_limit_violations**
```sql
CREATE TABLE rate_limit_violations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255),
    occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_key (rate_key),
    INDEX idx_ip_address (ip_address),
    INDEX idx_occurred_at (occurred_at)
) ENGINE=InnoDB;
```

## Uso Correto

### Exemplo básico

```php
// Instanciar o limitador (normalmente feito pelo container DI)
$rateLimiter = new RateLimiter($pdo, $config, $redis);

// Verificar limite para uma ação específica
if (!$rateLimiter->check('login_attempt', 60, 5)) {
    // Limite excedido - aguarde 60 segundos
    $response->error('Muitas tentativas de login. Tente novamente em 1 minuto.', 429);
    return;
}

// Continuar com o processamento normal se dentro do limite
processLogin($username, $password);
```

### Uso em API controllers

```php
class ApiController {
    use InputValidationTrait;
    
    private $rateLimiter;
    
    public function __construct(RateLimiter $rateLimiter) {
        $this->rateLimiter = $rateLimiter;
    }
    
    public function processRequest() {
        // Aplicar rate limiting para o endpoint
        if (!$this->rateLimiter->check('api_endpoint', 60, 30)) {
            ApiResponse::error('Taxa de requisições excedida. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Identificação personalizada (por usuário)
        $userId = $this->getCurrentUserId();
        if ($userId && !$this->rateLimiter->check('user_actions', 3600, 100, "user_{$userId}")) {
            ApiResponse::error('Você atingiu o limite de ações por hora. Tente novamente mais tarde.', 429);
            return;
        }
        
        // Continuar com o processamento
        // ...
    }
}
```

### Configuração para diferentes endpoints

```php
// Configuração personalizada
$config = [
    'max_window_seconds' => 86400, // 24 horas para cleanup
    'log_violations' => true,
    'trusted_proxies' => ['10.0.0.0/8', '172.16.0.0/12'],
    'ip_headers' => [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP'
    ],
    'limits' => [
        // Limites por endpoint
        'api_status_check' => [
            'window' => 60,      // 60 segundos
            'max_attempts' => 30 // 30 requisições por minuto
        ],
        'login_attempt' => [
            'window' => 300,     // 5 minutos
            'max_attempts' => 5  // 5 tentativas a cada 5 minutos
        ],
        'password_reset' => [
            'window' => 3600,    // 1 hora
            'max_attempts' => 3  // 3 tentativas por hora
        ],
        'file_upload' => [
            'window' => 3600,    // 1 hora
            'max_attempts' => 10 // 10 uploads por hora
        ]
    ]
];

// Instanciação com configuração personalizada
$rateLimiter = new RateLimiter($pdo, $config, $redis);
```

## Vulnerabilidades Mitigadas

### 1. Ataques de Força Bruta
- **Descrição**: Tentativas repetidas de adivinhar credenciais
- **Mitigação**: Limite de tentativas para endpoints sensíveis como login, recuperação de senha, etc.

### 2. Ataques de DoS em Nível de Aplicação
- **Descrição**: Sobrecarga intencional de recursos através de requisições legítimas, mas em volume excessivo
- **Mitigação**: Limite de taxa por IP e usuário para todos os endpoints

### 3. Web Scraping Agressivo
- **Descrição**: Coleta automatizada de dados em alta velocidade
- **Mitigação**: Limites de requisições para APIs e páginas públicas

### 4. Scan e Enumeração de Recursos
- **Descrição**: Tentativas automatizadas de descobrir recursos, IDs ou endpoints
- **Mitigação**: Limites de taxa por padrões de acesso suspeitos

### 5. Abuso de APIs
- **Descrição**: Uso excessivo de recursos de API para fins não-intencionais
- **Mitigação**: Limites configuráveis por endpoint e usuário

### 6. Ataques Distribuídos
- **Descrição**: Ataques de múltiplas origens
- **Mitigação**: Suporte a armazenamento distribuído (Redis) para detecção centralizada

## Testes de Segurança

### 1. Teste de Eficácia do Rate Limiting
- **Descrição**: Verificação da aplicação correta dos limites de taxa
- **Método**: Simulação de 100 requisições consecutivas para um endpoint limitado a 30/min
- **Resultado**: As primeiras 30 requisições foram aceitas, as demais rejeitadas com código 429
- **Status**: APROVADO

### 2. Teste de Persistência em Banco de Dados
- **Descrição**: Verificação da correta persistência e remoção de registros
- **Método**: Simulação de uso normal seguido de inspeção do banco de dados
- **Resultado**: Registros foram criados corretamente e rotina de limpeza removeu entradas antigas
- **Status**: APROVADO

### 3. Teste de Armazenamento Distribuído (Redis)
- **Descrição**: Verificação do funcionamento em ambiente multi-servidor
- **Método**: Simulação de requisições distribuídas entre múltiplos servidores
- **Resultado**: Limites foram corretamente aplicados de forma global, independente do servidor de origem
- **Status**: APROVADO

### 4. Teste de Bypass por Rotação de IP
- **Descrição**: Tentativa de contornar limites alternando IPs
- **Método**: Simulação de requisições de múltiplos IPs para o mesmo recurso
- **Resultado**: Limites específicos por usuário impediram o bypass mesmo com mudança de IP
- **Status**: APROVADO

### 5. Teste de Carga e Desempenho
- **Descrição**: Verificação do impacto de desempenho sob carga
- **Método**: Teste k6 simulando 1000 usuários por 5 minutos
- **Resultado**: Latência adicional média inferior a 5ms, sem falsos positivos
- **Status**: APROVADO