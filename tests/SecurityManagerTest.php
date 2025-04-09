<?php
/**
 * Testes para a classe SecurityManager
 * 
 * Este arquivo contém os testes unitários para verificar o funcionamento
 * correto das funcionalidades de segurança implementadas na classe SecurityManager.
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../app/lib/Security/SecurityManager.php';

/**
 * Classe de teste para SecurityManager
 */
class SecurityManagerTest {
    /**
     * Testa a geração de token CSRF
     * 
     * @return bool Verdadeiro se o teste passar, falso caso contrário
     */
    public function testCsrfTokenGeneration() {
        // Iniciar sessão simulada para teste
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpar qualquer token existente
        unset($_SESSION['csrf_token']);
        
        // Gerar token
        $token = SecurityManager::generateCsrfToken();
        
        // Verificar se o token foi gerado
        $result = !empty($token) && strlen($token) == 64; // 32 bytes = 64 caracteres hexadecimais
        
        // Verificar se o token foi armazenado na sessão
        $sessionResult = isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
        
        // Limpar sessão
        session_destroy();
        
        echo "Teste de geração de token CSRF: " . ($result && $sessionResult ? "PASSOU" : "FALHOU") . "\n";
        return $result && $sessionResult;
    }
    
    /**
     * Testa a validação de token CSRF
     * 
     * @return bool Verdadeiro se o teste passar, falso caso contrário
     */
    public function testCsrfTokenValidation() {
        // Iniciar sessão simulada para teste
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpar qualquer token existente
        unset($_SESSION['csrf_token']);
        
        // Gerar token
        $token = SecurityManager::generateCsrfToken();
        
        // Caso de sucesso: token correto
        $validResult = SecurityManager::validateCsrfToken($token);
        
        // Caso de falha: token incorreto
        $invalidResult = !SecurityManager::validateCsrfToken("token_invalido");
        
        // Caso de borda: token vazio
        $emptyResult = !SecurityManager::validateCsrfToken("");
        
        // Limpar sessão
        session_destroy();
        
        $passed = $validResult && $invalidResult && $emptyResult;
        
        echo "Teste de validação de token CSRF: " . ($passed ? "PASSOU" : "FALHOU") . "\n";
        return $passed;
    }
    
    /**
     * Testa a função de sanitização
     * 
     * @return bool Verdadeiro se o teste passar, falso caso contrário
     */
    public function testSanitize() {
        // Caso normal: texto com caracteres especiais
        $input = '<script>alert("XSS")</script>';
        $expected = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
        $sanitized = SecurityManager::sanitize($input);
        $normalCase = ($sanitized === $expected);
        
        // Caso de borda: texto sem caracteres especiais
        $input = 'Texto normal';
        $sanitized = SecurityManager::sanitize($input);
        $borderCase = ($sanitized === $input);
        
        // Caso de borda: entrada vazia
        $input = '';
        $sanitized = SecurityManager::sanitize($input);
        $emptyCase = ($sanitized === '');
        
        $passed = $normalCase && $borderCase && $emptyCase;
        
        echo "Teste de sanitização: " . ($passed ? "PASSOU" : "FALHOU") . "\n";
        return $passed;
    }
    
    /**
     * Testa a verificação de autenticação
     * 
     * @return bool Verdadeiro se o teste passar, falso caso contrário
     */
    public function testCheckAuthentication() {
        // Iniciar sessão simulada para teste
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Caso não autenticado: sem user_id
        unset($_SESSION['user_id']);
        $notAuthResult = !SecurityManager::checkAuthentication();
        
        // Caso autenticado: com user_id
        $_SESSION['user_id'] = 123;
        $_SESSION['last_activity'] = time();
        $authResult = SecurityManager::checkAuthentication();
        
        // Caso de sessão expirada
        $_SESSION['user_id'] = 123;
        $_SESSION['last_activity'] = time() - 3601; // 1 hora e 1 segundo atrás
        $expiredResult = !SecurityManager::checkAuthentication();
        
        // Limpar sessão
        session_destroy();
        
        $passed = $notAuthResult && $authResult && $expiredResult;
        
        echo "Teste de verificação de autenticação: " . ($passed ? "PASSOU" : "FALHOU") . "\n";
        return $passed;
    }
    
    /**
     * Executa todos os testes
     * 
     * @return bool Verdadeiro se todos os testes passarem, falso caso contrário
     */
    public function runAllTests() {
        echo "Iniciando testes para SecurityManager...\n";
        
        $testResults = [];
        $testResults[] = $this->testCsrfTokenGeneration();
        $testResults[] = $this->testCsrfTokenValidation();
        $testResults[] = $this->testSanitize();
        $testResults[] = $this->testCheckAuthentication();
        
        $allPassed = !in_array(false, $testResults);
        
        echo "\nResultado final: " . ($allPassed ? "TODOS OS TESTES PASSARAM" : "ALGUNS TESTES FALHARAM") . "\n";
        
        return $allPassed;
    }
}

// Executar os testes se este arquivo for executado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $tester = new SecurityManagerTest();
    $tester->runAllTests();
}