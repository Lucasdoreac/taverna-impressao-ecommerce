# Cabeçalhos HTTP de Segurança

## Visão Geral

Os cabeçalhos HTTP de segurança são uma camada crucial de proteção que ajuda a mitigar diversos vetores de ataque, incluindo XSS, clickjacking, e sniffing de conteúdo. A classe `HeaderManager` implementa um conjunto abrangente de cabeçalhos de segurança para proteger a aplicação Taverna da Impressão 3D.

## Classe HeaderManager

A classe `HeaderManager` fornece métodos para configurar e definir cabeçalhos HTTP relacionados à segurança:

```php
<?php
// Namespace real: App\Lib\Security
class HeaderManager {
    // Métodos principais
    public static function setSecurityHeaders();
    public static function setContentSecurityPolicy(array $customPolicy = []);
    public static function setStrictTransportSecurity($maxAge = 31536000, $includeSubDomains = true, $preload = false);
    public static function setFeaturePolicy(array $features = []);
    public static function setNoCacheHeaders();
    public static function setFrameOptions($mode = 'DENY', $allowFrom = '');
    public static function setDownloadHeaders($filename, $contentType, $contentLength, $inline = false);
    public static function setCorsHeaders($allowedOrigins = '*');
}
```

## Implementação Básica

### Bootstrap da Aplicação

Para aplicar cabeçalhos de segurança em toda a aplicação, implemente a seguinte configuração no ponto de entrada da aplicação:

```php
<?php
// No index.php ou bootstrap da aplicação
require_once 'app/lib/Security/HeaderManager.php';

// Definir cabeçalhos de segurança padrão
HeaderManager::setSecurityHeaders();

// Definir Content Security Policy (CSP)
HeaderManager::setContentSecurityPolicy();

// Definir HSTS para conexões HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    HeaderManager::setStrictTransportSecurity();
}

// Continuar com o processamento da requisição
// ...
```

### Middleware de Segurança

Alternativamente, implemente um middleware para aplicar cabeçalhos de segurança:

```php
<?php
class SecurityHeadersMiddleware {
    public function handle($request, $next) {
        // Aplicar cabeçalhos de segurança básicos
        HeaderManager::setSecurityHeaders();
        
        // Definir CSP baseado na rota atual
        $path = $_SERVER['REQUEST_URI'];
        
        if (strpos($path, '/admin') === 0) {
            // CSP mais restritivo para área administrativa
            HeaderManager::setContentSecurityPolicy([
                'script-src' => "'self'",
                'style-src' => "'self'"
            ]);
        } else {
            // CSP padrão para o resto do site
            HeaderManager::setContentSecurityPolicy();
        }
        
        // HSTS para conexões HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            HeaderManager::setStrictTransportSecurity();
        }
        
        // Continuar para o próximo middleware ou controller
        return $next($request);
    }
}
```

## Cabeçalhos Implementados

### Content Security Policy (CSP)

O CSP controla quais recursos o navegador pode carregar, mitigando ataques XSS e injeção de dados:

```php
<?php
// Política CSP padrão
HeaderManager::setContentSecurityPolicy();

// Ou com opções personalizadas
HeaderManager::setContentSecurityPolicy([
    'default-src' => "'self'",
    'script-src' => "'self' https://cdnjs.cloudflare.com",
    'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
    'img-src' => "'self' data: https://secure.example.com",
    'font-src' => "'self' https://fonts.gstatic.com",
    'connect-src' => "'self' https://api.example.com",
    'frame-src' => "'none'",
    'object-src' => "'none'",
    'base-uri' => "'self'"
]);
```

A política padrão aplicada inclui:

