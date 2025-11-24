<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Function to generate slug
function generateSlug($title, $pdo, $exclude_id = null) {
    // Convert to lowercase
    $slug = strtolower($title);
    
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Remove leading and trailing hyphens
    $slug = trim($slug, '-');
    
    // Check if slug exists and append number if it does
    $baseSlug = $slug;
    $counter = 1;
    
    do {
        if ($exclude_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $exclude_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    } while ($exists);
    
    return $slug;
}

$post = [
    'id' => null,
    'title' => '',
    'content' => '',
    'status' => 'draft',
    'image' => '',
    'slug' => ''
];

// If editing existing post
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $post = $stmt->fetch();
        
        if (!$post) {
            $_SESSION['error'] = "Post not found.";
            header('Location: posts.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching post: " . $e->getMessage();
        header('Location: posts.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $status = $_POST['status'];
    $post_id = $_POST['post_id'] ?? null;
    
    // Validate input
    if (empty($title)) {
        $_SESSION['error'] = "Title is required.";
    } else {
        try {
            // Generate slug
            $slug = generateSlug($title, $pdo, $post_id);
            
            // Handle image upload
            $image = $post['image']; // Keep existing image by default
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/';
                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
                }

                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if ($post['image'] && file_exists($upload_dir . $post['image'])) {
                        unlink($upload_dir . $post['image']);
                    }
                    $image = $new_filename;
                }
            }
            
            if (!isset($_SESSION['error'])) {
                if ($post_id) {
                    // Update existing post
                    $stmt = $pdo->prepare("
                        UPDATE posts 
                        SET title = ?, content = ?, status = ?, image = ?, slug = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $content, $status, $image, $slug, $post_id]);
                    $_SESSION['success'] = "Post updated successfully.";
                } else {
                    // Create new post
                    $stmt = $pdo->prepare("
                        INSERT INTO posts (title, content, status, image, slug, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$title, $content, $status, $image, $slug]);
                    $_SESSION['success'] = "Post created successfully.";
                }
                
                header('Location: posts.php');
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error saving post: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post['id'] ? 'Edit' : 'Create'; ?> Post | Fusion Digital Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/7nib2gx5502dz72slr0z5vj1g84e3c348u2jy991yf8wmzd6/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
                            <a href="posts.php" class="border-teal-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Posts
                            </a>
                            <a href="comments.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Comments
                            </a>
                            <a href="newsletters.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Newsletters
                            </a>
                            <a href="subscribers.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
                    <h1 class="text-2xl font-semibold text-gray-900">
                        <?php echo $post['id'] ? 'Edit Post' : 'Create New Post'; ?>
                    </h1>
                    <a href="posts.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Back to Posts
                    </a>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Post Form -->
            <div class="mt-8 bg-white shadow sm:rounded-lg">
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6 p-6">
                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" id="title" required
                               value="<?php echo htmlspecialchars($post['title']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                        <textarea name="content" id="content" rows="15"><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700">Featured Image</label>
                        <?php if ($post['image']): ?>
                            <div class="mt-2">
                                <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" 
                                     alt="Current featured image" 
                                     class="h-32 w-32 object-cover rounded">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" id="image" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-teal-50 file:text-teal-700
                                      hover:file:bg-teal-100">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700">
                            <?php echo $post['id'] ? 'Update Post' : 'Create Post'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500,
            images_upload_url: 'upload.php',
            automatic_uploads: true,
            file_picker_types: 'image',
            images_reuse_filename: true,
            images_upload_handler: function (blobInfo, success, failure) {
                var xhr, formData;
                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', 'upload.php');
                xhr.onload = function() {
                    var json;
                    if (xhr.status != 200) {
                        failure('HTTP Error: ' + xhr.status);
                        return;
                    }
                    json = JSON.parse(xhr.responseText);
                    if (!json || typeof json.location != 'string') {
                        failure('Invalid JSON: ' + xhr.responseText);
                        return;
                    }
                    success(json.location);
                };
                formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            }
        });
    </script>
</body>
</html> 