<?php
header("Content-Type: application/json; charset=UTF-8");

// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão com o banco de dados"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $acao = $_GET['acao'] ?? 'listar';
        
        if ($acao === 'disponiveis') {
            // Buscar mesas livres
            $query = "SELECT id, capacidade, estado FROM mesas WHERE estado = 'Livre' ORDER BY id ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "sucesso" => true,
                "mesas" => $mesas
            ]);
            
        } elseif ($acao === 'ocupadas') {
            // Buscar mesas ocupadas com informações
            $query = "SELECT m.id, m.capacidade, m.estado,
                            r.cliente_id, c.nome as cliente_nome,
                            r.num_pessoas, r.data, r.hora,
                            r.id as reserva_id
                     FROM mesas m
                     LEFT JOIN reservas r ON m.id = r.mesa_id 
                            AND r.status = 'Reservado' 
                            AND r.data = CURDATE()
                     LEFT JOIN clientes c ON r.cliente_id = c.id
                     WHERE m.estado = 'Ocupada'
                     ORDER BY m.id ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "sucesso" => true,
                "mesas" => $mesas
            ]);
        } else {
            // Listar todas as mesas
            $query = "SELECT id, capacidade, estado FROM mesas ORDER BY id ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                "sucesso" => true,
                "mesas" => $mesas
            ]);
        }
        break;
        
    case 'POST':
        // Ocupar mesa (walk-in)
        $json = json_decode(file_get_contents("php://input"), true);
        
        $mesas_ids = $json['mesas_ids'] ?? [];
        $cliente_nome = $json['cliente_nome'] ?? '';
        $num_pessoas = $json['num_pessoas'] ?? 0;
        
        if (empty($mesas_ids) || $num_pessoas <= 0) {
            http_response_code(400);
            echo json_encode(["erro" => "Dados inválidos"]);
            break;
        }
        
        try {
            $db->beginTransaction();
            
            // Atualizar estado das mesas para ocupada
            $mesa_ids_str = implode(',', array_map('intval', $mesas_ids));
            $query = "UPDATE mesas SET estado = 'Ocupada' WHERE id IN ($mesa_ids_str)";
            $db->exec($query);
            
            // Se há nome do cliente, tentar criar reserva walk-in
            if (!empty($cliente_nome)) {
                // Buscar ou criar cliente
                $cliente_id = null;
                
                // Tentar encontrar cliente existente por nome
                $query = "SELECT id FROM clientes WHERE nome LIKE :nome LIMIT 1";
                $stmt = $db->prepare($query);
                $nome_busca = "%{$cliente_nome}%";
                $stmt->bindParam(":nome", $nome_busca);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $cliente_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                }
                
                // Criar reservas walk-in para cada mesa
                if ($cliente_id) {
                    $distribuicao = distribuirPessoas($num_pessoas, count($mesas_ids));
                    $hoje = date('Y-m-d');
                    $agora = date('H:i');
                    
                    foreach ($mesas_ids as $index => $mesa_id) {
                        $pessoas_mesa = $distribuicao[$index] ?? 0;
                        
                        $query = "INSERT INTO reservas (cliente_id, mesa_id, data, hora, num_pessoas, status) 
                                 VALUES (:cliente_id, :mesa_id, :data, :hora, :num_pessoas, 'Reservado')";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(":cliente_id", $cliente_id);
                        $stmt->bindParam(":mesa_id", $mesa_id);
                        $stmt->bindParam(":data", $hoje);
                        $stmt->bindParam(":hora", $agora);
                        $stmt->bindParam(":num_pessoas", $pessoas_mesa);
                        $stmt->execute();
                    }
                }
            }
            
            $db->commit();
            
            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Mesas ocupadas com sucesso!"
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao ocupar mesas: " . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Liberar mesa
        $json = json_decode(file_get_contents("php://input"), true);
        $mesas_ids = $json['mesas_ids'] ?? [];
        
        if (empty($mesas_ids)) {
            http_response_code(400);
            echo json_encode(["erro" => "IDs das mesas não fornecidos"]);
            break;
        }
        
        try {
            $db->beginTransaction();
            
            // Liberar mesas
            $mesa_ids_str = implode(',', array_map('intval', $mesas_ids));
            $query = "UPDATE mesas SET estado = 'Livre' WHERE id IN ($mesa_ids_str)";
            $db->exec($query);
            
            // Atualizar reservas relacionadas para concluído
            $query = "UPDATE reservas SET status = 'Concluído' 
                     WHERE mesa_id IN ($mesa_ids_str) 
                     AND status = 'Reservado' 
                     AND data = CURDATE()";
            $db->exec($query);
            
            $db->commit();
            
            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Mesas liberadas com sucesso!"
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(["erro" => "Erro ao liberar mesas: " . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["erro" => "Método não permitido"]);
        break;
}

// Função auxiliar para distribuir pessoas
function distribuirPessoas($numPessoas, $numMesas) {
    $distribuicao = [];
    $pessoasPorMesa = floor($numPessoas / $numMesas);
    $pessoasRestantes = $numPessoas % $numMesas;
    
    for ($i = 0; $i < $numMesas; $i++) {
        $pessoas = $pessoasPorMesa;
        if ($i < $pessoasRestantes) {
            $pessoas += 1;
        }
        $distribuicao[] = min($pessoas, 4); // Máximo 4 por mesa
    }
    
    return $distribuicao;
}
?>