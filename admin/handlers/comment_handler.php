<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if (!$comment_id || !$action) {
        $_SESSION['error'] = "Invalid request parameters.";
        header('Location: ../comments.php');
        exit;
    }
    
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $_SESSION['success'] = "Comment approved successfully.";
                break;
                
            case 'unapprove':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $_SESSION['success'] = "Comment unapproved successfully.";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                $_SESSION['success'] = "Comment deleted successfully.";
                break;
                
            default:
                $_SESSION['error'] = "Invalid action specified.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing comment: " . $e->getMessage();
    }
    
    header('Location: ../comments.php');
    exit;
} else {
    header('Location: ../comments.php');
    exit;
} 