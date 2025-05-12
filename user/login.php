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
if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
    // Debug: Verificar redirecionamento
    error_log("DEBUG: Usuário já logado, redirecionando para index.php");
    
    if (!headers_sent()) {
        header("Location: index.php");
        exit();
    } else {
        echo '<script>window.location.href = "index.php";</script>';
        exit();
    }
}

// Mensagens de feedback
$erro = '';
$sucesso = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Corrigido: usar 'codigo' que é o name do input
        $codigo = filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_STRING);
        $senha = $_POST['senha'] ?? '';

        // Validações básicas
        if (empty($codigo)) {
            throw new Exception('Por favor, informe seu código ID');
        }
        
        if (empty($senha)) {
            throw new Exception('Por favor, informe sua senha');
        }

        // Consulta segura usando prepared statements
        $stmt = $pdo->prepare("SELECT id, nome, cod_id, senha, cargo_id FROM usuarios WHERE cod_id = ? LIMIT 1");
        $stmt->execute([$codigo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            if (password_verify($senha, $usuario['senha'])) {
            
                $update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
                $update->execute([$usuario['id']]);
                
                
                $_SESSION = [
                    'usuario_id' => $usuario['id'],
                    'usuario_nome' => $usuario['nome'],
                    'usuario_codigo' => $usuario['cod_id'],
                    'usuario_cargo' => $usuario['cargo_id'],
                    'logado' => true,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ];

            
                if (!headers_sent()) {
                    header("Location: index.php");
                    exit();
                } else {
                    echo '<script>window.location.href = "index.php";</script>';
                    exit();
                }
            } else {
                throw new Exception('Senha incorreta');
            }
        } else {
            throw new Exception('Código ID não cadastrado no sistema');
        }
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
    <title>Login - Almoxariffe</title>
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
        
        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-title {
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
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
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
        
        .debug-info {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #f0f0f0;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Acesse o Sistema</h1>
        
        <?php if ($erro): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="cod_id">Código ID:</label>
                <input type="text" id="codigo" name="codigo" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <?php if (isset($_GET['debug'])): ?>
            <div class="debug-info">
                <h3>Informações de Debug:</h3>
                <pre><?php print_r($_SESSION) ?></pre>
                <p>PHP Version: <?= phpversion() ?></p>
                <p>DB Tables: <?php 
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    echo implode(', ', $tables);
                ?></p>
            </div>
        <?php endif; ?>
        
        <div class="register-link">
            Não tem uma conta? <a href="register.php">Crie uma agora</a>
        </div>
    </div>
</body>
</html>