| Diretiva | Valor Padrão | Descrição |
|----------|--------------|-----------|
| default-src | 'self' | Restringe recursos ao mesmo domínio |
| script-src | 'self' https://cdnjs.cloudflare.com | Restringe JavaScript a domínios confiáveis |
| style-src | 'self' 'unsafe-inline' https://cdnjs.cloudflare.com | Restringe CSS a domínios confiáveis |
| img-src | 'self' data: | Restringe imagens ao mesmo domínio e data URIs |
| font-src | 'self' https://cdnjs.cloudflare.com | Restringe fontes a domínios confiáveis |
| connect-src | 'self' | Restringe conexões XHR/fetch ao mesmo domínio |
| frame-src | 'none' | Impede o uso de frames |
| object-src | 'none' | Impede plugins e objetos incorporados |
| base-uri | 'self' | Restringe elemento base ao mesmo domínio |

### HTTP Strict Transport Security (HSTS)

O HSTS força conexões HTTPS, prevenindo ataques de downgrade e man-in-the-middle:

```php
<?php
// HSTS padrão (1 ano, incluindo subdomínios)
HeaderManager::setStrictTransportSecurity();

// Ou personalizado
HeaderManager::setStrictTransportSecurity(
    $maxAge = 2592000,            // 30 dias
    $includeSubDomains = false,   // Apenas domínio principal
    $preload = false              // Sem preload list
);
```

### X-Frame-Options

Previne clickjacking controlando como a página pode ser incorporada em frames:

```php
<?php
// Impedir qualquer incorporação em frame (padrão)
HeaderManager::setFrameOptions('DENY');

// Permitir frames apenas no mesmo domínio
HeaderManager::setFrameOptions('SAMEORIGIN');

// Permitir frames apenas em domínios específicos
HeaderManager::setFrameOptions('ALLOW-FROM', 'https://trusted-domain.com');
```

### X-Content-Type-Options

Previne MIME sniffing, forçando o navegador a usar o tipo MIME declarado:

```php
<?php
// Já incluído em setSecurityHeaders()
header('X-Content-Type-Options: nosniff');
```

### Referrer-Policy

Controla quais informações de referência são incluídas nas requisições:

```php
<?php
// Já incluído em setSecurityHeaders()
header('Referrer-Policy: strict-origin-when-cross-origin');
```

### Permissions-Policy (Feature-Policy)

Controla quais APIs e recursos o navegador pode usar:

```php
<?php
// Política de permissões padrão
HeaderManager::setFeaturePolicy();

// Ou personalizada
HeaderManager::setFeaturePolicy([
    'camera' => "'self'",         // Permitir câmera apenas no mesmo domínio
    'microphone' => "'none'",     // Desabilitar microfone completamente
    'geolocation' => "'self'",    // Permitir geolocalização apenas no mesmo domínio
    'payment' => "'self'"         // Permitir API de pagamento apenas no mesmo domínio
]);
```

### Cache-Control

Previne o armazenamento em cache de dados sensíveis:

```php
<?php
// Para páginas sensíveis/autenticadas
HeaderManager::setNoCacheHeaders();
```

### CORS Headers

Controla quais domínios podem acessar recursos via AJAX:

```php
<?php
// Permitir acesso CORS de qualquer origem (não recomendado para produção)
HeaderManager::setCorsHeaders('*');

// Permitir acesso CORS de domínios específicos
HeaderManager::setCorsHeaders([
    'https://app.example.com',
    'https://admin.example.com'
]);
```

## Casos de Uso Específicos

### Download de Arquivos

```php
<?php
class DownloadController extends Controller {
    public function model($id) {
        // Obter modelo do banco de dados
        $model = $this->modelRepository->getById($id);
        
        if (!$model) {
            header('HTTP/1.1 404 Not Found');
            echo "Modelo não encontrado";
            return;
        }
        
        // Verificar permissão
        if (!AccessControl::canUserAccessObject($_SESSION['user_id'], $id, 'customer_model', 'download')) {
            header('HTTP/1.1 403 Forbidden');
            echo "Acesso negado";
            return;
        }
        
        // Caminho do arquivo
        $filePath = UPLOADS_PATH . '/models/' . $model['filename'];
        
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            echo "Arquivo não encontrado";
            return;
        }
        
        // Obter informações do arquivo
        $fileSize = filesize($filePath);
        $contentType = $this->getContentType($model['extension']);
        
        // Definir cabeçalhos para download
        HeaderManager::setDownloadHeaders(
            $model['original_filename'],
            $contentType,
            $fileSize,
            false // Download forçado
        );
        
        // Enviar arquivo
        readfile($filePath);
        exit;
    }
    
    private function getContentType($extension) {
        $contentTypes = [
            'stl' => 'application/sla',
            'obj' => 'application/object',
            '3mf' => 'application/vnd.ms-package.3dmanufacturing-3dmodel+xml'
        ];
        
        return $contentTypes[$extension] ?? 'application/octet-stream';
    }
}
```

