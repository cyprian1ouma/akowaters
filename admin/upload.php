<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'No file uploaded']);
    exit();
}

$file = $_FILES['file'];
$upload_dir = '../uploads/';

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid file type. Only JPG, PNG & GIF files are allowed.']);
    exit();
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$upload_path = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Return success response
    echo json_encode([
        'location' => '../uploads/' . $filename
    ]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to upload file']);
} 