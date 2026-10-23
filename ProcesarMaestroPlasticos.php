<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Maestro de Plásticos Cabal (MAESPLAS.txt)
 * Entidad 782 - Cabal
 */

require_once("BD/Funciones.php");
require_once("BD/BD.php");

function convertirFechaMaster($fecha)
{
    if (strlen(trim($fecha)) !== 6) {
        return "Formato incorrecto";
    }
    $dia = substr($fecha, 0, 2);
    $mes = substr($fecha, 2, 2);
    $anio = substr($fecha, 4, 2);
    return "$dia/$mes/$anio";
}

function DevuelveCausa($id)
{
    $id = trim($id);
    switch ($id) {
        case '00':
        case '0':
            return "HABILITADA";
        case '01':
        case '1':
            return "DENUNCIA POR EXTRAVIO";
        case '02':
        case '2':
            return "ANULACION POR ENTIDAD";
        case '03':
        case '3':
            return "ANULACION";
        case '04':
        case '4':
            return "DENUNCIA POR HURTO";
        case '07':
        case '7':
            return "REEMPLAZO";
        case '08':
        case '8':
            return "NO HABILITADA";
        case '14':
            return "BLOQUEO TRANSITORIO";
        default:
            return "SIN ESPECIFICAR (" . $id . ")";
    }
}

$nombreArchivoParam = isset($_GET['archivo']) ? trim($_GET['archivo']) : 'archivos/MAESPLAS.txt';
// Sanitizar nombre de archivo contra path traversal
$nombreArchivoParam = str_replace(array('../', '..\\'), '', $nombreArchivoParam);

$rutaArchivo = $nombreArchivoParam;
if (!file_exists($rutaArchivo)) {
    if (file_exists("archivos/" . basename($nombreArchivoParam))) {
        $rutaArchivo = "archivos/" . basename($nombreArchivoParam);
    }
}

$archivoExiste = file_exists($rutaArchivo);
$Movimiento = array();
$Movimiento[0][1] = "Nro";
$Movimiento[0][2] = "Numero Cuenta";
$Movimiento[0][3] = "Numero Tarjeta";
$Movimiento[0][4] = "Nombre y Apellido";
$Movimiento[0][5] = "Tipo Documento";
$Movimiento[0][6] = "Nro Documento";
$Movimiento[0][7] = "Causa";
$Movimiento[0][8] = "Fecha Alta";
$Movimiento[0][9] = "Fecha Emision";
$Movimiento[0][10] = "Fecha Vencimiento";
$Movimiento[0][11] = "Nombre Archivo";

$estadoGrabacion = '';
$claseEstado = 'info';
$totalRegistros = 0;
$habilitadas = 0;
$bloqueadas = 0;

