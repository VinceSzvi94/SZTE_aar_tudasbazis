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
    echo "Nincs ilyen cikk vagy nincs jogosultságod törölni.";
    exit();
}

// Törlés
$deleteStmt = $conn->prepare("DELETE FROM Cikk WHERE id = :id");
$deleteStmt->bindParam(':id', $article_id);

if ($deleteStmt->execute()) {
    header('Location: my_articles.php');
    exit();
} else {
    echo "Hiba történt a cikk törlése során.";
}
?>
