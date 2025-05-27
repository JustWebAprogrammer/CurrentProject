<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../auth_unified.php';

AuthUnified::logout();

header("Location: ../../Login.html?sucesso=" . urlencode("Logout realizado com sucesso!"));
exit;
?>