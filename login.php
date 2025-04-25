<?php
// Aktivera felrapportering
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Starta sessionen
session_start();

// Inkludera databasanslutning
require_once 'config/database.php';

// Om användaren redan är inloggad, omdirigera till dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Fel- och meddelande-variabler
$error = '';
$success = '';

// Hantera inloggningsförsök
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Anslut till databasen
        $db = getDbConnection();
        
        // Hämta användardata
        $username_input = trim($_POST['username']);
        $password_input = $_POST['password'];
        
        // Validera indata (enkel validering)
        if (empty($username_input) || empty($password_input)) {
            $error = "Användarnamn och lösenord krävs";
        } else {
            // Hämta användare från databasen
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_input, $username_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kontrollera om användaren finns och lösenordet är korrekt
            if ($user && password_verify($password_input, $user['password'])) {
                // Spara användarinformation i session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Uppdatera senaste inloggning
                $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update->execute([$user['user_id']]);
                
                // Omdirigera baserat på användarroll
                if ($user['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Felaktigt användarnamn eller lösenord";
            }
        }
    } catch (PDOException $e) {
        $error = "Databasfel: " . $e->getMessage();
    }
}
?>

<!-- Resten av HTML-koden är oförändrad -->

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logga in - Abonnemangssystem</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .error {
            color: red;
            background-color: #ffecec;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            color: green;
            background-color: #e7f6e7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        button {
            background-color: #4a6fa5;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        
        button:hover {
            background-color: #3a5a8a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Logga in</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Användarnamn eller e-post:</label>
                <input type="text" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Lösenord:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Logga in</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">Har du inget konto? Kontakta administratören.</p>
    </div>
</body>
</html>