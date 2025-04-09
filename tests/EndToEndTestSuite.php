<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/config.php';

class EndToEndTestSuite {
    private $results = [];
    private $failedTests = [];
    private $passedTests = 0;
    private $totalTests = 0;
    private $baseUrl;

    public function __construct() {
        // Set up test environment
        define('TESTING', true);
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Configure base URL for testing
        $this->baseUrl = 'http://localhost:8080'; // Adjust as needed
    }

    public function runAllTests() {
        $this->runUserFlowTests();
        $this->runProductFlowTests();
        $this->runOrderFlowTests();
        $this->displayResults();
    }

    private function runUserFlowTests() {
        $this->startTestGroup('User Flow Tests');
        
        // Test user registration
        $this->runTest('testUserRegistration', function() {
            $data = [
                'name' => 'Test User',
                'email' => 'test' . time() . '@example.com',
                'password' => 'testpassword123'
            ];
            
            $response = $this->makeRequest('/register', 'POST', $data);
            return $response && isset($response['success']) && $response['success'];
        });

        // Test user login
        $this->runTest('testUserLogin', function() {
            $data = [
                'email' => 'test@example.com',
                'password' => 'testpassword123'
            ];
            
            $response = $this->makeRequest('/login', 'POST', $data);
            return $response && isset($response['success']) && $response['success'];
        });
    }

    private function runProductFlowTests() {
        $this->startTestGroup('Product Flow Tests');
        
        // Test product browsing
        $this->runTest('testProductBrowsing', function() {
            $response = $this->makeRequest('/products', 'GET');
            return $response && isset($response['products']) && is_array($response['products']);
        });

        // Test product search
        $this->runTest('testProductSearch', function() {
            $response = $this->makeRequest('/products/search?q=test', 'GET');
            return $response && isset($response['products']);
        });

        // Test product details
        $this->runTest('testProductDetails', function() {
            $response = $this->makeRequest('/products/1', 'GET');
            return $response && isset($response['product']) && isset($response['product']['id']);
        });
    }

    private function runOrderFlowTests() {
        $this->startTestGroup('Order Flow Tests');
        
        // Test add to cart
        $this->runTest('testAddToCart', function() {
            $data = [
                'product_id' => 1,
                'quantity' => 1
            ];
            
            $response = $this->makeRequest('/cart/add', 'POST', $data);
            return $response && isset($response['success']) && $response['success'];
        });

        // Test checkout process
        $this->runTest('testCheckoutProcess', function() {
            // Add item to cart first
            $this->makeRequest('/cart/add', 'POST', ['product_id' => 1, 'quantity' => 1]);
            
            // Proceed with checkout
            $checkoutData = [
                'shipping_address' => '123 Test St',
                'billing_address' => '123 Test St',
                'payment_method' => 'credit_card',
                'card_number' => '4242424242424242',
                'card_expiry' => '12/25',
                'card_cvv' => '123'
            ];
            
            $response = $this->makeRequest('/checkout', 'POST', $checkoutData);
            return $response && isset($response['order_id']);
        });

        // Test order tracking
        $this->runTest('testOrderTracking', function() {
            // Create test order first
            $orderId = $this->createTestOrder();
            
            $response = $this->makeRequest("/orders/$orderId", 'GET');
            return $response && isset($response['order']) && $response['order']['id'] == $orderId;
        });
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $ch = curl_init($this->baseUrl . $endpoint);
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = http_build_query($data);
            }
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }

    private function createTestOrder() {
        $orderData = [
            'user_id' => 1,
            'total' => 99.99,
            'status' => 'pending'
        ];
        
        $orderModel = new OrderModel();
        return $orderModel->create($orderData);
    }

    private function startTestGroup($name) {
        $this->results[$name] = [
            'passed' => 0,
            'failed' => 0,
            'tests' => []
        ];
    }

    private function runTest($name, $callback) {
        $this->totalTests++;
        try {
            $result = $callback();
            if ($result) {
                $this->passedTests++;
                $this->results[array_key_last($this->results)]['passed']++;
                $this->results[array_key_last($this->results)]['tests'][$name] = true;
            } else {
                $this->failedTests[] = $name;
                $this->results[array_key_last($this->results)]['failed']++;
                $this->results[array_key_last($this->results)]['tests'][$name] = false;
            }
        } catch (Exception $e) {
            $this->failedTests[] = $name . ': ' . $e->getMessage();
            $this->results[array_key_last($this->results)]['failed']++;
            $this->results[array_key_last($this->results)]['tests'][$name] = false;
        }
    }

    private function displayResults() {
        echo "\nEnd-to-End Test Results:\n";
        echo "=====================\n\n";
        
        foreach ($this->results as $group => $results) {
            echo "$group:\n";
            echo str_repeat('-', strlen($group) + 1) . "\n";
            echo "Passed: {$results['passed']}\n";
            echo "Failed: {$results['failed']}\n\n";
            
            foreach ($results['tests'] as $test => $passed) {
                echo ($passed ? '✓' : '✗') . " $test\n";
            }
            echo "\n";
        }
        
        echo "Summary:\n";
        echo "========\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . count($this->failedTests) . "\n\n";
        
        if (!empty($this->failedTests)) {
            echo "Failed Tests:\n";
            foreach ($this->failedTests as $test) {
                echo "- $test\n";
            }
        }
    }
}

// Run the tests if this file is being executed directly
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)) {
    $suite = new EndToEndTestSuite();
    $suite->runAllTests();
}