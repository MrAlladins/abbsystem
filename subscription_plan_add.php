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
    // Hämta och validera data
    $plan_name = trim($_POST['plan_name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $billing_cycle = $_POST['billing_cycle'];
    $duration = (int)$_POST['duration'];
    
    // Validera obligatoriska fält
    if (empty($plan_name)) {
        $errors[] = "Abonnemangsnamn är obligatoriskt";
    }
    
    if (empty($price) || !is_numeric(str_replace(',', '.', $price))) {
        $errors[] = "Pris måste vara ett giltigt nummer";
    } else {
        // Konvertera till korrekt decimalformat
        $price = str_replace(',', '.', $price);
    }
    
    if ($duration < 1) {
        $errors[] = "Varaktighet måste vara minst 1";
    }
    
    // Om inga fel, lägg till abonnemangstypen
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO subscription_plans 
            (user_id, plan_name, description, price, billing_cycle, duration, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $result = $stmt->execute([
            $user_id, $plan_name, $description, $price, $billing_cycle, $duration
        ]);
        
        if ($result) {
            $_SESSION['success_message'] = "Abonnemangstypen har lagts till!";
            header("Location: subscription_plans.php");
            exit();
        } else {
            $errors[] = "Ett fel uppstod när abonnemangstypen skulle läggas till. Försök igen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skapa abonnemangstyp - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Skapa ny abonnemangstyp</h1>
        
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
            <div class="form-group">
                <label for="plan_name">Abonnemangsnamn: <span class="required">*</span></label>
                <input type="text" id="plan_name" name="plan_name" value="<?php echo isset($_POST['plan_name']) ? htmlspecialchars($_POST['plan_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Beskrivning:</label>
                <textarea id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price">Pris: <span class="required">*</span></label>
                    <input type="text" id="price" name="price" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="billing_cycle">Faktureringscykel: <span class="required">*</span></label>
                    <select id="billing_cycle" name="billing_cycle">
                        <option value="monthly" <?php echo (isset($_POST['billing_cycle']) && $_POST['billing_cycle'] === 'monthly') ? 'selected' : ''; ?>>Månadsvis</option>
                        <option value="quarterly" <?php echo (isset($_POST['billing_cycle']) && $_POST['billing_cycle'] === 'quarterly') ? 'selected' : ''; ?>>Kvartalsvis</option>
                        <option value="biannually" <?php echo (isset($_POST['billing_cycle']) && $_POST['billing_cycle'] === 'biannually') ? 'selected' : ''; ?>>Halvårsvis</option>
                        <option value="annually" <?php echo (isset($_POST['billing_cycle']) && $_POST['billing_cycle'] === 'annually') ? 'selected' : ''; ?>>Årsvis</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="duration">Varaktighet (antal perioder):</label>
                    <input type="number" id="duration" name="duration" min="1" value="<?php echo isset($_POST['duration']) ? (int)$_POST['duration'] : 1; ?>">
                    <small>Antal perioder som abonnemanget varar (t.ex. 12 månader för årslicens)</small>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Spara</button>
                <a href="subscription_plans.php" class="btn btn-secondary">Avbryt</a>
            </div>
        </form>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
</body>
</html>