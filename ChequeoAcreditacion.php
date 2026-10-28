<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Chequeo y Auditoría de Planilla de Acreditaciones vs Padrón de Beneficiarios
 * Entidad 782 - Cabal
 */

require_once('phpexcel/PHPExcel.php');
require_once('BD/BD.php');
require_once('BD/Funciones.php');

$archivo = "archivos/Acreditaciones.xlsx";
$archivo_existe = file_exists($archivo);

$filas = array();
$total_filas = 0;
$total_correctos = 0;
$total_errores = 0;
$error_mensaje = "";

if ($archivo_existe) {
    try {
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = (int)$sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $apellidoCarga = trim((string)$sheet->getCell("B" . $row)->getValue());
            $nombreCarga   = trim((string)$sheet->getCell("C" . $row)->getValue());
            $dniCarga      = trim((string)$sheet->getCell("E" . $row)->getValue());

            if ($apellidoCarga === '' && $nombreCarga === '' && $dniCarga === '') {
                continue;
            }

            $total_filas++;
            $datosBD = DevuelvoApellidoNombrePorDNI($dniCarga);
            $estado = "VERIFICAR";
            $observacion = "DNI no encontrado en el padrón";

            if (!empty($datosBD) && isset($datosBD[1])) {
                $apellidoBD = trim((string)$datosBD[1]);
                $nombreBD   = isset($datosBD[2]) ? trim((string)$datosBD[2]) : '';

                if (strcasecmp($apellidoCarga, $apellidoBD) === 0) {
                    $estado = "CORRECTO";
                    $observacion = "Coincide con padrón";
                    $total_correctos++;
                } else {
                    $estado = "VERIFICAR";
                    $observacion = "Nombre en BD: " . $apellidoBD . " " . $nombreBD;
                    $total_errores++;
                }
            } else {
                $total_errores++;
            }

            $filas[] = array(
                'num'         => $total_filas,
                'apellido'    => $apellidoCarga,
                'nombre'      => $nombreCarga,
                'dni'         => $dniCarga,
                'estado'      => $estado,
                'observacion' => $observacion
            );
        }
    } catch (Exception $e) {
        $error_mensaje = "Error al leer el archivo Excel: " . $e->getMessage();
    }
} else {
    $error_mensaje = "No se encontró el archivo 'archivos/Acreditaciones.xlsx' en el servidor. Por favor cárguelo desde la sección de subida de archivos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .badge-audit {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-audit.correcto {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-audit.verificar {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        [data-theme="dark"] .badge-audit.correcto {
            color: #34d399;
        }
        [data-theme="dark"] .badge-audit.verificar {
            color: #f87171;
        }
    </style>
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <a href="index.php" class="brand-group" id="brand-link">
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
            <a href="index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>
            <a href="subir_excel_acreditaciones.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Cargar Otra Planilla
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1200px; padding-top: 24px;">

    <?php if (!empty($error_mensaje)): ?>
    <div class="alert-modern danger" style="margin-bottom: 24px;">
        <strong>Atención:</strong> <?php echo htmlspecialchars($error_mensaje, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <!-- TARJETAS DE RESUMEN -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Total Registros Analizados
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                <?php echo number_format($total_filas, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Coincidentes con Padrón
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                <?php echo number_format($total_correctos, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Discrepancias / No Encontrados
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626;">
                <?php echo number_format($total_errores, 0, ',', '.'); ?>
            </div>
        </div>
    </div>

    <!-- TABLA DE AUDITORÍA -->
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                    Auditoría de Acreditaciones vs Padrón Local
                </h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Archivo fuente: <code><?php echo htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8'); ?></code>
                </p>
            </div>
            <div>
                <a href="AcreditarSaldos2026.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; border-radius: var(--radius-sm);">
                    Proceder a Acreditar
                </a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Apellido en Excel</th>
                        <th>Nombre en Excel</th>
                        <th>DNI en Excel</th>
                        <th>Estado de Validación</th>
                        <th>Observación Padrón</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total_filas > 0): ?>
                    <?php foreach ($filas as $f): ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $f['num']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($f['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($f['dni'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td>
                                <span class="badge-audit <?php echo strtolower($f['estado']); ?>">
                                    <?php echo $f['estado']; ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($f['observacion'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 32px; color: var(--text-muted);">
                            No hay registros disponibles para auditar.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
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