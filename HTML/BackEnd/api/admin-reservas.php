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
                case 'mesas_disponiveis':
                    $data = $_GET['data'] ?? '';
                    $hora = $_GET['hora'] ?? '';
                    $numPessoas = (int)($_GET['num_pessoas'] ?? 0);
                    
                    if (empty($data) || empty($hora) || $numPessoas < 1) {
                        header('Content-Type: application/json');
                        echo json_encode(['erro' => 'Parâmetros inválidos']);
                        exit;
                    }
                    
                    header('Content-Type: application/json');
                    echo json_encode($controller->obterMesasDisponiveis($data, $hora, $numPessoas));
                    break;
                    
                case 'reservas':
                    $filtro = $_GET['filtro'] ?? 'todas';
                    header('Content-Type: application/json');
                    echo json_encode($controller->obterReservasAdmin($filtro));
                    break;
                    
                default:
                    header('Content-Type: application/json');
                    echo json_encode(['erro' => 'Ação não especificada']);
            }
            break;

        case 'POST':
            $acao = $_POST['acao'] ?? '';
            
            switch($acao) {
                case 'criar_reserva':
                    $clienteId = (int)($_POST['cliente_id'] ?? 0);
                    $mesaId = (int)($_POST['mesa_id'] ?? 0);
                    $data = $_POST['data'] ?? '';
                    $hora = $_POST['hora'] ?? '';
                    $numPessoas = (int)($_POST['num_pessoas'] ?? 0);
                    
                    if ($clienteId < 1 || $mesaId < 1 || empty($data) || empty($hora) || $numPessoas < 1) {
                        header("Location: ../../gestao-reservas.html?erro=" . urlencode("Dados inválidos"));
                        exit;
                    }
                    
                    $resultado = $controller->criarReservaComoAdmin($clienteId, $mesaId, $data, $hora, $numPessoas);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../gestao-reservas.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../gestao-reservas.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                case 'cancelar_reserva':
                    $reservaId = (int)($_POST['reserva_id'] ?? 0);
                    
                    if ($reservaId < 1) {
                        header("Location: ../../gestao-reservas.html?erro=" . urlencode("ID da reserva inválido"));
                        exit;
                    }
                    
                    $resultado = $controller->cancelarReservaAdmin($reservaId);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../gestao-reservas.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../gestao-reservas.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                default:
                    header("Location: ../../gestao-reservas.html?erro=" . urlencode("Ação não reconhecida"));
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
        header("Location: ../../gestao-reservas.html?erro=" . urlencode("Erro interno do servidor"));
    }
    exit;
}
?>