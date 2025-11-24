<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $status = 'draft'; // Default status for new newsletters
    
    // Debug information
    error_log("Attempting to create newsletter with title: " . $title);
    
    try {
        // First, check if the table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'newsletters'");
        if ($tableCheck->rowCount() == 0) {
            throw new Exception("Newsletters table does not exist!");
        }

        // Check table structure
        $columns = $pdo->query("DESCRIBE newsletters")->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available columns: " . print_r($columns, true));

        // Prepare and execute the insert
        $stmt = $pdo->prepare("INSERT INTO newsletters (title, body, status) VALUES (?, ?, ?)");
        $result = $stmt->execute([$title, $body, $status]);
        
        if ($result) {
            $_SESSION['success'] = "Newsletter created successfully!";
            header('Location: newsletters.php');
            exit();
        } else {
            throw new Exception("Failed to insert newsletter into database");
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $error = "Error creating newsletter: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Debug information
error_log("POST data: " . print_r($_POST, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Newsletter | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Include TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/7nib2gx5502dz72slr0z5vj1g84e3c348u2jy991yf8wmzd6/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#body',
            plugins: 'lists link image table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | link image | table | code',
            height: 400,
            menubar: true,
            branding: false,
            promotion: false,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save(); // Ensure content is saved to textarea
                });
            }
        });
    </script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Create Newsletter</h1>
            <a href="newsletters.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Newsletters
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow p-6">
            <form action="" method="POST" class="space-y-6" onsubmit="return validateForm()">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           required 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="Enter newsletter title">
                </div>

                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    <textarea id="body" 
                              name="body" 
                              required 
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                              placeholder="Enter newsletter content"></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="newsletters.php" 
                       class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                        Create Newsletter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateForm() {
            // Ensure TinyMCE content is saved to textarea
            tinymce.triggerSave();
            
            // Basic validation
            const title = document.getElementById('title').value.trim();
            const body = document.getElementById('body').value.trim();
            
            if (!title) {
                alert('Please enter a title');
                return false;
            }
            
            if (!body) {
                alert('Please enter content');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html> 