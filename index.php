<?php
// Database Connection
require_once 'db_config.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch Featured Properties with their primary images
$stmt = $pdo->prepare("
    SELECT p.*, pt.type_name, 
           (SELECT image_path FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as primary_image
    FROM properties p 
    JOIN property_types pt ON p.type_id = pt.type_id 
    WHERE p.is_featured = 1 AND p.is_available = 1 
    LIMIT 6
");
$stmt->execute();
$featuredProperties = $stmt->fetchAll();

// Fetch all property types for filter
$types = $pdo->query("SELECT * FROM property_types")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heritage Crestline Realty | Luxury Properties & Student Accommodations</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php require_once 'header.php'; ?>

<!-- Hero Section with Video -->
<section class="hero">
    <img src="/Image/hero_bg.jpg" alt="Hero Image" class="hero-image">
    <div class="hero-content">
        <h1>Discover Exceptional Living</h1>
        <p>Luxury properties and premium student accommodations across the UK's finest locations</p>
        <div>
            <a href="properties.php" class="btn">Explore Properties</a>
            <a href="contact.php" class="btn btn-outline">Contact Us</a>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section id="featured-properties">
    <div class="section-title">
        <h2>Featured Properties</h2>
        <p>Explore our curated selection of premium properties that redefine luxury living</p>
    </div>

    <div class="property-list">
        <?php if (!empty($featuredProperties)): ?>
            <?php foreach ($featuredProperties as $property): ?>
                <div class="property-card">
                    <?php
                        // Convert Windows file path to browser-accessible URL
                        $imagePath = '/Images/default.jpg'; // fallback
                        if (!empty($property['primary_image'])) {
                            $imagePath = str_replace(['C:\\xampp\\htdocs', 'C:/xampp/htdocs'], '', $property['primary_image']);
                            $imagePath = str_replace('\\', '/', $imagePath); // normalize slashes
                        }
                    ?>
                    <div class="property-image">
                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($property['title']) ?>">
                    </div>
                    <div class="property-info">
                        <h3><?= htmlspecialchars($property['title']) ?></h3>
                        <div class="location">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?>
                        </div>
                        <div class="property-details">
                            <span><i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> Baths</span>
                            <span>
                                <i class="fas fa-<?= $property['is_for_rent'] ? 'pound-sign' : 'home' ?>"></i>
                                <?= $property['is_for_rent'] ? 'Rent' : 'Sale' ?>
                            </span>
                        </div>
                        <div class="property-price">£<?= number_format($property['price'], 2) ?></div>
                        <a href="property.php?id=<?= $property['property_id'] ?>" class="property-link">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-properties">No featured properties available at the moment.</p>
        <?php endif; ?>
    </div>
</section>


<!-- Our Services Section -->
<section class="section-services">
    <div class="section-title">
        <h2>Our Services</h2>
        <p>Everything you need for comfortable student living</p>
    </div>
    <div class="services-grid">
        <div class="service-card">
            <div class="service-icon"><i class="fas fa-home"></i></div>
            <h3 class="service-title">Property Acquisition</h3>
            <p class="service-text">
                Our experts will help you find the perfect property that meets all your requirements and exceeds your expectations.
            </p>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="fas fa-search-dollar"></i></div>
            <h3 class="service-title">Budget Planning</h3>
            <p class="service-text">
                We help students find accommodations that fit their budget without compromising on quality or location.
            </p>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="fas fa-users"></i></div>
            <h3 class="service-title">Roommate Matching</h3>
            <p class="service-text">
                Find compatible roommates to share accommodations and reduce living costs.
            </p>
        </div>
        <div class="service-card">
            <div class="service-icon"><i class="fas fa-file-signature"></i></div>
            <h3 class="service-title">Lease Assistance</h3>
            <p class="service-text">
                We guide you through the rental process and help understand lease agreements.
            </p>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
    <div class="testimonials-container">
        <div class="section-title">
            <h2>Client Testimonials</h2>
            <p>What our clients say about their experience with Heritage Crestline Realty</p>
        </div>

        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>Heritage Crestline made my property search effortless. Their attention to detail and understanding of my needs resulted in finding my dream home within weeks.</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sarah Johnson">
                            </div>
                            <div class="author-info">
                                <h4>Mushi Cleopa</h4>
                                <p>Property Owner, London</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>As an international student, finding quality accommodation was challenging until I discovered Heritage Crestline. Their student housing options are premium and well-located.</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="David Chen">
                            </div>
                            <div class="author-info">
                                <h4>Elenor James</h4>
                                <p>Student, University of Manchester</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>The team at Heritage Crestline went above and beyond to help me sell my property at the best possible price. Their market knowledge was impressive.</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Emily Rutherford">
                            </div>
                            <div class="author-info">
                                <h4>Khalid</h4>
                                <p>Property Seller, Birmingham</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Ready to Find Your Dream Property?</h2>
        <p>Our team of expert agents is ready to guide you through every step of your property journey.</p>
        <a href="contact.php" class="btn">Get in Touch</a>
    </div>
</section>

<?php require_once 'footer.php'; ?>

<script src="script.js"></script>
</body>
</html>