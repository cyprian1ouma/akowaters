<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Get project ID from URL
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($project_id <= 0) {
    header('Location: projects.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        header('Location: projects.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching project: " . $e->getMessage();
    header('Location: projects.php');
    exit;
}

// Fetch additional images for this project
try {
    $stmt = $pdo->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$project_id]);
    $project_images = $stmt->fetchAll();
} catch (PDOException $e) {
    $project_images = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Edit Project</h1>
                <p class="text-gray-600 mt-1">Update project details</p>
            </div>
            <a href="projects.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Projects
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <form action="handlers/project_handler.php" method="POST" class="space-y-6" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" id="title" required value="<?php echo htmlspecialchars($project['title']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition" rows="8"><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Project Settings</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="url" class="block text-sm font-medium text-gray-700 mb-2">External URL (optional)</label>
                                    <input type="url" name="url" id="url" value="<?php echo htmlspecialchars($project['url'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition" placeholder="https://example.com">
                                </div>

                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" id="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                                        <option value="draft" <?php echo $project['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $project['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                                    <?php if ($project['image']): ?>
                                        <div class="mb-4">
                                            <img src="uploads/<?php echo htmlspecialchars($project['image']); ?>" alt="Current image" class="h-32 w-32 object-cover rounded-lg mb-2">
                                            <p class="text-sm text-gray-600">Current image</p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-teal-500 transition-colors">
                                        <div class="space-y-1 text-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="image" class="relative cursor-pointer rounded-md font-medium text-teal-600 hover:text-teal-500 focus-within:outline-none">
                                                    <span>Upload new image</span>
                                                    <input type="file" name="image" id="image" accept="image/*" class="sr-only">
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                                        </div>
                                    </div>
                                </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Project Gallery</label>
                                        <?php if (!empty($project_images)): ?>
                                            <div class="grid grid-cols-3 gap-3 mb-4">
                                                <?php foreach ($project_images as $img): ?>
                                                    <div class="relative">
                                                        <img src="uploads/<?php echo htmlspecialchars($img['image']); ?>" class="h-24 w-full object-cover rounded">
                                                        <form method="POST" action="handlers/project_handler.php" class="absolute top-1 right-1">
                                                            <input type="hidden" name="action" value="delete_image">
                                                            <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                                            <button type="submit" class="bg-red-600 text-white p-1 rounded text-xs">Delete</button>
                                                        </form>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-500 mb-2">No additional images yet.</p>
                                        <?php endif; ?>

                                        <div>
                                            <label for="images" class="block text-sm font-medium text-gray-700 mb-2">Upload Additional Images</label>
                                            <input type="file" name="images[]" id="images" accept="image/*" multiple class="w-full">
                                            <p class="text-xs text-gray-500">You can upload multiple images (PNG, JPG, GIF) up to 5MB each. These will be added to the project gallery.</p>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Project
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
