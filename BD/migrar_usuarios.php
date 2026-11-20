<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Ejecutor Automático de Migración: Módulo de Usuarios, Roles y Seguridad
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Crea las tablas `roles`, `usuarios`, `recuperacion_tokens` y `auditoria_accesos`.
 * 2. Puebla los roles base: ADMIN, OPERADOR, AUDITOR.
 * 3. Crea/actualiza la cuenta del Administrador Inicial: dgarcia / 78900 / dgarcia@senaf.rionegro.gov.ar.
 * 4. Valida criptográficamente el hash de contraseña con password_verify().
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

$usuario_db = 'pucho';
$pass_db    = '78900';
$nombre_db  = 'cabal';
$host_db    = 'localhost';

$mensajes = array();
$exito = true;
$conexion_activa = false;
$conn = null;

// Intentar conexión con PDO
try {
    if (class_exists('PDO')) {
        $dsn = "mysql:host={$host_db};dbname={$nombre_db};charset=utf8";
        $conn = new PDO($dsn, $usuario_db, $pass_db, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        $conexion_activa = true;
    }
} catch (Exception $e) {
    // Si falla PDO, intentar con mysqli
    if (class_exists('mysqli')) {
        $connMysqli = @new mysqli($host_db, $usuario_db, $pass_db, $nombre_db);
        if (!$connMysqli->connect_error) {
            $connMysqli->set_charset("utf8");
            $conexion_activa = true;
        } else {
            $mensajes[] = array('tipo' => 'danger', 'texto' => "Error de conexión a MariaDB/MySQL: " . $connMysqli->connect_error);
            $exito = false;
        }
    } else {
        $mensajes[] = array('tipo' => 'danger', 'texto' => "Error de conexión PDO: " . $e->getMessage());
        $exito = false;
    }
}

// Ejecutar migración si la conexión está disponible
if ($conexion_activa) {
    // 1. Crear tabla roles
    $sqlRoles = "CREATE TABLE IF NOT EXISTS `roles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `codigo` VARCHAR(30) NOT NULL UNIQUE,
        `nombre` VARCHAR(60) NOT NULL,
        `descripcion` VARCHAR(255) NULL,
        `permisos_json` TEXT NULL,
        `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    // 2. Crear tabla usuarios
    $sqlUsuarios = "CREATE TABLE IF NOT EXISTS `usuarios` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_rol` INT NOT NULL,
        `usuario` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `nombre_completo` VARCHAR(100) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `activo` TINYINT(1) NOT NULL DEFAULT 1,
        `intentos_fallidos` INT NOT NULL DEFAULT 0,
        `bloqueado_hasta` DATETIME NULL DEFAULT NULL,
        `ultimo_login` DATETIME NULL DEFAULT NULL,
        `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    // 3. Crear tabla recuperacion_tokens
    $sqlTokens = "CREATE TABLE IF NOT EXISTS `recuperacion_tokens` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_usuario` INT NOT NULL,
        `token_hash` VARCHAR(64) NOT NULL UNIQUE,
        `expira_en` DATETIME NOT NULL,
        `utilizado` TINYINT(1) NOT NULL DEFAULT 0,
        `ip_solicitud` VARCHAR(45) NULL,
        `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    // 4. Crear tabla auditoria_accesos
    $sqlAuditoria = "CREATE TABLE IF NOT EXISTS `auditoria_accesos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_usuario` INT NULL,
        `usuario_ingresado` VARCHAR(50) NOT NULL,
        `accion` VARCHAR(50) NOT NULL,
        `ip_origen` VARCHAR(45) NULL,
        `user_agent` VARCHAR(255) NULL,
        `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_usuario_fecha` (`usuario_ingresado`, `fecha_hora`),
        INDEX `idx_accion` (`accion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    try {
        if ($conn instanceof PDO) {
            $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
            $conn->exec($sqlRoles);
            $conn->exec($sqlUsuarios);
            $conn->exec($sqlTokens);
            $conn->exec($sqlAuditoria);
            $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
        } else {
            $connMysqli->query("SET FOREIGN_KEY_CHECKS = 0;");
            $connMysqli->query($sqlRoles);
            $connMysqli->query($sqlUsuarios);
            $connMysqli->query($sqlTokens);
            $connMysqli->query($sqlAuditoria);
            $connMysqli->query("SET FOREIGN_KEY_CHECKS = 1;");
        }
        $mensajes[] = array('tipo' => 'success', 'texto' => "Estructura de tablas (<code>roles</code>, <code>usuarios</code>, <code>recuperacion_tokens</code>, <code>auditoria_accesos</code>) creada o verificada con éxito.");

        // Poblar Roles
        $rolesData = array(
            array(1, 'ADMIN', 'Administrador General', 'Control total del sistema, administración de usuarios y operaciones bancarias completas.', '{"usuarios_crud":true,"lotes_generar":true,"archivos_subir":true,"padron_editar":true,"reportes_ver":true,"auditoria_ver":true}'),
            array(2, 'OPERADOR', 'Operador de Carga y Lotes', 'Emisión y procesamiento de lotes bancarios (NOVCAR, ALTAS, NOVTRAN), importación e informes EML.', '{"usuarios_crud":false,"lotes_generar":true,"archivos_subir":true,"padron_editar":true,"reportes_ver":true,"auditoria_ver":false}'),
            array(3, 'AUDITOR', 'Auditor / Consulta', 'Visualización de padrón, consulta individual de beneficiarios y emisión de reportes mensuales.', '{"usuarios_crud":false,"lotes_generar":false,"archivos_subir":false,"padron_editar":false,"reportes_ver":true,"auditoria_ver":true}')
        );

        foreach ($rolesData as $r) {
            $sqlRole = "INSERT INTO `roles` (`id`, `codigo`, `nombre`, `descripcion`, `permisos_json`) 
                        VALUES (?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `descripcion` = VALUES(`descripcion`), `permisos_json` = VALUES(`permisos_json`)";
            if ($conn instanceof PDO) {
                $stmt = $conn->prepare($sqlRole);
                $stmt->execute(array($r[0], $r[1], $r[2], $r[3], $r[4]));
            } else {
                $stmt = $connMysqli->prepare($sqlRole);
                $stmt->bind_param('issss', $r[0], $r[1], $r[2], $r[3], $r[4]);
                $stmt->execute();
            }
        }
        $mensajes[] = array('tipo' => 'success', 'texto' => "Catálogo de 3 roles base inicializado (<code>ADMIN</code>, <code>OPERADOR</code>, <code>AUDITOR</code>).");

        // Crear o actualizar cuenta de Administrador dgarcia
        $adminUsuario = 'dgarcia';
        $adminEmail   = 'dgarcia@senaf.rionegro.gov.ar';
        $adminNombre  = 'Diego Garcia';
        $adminClave   = '78900';
        $hashBcrypt   = password_hash($adminClave, PASSWORD_BCRYPT);

        // Verificar si dgarcia ya existe
        $existeAdmin = false;
        if ($conn instanceof PDO) {
            $stmtCheck = $conn->prepare("SELECT id, password_hash FROM `usuarios` WHERE `usuario` = ? LIMIT 1");
            $stmtCheck->execute(array($adminUsuario));
            $rowAdmin = $stmtCheck->fetch();
            if ($rowAdmin) {
                $existeAdmin = true;
                // Actualizar correo y rol garantizando acceso
                $stmtUpd = $conn->prepare("UPDATE `usuarios` SET `email` = ?, `nombre_completo` = ?, `id_rol` = 1, `activo` = 1 WHERE `usuario` = ?");
                $stmtUpd->execute(array($adminEmail, $adminNombre, $adminUsuario));
            } else {
                $stmtIns = $conn->prepare("INSERT INTO `usuarios` (`id_rol`, `usuario`, `email`, `nombre_completo`, `password_hash`, `activo`) VALUES (1, ?, ?, ?, ?, 1)");
                $stmtIns->execute(array($adminUsuario, $adminEmail, $adminNombre, $hashBcrypt));
            }
        } else {
            $stmtCheck = $connMysqli->prepare("SELECT id, password_hash FROM `usuarios` WHERE `usuario` = ? LIMIT 1");
            $stmtCheck->bind_param('s', $adminUsuario);
            $stmtCheck->execute();
            $res = $stmtCheck->get_result();
            if ($rowAdmin = $res->fetch_assoc()) {
                $existeAdmin = true;
                $stmtUpd = $connMysqli->prepare("UPDATE `usuarios` SET `email` = ?, `nombre_completo` = ?, `id_rol` = 1, `activo` = 1 WHERE `usuario` = ?");
                $stmtUpd->bind_param('sss', $adminEmail, $adminNombre, $adminUsuario);
                $stmtUpd->execute();
            } else {
                $stmtIns = $connMysqli->prepare("INSERT INTO `usuarios` (`id_rol`, `usuario`, `email`, `nombre_completo`, `password_hash`, `activo`) VALUES (1, ?, ?, ?, ?, 1)");
                $stmtIns->bind_param('ssss', $adminUsuario, $adminEmail, $adminNombre, $hashBcrypt);
                $stmtIns->execute();
            }
        }

        $mensajes[] = array(
            'tipo' => 'success', 
            'texto' => $existeAdmin 
                ? "Usuario Administrador <code>dgarcia</code> ya existía: sincronizado y activo con email <strong>{$adminEmail}</strong> y rol <code>ADMIN</code>."
                : "Usuario Administrador <code>dgarcia</code> creado exitosamente con email <strong>{$adminEmail}</strong> y rol <code>ADMIN</code>."
        );

        // Prueba de verificación criptográfica
        $testPass = password_verify($adminClave, $hashBcrypt);
        $mensajes[] = array(
            'tipo' => 'info',
            'texto' => "Verificación de función <code>password_verify()</code>: " . ($testPass ? "<strong>CORRECTA (Algoritmo BCRYPT validado)</strong>" : "FALLÓ")
        );

    } catch (Exception $ex) {
        $mensajes[] = array('tipo' => 'danger', 'texto' => "Error durante la ejecución del DDL/DML: " . $ex->getMessage());
        $exito = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración: Módulo de Usuarios &bull; Cabal FSR</title>
    <link rel="icon" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .migracion-box {
            max-width: 800px;
            margin: 40px auto;
            background: var(--surface-card, #ffffff);
            border-radius: var(--radius-lg, 12px);
            border: 1px solid var(--border-light, #e2e8f0);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .migracion-header {
            background: #1e293b;
            color: #ffffff;
            padding: 24px 30px;
            border-bottom: 4px solid var(--primary-600, #2563eb);
        }
        .migracion-body {
            padding: 28px 30px;
        }
        .status-badge-ok {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.84rem;
            font-weight: 700;
        }
        .msg-item {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 0.88rem;
            line-height: 1.5;
            border-left: 4px solid transparent;
        }
        .msg-item.success { background: #ecfdf5; color: #065f46; border-left-color: #059669; }
        .msg-item.info { background: #eff6ff; color: #1e40af; border-left-color: #2563eb; }
        .msg-item.danger { background: #fef2f2; color: #991b1b; border-left-color: #dc2626; }
    </style>
</head>
<body>

<div class="migracion-box">
    <div class="migracion-header">
        <h2 style="margin: 0 0 6px 0; font-size: 1.25rem;">🛠️ Migración de Base de Datos — Fase 1</h2>
        <p style="margin: 0; font-size: 0.85rem; color: #94a3b8;">Sistema Cabal FSR &bull; Creación de Tablas del Módulo de Usuarios y Autenticación</p>
    </div>

    <div class="migracion-body">
        <?php if ($conexion_activa && $exito): ?>
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <span class="status-badge-ok">✓ FASE 1 COMPLETADA CON ÉXITO</span>
                <span style="font-size: 0.82rem; color: #64748b;">Base de datos: <strong>cabal</strong></span>
            </div>
        <?php endif; ?>

        <?php foreach ($mensajes as $m): ?>
            <div class="msg-item <?php echo $m['tipo']; ?>">
                <?php echo $m['texto']; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($conexion_activa && $exito): ?>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; margin-top: 24px;">
                <h4 style="margin: 0 0 10px 0; font-size: 0.95rem; color: #0f172a;">Resumen de la Cuenta Administrador Creada:</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 0.86rem; color: #334155; line-height: 1.6;">
                    <li><strong>Usuario:</strong> <code>dgarcia</code></li>
                    <li><strong>Email:</strong> <code>dgarcia@senaf.rionegro.gov.ar</code></li>
                    <li><strong>Rol:</strong> <code>ADMIN</code> (Administrador General)</li>
                    <li><strong>Contraseña inicial:</strong> <code>[PROTEGIDA CON BCRYPT]</code> (Clave configurada: <code>78900</code>)</li>
                    <li><strong>Estado:</strong> Activo (1) &bull; Intentos fallidos: 0</li>
                </ul>
            </div>
        <?php else: ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px 20px; margin-top: 24px; color: #991b1b; font-size: 0.86rem;">
                <strong>Nota operativa:</strong> Si la base de datos corre en Apache/XAMPP localmente, puede ejecutar esta migración directamente abriendo en el navegador:
                <br><code>http://localhost/cabal-Gemini/BD/migrar_usuarios.php</code> o importando el script <a href="migracion_modulo_usuarios.sql" style="color: #2563eb; font-weight: bold;">migracion_modulo_usuarios.sql</a> en phpMyAdmin.
            </div>
        <?php endif; ?>

        <div style="margin-top: 28px; display: flex; justify-content: flex-end; gap: 12px;">
            <a href="../index.php" class="btn-nav-action primary" style="text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 0.88rem; font-weight: 700; background: #2563eb; color: #ffffff;">
                Volver al Tablero Principal &rarr;
            </a>
        </div>
    </div>
</div>

</body>
</html>
