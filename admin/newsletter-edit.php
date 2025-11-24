<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

$newsletter = [
    'id' => null,
    'title' => '',
    'body' => '',
    'status' => 'draft'
];

// If editing existing newsletter
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM newsletters WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $newsletter = $stmt->fetch();
        
        if (!$newsletter) {
            $_SESSION['error'] = "Newsletter not found.";
            header('Location: newsletters.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching newsletter: " . $e->getMessage();
        header('Location: newsletters.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $status = $_POST['status'];
    
    // Validate input
    if (empty($title)) {
        $_SESSION['error'] = "Title is required.";
    } else {
        try {
            if ($newsletter['id']) {
                // Update existing newsletter
                $stmt = $pdo->prepare("
                    UPDATE newsletters 
                    SET title = ?, body = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $body, $status, $newsletter['id']]);
                $_SESSION['success'] = "Newsletter updated successfully.";
            } else {
                // Create new newsletter
                $stmt = $pdo->prepare("
                    INSERT INTO newsletters (title, body, status, created_at, updated_at, recipients)
                    VALUES (?, ?, ?, NOW(), NOW(), (SELECT COUNT(*) FROM subscribers WHERE status = 'active'))
                ");
                $stmt->execute([$title, $body, $status]);
                $_SESSION['success'] = "Newsletter created successfully.";
            }
            
            header('Location: newsletters.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error saving newsletter: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $newsletter['id'] ? 'Edit' : 'Create'; ?> Newsletter | Fusion Digital Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#body',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="dashboard.php" class="text-xl font-bold text-teal-600">Fusion Digital Admin</a>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="posts.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Posts
                            </a>
                            <a href="comments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Comments
                            </a>
                            <a href="newsletters.php" class="border-teal-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Newsletters
                            </a>
                            <a href="subscribers.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Subscribers
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="logout.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="px-4 py-6 sm:px-0">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">
                        <?php echo $newsletter['id'] ? 'Edit Newsletter' : 'Create New Newsletter'; ?>
                    </h1>
                    <a href="newsletters.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Back to Newsletters
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Newsletter Form -->
            <div class="mt-8 bg-white shadow rounded-lg">
                <form action="" method="POST" class="space-y-6 p-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Subject</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($newsletter['title']); ?>" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700">Content</label>
                        <textarea name="body" id="body" rows="10" required><?php echo htmlspecialchars($newsletter['body']); ?></textarea>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm">
                            <option value="draft" <?php echo $newsletter['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="sent" <?php echo $newsletter['status'] === 'sent' ? 'selected' : ''; ?>>Sent</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="newsletters.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                            Cancel
                        </a>
                        <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                            <?php echo $newsletter['id'] ? 'Update Newsletter' : 'Create Newsletter'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html> 