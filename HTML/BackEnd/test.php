<?php
// Script único: mostra form, processa email, consulta MySQL e exibe resultado
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$port = 5396;
$dbname = 'SiteDonaXinga';
$username = 'root';
$password = '';

$mensagem = '';
$erro = '';
$usuario = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (!$email) {
        $erro = "❌ Email é obrigatório!";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $mensagem = "✅ Login realizado com sucesso! Bem-vindo, " . htmlspecialchars($usuario['nome']);
            } else {
                $erro = "❌ Email não encontrado";
            }
        } catch (Exception $e) {
            $erro = "❌ Erro de conexão: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login Só com Email - Script Único</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .form-group { margin-bottom: 20px; }
    label { display: block; margin-bottom: 8px; font-weight: bold; }
    input[type="email"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
    button { background-color: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    button:hover { background-color: #0056b3; }
    .mensagem { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    .sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .dados-usuario { background: #eef; padding: 15px; border-radius: 6px; font-family: monospace; white-space: pre-wrap; }
</style>
</head>
<body>
<div class="container">
    <h1>🧪 Login Só com Email - Script Único</h1>

    <?php if ($mensagem): ?>
        <div class="mensagem sucesso"><?= $mensagem ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="mensagem erro"><?= $erro ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email:</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
            >
        </div>
        <button type="submit">Entrar</button>
    </form>

    <?php if ($usuario): ?>
        <div class="dados-usuario">
            <h3>📋 Dados do Usuário:</h3>
            Nome: <?= htmlspecialchars($usuario['nome']) . "\n" ?>
            Username: <?= htmlspecialchars($usuario['username'] ?? 'N/A') . "\n" ?>
            Senha (hash): <?= htmlspecialchars($usuario['senha']) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
