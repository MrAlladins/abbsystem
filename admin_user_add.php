<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kontrollera att användaren är inloggad och är admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$db = getDbConnection();
$errors = [];

// Hantera formulärinskick
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hämta och validera användardata
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'];
    
    // Validera användarnamn
    if (empty($username)) {
        $errors[] = "Användarnamn krävs";
    } elseif (strlen($username) < 3) {
        $errors[] = "Användarnamn måste vara minst 3 tecken";
    }
    
    // Validera e-post
    if (empty($email)) {
        $errors[] = "E-post krävs";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ogiltig e-postadress";
    }
    
    // Kontrollera om användarnamn eller e-post redan finns
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Användarnamn eller e-post finns redan registrerat";
    }
    
    // Validera lösenord
    if (empty($password)) {
        $errors[] = "Lösenord krävs";
    } elseif (strlen($password) < 6) {
        $errors[] = "Lösenord måste vara minst 6 tecken";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Lösenorden matchar inte";
    }
    
    // Kontrollera att rollen är giltig
    if (!in_array($role, ['user', 'manager', 'admin'])) {
        $errors[] = "Ogiltig roll";
    }
    
    // Om inga fel, registrera användaren
    if (empty($errors)) {
        // Hasha lösenordet
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Spara användaren i databasen
        $stmt = $db->prepare("INSERT INTO users (username, password, email, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $role]);
        
        if ($result) {
            $_SESSION['success_message'] = "Användaren har lagts till!";
            header("Location: admin_users.php");
            exit();
        } else {
            $errors[] = "Ett fel uppstod vid registrering. Försök igen.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lägg till användare - Abonnemangssystem</title>
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>
    <?php include 'views/layouts/header.php'; ?>
    
    <div class="container">
        <h1>Lägg till ny användare</h1>
        
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
                <label for="username">Användarnamn: <span class="required">*</span></label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">E-post: <span class="required">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Förnamn:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Efternamn:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="role">Roll: <span class="required">*</span></label>
                <select id="role" name="role" required>
                    <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] === 'user') ? 'selected' : ''; ?>>Användare</option>
                    <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] === 'manager') ? 'selected' : ''; ?>>Återförsäljare</option>
                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Administratör</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Lösenord: <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Bekräfta lösenord: <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Lägg till användare</button>
                <a href="admin_users.php" class="btn btn-secondary">Avbryt</a>
            </div>
        </form>
    </div>
    
    <?php include 'views/layouts/footer.php'; ?>
</body>
</html>