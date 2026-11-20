<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Middleware de Control de Acceso y Verificación de Sesiones
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Inicializa y supervisa la sesión segura del usuario.
 * 2. Verifica la caducidad por inactividad (20 minutos) y redirige a login si expiró.
 * 3. Provee guardas de autorización por roles (ADMIN, OPERADOR, AUDITOR).
 * 4. Expone funciones auxiliares para plantillas (obtenerUsuarioActual, tienePermiso).
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/config_sesion.php");
require_once(__DIR__ . "/funciones_auth.php");

// Inicializar sesión segura en cada solicitud
iniciarSesionSegura();

/**
 * Calcula la ruta relativa adecuada hacia auth/login.php desde el script actual.
 * 
 * @return string
 */
function obtenerRutaRelativaLogin() {
    $scriptFile = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : '';
    $dirApp = realpath(__DIR__ . '/..');

    if ($scriptFile && $dirApp) {
        $scriptDir = dirname($scriptFile);
        if ($scriptDir === $dirApp) {
            return 'auth/login.php';
        }
        if ($scriptDir === realpath(__DIR__)) {
            return 'login.php';
        }
        // Si está en un subdirectorio directo de la aplicación (ej: /Altas, /Novcar, /BD)
        if (dirname($scriptDir) === $dirApp) {
            return '../auth/login.php';
        }
        // Subdirectorios de segundo nivel
        if (dirname(dirname($scriptDir)) === $dirApp) {
            return '../../auth/login.php';
        }
    }
    return 'auth/login.php';
}

/**
 * Determina si el usuario actual posee una sesión activa y válida.
 * 
 * @return bool
 */
function estaAutenticado() {
    return !empty($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id']);
}

/**
 * Obtiene los datos del usuario actualmente conectado.
 * 
 * @return array|null
 */
function obtenerUsuarioActual() {
    if (!estaAutenticado()) {
        return null;
    }
    return array(
        'id'          => intval($_SESSION['usuario_id']),
        'usuario'     => isset($_SESSION['usuario_login']) ? $_SESSION['usuario_login'] : '',
        'email'       => isset($_SESSION['usuario_email']) ? $_SESSION['usuario_email'] : '',
        'nombre'      => isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : '',
        'rol_id'      => isset($_SESSION['usuario_rol_id']) ? intval($_SESSION['usuario_rol_id']) : 0,
        'rol_codigo'  => isset($_SESSION['usuario_rol_codigo']) ? $_SESSION['usuario_rol_codigo'] : 'INVITADO',
        'rol_nombre'  => isset($_SESSION['usuario_rol_nombre']) ? $_SESSION['usuario_rol_nombre'] : 'Invitado',
        'permisos'    => isset($_SESSION['usuario_permisos']) && is_array($_SESSION['usuario_permisos']) ? $_SESSION['usuario_permisos'] : array()
    );
}

/**
 * Verifica si el usuario actual posee un permiso específico.
 * Los usuarios con rol ADMIN poseen todos los permisos por defecto.
 * 
 * @param string $clavePermiso
 * @return bool
 */
function tienePermiso($clavePermiso) {
    if (!estaAutenticado()) {
        return false;
    }
    $rol = isset($_SESSION['usuario_rol_codigo']) ? $_SESSION['usuario_rol_codigo'] : '';
    if ($rol === 'ADMIN') {
        return true;
    }
    $permisos = isset($_SESSION['usuario_permisos']) && is_array($_SESSION['usuario_permisos']) ? $_SESSION['usuario_permisos'] : array();
    return !empty($permisos[$clavePermiso]);
}

/**
 * Guarda de autenticación obligatoria.
 * Si el usuario no ha iniciado sesión o expiró, redirige al login guardando la URL de retorno.
 * 
 * @param string|null $rutaLogin Ruta personalizada al login (opcional)
 */
function requerirAutenticacion($rutaLogin = null) {
    if ($rutaLogin === null) {
        $rutaLogin = obtenerRutaRelativaLogin();
    }

    // Verificar caducidad por inactividad
    verificarCaducidadSesion($rutaLogin);

    if (!estaAutenticado()) {
        // Guardar la URL actual para redirigir luego de un login exitoso
        if (!empty($_SERVER['REQUEST_URI'])) {
            $_SESSION['url_redireccion'] = $_SERVER['REQUEST_URI'];
        }
        header("Location: " . $rutaLogin . "?motivo=requerido");
        exit;
    }
}

/**
 * Guarda de autorización por rol institucional.
 * Exige que el usuario pertenezca a uno de los roles autorizados.
 * 
 * @param array|string $rolesPermitidos Ej: 'ADMIN' o array('ADMIN', 'OPERADOR')
 * @param string|null $rutaDenegado Ruta a donde redirigir en caso de denegación (opcional)
 */
function requerirRol($rolesPermitidos, $rutaDenegado = null) {
    requerirAutenticacion();

    if (!is_array($rolesPermitidos)) {
        $rolesPermitidos = array($rolesPermitidos);
    }

    $rolActual = isset($_SESSION['usuario_rol_codigo']) ? $_SESSION['usuario_rol_codigo'] : '';

    if (!in_array($rolActual, $rolesPermitidos, true)) {
        if ($rutaDenegado !== null) {
            header("Location: " . $rutaDenegado);
            exit;
        }

        // Renderizar vista institucional de Acceso Denegado (HTTP 403)
        header("HTTP/1.1 403 Forbidden");
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso Denegado • Cabal FSR</title>
            <link rel="stylesheet" href="<?php echo htmlspecialchars(obtenerRutaRelativaLogin() === 'login.php' ? '../css/dashboard.css' : 'css/dashboard.css', ENT_QUOTES, 'UTF-8'); ?>">
            <style>
                body {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background: #0a0f1d;
                    color: #f8fafc;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    box-sizing: border-box;
                }
                .denied-card {
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-radius: 16px;
                    padding: 40px;
                    max-width: 520px;
                    text-align: center;
                    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
                }
                .denied-icon {
                    width: 64px;
                    height: 64px;
                    background: rgba(239, 68, 68, 0.15);
                    border: 2px solid #ef4444;
                    color: #ef4444;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 20px;
                    font-size: 32px;
                }
                h1 { font-size: 24px; margin-top: 0; color: #f8fafc; }
                p { color: #94a3b8; line-height: 1.6; font-size: 14px; margin-bottom: 24px; }
                .btn-back {
                    display: inline-block;
                    background: #2563eb;
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: background 0.2s ease;
                }
                .btn-back:hover { background: #1d4ed8; }
            </style>
        </head>
        <body>
            <div class="denied-card">
                <div class="denied-icon">⚠️</div>
                <h1>Acceso No Autorizado (403)</h1>
                <p>Su rol actual (<strong><?php echo htmlspecialchars(isset($_SESSION['usuario_rol_nombre']) ? $_SESSION['usuario_rol_nombre'] : $rolActual, ENT_QUOTES, 'UTF-8'); ?></strong>) no cuenta con los permisos necesarios para acceder a este módulo institucional.</p>
                <a href="<?php echo htmlspecialchars(obtenerRutaRelativaLogin() === 'login.php' ? '../index.php' : 'index.php', ENT_QUOTES, 'UTF-8'); ?>" class="btn-back">Volver al Panel Principal</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
