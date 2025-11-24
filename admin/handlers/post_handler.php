<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

// Function to generate slug
function generateSlug($title) {
    // Convert the title to lowercase
    $slug = strtolower($title);
    
    // Replace non-alphanumeric characters with a dash
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    
    // Remove multiple consecutive dashes
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Remove dashes from the beginning and end
    $slug = trim($slug, '-');
    
    return $slug;
}

// Function to handle image upload
function handleImageUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception('File is too large. Maximum size is 5MB.');
        }
        throw new Exception('Error uploading file. Error code: ' . $file['error']);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create uploads directory.');
        }
    }

    // Ensure directory is writable
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            throw new Exception('Uploads directory is not writable.');
        }
    }

    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $extension;
    $destination = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file. Please check directory permissions.');
    }

    // Verify the file was actually moved
    if (!file_exists($destination)) {
        throw new Exception('File upload failed. Please try again.');
    }

    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle post creation
    if (isset($_POST['title']) && isset($_POST['content']) && !isset($_POST['post_id'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $status = $_POST['status'] ?? 'draft';
        $slug = generateSlug($title);
        
        try {
            // Handle image upload
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $image = handleImageUpload($_FILES['image']);
            }
            
            // Check if slug already exists
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            $existing = $stmt->fetch();
            
            // If slug exists, append a number
            if ($existing) {
                $counter = 1;
                do {
                    $newSlug = $slug . '-' . $counter;
                    $stmt->execute([$newSlug]);
                    $existing = $stmt->fetch();
                    $counter++;
                } while ($existing);
                $slug = $newSlug;
            }
            
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, status, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $slug, $content, $status, $image]);
            
            $_SESSION['success'] = "Post created successfully.";
            header('Location: ../posts.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error creating post: " . $e->getMessage();
            header('Location: ../create_post.php');
            exit();
        }
    }
    
    // Handle post update
    if (isset($_POST['post_id']) && isset($_POST['title']) && isset($_POST['content'])) {
        $post_id = $_POST['post_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $status = $_POST['status'] ?? 'draft';
        $slug = generateSlug($title);
        
        try {
            // Handle image upload
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Delete old image if exists
                $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $oldPost = $stmt->fetch();
                if ($oldPost && $oldPost['image']) {
                    $oldImagePath = '../uploads/' . $oldPost['image'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                $image = handleImageUpload($_FILES['image']);
            }
            
            // Check if slug already exists (excluding current post)
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $post_id]);
            $existing = $stmt->fetch();
            
            // If slug exists, append a number
            if ($existing) {
                $counter = 1;
                do {
                    $newSlug = $slug . '-' . $counter;
                    $stmt->execute([$newSlug, $post_id]);
                    $existing = $stmt->fetch();
                    $counter++;
                } while ($existing);
                $slug = $newSlug;
            }
            
            if ($image) {
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, status = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $status, $image, $post_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $status, $post_id]);
            }
            
            $_SESSION['success'] = "Post updated successfully.";
            header('Location: ../posts.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating post: " . $e->getMessage();
            header('Location: ../edit_post.php?id=' . $post_id);
            exit();
        }
    }
    
    // Handle other post actions
    $post_id = $_POST['post_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if (!$post_id || !$action) {
        $_SESSION['error'] = "Invalid request parameters.";
        header('Location: ../posts.php');
        exit;
    }
    
    try {
        switch ($action) {
            case 'publish':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'published' WHERE id = ?");
                $stmt->execute([$post_id]);
                $_SESSION['success'] = "Post published successfully.";
                break;
                
            case 'unpublish':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'draft' WHERE id = ?");
                $stmt->execute([$post_id]);
                $_SESSION['success'] = "Post unpublished successfully.";
                break;
                
            case 'delete':
                // Delete post image if exists
                $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $post = $stmt->fetch();
                if ($post && $post['image']) {
                    $image_path = dirname(__DIR__) . '/uploads/' . $post['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
                
                // Delete post
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $_SESSION['success'] = "Post deleted successfully.";
                break;
                
            case 'delete_image':
                // Delete post image if exists
                $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $post = $stmt->fetch();
                
                if ($post && $post['image']) {
                    $image_path = dirname(__DIR__) . '/uploads/' . $post['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                    
                    // Update post to remove image reference
                    $stmt = $pdo->prepare("UPDATE posts SET image = NULL WHERE id = ?");
                    $stmt->execute([$post_id]);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'No image found']);
                exit;
                
            default:
                $_SESSION['error'] = "Invalid action specified.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing post: " . $e->getMessage();
    }
    
    header('Location: ../posts.php');
    exit;
} else {
    header('Location: ../posts.php');
    exit;
} 