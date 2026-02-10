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
$userStmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$userStmt->execute([$customer_id]);
$user = $userStmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found";
    header("Location: user_login.php");
    exit;
}

// Handle inquiry status update (for admin)
$is_admin = isset($_SESSION['admin_id']);
$updateSuccess = false;
$updateError = false;

if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inquiry_id = $_POST['inquiry_id'];
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    try {
        $updateStmt = $pdo->prepare("UPDATE inquiries SET status = ?, admin_notes = ?, updated_at = NOW() WHERE inquiry_id = ?");
        $updateStmt->execute([$new_status, $admin_notes, $inquiry_id]);
        $updateSuccess = true;
        
        // Log admin action
        $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_type, target_table, target_id, description) 
                                 VALUES (?, 'update', 'inquiries', ?, ?)");
        $logStmt->execute([$_SESSION['admin_id'], $inquiry_id, "Updated inquiry status to $new_status"]);
    } catch (PDOException $e) {
        $updateError = "Failed to update inquiry: " . $e->getMessage();
    }
}

// Fetch all inquiries for this user (or all if admin)
if ($is_admin) {
    $inquiryStmt = $pdo->prepare("SELECT i.*, p.title as property_title, p.location, 
                                 c.name as customer_name, c.email as customer_email,
                                 a.name as admin_name
                                 FROM inquiries i
                                 JOIN properties p ON i.property_id = p.property_id
                                 JOIN customers c ON i.customer_id = c.customer_id
                                 LEFT JOIN admins a ON i.admin_id = a.admin_id
                                 ORDER BY i.created_at DESC");
    $inquiryStmt->execute();
} else {
    $inquiryStmt = $pdo->prepare("SELECT i.*, p.title as property_title, p.location 
                                 FROM inquiries i
                                 JOIN properties p ON i.property_id = p.property_id
                                 WHERE i.customer_id = ?
                                 ORDER BY i.created_at DESC");
    $inquiryStmt->execute([$customer_id]);
}

$inquiries = $inquiryStmt->fetchAll();
?>

<?php require_once 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_admin ? 'Manage' : 'My' ?> Inquiries | Heritage Crestline Realty</title>
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
                <li><a href="user_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="properties.php"><i class="fas fa-search"></i> Browse Properties</a></li>
                <li><a href="bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                <li><a href="inquiries.php" class="active"><i class="fas fa-envelope"></i> Inquiries</a></li>
                <li><a href="edit_profile.php"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
            </ul>
            
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
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="page-header">
                <h1><?= $is_admin ? 'Manage Inquiries' : 'My Inquiries' ?></h1>
                <p><?= $is_admin ? 'View and respond to client inquiries' : 'View your property inquiries and responses' ?></p>
            </div>

            <?php if ($updateSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Inquiry status updated successfully.
                </div>
            <?php elseif ($updateError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($updateError) ?>
                </div>
            <?php endif; ?>

            <div class="inquiries-container">
                <?php if (!empty($inquiries)): ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                        <div class="inquiry-card">
                            <div class="inquiry-header">
                                <div>
                                    <div class="inquiry-property"><?= htmlspecialchars($inquiry['property_title']) ?></div>
                                    <div class="inquiry-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($inquiry['location']) ?>
                                    </div>
                                </div>
                                <span class="inquiry-status status-<?= str_replace(' ', '_', strtolower($inquiry['status'])) ?>">
                                    <?= ucfirst(htmlspecialchars($inquiry['status'])) ?>
                                </span>
                            </div>
                            
                            <div class="inquiry-message">
                                <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                            </div>
                            
                            <?php if ($is_admin && !empty($inquiry['admin_notes'])): ?>
                                <div class="admin-response">
                                    <h4><i class="fas fa-user-tie"></i> Agent Notes</h4>
                                    <p><?= nl2br(htmlspecialchars($inquiry['admin_notes'])) ?></p>
                                </div>
                            <?php elseif (!empty($inquiry['admin_notes'])): ?>
                                <div class="admin-response">
                                    <h4><i class="fas fa-user-tie"></i> Agent Response</h4>
                                    <p><?= nl2br(htmlspecialchars($inquiry['admin_notes'])) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="inquiry-meta">
                                <div class="inquiry-date">
                                    <i class="far fa-calendar-alt"></i>
                                    Submitted on <?= date('M j, Y \a\t g:i a', strtotime($inquiry['created_at'])) ?>
                                </div>
                                <div class="inquiry-actions">
                                    <a href="property.php?id=<?= $inquiry['property_id'] ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Property
                                    </a>
                                    <?php if ($is_admin): ?>
                                        <button class="btn" onclick="document.getElementById('status-form-<?= $inquiry['inquiry_id'] ?>').style.display='block'">
                                            <i class="fas fa-edit"></i> Update Status
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($is_admin): ?>
                                <div id="status-form-<?= $inquiry['inquiry_id'] ?>" class="status-form" style="display: none;">
                                    <form method="POST">
                                        <input type="hidden" name="inquiry_id" value="<?= $inquiry['inquiry_id'] ?>">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-control" required>
                                                <option value="new" <?= $inquiry['status'] === 'new' ? 'selected' : '' ?>>New</option>
                                                <option value="in_progress" <?= $inquiry['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="closed" <?= $inquiry['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Agent Notes</label>
                                            <textarea name="admin_notes" class="form-control" rows="3"><?= htmlspecialchars($inquiry['admin_notes'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" name="update_status" class="btn">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                            <button type="button" class="btn btn-outline" onclick="document.getElementById('status-form-<?= $inquiry['inquiry_id'] ?>').style.display='none'">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="far fa-envelope-open"></i>
                        <h3>No Inquiries Yet</h3>
                        <p><?= $is_admin ? 'There are no inquiries in the system.' : 'You haven\'t made any property inquiries yet.' ?></p>
                        <a href="properties.php">Browse Properties</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Animation for inquiry cards
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.inquiry-card');
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