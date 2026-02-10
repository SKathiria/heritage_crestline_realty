<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "heritage_crestline_reality_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
$message_sent = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'contact_messages'");
    
    if ($table_check->num_rows == 0) {
        $error_message = "Database configuration error. Please try again later.";
    } else {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            
            // Sanitize inputs
            $name = htmlspecialchars($_POST['name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $subject = htmlspecialchars($_POST['subject'] ?? '');
            $message = htmlspecialchars($_POST['message'] ?? '');
            
            if ($stmt->execute()) {
                $message_sent = true;
            } else {
                $error_message = "Error sending message. Please try again.";
            }
            
            $stmt->close();
        } else {
            $error_message = "Database error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heritage Realty | Contact Us</title>
    <link rel="stylesheet" href="style.css">
</head>

<?php require_once 'header.php'; ?>

<body>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="hero-content">
            <h1>Get In Touch With Us</h1>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact">
        <h2 class="section-title">Contact Information</h2>
        
        <div class="contact-container">
            <div class="contact-info">
                <h3>Contact Information</h3>
                <div class="contact-details">
                    <div class="contact-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p>123 Gourmet Street<br>Foodville, FK 12345</p>
                        </div>
                    </div>
                    <div class="contact-detail">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>(123) 456-7890</p>
                        </div>
                    </div>
                    <div class="contact-detail">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@heritagerealty.com</p>
                        </div>
                    </div>
                    <div class="contact-detail">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Hours</h4>
                            <p>Monday-Friday: 9am - 6pm<br>Saturday-Sunday: 10am - 4pm</p>
                        </div>
                    </div>
                </div>
                
                <h3 style="margin-top: 3rem;">Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="contact-form">
                <h3>Send Us a Message</h3>
                
                <?php if($message_sent): ?>
                <div class="success-message">
                    Thank you for your message! We'll get back to you soon.
                </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Your Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Property Inquiry">Property Inquiry</option>
                            <option value="Viewing Appointment">Viewing Appointment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    <button type="submit" class="btn">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <section class="contact-map">
    <h2>Find Us</h2>
    <div class="map-container">
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2436.6438904569454!2d-0.1277583841901576!3d51.507350479634906!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x48761b3331e7bb27%3A0x8e2ff46ec71a8d1a!2s123%20Luxury%20Lane%2C%20London%2C%20UK!5e0!3m2!1sen!2suk!4v1684857229584!5m2!1sen!2suk" 
            width="100%" 
            height="400" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
    </section>


    <?php require_once 'footer.php'; ?>

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');
        
        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ? 
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
        
        // Smooth Scrolling for Anchor Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                }
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>