<?php
require_once 'config/database.php';

try {
    // Get the table structure
    $stmt = $pdo->query("DESCRIBE newsletters");
    $columns = $stmt->fetchAll();
    
    echo "<h2>Newsletters Table Structure:</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Get sample data
    $stmt = $pdo->query("SELECT * FROM newsletters LIMIT 1");
    $sample = $stmt->fetch();
    
    echo "<h2>Sample Data:</h2>";
    echo "<pre>";
    print_r($sample);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 