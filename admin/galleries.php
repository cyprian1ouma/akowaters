<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Handle delete actions (single item or whole group)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? null;
    $group_id = $_POST['group_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($action === 'delete' && $item_id) {
        // delete single item
        try {
            $stmt = $pdo->prepare("SELECT image FROM gallery_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $row = $stmt->fetch();
            if ($row && $row['image']) {
                $path = dirname(__FILE__) . '/uploads/' . $row['image'];
                if (file_exists($path)) unlink($path);
            }
            $stmt = $pdo->prepare("DELETE FROM gallery_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $_SESSION['success'] = "Gallery item deleted.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting item: " . $e->getMessage();
        }
    }

    if ($action === 'delete_group' && $group_id) {
        // delegate to handler which already handles group deletion
        header('Location: handlers/gallery_handler.php');
    }

    header('Location: galleries.php');
    exit();
}

try {
    // Fetch groups with representative image and count
    $stmt = $pdo->prepare(
        "SELECT g.id AS group_id, g.title AS group_title, g.caption AS group_caption, g.created_at AS created_at,
            (SELECT image FROM gallery_items WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) AS rep_image,
            (SELECT COUNT(*) FROM gallery_items WHERE group_id = g.id) AS cnt
         FROM gallery_groups g
         ORDER BY g.created_at DESC"
    );
    $stmt->execute();
    $groups = $stmt->fetchAll();

    // Fetch standalone items (not assigned to a group) and treat each as a single-item group
    $stmt2 = $pdo->prepare("SELECT id AS group_id, title AS group_title, caption AS group_caption, created_at, image AS rep_image, 1 AS cnt FROM gallery_items WHERE group_id IS NULL ORDER BY created_at DESC");
    $stmt2->execute();
    $standalone = $stmt2->fetchAll();

    // Merge and sort by created_at desc
    $all = array_merge($groups, $standalone);
    usort($all, function($a, $b) { return strtotime($b['created_at']) <=> strtotime($a['created_at']); });
} catch (PDOException $e) {
    $all = [];
    $_SESSION['error'] = "Error fetching gallery groups: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Gallery</h1>
                <p class="text-gray-600 mt-1">Manage gallery images</p>
            </div>
            <a href="create_gallery_item.php" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Image
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

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($all as $group): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="relative h-56 bg-gray-100 overflow-hidden">
                    <?php if (!empty($group['rep_image'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($group['rep_image']); ?>" alt="<?php echo htmlspecialchars($group['group_title'] ?: 'Gallery'); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400"><i class="fas fa-image text-4xl"></i></div>
                    <?php endif; ?>
                    <?php if ((int)$group['cnt'] > 1): ?>
                        <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-sm px-2 py-1 rounded"><?php echo (int)$group['cnt']; ?> images</div>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($group['group_title'] ?: 'Untitled'); ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($group['group_caption'] ?: ''); ?></p>
                    <div class="flex justify-between items-center">
                        <a href="gallery_group.php?id=<?php echo htmlspecialchars($group['group_id']); ?>" class="text-teal-600 hover:underline">View</a>
                        <div class="flex items-center space-x-2">
                            <?php if ((int)$group['cnt'] === 1): ?>
                                <form action="handlers/gallery_handler.php" method="POST" onsubmit="return confirm('Delete this image?');">
                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($group['group_id']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php else: ?>
                                <form action="handlers/gallery_handler.php" method="POST" onsubmit="return confirm('Delete this group and all its images?');">
                                    <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group['group_id']); ?>">
                                    <input type="hidden" name="action" value="delete_group">
                                    <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
