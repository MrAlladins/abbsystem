<?php
// Aktivera felrapportering
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Databasuppgifter
$host = 'localhost';
$dbname = 'u453515052_users';
$username = 'u453515052_users';
$password = 'Jonas366#';

try {
    // Anslut till databasen
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Skapa admin-användare om den inte finns
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn();
    
    if (!$adminExists) {
        // Skapa admin med lösenord "Admin123#"
        $admin_password = password_hash('Admin123#', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO users (username, password, email, first_name, last_name, role) 
            VALUES ('admin', :password, 'admin@example.com', 'Admin', 'User', 'admin')
        ");
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        
        echo "Admin-användare skapad med användarnamn 'admin' och lösenord 'Admin123#'";
    } else {
        // Återställ admins lösenord
        $admin_password = password_hash('Admin123#', PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            UPDATE users 
            SET password = :password 
            WHERE username = 'admin'
        ");
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        
        echo "Admin-användaren finns redan. Lösenordet återställt till 'Admin123#'";
    }
    
} catch (PDOException $e) {
    echo "Fel: " . $e->getMessage();
}
?>