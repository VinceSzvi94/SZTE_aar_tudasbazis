<?php
/**
 * Jelszó migráció kezelő script
 * 
 * Ez a fájl frissíti az adatbázisban található plain text jelszavakat,
 * biztonságos, hash-elt jelszavakká a PHP password_hash() függvény használatával.
 * Csak azokat a jelszavakat hash-eli, amelyek még nem voltak hash-elve korábban.
 */

// Adatbázis kapcsolódás PDO használatával
try {
    require_once 'DBConnection.php';
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Adatbázis kapcsolódási hiba: " . $e->getMessage();
    exit;
}

/**
 * Ellenőrzi, hogy egy jelszó már hash-elt-e
 * 
 * @param string $password A vizsgálandó jelszó
 * @return bool Igaz, ha a jelszó már hash-elt
 */
function isHashed($password) {
    // A password_hash általában $2y$ karakterekkel kezdődő jelszavakat állít elő (bcrypt)
    return (strpos($password, '$2y$') === 0);
}

$passwordsUpdated = 0;
$alreadyHashed = 0;

// Felhasználói táblában lévő jelszavak frissítése
$query = "SELECT ID, Jelszo FROM Felhasznalo";
$stmt = $pdo->query($query);

while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentPassword = $user['JELSZO'];
    
    // Csak akkor hash-elünk, ha a jelszó még nincs hash-elve
    if (!isHashed($currentPassword)) {
        $hashedPassword = password_hash($currentPassword, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE Felhasznalo SET Jelszo = :hashedPassword WHERE ID = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':hashedPassword', $hashedPassword);
        $updateStmt->bindParam(':id', $user['ID']);
        $updateStmt->execute();
        
        echo "Frissítve a jelszó a következő felhasználó ID-hoz: " . $user['ID'] . "<br>";
        $passwordsUpdated++;
    } else {
        $alreadyHashed++;
    }
}

// Ugyanezt elvégezzük az Admin táblához is
$query = "SELECT ID, Jelszo FROM Admin";
$stmt = $pdo->query($query);

while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentPassword = $admin['JELSZO'];
    
    // Csak akkor hash-elünk, ha a jelszó még nincs hash-elve
    if (!isHashed($currentPassword)) {
        $hashedPassword = password_hash($currentPassword, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE Admin SET Jelszo = :hashedPassword WHERE ID = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':hashedPassword', $hashedPassword);
        $updateStmt->bindParam(':id', $admin['ID']);
        $updateStmt->execute();
        
        echo "Frissítve a jelszó a következő admin ID-hoz: " . $admin['ID'] . "<br>";
        $passwordsUpdated++;
    } else {
        $alreadyHashed++;
    }
}

// Eredmény összegzése
echo "<br><strong>Jelszó migráció összefoglaló:</strong><br>";
echo "Frissített jelszavak: $passwordsUpdated<br>";
echo "Már hash-elt jelszavak: $alreadyHashed<br>";

if ($passwordsUpdated == 0) {
    echo "<br><strong>Minden jelszó már hash-elve van.</strong>";
} else {
    echo "<br><strong>Jelszó migráció sikeresen befejeződött!</strong>";
}
?>