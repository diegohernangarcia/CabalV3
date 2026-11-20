<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Funciones de Autenticación, Auditoría y Seguridad
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Autenticación robusta con hashing BCRYPT y protección anti-fuerza bruta (Rate Limiting).
 * 2. Registro forense de auditoría de accesos e intentos fallidos.
 * 3. Gestión y verificación de tokens anti-CSRF para formularios.
 * 4. Control de roles y permisos institucionales.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/config_sesion.php");

/**
 * Obtiene la conexión centralizada a la base de datos para autenticación.
 * 
 * @return PDO
 */
function obtenerConexionAuth() {
    static $conn = null;
    if ($conn === null) {
        $usuario_db = 'pucho';
        $pass_db    = '78900';
        $nombre_db  = 'cabal';
        $host_db    = 'localhost';

        $dsn = "mysql:host={$host_db};dbname={$nombre_db};charset=utf8";
        $conn = new PDO($dsn, $usuario_db, $pass_db, array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ));
    }
    return $conn;
}

/**
 * Obtiene la dirección IP real del cliente.
 * 
 * @return string
 */
function obtenerIpCliente() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
}

/**
 * Registra un evento en la tabla de auditoría de accesos.
 * 
 * @param int|null $idUsuario
 * @param string $usuarioIngresado
 * @param string $accion
 */
function registrarAuditoria($idUsuario, $usuarioIngresado, $accion) {
    try {
        $conn = obtenerConexionAuth();
        $ip = obtenerIpCliente();
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : 'Desconocido';

        $stmt = $conn->prepare("INSERT INTO `auditoria_accesos` (`id_usuario`, `usuario_ingresado`, `accion`, `ip_origen`, `user_agent`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array($idUsuario, $usuarioIngresado, $accion, $ip, $ua));
    } catch (Exception $e) {
        // Registro de auditoría silencioso ante fallos para no interrumpir el flujo del usuario
        @error_log("Error en auditoría Cabal: " . $e->getMessage());
    }
}

/**
 * Autentica un usuario contra la base de datos con verificación BCRYPT y bloqueo temporal.
 * 
 * @param string $identificador Usuario o Correo Electrónico
 * @param string $password Contraseña en texto plano
 * @return array
 */
function autenticarUsuario($identificador, $password) {
    $identificador = trim($identificador);
    if ($identificador === '' || $password === '') {
        return array('exito' => false, 'error' => 'Por favor ingrese su usuario y contraseña.');
    }

    try {
        $conn = obtenerConexionAuth();

        // Buscar usuario con su rol asociado
        $sql = "SELECT u.id, u.id_rol, u.usuario, u.email, u.nombre_completo, u.password_hash, 
                       u.activo, u.intentos_fallidos, u.bloqueado_hasta,
                       r.codigo AS rol_codigo, r.nombre AS rol_nombre, r.permisos_json
                FROM `usuarios` u
                INNER JOIN `roles` r ON u.id_rol = r.id
                WHERE u.usuario = :id1 OR u.email = :id2
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':id1' => $identificador, ':id2' => $identificador));
        $user = $stmt->fetch();

        if (!$user) {
            registrarAuditoria(null, $identificador, 'LOGIN_FALLIDO_USUARIO_INEXISTENTE');
            return array('exito' => false, 'error' => 'Credenciales inválidas. Verifique los datos ingresados.');
        }

        $userId = intval($user['id']);

        // 1. Verificar si la cuenta está activa
        if (intval($user['activo']) !== 1) {
            registrarAuditoria($userId, $identificador, 'INTENTO_CUENTA_INACTIVA');
            return array('exito' => false, 'error' => 'Esta cuenta se encuentra desactivada. Contacte al Administrador.');
        }

        // 2. Verificar bloqueo temporal por fuerza bruta
        if (!empty($user['bloqueado_hasta'])) {
            $tiempoBloqueo = strtotime($user['bloqueado_hasta']);
            $ahora = time();
            if ($tiempoBloqueo > $ahora) {
                $minutosRestantes = ceil(($tiempoBloqueo - $ahora) / 60);
                registrarAuditoria($userId, $identificador, 'INTENTO_CUENTA_BLOQUEADA');
                return array(
                    'exito' => false, 
                    'error' => "Cuenta bloqueada temporalmente por reiterados intentos fallidos. Intente nuevamente en {$minutosRestantes} minuto(s)."
                );
            }
        }

        // 3. Verificar contraseña con algoritmo seguro BCRYPT
        if (password_verify($password, $user['password_hash'])) {
            // Login exitoso: restablecer intentos fallidos y actualizar último login
            $stmtUpd = $conn->prepare("UPDATE `usuarios` SET `intentos_fallidos` = 0, `bloqueado_hasta` = NULL, `ultimo_login` = NOW() WHERE `id` = ?");
            $stmtUpd->execute(array($userId));

            registrarAuditoria($userId, $identificador, 'LOGIN_OK');

            // Decodificar permisos JSON
            $permisos = array();
            if (!empty($user['permisos_json'])) {
                $decoded = json_decode($user['permisos_json'], true);
                if (is_array($decoded)) {
                    $permisos = $decoded;
                }
            }

            return array(
                'exito'   => true,
                'usuario' => array(
                    'id'              => $userId,
                    'usuario'         => $user['usuario'],
                    'email'           => $user['email'],
                    'nombre_completo' => $user['nombre_completo'],
                    'id_rol'          => intval($user['id_rol']),
                    'rol_codigo'      => $user['rol_codigo'],
                    'rol_nombre'      => $user['rol_nombre'],
                    'permisos'        => $permisos
                )
            );
        } else {
            // Contraseña errónea: incrementar contador de fallos
            $intentos = intval($user['intentos_fallidos']) + 1;
            $bloquearHasta = null;
            $accionAudit = 'LOGIN_FALLIDO';
            $mensajeError = 'Credenciales inválidas. ';

            if ($intentos >= 5) {
                // Bloqueo temporal por 15 minutos (900 seg)
                $bloquearHasta = date('Y-m-d H:i:s', time() + 900);
                $accionAudit = 'BLOQUEO_TEMPORAL';
                $mensajeError = 'Ha superado el límite de 5 intentos fallidos. Su cuenta ha sido bloqueada temporalmente por 15 minutos.';
            } else {
                $restantes = 5 - $intentos;
                $mensajeError .= "Intentos restantes antes del bloqueo: {$restantes}.";
            }

            $stmtFail = $conn->prepare("UPDATE `usuarios` SET `intentos_fallidos` = ?, `bloqueado_hasta` = ? WHERE `id` = ?");
            $stmtFail->execute(array($intentos, $bloquearHasta, $userId));

            registrarAuditoria($userId, $identificador, $accionAudit);

            return array('exito' => false, 'error' => $mensajeError);
        }

    } catch (Exception $e) {
        return array('exito' => false, 'error' => 'Error de autenticación en el servidor: ' . $e->getMessage());
    }
}

