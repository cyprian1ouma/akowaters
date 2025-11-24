<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Handle comment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = $_POST['comment_id'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $_SESSION['success'] = "Comment approved successfully.";
                break;
                
            case 'unapprove':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'pending' WHERE id = ?");
                $stmt->execute([$comment_id]);
                $_SESSION['success'] = "Comment unapproved successfully.";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                $_SESSION['success'] = "Comment deleted successfully.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error processing comment: " . $e->getMessage();
    }
    
    header('Location: comments.php');
    exit();
}

// Get current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of items per page
$offset = ($page - 1) * $per_page;

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Base query for comments with post title
    $base_query = "FROM comments c LEFT JOIN posts p ON c.post_id = p.id";
    $params = [];
    
    // Add search condition if search term exists
    if (!empty($search)) {
        $base_query .= " WHERE c.content LIKE ? OR c.name LIKE ? OR c.status LIKE ? OR p.title LIKE ?";
        $search_term = "%{$search}%";
        $params = [$search_term, $search_term, $search_term, $search_term];
    }
    
    // Get total comments count
    $stmt = $pdo->prepare("SELECT COUNT(*) " . $base_query);
    $stmt->execute($params);
    $total_comments = $stmt->fetchColumn();
    $total_pages = ceil($total_comments / $per_page);
    
    // Get comments for current page
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            CASE 
                WHEN c.post_id = 0 THEN 'Testimonial'
                ELSE p.title 
            END as post_title,
            CASE 
                WHEN c.post_id = 0 THEN 'testimonial'
                ELSE 'comment'
            END as type
        " . $base_query . "
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    // Add pagination parameters
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt->execute($params);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching comments: " . $e->getMessage();
    $comments = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Comments</h1>
            <div class="flex space-x-2">
                <button onclick="exportToPDF()" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                </button>
                <button onclick="exportToExcel()" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>Export Excel
                </button>
            </div>
                    </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <form action="" method="GET" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by content, author or status..." 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="comments.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-teal-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Post/Testimonial</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($comments as $comment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($comment['name'] ?? 'Anonymous'); ?>
                                </div>
                                <?php if ($comment['type'] === 'comment'): ?>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($comment['email'] ?? 'No Email'); ?>
                                </div>
                                <?php endif; ?>
                                <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $comment['type'] === 'testimonial' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($comment['type'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($comment['post_title'] ?? 'No Title'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $comment['status'] === 'approved' ? 'bg-teal-100 text-teal-800' : 'bg-orange-100 text-orange-800'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($comment['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form action="handlers/comment_handler.php" method="POST" class="inline">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <?php if ($comment['status'] === 'approved'): ?>
                                        <button type="submit" name="action" value="unapprove" 
                                                class="text-orange-600 hover:text-orange-900 mr-3 transition-colors">
                                            <i class="fas fa-ban mr-1"></i>Unapprove
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="approve" 
                                                class="text-teal-600 hover:text-teal-900 mr-3 transition-colors">
                                            <i class="fas fa-check mr-1"></i>Approve
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" 
                                            class="text-red-600 hover:text-red-900 transition-colors"
                                            onclick="return confirm('Are you sure you want to delete this <?php echo $comment['type']; ?>?')">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-6">
            <nav class="inline-flex rounded-md shadow">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-teal-50 hover:text-teal-700 transition-colors">
                        Previous
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i === $page ? 'text-teal-600 bg-teal-50' : 'text-gray-500 hover:bg-teal-50 hover:text-teal-700'; ?> transition-colors">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-teal-50 hover:text-teal-700 transition-colors">
                        Next
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
    </div>

    <script>
        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(16);
            doc.text('Comments List', 14, 15);
            
            // Add date
            doc.setFontSize(10);
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 22);
            
            // Add table headers
            const headers = [['Comment', 'Post', 'Author', 'Status', 'Date']];
            const data = <?php echo json_encode(array_map(function($comment) {
                return [
                    $comment['content'],
                    $comment['post_title'],
                    $comment['name'],
                    ucfirst($comment['status']),
                    date('M j, Y', strtotime($comment['created_at']))
                ];
            }, $comments)); ?>;
            
            doc.autoTable({
                head: headers,
                body: data,
                startY: 30,
                theme: 'grid',
                styles: { fontSize: 8 },
                headStyles: { fillColor: [66, 139, 202] }
            });
            
            doc.save('comments.pdf');
        }

        // Export to Excel
        function exportToExcel() {
            const data = <?php echo json_encode(array_map(function($comment) {
                return [
                    'Comment' => $comment['content'],
                    'Post' => $comment['post_title'],
                    'Author' => $comment['name'],
                    'Email' => $comment['email'],
                    'Status' => ucfirst($comment['status']),
                    'Date' => date('M j, Y', strtotime($comment['created_at']))
                ];
            }, $comments)); ?>;
            
            const ws = XLSX.utils.json_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Comments");
            
            XLSX.writeFile(wb, "comments.xlsx");
        }
    </script>
</body>
</html> 