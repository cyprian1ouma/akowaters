<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

function generateSlug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

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

    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create uploads directory.');
        }
    }

    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            throw new Exception('Uploads directory is not writable.');
        }
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $extension;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file. Please check directory permissions.');
    }

    if (!file_exists($destination)) {
        throw new Exception('File upload failed. Please try again.');
    }

    return $filename;
}

function handleMultipleUploads($filesArray) {
    $uploaded = [];
    // $filesArray is the $_FILES['images'] structure
    if (!isset($filesArray) || !is_array($filesArray['name'])) return $uploaded;

    $count = count($filesArray['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($filesArray['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

        $file = [
            'name' => $filesArray['name'][$i],
            'type' => $filesArray['type'][$i],
            'tmp_name' => $filesArray['tmp_name'][$i],
            'error' => $filesArray['error'][$i],
            'size' => $filesArray['size'][$i]
        ];

        try {
            $filename = handleImageUpload($file);
            $uploaded[] = $filename;
        } catch (Exception $e) {
            // Skip failures but continue with others
            // Optionally, you could collect errors to show to admin
            continue;
        }
    }

    return $uploaded;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create project
    if (isset($_POST['title']) && isset($_POST['description']) && !isset($_POST['project_id'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $url = trim($_POST['url'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $slug = generateSlug($title);

        try {
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $image = handleImageUpload($_FILES['image']);
            }

            // Ensure unique slug
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ?");
            $stmt->execute([$slug]);
            $existing = $stmt->fetch();
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

            $stmt = $pdo->prepare("INSERT INTO projects (title, slug, description, image, url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $slug, $description, $image, $url, $status]);

            // handle additional images (multiple)
            $projectId = $pdo->lastInsertId();
            if (isset($_FILES['images'])) {
                $uploadedImages = handleMultipleUploads($_FILES['images']);
                if (!empty($uploadedImages)) {
                    $order = 0;
                    $stmtIns = $pdo->prepare("INSERT INTO project_images (project_id, image, sort_order, created_at) VALUES (?, ?, ?, NOW())");
                    foreach ($uploadedImages as $imgFile) {
                        $stmtIns->execute([$projectId, $imgFile, $order]);
                        $order++;
                    }
                }
            }

            $_SESSION['success'] = "Project created successfully.";
            header('Location: ../projects.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error creating project: " . $e->getMessage();
            header('Location: ../create_project.php');
            exit();
        }
    }

    // Update project
    if (isset($_POST['project_id']) && isset($_POST['title']) && isset($_POST['description'])) {
        $project_id = $_POST['project_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $url = trim($_POST['url'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $slug = generateSlug($title);

        try {
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                // delete old image
                $stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
                $stmt->execute([$project_id]);
                $old = $stmt->fetch();
                if ($old && $old['image']) {
                    $oldPath = dirname(__DIR__) . '/uploads/' . $old['image'];
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $image = handleImageUpload($_FILES['image']);
            }

            // Ensure unique slug excluding current
            $stmt = $pdo->prepare("SELECT id FROM projects WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $project_id]);
            $existing = $stmt->fetch();
            if ($existing) {
                $counter = 1;
                do {
                    $newSlug = $slug . '-' . $counter;
                    $stmt->execute([$newSlug, $project_id]);
                    $existing = $stmt->fetch();
                    $counter++;
                } while ($existing);
                $slug = $newSlug;
            }

            if ($image) {
                $stmt = $pdo->prepare("UPDATE projects SET title = ?, slug = ?, description = ?, url = ?, status = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $description, $url, $status, $image, $project_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE projects SET title = ?, slug = ?, description = ?, url = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $description, $url, $status, $project_id]);
            }

            // handle additional uploaded images for this project
            if (isset($_FILES['images'])) {
                $uploadedImages = handleMultipleUploads($_FILES['images']);
                if (!empty($uploadedImages)) {
                    // determine current max sort_order
                    $stmtOrder = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM project_images WHERE project_id = ?");
                    $stmtOrder->execute([$project_id]);
                    $row = $stmtOrder->fetch();
                    $order = isset($row['max_order']) ? ((int)$row['max_order'] + 1) : 0;
                    $stmtIns = $pdo->prepare("INSERT INTO project_images (project_id, image, sort_order, created_at) VALUES (?, ?, ?, NOW())");
                    foreach ($uploadedImages as $imgFile) {
                        $stmtIns->execute([$project_id, $imgFile, $order]);
                        $order++;
                    }
                }
            }

            $_SESSION['success'] = "Project updated successfully.";
            header('Location: ../projects.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating project: " . $e->getMessage();
            header('Location: ../edit_project.php?id=' . $project_id);
            exit();
        }
    }

    // Other project actions
    $project_id = $_POST['project_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$project_id || !$action) {
        $_SESSION['error'] = "Invalid request parameters.";
        header('Location: ../projects.php');
        exit;
    }

    try {
        switch ($action) {
            case 'publish':
                $stmt = $pdo->prepare("UPDATE projects SET status = 'published' WHERE id = ?");
                $stmt->execute([$project_id]);
                $_SESSION['success'] = "Project published successfully.";
                break;
            case 'unpublish':
                $stmt = $pdo->prepare("UPDATE projects SET status = 'draft' WHERE id = ?");
                $stmt->execute([$project_id]);
                $_SESSION['success'] = "Project unpublished successfully.";
                break;
            case 'delete':
                $stmt = $pdo->prepare("SELECT image FROM projects WHERE id = ?");
                $stmt->execute([$project_id]);
                $old = $stmt->fetch();
                if ($old && $old['image']) {
                    $path = dirname(__DIR__) . '/uploads/' . $old['image'];
                    if (file_exists($path)) unlink($path);
                }
                // delete additional project images files
                try {
                    $stmtImgs = $pdo->prepare("SELECT image FROM project_images WHERE project_id = ?");
                    $stmtImgs->execute([$project_id]);
                    $imgs = $stmtImgs->fetchAll();
                    foreach ($imgs as $ri) {
                        if ($ri && $ri['image']) {
                            $p = dirname(__DIR__) . '/uploads/' . $ri['image'];
                            if (file_exists($p)) unlink($p);
                        }
                    }
                } catch (Exception $e) {
                    // ignore file deletion errors
                }
                $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->execute([$project_id]);
                $_SESSION['success'] = "Project deleted successfully.";
                break;
            case 'delete_image':
                $image_id = $_POST['image_id'] ?? null;
                if ($image_id) {
                    // fetch image info
                    $stmtImg = $pdo->prepare("SELECT image FROM project_images WHERE id = ?");
                    $stmtImg->execute([$image_id]);
                    $imgRow = $stmtImg->fetch();
                    if ($imgRow && $imgRow['image']) {
                        $p = dirname(__DIR__) . '/uploads/' . $imgRow['image'];
                        if (file_exists($p)) unlink($p);
                    }
                    $stmtDel = $pdo->prepare("DELETE FROM project_images WHERE id = ?");
                    $stmtDel->execute([$image_id]);
                    $_SESSION['success'] = "Image deleted successfully.";
                } else {
                    $_SESSION['error'] = "Image id not specified.";
                }
                break;
            default:
                $_SESSION['error'] = "Invalid action specified.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing project: " . $e->getMessage();
    }

    header('Location: ../projects.php');
    exit;
} else {
    header('Location: ../projects.php');
    exit;
}

