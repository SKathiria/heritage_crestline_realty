<?php
session_start();
require_once 'db_config.php';
echo $pdo ? "DB CONNECTED<br>" : "NOT CONNECTED<br>";
$error = '';
$username = $password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['name'];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Both fields are required.";
    }
}
?>

    <?php require_once 'header.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Heritage Crestline Realty</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
<style>
        :root {
            --primary: #5D4037;
            --primary-light: #795548;
            --secondary: #BCAAA4;
            --accent: #C9A227;
            --accent-dark: #A67C00;
            --light: #F5F0EC;
            --light-gray: #ffffff;
            --dark: #3E3E3E;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            background-color: var(--light);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 6rem;
            background-image: url('images/luxury-pattern-bg.jpg');
            background-size: cover;
            background-position: center;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--primary-light);
            font-size: 0.95rem;
        }

        .login-body {
            padding: 2rem;
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .brand-logo img {
            height: 50px;
        }

        .brand-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .error-alert {
            color: #e74c3c;
            background-color: #fde8e8;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--primary-light);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-input:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.2);
        }

        .remember-group {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-checkbox {
            margin-right: 0.5rem;
            accent-color: var(--accent);
        }

        .remember-label {
            font-size: 0.9rem;
            color: var(--primary-light);
        }

        .login-button {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            display: block;
        }

        .login-button:hover {
            background-color: var(--accent-dark);
        }

    </style>
</head>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-form-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Enter your admin credentials</p>
        </div>
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password">
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
    </div>

    <?php require_once 'footer.php'; ?>

</body>
</html>
