<?php
require_once __DIR__ . '/../app/autoload.php';

header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Database Connection Test ===\n\n";

try {
    $model = new Model();
    echo "1. Database connection successful\n\n";
    
    echo "2. Testing categories table:\n";
    $sql = "SHOW TABLES LIKE 'categories'";
    $result = $model->db()->select($sql);
    
    if (!empty($result)) {
        echo "Categories table exists\n\n";
        
        echo "3. Listing all categories:\n";
        $sql = "SELECT id, name, slug, is_active, parent_id FROM categories";
        $categories = $model->db()->select($sql);
        
        if (!empty($categories)) {
            foreach ($categories as $cat) {
                echo "\nID: {$cat['id']}";
                echo "\nName: {$cat['name']}";
                echo "\nSlug: {$cat['slug']}";
                echo "\nActive: " . ($cat['is_active'] ? 'Yes' : 'No');
                echo "\nParent ID: " . ($cat['parent_id'] ?? 'None');
                echo "\n-------------------";
            }
        } else {
            echo "No categories found in the database\n";
        }
    } else {
        echo "Categories table does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
