<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Pantalla de Inicio de Sesión Institucional (Login)
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Formulario institucional con protección contra CSRF.
 * 2. Autenticación con algoritmo BCRYPT y protección anti-fuerza bruta (bloqueo tras 5 intentos).
 * 3. Notificación de motivos de redirección (cierre de sesión, caducidad por inactividad).
 * 4. Interfaz adaptativa (modo oscuro/claro) con doble cabecera institucional (SeNAF + Cabal).
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/config_sesion.php");
require_once(__DIR__ . "/funciones_auth.php");

iniciarSesionSegura();

// Si el usuario ya está autenticado, redirigir al panel principal
if (!empty($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$exito = '';
$usuarioIngresado = '';

// Procesar mensajes informativos vía GET
$motivo = isset($_GET['motivo']) ? $_GET['motivo'] : '';
if ($motivo === 'logout') {
    $exito = 'Sesión cerrada correctamente. Hasta pronto.';
} elseif ($motivo === 'expirada') {
    $error = 'Su sesión ha caducado por 20 minutos de inactividad. Por favor, ingrese nuevamente.';
} elseif ($motivo === 'requerido') {
    $error = 'Debe autenticarse para acceder a las funciones del sistema.';
} elseif ($motivo === 'clave_actualizada') {
    $exito = 'Su contraseña se actualizó exitosamente. Ingrese con sus nuevas credenciales.';
}

// Procesar formulario de login vía POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecibido = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $usuarioIngresado = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!verificarTokenCsrf($tokenRecibido)) {
        $error = 'Fallo de seguridad CSRF. La sesión expiró o el formulario es inválido. Por favor, recargue la página.';
    } else {
        $resultado = autenticarUsuario($usuarioIngresado, $password);

        if ($resultado['exito']) {
            // Establecer variables de sesión
            fijarSesionUsuario($resultado['usuario']);

            // Determinar destino de redirección seguro (evitar Open Redirect)
            $destino = '../index.php';
            if (!empty($_SESSION['url_redireccion'])) {
                $urlGuardada = $_SESSION['url_redireccion'];
                unset($_SESSION['url_redireccion']);
                // Permitir únicamente rutas relativas locales
                if (strpos($urlGuardada, 'http://') === false && strpos($urlGuardada, 'https://') === false) {
                    $destino = $urlGuardada;
                }
            }

            header("Location: " . $destino);
            exit;
        } else {
            $error = $resultado['error'];
        }
    }
}

$csrfToken = generarTokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión • Nexo FRS • Entidad 782</title>
    <link rel="icon" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        body.login-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #172554 0%, #0a0f1d 75%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 24px;
            box-sizing: border-box;
            color: #f8fafc;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(59, 130, 246, 0.25);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-banner-container {
            width: 100%;
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.85) 0%, rgba(15, 23, 42, 0.4) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 24px 20px 16px 20px;
            box-sizing: border-box;
            text-align: center;
        }

        .login-banner-container img.banner-nexo {
            width: 100%;
            max-width: 360px;
            height: auto;
            max-height: 140px;
            object-fit: contain;
            filter: drop-shadow(0 4px 14px rgba(0, 0, 0, 0.5));
            display: inline-block;
        }

        .login-header {
            padding: 16px 28px 18px 28px;
            background: rgba(15, 23, 42, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }

        .login-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .login-logos img.logo-cabal {
            height: 44px;
            width: auto;
            object-fit: contain;
        }

        .login-logos img.logo-senaf {
            height: 40px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
        }

        .login-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-title .badge {
            font-size: 11px;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.4);
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .login-subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
        }

        .login-body-content {
            padding: 28px;
        }

        .alert-box {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper input {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
            color: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .input-wrapper input:focus {
            border-color: #3b82f6;
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .input-wrapper .toggle-pwd-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .input-wrapper .toggle-pwd-btn:hover {
            color: #ffffff;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 13px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .login-footer {
            padding: 16px 28px 24px 28px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
        }

        .login-footer a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .login-footer a:hover {
            color: #93c5fd;
            text-decoration: underline;
        }

        .security-badge {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-wrapper">
        <div class="login-banner-container">
            <img src="../img/NexoFRS.png" alt="Nexo FRS" class="banner-nexo">
        </div>

        <div class="login-header">
            <h1 class="login-title">
                Nexo FRS
                <span class="badge">Entidad 782</span>
            </h1>
            <p class="login-subtitle">Gestión Cabal • Familia Rionegrina Solidaria • SeNAF</p>
        </div>

        <div class="login-body-content">
            <?php if (!empty($error)): ?>
                <div class="alert-box alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="alert-box alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?php echo htmlspecialchars($exito, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="usuario">Usuario o Correo Institucional</label>
                    <div class="input-wrapper">
                        <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuarioIngresado, ENT_QUOTES, 'UTF-8'); ?>" placeholder="ej: dgarcia" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="toggle-pwd-btn" id="btn-toggle-pwd" title="Mostrar/Ocultar contraseña" aria-label="Mostrar u ocultar contraseña">
                            <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btn-login-submit">
                    Iniciar Sesión
                </button>
            </form>
        </div>

        <div class="login-footer">
            <span style="color: #94a3b8; font-size: 12px;">Para blanqueo o reseteo de contraseñas, solicítelo al Administrador del sistema.</span>
            <div class="security-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span>Acceso seguro cifrado BCRYPT • Timeout 20 min</span>
            </div>
        </div>
    </div>

    <script>
        // Funcionalidad para mostrar/ocultar contraseña
        var pwdInput = document.getElementById('password');
        var toggleBtn = document.getElementById('btn-toggle-pwd');
        var eyeIcon = document.getElementById('eye-icon');

        if (toggleBtn && pwdInput) {
            toggleBtn.addEventListener('click', function() {
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                } else {
                    pwdInput.type = 'password';
                    eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                }
            });
        }
    </script>
</body>
</html>
