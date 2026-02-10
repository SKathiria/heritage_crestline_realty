<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: user_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// DB Connection - use the existing connection from db_config.php
require_once 'db_config.php';

// Verify connection exists
if (!isset($pdo)) {
    die("Database connection not established");
}

// Fetch user details - REMOVED profile_image from query
$userStmt = $pdo->prepare("SELECT customer_id, name, email, phone FROM customers WHERE customer_id = ?");
$userStmt->execute([$customer_id]);
$user = $userStmt->fetch();
// Fetch stats
$statsStmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM bookings WHERE customer_id = ?) as total_bookings,
        (SELECT COUNT(*) FROM favorites WHERE customer_id = ?) as total_favorites,
        (SELECT COUNT(*) FROM inquiries WHERE customer_id = ?) as total_inquiries,
        (SELECT SUM(p.price) FROM bookings b JOIN properties p ON b.property_id = p.property_id WHERE b.customer_id = ? AND b.status = 'completed') as portfolio_value
");
$statsStmt->execute([$customer_id, $customer_id, $customer_id, $customer_id]);
$stats = $statsStmt->fetch();

// Fetch recent bookings - GET FIRST IMAGE
// Fetch recent bookings - Alternative version
$bookingsStmt = $pdo->prepare("
    SELECT b.*, 
           p.title, 
           p.location, 
           p.price, 
           (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.property_id LIMIT 1) as image_path,
           pt.type_name 
    FROM bookings b
    JOIN properties p ON b.property_id = p.property_id
    JOIN property_types pt ON p.type_id = pt.type_id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
    LIMIT 3
");
$bookingsStmt->execute([$customer_id]);
$recentBookings = $bookingsStmt->fetchAll();

// Fetch recent favorites - Alternative version
// Fetch recent favorites with bedrooms and bathrooms
$recentFavorites = [];
try {
    $favoritesStmt = $pdo->prepare("
        SELECT f.*, 
               p.title, 
               p.location, 
               p.price, 
               p.bedrooms,
               p.bathrooms,
               (SELECT pi.image_path FROM property_images pi WHERE pi.property_id = p.property_id LIMIT 1) as image_path,
               pt.type_name 
        FROM favorites f
        JOIN properties p ON f.property_id = p.property_id
        JOIN property_types pt ON p.type_id = pt.type_id
        WHERE f.customer_id = ?
        ORDER BY f.favorited_at DESC
        LIMIT 3
    ");
    
    // This is where you add the try-catch block:
    try {
        $favoritesStmt->execute([$customer_id]);
        $recentFavorites = $favoritesStmt->fetchAll();
        
        // Debugging - remove in production
        // error_log(print_r($recentFavorites, true));
    } catch (PDOException $e) {
        error_log("Error fetching favorites: " . $e->getMessage());
        $recentFavorites = [];
    }
    
} catch (PDOException $e) {
    error_log("Error preparing favorites query: " . $e->getMessage());
}

// Fetch recent inquiries
$inquiriesStmt = $pdo->prepare("
    SELECT i.*, p.title, p.location 
    FROM inquiries i
    JOIN properties p ON i.property_id = p.property_id
    WHERE i.customer_id = ?
    ORDER BY i.created_at DESC
    LIMIT 3
");
$inquiriesStmt->execute([$customer_id]);
$recentInquiries = $inquiriesStmt->fetchAll();

// Portfolio value history for chart
$portfolioHistoryStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(b.created_at, '%b') as month,
        SUM(p.price) as value
    FROM bookings b
    JOIN properties p ON b.property_id = p.property_id
    WHERE b.customer_id = ? AND b.status = 'completed'
    GROUP BY MONTH(b.created_at)
    ORDER BY MONTH(b.created_at)
    LIMIT 6
");
$portfolioHistoryStmt->execute([$customer_id]);
$portfolioHistory = $portfolioHistoryStmt->fetchAll();

// Prepare chart data
$chartLabels = [];
$chartData = [];
foreach ($portfolioHistory as $item) {
    $chartLabels[] = $item['month'];
    $chartData[] = $item['value'];
}

// If no history, show demo data
if (empty($chartLabels)) {
    $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chartData = [120000, 190000, 150000, 200000, 180000, $stats['portfolio_value'] ?? 0];
}
?>

<?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Heritage Crestline</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                Heritage<span>Crestline</span>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="user_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="properties.php"><i class="fas fa-search"></i> Browse Properties</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
            </ul>
            
            <div class="user-profile-sidebar">
                <div class="user-avatar-sidebar">
            <?php if (!empty($user['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Image">
             <?php else: ?>
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
            <?php endif; ?>
            </div>
                <div class="user-info-sidebar">
                    <h4><?= htmlspecialchars($user['name']) ?></h4>
                </div>
            </div>
            
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-header">
    <div class="dashboard-title">
        <h1>Welcome</h1>
        <p>Your luxury property dashboard</p>
    </div>
    <div class="dashboard-actions">
    </div>
</div>


            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Properties</h3>
                    <div class="stat-value"><?= $stats['total_bookings'] ?></div>
                    <div class="stat-change up"><i class="fas fa-arrow-up"></i> 12% from last month</div>
                    <i class="fas fa-home stat-icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Favorites</h3>
                    <div class="stat-value"><?= $stats['total_favorites'] ?></div>
                    <div class="stat-change up"><i class="fas fa-arrow-up"></i> 5% from last month</div>
                    <i class="fas fa-heart stat-icon"></i>
                </div>
                <div class="stat-card">
                    <h3>Inquiries</h3>
                    <div class="stat-value"><?= $stats['total_inquiries'] ?></div>
                    <div class="stat-change down"><i class="fas fa-arrow-down"></i> 3% from last month</div>
                    <i class="fas fa-envelope stat-icon"></i>
                </div>

            <!-- Main Content Grid -->
            <div class="content-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Recent Bookings -->
                    <section class="activity-section">
                        <div class="section-header">
                            <h2>Recent Bookings</h2>
                            <a href="bookings.php">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        
                        <?php if (!empty($recentBookings)): ?>
                            <div class="activity-cards">
                                <?php foreach ($recentBookings as $booking): ?>
                                    <div class="activity-card">
                                        <div class="activity-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="activity-details">
                                            <h4><?= htmlspecialchars($booking['title']) ?></h4>
                                            <p><?= htmlspecialchars($booking['type_name']) ?> in <?= htmlspecialchars($booking['location']) ?></p>
                                            <div class="activity-meta">
                                                <span>Booked on <?= date('M d, Y', strtotime($booking['created_at'])) ?></span>
                                                <span class="status-badge status-<?= htmlspecialchars($booking['status']) ?>">
                                                    <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                                </span>
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
                    </section>

                    <!-- Favorite Properties Section -->
<section class="activity-section">
    <div class="section-header">
        <h2>Favorite Properties</h2>
        <a href="favorites.php">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <?php if (!empty($recentFavorites)): ?>
        <div class="activity-cards">
            <?php foreach ($recentFavorites as $favorite): ?>
                <div class="property-card">
                    <div class="property-image" style="background-image: url('<?= !empty($favorite['image_path']) ? htmlspecialchars($favorite['image_path']) : 'images/default-property.jpg' ?>');">
                        <div class="property-price">$<?= number_format($favorite['price']) ?></div>
                    </div>
                    <div class="property-content">
                        <h3 class="property-title"><?= htmlspecialchars($favorite['title']) ?></h3>
                        <div class="property-location">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($favorite['location']) ?>
                        </div>
                        <div class="property-features">
                            <div class="property-feature">
                                <i class="fas fa-bed"></i>
                                <span><?= isset($favorite['bedrooms']) && $favorite['bedrooms'] !== null ? $favorite['bedrooms'] : 'N/A' ?> Beds</span>
                            </div>
                            <div class="property-feature">
                                <i class="fas fa-bath"></i>
                                <span><?= isset($favorite['bathrooms']) && $favorite['bathrooms'] !== null ? $favorite['bathrooms'] : 'N/A' ?> Baths</span>
                            </div>
                            <div class="property-feature">
                                <i class="fas fa-vector-square"></i>
                                <span><?= htmlspecialchars($favorite['type_name'] ?? 'Property') ?></span>
                            </div>
                        </div>
                        <div class="property-actions">
                            <a href="property.php?id=<?= $favorite['property_id'] ?>" class="btn-view">View</a>
                            <a href="favorites.php?action=remove&property_id=<?= $favorite['property_id'] ?>" class="btn-cancel">Remove</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="far fa-heart"></i>
            <h3>No Favorites Yet</h3>
            <p>You haven't saved any properties to your favorites yet.</p>
            <a href="properties.php">Browse Properties</a>
        </div>
    <?php endif; ?>
</section>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <!-- Recent Inquiries -->
                    <section class="activity-section">
                        <div class="section-header">
                            <h2>Recent Inquiries</h2>
                            <a href="inquiries.php">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        
                        <?php if (!empty($recentInquiries)): ?>
                            <div class="activity-cards">
                                <?php foreach ($recentInquiries as $inquiry): ?>
                                    <div class="activity-card">
                                        <div class="activity-icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="activity-details">
                                            <h4><?= htmlspecialchars($inquiry['title']) ?></h4>
                                            <p><?= !empty($inquiry['message']) ? htmlspecialchars(substr($inquiry['message'], 0, 50)) . '...' : 'No message provided' ?></p>
                                            <div class="activity-meta">
                                                <span><?= date('M d, Y', strtotime($inquiry['created_at'])) ?></span>
                                                <span class="status-badge status-<?= htmlspecialchars($inquiry['status']) ?>">
                                                    <?= ucfirst(htmlspecialchars($inquiry['status'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="far fa-question-circle"></i>
                                <h3>No Inquiries Yet</h3>
                                <p>You haven't made any property inquiries yet.</p>
                                <a href="properties.php">Browse Properties</a>
                            </div>
                        <?php endif; ?>
                    </section>

                </div>
            </div>
        </main>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Initialize Chart
        const ctx = document.getElementById('portfolioChart').getContext('2d');
        const portfolioChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Portfolio Value',
                    data: <?= json_encode($chartData) ?>,
                    borderColor: '#C9A227',
                    backgroundColor: 'rgba(201, 162, 39, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Animation for cards
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.activity-card, .property-card');
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