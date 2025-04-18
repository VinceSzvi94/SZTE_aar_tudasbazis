<?php
/**
 * Adatbázis kapcsolódási fájl
 * 
 * Ez a fájl kezeli az Oracle adatbázishoz való kapcsolódást.
 * PDO kapcsolódást használ a biztonságos adatbázis műveletek érdekében.
 */

/**
 * Adatbázis kapcsolat létrehozása
 *
 * @return PDO Az adatbázis kapcsolatot reprezentáló objektum
 */
function getDatabaseConnection() {
    $tns = "
        (DESCRIPTION =
            (ADDRESS_LIST =
                (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521)) )
                (CONNECT_DATA = (SID = xe) ) )";
    $db_username = "C##tudasbazis";
    $db_password = "pass123456";
    try {
        $conn = new PDO("oci:dbname=" . $tns, $db_username, $db_password);
        return $conn;
    } catch (PDOException $e) {
        die("Adatbázis kapcsolódási hiba: " . $e->getMessage());
    }
}
?>