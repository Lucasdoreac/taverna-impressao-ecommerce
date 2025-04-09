# Testes de Segurança

## Visão Geral

Este documento descreve as estratégias e ferramentas para testar a segurança da aplicação Taverna da Impressão 3D. Os testes de segurança são essenciais para validar que as implementações de segurança estão funcionando corretamente e para identificar vulnerabilidades remanescentes.

## Tipos de Testes

### Testes Automatizados

Testes automatizados são executados regularmente para verificar a presença de vulnerabilidades conhecidas:

- **Testes Unitários**: Verificam componentes individuais de segurança
- **Testes de Integração**: Verificam a interação entre diferentes componentes de segurança
- **Testes de Penetração Automatizados**: Simulam ataques conhecidos para identificar vulnerabilidades

### Testes Manuais

Testes manuais são conduzidos por especialistas de segurança para identificar vulnerabilidades que podem não ser detectadas por testes automatizados:

- **Code Review**: Revisão manual de código em busca de vulnerabilidades
- **Testes de Penetração Manuais**: Simulação manual de ataques
- **Análise de Configuração**: Verificação de configurações de segurança

## Ferramentas de Teste

### Análise Estática de Código

```bash
# Executar PHP CodeSniffer com regras de segurança
phpcs --standard=Security app/

# Executar PHPMD com regras de segurança
phpmd app/ text codesize,unusedcode,naming,design,controversial,cleancode

# Executar PHPStan para análise estática avançada
phpstan analyse -l max app/
```

### Análise Dinâmica

```bash
# Executar OWASP ZAP para análise dinâmica
docker run -t owasp/zap2docker-stable zap-baseline.py -t https://darkblue-cattle-647559.hostingersite.com -g gen.conf -r testreport.html

# Executar Burp Suite para análise dinâmica
java -jar burpsuite_community.jar -project taverna-scan.burp
```

## Testes de Segurança Específicos

### Teste de XSS (Cross-Site Scripting)

```php
<?php
class XssTest extends TestCase {
    public function testSanitizationInOutputs() {
        // Preparar entrada maliciosa
        $maliciousInput = '<script>alert("XSS")</script>';
        
        // Configurar mock para Sanitizer
        $sanitizerMock = $this->getMockBuilder(Sanitizer::class)
            ->getMock();
        
        $sanitizerMock->expects($this->once())
            ->method('html')
            ->with($maliciousInput)
            ->willReturn('&lt;script&gt;alert("XSS")&lt;/script&gt;');
        
        // Injetar mock
        $controller = new ProductController();
        $controller->setSanitizer($sanitizerMock);
        
        // Executar método que utiliza sanitização
        $output = $controller->sanitizeDescription($maliciousInput);
        
        // Verificar que o resultado foi sanitizado
        $this->assertEquals('&lt;script&gt;alert("XSS")&lt;/script&gt;', $output);
    }
    
    public function testXssInForms() {
        // Configurar cliente de teste
        $client = $this->createClient();
        
        // Submeter formulário com payload XSS
        $client->request('POST', '/product/add', [
            'name' => '<script>alert("XSS")</script>',
            'description' => 'Test description',
            'price' => 99.99
        ]);
        
        // Verificar resposta
        $response = $client->getResponse();
        $content = $response->getContent();
        
        // Verificar que o script não está presente na resposta
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $content);
        
        // Verificar que a versão sanitizada está presente
        $this->assertStringContainsString('&lt;script&gt;alert("XSS")&lt;/script&gt;', $content);
    }
    
    public function testReflectedXss() {
        // Testar parâmetros de URL
        $client = $this->createClient();
        $client->request('GET', '/search?q=' . urlencode('<script>alert("XSS")</script>'));
        
        $response = $client->getResponse();
        $content = $response->getContent();
        
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $content);
        $this->assertStringContainsString('&lt;script&gt;alert("XSS")&lt;/script&gt;', $content);
    }
}
```

### Teste de CSRF (Cross-Site Request Forgery)

