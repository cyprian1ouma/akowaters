<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get all active subscribers
        $stmt = $pdo->prepare("
            SELECT email, name, status, subscribed_at 
            FROM subscribers 
            WHERE status = 'active' 
            ORDER BY subscribed_at DESC
        ");
        $stmt->execute();
        $subscribers = $stmt->fetchAll();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
        
        // Create CSV file
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, ['Email', 'Name', 'Status', 'Subscribed Date']);
        
        // Add data
        foreach ($subscribers as $subscriber) {
            fputcsv($output, [
                $subscriber['email'],
                $subscriber['name'],
                $subscriber['status'],
                date('Y-m-d H:i:s', strtotime($subscriber['subscribed_at']))
            ]);
        }
        
        fclose($output);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error exporting subscribers: " . $e->getMessage();
        header('Location: subscribers.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Subscribers | Fusion Digital Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                            <a href="newsletters.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Newsletters
                            </a>
                            <a href="subscribers.php" class="border-teal-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
                    <h1 class="text-2xl font-semibold text-gray-900">Export Subscribers</h1>
                    <a href="subscribers.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Back to Subscribers
                    </a>
                </div>
            </div>

            <!-- Export Form -->
            <div class="mt-8 bg-white shadow rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900">Export Options</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Click the button below to export all active subscribers to a CSV file. The file will include:
                        </p>
                        <ul class="mt-2 list-disc list-inside text-sm text-gray-600">
                            <li>Email address</li>
                            <li>Name (if provided)</li>
                            <li>Subscription status</li>
                            <li>Subscription date</li>
                        </ul>
                    </div>

                    <form action="" method="POST" class="space-y-6">
                        <div class="flex justify-end space-x-4">
                            <a href="subscribers.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                                Export Subscribers
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 