<?php
// Database connection using PDO
try {
    require_once 'DBConnection.php';
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
    exit;
}

// Function to check if a password is already hashed
function isHashed($password) {
    // password_hash typically produces strings starting with $2y$ (bcrypt)
    return (strpos($password, '$2y$') === 0);
}

$passwordsUpdated = 0;
$alreadyHashed = 0;

// Update Felhasznalo table passwords
$query = "SELECT ID, Jelszo FROM Felhasznalo";
$stmt = $pdo->query($query);

while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentPassword = $user['JELSZO'];
    
    // Only hash if the password isn't already hashed
    if (!isHashed($currentPassword)) {
        $hashedPassword = password_hash($currentPassword, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE Felhasznalo SET Jelszo = :hashedPassword WHERE ID = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':hashedPassword', $hashedPassword);
        $updateStmt->bindParam(':id', $user['ID']);
        $updateStmt->execute();
        
        echo "Updated password for user ID: " . $user['ID'] . "<br>";
        $passwordsUpdated++;
    } else {
        $alreadyHashed++;
    }
}

// Do the same for Admin table
$query = "SELECT ID, Jelszo FROM Admin";
$stmt = $pdo->query($query);

while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentPassword = $admin['JELSZO'];
    
    // Only hash if the password isn't already hashed
    if (!isHashed($currentPassword)) {
        $hashedPassword = password_hash($currentPassword, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE Admin SET Jelszo = :hashedPassword WHERE ID = :id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':hashedPassword', $hashedPassword);
        $updateStmt->bindParam(':id', $admin['ID']);
        $updateStmt->execute();
        
        echo "Updated password for admin ID: " . $admin['ID'] . "<br>";
        $passwordsUpdated++;
    } else {
        $alreadyHashed++;
    }
}

// Report summary
echo "<br><strong>Password migration summary:</strong><br>";
echo "Passwords updated: $passwordsUpdated<br>";
echo "Already hashed passwords: $alreadyHashed<br>";

if ($passwordsUpdated == 0) {
    echo "<br><strong>All passwords are already hashed.</strong>";
} else {
    echo "<br><strong>Password migration completed successfully!</strong>";
}
?>