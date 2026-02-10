<?php
// Start session at the very beginning
session_start();

// DB Connection
require_once 'db_config.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle favorite action if requested
if (isset($_GET['favorite_action']) && isset($_GET['property_id'])) {
    if (!isset($_SESSION['customer_id'])) {
        // Store the current URL in session to redirect back after login
        $_SESSION['redirect_url'] = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: user_login.php");
        exit;
    }
    
    $customer_id = $_SESSION['customer_id'];
    $property_id = (int)$_GET['property_id'];
    $action = $_GET['favorite_action'];
    
    // Verify property exists
    $propertyStmt = $pdo->prepare("SELECT 1 FROM properties WHERE property_id = ?");
    $propertyStmt->execute([$property_id]);
    
    if ($propertyStmt->rowCount() > 0) {
        if ($action === 'add') {
            // Check if already favorited
            $checkStmt = $pdo->prepare("SELECT fav_id FROM favorites WHERE customer_id = ? AND property_id = ?");
            $checkStmt->execute([$customer_id, $property_id]);
            
            if ($checkStmt->rowCount() === 0) {
                $insertStmt = $pdo->prepare("INSERT INTO favorites (customer_id, property_id) VALUES (?, ?)");
                $insertStmt->execute([$customer_id, $property_id]);
            }
        } elseif ($action === 'remove') {
            $deleteStmt = $pdo->prepare("DELETE FROM favorites WHERE customer_id = ? AND property_id = ?");
            $deleteStmt->execute([$customer_id, $property_id]);
        }
        
        // Remove favorite_action and property_id from GET parameters to prevent infinite redirect
        $queryParams = $_GET;
        unset($queryParams['favorite_action']);
        unset($queryParams['property_id']);
        
        // Redirect back without the favorite parameters
        header("Location: properties.php?" . http_build_query($queryParams));
        exit;
    }
}