```php
<?php
class CsrfTest extends TestCase {
    public function testCsrfProtectionEnabled() {
        // Verificar se token CSRF é gerado e incluído em formulários
        $client = $this->createClient();
        $crawler = $client->request('GET', '/product/add');
        
        // Verificar se há campo hidden com token CSRF
        $this->assertGreaterThan(0, $crawler->filter('input[name="csrf_token"]')->count());
    }
    
    public function testFormSubmissionWithoutCsrf() {
        // Testar formulário sem token CSRF
        $client = $this->createClient();
        $client->request('POST', '/product/add', [
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 99.99
            // Sem token CSRF
        ]);
        
        // Verificar que a requisição foi rejeitada
        $response = $client->getResponse();
        $this->assertTrue($response->isClientError());
        $this->assertStringContainsString('CSRF token inválido', $response->getContent());
    }
    
    public function testFormSubmissionWithInvalidCsrf() {
        // Testar formulário com token CSRF inválido
        $client = $this->createClient();
        $client->request('POST', '/product/add', [
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 99.99,
            'csrf_token' => 'invalid_token_123'
        ]);
        
        // Verificar que a requisição foi rejeitada
        $response = $client->getResponse();
        $this->assertTrue($response->isClientError());
        $this->assertStringContainsString('CSRF token inválido', $response->getContent());
    }
    
    public function testFormSubmissionWithValidCsrf() {
        // Obter token CSRF válido
        $client = $this->createClient();
        $crawler = $client->request('GET', '/product/add');
        $token = $crawler->filter('input[name="csrf_token"]')->attr('value');
        
        // Testar formulário com token CSRF válido
        $client->request('POST', '/product/add', [
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 99.99,
            'csrf_token' => $token
        ]);
        
        // Verificar que a requisição foi aceita
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection());
    }
}
```

### Teste de SQL Injection

```php
<?php
class SqlInjectionTest extends TestCase {
    public function testOrderByClauseSanitization() {
        // Testar método validateOrderBy da classe ProductModel
        $productModel = new ProductModel();
        
        // Caso válido
        $validOrderBy = 'name ASC';
        $result = $productModel->validateOrderBy($validOrderBy);
        $this->assertEquals('name ASC', $result);
        
        // Tentativa de injeção SQL
        $maliciousOrderBy = 'name; DROP TABLE users; --';
        $result = $productModel->validateOrderBy($maliciousOrderBy);
        
        // Deve retornar ordenação padrão ou apenas a parte válida
        $this->assertEquals('name', $result);
    }
    
    public function testPreparedStatements() {
        // Mock para o banco de dados
        $dbMock = $this->createMock(Database::class);
        
        // Configurar expectativa: deve usar prepared statement
        $dbMock->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM products WHERE id = ?')
            ->willReturn($this->createMock(\PDOStatement::class));
        
        // Injetar mock na classe a ser testada
        $productModel = new ProductModel($dbMock);
        
        // Executar método que deve usar prepared statement
        $productModel->getById(1);
    }
    
    public function testSearchFunctionSanitization() {
        // Testar função de busca com input potencialmente malicioso
        $client = $this->createClient();
        
        // Payload de SQL Injection
        $payload = "' OR '1'='1";
        
        // Fazer requisição com payload
        $client->request('GET', '/search?q=' . urlencode($payload));
        
        // Verificar que a aplicação não apresenta erro ou divulgação de informações
        $response = $client->getResponse();
        $content = $response->getContent();
        
        // Não deve conter mensagens de erro de SQL
        $this->assertStringNotContainsString('SQL syntax', $content);
        $this->assertStringNotContainsString('mysql_fetch', $content);
        $this->assertStringNotContainsString('ORA-', $content);
        
        // Não deve conter mais resultados do que o esperado
        // (isso depende da implementação específica)
    }
}
```

### Teste de IDOR (Insecure Direct Object References)

