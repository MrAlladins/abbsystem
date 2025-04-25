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

// Sökparametrar
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Hämta användare exklusive admin själv
$query = "SELECT * FROM users WHERE user_id != ?";
$params = [$_SESSION['user_id']];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$query .= " ORDER BY role, username";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hantera användare - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Hantera användare</h1>
        
        <div class="action-bar">
            <form method="get" action="" class="search-form">
                <input type="text" name="search" placeholder="Sök användare..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-small">Sök</button>
            </form>
            
            <a href="admin_user_add.php" class="btn">Lägg till ny användare</a>
        </div>
        
        <?php if (empty($users)): ?>
            <p>Inga användare hittades.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Användarnamn</th>
                        <th>Namn</th>
                        <th>E-post</th>
                        <th>Roll</th>
                        <th>Registrerad</th>
                        <th>Senaste inloggning</th>
                        <th>Åtgärder</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Aldrig'; ?></td>
                            <td>
                                <a href="admin_user_edit.php?id=<?php echo $user['user_id']; ?>" class="btn-small">Redigera</a>
                                <a href="admin_user_delete.php?id=<?php echo $user['user_id']; ?>" class="btn-small delete-link">Ta bort</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
    
    <script>
    // Enkel JavaScript för att bekräfta borttagning av användare
    document.addEventListener('DOMContentLoaded', function() {
        const deleteLinks = document.querySelectorAll('.delete-link');
        deleteLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Är du säker på att du vill ta bort denna användare? Detta kan inte ångras.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>