// Fetch property types and locations
$propertyTypes = $pdo->query("SELECT * FROM property_types")->fetchAll();
$locations = $pdo->query("SELECT DISTINCT location FROM properties ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

// Get filters
$type = $_GET['type'] ?? '';
$sale = $_GET['sale'] ?? '';
$location = $_GET['location'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

$limit = 9;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = ["p.is_available = 1"]; // Only show available properties
$params = [];

if ($type !== '') {
    $where[] = "p.type_id = :type";
    $params[':type'] = $type;
}
if ($sale !== '') {
    $where[] = "p.is_for_rent = :sale";
    $params[':sale'] = $sale;
}
if ($location !== '') {
    $where[] = "p.location = :location";
    $params[':location'] = $location;
}
if ($keyword !== '') {
    $where[] = "(p.title LIKE :keyword OR p.description LIKE :keyword)";
    $params[':keyword'] = '%' . $keyword . '%';
}
if ($minPrice !== '') {
    $where[] = "p.price >= :min_price";
    $params[':min_price'] = $minPrice;
}
if ($maxPrice !== '') {
    $where[] = "p.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}
if ($bedrooms !== '') {
    $where[] = "p.bedrooms = :bedrooms";
    $params[':bedrooms'] = $bedrooms;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$totalStmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties p $whereClause");
$totalStmt->execute($params);
$total = $totalStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Replace the existing properties query with this:
$query = "SELECT p.*, pt.type_name,
         (SELECT image_path FROM property_images pi WHERE pi.property_id = p.property_id AND is_primary = 1 LIMIT 1) AS image
         FROM properties p
         JOIN property_types pt ON p.type_id = pt.type_id
         $whereClause
         ORDER BY p.property_id DESC
         LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$properties = $stmt->fetchAll();

// Convert image paths to web URLs
foreach ($properties as &$property) {
    if (!empty($property['image'])) {
        $property['image'] = str_replace(
            'C:\\xampp\\htdocs\\', 
            'http://localhost/', 
            $property['image']
        );
    }
}
unset($property); // Break the reference

// Check favorites for logged in user
$favorites = [];
if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $favStmt = $pdo->prepare("SELECT property_id FROM favorites WHERE customer_id = ?");
    $favStmt->execute([$customer_id]);
    $favorites = $favStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Properties | Heritage Crestline Realty</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Video Hero Section -->
<section class="video-hero">
    <div class="video-container">
        <video autoplay muted loop playsinline>
            <source src="Images/real_estate.mp4" type="video/mp4">
            <!-- Fallback image if video doesn't load -->
        </video>
        <div class="video-overlay"></div>
        <div class="video-content">
            <h1>Discover Exceptional Properties</h1>
            <p>Explore our curated collection of the finest homes in the most prestigious locations worldwide.</p>
            <div class="video-cta">
                <a href="#property-grid" class="btn">View Properties</a>
                <a href="contact.php" class="btn btn-outline">Contact Agent</a>
            </div>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="filter-section">
    <h2 class="filter-title">Find Your Perfect Property</h2>
    <form class="filter-form" method="GET">
        <div class="filter-group">
            <label for="type">Property Type</label>
            <select id="type" name="type" class="filter-control">
                <option value="">All Types</option>
                <?php foreach ($propertyTypes as $pt): ?>
                    <option value="<?= $pt['type_id'] ?>" <?= $type == $pt['type_id'] ? 'selected' : '' ?>><?= $pt['type_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="sale">Listing Type</label>
            <select id="sale" name="sale" class="filter-control">
                <option value="">All Listings</option>
                <option value="1" <?= $sale === '1' ? 'selected' : '' ?>>For Rent</option>
                <option value="0" <?= $sale === '0' ? 'selected' : '' ?>>For Sale</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="location">Location</label>
            <select id="location" name="location" class="filter-control">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc ?>" <?= $location === $loc ? 'selected' : '' ?>><?= $loc ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="bedrooms">Bedrooms</label>
            <select id="bedrooms" name="bedrooms" class="filter-control">
                <option value="">Any</option>
                <option value="1" <?= $bedrooms === '1' ? 'selected' : '' ?>>1+</option>
                <option value="2" <?= $bedrooms === '2' ? 'selected' : '' ?>>2+</option>
                <option value="3" <?= $bedrooms === '3' ? 'selected' : '' ?>>3+</option>
                <option value="4" <?= $bedrooms === '4' ? 'selected' : '' ?>>4+</option>
                <option value="5" <?= $bedrooms === '5' ? 'selected' : '' ?>>5+</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="min_price">Min Price (£)</label>
            <input type="number" id="min_price" name="min_price" class="filter-control" placeholder="Min" value="<?= htmlspecialchars($minPrice) ?>">
        </div>

        <div class="filter-group">
            <label for="max_price">Max Price (£)</label>
            <input type="number" id="max_price" name="max_price" class="filter-control" placeholder="Max" value="<?= htmlspecialchars($maxPrice) ?>">
        </div>

        <div class="filter-group">
            <label for="keyword">Keyword</label>
            <input type="text" id="keyword" name="keyword" class="filter-control" placeholder="Search..." value="<?= htmlspecialchars($keyword) ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn">
                <i class="fas fa-search"></i> Search Properties
            </button>
            <a href="properties.php" class="btn btn-outline">
                <i class="fas fa-sync-alt"></i> Reset Filters
            </a>
        </div>
    </form>
</section>

<!-- Property Grid -->
<section class="property-grid" id="property-grid">
    <?php if (count($properties) > 0): ?>
        <div class="property-list">
            <?php foreach ($properties as $property): ?>
                <div class="property-card">
                    <?php if ($property['is_featured']): ?>
                        <span class="property-badge">Featured</span>
                    <?php endif; ?>
                    <div class="property-image">
    <?php if ($property['image']): ?>
        <img src="<?= htmlspecialchars($property['image']) ?>" alt="<?= htmlspecialchars($property['title']) ?>">
    <?php else: ?>
        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
            <i class="fas fa-home" style="font-size: 3rem;"></i>
        </div>
    <?php endif; ?>

    <form method="GET" action="properties.php" class="favorite-form">
        <input type="hidden" name="property_id" value="<?= $property['property_id'] ?>">
        <input type="hidden" name="favorite_action" value="<?= in_array($property['property_id'], $favorites) ? 'remove' : 'add' ?>">
        <button type="submit" class="favorite-btn <?= in_array($property['property_id'], $favorites) ? 'favorited' : '' ?>" title="Save">
            <i class="fas fa-heart"></i>
        </button>
    </form>
</div>

                    <div class="property-info">
                        <h3><?= htmlspecialchars($property['title']) ?></h3>
                        <div class="property-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?></span>
                            <span><i class="fas fa-building"></i> <?= htmlspecialchars($property['type_name']) ?></span>
                        </div>
                        <div class="property-meta">
                            <span><i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> Baths</span>
                        </div>
                        <div class="property-price">£<?= number_format($property['price'], 2) ?></div>
                        <a href="property.php?id=<?= $property['property_id'] ?>" class="property-link">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-properties-found">
            <i class="fas fa-search" style="font-size: 3rem; color: var(--accent); margin-bottom: 20px;"></i>
            <h3>No Properties Found</h3>
            <p>We couldn't find any properties matching your criteria. Try adjusting your filters or contact our agents for personalized assistance.</p>
            <a href="properties.php" class="btn">
                <i class="fas fa-sync-alt"></i> Reset Filters
            </a>
            <a href="contact.php" class="btn btn-outline" style="margin-left: 15px;">
                <i class="fas fa-headset"></i> Contact Agent
            </a>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php else: ?>
                <span class="disabled">
                    <i class="fas fa-chevron-left"></i> Previous
                </span>
            <?php endif; ?>

            <?php
            $visible = 2;
            $start = max(1, $page - $visible);
            $end = min($totalPages, $page + $visible);

            if ($start > 1) {
                echo '<a href="?'. http_build_query(array_merge($_GET, ['page'=>1])) .'">1</a>';
                if ($start > 2) echo '<span class="ellipsis">...</span>';
            }

            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) {
                    echo '<span class="active">' . $i . '</span>';
                } else {
                    echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '">' . $i . '</a>';
                }
            }

            if ($end < $totalPages) {
                if ($end < $totalPages - 1) echo '<span class="ellipsis">...</span>';
                echo '<a href="?'. http_build_query(array_merge($_GET, ['page'=>$totalPages])) .'">'.$totalPages.'</a>';
            }
            ?>

            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled">
                    Next <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<script>
    // Price range validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const minPrice = document.getElementById('min_price').value;
        const maxPrice = document.getElementById('max_price').value;
        
        if (minPrice && maxPrice && parseInt(minPrice) > parseInt(maxPrice)) {
            alert('Minimum price cannot be greater than maximum price');
            e.preventDefault();
        }
    });
</script>

<?php require_once 'footer.php'; ?>

</body>
</html>