```php
<?php
class IdorTest extends TestCase {
    public function testAccessControlForOrderViewing() {
        // Configurar usuários de teste
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        
        // Criar ordem para user1
        $order = $this->createOrderForUser($user1->id);
        
        // Tentar acessar como user2
        $client = $this->createClient();
        $this->loginAs($client, $user2);
        
        $client->request('GET', '/order/view/' . $order->id);
        
        // Deve receber acesso negado
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    public function testAccessControlWithCorrectUser() {
        // Configurar usuário de teste
        $user = $this->createUser();
        
        // Criar ordem para o usuário
        $order = $this->createOrderForUser($user->id);
        
        // Acessar como o mesmo usuário
        $client = $this->createClient();
        $this->loginAs($client, $user);
        
        $client->request('GET', '/order/view/' . $order->id);
        
        // Deve ter acesso
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testAccessControlWithAdminUser() {
        // Configurar usuários de teste
        $user = $this->createUser();
        $admin = $this->createUser(['role' => 'admin']);
        
        // Criar ordem para user
        $order = $this->createOrderForUser($user->id);
        
        // Acessar como admin
        $client = $this->createClient();
        $this->loginAs($client, $admin);
        
        $client->request('GET', '/order/view/' . $order->id);
        
        // Admin deve ter acesso
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testAccessControlForModelEditing() {
        // Configurar usuários de teste
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        
        // Criar modelo 3D para user1
        $model = $this->createModelForUser($user1->id);
        
        // Tentar editar como user2
        $client = $this->createClient();
        $this->loginAs($client, $user2);
        
        $client->request('POST', '/model/edit/' . $model->id, [
            'name' => 'Changed Name',
            'description' => 'Changed Description',
            'csrf_token' => $this->getCsrfToken($client)
        ]);
        
        // Deve receber acesso negado
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }
}
```

### Teste de Upload de Arquivos

```php
<?php
class FileUploadTest extends TestCase {
    public function testValidFileUpload() {
        // Criar arquivo de teste válido
        $filepath = $this->createTestFile('test.stl', 'valid stl content');
        
        // Configurar cliente de teste
        $client = $this->createClient();
        $this->loginAs($client, $this->createUser());
        
        // Submeter formulário de upload
        $client->request('GET', '/model/upload');
        $form = $client->crawler()->selectButton('Upload')->form();
        $form['model_file']->upload($filepath);
        $form['model_name'] = 'Test Model';
        $client->submit($form);
        
        // Verificar que upload foi aceito
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection());
        
        // Verificar que arquivo foi salvo no banco de dados
        $modelRepository = new ModelRepository();
        $model = $modelRepository->findOneBy(['name' => 'Test Model']);
        $this->assertNotNull($model);
    }
    
    public function testInvalidExtensionUpload() {
        // Criar arquivo com extensão não permitida
        $filepath = $this->createTestFile('malicious.php', '<?php echo "test"; ?>');
        
        // Configurar cliente de teste
        $client = $this->createClient();
        $this->loginAs($client, $this->createUser());
        
        // Submeter formulário de upload
        $client->request('GET', '/model/upload');
        $form = $client->crawler()->selectButton('Upload')->form();
        $form['model_file']->upload($filepath);
        $form['model_name'] = 'Test Model';
        $client->submit($form);
        
        // Verificar que upload foi rejeitado
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode()); // Não redireciona
        $this->assertStringContainsString('Tipo de arquivo não permitido', $response->getContent());
    }
    
    public function testOverSizeFileUpload() {
        // Criar arquivo muito grande
        $filepath = $this->createLargeTestFile('large.stl', 51 * 1024 * 1024); // 51MB
        
        // Configurar cliente de teste
        $client = $this->createClient();
        $this->loginAs($client, $this->createUser());
        
        // Submeter formulário de upload
        $client->request('GET', '/model/upload');
        $form = $client->crawler()->selectButton('Upload')->form();
        $form['model_file']->upload($filepath);
        $form['model_name'] = 'Test Model';
        $client->submit($form);
        
        // Verificar que upload foi rejeitado
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode()); // Não redireciona
        $this->assertStringContainsString('Arquivo muito grande', $response->getContent());
    }
    
    public function testMaliciousContentUpload() {
        // Criar arquivo com extensão STL mas conteúdo PHP
        $filepath = $this->createTestFile('fake.stl', '<?php echo "malicious"; ?>');
        
        // Configurar cliente de teste
        $client = $this->createClient();
        $this->loginAs($client, $this->createUser());
        
        // Submeter formulário de upload
        $client->request('GET', '/model/upload');
        $form = $client->crawler()->selectButton('Upload')->form();
        $form['model_file']->upload($filepath);
        $form['model_name'] = 'Test Model';
        $client->submit($form);
        
        // Verificar que upload foi rejeitado
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode()); // Não redireciona
        $this->assertStringContainsString('Tipo de arquivo inválido', $response->getContent());
    }
}
```

