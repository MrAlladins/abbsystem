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

// Visa användarinformation från sessionen
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['user_role'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .header { background: #4a6fa5; color: white; padding: 10px; margin-bottom: 20px; }
        .menu a { display: inline-block; margin: 10px; padding: 5px 10px; background: #4a6fa5; color: white; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Dashboard</h1>
        <p>Inloggad som: <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)</p>
    </div>
    
    <div class="menu">
        <a href="customers.php">Visa kunder</a>
        <a href="customer_add.php">Lägg till kund</a>
        <a href="logout.php">Logga ut</a>
    </div>
    
    <div>
        <h2>Välkommen till ditt abonnemangssystem!</h2>
        <p>Använd menyn ovan för att hantera dina kunder och abonnemang.</p>
    </div>
</body>
</html>