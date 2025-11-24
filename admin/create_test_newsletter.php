<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'fusion_digital';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        INSERT INTO newsletters (title, body, status, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    
    $title = "Welcome to Fusion Digital Newsletter";
    $body = "<h1>Welcome to Our First Newsletter!</h1>
             <p>We're excited to share our latest updates and insights with you.</p>
             <h2>What's New?</h2>
             <ul>
                 <li>New digital marketing strategies</li>
                 <li>Latest social media trends</li>
                 <li>Upcoming webinars and events</li>
             </ul>
             <p>Stay tuned for more exciting content!</p>";
    $status = "draft";
    
    $stmt->execute([$title, $body, $status]);
    echo "Test newsletter created successfully!";
} catch (PDOException $e) {
    echo "Error creating newsletter: " . $e->getMessage();
}
?> 