### Teste de Headers HTTP

```php
<?php
class HeadersTest extends TestCase {
    public function testSecurityHeadersPresent() {
        // Fazer requisição
        $client = $this->createClient();
        $client->request('GET', '/');
        
        // Obter resposta
        $response = $client->getResponse();
        $headers = $response->headers->all();
        
        // Verificar cabeçalhos de segurança
        $this->assertArrayHasKey('x-frame-options', $headers);
        $this->assertEquals('DENY', $headers['x-frame-options'][0]);
        
        $this->assertArrayHasKey('x-content-type-options', $headers);
        $this->assertEquals('nosniff', $headers['x-content-type-options'][0]);
        
        $this->assertArrayHasKey('x-xss-protection', $headers);
        $this->assertEquals('1; mode=block', $headers['x-xss-protection'][0]);
        
        $this->assertArrayHasKey('referrer-policy', $headers);
        $this->assertEquals('strict-origin-when-cross-origin', $headers['referrer-policy'][0]);
        
        $this->assertArrayHasKey('permissions-policy', $headers);
    }
    
    public function testContentSecurityPolicy() {
        // Fazer requisição
        $client = $this->createClient();
        $client->request('GET', '/');
        
        // Obter resposta
        $response = $client->getResponse();
        $headers = $response->headers->all();
        
        // Verificar CSP
        $this->assertArrayHasKey('content-security-policy', $headers);
        $csp = $headers['content-security-policy'][0];
        
        // Verificar diretivas críticas
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }
    
    public function testHstsHeader() {
        // Fazer requisição HTTPS
        $client = $this->createClient();
        $client->request('GET', 'https://localhost/');
        
        // Obter resposta
        $response = $client->getResponse();
        $headers = $response->headers->all();
        
        // Verificar HSTS
        $this->assertArrayHasKey('strict-transport-security', $headers);
        $hsts = $headers['strict-transport-security'][0];
        
        // Verificar configurações
        $this->assertStringContainsString("max-age=", $hsts);
        $this->assertStringContainsString("includeSubDomains", $hsts);
    }
}
```

## Scripts de Teste Automatizado

### Script de Verificação de XSS

```php
<?php
// scripts/security-test/xss_scanner.php

require_once __DIR__ . '/../../vendor/autoload.php';

// Configurações
$baseUrl = 'https://darkblue-cattle-647559.hostingersite.com';
$endpoints = [
    '/search?q=',
    '/product/view/',
    '/category/',
    '/contact?message='
];
$payloads = [
    '<script>alert("XSS")</script>',
    '"><script>alert("XSS")</script>',
    '<img src="x" onerror="alert(\'XSS\')">',
    '<body onload="alert(\'XSS\')">',
    "';alert('XSS');//"
];

// Iniciar cliente HTTP
$client = new GuzzleHttp\Client([
    'base_uri' => $baseUrl,
    'cookies' => true,
    'allow_redirects' => true
]);

// Função para verificar presença de XSS
function checkForXss($response, $payload) {
    $body = (string) $response->getBody();
    
    // Verificar se o payload está presente sem sanitização
    if (strpos($body, $payload) !== false) {
        return true;
    }
    
    // Verificar se consegue executar JavaScript
    if (strpos($body, 'alert(') !== false || 
        strpos($body, 'onerror=') !== false || 
        strpos($body, '<script>') !== false) {
        return true;
    }
    
    return false;
}

// Executar testes
$vulnerabilities = [];

foreach ($endpoints as $endpoint) {
    foreach ($payloads as $payload) {
        try {
            $url = $endpoint . urlencode($payload);
            $response = $client->request('GET', $url);
            
            if (checkForXss($response, $payload)) {
                $vulnerabilities[] = [
                    'endpoint' => $endpoint,
                    'payload' => $payload,
                    'status' => $response->getStatusCode()
                ];
            }
        } catch (\Exception $e) {
            echo "Erro ao testar {$endpoint} com payload {$payload}: " . $e->getMessage() . PHP_EOL;
        }
    }
}

// Exibir resultados
if (empty($vulnerabilities)) {
    echo "Nenhuma vulnerabilidade XSS encontrada!" . PHP_EOL;
} else {
    echo "Encontradas " . count($vulnerabilities) . " possíveis vulnerabilidades XSS:" . PHP_EOL;
    foreach ($vulnerabilities as $vuln) {
        echo "- Endpoint: {$vuln['endpoint']}" . PHP_EOL;
        echo "  Payload: {$vuln['payload']}" . PHP_EOL;
        echo "  Status: {$vuln['status']}" . PHP_EOL;
    }
}
```