/**
 * Genera un token CSRF y lo guarda en sesión.
 * 
 * @return string
 */
function generarTokenCsrf() {
    iniciarSesionSegura();
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            $_SESSION['csrf_token'] = bin2hex(md5(uniqid(rand(), true)));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF recibido contra el almacenado en sesión.
 * 
 * @param string $token
 * @return bool
 */
function verificarTokenCsrf($token) {
    iniciarSesionSegura();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    if (function_exists('hash_equals')) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    return $_SESSION['csrf_token'] === $token;
}

/**
 * Establece la sesión activa del usuario autenticado.
 * 
 * @param array $datosUsuario
 */
function fijarSesionUsuario($datosUsuario) {
    iniciarSesionSegura();

    // Rotación obligatoria del identificador de sesión para evitar fijación
    session_regenerate_id(true);

    $_SESSION['usuario_id']          = $datosUsuario['id'];
    $_SESSION['usuario_login']       = $datosUsuario['usuario'];
    $_SESSION['usuario_email']       = $datosUsuario['email'];
    $_SESSION['usuario_nombre']      = $datosUsuario['nombre_completo'];
    $_SESSION['usuario_rol_id']      = $datosUsuario['id_rol'];
    $_SESSION['usuario_rol_codigo']  = $datosUsuario['rol_codigo'];
    $_SESSION['usuario_rol_nombre']  = $datosUsuario['rol_nombre'];
    $_SESSION['usuario_permisos']    = $datosUsuario['permisos'];
    $_SESSION['ultimo_acceso']       = time();
}