if ($archivoExiste) {
    $txt_file = fopen($rutaArchivo, 'r');
    if ($txt_file) {
        $a = 1;
        while (($line = fgets($txt_file)) !== false) {
            $line = trim($line, "\r\n");
            if (strlen($line) < 30) {
                continue;
            }
            $causa = DevuelveCausa(substr($line, 70, 2));
            if (strpos($causa, 'HABILITADA') !== false && strpos($causa, 'NO') === false) {
                $habilitadas++;
            } else {
                $bloqueadas++;
            }

            $Movimiento[$a][1] = $a;
            $Movimiento[$a][2] = trim(substr($line, 0, 15));
            $Movimiento[$a][3] = trim(substr($line, 16, 16));
            $Movimiento[$a][4] = trim(substr($line, 32, 25));
            $Movimiento[$a][5] = trim(substr($line, 57, 2));
            $Movimiento[$a][6] = trim(substr($line, 59, 11));
            $Movimiento[$a][7] = $causa;
            $Movimiento[$a][8] = convertirFechaMaster(substr($line, 72, 6));
            $Movimiento[$a][9] = convertirFechaMaster(substr($line, 78, 6));
            $Movimiento[$a][10] = convertirFechaMaster(substr($line, 84, 6));
            $Movimiento[$a][11] = basename($rutaArchivo);
            $a++;
        }
        fclose($txt_file);
        $totalRegistros = count($Movimiento) - 1;

        if ($totalRegistros > 0) {
            $nombreBase = basename($rutaArchivo);
            if (!EstaArchivoMasterTarjeta($nombreBase)) {
                GrabarMasterTarjetasBaseDatos($Movimiento);
                $estadoGrabacion = "Los datos se grabaron exitosamente en la base de datos (Tabla MasterTarjetas).";
                $claseEstado = "success";
            } else {
                $estadoGrabacion = "El archivo '" . htmlspecialchars($nombreBase, ENT_QUOTES, 'UTF-8') . "' ya fue importado previamente en la base de datos.";
                $claseEstado = "warning";
            }
        } else {
            $estadoGrabacion = "El archivo se leyó pero no contenía registros procesables.";
            $claseEstado = "warning";
        }
    } else {
        $estadoGrabacion = "Error: No se pudo abrir el archivo para lectura.";
        $claseEstado = "danger";
    }
} else {
    $estadoGrabacion = "Error: El archivo '" . htmlspecialchars($rutaArchivo, ENT_QUOTES, 'UTF-8') . "' no existe en el servidor.";
    $claseEstado = "danger";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Maestro de Tarjetas — Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-status.habilitada {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-status.alerta {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .badge-status.neutro {
            background: rgba(100, 116, 139, 0.15);
            color: #475569;
            border: 1px solid rgba(100, 116, 139, 0.3);
        }
        [data-theme="dark"] .badge-status.habilitada {
            color: #34d399;
        }
        [data-theme="dark"] .badge-status.alerta {
            color: #f87171;
        }
        [data-theme="dark"] .badge-status.neutro {
            color: #94a3b8;
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
            <a href="reporte_tarjetas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Catálogo de Tarjetas
            </a>
            <a href="ProcesarMaestroPlasticosArchivo.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Cambiar Archivo
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1300px; padding-top: 24px;">

    <!-- BANNER DE RESULTADO -->
    <div class="alert-modern <?php echo $claseEstado; ?>" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.05rem; margin-bottom: 4px;">
            <?php 
                if ($claseEstado === 'success') echo '✅ Importación Exitosa';
                elseif ($claseEstado === 'warning') echo '⚠️ Archivo Ya Registrado o Advertencia';
                else echo '❌ Error en Archivo';
            ?>
        </div>
        <div><?php echo htmlspecialchars($estadoGrabacion, ENT_QUOTES, 'UTF-8'); ?></div>
        <div style="margin-top: 6px; font-size: 0.82rem; opacity: 0.85;">
            Archivo: <code><?php echo htmlspecialchars($rutaArchivo, ENT_QUOTES, 'UTF-8'); ?></code>
        </div>
    </div>

    <!-- TARJETAS DE MÉTRICAS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Total Plásticos Leídos
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                <?php echo number_format($totalRegistros, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Plásticos Habilitados
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                <?php echo number_format($habilitadas, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Bloqueos / Bajas / Otros
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626;">
                <?php echo number_format($bloqueadas, 0, ',', '.'); ?>
            </div>
        </div>
    </div>

    <!-- TABLA DE DETALLE -->
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                    Registro de Plásticos del Archivo
                </h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Visualización de tarjetas y estados devueltos por Cabal
                </p>
            </div>
            <div>
                <a href="reporte_tarjetas.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; border-radius: var(--radius-sm);">
                    Ver en Catálogo Completo
                </a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>N° Cuenta</th>
                        <th>N° Tarjeta</th>
                        <th>Beneficiario</th>
                        <th>Tipo / Doc</th>
                        <th>Estado / Causa</th>
                        <th>Alta</th>
                        <th>Emisión</th>
                        <th>Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($totalRegistros > 0): ?>
                    <?php for ($x = 1; $x <= $totalRegistros; $x++): ?>
                        <?php 
                            $causaTexto = $Movimiento[$x][7];
                            $claseBadge = 'neutro';
                            if (strpos($causaTexto, 'HABILITADA') !== false && strpos($causaTexto, 'NO') === false) {
                                $claseBadge = 'habilitada';
                            } elseif (strpos($causaTexto, 'DENUNCIA') !== false || strpos($causaTexto, 'ANULACION') !== false || strpos($causaTexto, 'HURTO') !== false) {
                                $claseBadge = 'alerta';
                            }
                            $tarjetaEnmascarada = formatearTarjetaCredito($Movimiento[$x][3]);
                        ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $Movimiento[$x][1]; ?></td>
                            <td><code><?php echo htmlspecialchars($Movimiento[$x][2], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><code><?php echo htmlspecialchars($tarjetaEnmascarada, ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($Movimiento[$x][4], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($Movimiento[$x][5] . ' ' . $Movimiento[$x][6], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="badge-status <?php echo $claseBadge; ?>">
                                    <?php echo htmlspecialchars($causaTexto, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($Movimiento[$x][8], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($Movimiento[$x][9], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($Movimiento[$x][10], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endfor; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 32px; color: var(--text-muted);">
                            No hay datos para mostrar en este lote.
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