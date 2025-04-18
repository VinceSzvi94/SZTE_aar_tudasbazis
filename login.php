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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Check if admin login
    if (isset($_POST['admin_login']) && $_POST['admin_login'] == '1') {
        $query = "SELECT ID, Nev, Email, Jelszo FROM Admin WHERE Email = :email";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['JELSZO'])) {
            // Admin login successful
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['user_name'] = $user['NEV'];
            $_SESSION['user_email'] = $user['EMAIL'];
            $_SESSION['is_admin'] = true;
            
            header('Location: admin.php');
            exit;
        } else {
            $error_message = 'Hibás admin bejelentkezési adatok!';
        }
    } else {
        // Regular user login
        $query = "SELECT f.ID, f.Nev, f.Email, f.Jelszo, CASE WHEN l.Felhasznalo_ID IS NOT NULL THEN 1 ELSE 0 END as IsLektor 
                  FROM Felhasznalo f 
                  LEFT JOIN Lektor l ON f.ID = l.Felhasznalo_ID 
                  WHERE f.Email = :email";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['JELSZO'])) {
            // User login successful
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['user_name'] = $user['NEV'];
            $_SESSION['user_email'] = $user['EMAIL'];
            $_SESSION['is_lektor'] = ($user['ISLEKTOR'] == 1);
            
            header('Location: index.php');
            exit;
        } else {
            $error_message = 'Hibás email cím vagy jelszó!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés - Tudásbázis</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Tudásbázis Logo">
            <h1>Tudásbázis</h1>
        </div>
        <div class="user-panel">
            <a href="register.php" class="btn">Regisztráció</a>
        </div>
    </header>
    
    <main class="container login-container">
        <div class="login-form">
            <h2>Bejelentkezés</h2>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="email">Email cím</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Jelszó</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="admin_login" value="1">
                        Admin belépés
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Bejelentkezés</button>
                    <a href="index.php" class="btn">Vissza a főoldalra</a>
                </div>
            </form>
            
            <div class="registration-link">
                <p>Még nincs fiókod? <a href="register.php">Regisztrálj most!</a></p>
            </div>
        </div>
    </main>

</body>
</html>

<?php
// Close database connection
$connection = null;
?>
