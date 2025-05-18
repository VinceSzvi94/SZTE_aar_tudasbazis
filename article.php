<?php
session_start();
require_once 'DBConnection.php';

// Ellenőrizze, hogy van-e cikk ID az URL-ben
if (!isset($_GET['id'])) {
    echo "Hiányzó cikk azonosító.";
    exit();
}

$conn = getDatabaseConnection();

$article_id = intval($_GET['id']);

// Cikk adatainak betöltése
$stmt = $conn->prepare("SELECT c.CIM, c.TARTALOM, c.LETREHOZAS_DATUM, k.NEV as KATEGORIA, f.NEV as SZERZO FROM Cikk c JOIN Kategoria k ON c.KATEGORIA_ID = k.ID JOIN Felhasznalo f ON c.LETREHOZTA_ID = f.ID WHERE c.ID = :id");
$stmt->bindParam(':id', $article_id);
$stmt->execute();
$article = $stmt->fetch(PDO::FETCH_ASSOC);

// Ha a tartalom CLOB/resource, sztringé alakítás
if (is_resource($article['TARTALOM'])) {
    $article['TARTALOM'] = stream_get_contents($article['TARTALOM']);
}

if (!$article) {
    echo "A keresett cikk nem létezik.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($article['CIM']); ?></title>
    <link rel="stylesheet" href="styles-css.css">
</head>
<body>
    <div class="container">
        <h2><?php echo htmlspecialchars($article['CIM']); ?></h2>
        <p><strong>Kategória:</strong> <?php echo htmlspecialchars($article['KATEGORIA']); ?></p>
        <p><strong>Szerző:</strong> <?php echo htmlspecialchars($article['SZERZO']); ?></p>
        <p><strong>Létrehozva:</strong> <?php echo htmlspecialchars($article['LETREHOZAS_DATUM']); ?></p>
        <hr>
        <p><?php echo nl2br(htmlspecialchars($article['TARTALOM'])); ?></p>
        <br>
        <a href="index.php" class="btn">Vissza a főoldalra</a>
    </div>
</body>
</html>
