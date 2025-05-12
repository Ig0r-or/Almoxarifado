<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagem'])) {
    // --- BLOQUEIO DA REQUISIÇÃO DE UPLOAD ---
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        die(json_encode(["status" => "error", "message" => "Acesso não autorizado. Faça login primeiro."]));
    }

    // Consulta o nível de permissão (exemplo com PDO)
    $stmt = $pdo->prepare("SELECT c.nivel 
                          FROM usuarios u
                          JOIN cargos c ON u.cargo_id = c.id
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $cargo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Bloqueia se o nível for insuficiente (ex: < 4)
    if (!$cargo || $cargo['nivel'] < 4) {
        http_response_code(403);
        die(json_encode(["status" => "error", "message" => "Você não tem permissão para enviar imagens."]));
    }

    $itemId = $_POST['item_id'];
    $targetDir = "uploads/slides/";

    if ($cargo['nivel'] < 4) {  
    die(json_encode(["status" => "error", "message" => "Acesso negado. Você não tem permissão para essa requisição."]));
}
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($_FILES['imagem']['name']);
    $targetFile = $targetDir . $fileName;
    $relativePath = 'uploads/slides/' . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES['imagem']['tmp_name']);
    if ($check === false) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => 'O arquivo não é uma imagem.']));
    }

    if ($_FILES['imagem']['size'] > 2000000) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => 'O arquivo é muito grande (máx. 2MB).']));
    }

    $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
    if (!in_array($imageFileType, $allowedTypes)) {
        http_response_code(400);
        die(json_encode(['status' => 'error', 'message' => 'Apenas JPG, JPEG, PNG e GIF são permitidos.']));
    }

    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $targetFile)) {
        $stmt = $pdo->prepare("UPDATE tabela SET imagem_path = ? WHERE id = ?");
        if ($stmt->execute([$relativePath, $itemId])) {
            echo json_encode([
                'status' => 'success', 
                'path' => $relativePath,
                'message' => 'Upload realizado com sucesso'
            ]);
        } else {
            unlink($targetFile);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar o banco de dados.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erro ao fazer upload do arquivo.']);
    }
 }else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido ou dados inválidos.']);
}
?>