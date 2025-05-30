<?php
// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'verificar_sessao.php';

header('Content-Type: application/json');

$logado = verificarLogin();
$cliente = null;

if ($logado) {
    $cliente = obterClienteLogado();
}

echo json_encode([
    'logado' => $logado,
    'cliente' => $cliente
]);
?>