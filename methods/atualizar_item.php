<?php
header('Content-Type: application/json');
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
    die(json_encode(["status" => "error", "message" => $conn->connect_error]));
}

$usuario_id = $_SESSION['usuario_id'];
$query = "SELECT c.nivel FROM usuarios u JOIN cargos c ON u.cargo_id = c.id WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(["status" => "error", "message" => "Usuário não encontrado."]));
}

$cargo = $result->fetch_assoc();


$id = intval($_POST['id'] ?? 0);
$campo = $_POST['campo'] ?? '';
$valor = $_POST['valor'] ?? '';


if ($cargo['nivel'] < 4) {
    die(json_encode(["status" => "error", "message" => "Acesso negado. Você não tem permissão para essa requisição."]));
}

if (!in_array($campo, ['nome', 'quantidade'])) {
    die(json_encode(["status" => "error", "message" => "Campo inválido"]));
}

$conn->begin_transaction();

try {
    if ($campo === "quantidade") {
        $valor = intval($valor);
        if ($valor < 0) {
            throw new Exception("Quantidade não pode ser negativa");
        }
        
        
        $sql_select = "SELECT quantidade, 
                      (janeiro + fevereiro + marco + abril + maio + junho +
                       julho + agosto + setembro + outubro + novembro + dezembro) as total_retiradas 
                       FROM tabela WHERE id = ?";
        $stmt = $conn->prepare($sql_select);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Item não encontrado");
        }
        
        $row = $result->fetch_assoc();
        $total_retiradas = $row['total_retiradas'];
        
       
        if ($valor < $total_retiradas) {
            throw new Exception("Nova quantidade não pode ser menor que o total já retirado ($total_retiradas)");
        }
        
        
        $novo_saldo = $valor - $total_retiradas;
        
        
        $sql = "UPDATE tabela SET quantidade = ?, saldo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $valor, $novo_saldo, $id);
    } else {
       
        $sql = "UPDATE tabela SET nome = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $valor, $id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao executar atualização: " . $conn->error);
    }
    
    $conn->commit();
    echo json_encode(["status" => "success", "message" => "Item atualizado com sucesso"]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>