<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kontrollera att användaren är inloggad och är admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$db = getDbConnection();

// Hämta statistik för admin
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$user_count = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM customers");
$customer_count = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
$active_subscriptions = $stmt->fetchColumn();

// Hämta de senaste användarna
$stmt = $db->query("
    SELECT * FROM users 
    WHERE role != 'admin' 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="dashboard-stats">
            <div class="stat-box">
                <h3>Användare</h3>
                <p class="stat-number"><?php echo $user_count; ?></p>
                <a href="admin_users.php" class="btn-small">Hantera användare</a>
            </div>
            
            <div class="stat-box">
                <h3>Kunder totalt</h3>
                <p class="stat-number"><?php echo $customer_count; ?></p>
                <a href="admin_customers.php" class="btn-small">Visa alla</a>
            </div>
            
            <div class="stat-box">
                <h3>Aktiva abonnemang</h3>
                <p class="stat-number"><?php echo $active_subscriptions; ?></p>
                <a href="admin_subscriptions.php" class="btn-small">Visa alla</a>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="admin_user_add.php" class="btn">Lägg till ny användare</a>
        </div>
        
        <div class="dashboard-section">
            <h2>Senast registrerade användare</h2>
            
            <?php if (empty($recent_users)): ?>
                <p>Inga användare har registrerats ännu.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Användarnamn</th>
                            <th>Namn</th>
                            <th>E-post</th>
                            <th>Roll</th>
                            <th>Registrerad</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="admin_user_edit.php?id=<?php echo $user['user_id']; ?>" class="btn-small">Redigera</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
</body>
</html>