<?php
session_start();
require_once 'db_config.php';

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: user_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $property_id = $_POST['property_id'] ?? null;
    $message = $_POST['message'] ?? '';
    $booking_date = $_POST['booking_date'] ?? null;

    if (!$property_id || !$booking_date) {
        $_SESSION['error'] = "Missing required information for booking.";
        header("Location: property.php?id=$property_id");
        exit;
    }

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Get admin_id for the property
        $stmt = $pdo->prepare("SELECT admin_id FROM properties WHERE property_id = ?");
        $stmt->execute([$property_id]);
        $admin = $stmt->fetch();
        $admin_id = $admin ? $admin['admin_id'] : null;

        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bookings (customer_id, property_id, admin_id, booking_date, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$customer_id, $property_id, $admin_id, $booking_date, $message]);

        $_SESSION['success'] = "Booking submitted successfully!";
        header("Location: bookings.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting booking: " . $e->getMessage();
        header("Location: property.php?id=$property_id");
        exit;
    }
}

// Handle booking cancellation
if (isset($_GET['cancel'])) {
    $booking_id = $_GET['cancel'];
    
    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Verify the booking belongs to the current user
        $stmt = $pdo->prepare("SELECT customer_id FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking || $booking['customer_id'] != $customer_id) {
            $_SESSION['error'] = "Invalid booking or unauthorized action.";
            header("Location: bookings.php");
            exit;
        }

        // Update booking status to cancelled
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        $_SESSION['success'] = "Booking cancelled successfully.";
        header("Location: bookings.php?success=cancelled");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error cancelling booking: " . $e->getMessage();
        header("Location: bookings.php");
        exit;
    }
}

// Fetch all bookings for the current user
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.title, 
               p.location, 
               p.price, 
               p.bedrooms,
               p.bathrooms,
               (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.property_id LIMIT 1) as image_path,
               pt.type_name 
        FROM bookings b
        JOIN properties p ON b.property_id = p.property_id
        JOIN property_types pt ON p.type_id = pt.type_id
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $error = "Error fetching bookings: " . $e->getMessage();
}

// Fetch user details for sidebar
try {
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = ['name' => 'User'];
}

require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Heritage Realty</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                Heritage<span>Realty</span>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="properties.php"><i class="fas fa-search"></i> Browse Properties</a></li>
                <li><a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-profile-sidebar">
                    <div class="user-avatar-sidebar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div class="user-info-sidebar">
                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                    </div>
                </div>
                <a href="logout.php" style="display: block; text-align: center; margin-top: 1rem; color: white; opacity: 0.7; font-size: 0.9rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="page-header">
                <div>
                    <h1>My Bookings</h1>
                    <p>View and manage your property bookings</p>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'cancelled'): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> Your booking has been successfully cancelled.
                </div>
            <?php endif; ?>

            <?php if (!empty($bookings)): ?>
                <div class="booking-cards">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-image" style="background-image: url('<?= !empty($booking['image_path']) ? htmlspecialchars($booking['image_path']) : 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60' ?>');">
                                <div class="booking-price">$<?= number_format($booking['price']) ?></div>
                            </div>
                            <div class="booking-details">
                                <div class="booking-header">
                                    <div>
                                        <h2 class="booking-title"><?= htmlspecialchars($booking['title']) ?></h2>
                                        <p class="booking-type"><?= htmlspecialchars($booking['type_name']) ?></p>
                                    </div>
                                    <span class="status-badge status-<?= htmlspecialchars($booking['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                    </span>
                                </div>
                                
                                <div class="booking-meta">
                                    <div class="booking-meta-item">
                                        <i class="fas fa-bed"></i> <?= htmlspecialchars($booking['bedrooms']) ?> Beds
                                    </div>
                                    <div class="booking-meta-item">
                                        <i class="fas fa-bath"></i> <?= htmlspecialchars($booking['bathrooms']) ?> Baths
                                    </div>
                                    <div class="booking-meta-item">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($booking['location']) ?>
                                    </div>
                                </div>
                                
                                <div class="booking-info">
                                    <div class="booking-info-item">
                                        <span class="booking-info-label">Booking Date:</span>
                                        <span class="booking-info-value"><?= date('F j, Y', strtotime($booking['created_at'])) ?></span>
                                    </div>
                                    <div class="booking-info-item">
                                        <span class="booking-info-label">Preferred Date:</span>
                                        <span class="booking-info-value"><?= date('F j, Y', strtotime($booking['booking_date'])) ?></span>
                                    </div>
                                    <div class="booking-info-item">
                                        <span class="booking-info-label">Reference #:</span>
                                        <span class="booking-info-value"><?= htmlspecialchars($booking['booking_id']) ?></span>
                                    </div>
                                    <?php if (!empty($booking['message'])): ?>
                                    <div class="booking-info-item">
                                        <span class="booking-info-label">Your Message:</span>
                                        <span class="booking-info-value"><?= htmlspecialchars($booking['message']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="booking-actions">
                                    <a href="property.php?id=<?= $booking['property_id'] ?>" class="btn btn-primary">
                                        View Property
                                    </a>
                                    <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                        <a href="bookings.php?cancel=<?= $booking['booking_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                            Cancel Booking
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>Cancelled</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-calendar-alt"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't booked any property viewings yet.</p>
                    <a href="properties.php">Browse Properties</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Simple animation for booking cards
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.booking-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.transitionDelay = `${index * 0.1}s`;
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>