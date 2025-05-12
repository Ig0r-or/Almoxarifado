<?php
$host = "sql201.infinityfree.com";
$username = "if0_38675151";
$password = "6KUnPJcDNdqlcO";
$dbname = "if0_38675151_db_registro";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>