### API REST

```php
<?php
class ApiController extends Controller {
    public function __construct() {
        parent::__construct();
        
        // Configurar CORS para a API
        HeaderManager::setCorsHeaders([
            'https://app.tavernada3d.com',
            'https://admin.tavernada3d.com'
        ]);
        
        // Definir cabeçalhos de segurança para API
        HeaderManager::setSecurityHeaders();
        
        // Definir CSP específico para API
        HeaderManager::setContentSecurityPolicy([
            'default-src' => "'none'",
            'connect-src' => "'self'"
        ]);
    }
    
    public function products() {
        // Definir tipo de conteúdo JSON
        header('Content-Type: application/json');
        
        // Restante do código da API...
        $products = $this->productModel->getAll();
        echo json_encode($products);
    }
}
```

## Monitoramento e Auditoria

### Verificação de Cabeçalhos

```php
<?php
class SecurityAudit {
    public function checkSecurityHeaders() {
        // Obter todos os cabeçalhos
        $headers = HeaderManager::getAllHeaders();
        
        // Cabeçalhos críticos para verificar
        $criticalHeaders = [
            'Content-Security-Policy',
            'X-Frame-Options',
            'X-Content-Type-Options',
            'Strict-Transport-Security'
        ];
        
        // Verificar presença de cabeçalhos críticos
        $missing = [];
        foreach ($criticalHeaders as $header) {
            $found = false;
            foreach ($headers as $h) {
                if (strpos($h, $header . ':') === 0) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $missing[] = $header;
            }
        }
        
        // Registrar cabeçalhos ausentes
        if (!empty($missing)) {
            error_log('Cabeçalhos de segurança ausentes: ' . implode(', ', $missing));
        }
        
        return empty($missing);
    }
}
```

## Testes de Segurança

### Verificação de Content Security Policy

```php
<?php
class CspTest {
    public function testCsp() {
        // Simular requisição
        $_SERVER['REQUEST_URI'] = '/product/view/123';
        
        // Capturar cabeçalhos
        ob_start();
        HeaderManager::setContentSecurityPolicy();
        $headers = xdebug_get_headers(); // Requer extensão xdebug
        ob_end_clean();
        
        // Verificar se CSP foi definido
        $cspFound = false;
        foreach ($headers as $header) {
            if (strpos($header, 'Content-Security-Policy:') === 0) {
                $cspFound = true;
                echo "CSP encontrado: " . $header . "\n";
                break;
            }
        }
        
        return $cspFound;
    }
}
```

## Boas Práticas

1. **Aplicar em toda a aplicação**: Incluir cabeçalhos de segurança em todas as respostas HTTP
2. **Ajustar para diferentes rotas**: Usar políticas diferentes baseadas no contexto da requisição
3. **Começar restritivo**: Iniciar com políticas restritivas e afrouxar conforme necessário
4. **Testar cuidadosamente**: Verificar se os cabeçalhos não quebram funcionalidades existentes
5. **Monitorar violações**: Configurar relatório de violações para identificar problemas
6. **Atualizar regularmente**: Revisar e atualizar políticas conforme novas ameaças são descobertas

## Referências

- [OWASP Secure Headers Project](https://owasp.org/www-project-secure-headers/)
- [Mozilla Observatory](https://observatory.mozilla.org/)
- [Content Security Policy Reference](https://content-security-policy.com/)
- [MDN: HTTP Security Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers#security)