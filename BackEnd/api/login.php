<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../auth_unified.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Falha na conexão com o banco de dados");
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        
        if (empty($email) || empty($senha)) {
            $erro = "Email e senha são obrigatórios";
        } else {
            // Primeiro tenta login como usuário do sistema (admin/rececionista)
            $adminController = new AdminController($db);
            $resultadoAdmin = $adminController->login($email, $senha);
            
            if ($resultadoAdmin['sucesso']) {
                AuthUnified::logarUsuario($resultadoAdmin['usuario'], $resultadoAdmin['usuario']['tipo']);
                
                // Redirecionar baseado no tipo de usuário
                if ($resultadoAdmin['usuario']['tipo'] === 'Rececionista') {
                    header("Location: ../../dashboard-recepcionista.html");
                } else {
                    // Para Administrador
                    header("Location: ../../dashboard.html");
                }
                exit;
            }
            
            // Se não for admin, tenta login como cliente
            $authController = new AuthController($db);
            $resultadoCliente = $authController->login($email, $senha);
            
            if ($resultadoCliente['sucesso']) {
                AuthUnified::logarUsuario($resultadoCliente['cliente'], 'Cliente');
                header("Location: ../../Perfil.php");
                exit;
            }
            
            // Se nenhum login funcionou
            $erro = "Email ou senha incorretos";
        }
    }
} catch (Exception $e) {
    $erro = "Erro interno do servidor: " . $e->getMessage();
}

// Se chegou aqui, houve erro
if (isset($erro)) {
    header("Location: ../../Login.html?erro=" . urlencode($erro));
    exit;
}
?>