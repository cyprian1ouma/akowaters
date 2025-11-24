<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Get statistics
$stats = [
    'posts' => $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'comments' => $pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn(),
    'subscribers' => $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn(),
    'pending_comments' => $pdo->query('SELECT COUNT(*) FROM comments WHERE status = "pending"')->fetchColumn(),
    'newsletters' => $pdo->query('SELECT COUNT(*) FROM newsletters')->fetchColumn(),
    'sent_newsletters' => $pdo->query('SELECT COUNT(*) FROM newsletters WHERE status = "sent"')->fetchColumn()
];

// Add projects and gallery stats
try {
    $stats['projects'] = $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $stats['gallery_groups'] = $pdo->query('SELECT COUNT(*) FROM gallery_groups')->fetchColumn();
    $stats['gallery_images'] = $pdo->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn();
} catch (PDOException $e) {
    // Tables might not exist yet
    $stats['projects'] = 0;
    $stats['gallery_groups'] = 0;
    $stats['gallery_images'] = 0;
}

// Get recent posts
$recent_posts = $pdo->query('SELECT * FROM posts ORDER BY created_at DESC LIMIT 5')->fetchAll();

// Get recent comments
$recent_comments = $pdo->query('SELECT c.*, p.title as post_title 
                               FROM comments c 
                               JOIN posts p ON c.post_id = p.id 
                               ORDER BY c.created_at DESC LIMIT 5')->fetchAll();

// Get recent newsletters
$recent_newsletters = $pdo->query('SELECT * FROM newsletters ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Fusion Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600 mt-1">Welcome to your admin dashboard</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-teal-100 rounded-lg p-3">
                        <i class="fas fa-newspaper text-teal-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Total Posts</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['posts']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <i class="fas fa-comments text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Total Comments</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['comments']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Subscribers</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['subscribers']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Pending Comments</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['pending_comments']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <i class="fas fa-envelope text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Total Newsletters</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['newsletters']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                        <i class="fas fa-paper-plane text-indigo-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Sent Newsletters</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['sent_newsletters']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-100 rounded-lg p-3">
                        <i class="fas fa-briefcase text-orange-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Total Projects</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['projects']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-pink-100 rounded-lg p-3">
                        <i class="fas fa-images text-pink-600 text-xl"></i>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Gallery Images</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['gallery_images']; ?></p>
                    </div>
                </div>
            </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Recent Posts -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Posts</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($recent_posts as $post): ?>
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </div>
                            <div class="ml-2 flex-shrink-0">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-teal-100 text-teal-800">
                                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Comments -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Comments</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($recent_comments as $comment): ?>
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($comment['name']); ?>
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    on <?php echo htmlspecialchars($comment['post_title']); ?>
                                </p>
                            </div>
                            <div class="ml-2 flex-shrink-0">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-teal-100 text-teal-800">
                                    <?php echo date('M d, Y', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Newsletters -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Newsletters</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($recent_newsletters as $newsletter): ?>
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <?php echo htmlspecialchars($newsletter['subject']); ?>
                            </div>
                            <div class="ml-2 flex-shrink-0">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-teal-100 text-teal-800">
                                    <?php echo date('M d, Y', strtotime($newsletter['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 