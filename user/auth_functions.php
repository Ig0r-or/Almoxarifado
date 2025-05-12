<?php

function startSecureSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start([
            'use_only_cookies' => 1,
            'cookie_lifetime' => 86400,
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict'
        ]);
    }
        
     if (!isset($_SESSION['__last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['__last_regeneration'] = time();
    } elseif (time() - $_SESSION['__last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['__last_regeneration'] = time();
    }
}    

function logoutUser() {
    startSecureSession();
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();

    header("Location: login.php");
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}

function verifySession() {
    startSecureSession();
    
    if (empty($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }
    
    return true;
}
?>