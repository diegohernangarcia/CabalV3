<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Panel de Administración de Usuarios, Roles y Auditoría de Seguridad
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Control de acceso restringido exclusivamente a rol ADMIN.
 * 2. Gestión integral de usuarios (CRUD): Creación, edición, activación/desactivación.
 * 3. Desbloqueo de cuentas bloqueadas por fuerza bruta (5 intentos fallidos).
 * 4. Asignación de roles institucionales (ADMIN, OPERADOR, AUDITOR).
 * 5. Restablecimiento administrativo de contraseñas con hashing BCRYPT.
 * 6. Visualizador forense de eventos de auditoría de accesos en tiempo real.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/auth/verificar_sesion.php");
require_once(__DIR__ . "/auth/funciones_auth.php");

// Exigir autenticación y rol de Administrador
requerirRol('ADMIN');

$usuarioActual = obtenerUsuarioActual();
$conn = obtenerConexionAuth();

$mensajeExito = '';
$mensajeError = '';

// Procesar acciones POST protegidas por CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!verificarTokenCsrf($csrfToken)) {
        $mensajeError = 'Error de seguridad CSRF. La sesión expiró o la solicitud es inválida.';
    } else {
        $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

        // 1. CREAR NUEVO USUARIO
        if ($accion === 'crear') {
            $nuevoUsuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
            $email        = isset($_POST['email']) ? trim($_POST['email']) : '';
            $nombre       = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
            $idRol        = isset($_POST['id_rol']) ? intval($_POST['id_rol']) : 0;
            $password     = isset($_POST['password']) ? $_POST['password'] : '';

            if ($nuevoUsuario === '' || $email === '' || $nombre === '' || $idRol <= 0 || $password === '') {
                $mensajeError = 'Todos los campos son obligatorios para crear un usuario.';
            } elseif (strlen($password) < 5) {
                $mensajeError = 'La contraseña debe contener al menos 5 caracteres.';
            } else {
                try {
                    // Verificar que el usuario o email no existan
                    $stmtCheck = $conn->prepare("SELECT id FROM `usuarios` WHERE `usuario` = ? OR `email` = ? LIMIT 1");
                    $stmtCheck->execute(array($nuevoUsuario, $email));
                    if ($stmtCheck->fetch()) {
                        $mensajeError = 'El nombre de usuario o correo electrónico ya se encuentra registrado.';
                    } else {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmtIns = $conn->prepare("INSERT INTO `usuarios` (`id_rol`, `usuario`, `email`, `nombre_completo`, `password_hash`, `activo`, `intentos_fallidos`) VALUES (?, ?, ?, ?, ?, 1, 0)");
                        $stmtIns->execute(array($idRol, $nuevoUsuario, $email, $nombre, $hash));
                        $idNuevo = $conn->lastInsertId();

                        registrarAuditoria($usuarioActual['id'], $usuarioActual['usuario'], "CREACION_USUARIO: {$nuevoUsuario} (ID {$idNuevo})");
                        $mensajeExito = "El usuario '{$nuevoUsuario}' fue creado exitosamente con rol asignado.";
                    }
                } catch (Exception $e) {
                    $mensajeError = 'Error al registrar el usuario: ' . $e->getMessage();
                }
            }
        }

        // 2. EDITAR USUARIO
        elseif ($accion === 'editar') {
            $idEditar = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
            $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
            $nombre   = isset($_POST['nombre_completo']) ? trim($_POST['nombre_completo']) : '';
            $idRol    = isset($_POST['id_rol']) ? intval($_POST['id_rol']) : 0;
            $activo   = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
            $newPass  = isset($_POST['nueva_password']) ? trim($_POST['nueva_password']) : '';

            if ($idEditar <= 0 || $email === '' || $nombre === '' || $idRol <= 0) {
                $mensajeError = 'Datos incompletos para actualizar el usuario.';
            } else {
                try {
                    // Protección: El usuario actual no puede desactivarse a sí mismo
                    if ($idEditar === $usuarioActual['id'] && $activo !== 1) {
                        $mensajeError = 'No puede desactivar su propia cuenta de administrador en uso.';
                    } else {
                        if ($newPass !== '') {
                            if (strlen($newPass) < 5) {
                                $mensajeError = 'La nueva contraseña debe contener al menos 5 caracteres.';
                            } else {
                                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                                $stmtUpd = $conn->prepare("UPDATE `usuarios` SET `email` = ?, `nombre_completo` = ?, `id_rol` = ?, `activo` = ?, `password_hash` = ? WHERE `id` = ?");
                                $stmtUpd->execute(array($email, $nombre, $idRol, $activo, $hash, $idEditar));
                                registrarAuditoria($usuarioActual['id'], $usuarioActual['usuario'], "EDICION_USUARIO_CON_PASSWORD: ID {$idEditar}");
                                $mensajeExito = "Usuario ID {$idEditar} actualizado exitosamente (con cambio de contraseña).";
                            }
                        } else {
                            $stmtUpd = $conn->prepare("UPDATE `usuarios` SET `email` = ?, `nombre_completo` = ?, `id_rol` = ?, `activo` = ? WHERE `id` = ?");
                            $stmtUpd->execute(array($email, $nombre, $idRol, $activo, $idEditar));
                            registrarAuditoria($usuarioActual['id'], $usuarioActual['usuario'], "EDICION_USUARIO: ID {$idEditar}");
                            $mensajeExito = "Usuario ID {$idEditar} actualizado exitosamente.";
                        }
                    }
                } catch (Exception $e) {
                    $mensajeError = 'Error al actualizar el usuario: ' . $e->getMessage();
                }
            }
        }

        // 3. DESBLOQUEAR USUARIO
        elseif ($accion === 'desbloquear') {
            $idDesbloquear = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
            if ($idDesbloquear > 0) {
                try {
                    $stmtDes = $conn->prepare("UPDATE `usuarios` SET `intentos_fallidos` = 0, `bloqueado_hasta` = NULL WHERE `id` = ?");
                    $stmtDes->execute(array($idDesbloquear));
                    registrarAuditoria($usuarioActual['id'], $usuarioActual['usuario'], "DESBLOQUEO_USUARIO: ID {$idDesbloquear}");
                    $mensajeExito = "Cuenta de usuario ID {$idDesbloquear} desbloqueada exitosamente.";
                } catch (Exception $e) {
                    $mensajeError = 'Error al desbloquear usuario: ' . $e->getMessage();
                }
            }
        }

        // 4. ALTERNAR ESTADO (ACTIVAR / DESACTIVAR)
        elseif ($accion === 'toggle_estado') {
            $idToggle = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
            $nuevoEstado = isset($_POST['nuevo_estado']) ? intval($_POST['nuevo_estado']) : 0;

            if ($idToggle === $usuarioActual['id']) {
                $mensajeError = 'No puede modificar el estado de su propia cuenta activa.';
            } elseif ($idToggle > 0) {
                try {
                    $stmtTog = $conn->prepare("UPDATE `usuarios` SET `activo` = ? WHERE `id` = ?");
                    $stmtTog->execute(array($nuevoEstado, $idToggle));
                    $txtAccion = ($nuevoEstado === 1) ? 'ACTIVACION_USUARIO' : 'DESACTIVACION_USUARIO';
                    registrarAuditoria($usuarioActual['id'], $usuarioActual['usuario'], "{$txtAccion}: ID {$idToggle}");
                    $mensajeExito = "Estado del usuario ID {$idToggle} actualizado a " . ($nuevoEstado === 1 ? 'ACTIVO' : 'INACTIVO') . ".";
                } catch (Exception $e) {
                    $mensajeError = 'Error al alternar estado: ' . $e->getMessage();
                }
            }
        }

        // 5. BLANQUEAR CONTRASEÑA DIRECTAMENTE (ADMINISTRADOR)
        elseif ($accion === 'blanquear_password') {
            $idBlanquear = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
            $nuevaClave  = isset($_POST['nueva_password']) ? trim($_POST['nueva_password']) : '';

            if ($idBlanquear <= 0) {
                $mensajeError = 'Identificador de usuario inválido.';
            } elseif (strlen($nuevaClave) < 5) {
                $mensajeError = 'La nueva contraseña debe contener al menos 5 caracteres.';
            } else {
                try {
                    $stmtUser = $conn->prepare("SELECT id, usuario, email, nombre_completo FROM `usuarios` WHERE `id` = ? LIMIT 1");
                    $stmtUser->execute(array($idBlanquear));
                    $target = $stmtUser->fetch();

                    if (!$target) {
                        $mensajeError = 'El usuario seleccionado no existe.';
                    } else {
                        $hash = password_hash($nuevaClave, PASSWORD_BCRYPT);
                        $stmtUpd = $conn->prepare("UPDATE `usuarios` SET `password_hash` = ?, `intentos_fallidos` = 0, `bloqueado_hasta` = NULL WHERE `id` = ?");
                        $stmtUpd->execute(array($hash, $idBlanquear));

                        registrarAuditoria($usuarioActual['id'], $usuarioActual['usuario'], "BLANQUEO_PASSWORD: {$target['usuario']} (ID {$idBlanquear}) por Admin");

                        $mensajeExito = "Contraseña blanqueada exitosamente para el usuario <strong>" . htmlspecialchars($target['usuario'], ENT_QUOTES, 'UTF-8') . "</strong> (" . htmlspecialchars($target['nombre_completo'], ENT_QUOTES, 'UTF-8') . "). Nueva clave asignada: <code style='background:rgba(16,185,129,0.2); padding:2px 8px; border-radius:4px; font-weight:bold; color:#047857;'>" . htmlspecialchars($nuevaClave, ENT_QUOTES, 'UTF-8') . "</code>";
                    }
                } catch (Exception $e) {
                    $mensajeError = 'Error al blanquear contraseña: ' . $e->getMessage();
                }
            }
        }
    }
}

