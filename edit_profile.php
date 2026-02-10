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

// Initialize variables with default values
$user = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'username' => '',
    'created_at' => date('Y-m-d H:i:s')
];

// Fetch user details with error handling
try {
    $userStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
    $userStmt->execute([$customer_id]);
    $userData = $userStmt->fetch();
    
    if ($userData) {
        $user = array_merge($user, $userData);
    } else {
        $_SESSION['error'] = "User not found";
        header("Location: user_login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching user: " . $e->getMessage());
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }

    // Check if email or username already exists (excluding current user)
    try {
        $emailCheck = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?");
        $emailCheck->execute([$email, $customer_id]);
        if ($emailCheck->fetch()) {
            $errors['email'] = 'Email already in use by another account';
        }

        $usernameCheck = $pdo->prepare("SELECT customer_id FROM customers WHERE username = ? AND customer_id != ?");
        $usernameCheck->execute([$username, $customer_id]);
        if ($usernameCheck->fetch()) {
            $errors['username'] = 'Username already in use by another account';
        }
    } catch (PDOException $e) {
        $errors['database'] = 'Error checking user credentials: ' . $e->getMessage();
    }

    // Password change logic
    $password_changed = false;
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }

        if (empty($new_password)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }

        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (empty($errors)) {
            $password_changed = true;
        }
    }

    // Update database if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($password_changed) {
                $updateStmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, username = ?, password_hash = ? WHERE customer_id = ?");
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt->execute([$name, $email, $phone, $username, $hashed_password, $customer_id]);
            } else {
                $updateStmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, username = ? WHERE customer_id = ?");
                $updateStmt->execute([$name, $email, $phone, $username, $customer_id]);
            }

            $pdo->commit();
            $success = true;
            
            // Refresh user data
            $userStmt->execute([$customer_id]);
            $user = $userStmt->fetch();
            $_SESSION['user_name'] = $user['name'];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['database'] = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}
?>

<?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | Heritage Realty</title>

    <link rel="stylesheet" href="style.css">

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
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
                <li><a href="edit_profile.php" class="active"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
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
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="profile-container">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Your profile has been updated successfully.
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Please correct the errors below.
                        <?php if (isset($errors['database'])): ?>
                            <div class="error-message"><?= htmlspecialchars($errors['database']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header">
                    <h1>Profile Settings</h1>
                    <p>Manage your personal information and account security</p>
                </div>

                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($user['name']) ?></h2>
                            <p>Member since <?= !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : 'Unknown' ?></p>
                        </div>
                    </div>

                    <div class="profile-card-body">
                        <form action="edit_profile.php" method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="error-message"><?= htmlspecialchars($errors['name']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="error-message"><?= htmlspecialchars($errors['email']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                                    <?php if (isset($errors['username'])): ?>
                                        <div class="error-message"><?= htmlspecialchars($errors['username']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    <div class="form-text">Optional - used for property viewings</div>
                                </div>

                                <div class="form-group full-width">
                                    <h3 style="color: var(--primary); margin-bottom: 1rem; font-family: 'Playfair Display', serif;">Change Password</h3>
                                    <p class="form-text">Leave these fields blank if you don't want to change your password</p>
                                </div>

                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="password-toggle">
                                        <input type="password" id="current_password" name="current_password" class="form-control">
                                        <i class="fas fa-eye toggle-icon" onclick="togglePassword('current_password', this)"></i>
                                    </div>
                                    <?php if (isset($errors['current_password'])): ?>
                                        <div class="error-message"><?= htmlspecialchars($errors['current_password']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="password-toggle">
                                        <input type="password" id="new_password" name="new_password" class="form-control">
                                        <i class="fas fa-eye toggle-icon" onclick="togglePassword('new_password', this)"></i>
                                    </div>
                                    <div class="form-text">At least 8 characters</div>
                                    <?php if (isset($errors['new_password'])): ?>
                                        <div class="error-message"><?= htmlspecialchars($errors['new_password']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="password-toggle">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                                        <i class="fas fa-eye toggle-icon" onclick="togglePassword('confirm_password', this)"></i>
                                    </div>
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="error-message"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group full-width" style="margin-top: 1rem;">
                                    <button type="submit" class="btn">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <a href="user_dashboard.php" class="btn btn-outline" style="margin-left: 1rem;">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>


    <script>
        // Toggle password visibility
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form submission animation
        document.querySelector('form')?.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>