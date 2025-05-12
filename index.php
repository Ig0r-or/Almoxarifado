<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'auth_functions.php';
startSecureSession();

require 'db_connection.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT u.*, c.nome as cargo_nome 
                      FROM usuarios u
                      JOIN cargos c ON u.cargo_id = c.id
                      WHERE u.id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>almoxarifado</title>
</head>
<body>
    <header class="header">
        <div class="header-icon">
            <a>
            <img src="db7b8b7d-e7c2-4dd6-a55d-76ed85275d8c.png" alt="Ícone"/>
            </a>
        </div>
        <div class="header-title">Olá, <?= htmlspecialchars($usuario['nome']) ?> (<?= htmlspecialchars($usuario['cargo_nome']) ?>)</div>
        <div class="button-container">
            <button class="page-button" id="btnImprimir"></button>
            <button class="theme-button" onclick="toggleDarkMode()"></button>
            <a href="logout.php" class="logout-button"> <i class="fas fa-sign-out-alt"></i>Sair</a>
        </div>
    </header>
<!----------------------------------------------------------------------------------------------------------------------------------------------->
    <form class="tabela-container">
    <table id="tabelaItens" border="1">
        <thead>
            <tr>
              <th>ID</th>
              <th>Itens</th>
              <th>Quantidade</th>
              <th>Janeiro</th>
              <th>Fevereiro</th>
              <th>Março</th>
              <th>Abril</th>
              <th>Maio</th>
              <th>Junho</th>
              <th>Julho</th>
              <th>Agosto</th>
              <th>Setembro</th>
              <th>Outubro</th>
              <th>Novembro</th>
              <th>Dezembro</th>
              <th>Saldo</th>
              <th>v2.4</th>
            </tr>
        </thead>
           <tbody>
           <!-- Itens serão carregados aqui -->
          </tbody>
      </table>
      </form>
        <form id="formAdicionar">
        <button type="submit" class="page-button">Adicionar Item</button>
    </form>
<!----------------------------------------------------------------------------------------------------------------------------------------------->
<!-- Slider de Imagens -->
<div class="slider-container">
    <h2>Info</h2>
    <div class="slider">
    </div>
     <div class="slider-controls">
        <button class="prev-btn"><i class="fas fa-chevron-left"></i></button>
        <button class="add-image-btn" id="openAddImageModal">
            <i class="fas fa-plus"></i>
        </button>
        <button class="next-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="slider-dots"></div>
</div>
<div id="addImageModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>Adicionar Nova Imagem</h3>
        <form id="uploadImageForm">
            <div class="form-group">
                <label for="itemSelect">Selecione o Item:</label>
                <select id="itemSelect" required>
                    <option value="">Carregando itens...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="imageUpload">Escolha a imagem:</label>
                <input type="file" id="imageUpload" accept="image/*" required>
            </div>
            <button type="submit" class="submit-btn">Enviar</button>
        </form>
        <div id="uploadStatus"></div>
    </div>
</div>
<!----------------------------------------------------------------------------------------------------------------------------------------------->

    <div class="form-container" id="form-container">
    <h2>Formulário de Retirada</h2>
    <form id="formSolicitacao" method="POST">
    
        <div class="form-group">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" readonly required>
        </div>

        <div class="form-group">
            <label for="data">Data:</label>
            <input type="text" id="data" name="data" readonly>
            <input type="hidden" id="mes" name="mes" value="">
        </div>

        <div class="form-group">
            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="item">Item:</label>
            <select id="item" name="item" required>
                <option value="">Carregando itens...</option>
            </select>
        </div>

        <div class="form-group">
            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" required>
        </div>

        <button type="submit" class="submit-button" id="formSolicitacao">Enviar</button>
    </form>
</div>
 <div id="entrada-popup"> 
        <h2>Histórico &uarr;</h2> 
        <ul id="historico-entrada"></ul> 
    </div>
<!----------------------------------------------------------------------------------------------------------------------------------------------->
<script src="main.js"></script>
</body>
</html>