### Script de Verificação de CSRF

```php
<?php
// scripts/security-test/csrf_scanner.php

require_once __DIR__ . '/../../vendor/autoload.php';

// Configurações
$baseUrl = 'https://darkblue-cattle-647559.hostingersite.com';
$formEndpoints = [
    '/product/add',
    '/model/upload',
    '/user/profile/edit',
    '/order/create'
];

// Iniciar cliente HTTP
$client = new GuzzleHttp\Client([
    'base_uri' => $baseUrl,
    'cookies' => true,
    'allow_redirects' => true
]);

// Executar login para obter sessão
try {
    $response = $client->request('POST', '/login', [
        'form_params' => [
            'email' => 'test@example.com',
            'password' => 'Test123!'
        ]
    ]);
} catch (\Exception $e) {
    echo "Erro ao fazer login: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Testar formulários para proteção CSRF
$vulnerabilities = [];

foreach ($formEndpoints as $endpoint) {
    try {
        // Primeiro, verificar se o formulário contém token CSRF
        $response = $client->request('GET', $endpoint);
        $body = (string) $response->getBody();
        
        $hasToken = preg_match('/<input[^>]*name=["\']csrf_token["\'][^>]*>/', $body);
        
        if (!$hasToken) {
            $vulnerabilities[] = [
                'endpoint' => $endpoint,
                'issue' => 'Formulário não contém token CSRF'
            ];
            continue;
        }
        
        // Depois, tentar submeter sem token CSRF
        try {
            $response = $client->request('POST', $endpoint, [
                'form_params' => [
                    'dummy_field' => 'test'
                ]
            ]);
            
            // Se aceitar requisição sem token, é vulnerável
            $vulnerabilities[] = [
                'endpoint' => $endpoint,
                'issue' => 'Aceita requisição sem token CSRF',
                'status' => $response->getStatusCode()
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Comportamento esperado: rejeitar requisição
            if ($e->getResponse()->getStatusCode() === 403) {
                echo "Endpoint {$endpoint} está protegido contra CSRF." . PHP_EOL;
            } else {
                echo "Endpoint {$endpoint} gerou erro não relacionado a CSRF: " . $e->getMessage() . PHP_EOL;
            }
        }
    } catch (\Exception $e) {
        echo "Erro ao testar CSRF em {$endpoint}: " . $e->getMessage() . PHP_EOL;
    }
}

// Exibir resultados
if (empty($vulnerabilities)) {
    echo "Nenhuma vulnerabilidade CSRF encontrada!" . PHP_EOL;
} else {
    echo "Encontradas " . count($vulnerabilities) . " possíveis vulnerabilidades CSRF:" . PHP_EOL;
    foreach ($vulnerabilities as $vuln) {
        echo "- Endpoint: {$vuln['endpoint']}" . PHP_EOL;
        echo "  Problema: {$vuln['issue']}" . PHP_EOL;
        if (isset($vuln['status'])) {
            echo "  Status: {$vuln['status']}" . PHP_EOL;
        }
    }
}
```

## Ambiente de Teste Seguro

Para executar testes de segurança de forma segura, sem afetar ambientes de produção:

```bash
#!/bin/bash
# setup-security-test-env.sh

# Criar diretório para ambiente de teste
mkdir -p security-test-env
cd security-test-env

# Clonar o repositório
git clone https://github.com/Lucasdoreac/taverna-impressao-ecommerce.git
cd taverna-impressao-ecommerce

# Criar banco de dados de teste
mysql -u root -p < database/schema.sql
mysql -u root -p -e "CREATE DATABASE taverna_test; GRANT ALL PRIVILEGES ON taverna_test.* TO 'taverna_user'@'localhost';"

# Configurar ambiente de teste
cp app/config/config.example.php app/config/config.php
sed -i 's/DB_NAME=.*/DB_NAME=taverna_test/' app/config/config.php

# Instalar dependências
composer install

# Configurar ambiente para testes de segurança
cp app/config/security.example.php app/config/security.php
sed -i 's/SECURITY_MODE=.*/SECURITY_MODE=testing/' app/config/security.php

# Executar testes de segurança
php vendor/bin/phpunit tests/security
```

