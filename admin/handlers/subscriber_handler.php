<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriber_id = $_POST['subscriber_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if (!$subscriber_id || !$action) {
        $_SESSION['error'] = "Invalid request parameters.";
        header('Location: ../subscribers.php');
        exit;
    }
    
    try {
        switch ($action) {
            case 'subscribe':
                $stmt = $pdo->prepare("UPDATE subscribers SET status = 'active' WHERE id = ?");
                $stmt->execute([$subscriber_id]);
                $_SESSION['success'] = "Subscriber activated successfully.";
                break;
                
            case 'unsubscribe':
                $stmt = $pdo->prepare("UPDATE subscribers SET status = 'unsubscribed' WHERE id = ?");
                $stmt->execute([$subscriber_id]);
                $_SESSION['success'] = "Subscriber unsubscribed successfully.";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = ?");
                $stmt->execute([$subscriber_id]);
                $_SESSION['success'] = "Subscriber deleted successfully.";
                break;
                
            default:
                $_SESSION['error'] = "Invalid action specified.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing subscriber: " . $e->getMessage();
    }
    
    header('Location: ../subscribers.php');
    exit;
} else {
    header('Location: ../subscribers.php');
    exit;
} 