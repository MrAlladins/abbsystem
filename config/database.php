<?php
// config/database.php
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'u453515052_users';
    $username = 'u453515052_users';
    $password = 'Jonas366#';
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        echo "Anslutningsfel: " . $e->getMessage();
        die();
    }
}
?>
