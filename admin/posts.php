<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Handle post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'publish':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'published' WHERE id = ?");
                $stmt->execute([$post_id]);
                $_SESSION['success'] = "Post published successfully.";
                break;
                
            case 'unpublish':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'draft' WHERE id = ?");
        $stmt->execute([$post_id]);
                $_SESSION['success'] = "Post unpublished successfully.";
                break;
                
            case 'delete':
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $_SESSION['success'] = "Post deleted successfully.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing post: " . $e->getMessage();
    }
    
    header('Location: posts.php');
    exit();
}

// Get current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of items per page
$offset = ($page - 1) * $per_page;

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Base query for posts
    $base_query = "FROM posts";
    $params = [];
    
    // Add search condition if search term exists
    if (!empty($search)) {
        $base_query .= " WHERE title LIKE ? OR content LIKE ? OR status LIKE ?";
        $search_term = "%{$search}%";
        $params = [$search_term, $search_term, $search_term];
    }

    // Get total posts count
    $stmt = $pdo->prepare("SELECT COUNT(*) " . $base_query);
    $stmt->execute($params);
    $total_posts = $stmt->fetchColumn();
    $total_pages = ceil($total_posts / $per_page);

    // Get posts for current page
    $stmt = $pdo->prepare("
        SELECT * " . $base_query . "
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    // Add pagination parameters
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching posts: " . $e->getMessage();
    $posts = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Posts</h1>
                <p class="text-gray-600 mt-1">Manage your blog posts and content</p>
            </div>
            <a href="create_post.php" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Create New Post
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Posts Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($posts as $post): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($post['image']): ?>
                                        <div class="flex-shrink-0 h-16 w-16 mr-4">
                                            <img src="../admin/uploads/<?php echo htmlspecialchars($post['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                 class="h-16 w-16 object-cover rounded-lg">
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 line-clamp-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $post['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($post['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a href="edit_post.php?id=<?php echo $post['id']; ?>" 
                                       class="text-teal-600 hover:text-teal-900 transition-colors"
                                       title="Edit Post">
                                        <i class="fas fa-edit"></i>
                                        <span class="sr-only">Edit</span>
                                    </a>
                                    <?php if ($post['status'] === 'draft'): ?>
                                        <form action="handlers/post_handler.php" method="POST" class="inline">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="hidden" name="action" value="publish">
                                            <button type="submit" 
                                                    class="text-green-600 hover:text-green-900 transition-colors"
                                                    title="Publish Post">
                                                <i class="fas fa-check"></i>
                                                <span class="sr-only">Publish</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="handlers/post_handler.php" method="POST" class="inline">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <input type="hidden" name="action" value="unpublish">
                                            <button type="submit" 
                                                    class="text-yellow-600 hover:text-yellow-900 transition-colors"
                                                    title="Unpublish Post">
                                                <i class="fas fa-undo"></i>
                                                <span class="sr-only">Unpublish</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="handlers/post_handler.php" method="POST" class="inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this post?');">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900 transition-colors"
                                                title="Delete Post">
                                            <i class="fas fa-trash"></i>
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(16);
            doc.text('Posts List', 14, 15);
            
            // Add date
            doc.setFontSize(10);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 22);
            
            // Add table headers
            const headers = [['Title', 'Status', 'Created Date']];
            const data = <?php echo json_encode(array_map(function($post) {
                return [
                    $post['title'],
                    ucfirst($post['status']),
                    date('M j, Y', strtotime($post['created_at']))
                ];
            }, $posts)); ?>;
            
            doc.autoTable({
                head: headers,
                body: data,
                startY: 30,
                theme: 'grid',
                styles: { fontSize: 8 },
                headStyles: { fillColor: [66, 139, 202] }
            });
            
            doc.save('posts.pdf');
        }

        // Export to Excel
        function exportToExcel() {
            const data = <?php echo json_encode(array_map(function($post) {
                return [
                    'Title' => $post['title'],
                    'Status' => ucfirst($post['status']),
                    'Created Date' => date('M j, Y', strtotime($post['created_at']))
                ];
            }, $posts)); ?>;
            
            const ws = XLSX.utils.json_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Posts");
            
            XLSX.writeFile(wb, "posts.xlsx");
        }
    </script>
</body>
</html> 