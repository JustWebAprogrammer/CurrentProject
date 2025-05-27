<?php
require_once __DIR__ . '/../auth_unified.php';

class AuthMiddleware {
    
    public static function protegerRota($tiposPermitidos = null) {
        if (!AuthUnified::verificarLogin()) {
            self::redirecionarLogin();
        }
        
        if ($tiposPermitidos && !in_array(AuthUnified::getTipoUsuario(), $tiposPermitidos)) {
            self::redirecionarAcessoNegado();
        }
    }
    
    public static function protegerRotaCliente() {
        self::protegerRota(['Cliente']);
    }
    
    public static function protegerRotaAdmin() {
        self::protegerRota(['Administrador', 'Rececionista']);
    }
    
    public static function protegerRotaApenasAdmin() {
        self::protegerRota(['Administrador']);
    }
    
    private static function redirecionarLogin() {
        if (self::isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(["erro" => "Usuário não autenticado"]);
        } else {
            header("Location: ../../Login.html");
        }
        exit;
    }
    
    private static function redirecionarAcessoNegado() {
        if (self::isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(["erro" => "Acesso não autorizado"]);
        } else {
            header("Location: ../../acesso_negado.html");
        }
        exit;
    }
    
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}
?>