## Monitoramento de Segurança Contínuo

```php
<?php
// scripts/security-monitor.php

/**
 * Script para monitoramento contínuo de segurança
 * Executar via cron diariamente
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Iniciar logger
$logger = new Logger('security-monitor');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/security-monitor.log', Logger::INFO));
$logger->pushHandler(new MailHandler('security-alerts@example.com', 'Alerta de Segurança', Logger::CRITICAL));

// Monitorar tentativas de login falhas
$db = Database::getInstance();
$sql = "SELECT ip_address, COUNT(*) as count 
        FROM login_attempts 
        WHERE success = 0 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) 
        GROUP BY ip_address 
        HAVING count > 10";
        
$result = $db->select($sql);

foreach ($result as $row) {
    $logger->warning("Possível ataque de força bruta: IP {$row['ip_address']} com {$row['count']} tentativas falhas");
    
    // Se for muito suspeito, adicionar à lista de bloqueio
    if ($row['count'] > 50) {
        $db->insert("INSERT INTO ip_blacklist (ip_address, reason, created_at) VALUES (?, ?, NOW())", 
            [$row['ip_address'], 'Ataque de força bruta']);
        $logger->alert("IP {$row['ip_address']} bloqueado por ataque de força bruta");
    }
}

// Verificar uploads suspeitos
$sql = "SELECT psf.id, psf.original_filename, psf.filetype, psf.filepath, u.email
        FROM print_status_files psf
        JOIN users u ON psf.user_id = u.id
        WHERE psf.security_scan_status = 'suspicious'
        AND psf.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
        
$result = $db->select($sql);

foreach ($result as $row) {
    $logger->warning("Upload suspeito: Arquivo #{$row['id']} ({$row['original_filename']}) por {$row['email']}");
}

// Verificar padrões suspeitos de acesso
$sql = "SELECT r.user_id, r.resource_type, r.resource_id, r.access_type, r.success, u.email, COUNT(*) as count
        FROM resource_access_log r
        JOIN users u ON r.user_id = u.id
        WHERE r.success = 0
        AND r.created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        GROUP BY r.user_id, r.resource_type, r.access_type
        HAVING count > 5";
        
$result = $db->select($sql);

foreach ($result as $row) {
    $logger->warning("Padrão suspeito de tentativas de acesso: Usuário {$row['email']} tentou acessar {$row['resource_type']} com tipo de acesso {$row['access_type']} {$row['count']} vezes sem sucesso");
}

// Executar verificações de cabeçalhos de segurança
$client = new GuzzleHttp\Client();
$response = $client->request('GET', 'https://darkblue-cattle-647559.hostingersite.com');
$headers = $response->getHeaders();

$requiredHeaders = [
    'X-Frame-Options',
    'X-Content-Type-Options',
    'X-XSS-Protection',
    'Content-Security-Policy',
    'Referrer-Policy'
];

foreach ($requiredHeaders as $header) {
    if (!isset($headers[$header])) {
        $logger->critical("Cabeçalho de segurança ausente: {$header}");
    }
}

$logger->info("Monitoramento de segurança concluído");
```

## Melhores Práticas para Testes de Segurança

1. **Testes Regulares**: Executar testes de segurança regularmente, não apenas após mudanças
2. **Ambiente Isolado**: Usar ambiente de teste separado para evitar impacto na produção
3. **Monitoramento Contínuo**: Implementar monitoramento para detectar problemas de segurança
4. **Atualização de Testes**: Manter os testes atualizados com novas técnicas de ataque
5. **Teste de Regressão**: Verificar se correções não introduzem novas vulnerabilidades
6. **Documentação de Resultados**: Documentar todos os resultados de testes e correções aplicadas
7. **Educação**: Compartilhar resultados de testes com a equipe para educação contínua

## Referências

- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [OWASP ZAP](https://www.zaproxy.org/)
- [Burp Suite](https://portswigger.net/burp)
- [PHP Security Testing Checklist](https://www.php.net/manual/en/security.php)