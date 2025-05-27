<?php
session_start();
require_once 'verificar_sessao.php';

if (!verificarLogin()) {
    header("Location: Login.html");
    exit;
}
?>