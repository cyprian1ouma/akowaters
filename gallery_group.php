<?php
require_once 'admin/config/database.php';

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0; // for standalone single items

if ($item_id > 0) {
    // show single item
    try {
        $stmt = $pdo->prepare("SELECT * FROM gallery_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        if (!$item) header('Location: gallery.php');
    } catch (PDOException $e) {
        header('Location: gallery.php');
    }
    $images = [$item];
    $title = $item['title'] ?: 'Gallery Image';
    $caption = $item['caption'] ?: '';
} else {
    if ($group_id <= 0) {
        header('Location: gallery.php');
        exit;
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM gallery_groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $group = $stmt->fetch();
        if (!$group) header('Location: gallery.php');

        $stmt = $pdo->prepare("SELECT * FROM gallery_items WHERE group_id = ? ORDER BY created_at ASC");
        $stmt->execute([$group_id]);
        $images = $stmt->fetchAll();
        $title = $group['title'] ?: 'Gallery';
        $caption = $group['caption'] ?: '';
    } catch (PDOException $e) {
        header('Location: gallery.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ako</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/logo3.png" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Topbar Start -->
    <div class="container-fluid bg-dark p-0">
        <div class="row gx-0 d-none d-lg-flex">
            <div class="col-lg-7 px-5 text-start">
                <div class="h-100 d-inline-flex align-items-center me-4">
                    <small class="fa fa-map-marker-alt text-primary me-2"></small>
                    <small>111-80100 MOMBASA,KENYA.</small>
                </div>
                <div class="h-100 d-inline-flex align-items-center">
                    <small class="far fa-clock text-primary me-2"></small>
                    <small>Mon - Fri : 09.00 AM - 09.00 PM</small>
                </div>
            </div>
            <div class="col-lg-5 px-5 text-end">
                <div class="h-100 d-inline-flex align-items-center me-4">
                    <small class="fa fa-phone-alt text-primary me-2"></small>
                    <small>+254 718897204</small>
                </div>
                <div class="h-100 d-inline-flex align-items-center mx-n2">
                    <a class="btn btn-square btn-link rounded-0 border-0 border-end border-secondary" href="./admin/login.php"><i class="fas fa-user-lock"></i></a>
                    <a class="btn btn-square btn-link rounded-0 border-0 border-end border-secondary" href=""><i class="fab fa-facebook-f"></i></a>
                    <a class="btn btn-square btn-link rounded-0 border-0 border-end border-secondary" href=""><i class="fab fa-twitter"></i></a>
                    <a class="btn btn-square btn-link rounded-0 border-0 border-end border-secondary" href=""><i class="fab fa-linkedin-in"></i></a>
                    <a class="btn btn-square btn-link rounded-0" href=""><i class="fab fa-instagram"></i></a>
                    
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
        <a href="index.php" class="navbar-brand d-flex align-items-center border-end px-4 px-lg-5">
            <!-- Logo image -->
            <img src="./img/logo3.png" style="height: 120px;width: 140px;" alt="Ako Logo" class="h-10 w-11">
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                <a href="index.php" class="nav-item nav-link">Home</a>
                <a href="about.php" class="nav-item nav-link">About</a>
                <a href="service.php" class="nav-item nav-link">Service</a>
                <a href="project.php" class="nav-item nav-link">Project</a>
                <a href="gallery.php" class="nav-item nav-link active">Gallery</a>
                <a href="contact.php" class="nav-item nav-link">Contact</a>
            </div>
            <!-- <a href="" class="btn btn-primary rounded-0 py-4 px-lg-5 d-none d-lg-block">Get A Quote<i class="fa fa-arrow-right ms-3"></i></a> -->
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5">
        <div class="container py-5">
            <h1 class="display-3 text-white mb-3 animated slideInDown">Gallery</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="text-white" href="#">Home</a></li>
                    <li class="breadcrumb-item"><a class="text-white" href="#">Pages</a></li>
                    <li class="breadcrumb-item text-white active" aria-current="page">Gallery</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <section class="pt-5 pb-3 bg-white">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="h3 fw-bold mb-2"><?php echo htmlspecialchars($title); ?></h1>
                    <p class="text-muted"><?php echo htmlspecialchars($caption); ?></p>
                </div>
                <a href="gallery.php" class="btn btn-dark">Back to Gallery</a>
            </div>
        </div>
    </section>

    <section class="py-4 bg-white">
        <div class="container">
            <div class="row g-4">
                <?php foreach ($images as $img): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="rounded overflow-hidden shadow-sm">
                        <img src="admin/uploads/<?php echo htmlspecialchars($img['image']); ?>" alt="" class="w-100 h-80 object-contain bg-gray-100">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-body footer mt-5 pt-5">
        <div class="container py-5">
            <div class="row g-5">
                <!-- Logo and Description Column -->
                <div class="col-lg-3 col-md-6">
                <img src="./img/logo4.png" alt="Ako Water and Energy Solutions" class="mb-3"  style="height: 120px;width: 140px;">
                    <p class="text-light small">
                        Providing sustainable water and energy solutions for a better tomorrow. Your trusted partner in hydrogeological services and renewable energy systems.
                    </p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-square btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-square btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-square btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-4">Address</h5>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>111-80100 MOMBASA KENYA.</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+254 718897204</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>Akowatersolutions@gmail.com</p>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-4">Quick Links</h5>
                    <a class="btn btn-link d-block text-start mb-2" href="./about.php">About Us</a>
                    <a class="btn btn-link d-block text-start mb-2" href="./contact.php">Contact Us</a>
                    <a class="btn btn-link d-block text-start mb-2" href="./service.php">Our Services</a>
                    <a class="btn btn-link d-block text-start mb-2" href="./contact.php">Terms & Condition</a>
                    <a class="btn btn-link d-block text-start mb-2" href="./team.php">Support</a>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <h5 class="text-white mb-4">Newsletter</h5>
                    <p class="small">Subscribe to receive our latest news, updates, and special offers.</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <input class="form-control border-0 w-100 py-2 ps-3 pe-5 small" type="text" placeholder="Your email">
                        <button type="button" class="btn btn-primary py-1 px-3 small position-absolute top-0 end-0 mt-1 me-1">Sign Up</button>
                    </div>
                </div>                
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a href="#" class="text-white">Ako Water and Energy Solutions LTD</a>, All Right Reserved.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        Designed By Ako Water and Energy Solutions LTD
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>
    <a href="https://wa.me/254718897204?text=Hi,%20welcome%20to%20our%20organization.%20How%20can%20I%20help%20you%3F" 
        target="_blank" 
        class="whatsapp-float">
        <i class="bi bi-whatsapp"></i>
    </a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/isotope/isotope.pkgd.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
