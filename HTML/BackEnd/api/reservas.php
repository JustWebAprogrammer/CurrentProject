<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../controllers/ReservaController.php';
require_once '../verificar_sessao.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ReservaController($db);

$method = $_SERVER['REQUEST_METHOD'];

// Handle PUT method override
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $method = 'PUT';
}

switch($method) {
    case 'POST':
        try {
            // Verificar se o usuário está logado
            if (!verificarLogin()) {
                // Se veio de formulário, redirecionar para login
                if (isset($_POST['reservation_date']) || isset($_POST['data'])) {
                    header("Location: ../../Login.html?erro=" . urlencode("Você precisa estar logado para fazer uma reserva"));
                    exit;
                }
                // Se veio de API, retornar erro JSON
                http_response_code(401);
                echo json_encode(["erro" => "Usuário não autenticado"]);
                exit;
            }
    
            $cliente_logado = obterClienteLogado();
    
            // Aceitar dados tanto de JSON quanto de formulário
            if (isset($_POST['reservation_date']) && isset($_POST['reservation_time']) && isset($_POST['num_people'])) {
                // Dados do formulário (nomes originais do HTML)
                $data = [
                    'data' => $_POST['reservation_date'],
                    'hora' => $_POST['reservation_time'], 
                    'num_pessoas' => (int)$_POST['num_people'],
                    'cliente_id' => $cliente_logado['id']
                ];
                $isFormSubmission = true;
            } elseif (isset($_POST['data']) && isset($_POST['hora']) && isset($_POST['num_pessoas'])) {
                // Dados do formulário (nomes alternativos - para compatibilidade)
                $data = [
                    'data' => $_POST['data'],
                    'hora' => $_POST['hora'],
                    'num_pessoas' => (int)$_POST['num_pessoas'],
                    'cliente_id' => $cliente_logado['id']
                ];
                $isFormSubmission = true;
            } else {
                // Dados JSON (mantém compatibilidade)
                $json = json_decode(file_get_contents("php://input"), true);
                if (!$json) {
                    http_response_code(400);
                    echo json_encode([
                        "erro" => "Dados inválidos",
                        "debug" => [
                            "campos_esperados" => ["reservation_date", "reservation_time", "num_people"],
                            "campos_recebidos" => array_keys($_POST)
                        ]
                    ]);
                    break;
                }
                $data = $json;
                $data['cliente_id'] = $cliente_logado['id'];
                $isFormSubmission = false;
            }
    
            $resultado = $controller->criarReserva($data);
            
            if ($resultado['sucesso']) {
                if ($isFormSubmission) {
                    // Redirecionar para perfil com mensagem de sucesso
                    header("Location: ../../Perfil.php?sucesso=" . urlencode($resultado['mensagem']));
                    exit;
                } else {
                    // Resposta JSON para API
                    http_response_code(201);
                    echo json_encode($resultado);
                }
            } else {
                if ($isFormSubmission) {
                    // Redirecionar de volta para reserva com erro
                    header("Location: ../../Reserva.html?erro=" . urlencode($resultado['erro']));
                    exit;
                } else {
                    // Resposta JSON para API
                    http_response_code(400);
                    echo json_encode($resultado);
                }
            }
        } catch (Exception $e) {
            if (isset($isFormSubmission) && $isFormSubmission) {
                header("Location: ../../Reserva.html?erro=" . urlencode("Erro interno do servidor"));
                exit;
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro interno do servidor"]);
            }
        }
        break;

    case 'GET':
        // Verificar se o usuário está logado para GET requests
        if (!verificarLogin()) {
            http_response_code(401);
            echo json_encode(["erro" => "Usuário não autenticado"]);
            exit;
        }

        $cliente_logado = obterClienteLogado();

        // Buscar reservas por cliente
        $filtro = $_GET['filtro'] ?? 'proximas';
        $reservas = $controller->buscarReservasPorCliente($cliente_logado['id'], $filtro);
        echo json_encode($reservas);
        break;

        case 'PUT':
            try {
                // DEBUG
                error_log("DEBUG PUT - POST data: " . print_r($_POST, true));
                error_log("DEBUG PUT - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
                
                // Verificar se o usuário está logado
                if (!verificarLogin()) {
                    http_response_code(401);
                    echo json_encode(["erro" => "Usuário não autenticado"]);
                    exit;
                }
            
                // Edição de reservas
                if (isset($_POST['reserva_id'])) {
                    error_log("DEBUG - Reserva ID encontrado: " . $_POST['reserva_id']);
                    
                    // Mapear nomes do formulário para os nomes esperados
                    $data = [
                        'reserva_id' => $_POST['reserva_id'],
                        'data' => $_POST['reservation_date'] ?? $_POST['data'],
                        'hora' => $_POST['reservation_time'] ?? $_POST['hora'],
                        'num_pessoas' => (int)($_POST['num_people'] ?? $_POST['num_pessoas'])
                    ];
                    
                    // DEBUG: Verificar o valor do horário
                    error_log("DEBUG - Horário recebido: " . ($data['hora'] ?? 'Nenhum'));
                    error_log("DEBUG - Dados para edição: " . print_r($data, true));
                    
                    $isFormSubmission = true;
                } else {
                    // Dados JSON
                    $json = json_decode(file_get_contents("php://input"), true);
                    if (!$json || !isset($json['reserva_id'])) {
                        http_response_code(400);
                        echo json_encode(["erro" => "Dados inválidos para edição"]);
                        break;
                    }
                    $data = $json;
                    $isFormSubmission = false;
                }
            
                $resultado = $controller->editarReserva($data);
                
                if ($resultado['sucesso']) {
                    if ($isFormSubmission) {
                        header("Location: ../../Perfil.php?sucesso=" . urlencode($resultado['mensagem']));
                        exit;
                    } else {
                        echo json_encode($resultado);
                    }
                } else {
                    if ($isFormSubmission) {
                        header("Location: ../../Reserva.html?erro=" . urlencode($resultado['erro']));
                        exit;
                    } else {
                        http_response_code(400);
                        echo json_encode($resultado);
                    }
                }
            } catch (Exception $e) {
                if (isset($isFormSubmission) && $isFormSubmission) {
                    header("Location: ../../Reserva.html?erro=" . urlencode("Erro interno do servidor: " . $e->getMessage()));
                    exit;
                } else {
                    http_response_code(500);
                    echo json_encode(["erro" => "Erro interno do servidor"]);
                }
            }
            break;

    case 'DELETE':
        // Verificar se o usuário está logado
        if (!verificarLogin()) {
            http_response_code(401);
            echo json_encode(["erro" => "Usuário não autenticado"]);
            exit;
        }

        $reserva_id = $_GET['reserva_id'] ?? null;
        if (!$reserva_id) {
            // Try to get from JSON body for proper DELETE requests
            $json = json_decode(file_get_contents("php://input"), true);
            $reserva_id = $json['id'] ?? null;
        }
        
        if ($reserva_id) {
            $resultado = $controller->cancelarReserva($reserva_id);
            echo json_encode($resultado);
        } else {
            http_response_code(400);
            echo json_encode(["erro" => "ID da reserva necessário"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido"]);
        break;
}
?>