<?php
/**
 * Testes para o componente QuotationNotifier
 * 
 * Execute com: php tests/quotation/QuotationNotifierTest.php
 */

// Caminhos de inclusão
require_once __DIR__ . '/../../app/lib/Analysis/Queue/QuotationNotifier.php';
require_once __DIR__ . '/../../app/lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../../app/lib/Database.php';

// Simular dependências se não existirem (para testes)
if (!class_exists('NotificationManager')) {
    class NotificationManager {
        public function sendEmail($to, $subject, $template, $data) {
            echo "TESTE: E-mail enviado para {$to}, assunto: {$subject}, template: {$template}\n";
            return true;
        }
        
        public function sendUserNotification($userId, $title, $message, $url, $linkText, $type) {
            echo "TESTE: Notificação enviada para usuário {$userId}, título: {$title}, tipo: {$type}\n";
            return true;
        }
    }
}

if (!class_exists('Database')) {
    class Database {
        private static $instance;
        
        public static function getInstance() {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function fetchSingle($sql, $params = []) {
            // Simular usuários para teste
            if (strpos($sql, 'users') !== false && isset($params[':id'])) {
                if ($params[':id'] == 1) {
                    return [
                        'id' => 1,
                        'name' => 'Usuário Teste',
                        'email' => 'teste@example.com',
                        'role' => 'customer'
                    ];
                }
            }
            
            // Simular modelos para teste
            if (strpos($sql, 'customer_models') !== false && isset($params[':id'])) {
                if ($params[':id'] == 100) {
                    return [
                        'id' => 100,
                        'user_id' => 1,
                        'original_name' => 'Modelo Teste.stl',
                        'file_name' => 'secure_filename_123.stl',
                        'status' => 'approved',
                        'created_at' => '2025-04-01 10:00:00'
                    ];
                }
            }
            
            return null;
        }
        
        public function fetchAll($sql, $params = []) {
            return [];
        }
        
        public function execute($sql, $params = []) {
            echo "TESTE: Executando SQL: " . substr($sql, 0, 50) . "...\n";
            return true;
        }
        
        public function beginTransaction() {
            return true;
        }
        
        public function commit() {
            return true;
        }
        
        public function rollBack() {
            return true;
        }
    }
}

/**
 * Classe de teste para QuotationNotifier
 */
class QuotationNotifierTest {
    /**
     * Executa todos os testes
     */
    public function runTests() {
        echo "Iniciando testes do QuotationNotifier...\n";
        echo "----------------------------------------\n";
        
        $this->testCompletionNotification();
        $this->testErrorNotification();
        $this->testSanitization();
        $this->testTypeValidation();
        
        echo "----------------------------------------\n";
        echo "Testes concluídos!\n";
    }
    
    /**
     * Testa o envio de notificação de conclusão
     */
    private function testCompletionNotification() {
        echo "\n[TESTE] Notificação de conclusão\n";
        
        $notifier = new QuotationNotifier(['debug_mode' => true]);
        
        // Dados de teste
        $task = [
            'task_id' => 'q-1234-abcdef',
            'user_id' => 1,
            'model_id' => 100,
            'notification_type' => 'system'
        ];
        
        $result = [
            'total_cost' => 45.50,
            'material_cost' => 15.75,
            'printing_cost' => 29.75,
            'estimated_print_time_minutes' => 180,
            'complexity_score' => 65,
            'material' => 'pla'
        ];
        
        $success = $notifier->sendCompletionNotification($task, $result);
        
        echo $success ? "✅ Notificação de conclusão enviada com sucesso\n" : "❌ Falha ao enviar notificação de conclusão\n";
    }
    
    /**
     * Testa o envio de notificação de erro
     */
    private function testErrorNotification() {
        echo "\n[TESTE] Notificação de erro\n";
        
        $notifier = new QuotationNotifier(['debug_mode' => true]);
        
        // Dados de teste
        $task = [
            'task_id' => 'q-1234-abcdef',
            'user_id' => 1,
            'model_id' => 100,
            'notification_type' => 'system'
        ];
        
        $errorMessage = "Erro ao processar modelo: arquivo corrompido em /var/www/html/uploads/models/file.stl na linha 502. Stack trace: #0 /var/www/func.php(10): processFile() #1 /var/www/index.php(5): execute()";
        
        $success = $notifier->sendErrorNotification($task, $errorMessage);
        
        echo $success ? "✅ Notificação de erro enviada com sucesso\n" : "❌ Falha ao enviar notificação de erro\n";
    }
    
    /**
     * Testa a sanitização de mensagens de erro
     */
    private function testSanitization() {
        echo "\n[TESTE] Sanitização de mensagens de erro\n";
        
        $notifier = new QuotationNotifier(['debug_mode' => true]);
        
        // Acessar método privado para teste
        $reflectionClass = new ReflectionClass(QuotationNotifier::class);
        $method = $reflectionClass->getMethod('sanitizeErrorMessage');
        $method->setAccessible(true);
        
        $errorMessages = [
            "Erro no arquivo /var/www/html/app/lib/Analysis/ModelComplexityAnalyzer.php na linha 123" => "Erro no arquivo",
            "Exception: Falha ao processar arquivo. Stack trace: #0 /var/www/func.php" => "Falha ao processar arquivo.",
            "PHP Warning: file_get_contents(): Failed to open stream" => "Failed to open stream",
            "<script>alert('XSS')</script>" => "alert('XSS')",
            "" => "Ocorreu um problema durante o processamento do seu modelo. Nossa equipe técnica foi notificada."
        ];
        
        $allPassed = true;
        
        foreach ($errorMessages as $original => $expected) {
            $sanitized = $method->invoke($notifier, $original);
            
            $result = (strpos($sanitized, $expected) !== false && 
                      strpos($sanitized, "/var/www") === false && 
                      strpos($sanitized, "Stack trace") === false);
            
            echo ($result ? "✅" : "❌") . " Original: \"{$original}\"\n";
            echo "   Sanitizado: \"{$sanitized}\"\n";
            
            if (!$result) {
                $allPassed = false;
            }
        }
        
        echo $allPassed ? "✅ Todos os testes de sanitização passaram\n" : "❌ Alguns testes de sanitização falharam\n";
    }
    
    /**
     * Testa a validação de tipos de notificação
     */
    private function testTypeValidation() {
        echo "\n[TESTE] Validação de tipos de notificação\n";
        
        $notifier = new QuotationNotifier(['debug_mode' => true]);
        
        // Acessar método privado para teste
        $reflectionClass = new ReflectionClass(QuotationNotifier::class);
        $method = $reflectionClass->getMethod('validateNotificationType');
        $method->setAccessible(true);
        
        $testCases = [
            'email' => 'email',
            'system' => 'system',
            'none' => 'none',
            'invalid' => 'system',
            'EMAIL' => 'system',
            '<script>alert(1)</script>' => 'system',
            '' => 'system'
        ];
        
        $allPassed = true;
        
        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($notifier, $input);
            $passed = ($result === $expected);
            
            echo ($passed ? "✅" : "❌") . " Tipo: \"{$input}\" => \"{$result}\" (esperado: \"{$expected}\")\n";
            
            if (!$passed) {
                $allPassed = false;
            }
        }
        
        echo $allPassed ? "✅ Todos os testes de validação de tipo passaram\n" : "❌ Alguns testes de validação de tipo falharam\n";
    }
}

// Executar testes
$tester = new QuotationNotifierTest();
$tester->runTests();
