<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['customer_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db_config.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful - set session and redirect to dashboard
            $_SESSION['customer_id'] = $user['customer_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            
            header("Location: user_dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Heritage Crestline</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <div class="login-page">
        <div class="login-form-container">
            <div class="login-header">
                <h1>User Login</h1>
                <p>User Login</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="user_login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn">LOG IN</button>
            </form>
            
            <div class="login-footer">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>
    </div>
    
    <?php require_once 'footer.php'; ?>
</body>
</html>