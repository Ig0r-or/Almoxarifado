<?php
error_reporting(0);
ini_set('display_errors', 0);

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
    die(json_encode(["status" => "error", "message" => "Erro de conexão com o banco de dados."]));
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(["status" => "error", "message" => "Requisição inválida."]));
}
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']); 
    
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

if ($cargo['nivel'] < 4) {  
    die(json_encode(["status" => "error", "message" => "Acesso negado. Você não tem permissão para essa requisição."]));
}
    $sql = "DELETE FROM historico_retiradas WHERE id = ?";
    $stmt = $conn->prepare($sql);

     if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Item deletado com sucesso!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao deletar item."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao preparar a query."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Ação inválida ou ID não fornecido."]);
}


$conn->close();
?>
