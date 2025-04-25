<?php
// Aktivera felrapportering
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Starta sessionen
session_start();

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Databasuppgifter direkt i filen för enkelhetens skull
$host = 'localhost';
$dbname = 'u453515052_users';
$username = 'u453515052_users';
$password = 'Jonas366#';

try {
    // Anslut till databasen
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Hämta kunder
    if ($user_role === 'admin') {
        $query = "SELECT * FROM customers";
        $stmt = $db->query($query);
    } else {
        $query = "SELECT * FROM customers WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    }
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Databasfel: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kunder</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .header { background: #4a6fa5; color: white; padding: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 8px; border: 1px solid #ddd; }
        .menu a { display: inline-block; margin: 10px; padding: 5px 10px; background: #4a6fa5; color: white; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Kunder</h1>
    </div>
    
    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="customer_add.php">Lägg till kund</a>
        <a href="logout.php">Logga ut</a>
    </div>
    
    <?php if (empty($customers)): ?>
        <p>Inga kunder hittades.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Namn</th>
                <th>E-post</th>
                <th>Telefon</th>
                <th>Stad</th>
            </tr>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo $customer['customer_id']; ?></td>
                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                    <td><?php echo htmlspecialchars($customer['city']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>