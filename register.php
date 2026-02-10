<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

// DB Setup
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

$errors = [];
$success = false;
$formData = [
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];

    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = "Full name is required";
    }

    if (empty($formData['username'])) {
        $errors['username'] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $formData['username'])) {
        $errors['username'] = "Username must be 4-20 characters (letters, numbers, underscores)";
    }

    if (empty($formData['email'])) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    if (!empty($formData['phone']) && !preg_match('/^[\d\s\-+()]{10,20}$/', $formData['phone'])) {
        $errors['phone'] = "Invalid phone number format";
    }

    if (empty($formData['password'])) {
        $errors['password'] = "Password is required";
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = "Password must be at least 8 characters";
    }

    if ($formData['password'] !== $formData['password_confirm']) {
        $errors['password_confirm'] = "Passwords do not match";
    }

    // Check uniqueness
    if (empty($errors['username']) && empty($errors['email'])) {
        $check = $pdo->prepare("SELECT * FROM customers WHERE username = :username OR email = :email");
        $check->execute([':username' => $formData['username'], ':email' => $formData['email']]);
        if ($existing = $check->fetch()) {
            if (strtolower($existing['username']) === strtolower($formData['username'])) {
                $errors['username'] = "Username already in use";
            }
            if (strtolower($existing['email']) === strtolower($formData['email'])) {
                $errors['email'] = "Email already in use";
            }
        }
    }

    // Insert if valid
    if (empty($errors)) {
        $hash = password_hash($formData['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO customers (name, username, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$formData['name'], $formData['username'], $formData['email'], $hash, $formData['phone']]);
        
        // Log in the user immediately after registration
        $customer_id = $pdo->lastInsertId();
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['customer_name'] = $formData['name'];
        $_SESSION['customer_email'] = $formData['email'];
        $_SESSION['customer_phone'] = $formData['phone'];
        
        header("Location: user_dashboard.php?welcome=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Heritage Crestline</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <div class="register-page">
        <div class="register-container">
            <div class="register-header">
                <h1>Join Heritage Realty</h1>
                <p>Create your account to access luxury properties</p>
            </div>
            
            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="name" class="required-field">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" 
                           value="<?= htmlspecialchars($formData['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['name']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="username" class="required-field">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" 
                           value="<?= htmlspecialchars($formData['username']) ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['username']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email" class="required-field">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" 
                           value="<?= htmlspecialchars($formData['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" 
                           value="<?= htmlspecialchars($formData['phone']) ?>">
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password" class="required-field">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="passwordStrengthText"></div>
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="required-field">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirm your password" required>
                    <i class="fas fa-eye" id="togglePasswordConfirm"></i>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="error-message"><?= htmlspecialchars($errors['password_confirm']) ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="register-btn">Create Account</button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? <a href="user_login.php">Sign in here</a></p>
            </div>
        </div>
    </div>
    
    <?php require_once 'footer.php'; ?>

    <script>
        // Password visibility toggle
        const togglePassword = document.querySelector('#togglePassword');
        const togglePasswordConfirm = document.querySelector('#togglePasswordConfirm');
        const password = document.querySelector('#password');
        const passwordConfirm = document.querySelector('#password_confirm');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        togglePasswordConfirm.addEventListener('click', function() {
            const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirm.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Password strength indicator
        password.addEventListener('input', function() {
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            const strength = calculatePasswordStrength(this.value);
            
            strengthBar.style.width = strength.percentage + '%';
            strengthBar.className = 'password-strength-bar strength-' + strength.class;
            strengthText.textContent = strength.text;
        });

        function calculatePasswordStrength(password) {
            let strength = 0;
            let text = '';
            let strengthClass = '';
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Character variety
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Determine strength level
            if (password.length === 0) {
                text = '';
                strengthClass = '';
            } else if (strength <= 2) {
                text = 'Weak';
                strengthClass = 'weak';
            } else if (strength <= 4) {
                text = 'Medium';
                strengthClass = 'medium';
            } else {
                text = 'Strong';
                strengthClass = 'strong';
            }
            
            // Calculate percentage (for visual bar)
            const percentage = Math.min(100, Math.max(0, (strength / 5) * 100));
            
            return {
                percentage: percentage,
                text: text,
                class: strengthClass
            };
        }
    </script>
</body>
</html>