<?php

$host = "localhost";
$db   = "trustvote_database";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper: ambil setting dari tabel settings
function getSetting($pdo, $key, $default = "") {
    try {
        $q = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
        $q->execute([$key]);
        $val = $q->fetchColumn();
        return ($val !== false && $val !== '') ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

?>
