<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require 'db_connection.php';

$stmt = $pdo->query("SELECT id, nome, saldo, imagem_path FROM tabela WHERE imagem_path IS NOT NULL ORDER BY nome");
$slides = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($slides);
?>