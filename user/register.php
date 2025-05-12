<?php
// Ativar relatório de erros detalhado
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sessão segura
require 'auth_functions.php';
startSecureSession();

// Conexão com o banco de dados
require 'db_connection.php';

// Verificar se tabela existe
$tabelaExiste = $pdo->query("SHOW TABLES LIKE 'usuarios'")->rowCount() > 0;
if (!$tabelaExiste) {
    die("Erro: Tabela 'usuarios' não encontrada. Execute o script de criação do banco de dados.");
}

// Verificar se já está logado
if (!empty($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

// Mensagens de feedback
$erro = '';
$sucesso = '';

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
        $codigo = $_POST['cod_id'];
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        // Validações
        if (empty($nome)) {
            throw new Exception('Por favor, informe seu nome completo');
        }

        if (empty($codigo)) {
            throw new Exception('Por favor, informe um código válido');
        }

        if (!preg_match('/^\d{4}$/', $codigo)) {
            throw new Exception('O código ID deve conter exatamente 4 dígitos numéricos');
        }

        if (empty($senha)) {
            throw new Exception('Por favor, informe uma senha');
        }
        
        if ($senha !== $confirmar_senha) {
            throw new Exception('As senhas não coincidem');
        }
        
        if (strlen($senha) < 8) {
            throw new Exception('A senha deve ter pelo menos 8 caracteres');
        }

        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE cod_id = ? LIMIT 1");
        $stmt->execute([$codigo]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Este código já está cadastrado');
        }

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir novo usuário (cargo_id 2 = usuário comum, 1 seria admin)
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, cod_id, senha, cargo_id, data_cadastro) VALUES (?, ?, ?, 2, NOW())");
        $stmt->execute([$nome, $codigo, $senha_hash]);

        // Redirecionar para login com mensagem de sucesso
        $_SESSION['mensagem_sucesso'] = 'Cadastro realizado com sucesso! Faça login para continuar.';
        header("Location: login.php");
        exit();

    } catch (PDOException $e) {
        error_log("Erro de banco de dados: " . $e->getMessage());
        $erro = 'Erro no sistema. Por favor, tente mais tarde.';
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Almoxeriffe</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #4caf50, #2196f3);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .register-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .register-title {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .btn-register {
            width: 100%;
            padding: 0.75rem;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-register:hover {
            background-color: #219653;
        }
        
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-error {
            background-color: #fdecea;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1 class="register-title">Crie uma nova Conta</h1>
        
        <?php if ($erro): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['mensagem_sucesso']) ?></div>
            <?php unset($_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="codigo">Código ID (4 dígitos):</label>
                <input type="number" id="codigo" name="cod_id" required  min="0001" max="9999" 
                oninput="javascript: if (this.value.length > 4) this.value = this.value.slice(0, 4);">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha (mínimo 8 caracteres):</label>
                <input type="password" id="senha" name="senha" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
            </div>
            
            <button type="submit" class="btn-register">Registrar</button>
        </form>

        <div class="login-link">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>
</body>
</html>