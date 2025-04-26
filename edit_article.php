<?php
session_start();
require_once 'DBConnection.php';

// Ellenőrizze, hogy be van-e jelentkezve a felhasználó
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    echo "Hiányzó cikk azonosító.";
    exit();
}

$article_id = intval($_GET['id']);

// Ellenőrizzük, hogy a cikk a jelenlegi felhasználóé
$stmt = $conn->prepare("SELECT * FROM Cikk WHERE id = :id AND letrehozta_id = :user_id");
$stmt->bindParam(':id', $article_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    echo "Nincs ilyen cikk vagy nincs jogosultságod szerkeszteni.";
    exit();
}

// Kategóriák betöltése
$categories = $conn->query("SELECT id, nev FROM Kategoria")->fetchAll(PDO::FETCH_ASSOC);

// Ha beküldték a módosítást
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = intval($_POST['category_id']);

    $updateStmt = $conn->prepare("UPDATE Cikk SET cim = :title, tartalom = :content, kategoria_id = :category_id, modositas_datum = SYSDATE, van_e_lektoralva = 0 WHERE id = :id");
    $updateStmt->bindParam(':title', $title);
    $updateStmt->bindParam(':content', $content);
    $updateStmt->bindParam(':category_id', $category_id);
    $updateStmt->bindParam(':id', $article_id);

    if ($updateStmt->execute()) {
        $success_message = "Cikk sikeresen frissítve.";
        // Friss adat betöltése
        $stmt->execute();
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error_message = "Hiba történt a frissítés során.";
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Cikk módosítása</title>
    <link rel="stylesheet" href="styles-css.css">
</head>
<body>
    <div class="container">
        <h2>Cikk módosítása</h2>

        <?php if (isset($success_message)) : ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="title">Cím:</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($article['CIM']); ?>" required>
            </div>

            <div class="form-group">
                <label for="content">Tartalom:</label>
                <textarea name="content" id="content" rows="8" required><?php echo htmlspecialchars($article['TARTALOM']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="category_id">Kategória:</label>
                <select name="category_id" id="category_id" required>
                    <?php foreach ($categories as $category) : ?>
                        <option value="<?php echo $category['ID']; ?>" <?php if ($category['ID'] == $article['KATEGORIA_ID']) echo 'selected'; ?>><?php echo htmlspecialchars($category['NEV']); ?></option>
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
