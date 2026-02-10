<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection - use the one from db_config.php
// Database Connection - use the one from db_config.php
require_once 'db_config.php';

// Fetch Featured Properties - Fixed column name from featured to is_featured
$stmt = $pdo->prepare("SELECT p.*, pt.type_name FROM properties p JOIN property_types pt ON p.type_id = pt.type_id WHERE p.is_featured = 1 LIMIT 6");
$stmt->execute();
$featuredProperties = $stmt->fetchAll();

// Fetch all property types for filter
$types = $pdo->query("SELECT * FROM property_types")->fetchAll();

// Check if user is logged in
$isUserLoggedIn = isset($_SESSION['customer_id']);
$isAdminLoggedIn = isset($_SESSION['admin_id']);

// Get user/admin name if logged in
$userName = '';
if ($isUserLoggedIn) {
    $userStmt = $pdo->prepare("SELECT name FROM customers WHERE customer_id = ?");
    $userStmt->execute([$_SESSION['customer_id']]);
    $user = $userStmt->fetch();
    $userName = $user['name'] ?? '';
} elseif ($isAdminLoggedIn) {
    $adminStmt = $pdo->prepare("SELECT name FROM admins WHERE admin_id = ?");
    $adminStmt->execute([$_SESSION['admin_id']]);
    $admin = $adminStmt->fetch();
    $userName = $admin['name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heritage Crestline Realty | Luxury Properties</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="main-nav">
        <a href="index.php" class="logo">
            <i class="fas fa-building"></i> Heritage Crestline
        </a>
        <button class="mobile-menu-toggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="properties.php">Properties</a></li>
            <li><a href="contact.php">Contact</a></li>
            
            <?php if ($isUserLoggedIn || $isAdminLoggedIn): ?>
                <li class="user-dropdown">
                    <div class="user-greeting">
                        <div class="user-avatar">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                        <span>Welcome, <?= htmlspecialchars($userName) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="user-dropdown-content">
                        <?php if ($isUserLoggedIn): ?>
                            <a href="user_dashboard.php"><i class="fas fa-user"></i> Dashboard</a>
                            <a href="edit_profile.php"><i class="fas fa-cog"></i> Profile Settings</a>
                            <a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                            <a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a>
                        <?php elseif ($isAdminLoggedIn): ?>
                            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                            <a href="manage_properties.php"><i class="fas fa-home"></i> Manage Properties</a>
                            <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="user_login.php">Login</a></li>
                <li><a href="admin_login.php">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <script>
    // Enhanced debugging
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Debug] DOM ready');
        
        const toggler = document.querySelector('.navbar-toggler');
        const menu = document.querySelector('#navbarNav');
        
        if (!toggler) console.error('[Debug] Toggler button not found');
        if (!menu) console.error('[Debug] Menu element not found');
        
        // Fallback click handler
        toggler?.addEventListener('click', function() {
            console.log('[Debug] Toggler clicked');
            menu?.classList.toggle('show');
        });
        
        // Close menu when clicking links (mobile)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    menu?.classList.remove('show');
                }
            });
        });
    });
    </script>
