<?php
header("Content-Type: application/json; charset=UTF-8");

// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../models/Cliente.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(["erro" => "Falha na conexão com o banco de dados"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $cliente = new Cliente($db);
    
    // Parâmetros de busca
    $termo = $_GET['termo'] ?? '';
    $tipo = $_GET['tipo'] ?? 'nome'; // nome, email, telefone
    
    try {
        $clientes = [];
        
        if (!empty($termo)) {
            switch($tipo) {
                case 'email':
                    $clienteEncontrado = $cliente->buscarPorEmail($termo);
                    if ($clienteEncontrado) {
                        $clientes[] = $clienteEncontrado;
                    }
                    break;
                    
                case 'telefone':
                    $telefone_limpo = preg_replace('/\D/', '', $termo);
                    $clienteEncontrado = $cliente->buscarPorTelefone($telefone_limpo);
                    if ($clienteEncontrado) {
                        $clientes[] = $clienteEncontrado;
                    }
                    break;
                    
                case 'nome':
                default:
                    $clientes = $cliente->buscarPorNome($termo);
                    break;
            }
        } else {
            // Se não há termo, buscar todos os clientes (limitado)
            $clientes = $cliente->buscarTodos(50); // Limitar a 50 resultados
        }
        
        echo json_encode([
            "sucesso" => true,
            "clientes" => $clientes
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao buscar clientes: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido"]);
}
?>