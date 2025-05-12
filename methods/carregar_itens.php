<?php
require 'db_connection.php';

$stmt = $pdo->query("SELECT id, nome, quantidade, imagem_path FROM tabela WHERE imagem_path IS NOT NULL");
$itens = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($itens);
?>