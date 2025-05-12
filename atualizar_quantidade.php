<?php
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_cargo'])) {
    die(json_encode(["status" => "error", "message" => "Acesso não autorizado. Faça login primeiro."]));
}

$host = "sql201.infinityfree.com";
$user = "if0_38675151";
$password = "6KUnPJcDNdqlcO";
$dbname = "if0_38675151_db_registro";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Erro de conexão com o banco de dados."]));
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

$id = $_POST['id'] ?? null;
$quantidade = $_POST['quantidade'] ?? null;
$mes = $_POST['mes'] ?? null;
$action = $_POST['action'] ?? null;

if (!$id || !$quantidade || !$mes || !$action || !$cargo) {
    echo json_encode(["status" => "error", "message" => "Dados incompletos."]);
    exit;
}


$meses = [
    1 => 'janeiro',
    2 => 'fevereiro',
    3 => 'marco',
    4 => 'abril',
    5 => 'maio',
    6 => 'junho',
    7 => 'julho',
    8 => 'agosto',
    9 => 'setembro',
    10 => 'outubro',
    11 => 'novembro',
    12 => 'dezembro'
];

if ($cargo['nivel'] < 4) {  
    die(json_encode(["status" => "error", "message" => "Acesso negado. Você não tem permissão para essa requisição."]));
}

if (!isset($meses[$mes])) {
    echo json_encode(["status" => "error", "message" => "Mês inválido."]);
    exit;
}

$coluna_mes = $meses[$mes];

$sql = "SELECT saldo FROM tabela WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Item não encontrado."]);
    exit;
}

$row = $result->fetch_assoc();
$saldoAtual = $row['saldo'];

if ($action === "retirar") {
    if ($quantidade > $saldoAtual) {
        echo json_encode(["status" => "error", "message" => "Quantidade insuficiente em estoque."]);
        exit;
    }
    
    $novoSaldo = $saldoAtual - $quantidade;
    
    $sql = "UPDATE tabela SET saldo = ?, $coluna_mes = $coluna_mes + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $novoSaldo, $quantidade, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Retirada registrada com sucesso! Saldo atualizado."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao atualizar o saldo."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Ação inválida."]);
}
$conn->close();
?>