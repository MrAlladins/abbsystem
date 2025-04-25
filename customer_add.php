<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kontrollera om användaren är inloggad
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = getDbConnection();
$user_id = $_SESSION['user_id'];
$errors = [];

// Hantera formulärinskick
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hämta och validera kunddata
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $notes = trim($_POST['notes']);
    
    // Validera obligatoriska fält
    if (empty($first_name)) {
        $errors[] = "Förnamn är obligatoriskt";
    }
    
    if (empty($last_name)) {
        $errors[] = "Efternamn är obligatoriskt";
    }
    
    // Validera e-post om den anges
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ogiltig e-postadress";
    }
    
    // Om inga fel, lägg till kunden
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO customers 
            (user_id, first_name, last_name, email, phone, address, city, postal_code, country, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $user_id, $first_name, $last_name, $email, $phone, $address, $city, $postal_code, $country, $notes
        ]);
        
        if ($result) {
            $customer_id = $db->lastInsertId();
            $_SESSION['success_message'] = "Kunden har lagts till!";
            
            // Omdirigera till kundsidan eller till att lägga till ett abonnemang
            if (isset($_POST['add_subscription'])) {
                header("Location: subscription_add.php?customer_id=" . $customer_id);
            } else {
                header("Location: customer_view.php?id=" . $customer_id);
            }
            exit();
        } else {
            $errors[] = "Ett fel uppstod när kunden skulle läggas till. Försök igen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lägg till kund - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Lägg till ny kund</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Förnamn: <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Efternamn: <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-post:</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Telefon:</label>
                    <input type="text" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Adress:</label>
                <input type="text" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="postal_code">Postnummer:</label>
                    <input type="text" id="postal_code" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="city">Ort:</label>
                    <input type="text" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="country">Land:</label>
                    <input type="text" id="country" name="country" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : 'Sweden'; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Anteckningar:</label>
                <textarea id="notes" name="notes" rows="4"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Spara</button>
                <button type="submit" name="add_subscription" value="1" class="btn">Spara och lägg till abonnemang</button>
                <a href="customers.php" class="btn btn-secondary">Avbryt</a>
            </div>
        </form>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
</body>
</html>