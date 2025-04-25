
<?php
/**
 * Säkra utdata för att förhindra XSS-attacker
 */
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Kontrollera om användaren är inloggad
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Kontrollera om användaren har en specifik roll
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Validera datum
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Beräkna slutdatum baserat på startdatum, cykel och duration
 */
function calculateEndDate($start_date, $billing_cycle, $duration) {
    $date = new DateTime($start_date);
    
    switch ($billing_cycle) {
        case 'monthly':
            $date->add(new DateInterval('P' . $duration . 'M'));
            break;
        case 'quarterly':
            $date->add(new DateInterval('P' . ($duration * 3) . 'M'));
            break;
        case 'biannually':
            $date->add(new DateInterval('P' . ($duration * 6) . 'M'));
            break;
        case 'annually':
            $date->add(new DateInterval('P' . $duration . 'Y'));
            break;
        default:
            $date->add(new DateInterval('P' . $duration . 'M'));
    }
    
    return $date->format('Y-m-d');
}

/**
 * Formatera datum till svenskt format
 */
function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

/**
 * Formatera valuta
 */
function formatCurrency($amount) {
    return number_format((float)$amount, 2, ',', ' ') . ' kr';
}

/**
 * Hämta text för faktureringscykel
 */
function getSubscriptionCycleText($cycle) {
    switch ($cycle) {
        case 'monthly':
            return 'Månad';
        case 'quarterly':
            return 'Kvartal';
        case 'biannually':
            return 'Halvår';
        case 'annually':
            return 'År';
        default:
            return ucfirst($cycle);
    }
}

/**
 * Hämta text för abonnemangsstatus
 */
function getSubscriptionStatusText($status) {
    switch ($status) {
        case 'active':
            return 'Aktiv';
        case 'pending':
            return 'Väntande';
        case 'cancelled':
            return 'Avslutad';
        case 'expired':
            return 'Utgått';
        default:
            return ucfirst($status);
    }
}

/**
 * Hämta text för betalningsstatus
 */
function getPaymentStatusText($status) {
    switch ($status) {
        case 'completed':
            return 'Genomförd';
        case 'pending':
            return 'Väntande';
        case 'failed':
            return 'Misslyckad';
        case 'refunded':
            return 'Återbetald';
        default:
            return ucfirst($status);
    }
}

/**
 * Logga ut användaren (logout.php används för detta)
 */
function logoutUser() {
    // Ta bort all sessionsinformation
    $_SESSION = array();
    
    // Om cookies används för sessionen, ta bort dessa
    if (ini_get("session.use_cookies")) {
        $params = session_get
