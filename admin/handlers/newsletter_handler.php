<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newsletter_id = $_POST['newsletter_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if (!$newsletter_id || !$action) {
        $_SESSION['error'] = "Invalid request parameters.";
        header('Location: ../newsletters.php');
        exit;
    }
    
    try {
        switch ($action) {
            case 'send':
                $stmt = $pdo->prepare("UPDATE newsletters SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$newsletter_id]);
                $_SESSION['success'] = "Newsletter sent successfully.";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM newsletters WHERE id = ?");
                $stmt->execute([$newsletter_id]);
                $_SESSION['success'] = "Newsletter deleted successfully.";
                break;
                
            default:
                $_SESSION['error'] = "Invalid action specified.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing newsletter: " . $e->getMessage();
    }
    
    header('Location: ../newsletters.php');
    exit;
} else {
    header('Location: ../newsletters.php');
    exit;
} 