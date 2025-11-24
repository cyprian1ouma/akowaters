<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="dashboard.php" class="text-xl font-bold">
                        <span class="text-teal-800">A</span><span class="text-orange-800">K</span><span class="text-teal-800">O</span>
                        <span class="text-gray-600 text-sm">Admin</span>
                    </a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                <!--<a href="dashboard.php" 
                       class="<?php echo $current_page === 'dashboard.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="posts.php" 
                       class="<?php echo $current_page === 'posts.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Posts
                    </a>
                    <a href="comments.php" 
                       class="<?php echo $current_page === 'comments.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Comments
                    </a>
                    <a href="newsletters.php" 
                       class="<?php echo $current_page === 'newsletters.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Newsletters
                    </a>
                    <a href="subscribers.php" 
                       class="<?php echo $current_page === 'subscribers.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Subscribers
                    </a>-->
                    <a href="projects.php" 
                       class="<?php echo $current_page === 'projects.php' || $current_page === 'create_project.php' || $current_page === 'edit_project.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Projects
                    </a>
                    <a href="galleries.php" 
                       class="<?php echo $current_page === 'galleries.php' || $current_page === 'create_gallery_item.php' ? 'border-teal-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Gallery
                    </a>
                </div>
            </div>
            <div class="flex items-center">
                <div class="ml-3 relative">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700 text-sm">
                            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                        </span>
                        <a href="logout.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="sm:hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="dashboard.php" 
               class="<?php echo $current_page === 'dashboard.php' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Dashboard
            </a>
            <a href="posts.php" 
               class="<?php echo $current_page === 'posts.php' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Posts
            </a>
            <a href="comments.php" 
               class="<?php echo $current_page === 'comments.php' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Comments
            </a>
            <a href="newsletters.php" 
               class="<?php echo $current_page === 'newsletters.php' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Newsletters
            </a>
            <a href="subscribers.php" 
               class="<?php echo $current_page === 'subscribers.php' ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Subscribers
            </a>
            <a href="projects.php" 
               class="<?php echo in_array($current_page, ['projects.php', 'create_project.php', 'edit_project.php']) ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Projects
            </a>
            <a href="galleries.php" 
               class="<?php echo in_array($current_page, ['galleries.php', 'create_gallery_item.php']) ? 'bg-teal-50 border-teal-500 text-teal-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Gallery
            </a>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script> 