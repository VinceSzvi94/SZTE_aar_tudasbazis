<?php
session_start();
require_once 'DBConnection.php';

// Ellenőrizze, hogy be van-e jelentkezve a felhasználó
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ha beküldték a módosítást
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $city = trim($_POST['city']);
    $street_name = trim($_POST['street_name']);
    $street_type = trim($_POST['street_type']);
    $house_number = trim($_POST['house_number']);

    $stmt = $conn->prepare("UPDATE Felhasznalo SET nev = :name, email = :email, varos = :city, kozterulet_neve = :street_name, kozterulet_tipusa = :street_type, hazszam = :house_number WHERE id = :id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':city', $city);
    $stmt->bindParam(':street_name', $street_name);
    $stmt->bindParam(':street_type', $street_type);
    $stmt->bindParam(':house_number', $house_number);
    $stmt->bindParam(':id', $user_id);

    if ($stmt->execute()) {
        $success_message = "Profil sikeresen frissítve.";
    } else {
        $error_message = "Hiba történt a frissítés során.";
    }
}

// Felhasználó adatok betöltése
$stmt = $conn->prepare("SELECT * FROM Felhasznalo WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Nem található a felhasználó.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Profil szerkesztése</title>
    <link rel="stylesheet" href="styles-css.css">
</head>
<body>
    <div class="container">
        <h2>Profil szerkesztése</h2>

        <?php if (isset($success_message)) : ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="name">Név:</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['NEV']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['EMAIL']); ?>" required>
            </div>

            <div class="form-group">
                <label for="city">Város:</label>
                <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($user['VAROS']); ?>">
            </div>

            <div class="form-group">
                <label for="street_name">Közterület neve:</label>
                <input type="text" name="street_name" id="street_name" value="<?php echo htmlspecialchars($user['KOZTERULET_NEVE']); ?>">
            </div>

            <div class="form-group">
                <label for="street_type">Közterület típusa:</label>
                <input type="text" name="street_type" id="street_type" value="<?php echo htmlspecialchars($user['KOZTERULET_TIPUSA']); ?>">
            </div>

            <div class="form-group">
                <label for="house_number">Házszám:</label>
                <input type="text" name="house_number" id="house_number" value="<?php echo htmlspecialchars($user['HAZSZAM']); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Mentés</button>
            </div>
        </form>
    </div>
</body>
</html>
