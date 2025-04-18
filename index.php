<?php
/**
 * Tudásbázis főoldal
 * 
 * Ez a fájl a weboldal főoldalát jeleníti meg,
 * mutatja a legfrissebb cikkeket, kategóriákat és felhasználói opciókat.
 */

// Munkamenet indítása a felhasználói hitelesítéshez
session_start();

require_once 'DBConnection.php';

$connection = getDatabaseConnection();
if ($connection) {
    echo "Kapcsolódás sikeres";
}

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
$isLoggedIn = isset($_SESSION['user_id']);
$isLektor = isset($_SESSION['is_lektor']) && $_SESSION['is_lektor'] == true;
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true;

// Legújabb cikkek lekérése
$latestArticlesQuery = "SELECT c.ID, c.Cim, c.Letrehozas_Datum, k.Nev as Kategoria, f.Nev as Szerzo 
                        FROM Cikk c 
                        LEFT JOIN Kategoria k ON c.Kategoria_ID = k.ID 
                        LEFT JOIN Felhasznalo f ON c.Letrehozta_ID = f.ID 
                        ORDER BY c.Letrehozas_Datum DESC 
                        FETCH FIRST 5 ROWS ONLY";
$latestStmt = $connection->prepare($latestArticlesQuery);
$latestStmt->execute();

// Nemrég módosított cikkek lekérése
$recentlyModifiedQuery = "SELECT c.ID, c.Cim, c.Modositas_Datum, k.Nev as Kategoria, f.Nev as Szerzo 
                          FROM Cikk c 
                          LEFT JOIN Kategoria k ON c.Kategoria_ID = k.ID 
                          LEFT JOIN Felhasznalo f ON c.Letrehozta_ID = f.ID 
                          WHERE c.Modositas_Datum > c.Letrehozas_Datum
                          ORDER BY c.Modositas_Datum DESC 
                          FETCH FIRST 5 ROWS ONLY";
$modifiedStmt = $connection->prepare($recentlyModifiedQuery);
$modifiedStmt->execute();

// Kategóriák lekérése
$categoriesQuery = "SELECT ID, Nev FROM Kategoria ORDER BY Nev";
$categoriesStmt = $connection->prepare($categoriesQuery);
$categoriesStmt->execute();

// Kategóriák tárolása tömbben későbbi felhasználásra
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tudásbázis</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="logo.jpg" alt="Tudásbázis Logo">
            <h1>Tudásbázis</h1>
        </div>
        <div class="search">
            <form action="search.php" method="GET">
                <input type="text" name="query" placeholder="Keresés...">
                <button type="submit">Keresés</button>
            </form>
        </div>
        <div class="user-panel">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" class="btn btn-primary">Profil</a>
                <a href="logout.php" class="btn">Kijelentkezés</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Bejelentkezés</a>
                <a href="register.php" class="btn">Regisztráció</a>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="container">
        <div class="left-panel">
            <section class="featured-article">
                <h2>A nap cikke</h2>
                <?php
                // Kiemelt cikk lekérése (egyszerűség kedvéért a legújabb lektorált cikk)
                $featuredQuery = "SELECT c.ID, c.Cim, DBMS_LOB.SUBSTR(c.Tartalom, 200, 1) as RovidTartalom 
                                  FROM Cikk c 
                                  WHERE c.Van_e_lektoralva = 1
                                  ORDER BY c.Letrehozas_Datum DESC 
                                  FETCH FIRST 1 ROW ONLY";
                $featuredStmt = $connection->prepare($featuredQuery);
                $featuredStmt->execute();
                $featured = $featuredStmt->fetch(PDO::FETCH_ASSOC);

                if ($featured) {
                    echo '<h3><a href="article.php?id=' . $featured['ID'] . '">' . $featured['CIM'] . '</a></h3>';
                    echo '<p>' . $featured['ROVIDTARTALOM'] . '... <a href="article.php?id=' . $featured['ID'] . '">Tovább</a></p>';
                } else {
                    echo '<p>Nincs elérhető lektorált cikk.</p>';
                }
                ?>
            </section>
            
            <section class="latest-articles">
                <h2>Legutóbb megjelent</h2>
                <ul>
                    <?php
                    while ($article = $latestStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li>';
                        echo '<a href="article.php?id=' . $article['ID'] . '">' . $article['CIM'] . '</a>';
                        echo '<span class="article-meta">Kategória: ' . $article['KATEGORIA'] . ', Szerző: ' . $article['SZERZO'] . '</span>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </section>
        </div>
        
        <div class="right-panel">
            <section class="user-options">
                <?php if ($isLoggedIn): ?>
                <div class="profile-menu">
                    <h3>Profil adatok</h3>
                    <ul>
                        <li><a href="profile.php">Saját cikkek</a></li>
                        <li><a href="reported-errors.php">Hiba bejelentések</a></li>
                        <?php if ($isLektor): ?>
                        <li><a href="pending-reviews.php" class="lektor-link">Átolvasásra váró cikkek</a></li>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                        <li><a href="admin.php" class="admin-link">Admin felület</a></li>
                        <?php endif; ?>
                        <li><a href="new-article.php">Új cikk létrehozása</a></li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="categories">
                    <h3>Kategóriák</h3>
                    <ul>
                        <?php
                        foreach ($categories as $category) {
                            echo '<li><a href="category.php?id=' . $category['ID'] . '">' . $category['NEV'] . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
            </section>
            
            <section class="recent-activity">
                <h3>Legutóbb módosított cikkek</h3>
                <ul>
                    <?php
                    while ($article = $modifiedStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li>';
                        echo '<a href="article.php?id=' . $article['ID'] . '">' . $article['CIM'] . '</a>';
                        echo '<span class="article-meta">Módosítva: ' . date('Y-m-d', strtotime($article['MODOSITAS_DATUM'])) . '</span>';
                        echo '</li>';
                    }
                    ?>
                </ul>
                
                <div class="non-reviewed">
                    <h3>Nem lektorált cikkek</h3>
                    <?php
                    $nonReviewedQuery = "SELECT COUNT(*) as CNT FROM Cikk WHERE Van_e_lektoralva = 0";
                    $nonReviewedStmt = $connection->prepare($nonReviewedQuery);
                    $nonReviewedStmt->execute();
                    $nonReviewed = $nonReviewedStmt->fetch(PDO::FETCH_ASSOC);
                    echo '<p>Jelenleg ' . $nonReviewed['CNT'] . ' cikk vár lektorálásra.</p>';
                    ?>
                </div>
            </section>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-links">
                <?php if ($isAdmin): ?>
                <a href="admin.php" class="admin-link">Admin felület</a>
                <?php endif; ?>
                <?php if ($isLektor): ?>
                <a href="lektor.php" class="lektor-link">Lektor felület</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>
</body>
</html>

<?php
// Adatbázis kapcsolat lezárása
$connection = null;
?>
