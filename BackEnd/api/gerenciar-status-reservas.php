<?php
header("Content-Type: application/json; charset=UTF-8");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../verificar_sessao.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

// Função para expirar reservas automaticamente
function expirarReservasAutomaticamente($db) {
    try {
        // Expirar reservas que passaram 20 minutos do horário marcado
        $query = "UPDATE reservas 
                  SET status = 'Expirado' 
                  WHERE status = 'Reservado' 
                  AND CONCAT(data, ' ', hora) < DATE_SUB(NOW(), INTERVAL 20 MINUTE)";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (Exception $e) {
        return 0;
    }
}

switch($method) {
    case 'GET':
        // Verificar se é recepcionista ou admin
        if (!verificarLogin() || !verificarPermissaoRecepcionista()) {
            http_response_code(401);
            echo json_encode(["erro" => "Acesso negado"]);
            exit;
        }
        
        if (isset($_GET['acao'])) {
            switch($_GET['acao']) {
                
                case 'reservas_para_garcom':
                    // Buscar reservas que o garçom pode gerenciar (Reservado e Confirmado)
                    $query = "SELECT r.*, c.nome as cliente_nome, c.email, c.telemovel,
                                     m.id as mesa_id, m.capacidade
                              FROM reservas r 
                              JOIN clientes c ON r.cliente_id = c.id 
                              LEFT JOIN mesas m ON r.mesa_id = m.id
                              WHERE r.status IN ('Reservado', 'Confirmado') 
                              AND r.data = CURDATE()
                              ORDER BY 
                                CASE 
                                    WHEN r.status = 'Confirmado' THEN 1 
                                    WHEN r.status = 'Reservado' THEN 2 
                                    ELSE 3 
                                END,
                                r.hora ASC";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        "sucesso" => true,
                        "reservas" => $reservas
                    ]);
                    break;  
                
                case 'expirar_automatico':
                    $expiradas = expirarReservasAutomaticamente($db);
                    echo json_encode([
                        "sucesso" => true,
                        "reservas_expiradas" => $expiradas,
                        "mensagem" => "$expiradas reservas foram expiradas automaticamente"
                    ]);
                    break;
                    
                case 'reservas_pendentes':
                    // Buscar reservas que estão próximas de expirar ou já deveriam ter expirado
                    $query = "SELECT r.*, c.nome as cliente_nome, c.email, c.telemovel,
                                     TIMESTAMPDIFF(MINUTE, CONCAT(r.data, ' ', r.hora), NOW()) as minutos_atraso
                              FROM reservas r 
                              JOIN clientes c ON r.cliente_id = c.id 
                              WHERE r.status = 'Reservado' 
                              AND r.data = CURDATE()
                              AND CONCAT(r.data, ' ', r.hora) <= NOW()
                              ORDER BY r.hora ASC";
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        "sucesso" => true,
                        "reservas" => $reservas
                    ]);
                    break;
            }
        }
        break;
        
    case 'PUT':
        // Verificar se é recepcionista ou admin
        if (!verificarLogin() || !verificarPermissaoRecepcionista()) {
            http_response_code(401);
            echo json_encode(["erro" => "Acesso negado"]);
            exit;
        }
        
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($input['reserva_id']) || !isset($input['novo_status'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Dados incompletos"]);
            exit;
        }
        
        $reserva_id = $input['reserva_id'];
        $novo_status = $input['novo_status'];
        
        // Validar status permitidos
        if (!in_array($novo_status, ['Concluido', 'Expirado', 'Cancelado'])) {
            http_response_code(400);
            echo json_encode(["erro" => "Status inválido"]);
            exit;
        }
        
        try {
            // Atualizar status da reserva
            $query = "UPDATE reservas 
                      SET status = :status,
                          tempo_expiracao = CASE 
                              WHEN :status IN ('Concluido', 'Expirado', 'Cancelado') THEN NOW() 
                              ELSE tempo_expiracao 
                          END
                      WHERE id = :reserva_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $novo_status);
            $stmt->bindParam(':reserva_id', $reserva_id);
            
            if ($stmt->execute()) {
                // Lógica especial para ações do garçom
                if (isset($input['acao_garcom']) && $input['acao_garcom']) {
                    switch($novo_status) {
                        case 'Confirmado':
                            // Quando cliente chega, ocupar a mesa
                            $queryOcupar = "UPDATE mesas SET estado = 'Ocupada' 
                                           WHERE id IN (
                                               SELECT mesa_id FROM reservas 
                                               WHERE id = :reserva_id OR reserva_principal_id = :reserva_id
                                           ) AND mesa_id IS NOT NULL";
                            
                            $stmtOcupar = $db->prepare($queryOcupar);
                            $stmtOcupar->bindParam(':reserva_id', $reserva_id);
                            $stmtOcupar->execute();
                            break;
                            
                        case 'Concluido':
                        case 'Expirado':
                            // Quando finaliza ou expira, liberar a mesa
                            $queryLiberar = "UPDATE mesas SET estado = 'Livre' 
                                            WHERE id IN (
                                                SELECT mesa_id FROM reservas 
                                                WHERE id = :reserva_id OR reserva_principal_id = :reserva_id
                                            ) AND mesa_id IS NOT NULL";
                            
                            $stmtLiberar = $db->prepare($queryLiberar);
                            $stmtLiberar->bindParam(':reserva_id', $reserva_id);
                            $stmtLiberar->execute();
                            break;
                    }
                }
                
                echo json_encode([
                    "sucesso" => true,
                    "mensagem" => "Status da reserva atualizado para: $novo_status"
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["erro" => "Erro ao atualizar status"]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["erro" => "Erro interno: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido"]);
        break;
}

// Função auxiliar para verificar permissão de recepcionista
function verificarPermissaoRecepcionista() {
    return isset($_SESSION['usuario_tipo']) && 
           in_array($_SESSION['usuario_tipo'], ['recepcionista', 'admin']);
}
?>