<?php
// Start session for user authentication
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'DBConnection.php';

$connection = getDatabaseConnection();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle registration form submission via POST to register_user.php
    header('Location: register_user.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció - Tudásbázis</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Tudásbázis Logo">
            <h1>Tudásbázis</h1>
        </div>
        <div class="user-panel">
            <a href="login.php" class="btn">Bejelentkezés</a>
        </div>
    </header>
    
    <main class="container login-container">
        <div class="login-form">
            <h2>Regisztráció</h2>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="register_user.php">
                <div class="form-group">
                    <label for="name">Teljes név</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email cím</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Jelszó megerősítése</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="city">Város</label>
                    <input type="text" id="city" name="city">
                </div>
                
                <div class="form-group">
                    <label for="street_name">Közterület neve</label>
                    <input type="text" id="street_name" name="street_name">
                </div>
                
                <div class="form-group">
                    <label for="street_type">Közterület típusa</label>
                    <input type="text" id="street_type" name="street_type">
                </div>
                
                <div class="form-group">
                    <label for="house_number">Házszám</label>
                    <input type="text" id="house_number" name="house_number">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Regisztráció</button>
                    <a href="index.php" class="btn">Vissza a főoldalra</a>
                </div>
            </form>
            
            <div class="registration-link">
                <p>Már van fiókod? <a href="login.php">Jelentkezz be!</a></p>
            </div>
        </div>
    </main>

</body>
</html>

<?php
// Close database connection
$connection = null;
?>