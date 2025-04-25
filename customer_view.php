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

// Kontrollera om kund-ID finns
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = $_GET['id'];

// Hämta kundinformation
$stmt = $db->prepare("
    SELECT * FROM customers 
    WHERE customer_id = ? AND user_id = ?
");
$stmt->execute([$customer_id, $user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Om kunden inte finns eller inte tillhör användaren
if (!$customer) {
    header("Location: customers.php");
    exit();
}

// Hämta kundens abonnemang
$stmt = $db->prepare("
    SELECT s.*, p.plan_name, p.price, p.billing_cycle 
    FROM subscriptions s
    JOIN subscription_plans p ON s.plan_id = p.plan_id
    WHERE s.customer_id = ?
    ORDER BY s.status, s.end_date DESC
");
$stmt->execute([$customer_id]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hämta betalningshistorik
$stmt = $db->prepare("
    SELECT py.*, s.subscription_id, p.plan_name 
    FROM payments py
    JOIN subscriptions s ON py.subscription_id = s.subscription_id
    JOIN subscription_plans p ON s.plan_id = p.plan_id
    WHERE s.customer_id = ?
    ORDER BY py.payment_date DESC
    LIMIT 10
");
$stmt->execute([$customer_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?> - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h1>
            
            <div class="header-actions">
                <a href="customer_edit.php?id=<?php echo $customer_id; ?>" class="btn">Redigera</a>
                <a href="subscription_add.php?customer_id=<?php echo $customer_id; ?>" class="btn">Lägg till abonnemang</a>
            </div>
        </div>
        
        <div class="customer-details">
            <div class="customer-info">
                <h2>Kunduppgifter</h2>
                
                <div class="info-group">
                    <p><strong>E-post:</strong> <?php echo htmlspecialchars($customer['email'] ?: '-'); ?></p>
                    <p><strong>Telefon:</strong> <?php echo htmlspecialchars($customer['phone'] ?: '-'); ?></p>
                </div>
                
                <div class="info-group">
                    <p><strong>Adress:</strong> <?php echo htmlspecialchars($customer['address'] ?: '-'); ?></p>
                    <p><strong>Postnummer & ort:</strong> <?php echo htmlspecialchars(($customer['postal_code'] ? $customer['postal_code'] . ' ' : '') . ($customer['city'] ?: '-')); ?></p>
                    <p><strong>Land:</strong> <?php echo htmlspecialchars($customer['country'] ?: '-'); ?></p>
                </div>
                
                <?php if (!empty($customer['notes'])): ?>
                <div class="info-group">
                    <p><strong>Anteckningar:</strong></p>
                    <div class="notes-box"><?php echo nl2br(htmlspecialchars($customer['notes'])); ?></div>
                </div>
                <?php endif; ?>
                
                <p><strong>Kund sedan:</strong> <?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></p>
            </div>
        </div>
        
        <div class="section">
            <h2>Abonnemang</h2>
            
            <?php if (empty($subscriptions)): ?>
                <p>Denna kund har inga abonnemang ännu.</p>
                <a href="subscription_add.php?customer_id=<?php echo $customer_id; ?>" class="btn">Lägg till abonnemang</a>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Abonnemang</th>
                            <th>Pris</th>
                            <th>Faktureringscykel</th>
                            <th>Startdatum</th>
                            <th>Slutdatum</th>
                            <th>Status</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr class="<?php echo $subscription['status']; ?>">
                                <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                                <td><?php echo formatCurrency($subscription['price']); ?></td>
                                <td><?php echo getSubscriptionCycleText($subscription['billing_cycle']); ?></td>
                                <td><?php echo formatDate($subscription['start_date']); ?></td>
                                <td><?php echo $subscription['end_date'] ? formatDate($subscription['end_date']) : '-'; ?></td>
                                <td><?php echo getSubscriptionStatusText($subscription['status']); ?></td>
                                <td>
                                    <a href="subscription_view.php?id=<?php echo $subscription['subscription_id']; ?>" class="btn-small">Visa</a>
                                    <?php if ($subscription['status'] === 'active'): ?>
                                        <a href="subscription_renew.php?id=<?php echo $subscription['subscription_id']; ?>" class="btn-small">Förnya</a>
                                        <a href="payment_add.php?subscription_id=<?php echo $subscription['subscription_id']; ?>" class="btn-small">Registrera betalning</a>
                                    <?php elseif ($subscription['status'] === 'expired'): ?>
                                        <a href="subscription_reactivate.php?id=<?php echo $subscription['subscription_id']; ?>" class="btn-small">Återaktivera</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>Betalningshistorik</h2>
            
            <?php if (empty($payments)): ?>
                <p>Ingen betalningshistorik finns för denna kund.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Abonnemang</th>
                            <th>Belopp</th>
                            <th>Betalningsmetod</th>
                            <th>Status</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['plan_name']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method'] ?: '-'); ?></td>
                                <td><?php echo getPaymentStatusText($payment['status']); ?></td>
                                <td>
                                    <a href="payment_view.php?id=<?php echo $payment['payment_id']; ?>" class="btn-small">Visa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p><a href="payments.php?customer_id=<?php echo $customer_id; ?>">Visa all betalningshistorik</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
    
    <script>
    // Enkel JavaScript för att bekräfta borttagning av kund
    document.addEventListener('DOMContentLoaded', function() {
        const deleteLinks = document.querySelectorAll('.delete-link');
        deleteLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Är du säker på att du vill ta bort denna kund? Alla tillhörande abonnemang och betalningar kommer också att tas bort.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>