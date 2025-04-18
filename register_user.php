<?php
/**
 * Felhasználói regisztráció feldolgozó oldal
 * 
 * Ez a fájl dolgozza fel a regisztrációs űrlapról érkező adatokat,
 * validálja azokat, hash-eli a jelszót és létrehozza az új felhasználói fiókot.
 */

// Munkamenet indítása
session_start();

// Átirányítás, ha már bejelentkezett
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'DBConnection.php';

$connection = getDatabaseConnection();
$error_message = '';
$success = false;

// Regisztrációs űrlap feldolgozása
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Űrlap adatok beolvasása
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $city = isset($_POST['city']) ? trim($_POST['city']) : null;
    $street_name = isset($_POST['street_name']) ? trim($_POST['street_name']) : null;
    $street_type = isset($_POST['street_type']) ? trim($_POST['street_type']) : null;
    $house_number = isset($_POST['house_number']) ? trim($_POST['house_number']) : null;
    
    // Űrlap adatok ellenőrzése
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Minden kötelező mezőt ki kell tölteni.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'A megadott jelszavak nem egyeznek.';
    } elseif (strlen($password) < 6) {
        $error_message = 'A jelszónak legalább 6 karakter hosszúnak kell lennie.';
    } else {
        // Email cím foglaltságának ellenőrzése
        $checkQuery = "SELECT COUNT(*) as count FROM Felhasznalo WHERE Email = :email";
        $checkStmt = $connection->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['COUNT'] > 0) {
            $error_message = 'Ez az email cím már használatban van.';
        } else {
            // Jelszó hash-elése
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                // Következő ID lekérése a szekvenciából
                $seqQuery = "SELECT felhasznalo_seq.NEXTVAL as next_id FROM DUAL";
                $seqStmt = $connection->query($seqQuery);
                $seqResult = $seqStmt->fetch(PDO::FETCH_ASSOC);
                $userId = $seqResult['NEXT_ID'];
                
                // Új felhasználó beszúrása
                $insertQuery = "INSERT INTO Felhasznalo (ID, Nev, Email, Jelszo, Varos, Kozterulet_nev, Kozterulet_tipus, Hazszam) 
                                VALUES (:id, :nev, :email, :jelszo, :varos, :kozterulet_nev, :kozterulet_tipus, :hazszam)";
                                
                $insertStmt = $connection->prepare($insertQuery);
                $insertStmt->bindParam(':id', $userId);
                $insertStmt->bindParam(':nev', $name);
                $insertStmt->bindParam(':email', $email);
                $insertStmt->bindParam(':jelszo', $hashed_password);
                $insertStmt->bindParam(':varos', $city);
                $insertStmt->bindParam(':kozterulet_nev', $street_name);
                $insertStmt->bindParam(':kozterulet_tipus', $street_type);
                $insertStmt->bindParam(':hazszam', $house_number);
                $insertStmt->execute();
                
                // Regisztráció sikeres
                $success = true;
                
            } catch (PDOException $e) {
                $error_message = 'Hiba történt a regisztráció során: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció - Tudásbázis</title>
    <link rel="stylesheet" href="styles.css">
    <meta http-equiv="refresh" content="5;url=login.php">
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
            <h2>Regisztráció feldolgozása</h2>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <p><?php echo $error_message; ?></p>
                    <p>Visszairányítás a regisztrációs oldalra 5 másodperc múlva...</p>
                    <p><a href="register.php">Kattints ide, ha nem akarsz várni.</a></p>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">
                    <p>Sikeres regisztráció! Most már bejelentkezhetsz.</p>
                    <p>Átirányítás a bejelentkezési oldalra 5 másodperc múlva...</p>
                    <p><a href="login.php">Kattints ide, ha nem akarsz várni.</a></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>

<?php
// Adatbázis kapcsolat lezárása
$connection = null;
?>