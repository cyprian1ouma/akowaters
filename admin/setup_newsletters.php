<?php
require_once 'config/database.php';

try {
    // Drop the existing table if it exists
    $pdo->exec("DROP TABLE IF EXISTS newsletters");
    
    // Create the newsletters table with the correct structure
    $pdo->exec("CREATE TABLE newsletters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        status ENUM('draft', 'sent') DEFAULT 'draft',
        sent_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "Newsletters table created successfully!";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?> 