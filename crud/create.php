<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Alta Manual de Beneficiario
 */

$mensaje_error = '';

if (isset($_POST['submit'])) {
    include_once 'conexion.php';

    $apellido = trim($_POST['apellido']);
    $nombre = trim($_POST['nombre']);
    $dni = trim($_POST['dni']);
    $cuit = trim($_POST['cuit']);
    $cuenta = trim($_POST['cuenta']);

    if (!empty($apellido) && !empty($nombre)) {
        $stmt = $conexion->prepare("INSERT INTO beneficiario (Apellido, Nombre, NumeroDocumento, Cuit, NumeroCuenta, EntidadEmisora) VALUES (?, ?, ?, ?, ?, '782')");
        if ($stmt) {
            $stmt->bind_param("sssss", $apellido, $nombre, $dni, $cuit, $cuenta);
            $stmt->execute();
            $stmt->close();
            $conexion->close();
            header('Location: index.php');
            exit;
        } else {
            $mensaje_error = "Error al preparar la consulta de inserción.";
        }
    } else {
        $mensaje_error = "El Apellido y Nombre son campos requeridos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Beneficiario — Cabal FSR</title>
    <link rel="icon" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="../js/theme.js"></script>
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <a href="../index.php" class="brand-group" id="brand-link">
            <div class="brand-logo-wrap">
                <img src="../img/cabal.png" alt="Logo Cabal" onerror="this.src='../img/favicon.png'">
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
            <a href="index.php" class="btn-nav-action primary" id="btn-volver-crud">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Padrón
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 640px; padding-top: 32px;">
    <?php if (!empty($mensaje_error)): ?>
        <div class="alert-modern danger" style="margin-bottom: 20px;">
            <?php echo htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="modern-card">
        <div class="card-header-custom" style="padding: 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                ➕ Nuevo Beneficiario
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                Complete los datos filiatorios para dar de alta al titular en el sistema
            </p>
        </div>

        <div class="card-body-custom" style="padding: 28px;">
            <form method="post">
                <div style="margin-bottom: 18px;">
                    <label for="apellido" style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; color: var(--text-main);">
                        Apellido: <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" id="apellido" name="apellido" required
                           style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.95rem; background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 18px;">
                    <label for="nombre" style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; color: var(--text-main);">
                        Nombre: <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" id="nombre" name="nombre" required
                           style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.95rem; background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                    <div>
                        <label for="dni" style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; color: var(--text-main);">
                            Número Documento:
                        </label>
                        <input type="text" id="dni" name="dni" placeholder="Ej: 28123456"
                               style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.95rem; background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                    </div>
                    <div>
                        <label for="cuit" style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; color: var(--text-main);">
                            CUIT:
                        </label>
                        <input type="text" id="cuit" name="cuit" placeholder="Ej: 20281234568"
                               style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.95rem; background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label for="cuenta" style="display: block; font-weight: 600; font-size: 0.88rem; margin-bottom: 6px; color: var(--text-main);">
                        Número de Cuenta (15 dígitos):
                    </label>
                    <input type="text" id="cuenta" name="cuenta" placeholder="Ej: 782001000012345"
                           style="width: 100%; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.95rem; background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="index.php" class="btn-modern-secondary" style="padding: 10px 20px; border-radius: var(--radius-sm); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                        Cancelar
                    </a>
                    <button type="submit" name="submit" class="btn-modern-primary" style="padding: 10px 24px; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer;">
                        Guardar Beneficiario
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<a href="javascript:history.back()" class="btn-modern-flotante" title="Volver atrás">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver
</a>

</body>
</html>
