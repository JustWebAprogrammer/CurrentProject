<?php
// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../verificar_admin.php';

// Verificar se o admin está logado
if (!verificarAdmin()) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Acesso não autorizado']);
    } else {
        header("Location: ../../admin-login.html?erro=" . urlencode("Acesso não autorizado"));
    }
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $controller = new AdminController($db);

    $method = $_SERVER['REQUEST_METHOD'];

    switch($method) {
        case 'GET':
            $acao = $_GET['acao'] ?? '';
            
            switch($acao) {
                case 'clientes':
                    $busca = $_GET['busca'] ?? '';
                    header('Content-Type: application/json');
                    echo json_encode($controller->obterClientes($busca));
                    break;
                    
                case 'usuarios_sistema':
                    header('Content-Type: application/json');
                    echo json_encode($controller->obterUsuariosSistema());
                    break;
                    
                default:
                    header('Content-Type: application/json');
                    echo json_encode(['erro' => 'Ação não especificada']);
            }
            break;

        case 'POST':
            $acao = $_POST['acao'] ?? '';
            
            switch($acao) {
                case 'adicionar_usuario':
                    $dados = [
                        'nome' => $_POST['nome'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'senha' => $_POST['senha'] ?? '',
                        'tipo' => $_POST['tipo'] ?? ''
                    ];
                    
                    // Validações
                    if (empty($dados['nome']) || empty($dados['email']) || empty($dados['senha']) || empty($dados['tipo'])) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Todos os campos são obrigatórios"));
                        exit;
                    }
                    
                    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Email inválido"));
                        exit;
                    }
                    
                    if (!in_array($dados['tipo'], ['Rececionista', 'Administrador'])) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Tipo de usuário inválido"));
                        exit;
                    }
                    
                    $resultado = $controller->adicionarUsuarioSistema($dados);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../gestao_cliente.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                case 'editar_usuario':
                    $dados = [
                        'id' => (int)($_POST['id'] ?? 0),
                        'nome' => $_POST['nome'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'senha' => $_POST['senha'] ?? '',
                        'tipo' => $_POST['tipo'] ?? ''
                    ];
                    
                    // Validações
                    if ($dados['id'] < 1 || empty($dados['nome']) || empty($dados['email']) || empty($dados['tipo'])) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Dados inválidos"));
                        exit;
                    }
                    
                    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Email inválido"));
                        exit;
                    }
                    
                    if (!in_array($dados['tipo'], ['Rececionista', 'Administrador'])) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Tipo de usuário inválido"));
                        exit;
                    }
                    
                    $resultado = $controller->editarUsuarioSistema($dados);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../gestao_cliente.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                case 'remover_usuario':
                    $usuarioId = (int)($_POST['usuario_id'] ?? 0);
                    
                    if ($usuarioId < 1) {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode("ID do usuário inválido"));
                        exit;
                    }
                    
                    $resultado = $controller->removerUsuarioSistema($usuarioId);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../gestao_cliente.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../gestao_cliente.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                default:
                    header("Location: ../../gestao_cliente.html?erro=" . urlencode("Ação não reconhecida"));
                    exit;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
    }

} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Erro interno do servidor']);
    } else {
        header("Location: ../../gestao_cliente.html?erro=" . urlencode("Erro interno do servidor"));
    }
    exit;
}
