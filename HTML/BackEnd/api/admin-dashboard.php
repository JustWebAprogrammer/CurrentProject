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
    header("Location: ../../admin-login.html?erro=" . urlencode("Acesso não autorizado"));
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
                case 'estatisticas':
                    header('Content-Type: application/json');
                    echo json_encode($controller->obterEstatisticas());
                    break;
                    
                case 'mesas':
                    header('Content-Type: application/json');
                    echo json_encode($controller->obterMesas());
                    break;
                    
                default:
                    header('Content-Type: application/json');
                    echo json_encode(['erro' => 'Ação não especificada']);
            }
            break;

        case 'POST':
            $acao = $_POST['acao'] ?? '';
            
            switch($acao) {
                case 'adicionar_mesa':
                    $capacidade = (int)($_POST['capacidade'] ?? 0);
                    if ($capacidade < 1) {
                        header("Location: ../../dashboard.html?erro=" . urlencode("Capacidade inválida"));
                        exit;
                    }
                    
                    $resultado = $controller->adicionarMesa($capacidade);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../dashboard.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../dashboard.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                case 'remover_mesa':
                    $mesaId = (int)($_POST['mesa_id'] ?? 0);
                    if ($mesaId < 1) {
                        header("Location: ../../dashboard.html?erro=" . urlencode("ID da mesa inválido"));
                        exit;
                    }
                    
                    $resultado = $controller->removerMesa($mesaId);
                    
                    if ($resultado['sucesso']) {
                        header("Location: ../../dashboard.html?sucesso=" . urlencode($resultado['mensagem']));
                    } else {
                        header("Location: ../../dashboard.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                case 'gerar_relatorio':
                    $resultado = $controller->gerarRelatorioDiario();
                    
                    if ($resultado['sucesso']) {
                        $dados = $resultado['dados'];
                        $mensagem = "Relatório gerado!\n\n";
                        $mensagem .= "Data: " . date('d/m/Y', strtotime($dados['data'])) . "\n";
                        $mensagem .= "Reservas: {$dados['num_reservas']}\n";
                        $mensagem .= "Mesas ocupadas: {$dados['num_mesas_ocupadas']}\n";
                        $mensagem .= "Taxa de ocupação: {$dados['percent_ocupacao']}%\n";
                        $mensagem .= "Expirações: {$dados['num_expiracoes']}\n";
                        $mensagem .= "Cancelamentos: {$dados['num_cancelamentos']}";
                        
                        header("Location: ../../dashboard.html?sucesso=" . urlencode($mensagem));
                    } else {
                        header("Location: ../../dashboard.html?erro=" . urlencode($resultado['erro']));
                    }
                    exit;
                    
                default:
                    header("Location: ../../dashboard.html?erro=" . urlencode("Ação não reconhecida"));
                    exit;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
    }

} catch (Exception $e) {
    header("Location: ../../dashboard.html?erro=" . urlencode("Erro interno do servidor"));
    exit;
}
?>