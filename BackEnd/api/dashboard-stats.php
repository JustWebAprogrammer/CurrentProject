<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../verificar_admin.php';

// Verificar se é admin
if (!verificarAdmin()) {
    http_response_code(401);
    echo json_encode(["erro" => "Acesso negado"]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Reservas de hoje
    $query = "SELECT COUNT(*) as total FROM reservas WHERE DATE(data) = CURDATE() AND status IN ('Reservado', 'Concluído')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reservas_hoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de clientes
    $query = "SELECT COUNT(*) as total FROM clientes";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_clientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Mesas ocupadas hoje
    $query = "SELECT COUNT(DISTINCT mesa_id) as total FROM reservas WHERE DATE(data) = CURDATE() AND status = 'Reservado'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $mesas_ocupadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de mesas
    $query = "SELECT COUNT(*) as total FROM mesas";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_mesas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Taxa de ocupação
    $taxa_ocupacao = $total_mesas > 0 ? round(($mesas_ocupadas / $total_mesas) * 100, 1) : 0;
    
    echo json_encode([
        'reservas_hoje' => $reservas_hoje,
        'total_clientes' => $total_clientes,
        'mesas_ocupadas' => $mesas_ocupadas,
        'taxa_ocupacao' => $taxa_ocupacao
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro interno do servidor"]);
}
?>