<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($group_id <= 0) {
    header('Location: galleries.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM gallery_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();

    if (!$group) {
        header('Location: galleries.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM gallery_items WHERE group_id = ? ORDER BY created_at DESC");
    $stmt->execute([$group_id]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching group: ' . $e->getMessage();
    header('Location: galleries.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Group | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($group['title'] ?: 'Gallery Group'); ?></h1>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($group['caption'] ?: ''); ?></p>
            </div>
            <a href="galleries.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Gallery
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Upload Additional Images to this Group</h2>
            <form action="handlers/gallery_handler.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <div>
                    <input type="file" name="images[]" multiple accept="image/*" required class="w-full">
                    <p class="text-xs text-gray-500">You can upload multiple images at once (PNG, JPG, GIF) up to 5MB each.</p>
                </div>
                <div class="mt-4 text-right">
                    <button class="px-4 py-2 bg-teal-600 text-white rounded">Upload</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($items as $it): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="h-56 overflow-hidden">
                    <img src="uploads/<?php echo htmlspecialchars($it['image']); ?>" alt="" class="w-full h-full object-cover">
                </div>
                <div class="p-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold"><?php echo htmlspecialchars($it['title'] ?: ''); ?></h4>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($it['caption'] ?: ''); ?></p>
                    </div>
                    <form action="handlers/gallery_handler.php" method="POST" onsubmit="return confirm('Delete this image?');">
                        <input type="hidden" name="item_id" value="<?php echo $it['id']; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8">
            <form action="handlers/gallery_handler.php" method="POST" onsubmit="return confirm('Delete this entire group and all its images?');">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <input type="hidden" name="action" value="delete_group">
                <button class="px-4 py-2 bg-red-600 text-white rounded">Delete Group</button>
            </form>
        </div>
    </div>
</body>
</html>
