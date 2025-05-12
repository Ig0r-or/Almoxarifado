<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_cargo'])) {
    die(json_encode(["status" => "error", "message" => "Acesso não autorizado. Faça login primeiro."]));
}

// Configurações do banco de dados
$host = "sql201.infinityfree.com";
$user = "if0_38675151";
$password = "6KUnPJcDNdqlcO";
$dbname = "if0_38675151_db_registro";

// Conexão com o banco
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Erro de conexão: ' . $conn->connect_error
    ]));
}

// Verificar permissões primeiro
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

if ($cargo['nivel'] < 4) {
    die(json_encode(["status" => "error", "message" => "Acesso negado. Você não tem permissão para essa requisição."]));
}

// Recebe os dados do POST
$input = json_decode(file_get_contents('php://input'), true);

// Validação dos campos obrigatórios
$requiredFields = ['item_id', 'nome', 'quantidade'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            'status' => 'error',
            'message' => "O campo $field é obrigatório"
        ]);
        exit;
    }
}

// Processamento dos dados
$item_id = intval($input['item_id']);
$nome = $conn->real_escape_string(trim($input['nome']));
$descricao = isset($input['descricao']) ? $conn->real_escape_string(trim($input['descricao'])) : '';
$quantidade = intval($input['quantidade']);

// Validação e formatação da data
if (empty($input['data'])) {
    $data_retirada = date('Y-m-d'); // Data atual se não informada
} else {
    // Converte de DD/MM/AAAA para AAAA-MM-DD
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $input['data'], $matches)) {
        $data_retirada = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Formato de data inválido. Use DD/MM/AAAA'
        ]);
        exit;
    }
}

// Determina o mês da retirada (1-12)
$mes_retirada = isset($input['mes']) ? intval($input['mes']) : date('n');

// Validação final dos dados
if ($quantidade <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Quantidade deve ser maior que zero'
    ]);
    exit;
}

if ($mes_retirada < 1 || $mes_retirada > 12) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mês inválido (deve ser entre 1 e 12)'
    ]);
    exit;
}

// Corrigido: Removido o parâmetro extra (usuário_id) que não existe na tabela
$sql = "INSERT INTO historico_retiradas (
    item_id,
    nome_solicitante,
    descricao,
    quantidade,
    data_retirada,
    mes_retirada
) VALUES (?, ?, ?, ?, ?, ?)"; // 6 placeholders agora

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao preparar query: ' . $conn->error
    ]);
    exit;
}

// Corrigido: Apenas 6 parâmetros agora
$stmt->bind_param(
    "issisi", // Tipos: i-integer, s-string
    $item_id,
    $nome,
    $descricao,
    $quantidade,
    $data_retirada,
    $mes_retirada
);

// Executa a query
if ($stmt->execute()) {
    $response = [
        'status' => 'success',
        'message' => 'Retirada registrada com sucesso',
        'data' => [
            'id' => $stmt->insert_id,
            'data_formatada' => date('d/m/Y', strtotime($data_retirada)),
            'mes' => $mes_retirada
        ]
    ];
} else {
    $response = [
        'status' => 'error',
        'message' => 'Erro ao registrar retirada: ' . $stmt->error
    ];
}

// Fecha a conexão
$stmt->close();
$conn->close();

// Retorna a resposta
echo json_encode($response);
?>