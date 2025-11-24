<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';

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
            // skip failed file but continue
            continue;
        }
    }

    return $uploaded;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create gallery group + items, or add items to existing group
    if (!isset($_POST['item_id']) && (isset($_POST['title']) || isset($_POST['group_id']))) {
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $groupId = isset($_POST['group_id']) && is_numeric($_POST['group_id']) ? (int)$_POST['group_id'] : null;

        try {
            // If no group_id provided, create a new group (even for single image)
            if (!$groupId) {
                $stmtG = $pdo->prepare("INSERT INTO gallery_groups (title, caption, created_at) VALUES (?, ?, NOW())");
                $stmtG->execute([$title, $caption]);
                $groupId = $pdo->lastInsertId();
            } else {
                // update group title/caption if provided
                if ($title !== '' || $caption !== '') {
                    $stmtUp = $pdo->prepare("UPDATE gallery_groups SET title = ?, caption = ? WHERE id = ?");
                    $stmtUp->execute([$title, $caption, $groupId]);
                }
            }

            $inserted = 0;

            // Multiple files via images[]
            if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
                $uploaded = handleMultipleUploads($_FILES['images']);
                if (!empty($uploaded)) {
                    $stmt = $pdo->prepare("INSERT INTO gallery_items (group_id, title, caption, image, created_at) VALUES (?, ?, ?, ?, NOW())");
                    foreach ($uploaded as $file) {
                        $stmt->execute([$groupId, $title, $caption, $file]);
                        $inserted++;
                    }
                }
            }

            // Backwards-compatible single file field
            if ($inserted === 0 && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $image = handleImageUpload($_FILES['image']);
                $stmt = $pdo->prepare("INSERT INTO gallery_items (group_id, title, caption, image, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$groupId, $title, $caption, $image]);
                $inserted = 1;
            }

            if ($inserted > 0) {
                $_SESSION['success'] = "Gallery item(s) added to group.";
                header('Location: ../galleries.php');
                exit();
            } else {
                // No files uploaded; redirect to group edit view
                $_SESSION['success'] = "Gallery group saved.";
                header('Location: ../gallery_group.php?id=' . $groupId);
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error creating gallery item: " . $e->getMessage();
            header('Location: ../create_gallery_item.php');
            exit();
        }
    }

    // Update or delete
    $item_id = $_POST['item_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($action === 'delete' && $item_id) {
        try {
            $stmt = $pdo->prepare("SELECT image FROM gallery_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $row = $stmt->fetch();
            if ($row && $row['image']) {
                $path = dirname(__DIR__) . '/uploads/' . $row['image'];
                if (file_exists($path)) unlink($path);
            }
            $stmt = $pdo->prepare("DELETE FROM gallery_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $_SESSION['success'] = "Gallery item deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting gallery item: " . $e->getMessage();
        }
        // If item belonged to a group, redirect back to the group view; otherwise to galleries
        $redirect = '../galleries.php';
        try {
            $stmtG = $pdo->prepare("SELECT group_id FROM gallery_items WHERE id = ?");
            $stmtG->execute([$item_id]);
            $gRow = $stmtG->fetch();
            if ($gRow && $gRow['group_id']) $redirect = '../gallery_group.php?id=' . $gRow['group_id'];
        } catch (Exception $e) {
            // ignore
        }
        header('Location: ' . $redirect);
        exit();
    }

    // Delete entire group
    if ($action === 'delete_group' && isset($_POST['group_id'])) {
        $gid = (int)$_POST['group_id'];
        try {
            // fetch images
            $stmtImgs = $pdo->prepare("SELECT image FROM gallery_items WHERE group_id = ?");
            $stmtImgs->execute([$gid]);
            $imgs = $stmtImgs->fetchAll();
            foreach ($imgs as $ri) {
                if ($ri && $ri['image']) {
                    $p = dirname(__DIR__) . '/uploads/' . $ri['image'];
                    if (file_exists($p)) unlink($p);
                }
            }
            // delete items
            $stmtDel = $pdo->prepare("DELETE FROM gallery_items WHERE group_id = ?");
            $stmtDel->execute([$gid]);
            // delete group
            $stmtG = $pdo->prepare("DELETE FROM gallery_groups WHERE id = ?");
            $stmtG->execute([$gid]);
            $_SESSION['success'] = 'Gallery group deleted.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error deleting group: ' . $e->getMessage();
        }
        header('Location: ../galleries.php');
        exit();
    }

    header('Location: ../galleries.php');
    exit();
}

header('Location: ../galleries.php');
exit;

