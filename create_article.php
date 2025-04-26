<?php
session_start();
require_once 'DBConnection.php';

// Ellenőrizze, hogy be van-e jelentkezve a felhasználó
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ha beküldték az új cikket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = intval($_POST['category_id']);

    $stmt = $conn->prepare("INSERT INTO Cikk (cim, tartalom, letrehozta_id, kategoria_id, van_e_lektoralva) VALUES (:title, :content, :user_id, :category_id, 0)");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':category_id', $category_id);

    if ($stmt->execute()) {
        $success_message = "Cikk sikeresen létrehozva.";
    } else {
        $error_message = "Hiba történt a cikk létrehozása során.";
    }
}

// Kategoriák betöltése a legördülőhöz
$categories = $conn->query("SELECT id, nev FROM Kategoria")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Új cikk létrehozása</title>
    <link rel="stylesheet" href="styles-css.css">
</head>
<body>
    <div class="container">
        <h2>Új cikk létrehozása</h2>

        <?php if (isset($success_message)) : ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="title">Cím:</label>
                <input type="text" name="title" id="title" required>
            </div>

            <div class="form-group">
                <label for="content">Tartalom:</label>
                <textarea name="content" id="content" rows="8" required></textarea>
            </div>

            <div class="form-group">
                <label for="category_id">Kategória:</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Válassz kategóriát</option>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo $category['ID']; ?>"><?php echo htmlspecialchars($category['NEV']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Mentés</button>
            </div>
        </form>
    </div>
</body>
</html>
