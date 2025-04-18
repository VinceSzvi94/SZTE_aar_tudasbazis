<?php
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
        die("Database connection failed: " . $e->getMessage());
    }
}
?>