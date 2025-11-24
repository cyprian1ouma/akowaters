<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Gallery Item | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Add Gallery Item</h1>
                <p class="text-gray-600 mt-1">Upload images to the gallery</p>
            </div>
            <a href="galleries.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Gallery
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <form action="handlers/gallery_handler.php" method="POST" class="space-y-6" enctype="multipart/form-data">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title (optional)</label>
                    <input type="text" name="title" id="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Image title">
                </div>

                <div>
                    <label for="caption" class="block text-sm font-medium text-gray-700 mb-2">Caption (optional)</label>
                    <textarea name="caption" id="caption" class="w-full px-4 py-2 border border-gray-300 rounded-lg" rows="4"></textarea>
                </div>

                <div>
                    <label for="images" class="block text-sm font-medium text-gray-700 mb-2">Images</label>
                    <div class="mt-1">
                        <input type="file" name="images[]" id="images" accept="image/*" multiple required class="w-full">
                        <p class="text-xs text-gray-500">You can upload multiple images at once (PNG, JPG, GIF) up to 5MB each.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Add to Gallery
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
