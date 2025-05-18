<?php
session_start();
require_once 'DBConnection.php';
$conn = getDatabaseConnection();

// Ellenőrizze, hogy be van-e jelentkezve a felhasználó
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Felhasználó saját cikkeinek betöltése
$stmt = $conn->prepare("SELECT Cikk.id, Cikk.cim, Cikk.tartalom, Kategoria.nev AS kategoria FROM Cikk JOIN Kategoria ON Cikk.kategoria_id = Kategoria.id WHERE letrehozta_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Saját cikkeim</title>
    <link rel="stylesheet" href="styles-css.css">
</head>
<body>
    <div class="container">
        <h2>Saját cikkeim</h2>

        <?php if (count($articles) > 0) : ?>
            <?php foreach ($articles as $article) : ?>
                <section class="featured-article">
                    <h3><?php echo htmlspecialchars($article['CIM']); ?></h3>
                    <p><strong>Kategória:</strong> <?php echo htmlspecialchars($article['KATEGORIA']); ?></p>
                    <div class="form-actions">
                        <a href="edit_article.php?id=<?php echo $article['ID']; ?>" class="btn btn-primary">Módosítás</a>
                        <a href="delete_article.php?id=<?php echo $article['ID']; ?>" class="btn" onclick="return confirm('Biztosan törlöd ezt a cikket?');">Törlés</a>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Nincs még saját cikked.</p>
        <?php endif; ?>

    </div>
</body>
</html>
