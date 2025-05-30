<?php
// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarLogin() {
    return isset($_SESSION['cliente_logado']);
}

function obterClienteLogado() {
    return $_SESSION['cliente_logado'] ?? null;
}

function logout() {
    session_destroy();
    header("Location: ../Login.html");
    exit;
}
?>