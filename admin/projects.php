<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($project_id && $action) {
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
                    $row = $stmt->fetch();
                    if ($row && $row['image']) {
                        $path = dirname(__FILE__) . '/uploads/' . $row['image'];
                        if (file_exists($path)) unlink($path);
                    }
                    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                    $stmt->execute([$project_id]);
                    $_SESSION['success'] = "Project deleted successfully.";
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error processing project: " . $e->getMessage();
        }
    }

    header('Location: projects.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $total_pages = ceil($total / $per_page);

    $stmt = $pdo->prepare("SELECT * FROM projects ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$per_page, $offset]);
    $projects = $stmt->fetchAll();
} catch (PDOException $e) {
    $projects = [];
    $total_pages = 0;
    $_SESSION['error'] = "Error fetching projects: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Projects</h1>
                <p class="text-gray-600 mt-1">Manage projects that appear on the site</p>
            </div>
            <a href="create_project.php" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Create New Project
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

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($projects as $project): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($project['image']): ?>
                                        <div class="flex-shrink-0 h-16 w-16 mr-4">
                                            <img src="uploads/<?php echo htmlspecialchars($project['image']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="h-16 w-16 object-cover rounded-lg">
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['title']); ?></div>
                                        <div class="text-sm text-gray-500 line-clamp-2"><?php echo substr(strip_tags($project['description']),0,120) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $project['status']==='published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($project['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="text-teal-600 hover:text-teal-900" title="Edit Project">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($project['status'] === 'draft'): ?>
                                        <form action="handlers/project_handler.php" method="POST" class="inline"><input type="hidden" name="project_id" value="<?php echo $project['id']; ?>"><input type="hidden" name="action" value="publish"><button type="submit" class="text-green-600 hover:text-green-900"><i class="fas fa-check"></i></button></form>
                                    <?php else: ?>
                                        <form action="handlers/project_handler.php" method="POST" class="inline"><input type="hidden" name="project_id" value="<?php echo $project['id']; ?>"><input type="hidden" name="action" value="unpublish"><button type="submit" class="text-yellow-600 hover:text-yellow-900"><i class="fas fa-undo"></i></button></form>
                                    <?php endif; ?>
                                    <form action="handlers/project_handler.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this project?');"><input type="hidden" name="project_id" value="<?php echo $project['id']; ?>"><input type="hidden" name="action" value="delete"><button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button></form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
