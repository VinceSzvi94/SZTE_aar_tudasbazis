<?php
/**
 * Kijelentkezés kezelő oldal
 * 
 * Ez a fájl kezeli a felhasználók kijelentkezését,
 * megszünteti a munkamenetet és visszairányít a főoldalra.
 */

// Munkamenet indítása
session_start();

// Összes munkamenet változó törlése
$_SESSION = array();

// Munkamenet megszüntetése
session_destroy();

// Átirányítás a főoldalra
header('Location: index.php');
exit;
?>