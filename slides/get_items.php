<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require 'db_connection.php';

$stmt = $pdo->query("SELECT id, nome FROM tabela ORDER BY nome");
$itens = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($itens);
?>