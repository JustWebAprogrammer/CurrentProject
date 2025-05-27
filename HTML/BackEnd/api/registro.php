<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cliente.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../Registrar.html?erro=" . urlencode("Método não permitido"));
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Falha na conexão com o banco de dados");
    }
    
    // Receber dados do formulário
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validações básicas
    if (empty($username) || empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        throw new Exception("Todos os campos são obrigatórios");
    }
    
    if (strlen($username) < 3) {
        throw new Exception("Nome de usuário deve ter pelo menos 3 caracteres");
    }
    
    if (strlen($full_name) < 3) {
        throw new Exception("Nome completo deve ter pelo menos 3 caracteres");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
    
    // Limpar telefone e validar
    $phone_clean = preg_replace('/\D/', '', $phone);
    if (strlen($phone_clean) !== 9) {
        throw new Exception("Telefone deve ter exatamente 9 dígitos");
    }
    
    if (strlen($password) < 6) {
        throw new Exception("Senha deve ter pelo menos 6 caracteres");
    }
    
    if ($password !== $confirm_password) {
        throw new Exception("Senhas não coincidem");
    }
    
    // Verificar se email já existe
    $cliente = new Cliente($db);
    if ($cliente->buscarPorEmail($email)) {
        throw new Exception("Este email já está cadastrado");
    }
    
    // Verificar se telefone já existe
    if ($cliente->buscarPorTelefone($phone_clean)) {
        throw new Exception("Este telefone já está cadastrado");
    }
    
    // Criar cliente
    $cliente->nome = $full_name;
    $cliente->email = $email;
    $cliente->telemovel = $phone_clean;
    $cliente->senha = $password;
    
    if ($cliente->criar()) {
        // Registro bem-sucedido - fazer login automático
        $clienteData = $cliente->buscarPorEmail($email);
        $_SESSION['cliente_logado'] = [
            'id' => $clienteData['id'],
            'nome' => $clienteData['nome'],
            'email' => $clienteData['email'],
            'telemovel' => $clienteData['telemovel']
        ];
        
        header("Location: ../../Perfil.php?sucesso=" . urlencode("Conta criada com sucesso!"));
        exit;
    } else {
        throw new Exception("Erro ao criar conta. Tente novamente.");
    }
    
} catch (Exception $e) {
    header("Location: ../../Registrar.html?erro=" . urlencode($e->getMessage()));
    exit;
}
?>