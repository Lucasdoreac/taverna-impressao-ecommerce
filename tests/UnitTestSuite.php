<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/config.php';

class UnitTestSuite {
    private $results = [];
    private $failedTests = [];
    private $passedTests = 0;
    private $totalTests = 0;

    public function __construct() {
        // Set up test environment
        define('TESTING', true);
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function runAllTests() {
        $this->runCategoryTests();
        $this->runProductTests();
        $this->runOrderTests();
        $this->runCartTests();
        $this->displayResults();
    }

    private function runCategoryTests() {
        $this->startTestGroup('Category Tests');
        
        // Test category creation
        $this->runTest('testCategoryCreation', function() {
            $categoryModel = new CategoryModel();
            $data = [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'description' => 'Test Description'
            ];
            $result = $categoryModel->create($data);
            return $result && $result > 0;
        });

        // Test category retrieval
        $this->runTest('testCategoryRetrieval', function() {
            $categoryModel = new CategoryModel();
            $categories = $categoryModel->all();
            return is_array($categories);
        });

        // Test category hierarchy
        $this->runTest('testCategoryHierarchy', function() {
            $categoryModel = new CategoryModel();
            $parentData = [
                'name' => 'Parent Category',
                'slug' => 'parent-category'
            ];
            $parentId = $categoryModel->create($parentData);
            
            $childData = [
                'name' => 'Child Category',
                'slug' => 'child-category',
                'parent_id' => $parentId
            ];
            $childId = $categoryModel->create($childData);
            
            $child = $categoryModel->find($childId);
            return $child && $child['parent_id'] == $parentId;
        });
    }

    private function runProductTests() {
        $this->startTestGroup('Product Tests');
        
        // Test product creation
        $this->runTest('testProductCreation', function() {
            $productModel = new ProductModel();
            $data = [
                'name' => 'Test Product',
                'slug' => 'test-product',
                'price' => 99.99,
                'description' => 'Test Description'
            ];
            $result = $productModel->create($data);
            return $result && $result > 0;
        });

        // Test product retrieval
        $this->runTest('testProductRetrieval', function() {
            $productModel = new ProductModel();
            $products = $productModel->all();
            return is_array($products);
        });
    }

    private function runOrderTests() {
        $this->startTestGroup('Order Tests');
        
        // Test order creation
        $this->runTest('testOrderCreation', function() {
            $orderModel = new OrderModel();
            $data = [
                'user_id' => 1,
                'total' => 199.99,
                'status' => 'pending'
            ];
            $result = $orderModel->create($data);
            return $result && $result > 0;
        });

        // Test order status updates
        $this->runTest('testOrderStatusUpdate', function() {
            $orderModel = new OrderModel();
            $orderId = $orderModel->create([
                'user_id' => 1,
                'total' => 299.99,
                'status' => 'pending'
            ]);
            
            $updated = $orderModel->update($orderId, ['status' => 'processing']);
            $order = $orderModel->find($orderId);
            return $updated && $order['status'] === 'processing';
        });
    }

    private function runCartTests() {
        $this->startTestGroup('Cart Tests');
        
        // Test cart creation
        $this->runTest('testCartCreation', function() {
            $cartModel = new CartModel();
            $data = [
                'user_id' => 1,
                'session_id' => session_id()
            ];
            $result = $cartModel->create($data);
            return $result && $result > 0;
        });

        // Test adding items to cart
        $this->runTest('testAddToCart', function() {
            $cartModel = new CartModel();
            $cartId = $cartModel->create(['user_id' => 1]);
            
            $itemData = [
                'cart_id' => $cartId,
                'product_id' => 1,
                'quantity' => 2
            ];
            $result = $cartModel->addItem($itemData);
            return $result && $result > 0;
        });
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
        echo "\nTest Results:\n";
        echo "============\n\n";
        
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
    $suite = new UnitTestSuite();
    $suite->runAllTests();
}