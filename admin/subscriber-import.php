<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if ($handle !== false) {
            // Skip header row
            fgetcsv($handle);
            
            $success_count = 0;
            $error_count = 0;
            $errors = [];
            
            try {
                $pdo->beginTransaction();
                
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) >= 2) {
                        $email = filter_var(trim($data[0]), FILTER_VALIDATE_EMAIL);
                        $name = trim($data[1]);
                        
                        if ($email) {
                            // Check if subscriber already exists
                            $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
                            $stmt->execute([$email]);
                            
                            if (!$stmt->fetch()) {
                                $stmt = $pdo->prepare("
                                    INSERT INTO subscribers (email, name, status, subscribed_at)
                                    VALUES (?, ?, 'active', NOW())
                                ");
                                $stmt->execute([$email, $name]);
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Email already exists: $email";
                            }
                        } else {
                            $error_count++;
                            $errors[] = "Invalid email format: {$data[0]}";
                        }
                    }
                }
                
                $pdo->commit();
                
                if ($success_count > 0) {
                    $_SESSION['success'] = "Successfully imported $success_count subscribers.";
                }
                if ($error_count > 0) {
                    $_SESSION['error'] = "Failed to import $error_count subscribers. " . implode(', ', $errors);
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Error importing subscribers: " . $e->getMessage();
            }
            
            fclose($handle);
        } else {
            $_SESSION['error'] = "Error reading the uploaded file.";
        }
    } else {
        $_SESSION['error'] = "Please select a valid CSV file.";
    }
    
    header('Location: subscribers.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Subscribers | Fusion Digital Admin</title>
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
                    <h1 class="text-2xl font-semibold text-gray-900">Import Subscribers</h1>
                    <a href="subscribers.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Back to Subscribers
                    </a>
                </div>
            </div>

            <!-- Import Form -->
            <div class="mt-8 bg-white shadow rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h2 class="text-lg font-medium text-gray-900">Instructions</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Upload a CSV file containing subscriber information. The file should have the following columns:
                        </p>
                        <ul class="mt-2 list-disc list-inside text-sm text-gray-600">
                            <li>Email (required)</li>
                            <li>Name (optional)</li>
                        </ul>
                        <p class="mt-2 text-sm text-gray-600">
                            The first row should contain the column headers. Duplicate email addresses will be skipped.
                        </p>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label for="csv_file" class="block text-sm font-medium text-gray-700">CSV File</label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                        </div>

                        <div class="flex justify-end space-x-4">
                            <a href="subscribers.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                                Import Subscribers
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 