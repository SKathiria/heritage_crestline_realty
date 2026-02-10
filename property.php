<?php
session_start();
require_once 'db_config.php';

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get property by ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: properties.php");
    exit();
}

$property_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT p.*, pt.type_name FROM properties p JOIN property_types pt ON p.type_id = pt.type_id WHERE p.property_id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: properties.php");
    exit();
}

$property_images = []; // Initialize as empty array

try {
    $stmt = $pdo->prepare("SELECT image_path, alt_text FROM property_images WHERE property_id = ?");
    $stmt->execute([$property_id]);
    $db_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert paths and populate $property_images
    foreach ($db_images as $image) {
        $property_images[] = [
            'image_path' => str_replace(
                'C:\\xampp\\htdocs\\', 
                'http://localhost/', 
                $image['image_path']
            ),
            'alt_text' => $image['alt_text']
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching property images: " . $e->getMessage());
    $property_images = []; // Ensure it remains an array
}

// Check if property is favorited by current user
$is_favorite = false;
if (isset($_SESSION['customer_id'])) {
    $fav_check = $pdo->prepare("SELECT * FROM favorites WHERE customer_id = ? AND property_id = ?");
    $fav_check->execute([$_SESSION['customer_id'], $property_id]);
    $is_favorite = $fav_check->rowCount() > 0;
}

// Handle form submissions
$bookingSuccess = false;
$enquirySuccess = false;
$favoriteSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['login_redirect'] = "property.php?id=$property_id";
        header("Location:user_login.php");
        exit();
    }

    $customer_id = $_SESSION['customer_id'];

/// Handle Booking Form Submission
if (isset($_POST['book_now'])) {
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['login_redirect'] = "property.php?id=$property_id";
        header("Location: user_login.php");
        exit();
    }

    $customer_id = $_SESSION['customer_id'];
    $date = $_POST['preferred_date'];
    $message = $_POST['message'] ?? '';

    // Validate date
    if (empty($date)) {
        $bookingError = "Please select a date and time";
    } else {
        try {
            $insert = $pdo->prepare("INSERT INTO bookings (customer_id, property_id, booking_date, message, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
            $insert->execute([$customer_id, $property_id, $date, $message]);
            $bookingSuccess = true;
            
            // Clear form on success
            unset($_POST['preferred_date']);
            unset($_POST['message']);
        } catch (PDOException $e) {
            $bookingError = "Error submitting booking: " . $e->getMessage();
        }
    }
}
    // Handle Enquiry Submission
    if (isset($_POST['send_enquiry'])) {
        $message = $_POST['enquiry_message'];

        $insert = $pdo->prepare("INSERT INTO inquiries (customer_id, property_id, message) VALUES (?, ?, ?)");
        $insert->execute([$customer_id, $property_id, $message]);
        $enquirySuccess = true;
    }
// Handle Favorite Toggle
if (isset($_POST['toggle_favorite']) && isset($_POST['property_id'])) {
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['login_redirect'] = "property.php?id=$property_id";
        header("Location: user_login.php");
        exit();
    }

    $customer_id = $_SESSION['customer_id'];
    $property_id = (int)$_POST['property_id'];
    
    // Verify property exists
    $property_check = $pdo->prepare("SELECT 1 FROM properties WHERE property_id = ?");
    $property_check->execute([$property_id]);
    
    if ($property_check->rowCount() > 0) {
        if ($is_favorite) {
            $delete = $pdo->prepare("DELETE FROM favorites WHERE customer_id = ? AND property_id = ?");
            $delete->execute([$customer_id, $property_id]);
            $is_favorite = false;
        } else {
            $insert = $pdo->prepare("INSERT INTO favorites (customer_id, property_id, favorited_at) VALUES (?, ?, NOW())");
            $insert->execute([$customer_id, $property_id]);
            $is_favorite = true;
        }
        $favoriteSuccess = true;
        
        // For AJAX requests, return simple response
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo $is_favorite ? 'added' : 'removed';
            exit();
        }
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> | Heritage Crestline Realty</title>
    <link rel="stylesheet" href="style.css">    
</head>
<body>

<?php require_once 'header.php'; ?>

<!-- Property Header -->
<section class="property-header">
    <div class="property-container">
        <h1><?= htmlspecialchars($property['title']) ?></h1>
        <p><?= htmlspecialchars($property['location']) ?> • <?= htmlspecialchars($property['type_name']) ?></p>
    </div>
</section>

<!-- Property Container -->
<div class="property-container">

    <!-- Image Gallery -->
    <div class="gallery-container">
        <form method="POST" class="favorite-form">
    <button type="submit" name="toggle_favorite" class="favorite-btn <?= $is_favorite ? 'favorited' : '' ?>" 
            title="<?= $is_favorite ? 'Remove from favorites' : 'Add to favorites' ?>">
        <i class="fas fa-heart"></i>
        <span><?= $is_favorite ? 'Favorited' : 'Add to Favorites' ?></span>
    </button>
    <input type="hidden" name="property_id" value="<?= $property_id ?>">
</form>
        
        <div class="gallery-main">
            <?php if (count($property_images) > 0): ?>
                <img src="<?= htmlspecialchars($property_images[0]['image_path']) ?>" alt="<?= htmlspecialchars($property['title']) ?>" id="mainImage">
            <?php else: ?>
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                    <i class="fas fa-home" style="font-size: 3rem;"></i>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($property_images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($property_images as $index => $image): ?>
                    <div class="gallery-thumb <?= $index === 0 ? 'active' : '' ?>" onclick="changeImage('<?= htmlspecialchars($image['image_path']) ?>', this)">
                        <img src="<?= htmlspecialchars($image['image_path']) ?>" alt="Property Image <?= $index + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($favoriteSuccess): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Property <?= $is_favorite ? 'added to' : 'removed from' ?> your favorites!
        </div>
    <?php endif; ?>

    <!-- Property Details -->
    <div class="property-details">
        <div class="details-main">
            <h2>Property Details</h2>
            <p class="description"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
            
            <h3>Features</h3>
            <div class="meta-grid">
                <div class="meta-item">
                    <i class="fas fa-bed"></i>
                    <span><?= $property['bedrooms'] ?> Bedrooms</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-bath"></i>
                    <span><?= $property['bathrooms'] ?> Bathrooms</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-ruler-combined"></i>
                    <span>2,450 sq ft</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Built in 2018</span>
                </div>
            </div>

            <h3>Amenities</h3>
            <div class="amenities-grid">
                <div class="amenity-item">
                    <i class="fas fa-wifi"></i>
                    <span>High-Speed WiFi</span>
                </div>
                <div class="amenity-item">
                    <i class="fas fa-swimming-pool"></i>
                    <span>Swimming Pool</span>
                </div>
                <div class="amenity-item">
                    <i class="fas fa-warehouse"></i>
                    <span>Garage</span>
                </div>
                <div class="amenity-item">
                    <i class="fas fa-utensils"></i>
                    <span>Modern Kitchen</span>
                </div>
                <div class="amenity-item">
                    <i class="fas fa-fire"></i>
                    <span>Fireplace</span>
                </div>
                <div class="amenity-item">
                    <i class="fas fa-dumbbell"></i>
                    <span>Gym</span>
                </div>
            </div>
        </div>

        <div class="details-sidebar">
            <div class="price-box">
                <span class="type"><?= $property['is_for_rent'] ? 'For Rent' : 'For Sale' ?></span>
                <div class="price">£<?= number_format($property['price'], 2) ?></div>
                <span>Price <?= $property['is_for_rent'] ? 'per month' : '' ?></span>
            </div>

            <div class="form-card">
                <h3>Contact Agent</h3>
                <p>For more information about this property, contact our agent:</p>
                <p><strong>John Smith</strong><br>
                <i class="fas fa-phone"></i> +44 20 1234 5678<br>
                <i class="fas fa-envelope"></i> john.smith@heritagecrestline.com</p>
            </div>
        </div>
    </div>

    <!-- Contact Forms -->
<div class="form-card">
    <h3>Book a Viewing</h3>
    <?php if ($bookingSuccess): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Your viewing request has been submitted successfully! Our agent will contact you shortly.
        </div>
    <?php elseif (isset($bookingError)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($bookingError) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['customer_id'])): ?>
        <form method="POST" id="booking-form">
            <div class="form-group">
                <label for="preferred_date">Preferred Date & Time</label>
                <input type="datetime-local" id="preferred_date" name="preferred_date" 
                       class="form-control" required
                       value="<?= isset($_POST['preferred_date']) ? htmlspecialchars($_POST['preferred_date']) : '' ?>"
                       min="<?= date('Y-m-d\TH:i', strtotime('+1 day')) ?>">
            </div>
            <div class="form-group">
                <label for="message">Additional Notes</label>
                <textarea id="message" name="message" class="form-control"><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
            </div>
            <button type="submit" name="book_now" class="btn btn-block">
                <i class="fas fa-calendar-check"></i> Request Viewing
            </button>
        </form>
    <?php else: ?>
        <div class="login-prompt">
            <i class="fas fa-lock"></i>
            <p>Please <a href="user_login.php?redirect=property.php?id=<?= $property_id ?>">login</a> to book a viewing.</p>
        </div>
    <?php endif; ?>
</div>

        <div class="form-card">
            <h3>Send Enquiry</h3>
            <?php if ($enquirySuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Your enquiry has been sent successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['customer_id'])): ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="enquiry_message">Your Message</label>
                        <textarea id="enquiry_message" name="enquiry_message" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="send_enquiry" class="btn btn-block">Send Enquiry</button>
                </form>
            <?php else: ?>
                <div class="login-prompt">
                    <i class="fas fa-lock"></i>
                    <p>Please <a href="login.php?redirect=property.php?id=<?= $property_id ?>">login</a> to send an enquiry.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Handle favorite button with AJAX for better UX
document.addEventListener('DOMContentLoaded', function() {
    // Favorite forms handling
    const favoriteForms = document.querySelectorAll('.favorite-form');
    
    favoriteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button');
            
            fetch('favorites.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Toggle visual state
                button.classList.toggle('favorited');
                const isFavorited = button.classList.contains('favorited');
                button.title = isFavorited ? 'Remove from favorites' : 'Add to favorites';
                button.querySelector('span').textContent = isFavorited ? 'Favorited' : 'Add to Favorites';
                
                // Show temporary feedback
                const feedback = document.createElement('div');
                feedback.className = 'favorite-feedback';
                feedback.innerHTML = `<i class="fas fa-heart"></i> ${isFavorited ? 'Added to favorites!' : 'Removed from favorites!'}`;
                this.appendChild(feedback);
                
                setTimeout(() => feedback.remove(), 2000);
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Booking form handling
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const dateInput = this.querySelector('#preferred_date');
            const now = new Date();
            const selectedDate = new Date(dateInput.value);
            
            // Validate date is in the future
            if (selectedDate <= now) {
                e.preventDefault();
                alert('Please select a future date and time');
                dateInput.focus();
            }
        });
    }
});
</script>
<?php require_once 'footer.php'; ?>

</body>
</html>