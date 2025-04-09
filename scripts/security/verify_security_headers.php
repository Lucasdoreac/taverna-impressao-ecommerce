<?php
/**
 * Script para verificação de headers de segurança HTTP
 * 
 * Este script verifica a conformidade dos headers de segurança em
 * endpoints críticos da aplicação, garantindo a implementação correta
 * dos mecanismos de segurança.
 * 
 * @package App\Scripts\Security
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Classe para verificação de headers de segurança
 */
class SecurityHeadersVerifier {
    /** @var string URL base para testes */
    private $baseUrl;
    
    /** @var array Endpoints a verificar */
    private $endpoints = [
        '/',                    // Página inicial
        '/login',               // Página de login
        '/account/profile',     // Perfil do usuário
        '/admin/dashboard',     // Painel do administrador
        '/api/status-check',    // API para verificação de status
        '/api/products'         // API de produtos
    ];
    
    /** @var array Mapeamento de URLs personalizadas */
    private $customEndpoints = [];
    
    /** @var array Headers de segurança obrigatórios */
    private $requiredHeaders = [
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-XSS-Protection',
        'Content-Security-Policy',
        'Strict-Transport-Security',
        'Referrer-Policy'
    ];
    
    /** @var array Valores esperados/recomendados para headers */
    private $expectedValues = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => ['DENY', 'SAMEORIGIN'],
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => ['no-referrer', 'strict-origin-when-cross-origin', 'same-origin'],
        'Strict-Transport-Security' => function($value) {
            return strpos($value, 'max-age=') !== false && 
                   preg_match('/max-age=(\d+)/', $value, $matches) && 
                   $matches[1] >= 31536000; // Pelo menos 1 ano
        }
    ];
    
    /** @var array Resultados das verificações */
    private $results = [];
    
    /** @var resource Contexto cURL */
    private $ch;
    
    /**
     * Construtor
     * 
     * @param string $baseUrl URL base para testes
     * @param array $customEndpoints Endpoints personalizados (opcional)
     */
    public function __construct($baseUrl, array $customEndpoints = []) {
        $this->baseUrl = $baseUrl;
        
        // Adicionar endpoints personalizados
        if (!empty($customEndpoints)) {
            $this->customEndpoints = $customEndpoints;
        }
        
        // Inicializar cURL
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, true);
        curl_setopt($this->ch, CURLOPT_NOBODY, true); // Apenas cabeçalhos
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
    }
    
    /**
     * Destrutor - Libera recursos
     */
    public function __destruct() {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }
    
    /**
     * Executa verificação de headers em todos os endpoints
     * 
     * @return array Resultados da verificação
     */
    public function verifyHeaders() {
        echo "Iniciando verificação de headers de segurança...\n\n";
        
        // Mesclar endpoints padrão e personalizados
        $allEndpoints = array_merge($this->endpoints, $this->customEndpoints);
        
        foreach ($allEndpoints as $endpoint) {
            echo "Verificando endpoint: $endpoint\n";
            
            $headers = $this->fetchHeaders($endpoint);
            
            if ($headers === false) {
                echo "  ERRO: Não foi possível obter headers para $endpoint\n";
                $this->results[$endpoint] = [
                    'status' => 'error',
                    'message' => 'Não foi possível obter headers',
                    'headers' => []
                ];
                continue;
            }
            
            // Verificar headers obrigatórios
            $missing = [];
            $incorrect = [];
            $securityScore = 0;
            $maxScore = count($this->requiredHeaders);
            
            foreach ($this->requiredHeaders as $header) {
                $headerNormalized = strtolower($header);
                
                if (!isset($headers[$headerNormalized])) {
                    $missing[] = $header;
                    continue;
                }
                
                // Verificar valor do header
                $value = $headers[$headerNormalized];
                
                if (isset($this->expectedValues[$header])) {
                    $expected = $this->expectedValues[$header];
                    $isValid = false;
                    
                    if (is_callable($expected)) {
                        $isValid = $expected($value);
                    } elseif (is_array($expected)) {
                        foreach ($expected as $validValue) {
                            if (strpos($value, $validValue) !== false) {
                                $isValid = true;
                                break;
                            }
                        }
                    } else {
                        $isValid = strpos($value, $expected) !== false;
                    }
                    
                    if (!$isValid) {
                        $incorrect[] = "$header: $value";
                    } else {
                        $securityScore++;
                    }
                } else {
                    // Se não temos valor esperado específico, considerar válido se presente
                    $securityScore++;
                }
            }
            
            // Calcular pontuação final
            $scorePercentage = ($maxScore > 0) ? (($securityScore / $maxScore) * 100) : 0;
            
            // Determinar status
            $status = 'passed';
            if (!empty($missing) || !empty($incorrect)) {
                $status = 'warning';
                
                if ($scorePercentage < 70) {
                    $status = 'failed';
                }
            }
            
            // Registrar resultados
            $this->results[$endpoint] = [
                'status' => $status,
                'score' => $scorePercentage,
                'missing' => $missing,
                'incorrect' => $incorrect,
                'headers' => $headers
            ];
            
            // Exibir resultados parciais
            $this->printEndpointResult($endpoint, $this->results[$endpoint]);
        }
        
        // Exibir resumo
        $this->printSummary();
        
        return $this->results;
    }
    
    /**
     * Busca headers para um endpoint específico
     * 
     * @param string $endpoint URL do endpoint
     * @return array|false Headers obtidos ou false em caso de erro
     */
    private function fetchHeaders($endpoint) {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        // Configurar headers de usuário
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/html,application/json,application/xhtml+xml'
        ];
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        
        // Executar requisição
        $response = curl_exec($this->ch);
        
        // Verificar erros
        if (curl_errno($this->ch)) {
            return false;
        }
        
        // Processar headers da resposta
        $headerSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $headerText = substr($response, 0, $headerSize);
        
        // Converter texto de headers para array
        $headerLines = explode("\r\n", $headerText);
        $headers = [];
        
        foreach ($headerLines as $line) {
            $parts = explode(':', $line, 2);
            
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        
        return $headers;
    }
    
    /**
     * Imprime resultado de um endpoint
     * 
     * @param string $endpoint URL do endpoint
     * @param array $result Resultado da verificação
     */
    private function printEndpointResult($endpoint, $result) {
        $status = $result['status'];
        $statusText = '';
        
        switch ($status) {
            case 'passed':
                $statusText = "\033[32mPASSED\033[0m"; // Verde
                break;
            case 'warning':
                $statusText = "\033[33mWARNING\033[0m"; // Amarelo
                break;
            case 'failed':
                $statusText = "\033[31mFAILED\033[0m"; // Vermelho
                break;
            case 'error':
                $statusText = "\033[31mERROR\033[0m"; // Vermelho
                break;
        }
        
        echo "  Status: $statusText";
        
        if ($status !== 'error') {
            echo " (Score: " . number_format($result['score'], 1) . "%)";
        }
        
        echo "\n";
        
        // Listar headers ausentes
        if (!empty($result['missing'])) {
            echo "  Headers ausentes:\n";
            foreach ($result['missing'] as $header) {
                echo "    - $header\n";
            }
        }
        
        // Listar headers com valores incorretos
        if (!empty($result['incorrect'])) {
            echo "  Headers com valores inadequados:\n";
            foreach ($result['incorrect'] as $header) {
                echo "    - $header\n";
            }
        }
        
        // Se passar, exibir headers presentes
        if ($status === 'passed') {
            echo "  Todos os headers de segurança estão presentes e válidos.\n";
        }
        
        echo "\n";
    }
    
    /**
     * Imprime resumo final da verificação
     */
    private function printSummary() {
        $totalEndpoints = count($this->results);
        $passed = 0;
        $warnings = 0;
        $failed = 0;
        $errors = 0;
        
        foreach ($this->results as $result) {
            switch ($result['status']) {
                case 'passed':
                    $passed++;
                    break;
                case 'warning':
                    $warnings++;
                    break;
                case 'failed':
                    $failed++;
                    break;
                case 'error':
                    $errors++;
                    break;
            }
        }
        
        echo "=================================\n";
        echo "RESUMO DA VERIFICAÇÃO DE HEADERS\n";
        echo "=================================\n\n";
        
        echo "Total de endpoints verificados: $totalEndpoints\n";
        echo "Endpoints com todos os headers corretos: $passed\n";
        echo "Endpoints com avisos (alguns headers inadequados): $warnings\n";
        echo "Endpoints reprovados (headers críticos ausentes): $failed\n";
        echo "Endpoints com erros de acesso: $errors\n\n";
        
        // Calcular nota geral
        $score = 0;
        $maxScore = $totalEndpoints * 100;
        
        foreach ($this->results as $result) {
            if ($result['status'] !== 'error') {
                $score += $result['score'];
            }
        }
        
        $finalScore = ($maxScore > 0) ? (($score / $maxScore) * 100) : 0;
        
        echo "Nota de segurança HTTP geral: " . number_format($finalScore, 1) . "%\n\n";
        
        // Recomendações
        echo "Recomendações:\n";
        
        if ($passed === $totalEndpoints) {
            echo "✓ Todos os endpoints estão configurados corretamente. Continue mantendo.\n";
        } else {
            // Identificar headers mais comumente ausentes
            $allMissing = [];
            $allIncorrect = [];
            
            foreach ($this->results as $endpoint => $result) {
                foreach ($result['missing'] ?? [] as $header) {
                    if (!isset($allMissing[$header])) {
                        $allMissing[$header] = [];
                    }
                    $allMissing[$header][] = $endpoint;
                }
                
                foreach ($result['incorrect'] ?? [] as $headerInfo) {
                    $parts = explode(':', $headerInfo, 2);
                    $header = trim($parts[0]);
                    
                    if (!isset($allIncorrect[$header])) {
                        $allIncorrect[$header] = [];
                    }
                    $allIncorrect[$header][] = $endpoint;
                }
            }
            
            // Exibir recomendações por header
            foreach ($allMissing as $header => $endpoints) {
                $endpointList = implode(', ', $endpoints);
                echo "! Adicionar o header '$header' nos endpoints: $endpointList\n";
            }
            
            foreach ($allIncorrect as $header => $endpoints) {
                $endpointList = implode(', ', $endpoints);
                $expectedValue = is_array($this->expectedValues[$header]) ? 
                                 implode(' ou ', $this->expectedValues[$header]) : 
                                 $this->expectedValues[$header];
                
                if (is_callable($this->expectedValues[$header])) {
                    echo "! Corrigir o valor do header '$header' nos endpoints: $endpointList\n";
                } else {
                    echo "! Corrigir o valor do header '$header' para '$expectedValue' nos endpoints: $endpointList\n";
                }
            }
        }
        
        // Referência para consulta
        echo "\nInformações adicionais:\n";
        echo "- Para detalhes sobre configuração de headers de segurança, consulte a documentação em:\n";
        echo "  docs/security/SecurityHeaders.md\n";
        echo "- Para implementar automaticamente os headers, utilize a classe SecurityHeaders\n";
        echo "  no namespace App\\Lib\\Security\n";
    }
}

// Ponto de entrada do script
try {
    echo "=================================\n";
    echo "Verificação de Headers de Segurança\n";
    echo "=================================\n\n";
    
    // Ler parâmetros da linha de comando
    $options = getopt('', ['url:', 'endpoints:']);
    
    // Configuração padrão
    $baseUrl = 'http://localhost:8000';
    $customEndpoints = [];
    
    // Processar argumentos
    if (isset($options['url'])) {
        $baseUrl = $options['url'];
    }
    
    if (isset($options['endpoints'])) {
        $endpointsList = $options['endpoints'];
        $customEndpoints = explode(',', $endpointsList);
    }
    
    echo "URL base: $baseUrl\n";
    if (!empty($customEndpoints)) {
        echo "Endpoints personalizados: " . implode(', ', $customEndpoints) . "\n";
    }
    echo "\n";
    
    // Executar verificação
    $verifier = new SecurityHeadersVerifier($baseUrl, $customEndpoints);
    $results = $verifier->verifyHeaders();
    
    // Determinar código de saída
    $exitCode = 0;
    foreach ($results as $result) {
        if ($result['status'] === 'failed' || $result['status'] === 'error') {
            $exitCode = 1;
            break;
        }
    }
    
    exit($exitCode);
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
