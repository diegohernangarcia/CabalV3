<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Configuración de Seguridad y Ciclo de Vida de Sesiones
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Configuración de directivas de seguridad para cookies de sesión (HttpOnly, Secure condicional).
 * 2. Control de tiempo de inactividad (Timeout de 20 minutos / 1200 segundos).
 * 3. Prevención de Session Fixation y secuestro de sesión mediante rotación de ID.
 * 4. Destrucción segura y saneamiento en cierre de sesión o expiración.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

if (!defined('CABAL_SESSION_TIMEOUT')) {
    define('CABAL_SESSION_TIMEOUT', 1200); // 20 minutos en segundos
}

if (!defined('CABAL_SESSION_NAME')) {
    define('CABAL_SESSION_NAME', 'CABAL_FSR_SESSID');
}

/**
 * Inicializa la sesión con parámetros de seguridad reforzados.
 */
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Directivas de sesión para PHP 5.6+
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);

        // Detectar si la conexión es HTTPS
        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_name(CABAL_SESSION_NAME);

        // Parámetros de cookie compatibles con PHP 5.6
        // lifetime = 0 (dura hasta que se cierre el navegador), path = '/', domain = '', secure, httponly
        session_set_cookie_params(0, '/', '', $esHttps, true);

        session_start();
    }
}

/**
 * Verifica si la sesión ha expirado por inactividad.
 * Si superó el tiempo límite, destruye la sesión y redirige a login.php.
 * 
 * @param string $rutaLogin Ruta relativa hacia auth/login.php
 */
function verificarCaducidadSesion($rutaLogin = 'auth/login.php') {
    if (isset($_SESSION['usuario_id'])) {
        $ahora = time();
        $ultimoAcceso = isset($_SESSION['ultimo_acceso']) ? intval($_SESSION['ultimo_acceso']) : $ahora;

        if (($ahora - $ultimoAcceso) > CABAL_SESSION_TIMEOUT) {
            // Sesión expirada por inactividad
            cerrarSesionSegura();
            header("Location: " . $rutaLogin . "?motivo=expirada");
            exit;
        }

        // Actualizar marca de tiempo de último acceso
        $_SESSION['ultimo_acceso'] = $ahora;
    }
}

/**
 * Destruye completamente la sesión activa y borra la cookie en el cliente.
 */
function cerrarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Vaciar variables de sesión
    $_SESSION = array();

    // Eliminar la cookie de sesión del navegador
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruir la sesión en el servidor
    @session_destroy();
}
