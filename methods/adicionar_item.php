<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_cargo'])) {
    die(json_encode(["status" => "error", "message" => "Acesso não autorizado. Faça login primeiro."]));
}

$host = "XXXXXXXXXXXXXXXX";
$user = "XXXXXXXXXXXXXXXXX";
$password = "XXXXXXXXXXXXXXXXXX";
$dbname = "XXXXXXXXXXXXXXXXX_db_registro";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Erro de conexão: " . $conn->connect_error]));
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(["status" => "error", "message" => "Método não permitido."]));
}

$usuario_id = $_SESSION['usuario_id'];
$query = "SELECT c.nivel 
          FROM usuarios u
          JOIN cargos c ON u.cargo_id = c.id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(["status" => "error", "message" => "Usuário não encontrado."]));
}

$cargo = $result->fetch_assoc();

$sql = "INSERT INTO tabela (nome, quantidade, janeiro, fevereiro, marco, abril, maio, junho, julho, agosto, setembro, outubro, novembro, dezembro, saldo) 
        VALUES ('Novo Item', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)";
        
if ($cargo['nivel'] < 4) {  
    die(json_encode(["status" => "error", "message" => "Acesso negado. Você não tem permissão para essa requisição."]));
}
if ($conn->query($sql)) {
    echo json_encode(["status" => "success", "message" => "Item adicionado!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Erro SQL: " . $conn->error]);
}

$conn->close();
?>