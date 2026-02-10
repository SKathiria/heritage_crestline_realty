<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: user_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// DB Connection
require_once 'db_config.php';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Fetch user details
$userStmt = $pdo->prepare("SELECT name, email FROM customers WHERE customer_id = ?");
$userStmt->execute([$customer_id]);
$user = $userStmt->fetch();

// Handle favorite actions
if (isset($_GET['action']) && isset($_GET['property_id'])) {
    $property_id = (int)$_GET['property_id'];
    $action = $_GET['action'];
    
    // Verify property exists
    $propertyStmt = $pdo->prepare("SELECT 1 FROM properties WHERE property_id = ?");
    $propertyStmt->execute([$property_id]);
    
    if ($propertyStmt->rowCount() > 0) {
        if ($action === 'add') {
            // Check if already favorited
            $checkStmt = $pdo->prepare("SELECT 1 FROM favorites WHERE customer_id = ? AND property_id = ?");
            $checkStmt->execute([$customer_id, $property_id]);
            
            if ($checkStmt->rowCount() === 0) {
                $insertStmt = $pdo->prepare("INSERT INTO favorites (customer_id, property_id, favorited_at) VALUES (?, ?, NOW())");
                $insertStmt->execute([$customer_id, $property_id]);
            }
        } elseif ($action === 'remove') {
            $deleteStmt = $pdo->prepare("DELETE FROM favorites WHERE customer_id = ? AND property_id = ?");
            $deleteStmt->execute([$customer_id, $property_id]);
        }
        
        // Redirect back to previous page if available
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'properties.php';
        header("Location: $redirect");
        exit;
    }
}

// Handle bulk removal
if (isset($_POST['remove_favorites']) && isset($_POST['favorites'])) {
    $placeholders = implode(',', array_fill(0, count($_POST['favorites']), '?'));
    
    $deleteStmt = $pdo->prepare("DELETE FROM favorites WHERE customer_id = ? AND fav_id IN ($placeholders)");
    $deleteStmt->execute(array_merge([$customer_id], $_POST['favorites']));
    
    header("Location: favorites.php?success=removed");
    exit;
}

// Fetch all favorites with favorited_at
$favStmt = $pdo->prepare("SELECT f.fav_id, f.favorited_at, p.property_id, p.title, p.price, p.location, p.bedrooms, p.bathrooms, pt.type_name, 
                         (SELECT image_path FROM property_images WHERE property_id = p.property_id LIMIT 1) as image_path
                         FROM favorites f 
                         JOIN properties p ON f.property_id = p.property_id 
                         JOIN property_types pt ON p.type_id = pt.type_id
                         WHERE f.customer_id = ? 
                         ORDER BY f.favorited_at DESC");
$favStmt->execute([$customer_id]);
$favorites = $favStmt->fetchAll();
?>

<?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites | Heritage Realty</title>

    <link rel="stylesheet" href="styles.css">

</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar - Same as user_dashboard.php -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-brand">
                Heritage<span>Realty</span>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="properties.php"><i class="fas fa-search"></i> Browse Properties</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="favorites.php" class="active"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-profile-sidebar">
                    <div class="user-avatar-sidebar">
                        <?= isset($user['name']) ? strtoupper(substr($user['name'], 0, 1)) : 'U' ?>
                    </div>
                    <div class="user-info-sidebar">
                        <h4><?= isset($user['name']) ? htmlspecialchars($user['name']) : 'User' ?></h4>
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
                    <h1>My Favorites</h1>
                    <p>Your saved luxury properties</p>
                </div>
                <a href="user_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'removed'): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> Selected properties have been removed from your favorites.
                </div>
            <?php endif; ?>

            <?php if ($favorites): ?>
                <form method="POST" id="favorites-form">
                    <div class="bulk-actions">
                        <div class="selected-count">
                            <span id="selected-count">0</span> selected
                        </div>
                        <button type="submit" name="remove_favorites" class="bulk-remove-btn" disabled>
                            <i class="fas fa-trash-alt"></i> Remove Selected
                        </button>
                    </div>

                    <div class="favorites-grid">
                        <?php foreach ($favorites as $favorite): ?>
                            <div class="favorite-card">
                                <div class="favorite-image" style="background-image: url('<?= isset($favorite['image_path']) ? htmlspecialchars($favorite['image_path']) : 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60' ?>');">
                                    <div class="favorite-price">$<?= isset($favorite['price']) ? number_format($favorite['price']) : '0' ?></div>
                                    <input type="checkbox" class="favorite-checkbox" name="favorites[]" value="<?= $favorite['fav_id'] ?>">
                                </div>
                                <div class="favorite-content">
                                    <h3 class="favorite-title"><?= isset($favorite['title']) ? htmlspecialchars($favorite['title']) : 'Untitled Property' ?></h3>
                                    <div class="favorite-meta">
                                        <span class="favorite-type"><?= isset($favorite['type_name']) ? htmlspecialchars($favorite['type_name']) : 'Unknown Type' ?></span>
                                        <span><?= isset($favorite['favorited_at']) ? date('M d, Y', strtotime($favorite['favorited_at'])) : 'Date unknown' ?></span>
                                    </div>
                                    <div class="favorite-location">
                                        <i class="fas fa-map-marker-alt"></i> <?= isset($favorite['location']) ? htmlspecialchars($favorite['location']) : 'Location unknown' ?>
                                    </div>
                                    <div class="favorite-features">
                                        <div class="favorite-feature">
                                            <i class="fas fa-bed"></i>
                                            <span><?= isset($favorite['bedrooms']) ? htmlspecialchars($favorite['bedrooms']) : '0' ?> Beds</span>
                                        </div>
                                        <div class="favorite-feature">
                                            <i class="fas fa-bath"></i>
                                            <span><?= isset($favorite['bathrooms']) ? htmlspecialchars($favorite['bathrooms']) : '0' ?> Baths</span>
                                        </div>
                                        <div class="favorite-feature">
                                            <i class="fas fa-ruler-combined"></i>
                                            <span>Sq Ft</span>
                                        </div>
                                    </div>
                                    <div class="favorite-actions">
                                        <a href="property.php?id=<?= $favorite['property_id'] ?>" class="btn btn-view">
                                            View Property
                                        </a>
                                        <a href="favorites.php?action=remove&property_id=<?= $favorite['property_id'] ?>" class="btn btn-remove" onclick="return confirm('Remove this property from your favorites?');">
                                            Remove
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-heart"></i>
                    <h3>No Favorites Yet</h3>
                    <p>You haven't saved any properties to your favorites yet.</p>
                    <a href="properties.php">Browse Properties</a>
                </div>
            <?php endif; ?>
        </main>
    </div>


    <script>
        // Update selected count and enable/disable bulk remove button
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('favorites-form');
            const checkboxes = form.querySelectorAll('.favorite-checkbox');
            const selectedCount = document.getElementById('selected-count');
            const bulkRemoveBtn = form.querySelector('.bulk-remove-btn');
            
            function updateSelection() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked).length;
                selectedCount.textContent = selected;
                bulkRemoveBtn.disabled = selected === 0;
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });
            
            // Animation for cards
            const cards = document.querySelectorAll('.favorite-card');
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
        });// Your existing JavaScript remains unchanged
    </script>
</body>
</html>