<?php
session_start();
require_once 'db_config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Fetch stats
$totalProperties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$totalInquiries = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$totalBookings  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalViewings  = $pdo->query("SELECT COUNT(*) FROM viewings")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-brand">Heritage<span>Admin</span></div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="admin_properties.php"><i class="fas fa-building"></i> Manage Properties</a></li>
            <li><a href="admin_viewings.php"><i class="fas fa-calendar-alt"></i> Viewings</a></li>
            <li><a href="admin_inquiries.php"><i class="fas fa-envelope-open-text"></i> Inquiries</a></li>
            <li><a href="admin_appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
            <li><a href="admin_profile.php"><i class="fas fa-user-cog"></i> My Profile</a></li>
            <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <div class="user-profile-sidebar">
            <div class="user-avatar-sidebar"><?= strtoupper($adminName[0]) ?></div>
            <div class="user-info-sidebar">
                <h4><?= htmlspecialchars($adminName) ?></h4>
                <p>Administrator</p>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1>Welcome, <?= htmlspecialchars($adminName) ?> 👋</h1>
                <p>Here is a quick overview of your site’s activity.</p>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Properties</h3>
                <div class="stat-value"><?= $totalProperties ?></div>
                <div class="stat-icon"><i class="fas fa-home"></i></div>
            </div>

            <div class="stat-card">
                <h3>Total Inquiries</h3>
                <div class="stat-value"><?= $totalInquiries ?></div>
                <div class="stat-icon"><i class="fas fa-envelope"></i></div>
            </div>

            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="stat-value"><?= $totalBookings ?></div>
                <div class="stat-icon"><i class="fas fa-book"></i></div>
            </div>

            <div class="stat-card">
                <h3>Total Viewings</h3>
                <div class="stat-value"><?= $totalViewings ?></div>
                <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            </div>
        </div>
    </main>
</div>

<!-- FontAwesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
