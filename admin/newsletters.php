<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Handle newsletter actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newsletter_id = $_POST['newsletter_id'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'send':
                $stmt = $pdo->prepare("UPDATE newsletters SET status = 'sent', sent_at = NOW() WHERE id = ?");
                $stmt->execute([$newsletter_id]);
                $_SESSION['success'] = "Newsletter sent successfully.";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM newsletters WHERE id = ?");
                $stmt->execute([$newsletter_id]);
                $_SESSION['success'] = "Newsletter deleted successfully.";
                break;
        }
    } catch (PDOException $e) {
        error_log("Newsletter action error: " . $e->getMessage());
        $_SESSION['error'] = "Error processing newsletter: " . $e->getMessage();
    }
    
    header('Location: newsletters.php');
    exit();
}

// Get current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of items per page
$offset = ($page - 1) * $per_page;

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // First, let's check if the table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'newsletters'");
    if ($tableCheck->rowCount() == 0) {
        throw new Exception("Newsletters table does not exist!");
    }

    // Check table structure
    $stmt = $pdo->query("DESCRIBE newsletters");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Available columns: " . print_r($columns, true));
    
    // Remove duplicates while keeping the most recent entry
    $pdo->exec("
        DELETE n1 FROM newsletters n1
        INNER JOIN newsletters n2
        WHERE n1.id < n2.id 
        AND n1.title = n2.title
    ");
    
    // Debug: Show all newsletters after cleanup
    $debug_query = $pdo->query("SELECT id, title, created_at FROM newsletters ORDER BY id DESC");
    $all_newsletters = $debug_query->fetchAll(PDO::FETCH_ASSOC);
    error_log("All newsletters in database after cleanup: " . print_r($all_newsletters, true));
    
    // Base query for newsletters
    $base_query = "FROM newsletters";
    $params = [];
    
    // Add search condition if search term exists
    if (!empty($search)) {
        $base_query .= " WHERE title LIKE ? OR body LIKE ? OR status LIKE ?";
        $search_term = "%{$search}%";
        $params = [$search_term, $search_term, $search_term];
    }
    
    // Get total newsletters count
    $stmt = $pdo->prepare("SELECT COUNT(*) " . $base_query);
    $stmt->execute($params);
    $total_newsletters = $stmt->fetchColumn();
    $total_pages = ceil($total_newsletters / $per_page);
    
    // Get newsletters for current page
    $query = "
        SELECT DISTINCT title, body, status, created_at, sent_at, id
        FROM newsletters
        " . (!empty($search) ? "WHERE title LIKE ? OR body LIKE ? OR status LIKE ?" : "") . "
        ORDER BY created_at DESC, id DESC
        LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
    
    error_log("Newsletter Query: " . $query);
    error_log("Query Parameters: " . print_r($params, true));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Number of newsletters fetched: " . count($newsletters));
    error_log("Newsletters data: " . print_r($newsletters, true));
    
    // Debug information
    error_log("Fetched newsletters: " . print_r($newsletters, true));
    
    // Add default values for missing columns
    foreach ($newsletters as &$newsletter) {
        if (!isset($newsletter['title'])) $newsletter['title'] = 'No Title';
        if (!isset($newsletter['body'])) $newsletter['body'] = 'No Content';
        if (!isset($newsletter['status'])) $newsletter['status'] = 'draft';
        if (!isset($newsletter['created_at'])) $newsletter['created_at'] = date('Y-m-d H:i:s');
    }
    
    error_log("Newsletters data after processing: " . print_r($newsletters, true));
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error = "Error fetching newsletters: " . $e->getMessage();
    $newsletters = [];
    $total_pages = 0;
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $error = $e->getMessage();
    $newsletters = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletters | Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Newsletters</h1>
            <div class="flex space-x-2">
                <a href="create_newsletter.php" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>New Newsletter
                </a>
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
                           placeholder="Search by subject, content or status..." 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <button type="submit" class="bg-teal-600 text-white px-6 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="newsletters.php" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Content</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Created Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-teal-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($newsletters)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No newsletters found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($newsletters as $newsletter): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($newsletter['title'] ?? 'No Title'); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500">
                                            <?php 
                                            $content = strip_tags($newsletter['body'] ?? 'No Content');
                                            echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $newsletter['status'] === 'sent' ? 'bg-teal-100 text-teal-800' : 'bg-orange-100 text-orange-800'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($newsletter['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($newsletter['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="newsletter_id" value="<?php echo $newsletter['id']; ?>">
                                            <?php if ($newsletter['status'] !== 'sent'): ?>
                                                <button type="submit" name="action" value="send" 
                                                        class="text-teal-600 hover:text-teal-900 mr-3 transition-colors">
                                                    <i class="fas fa-paper-plane mr-1"></i>Send
                                                </button>
                                            <?php endif; ?>
                                            <a href="edit_newsletter.php?id=<?php echo $newsletter['id']; ?>" 
                                               class="text-teal-600 hover:text-teal-900 mr-3 transition-colors">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <button type="submit" name="action" value="delete" 
                                                    class="text-red-600 hover:text-red-900 transition-colors"
                                                    onclick="return confirm('Are you sure you want to delete this newsletter?')">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                           class="px-3 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium <?php echo $i === $page ? 'text-teal-600 bg-teal-50' : 'text-gray-500 hover:bg-teal-50 hover:text-teal-700'; ?> transition-colors">
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
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(16);
            doc.text('Newsletters Report', 14, 15);
            
            // Add table headers
            const headers = ['Subject', 'Content', 'Status', 'Created Date'];
            let y = 30;
            
            headers.forEach((header, i) => {
                doc.text(header, 14 + (i * 45), y);
            });
            
            // Add table data
            y += 10;
            const newsletters = <?php 
                echo json_encode(array_map(function($newsletter) {
                    return [
                        'title' => $newsletter['title'] ?? 'No Title',
                        'content' => substr(strip_tags($newsletter['body'] ?? 'No Content'), 0, 30) . '...',
                        'status' => $newsletter['status'] ?? 'draft',
                        'created_at' => date('M j, Y', strtotime($newsletter['created_at'] ?? 'now'))
                    ];
                }, $newsletters));
            ?>;
            
            newsletters.forEach(newsletter => {
                doc.setFontSize(10);
                doc.text(newsletter.title, 14, y);
                doc.text(newsletter.content, 59, y);
                doc.text(newsletter.status, 104, y);
                doc.text(newsletter.created_at, 149, y);
                y += 7;
            });
            
            doc.save('newsletters.pdf');
        }

        function exportToExcel() {
            const newsletters = <?php 
                echo json_encode(array_map(function($newsletter) {
                    return [
                        'title' => $newsletter['title'] ?? 'No Title',
                        'content' => substr(strip_tags($newsletter['body'] ?? 'No Content'), 0, 100) . '...',
                        'status' => $newsletter['status'] ?? 'draft',
                        'created_at' => date('M j, Y', strtotime($newsletter['created_at'] ?? 'now'))
                    ];
                }, $newsletters));
            ?>;
            
            const data = [
                ['Subject', 'Content', 'Status', 'Created Date'],
                ...newsletters.map(newsletter => [
                    newsletter.title,
                    newsletter.content,
                    newsletter.status,
                    newsletter.created_at
                ])
            ];
            
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Newsletters');
            XLSX.writeFile(wb, 'newsletters.xlsx');
        }
    </script>
</body>
</html> 