// Consultar lista de usuarios con sus roles
$usuarios = array();
$totalUsuarios = 0;
$totalActivos = 0;
$totalBloqueados = 0;

try {
    $sqlUsuarios = "SELECT u.id, u.id_rol, u.usuario, u.email, u.nombre_completo, u.activo,
                           u.intentos_fallidos, u.bloqueado_hasta, u.ultimo_login, u.creado_en,
                           r.codigo AS rol_codigo, r.nombre AS rol_nombre
                    FROM `usuarios` u
                    INNER JOIN `roles` r ON u.id_rol = r.id
                    ORDER BY u.id ASC";
    $stmtUsers = $conn->query($sqlUsuarios);
    $usuarios = $stmtUsers->fetchAll();

    $ahora = time();
    foreach ($usuarios as $u) {
        $totalUsuarios++;
        if (intval($u['activo']) === 1) {
            $totalActivos++;
        }
        if (!empty($u['bloqueado_hasta']) && strtotime($u['bloqueado_hasta']) > $ahora) {
            $totalBloqueados++;
        }
    }
} catch (Exception $e) {
    $mensajeError = 'Error al consultar usuarios: ' . $e->getMessage();
}

// Consultar roles disponibles para formularios
$roles = array();
try {
    $stmtRoles = $conn->query("SELECT id, codigo, nombre, descripcion FROM `roles` ORDER BY id ASC");
    $roles = $stmtRoles->fetchAll();
} catch (Exception $e) {
    // Silencioso
}

