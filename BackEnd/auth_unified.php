<?php
// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class AuthUnified {
    
    public static function verificarLogin() {
        return isset($_SESSION['usuario_logado']);
    }
    
    public static function obterUsuarioLogado() {
        return $_SESSION['usuario_logado'] ?? null;
    }
    
    public static function getTipoUsuario() {
        $usuario = self::obterUsuarioLogado();
        return $usuario['tipo'] ?? null;
    }
    
    public static function isCliente() {
        return self::getTipoUsuario() === 'Cliente';
    }
    
    public static function isAdmin() {
        return self::getTipoUsuario() === 'Administrador';
    }
    
    public static function isRececionista() {
        return self::getTipoUsuario() === 'Rececionista';
    }
    
    public static function isAdminOuRececionista() {
        $tipo = self::getTipoUsuario();
        return in_array($tipo, ['Administrador', 'Rececionista']);
    }
    
    public static function logarUsuario($dadosUsuario, $tipo) {
        $_SESSION['usuario_logado'] = [
            'id' => $dadosUsuario['id'],
            'nome' => $dadosUsuario['nome'],
            'email' => $dadosUsuario['email'],
            'tipo' => $tipo,
            'telemovel' => $dadosUsuario['telemovel'] ?? null,
            'data_login' => date('Y-m-d H:i:s')
        ];
        
        // Manter compatibilidade com sistema antigo
        if ($tipo === 'Cliente') {
            $_SESSION['cliente_logado'] = $_SESSION['usuario_logado'];
        } else {
            $_SESSION['admin_logado'] = $_SESSION['usuario_logado'];
        }
    }
    
    public static function logout() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    public static function redirecionarSeNaoLogado($urlRedirect = '../Login.html') {
        if (!self::verificarLogin()) {
            header("Location: $urlRedirect");
            exit;
        }
    }
    
    public static function redirecionarSeTipoIncorreto($tiposPermitidos, $urlRedirect = '../Login.html') {
        if (!self::verificarLogin()) {
            header("Location: $urlRedirect");
            exit;
        }
        
        $tipoAtual = self::getTipoUsuario();
        if (!in_array($tipoAtual, $tiposPermitidos)) {
            header("Location: ../acesso_negado.html");
            exit;
        }
    }
}

// Funções de compatibilidade para não quebrar código existente
function verificarLogin() {
    return AuthUnified::verificarLogin();
}

function obterClienteLogado() {
    $usuario = AuthUnified::obterUsuarioLogado();
    return ($usuario && $usuario['tipo'] === 'Cliente') ? $usuario : null;
}

function verificarAdmin() {
    return AuthUnified::isAdminOuRececionista();
}

function obterAdminLogado() {
    $usuario = AuthUnified::obterUsuarioLogado();
    return AuthUnified::isAdminOuRececionista() ? $usuario : null;
}
?>