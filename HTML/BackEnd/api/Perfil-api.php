<?php


// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../verificar_sessao.php';

// Verificar se o usuário está logado
if (!verificarLogin()) {
    http_response_code(401);
    echo json_encode(["erro" => "Usuário não autenticado"]);
    exit;
}

// Handle both POST and PUT requests
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $method = 'PUT';
}

if ($method === 'POST' || $method === 'PUT') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Falha na conexão com o banco de dados");
        }
        
        $cliente_logado = obterClienteLogado();
        
        // Handle JSON data for PUT requests
        if ($method === 'PUT') {
            $json_data = json_decode(file_get_contents('php://input'), true);
            if ($json_data) {
                $nome = $json_data['nome'] ?? '';
                $email = $json_data['email'] ?? '';
                $telemovel = $json_data['telemovel'] ?? '';
            } else {
                throw new Exception("Dados JSON inválidos");
            }
        } else {
            // Handle form data for POST requests
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? '';
            $telemovel = $_POST['telemovel'] ?? '';
        }
        
        // Validações básicas
        if (empty($nome) || empty($email) || empty($telemovel)) {
            throw new Exception("Todos os campos são obrigatórios");
        }
        
        if (strlen($nome) < 3) {
            throw new Exception("Nome deve ter pelo menos 3 caracteres");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email inválido");
        }
        
        // Limpar telefone e validar
        $phone_clean = preg_replace('/\D/', '', $telemovel);
        if (strlen($phone_clean) !== 9) {
            throw new Exception("Telefone deve ter exatamente 9 dígitos");
        }
        
        $cliente = new Cliente($db);
        
        // Verificar se email já existe (para outro usuário)
        $clienteExistente = $cliente->buscarPorEmail($email);
        if ($clienteExistente && $clienteExistente['id'] != $cliente_logado['id']) {
            throw new Exception("Este email já está sendo usado por outro usuário");
        }
        
        // Verificar se telefone já existe (para outro usuário)
        $clienteExistente = $cliente->buscarPorTelefone($phone_clean);
        if ($clienteExistente && $clienteExistente['id'] != $cliente_logado['id']) {
            throw new Exception("Este telefone já está sendo usado por outro usuário");
        }
        
        // Atualizar dados
        $cliente->id = $cliente_logado['id'];
        $cliente->nome = $nome;
        $cliente->email = $email;
        $cliente->telemovel = $phone_clean;
        
        if ($cliente->atualizar()) {
            // Atualizar dados na sessão
            $_SESSION['cliente_logado'] = [
                'id' => $cliente_logado['id'],
                'nome' => $nome,
                'email' => $email,
                'telemovel' => $phone_clean
            ];
            
            if ($method === 'PUT') {
                echo json_encode([
                    "sucesso" => true, 
                    "mensagem" => "Dados atualizados com sucesso!"
                ]);
            } else {
                header("Location: ../../Perfil.php?sucesso=" . urlencode("Dados atualizados com sucesso!"));
                exit;
            }
        } else {
            throw new Exception("Erro ao atualizar dados. Tente novamente.");
        }
        
    } catch (Exception $e) {
        if ($method === 'PUT') {
            http_response_code(400);
            echo json_encode(["erro" => $e->getMessage()]);
        } else {
            header("Location: ../../Perfil.php?erro=" . urlencode($e->getMessage()));
            exit;
        }
    }
} else {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido"]);
}
?>