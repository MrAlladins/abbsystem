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

// Kontrollera om kund-ID finns
if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = $_GET['customer_id'];

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

// Hämta tillgängliga abonnemangstyper
$stmt = $db->prepare("
    SELECT * FROM subscription_plans 
    WHERE user_id = ? AND is_active = 1
    ORDER BY plan_name
");
$stmt->execute([$user_id]);
$subscription_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Om det inte finns några abonnemangstyper
if (empty($subscription_plans)) {
    $_SESSION['warning_message'] = "Du måste först skapa minst en abonnemangstyp.";
    header("Location: subscription_plan_add.php");
    exit();
}

// Hantera formulärinskick
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hämta och validera data
    $plan_id = (int)$_POST['plan_id'];
    $start_date = trim($_POST['start_date']);
    $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;
    $notes = trim($_POST['notes']);
    
    // Validera obligatoriska fält
    if (empty($plan_id)) {
        $errors[] = "Välj en abonnemangstyp";
    }
    
    if (empty($start_date)) {
        $errors[] = "Startdatum är obligatoriskt";
    } elseif (!validateDate($start_date)) {
        $errors[] = "Ogiltigt startdatum";
    }
    
    // Hämta abonnemangstyp för att beräkna slutdatum
    if (!empty($plan_id)) {
        $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_id = ? AND user_id = ?");
        $stmt->execute([$plan_id, $user_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            $errors[] = "Ogiltig abonnemangstyp";
        } else {
            // Beräkna slutdatum
            $end_date = calculateEndDate($start_date, $plan['billing_cycle'], $plan['duration']);
            
            // Beräkna nästa faktureringsdatum
            $next_billing_date = $auto_renew ? $end_date : null;
        }
    }
    
    // Om inga fel, lägg till abonnemanget
    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO subscriptions 
            (customer_id, plan_id, start_date, end_date, status, auto_renew, next_billing_date, notes) 
            VALUES (?, ?, ?, ?, 'active', ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $customer_id, $plan_id, $start_date, $end_date, $auto_renew, $next_billing_date, $notes
        ]);
        
        if ($result) {
            $subscription_id = $db->lastInsertId();
            $_SESSION['success_message'] = "Abonnemanget har lagts till!";
            
            // Omdirigera till betalningsregistrering eller tillbaka till kunden
            if (isset($_POST['add_payment'])) {
                header("Location: payment_add.php?subscription_id=" . $subscription_id);
            } else {
                header("Location: customer_view.php?id=" . $customer_id);
            }
            exit();
        } else {
            $errors[] = "Ett fel uppstod när abonnemanget skulle läggas till. Försök igen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lägg till abonnemang - <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Lägg till abonnemang</h1>
        <h2>Kund: <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h2>
        
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
                <label for="plan_id">Abonnemangstyp: <span class="required">*</span></label>
                <select id="plan_id" name="plan_id" required>
                    <option value="">Välj abonnemangstyp</option>
                    <?php foreach ($subscription_plans as $plan): ?>
                        <option value="<?php echo $plan['plan_id']; ?>" <?php echo (isset($_POST['plan_id']) && $_POST['plan_id'] == $plan['plan_id']) ? 'selected' : ''; ?> data-price="<?php echo $plan['price']; ?>" data-cycle="<?php echo $plan['billing_cycle']; ?>" data-duration="<?php echo $plan['duration']; ?>">
                            <?php echo htmlspecialchars($plan['plan_name']); ?> (<?php echo formatCurrency($plan['price']); ?> / <?php echo getSubscriptionCycleText($plan['billing_cycle']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Startdatum: <span class="required">*</span></label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date_preview">Slutdatum (beräknat):</label>
                    <input type="text" id="end_date_preview" readonly>
                    <small>Slutdatum beräknas automatiskt baserat på abonnemangstyp och startdatum</small>
                </div>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="auto_renew" name="auto_renew" <?php echo (!isset($_POST['auto_renew']) || $_POST['auto_renew']) ? 'checked' : ''; ?>>
                <label for="auto_renew">Automatisk förnyelse</label>
                <small>Abonnemanget förnyas automatiskt vid slutdatum</small>
            </div>
            
            <div class="form-group">
                <label for="notes">Anteckningar:</label>
                <textarea id="notes" name="notes" rows="4"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Spara</button>
                <button type="submit" name="add_payment" value="1" class="btn">Spara och registrera betalning</button>
                <a href="customer_view.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">Avbryt</a>
            </div>
        </form>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funktion för att beräkna slutdatum
        function calculateEndDate(startDate, billingCycle, duration) {
            const date = new Date(startDate);
            
            if (!date.getTime()) {
                return '';
            }
            
            switch (billingCycle) {
                case 'monthly':
                    date.setMonth(date.getMonth() + parseInt(duration));
                    break;
                case 'quarterly':
                    date.setMonth(date.getMonth() + (parseInt(duration) * 3));
                    break;
                case 'biannually':
                    date.setMonth(date.getMonth() + (parseInt(duration) * 6));
                    break;
                case 'annually':
                    date.setFullYear(date.getFullYear() + parseInt(duration));
                    break;
                default:
                    date.setMonth(date.getMonth() + parseInt(duration));
            }
            
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            return `${year}-${month}-${day}`;
        }
        
        // Uppdatera slutdatum när abonnemangstyp eller startdatum ändras
        function updateEndDate() {
            const planSelect = document.getElementById('plan_id');
            const startDateInput = document.getElementById('start_date');
            const endDatePreview = document.getElementById('end_date_preview');
            
            if (planSelect.selectedIndex > 0 && startDateInput.value) {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                const billingCycle = selectedOption.getAttribute('data-cycle');
                const duration = selectedOption.getAttribute('data-duration');
                
                const endDate = calculateEndDate(startDateInput.value, billingCycle, duration);
                endDatePreview.value = endDate;
            } else {
                endDatePreview.value = '';
            }
        }
        
        // Lyssna på ändringar i abonnemangstyp och startdatum
        document.getElementById('plan_id').addEventListener('change', updateEndDate);
        document.getElementById('start_date').addEventListener('change', updateEndDate);
        
        // Beräkna slutdatum när sidan laddas
        updateEndDate();
    });
    </script>
</body>
</html>