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

// Kontrollera om abonnemangs-ID finns
if (!isset($_GET['subscription_id']) || !is_numeric($_GET['subscription_id'])) {
    header("Location: subscriptions.php");
    exit();
}

$subscription_id = $_GET['subscription_id'];

// Hämta abonnemangsinformation
$stmt = $db->prepare("
    SELECT s.*, c.customer_id, c.first_name, c.last_name, p.plan_name, p.price 
    FROM subscriptions s
    JOIN customers c ON s.customer_id = c.customer_id
    JOIN subscription_plans p ON s.plan_id = p.plan_id
    WHERE s.subscription_id = ? AND c.user_id = ?
");
$stmt->execute([$subscription_id, $user_id]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Om abonnemanget inte finns eller inte tillhör användarens kunder
if (!$subscription) {
    header("Location: subscriptions.php");
    exit();
}

// Hantera formulärinskick
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hämta och validera data
    $amount = trim($_POST['amount']);
    $payment_date = trim($_POST['payment_date']);
    $payment_method = trim($_POST['payment_method']);
    $transaction_id = trim($_POST['transaction_id']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    // Validera obligatoriska fält
    if (empty($amount) || !is_numeric(str_replace(',', '.', $amount))) {
        $errors[] = "Belopp måste vara ett giltigt nummer";
    } else {
        // Konvertera till korrekt decimalformat
        $amount = str_replace(',', '.', $amount);
    }
    
    if (empty($payment_date)) {
        $errors[] = "Betalningsdatum är obligatoriskt";
    } elseif (!validateDate($payment_date)) {
        $errors[] = "Ogiltigt betalningsdatum";
    }
    
    // Om inga fel, registrera betalningen
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO payments 
            (subscription_id, amount, payment_date, payment_method, transaction_id, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $subscription_id, $amount, $payment_date, $payment_method, $transaction_id, $status, $notes
        ]);
        
        if ($result) {
            $_SESSION['success_message'] = "Betalningen har registrerats!";
            header("Location: customer_view.php?id=" . $subscription['customer_id']);
            exit();
        } else {
            $errors[] = "Ett fel uppstod när betalningen skulle registreras. Försök igen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrera betalning - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Registrera betalning</h1>
        
        <div class="info-box">
            <h2>Abonnemang: <?php echo htmlspecialchars($subscription['plan_name']); ?></h2>
            <p><strong>Kund:</strong> <?php echo htmlspecialchars($subscription['first_name'] . ' ' . $subscription['last_name']); ?></p>
            <p><strong>Pris:</strong> <?php echo formatCurrency($subscription['price']); ?></p>
            <p><strong>Period:</strong> <?php echo formatDate($subscription['start_date']); ?> - <?php echo $subscription['end_date'] ? formatDate($subscription['end_date']) : 'Tillsvidare'; ?></p>
        </div>
        
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
                    <label for="amount">Belopp: <span class="required">*</span></label>
                    <input type="text" id="amount" name="amount" value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : htmlspecialchars($subscription['price']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_date">Betalningsdatum: <span class="required">*</span></label>
                    <input type="date" id="payment_date" name="payment_date" value="<?php echo isset($_POST['payment_date']) ? htmlspecialchars($_POST['payment_date']) : date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="payment_method">Betalningsmetod:</label>
                    <select id="payment_method" name="payment_method">
                        <option value="bank" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank') ? 'selected' : ''; ?>>Banköverföring</option>
                        <option value="card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'card') ? 'selected' : ''; ?>>Kortbetalning</option>
                        <option value="swish" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'swish') ? 'selected' : ''; ?>>Swish</option>
                        <option value="invoice" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'invoice') ? 'selected' : ''; ?>>Faktura</option>
                        <option value="cash" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? 'selected' : ''; ?>>Kontant</option>
                        <option value="other" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'other') ? 'selected' : ''; ?>>Annan</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="transaction_id">Transaktions-ID:</label>
                    <input type="text" id="transaction_id" name="transaction_id" value="<?php echo isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id']) : ''; ?>">
                    <small>Frivilligt. Referensnummer för betalningen.</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="completed" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'completed') ? 'selected' : ''; ?>>Genomförd</option>
                    <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : ''; ?>>Väntande</option>
                    <option value="failed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'failed') ? 'selected' : ''; ?>>Misslyckad</option>
                    <option value="refunded" <?php echo (isset($_POST['status']) && $_POST['status'] === 'refunded') ? 'selected' : ''; ?>>Återbetald</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notes">Anteckningar:</label>
                <textarea id="notes" name="notes" rows="4"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Registrera betalning</button>
                <a href="customer_view.php?id=<?php echo $subscription['customer_id']; ?>" class="btn btn-secondary">Avbryt</a>
            </div>
        </form>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
</body>
</html>