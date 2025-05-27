<?php
// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarAdmin() {
    return isset($_SESSION['admin_logado']);
}

function obterAdminLogado() {
    return $_SESSION['admin_logado'] ?? null;
}

function logoutAdmin() {
    session_destroy();
    header("Location: ../Login.html");
    exit;
}
?>