// Consultar últimos 60 registros de auditoría
$auditorias = array();
try {
    $stmtAudit = $conn->query("SELECT a.id, a.id_usuario, a.usuario_ingresado, a.accion, a.ip_origen, a.user_agent, a.fecha_hora,
                                      u.usuario AS usuario_real, u.nombre_completo
                               FROM `auditoria_accesos` a
                               LEFT JOIN `usuarios` u ON a.id_usuario = u.id
                               ORDER BY a.id DESC
                               LIMIT 60");
    $auditorias = $stmtAudit->fetchAll();
} catch (Exception $e) {
    // Silencioso
}

$csrfToken = generarTokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios y Seguridad • Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .admin-main {
            max-width: 1360px;
            margin: 0 auto;
            padding: 28px 24px 60px 24px;
        }

        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .admin-title-wrap h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--navy-900);
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-title-wrap p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
        }

        .stat-card .label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-card .val {
            font-size: 26px;
            font-weight: 800;
            color: var(--navy-900);
        }

        .tabs-header {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid var(--border-light);
            margin-bottom: 24px;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s ease;
        }

        .tab-btn:hover {
            color: var(--primary-600);
        }

        .tab-btn.active {
            color: var(--primary-600);
            border-bottom-color: var(--primary-600);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .data-table-container {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table-toolbar {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid var(--border-light);
            flex-wrap: wrap;
        }

        .table-search {
            display: flex;
            align-items: center;
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            gap: 8px;
            min-width: 280px;
        }

        .table-search input {
            background: none;
            border: none;
            outline: none;
            font-size: 13px;
            width: 100%;
            color: var(--navy-900);
        }

        table.admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            text-align: left;
        }

        table.admin-table th {
            background: var(--surface-subtle);
            color: var(--text-muted);
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.03em;
        }

        table.admin-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--navy-900);
            vertical-align: middle;
        }

        table.admin-table tr:last-child td {
            border-bottom: none;
        }

        table.admin-table tr:hover td {
            background: var(--surface-subtle);
        }

        .badge-role {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-role.ADMIN {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-role.OPERADOR {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-role.AUDITOR {
            background: rgba(126, 34, 206, 0.15);
            color: #7e22ce;
            border: 1px solid rgba(126, 34, 206, 0.3);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-status.activo {
            color: #059669;
        }

        .badge-status.inactivo {
            color: #ef4444;
        }

        .badge-status.bloqueado {
            color: #d97706;
            background: rgba(245, 158, 11, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .action-btns {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-tbl {
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            color: var(--navy-900);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }

        .btn-tbl:hover {
            background: var(--border-light);
        }

        .btn-tbl.primary {
            background: var(--primary-600);
            color: white;
            border-color: var(--primary-600);
        }

        .btn-tbl.primary:hover {
            background: var(--primary-700);
        }

        .btn-tbl.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .btn-tbl.warning:hover {
            background: rgba(245, 158, 11, 0.25);
        }

        .btn-tbl.blanquear {
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
            border-color: rgba(59, 130, 246, 0.35);
        }

        .btn-tbl.blanquear:hover {
            background: rgba(59, 130, 246, 0.22);
            color: #1d4ed8;
        }

        /* Modal Styles */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(10, 15, 29, 0.75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-box {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-head {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-head h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--navy-900);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-body {
            padding: 24px;
        }

        .form-group-adm {
            margin-bottom: 16px;
        }

        .form-group-adm label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-group-adm input, .form-group-adm select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--navy-900);
            font-size: 13px;
            box-sizing: border-box;
            outline: none;
        }

        .form-group-adm input:focus, .form-group-adm select:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px var(--primary-100);
        }

        .modal-foot {
            padding: 16px 24px;
            background: var(--surface-subtle);
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .alert-adm {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-adm.exito {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #047857;
        }

        .alert-adm.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #b91c1c;
        }
    </style>
</head>
<body>

<!-- NAVEGACIÓN INSTITUCIONAL -->
<header class="top-nav">
    <div class="top-nav-inner">
        <a href="index.php" class="brand-group">
            <div class="brand-logo-wrap">
                <img src="img/cabal.png" alt="Logo Cabal" onerror="this.src='img/favicon.png'">
            </div>
            <div class="brand-text">
                <h1>
                    CABAL FSR
                    <span class="brand-badge">Entidad 782</span>
                </h1>
                <p>Familia Rionegrina Solidaria • SeNAF Río Negro</p>
            </div>
        </a>

        <div class="nav-actions">
            <button type="button" class="theme-toggle-btn" id="btn-theme-toggle" title="Cambiar tema">
                <svg class="sun-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg class="moon-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                <span class="theme-toggle-label">Modo</span>
            </button>

            <a href="index.php" class="btn-nav-action">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Panel Principal
            </a>

            <div class="user-pill" title="<?php echo htmlspecialchars($usuarioActual['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="user-avatar">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($usuarioActual['usuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="user-role-badge role-admin">ADMIN</span>
                </div>
            </div>

            <a href="auth/logout.php" class="btn-nav-action btn-logout" title="Cerrar sesión">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Salir</span>
            </a>
        </div>
    </div>
</header>

<main class="admin-main">
    <div class="admin-header">
        <div class="admin-title-wrap">
            <h1>
                🛡️ Administración de Usuarios y Seguridad
            </h1>
            <p>Control de acceso basado en roles (RBAC), desbloqueo de operadores y auditoría forense</p>
        </div>
        <div>
            <button type="button" class="btn-tbl primary" onclick="abrirModalCrear()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nuevo Usuario
            </button>
        </div>
    </div>

    <?php if (!empty($mensajeExito)): ?>
        <div class="alert-adm exito">
            <span>✅ <?php echo htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div class="alert-adm error">
            <span>❌ <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    <?php endif; ?>

    <!-- TARJETAS DE MÉTRICAS -->
    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="label">Usuarios Totales</div>
            <div class="val"><?php echo $totalUsuarios; ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Cuentas Activas</div>
            <div class="val" style="color: #059669;"><?php echo $totalActivos; ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Cuentas Bloqueadas</div>
            <div class="val" style="color: <?php echo $totalBloqueados > 0 ? '#ef4444' : '#64748b'; ?>;"><?php echo $totalBloqueados; ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Roles Definidos</div>
            <div class="val" style="color: #3b82f6;"><?php echo count($roles); ?></div>
        </div>
    </div>

    <!-- PESTAÑAS -->
    <div class="tabs-header">
        <button type="button" class="tab-btn active" onclick="cambiarTab('usuarios', this)">
            👥 Usuarios del Sistema (<?php echo count($usuarios); ?>)
        </button>
        <button type="button" class="tab-btn" onclick="cambiarTab('auditoria', this)">
            📜 Registro de Auditoría de Accesos
        </button>
    </div>

    <!-- TAB 1: USUARIOS -->
    <div id="tab-usuarios" class="tab-content active">
        <div class="data-table-container">
            <div class="table-toolbar">
                <div class="table-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="filtro-usuarios" placeholder="Filtrar por nombre, usuario, rol o email..." onkeyup="filtrarTablaUsuarios()">
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    Mostrando <?php echo count($usuarios); ?> cuentas registradas
                </div>
            </div>

            <table class="admin-table" id="tabla-usuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre y Apellido</th>
                        <th>Email Institucional</th>
                        <th>Rol</th>
                        <th>Estado / Seguridad</th>
                        <th>Último Acceso</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): 
                        $estaBloqueado = (!empty($u['bloqueado_hasta']) && strtotime($u['bloqueado_hasta']) > time());
                    ?>
                    <tr>
                        <td><strong>#<?php echo $u['id']; ?></strong></td>
                        <td><code><?php echo htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td><?php echo htmlspecialchars($u['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <span class="badge-role <?php echo htmlspecialchars($u['rol_codigo'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($u['rol_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($estaBloqueado): ?>
                                <span class="badge-status bloqueado" title="Bloqueado hasta: <?php echo $u['bloqueado_hasta']; ?>">
                                    🔒 Bloqueado (<?php echo intval($u['intentos_fallidos']); ?> fallos)
                                </span>
                            <?php elseif (intval($u['activo']) === 1): ?>
                                <span class="badge-status activo">● Activo</span>
                            <?php else: ?>
                                <span class="badge-status inactivo">○ Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo !empty($u['ultimo_login']) ? htmlspecialchars($u['ultimo_login'], ENT_QUOTES, 'UTF-8') : '<span style="color:var(--text-muted);">Nunca</span>'; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-btns" style="justify-content: flex-end;">
                                <?php if ($estaBloqueado): ?>
                                    <form method="POST" action="admin_usuarios.php" style="display:inline;" onsubmit="return confirm('¿Desbloquear la cuenta de <?php echo htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8'); ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="accion" value="desbloquear">
                                        <input type="hidden" name="id_usuario" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn-tbl warning" title="Desbloquear cuenta de inmediato">
                                            🔓 Desbloquear
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="btn-tbl" onclick='abrirModalEditar(<?php echo json_encode($u); ?>)'>
                                    ✏️ Editar
                                </button>

                                <button type="button" class="btn-tbl blanquear" onclick='abrirModalBlanquear(<?php echo json_encode(array("id" => $u["id"], "usuario" => $u["usuario"], "nombre" => $u["nombre_completo"])); ?>)' title="Blanquear contraseña de <?php echo htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8'); ?>">
                                    🔑 Blanquear
                                </button>

                                <?php if ($u['id'] !== $usuarioActual['id']): ?>
                                    <form method="POST" action="admin_usuarios.php" style="display:inline;" onsubmit="return confirm('¿Confirma cambiar el estado de <?php echo htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8'); ?>?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="accion" value="toggle_estado">
                                        <input type="hidden" name="id_usuario" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?php echo intval($u['activo']) === 1 ? '0' : '1'; ?>">
                                        <button type="submit" class="btn-tbl" title="<?php echo intval($u['activo']) === 1 ? 'Desactivar cuenta' : 'Activar cuenta'; ?>">
                                            <?php echo intval($u['activo']) === 1 ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: AUDITORÍA -->
    <div id="tab-auditoria" class="tab-content">
        <div class="data-table-container">
            <div class="table-toolbar">
                <div class="table-search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="filtro-auditoria" placeholder="Buscar en auditoría (usuario, acción, IP)..." onkeyup="filtrarTablaAuditoria()">
                </div>
                <div style="font-size: 12px; color: var(--text-muted);">
                    Mostrando últimos 60 eventos de seguridad
                </div>
            </div>

            <table class="admin-table" id="tabla-auditoria">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Usuario Identificado</th>
                        <th>Acción Registrada</th>
                        <th>IP Origen</th>
                        <th>Navegador / User-Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditorias)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">No se registran eventos de auditoría aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($auditorias as $a): 
                            $esOk = (strpos($a['accion'], 'OK') !== false);
                            $esFallo = (strpos($a['accion'], 'FALLID') !== false || strpos($a['accion'], 'BLOQUEO') !== false);
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($a['fecha_hora'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars(!empty($a['usuario_real']) ? $a['usuario_real'] : $a['usuario_ingresado'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php if (!empty($a['nombre_completo'])): ?>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($a['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight:700; color: <?php echo $esOk ? '#059669' : ($esFallo ? '#dc2626' : '#2563eb'); ?>;">
                                    <?php echo htmlspecialchars($a['accion'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><code><?php echo htmlspecialchars($a['ip_origen'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($a['user_agent'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($a['user_agent'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- MODAL CREAR USUARIO -->
<div class="modal-backdrop" id="modal-crear">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Nuevo Usuario del Sistema</h3>
            <button type="button" class="modal-close" onclick="cerrarModal('modal-crear')">&times;</button>
        </div>
        <form method="POST" action="admin_usuarios.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="crear">

            <div class="modal-body">
                <div class="form-group-adm">
                    <label for="nuevo_usuario">Identificador / Usuario *</label>
                    <input type="text" id="nuevo_usuario" name="usuario" placeholder="ej: jgonzalez" required pattern="^[a-zA-Z0-9_\-\.]{3,30}$">
                </div>

                <div class="form-group-adm">
                    <label for="nuevo_nombre">Nombre y Apellido Completo *</label>
                    <input type="text" id="nuevo_nombre" name="nombre_completo" placeholder="ej: Juan González" required>
                </div>

                <div class="form-group-adm">
                    <label for="nuevo_email">Correo Electrónico Institucional *</label>
                    <input type="email" id="nuevo_email" name="email" placeholder="ej: jgonzalez@senaf.rionegro.gov.ar" required>
                </div>

                <div class="form-group-adm">
                    <label for="nuevo_rol">Rol Institucional *</label>
                    <select id="nuevo_rol" name="id_rol" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $r['codigo'] === 'OPERADOR' ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['nombre'] . ' (' . $r['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-adm">
                    <label for="nuevo_password">Contraseña Inicial (mínimo 5 caracteres) *</label>
                    <input type="password" id="nuevo_password" name="password" required minlength="5">
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-tbl" onclick="cerrarModal('modal-crear')">Cancelar</button>
                <button type="submit" class="btn-tbl primary">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR USUARIO -->
<div class="modal-backdrop" id="modal-editar">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Editar Usuario: <span id="lbl-edit-usuario"></span></h3>
            <button type="button" class="modal-close" onclick="cerrarModal('modal-editar')">&times;</button>
        </div>
        <form method="POST" action="admin_usuarios.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_usuario" id="edit_id_usuario">

            <div class="modal-body">
                <div class="form-group-adm">
                    <label for="edit_nombre">Nombre y Apellido Completo *</label>
                    <input type="text" id="edit_nombre" name="nombre_completo" required>
                </div>

                <div class="form-group-adm">
                    <label for="edit_email">Correo Electrónico Institucional *</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group-adm">
                    <label for="edit_rol">Rol Institucional *</label>
                    <select id="edit_rol" name="id_rol" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>">
                                <?php echo htmlspecialchars($r['nombre'] . ' (' . $r['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-adm">
                    <label for="edit_activo">Estado de Cuenta</label>
                    <select id="edit_activo" name="activo">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo / Desactivado</option>
                    </select>
                </div>

                <div class="form-group-adm" style="margin-top: 20px; border-top: 1px dashed var(--border-light); padding-top: 14px;">
                    <label for="edit_password">Nueva Contraseña (dejar en blanco para mantener actual)</label>
                    <input type="password" id="edit_password" name="nueva_password" placeholder="••••••••" minlength="5">
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-tbl" onclick="cerrarModal('modal-editar')">Cancelar</button>
                <button type="submit" class="btn-tbl primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL BLANQUEAR CONTRASEÑA -->
<div class="modal-backdrop" id="modal-blanquear">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🔑 Blanquear Contraseña: <span id="lbl-blanquear-usuario" style="color:var(--primary-600);"></span></h3>
            <button type="button" class="modal-close" onclick="cerrarModal('modal-blanquear')">&times;</button>
        </div>
        <form method="POST" action="admin_usuarios.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="blanquear_password">
            <input type="hidden" name="id_usuario" id="blanquear_id_usuario">

            <div class="modal-body">
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 0; line-height: 1.5;">
                    Está por restablecer directamente la clave de acceso para: <strong id="lbl-blanquear-nombre" style="color:var(--navy-900);"></strong>.
                    El usuario podrá iniciar sesión inmediatamente con la nueva contraseña que asigne.
                </p>

                <div class="form-group-adm">
                    <label for="blanquear_password_input">Nueva Contraseña (mínimo 5 caracteres) *</label>
                    <input type="text" id="blanquear_password_input" name="nueva_password" placeholder="Ingrese nueva contraseña" required minlength="5" style="font-family: monospace; font-size: 14px; font-weight: 600;">
                </div>

                <div style="margin-top: 12px;">
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">Sugerencias rápidas:</div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="btn-tbl" onclick="asignarClaveSugerida('12345')">Usar "12345"</button>
                        <button type="button" class="btn-tbl" onclick="asignarClaveSugerida('cabal2026')">Usar "cabal2026"</button>
                        <button type="button" class="btn-tbl" onclick="generarClaveAleatoria()">🎲 Generar Clave</button>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn-tbl" onclick="cerrarModal('modal-blanquear')">Cancelar</button>
                <button type="submit" class="btn-tbl primary" style="background:#2563eb; border-color:#2563eb;">Confirmar Blanqueo</button>
            </div>
        </form>
    </div>
</div>

<script>
    function cambiarTab(nombre, btn) {
        var contents = document.querySelectorAll('.tab-content');
        contents.forEach(function(c) { c.classList.remove('active'); });

        var tabs = document.querySelectorAll('.tab-btn');
        tabs.forEach(function(t) { t.classList.remove('active'); });

        document.getElementById('tab-' + nombre).classList.add('active');
        btn.classList.add('active');
    }

    function abrirModalCrear() {
        document.getElementById('modal-crear').style.display = 'flex';
    }

    function abrirModalEditar(user) {
        document.getElementById('edit_id_usuario').value = user.id;
        document.getElementById('lbl-edit-usuario').innerText = user.usuario;
        document.getElementById('edit_nombre').value = user.nombre_completo;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_rol').value = user.id_rol;
        document.getElementById('edit_activo').value = user.activo;
        document.getElementById('edit_password').value = '';
        document.getElementById('modal-editar').style.display = 'flex';
    }

    function abrirModalBlanquear(user) {
        document.getElementById('blanquear_id_usuario').value = user.id;
        document.getElementById('lbl-blanquear-usuario').innerText = user.usuario;
        document.getElementById('lbl-blanquear-nombre').innerText = user.nombre + ' (' + user.usuario + ')';
        document.getElementById('blanquear_password_input').value = '12345';
        document.getElementById('modal-blanquear').style.display = 'flex';
        document.getElementById('blanquear_password_input').select();
    }

    function asignarClaveSugerida(clave) {
        var inp = document.getElementById('blanquear_password_input');
        inp.value = clave;
        inp.focus();
        inp.select();
    }

    function generarClaveAleatoria() {
        var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        var res = '';
        for (var i = 0; i < 8; i++) {
            res += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        var inp = document.getElementById('blanquear_password_input');
        inp.value = res;
        inp.focus();
        inp.select();
    }

    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    window.onclick = function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            e.target.style.display = 'none';
        }
    };

    function filtrarTablaUsuarios() {
        var val = document.getElementById('filtro-usuarios').value.toLowerCase();
        var rows = document.querySelectorAll('#tabla-usuarios tbody tr');
        rows.forEach(function(r) {
            var txt = r.textContent.toLowerCase();
            r.style.display = txt.indexOf(val) !== -1 ? '' : 'none';
        });
    }

    function filtrarTablaAuditoria() {
        var val = document.getElementById('filtro-auditoria').value.toLowerCase();
        var rows = document.querySelectorAll('#tabla-auditoria tbody tr');
        rows.forEach(function(r) {
            var txt = r.textContent.toLowerCase();
            r.style.display = txt.indexOf(val) !== -1 ? '' : 'none';
        });
    }
</script>

</body>
</html>
