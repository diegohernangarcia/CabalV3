<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Exportación de Padrón de Beneficiarios a Formato Excel con Vista Previa Filtrable
 * Entidad 782 - Cabal
 */

$usuario = 'pucho';
$pass = '78900';
$fecha_actual = date("d-m-Y");
$hora_actual = date("H:i");

$archivo_generado = "";
$url_archivo = "";
$total_exportados = 0;
$error_mensaje = "";
$beneficiarios_lista = array();

// Conexión a la base de datos
$conexion = new mysqli("localhost", $usuario, $pass, "cabal");
$conexion->set_charset("utf8");

if ($conexion->connect_error) {
    $error_mensaje = "Error en la conexión a la base de datos: " . $conexion->connect_error;
} else {
    $consulta = "SELECT * FROM beneficiario ORDER BY Apellido ASC, Nombre ASC";
    $resultado = $conexion->query($consulta);

    if ($resultado && $resultado->num_rows > 0) {
        $total_exportados = $resultado->num_rows;

        // Cargar librerías necesarias
        require_once "./phpexcel/PHPExcel.php";
        require_once "BD/Funciones.php";

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getActiveSheet()->setTitle('Beneficiarios');

        // Encabezados de columna en Excel
        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Id');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Apellido');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Nombre');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Localidad');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Provincia');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', 'NumeroCuenta');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', 'NumeroTarjeta');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', 'CodigoPostal');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', 'TipoDocumento');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', 'NumeroDocumento');
        $objPHPExcel->getActiveSheet()->setCellValue('K1', 'Cuit');

        $fila = 2;
        while ($row = $resultado->fetch_assoc()) {
            $tarjeta_enmascarada = formatearTarjetaCredito($row['NumeroTarjeta']);

            $objPHPExcel->getActiveSheet()->setCellValue('A' . $fila, $row['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $fila, $row['Apellido']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $fila, $row['Nombre']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $fila, $row['Localidad']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $fila, $row['Provincia']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $fila, $row['NumeroCuenta']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $fila, $tarjeta_enmascarada);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $fila, $row['CodigoPostal']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $fila, $row['TipoDocumento']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $fila, $row['NumeroDocumento']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $fila, $row['Cuit']);

            // Almacenar todos los registros para la vista previa interactiva filtrable
            $beneficiarios_lista[] = array(
                'id'        => $row['id'],
                'apellido'  => trim($row['Apellido']),
                'nombre'    => trim($row['Nombre']),
                'localidad' => trim($row['Localidad']),
                'provincia' => trim($row['Provincia']),
                'cuenta'    => trim($row['NumeroCuenta']),
                'tarjeta'   => $tarjeta_enmascarada,
                'tipodoc'   => trim($row['TipoDocumento']),
                'dni'       => trim($row['NumeroDocumento']),
                'cuit'      => trim($row['Cuit'])
            );

            $fila++;
        }

        // Estilos de la planilla Excel
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(22);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(24);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(18);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(18);

        // Guardar archivo Excel en disco
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $archivo_generado = "Beneficiarios FSR $fecha_actual.xlsx";
        $objWriter->save($archivo_generado);
        $url_archivo = $archivo_generado;

        $resultado->free_result();
    } else {
        $error_mensaje = "No se encontraron registros de beneficiarios en la base de datos.";
    }

    $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Padrón de Beneficiarios a Excel — Sistema Cabal</title>
    <meta name="description" content="Exportación de padrón consolidado de beneficiarios a formato Excel con vista previa y filtros en tiempo real">
    <link rel="icon" href="img/favicon.png">
    <!-- Estilos del Tablero de Operaciones -->
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Gestor de Modo Oscuro / Claro -->
    <script src="js/theme.js"></script>
    <style>
        .export-card-success {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            padding: 32px 36px;
            margin-bottom: 28px;
        }

        .export-status-banner {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 22px;
            background: var(--emerald-50);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
        }

        .export-status-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--emerald-600);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .export-status-texts h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 2px;
        }

        .export-status-texts p {
            color: #047857;
            font-size: 0.85rem;
        }

        .export-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .export-meta-item {
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 14px 18px;
        }

        .meta-item-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .meta-item-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--navy-800);
            word-break: break-all;
        }

        .btn-download-excel {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--emerald-600), var(--emerald-700));
            color: #ffffff;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
            transition: all var(--transition-fast);
        }

        .btn-download-excel:hover {
            background: linear-gradient(135deg, #047857, #065f46);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.45);
        }

        /* VISTA PREVIA Y FILTROS */
        .preview-section {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            padding: 28px 32px;
            margin-bottom: 32px;
        }

        .preview-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }

        .preview-header-bar h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--navy-800);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .preview-count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: var(--primary-50);
            color: var(--primary-700);
            border: 1px solid var(--primary-200);
            border-radius: var(--radius-full);
            font-size: 0.82rem;
            font-weight: 700;
        }

        /* TOOLBAR DE FILTROS */
        .filters-toolbar {
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 20px;
        }

        .filters-toolbar-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--navy-700);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto;
            gap: 14px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .filter-input-wrap {
            position: relative;
        }

        .filter-input-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
        }

        .filter-input-wrap input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            background: var(--input-bg);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            color: var(--text-main);
            outline: none;
            transition: all var(--transition-fast);
        }

        .filter-input-wrap input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .filter-input-wrap input::placeholder {
            color: var(--text-light);
            font-size: 0.82rem;
        }

        .btn-clear-filters {
            padding: 10px 18px;
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            color: var(--text-muted);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 40px;
        }

        .btn-clear-filters:hover {
            background: var(--surface-subtle);
            color: #ef4444;
            border-color: #fca5a5;
        }

        /* TABLA CON SCROLL Y ENCABEZADOS STICKY */
        .table-responsive-container {
            max-height: 560px;
            overflow-y: auto;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: var(--shadow-sm);
        }

        .table-beneficiarios {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
            background: transparent;
        }

        .table-beneficiarios thead th {
            position: sticky;
            top: 0;
            background: var(--surface-subtle);
            color: var(--navy-800);
            font-weight: 700;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border-light);
            z-index: 5;
            white-space: nowrap;
        }

        .table-beneficiarios tbody td {
            padding: 11px 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-main);
            vertical-align: middle;
        }

        .table-beneficiarios tbody tr:hover td {
            background: var(--primary-50);
        }

        .benef-id-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            background: var(--surface-subtle);
            color: var(--text-muted);
            border: 1px solid var(--border-light);
        }

        .benef-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            background: var(--surface-subtle);
            color: var(--text-main);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid var(--border-light);
        }

        .table-beneficiarios code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            background: var(--surface-subtle);
            color: var(--primary-700);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid var(--border-light);
        }

        .no-records-cell {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
    </style>
</head>
<body>

<!-- =======================================================================
     BARRA DE NAVEGACIÓN INSTITUCIONAL
     ======================================================================= -->
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

            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer" class="btn-nav-action" id="btn-portal-cabal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                Portal Cabal
            </a>

            <a href="ayuda.html" target="_blank" class="btn-nav-action" id="btn-ayuda">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Guía Operativa
            </a>
        </div>
    </div>
</header>

<!-- =======================================================================
     CONTENEDOR PRINCIPAL
     ======================================================================= -->
<main class="dashboard-container">

    <!-- HERO BANNER -->
    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-500);">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Padrón de Beneficiarios y Exportación
            </h2>
            <p>
                Visualización, búsqueda interactiva y descarga en formato Excel de los titulares de tarjeta del programa <b>Familia Rionegrina Solidaria</b>.
            </p>
        </div>
        <div>
            <a href="index.php" class="btn-nav-action primary" style="padding: 10px 20px; font-size: 0.88rem;">
                Volver al Inicio
            </a>
        </div>
    </section>

    <?php if (!empty($archivo_generado)): ?>
        <!-- CARD DE ESTADO Y DESCARGA -->
        <section class="export-card-success">
            <div class="export-status-banner">
                <div class="export-status-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="export-status-texts">
                    <h3>¡Padrón Exportado Exitosamente!</h3>
                    <p>La planilla Excel se ha generado correctamente con las columnas de padrón y tarjetas enmascaradas.</p>
                </div>
            </div>

            <div class="export-meta-grid">
                <div class="export-meta-item">
                    <div class="meta-item-label">Archivo Generado</div>
                    <div class="meta-item-value" style="font-size: 0.98rem; color: var(--primary-700);"><?php echo htmlspecialchars($archivo_generado); ?></div>
                </div>

                <div class="export-meta-item">
                    <div class="meta-item-label">Total en Padrón</div>
                    <div class="meta-item-value"><?php echo number_format($total_exportados, 0, ',', '.'); ?> titulares</div>
                </div>

                <div class="export-meta-item">
                    <div class="meta-item-label">Fecha y Hora</div>
                    <div class="meta-item-value"><?php echo $fecha_actual . " — " . $hora_actual; ?> hs</div>
                </div>

                <div class="export-meta-item">
                    <div class="meta-item-label">Seguridad de Tarjetas</div>
                    <div class="meta-item-value" style="font-size: 0.85rem; color: #059669;">Máscara PCI (4 dígitos visibles)</div>
                </div>
            </div>

            <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
                <a href="<?php echo htmlspecialchars($url_archivo); ?>" download class="btn-download-excel" id="btn-download-file">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Descargar Archivo Excel (<?php echo htmlspecialchars($archivo_generado); ?>)
                </a>

                <a href="ConsultarBeneficiario.php" class="btn-nav-action" style="padding: 12px 18px; font-size: 0.9rem; color: var(--navy-800); background: var(--surface-subtle); border-color: var(--border-light);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Ficha Individual de Beneficiario
                </a>
            </div>
        </section>

        <!-- VISTA PREVIA COMPLETA CON FILTROS DINÁMICOS -->
        <section class="preview-section">
            <div class="preview-header-bar">
                <div>
                    <h4>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-600);">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Vista Previa del Padrón de Beneficiarios
                    </h4>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Podés filtrar en tiempo real por DNI, CUIT, Apellido o Nombre de forma combinada.
                    </p>
                </div>

                <div class="preview-count-badge" id="badge-contador">
                    Mostrando <strong id="cant-filtrados" style="margin: 0 3px;"><?php echo count($beneficiarios_lista); ?></strong> de <?php echo count($beneficiarios_lista); ?> beneficiarios
                </div>
            </div>

            <!-- TOOLBAR DE FILTROS EN TIEMPO REAL -->
            <div class="filters-toolbar">
                <div class="filters-toolbar-title">
                    <span>Filtros de Búsqueda Instantánea</span>
                    <span style="font-size: 0.72rem; color: var(--text-light); text-transform: none; font-weight: 400;">
                        Filtrado en tiempo real sin recargar la página
                    </span>
                </div>
                <div class="filters-grid">
                    <!-- FILTRO DNI -->
                    <div class="filter-group">
                        <label for="filtro-dni">DNI / Documento:</label>
                        <div class="filter-input-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="filtro-dni" placeholder="Ej: 34123..." autocomplete="off">
                        </div>
                    </div>

                    <!-- FILTRO CUIT -->
                    <div class="filter-group">
                        <label for="filtro-cuit">CUIT / CUIL:</label>
                        <div class="filter-input-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="filtro-cuit" placeholder="Ej: 2034123..." autocomplete="off">
                        </div>
                    </div>

                    <!-- FILTRO APELLIDO -->
                    <div class="filter-group">
                        <label for="filtro-apellido">Apellido:</label>
                        <div class="filter-input-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="filtro-apellido" placeholder="Buscar por apellido..." autocomplete="off">
                        </div>
                    </div>

                    <!-- FILTRO NOMBRE -->
                    <div class="filter-group">
                        <label for="filtro-nombre">Nombre:</label>
                        <div class="filter-input-wrap">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="filtro-nombre" placeholder="Buscar por nombre..." autocomplete="off">
                        </div>
                    </div>

                    <!-- BOTÓN LIMPIAR -->
                    <div>
                        <button type="button" class="btn-clear-filters" id="btn-limpiar-filtros" title="Restablecer todos los filtros">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLA SCROLLABLE CON TODOS LOS BENEFICIARIOS -->
            <div class="table-responsive-container">
                <table class="table-beneficiarios" id="tabla-beneficiarios">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Apellido y Nombre</th>
                            <th>DNI / Documento</th>
                            <th>CUIT</th>
                            <th>Localidad</th>
                            <th>Provincia</th>
                            <th>Nro. Cuenta</th>
                            <th>Tarjeta (Enmascarada)</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-beneficiarios">
                        <?php foreach ($beneficiarios_lista as $b): ?>
                            <tr class="beneficiario-row"
                                data-apellido="<?php echo htmlspecialchars(mb_strtolower($b['apellido'], 'UTF-8')); ?>"
                                data-nombre="<?php echo htmlspecialchars(mb_strtolower($b['nombre'], 'UTF-8')); ?>"
                                data-dni="<?php echo htmlspecialchars($b['dni']); ?>"
                                data-cuit="<?php echo htmlspecialchars($b['cuit']); ?>">
                                <td>
                                    <span class="benef-id-badge">#<?php echo htmlspecialchars($b['id']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($b['apellido'] . ', ' . $b['nombre']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($b['tipodoc'] . ' ' . $b['dni']); ?>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($b['cuit']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($b['localidad']); ?></td>
                                <td><?php echo htmlspecialchars($b['provincia']); ?></td>
                                <td>
                                    <span class="benef-code"><?php echo htmlspecialchars($b['cuenta']); ?></span>
                                </td>
                                <td>
                                    <span class="benef-code"><?php echo htmlspecialchars($b['tarjeta']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="fila-sin-resultados" style="display: none;">
                            <td colspan="8" class="no-records-cell">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="color: #94a3b8; margin-bottom: 8px;">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <div>No se encontraron beneficiarios que coincidan con los filtros ingresados.</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    <?php else: ?>
        <!-- CARD DE ALERTA O ERROR -->
        <section class="export-card-success">
            <div class="export-status-banner" style="background: var(--amber-50); border-color: rgba(245, 158, 11, 0.3);">
                <div class="export-status-icon" style="background: var(--amber-500);">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <div class="export-status-texts">
                    <h3 style="color: #92400e;">Atención</h3>
                    <p style="color: #b45309;"><?php echo htmlspecialchars($error_mensaje); ?></p>
                </div>
            </div>
            <a href="index.php" class="btn-nav-action primary" style="padding: 12px 24px;">Volver al Tablero</a>
        </section>
    <?php endif; ?>

</main>

<!-- =======================================================================
     FOOTER INSTITUCIONAL
     ======================================================================= -->
<footer class="footer-main">
    <div class="footer-inner">
        <div>
            <strong>Sistema CABAL FSR</strong> — Secretaría de Niñez, Adolescencia y Familia (SeNAF)
            <div style="font-size: 0.75rem; margin-top: 4px; color: #64748b;">
                Gobierno de la Provincia de Río Negro • Entidad Financiera 782 • Sucursal 000
            </div>
        </div>
        <div class="footer-links">
            <a href="index.php">Inicio</a>
            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer">Plataforma Cabal</a>
            <a href="ayuda.html" target="_blank">Manual de Usuario</a>
            <a href="docs/ARQUITECTURA.md" target="_blank">Arquitectura</a>
            <a href="GEMINI.md" target="_blank">Normas Técnicas</a>
        </div>
    </div>
</footer>

<!-- SCRIPT DE FILTRADO MULTI-CAMPO EN TIEMPO REAL -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    var inputDni = document.getElementById('filtro-dni');
    var inputCuit = document.getElementById('filtro-cuit');
    var inputApellido = document.getElementById('filtro-apellido');
    var inputNombre = document.getElementById('filtro-nombre');
    var btnLimpiar = document.getElementById('btn-limpiar-filtros');
    var cantFiltrados = document.getElementById('cant-filtrados');
    var filaSinResultados = document.getElementById('fila-sin-resultados');
    var filas = document.querySelectorAll('.beneficiario-row');

    if (!filas.length) return;

    // Función para normalizar texto (sin tildes, minúsculas y sin espacios sobrantes)
    function normalizar(texto) {
        return (texto || '').toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function aplicarFiltros() {
        var valDni = normalizar(inputDni.value);
        var valCuit = normalizar(inputCuit.value);
        var valApellido = normalizar(inputApellido.value);
        var valNombre = normalizar(inputNombre.value);

        var visibles = 0;

        filas.forEach(function (fila) {
            var rowDni = normalizar(fila.getAttribute('data-dni'));
            var rowCuit = normalizar(fila.getAttribute('data-cuit'));
            var rowApellido = normalizar(fila.getAttribute('data-apellido'));
            var rowNombre = normalizar(fila.getAttribute('data-nombre'));

            var coincideDni = !valDni || rowDni.indexOf(valDni) !== -1;
            var coincideCuit = !valCuit || rowCuit.indexOf(valCuit) !== -1;
            var coincideApellido = !valApellido || rowApellido.indexOf(valApellido) !== -1;
            var coincideNombre = !valNombre || rowNombre.indexOf(valNombre) !== -1;

            if (coincideDni && coincideCuit && coincideApellido && coincideNombre) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        cantFiltrados.textContent = visibles;
        filaSinResultados.style.display = (visibles === 0) ? '' : 'none';
    }

    [inputDni, inputCuit, inputApellido, inputNombre].forEach(function (input) {
        input.addEventListener('input', aplicarFiltros);
    });

    btnLimpiar.addEventListener('click', function () {
        inputDni.value = '';
        inputCuit.value = '';
        inputApellido.value = '';
        inputNombre.value = '';
        aplicarFiltros();
        inputApellido.focus();
    });
});
</script>

</body>
</html>