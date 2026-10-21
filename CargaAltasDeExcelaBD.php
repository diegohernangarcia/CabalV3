<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Importación y Validación de Planilla Excel Altas a Base de Datos
 * Entidad 782 - Cabal
 */

require_once('phpexcel/PHPExcel.php');
require_once('BD/BD.php');
require_once('BD/Funciones.php');

$archivo_excel = "archivos/Altas.xlsx";
$archivo_existe = file_exists($archivo_excel);

$registros = array();
$errores = array();
$total_filas = 0;
$total_validos = 0;
$estado_proceso = 'espera'; // 'exito', 'error_validacion', 'sin_archivo', 'error_lectura'
$mensaje_proceso = '';
$fecha_proceso = date("d/m/Y H:i");

if (!$archivo_existe) {
    $estado_proceso = 'sin_archivo';
    $mensaje_proceso = 'No se encontró el archivo archivos/Altas.xlsx en el servidor. Por favor suba la planilla correspondiente desde el módulo de recepción de archivos.';
} else {
    try {
        $inputFileType = PHPExcel_IOFactory::identify($archivo_excel);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo_excel);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = (int)$sheet->getHighestRow();

        $dnis_en_archivo = array();

        // Recorrido y validación estricta fila por fila
        for ($row = 2; $row <= $highestRow; $row++) {
            $apellido = trim((string)$sheet->getCell("A" . $row)->getValue());
            $nombre = trim((string)$sheet->getCell("B" . $row)->getValue());
            $calle = trim((string)$sheet->getCell("C" . $row)->getValue());
            $numero = trim((string)$sheet->getCell("D" . $row)->getValue());
            $piso = trim((string)$sheet->getCell("E" . $row)->getValue());
            $depto = trim((string)$sheet->getCell("F" . $row)->getValue());
            $localidad = trim((string)$sheet->getCell("G" . $row)->getValue());
            $provincia = trim((string)$sheet->getCell("H" . $row)->getValue());
            $sexo = strtoupper(trim((string)$sheet->getCell("I" . $row)->getValue()));
            $cp = trim((string)$sheet->getCell("J" . $row)->getValue());
            $tel_carac = trim((string)$sheet->getCell("K" . $row)->getValue());
            $tel_num = trim((string)$sheet->getCell("L" . $row)->getValue());
            $estado_civil = strtoupper(trim((string)$sheet->getCell("M" . $row)->getValue()));
            $fecha_nac = trim((string)$sheet->getCell("N" . $row)->getValue());
            $doc_tipo = trim((string)$sheet->getCell("O" . $row)->getValue());
            $doc_num = trim((string)$sheet->getCell("P" . $row)->getValue());
            $doc_prov = trim((string)$sheet->getCell("Q" . $row)->getValue());
            $cuit = trim((string)$sheet->getCell("R" . $row)->getValue());

            // Ignorar filas totalmente vacías
            if ($apellido === '' && $nombre === '' && $doc_num === '') {
                continue;
            }

            $total_filas++;
            $fila_errores = array();

            // 1. Verificación de DNI Duplicado dentro del mismo Excel
            if ($doc_num !== '') {
                if (isset($dnis_en_archivo[$doc_num])) {
                    $fila_errores[] = "DNI duplicado en el mismo archivo (ya ingresado en la fila " . $dnis_en_archivo[$doc_num] . ")";
                } else {
                    $dnis_en_archivo[$doc_num] = $row;
                }
            } else {
                $fila_errores[] = "El Número de Documento no puede estar vacío";
            }

            // 2. Verificación si el beneficiario ya existe en la base de datos
            if ($doc_num !== '') {
                $dni_busqueda = $doc_num;
                if (EstaDNI($dni_busqueda)) {
                    $fila_errores[] = "El titular con DNI {$doc_num} YA se encuentra registrado en el padrón de beneficiarios";
                }
            }

            // 3. Validaciones de longitud y formato reglamentario Cabal
            if (mb_strlen($apellido, 'UTF-8') > 16) {
                $fila_errores[] = "Apellido excede 16 caracteres (tiene " . mb_strlen($apellido, 'UTF-8') . ")";
            }
            if (mb_strlen($apellido . '/' . $nombre, 'UTF-8') > 25) {
                $fila_errores[] = "Apellido/Nombre combinados exceden 25 caracteres (tiene " . mb_strlen($apellido . '/' . $nombre, 'UTF-8') . ")";
            }
            if (mb_strlen($calle, 'UTF-8') > 25) {
                $fila_errores[] = "Calle excede 25 caracteres (tiene " . mb_strlen($calle, 'UTF-8') . ")";
            }
            if (mb_strlen($numero, 'UTF-8') > 5) {
                $fila_errores[] = "Número de domicilio excede 5 caracteres";
            }
            if (mb_strlen($piso, 'UTF-8') > 2) {
                $fila_errores[] = "Piso excede 2 caracteres";
            }
            if (mb_strlen($depto, 'UTF-8') > 2) {
                $fila_errores[] = "Departamento excede 2 caracteres";
            }
            if (mb_strlen($localidad, 'UTF-8') > 30) {
                $fila_errores[] = "Localidad excede 30 caracteres";
            }
            if (mb_strlen($provincia, 'UTF-8') > 2) {
                $fila_errores[] = "Provincia excede 2 caracteres";
            }
            if ($sexo !== 'M' && $sexo !== 'F') {
                $fila_errores[] = "Género debe ser 'M' o 'F' (recibido: '{$sexo}')";
            }
            if (mb_strlen($cp, 'UTF-8') > 8) {
                $fila_errores[] = "Código Postal excede 8 caracteres (CPA Correo Argentino)";
            }
            if (mb_strlen($tel_carac, 'UTF-8') > 5) {
                $fila_errores[] = "Característica telefónica excede 5 dígitos";
            }
            if (mb_strlen($tel_num, 'UTF-8') > 8) {
                $fila_errores[] = "Número de teléfono excede 8 dígitos";
            }
            if (!in_array($estado_civil, array('C', 'S', 'V', 'O'))) {
                $fila_errores[] = "Estado Civil inválido. Valores válidos: C, S, V, O (recibido: '{$estado_civil}')";
            }
            if (mb_strlen($fecha_nac, 'UTF-8') > 8) {
                $fila_errores[] = "Fecha de Nacimiento excede 8 caracteres (AAAAMMDD)";
            }
            if (mb_strlen($doc_tipo, 'UTF-8') > 2) {
                $fila_errores[] = "Tipo de Documento excede 2 caracteres";
            }
            if (mb_strlen($doc_num, 'UTF-8') > 11) {
                $fila_errores[] = "Número de Documento excede 11 dígitos";
            }
            if (mb_strlen($doc_prov, 'UTF-8') > 2) {
                $fila_errores[] = "Provincia del Documento excede 2 caracteres";
            }
            if (mb_strlen($cuit, 'UTF-8') > 11) {
                $fila_errores[] = "CUIT excede 11 dígitos";
            }

            $es_valido = (count($fila_errores) === 0);
            if ($es_valido) {
                $total_validos++;
            } else {
                foreach ($fila_errores as $err) {
                    $errores[] = array(
                        'fila'    => $row,
                        'titular' => $apellido . ' ' . $nombre,
                        'dni'     => $doc_num,
                        'error'   => $err
                    );
                }
            }

            $registros[] = array(
                'fila'                   => $row,
                'valido'                 => $es_valido,
                'errores'                => $fila_errores,
                'Apellido'               => $apellido,
                'Nombre'                 => $nombre,
                'DomicilioCalle'         => $calle,
                'DomicilioNumero'        => $numero,
                'DomicilioPiso'          => $piso,
                'DomicilioDepartamento'  => $depto,
                'Localidad'              => $localidad,
                'Provincia'              => $provincia,
                'Sexo'                   => $sexo,
                'CodigoPostal'           => $cp,
                'TelefonoCaracteristica' => $tel_carac,
                'TelefonoNumero'         => $tel_num,
                'EstadoCivil'            => $estado_civil,
                'FechaNacimiento'        => $fecha_nac,
                'DocumentoTipo'          => $doc_tipo,
                'DocumentoNumero'        => $doc_num,
                'DocumentoProvincia'     => $doc_prov,
                'CUIT'                   => $cuit
            );
        }

        // Si hay errores de validación, NO modificamos la base de datos (seguridad transaccional)
        if (count($errores) > 0) {
            $estado_proceso = 'error_validacion';
            $mensaje_proceso = 'La planilla contiene ' . count($errores) . ' inconsistencias o beneficiarios duplicados. La tabla altas NO fue modificada para proteger la integridad de los datos.';
        } else if ($total_filas === 0) {
            $estado_proceso = 'error_validacion';
            $mensaje_proceso = 'El archivo Excel no contiene filas de beneficiarios para procesar.';
        } else {
            // Guardar en la base de datos de manera atómica con transacción
            $pdo = new PDO(
                'mysql:host=localhost;dbname=cabal;charset=utf8',
                'pucho',
                '78900',
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );

            $pdo->beginTransaction();
            $pdo->exec("TRUNCATE TABLE altas");

            $sql_ins = "INSERT INTO altas (
                Apellido, Nombre, DomicilioCalle, DomicilioNumero, DomicilioPiso, DomicilioDepartamento,
                Localidad, Provincia, Sexo, CodigoPostal, TelefonoCaracteristica, TelefonoNumero,
                EstadoCivil, FechaNacimiento, DocumentoTipo, DocumentoNumero, DocumentoProvincia, CUIT
            ) VALUES (
                :Apellido, :Nombre, :DomicilioCalle, :DomicilioNumero, :DomicilioPiso, :DomicilioDepartamento,
                :Localidad, :Provincia, :Sexo, :CodigoPostal, :TelefonoCaracteristica, :TelefonoNumero,
                :EstadoCivil, :FechaNacimiento, :DocumentoTipo, :DocumentoNumero, :DocumentoProvincia, :CUIT
            )";

            $stmt_ins = $pdo->prepare($sql_ins);

            foreach ($registros as $reg) {
                $stmt_ins->execute(array(
                    ':Apellido'               => mb_strtoupper($reg['Apellido'], 'UTF-8'),
                    ':Nombre'                 => mb_strtoupper($reg['Nombre'], 'UTF-8'),
                    ':DomicilioCalle'         => mb_strtoupper($reg['DomicilioCalle'], 'UTF-8'),
                    ':DomicilioNumero'        => mb_strtoupper($reg['DomicilioNumero'], 'UTF-8'),
                    ':DomicilioPiso'          => mb_strtoupper($reg['DomicilioPiso'], 'UTF-8'),
                    ':DomicilioDepartamento'  => mb_strtoupper($reg['DomicilioDepartamento'], 'UTF-8'),
                    ':Localidad'              => mb_strtoupper($reg['Localidad'], 'UTF-8'),
                    ':Provincia'              => mb_strtoupper($reg['Provincia'], 'UTF-8'),
                    ':Sexo'                   => mb_strtoupper($reg['Sexo'], 'UTF-8'),
                    ':CodigoPostal'           => mb_strtoupper($reg['CodigoPostal'], 'UTF-8'),
                    ':TelefonoCaracteristica' => mb_strtoupper($reg['TelefonoCaracteristica'], 'UTF-8'),
                    ':TelefonoNumero'         => mb_strtoupper($reg['TelefonoNumero'], 'UTF-8'),
                    ':EstadoCivil'            => mb_strtoupper($reg['EstadoCivil'], 'UTF-8'),
                    ':FechaNacimiento'        => mb_strtoupper($reg['FechaNacimiento'], 'UTF-8'),
                    ':DocumentoTipo'          => mb_strtoupper($reg['DocumentoTipo'], 'UTF-8'),
                    ':DocumentoNumero'        => mb_strtoupper($reg['DocumentoNumero'], 'UTF-8'),
                    ':DocumentoProvincia'     => mb_strtoupper($reg['DocumentoProvincia'], 'UTF-8'),
                    ':CUIT'                   => mb_strtoupper($reg['CUIT'], 'UTF-8')
                ));
            }

            $pdo->commit();
            $estado_proceso = 'exito';
            $mensaje_proceso = 'Se validaron e importaron correctamente ' . count($registros) . ' registros a la tabla altas.';
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $estado_proceso = 'error_lectura';
        $mensaje_proceso = 'Error crítico al procesar el archivo Excel: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Excel Altas a Base de Datos | Sistema Cabal FSR</title>
    <meta name="description" content="Módulo de validación de longitud reglamentaria Cabal y carga de registros a la tabla altas">
    <link rel="icon" href="img/favicon.png">
    <!-- Estilos del Tablero de Operaciones -->
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Gestor de Modo Oscuro / Claro -->
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .status-hero-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            padding: 28px 32px;
            margin-bottom: 28px;
        }

        .banner-outcome {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 22px;
            border-radius: var(--radius-lg);
            margin-bottom: 22px;
        }

        .banner-outcome.success {
            background: var(--emerald-50);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #065f46;
        }

        .banner-outcome.error {
            background: #fef2f2;
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #991b1b;
        }

        .banner-outcome.warning {
            background: var(--amber-50);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #92400e;
        }

        .outcome-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #ffffff;
        }

        .banner-outcome.success .outcome-icon {
            background: var(--emerald-600);
        }

        .banner-outcome.error .outcome-icon {
            background: #dc2626;
        }

        .banner-outcome.warning .outcome-icon {
            background: var(--amber-500);
        }

        .outcome-texts h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.18rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .outcome-texts p {
            font-size: 0.92rem;
            line-height: 1.45;
            margin: 0;
        }

        .summary-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-stat-box {
            background: #f8fafc;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 16px 20px;
        }

        .stat-box-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-box-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--navy-900);
        }

        .stat-box-desc {
            font-size: 0.78rem;
            color: var(--text-light);
            margin-top: 4px;
        }

        .errors-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid #fecaca;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 28px;
        }

        .errors-card-header {
            background: #fee2e2;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #991b1b;
            font-weight: 700;
            font-size: 1rem;
        }

        .table-errors {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .table-errors th {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px 16px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid #fecaca;
        }

        .table-errors td {
            padding: 10px 16px;
            border-bottom: 1px solid #fee2e2;
            vertical-align: middle;
        }

        .table-errors tr:last-child td {
            border-bottom: none;
        }

        .preview-section-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .preview-section-header {
            padding: 22px 28px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-control-wrap {
            position: relative;
            min-width: 280px;
        }

        .search-control-wrap input {
            width: 100%;
            padding: 9px 14px 9px 38px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            color: var(--text-main);
            background: var(--input-bg);
            outline: none;
            transition: all var(--transition-fast);
        }

        .search-control-wrap input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .search-control-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .table-scroll-container {
            overflow-x: auto;
            max-height: 580px;
        }

        .altas-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            text-align: left;
        }

        .altas-data-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: var(--navy-800);
            color: #ffffff;
        }

        .altas-data-table th {
            padding: 12px 14px;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .altas-data-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-light);
            white-space: nowrap;
            color: var(--text-main);
        }

        .altas-data-table tr.row-invalid {
            background: #fff1f2;
        }

        .altas-data-table tr.row-invalid td {
            color: #9f1239;
        }

        .altas-data-table tr:hover {
            background: var(--surface-subtle);
        }

        .altas-data-table tr.row-invalid:hover {
            background: #ffe4e6;
        }

        .badge-status-val {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: var(--radius-full);
            text-transform: uppercase;
        }

        .badge-status-val.ok {
            background: #dcfce7;
            color: #15803d;
        }

        .badge-status-val.err {
            background: #fee2e2;
            color: #b91c1c;
        }

        .badge-gender {
            display: inline-block;
            font-weight: 700;
            font-size: 0.72rem;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .badge-gender.m {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-gender.f {
            background: #fce7f3;
            color: #9d174d;
        }

        .badge-civil {
            display: inline-block;
            font-size: 0.74rem;
            font-weight: 600;
            color: #475569;
            background: #f1f5f9;
            padding: 1px 6px;
            border-radius: 4px;
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
            <a href="SubirArchivo.html" class="btn-nav-action primary" id="btn-subir-archivo">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Subir Planilla
            </a>

            <a href="GenerarNovedadAltas.php" class="btn-nav-action" id="btn-generar-altas">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                </svg>
                Generar ALTAUSUCBPRE
            </a>

            <a href="index.php" class="btn-nav-action" id="btn-inicio">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Tablero
            </a>
        </div>
    </div>
</header>

<!-- =======================================================================
     CONTENEDOR PRINCIPAL
     ======================================================================= -->
<main class="page-container" style="margin-top: 32px;">

    <!-- HERO HEADER DEL MÓDULO -->
    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-content">
            <div class="help-badge-topic">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Módulo Beneficiarios • Importación de Nuevas Altas
            </div>
            <h2>Importar Planilla Excel de Altas a Base de Datos</h2>
            <p>
                Lectura del archivo <code>archivos/Altas.xlsx</code>, validación de longitudes reglamentarias Cabal y carga de registros en la tabla temporal <code>altas</code> para la posterior emisión del lote <b>ALTAUSUCBPRE.782</b>.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <span class="badge-tag accent" style="padding: 8px 14px; font-size: 0.82rem;">
                Entidad 782 • Anexo 1
            </span>
        </div>
    </section>

    <!-- ESTADO DEL PROCESO & RESULTADO -->
    <section class="status-hero-card">
        
        <?php if ($estado_proceso === 'exito'): ?>
            <div class="banner-outcome success">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <div class="outcome-texts">
                    <h3>¡Planilla Importada con Éxito a la Tabla Altas!</h3>
                    <p><?php echo htmlspecialchars($mensaje_proceso); ?></p>
                </div>
            </div>
        <?php elseif ($estado_proceso === 'error_validacion'): ?>
            <div class="banner-outcome error">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="outcome-texts">
                    <h3>Inconsistencias Detectadas en la Planilla</h3>
                    <p><?php echo htmlspecialchars($mensaje_proceso); ?></p>
                </div>
            </div>
        <?php elseif ($estado_proceso === 'sin_archivo'): ?>
            <div class="banner-outcome warning">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <div class="outcome-texts">
                    <h3>Archivo No Encontrado</h3>
                    <p><?php echo htmlspecialchars($mensaje_proceso); ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="banner-outcome error">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="outcome-texts">
                    <h3>Error Durante la Ejecución</h3>
                    <p><?php echo htmlspecialchars($mensaje_proceso); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- TARJETAS MÉTRICAS DE RESUMEN -->
        <div class="summary-stats-grid">
            <div class="summary-stat-box">
                <div class="stat-box-label">Total Filas Leídas</div>
                <div class="stat-box-value"><?php echo number_format($total_filas, 0, ',', '.'); ?></div>
                <div class="stat-box-desc">Registros en planilla Excel</div>
            </div>

            <div class="summary-stat-box">
                <div class="stat-box-label">Registros Válidos</div>
                <div class="stat-box-value" style="color: <?php echo $total_validos === $total_filas && $total_filas > 0 ? 'var(--emerald-600)' : '#e11d48'; ?>;">
                    <?php echo number_format($total_validos, 0, ',', '.'); ?>
                </div>
                <div class="stat-box-desc"><?php echo count($errores) === 0 ? '100% de registros aprobados' : count($errores) . ' errores a corregir'; ?></div>
            </div>

            <div class="summary-stat-box">
                <div class="stat-box-label">Estado de la Base de Datos</div>
                <div class="stat-box-value" style="font-size: 1.15rem; color: <?php echo $estado_proceso === 'exito' ? 'var(--emerald-600)' : '#dc2626'; ?>;">
                    <?php echo $estado_proceso === 'exito' ? 'Tabla altas Actualizada' : 'Tabla altas Sin Modificar'; ?>
                </div>
                <div class="stat-box-desc">Protección contra inconsistencias</div>
            </div>

            <div class="summary-stat-box">
                <div class="stat-box-label">Archivo Fuente</div>
                <div class="stat-box-value" style="font-size: 1rem; font-family: 'JetBrains Mono', monospace; color: var(--primary-700);">
                    Altas.xlsx
                </div>
                <div class="stat-box-desc">Ubicación: archivos/Altas.xlsx</div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS SEGÚN RESULTADO -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; padding-top: 10px; border-top: 1px solid var(--border-light);">
            <?php if ($estado_proceso === 'exito'): ?>
                <a href="GenerarNovedadAltas.php" class="btn-nav-action primary" style="padding: 11px 20px; font-size: 0.92rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                    1. Generar ALTAUSUCBPRE.782
                </a>
                <a href="V2/Altas.php" class="btn-nav-action" style="padding: 11px 20px; font-size: 0.92rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    2. Conversor Altas V2 (Descarga Directa)
                </a>
            <?php else: ?>
                <a href="SubirArchivo.html" class="btn-nav-action primary" style="padding: 11px 20px; font-size: 0.92rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Subir Planilla Altas.xlsx Corregida
                </a>
            <?php endif; ?>

            <a href="index.php" class="btn-nav-action" style="padding: 11px 20px; font-size: 0.92rem;">
                Volver al Tablero Principal
            </a>
        </div>

    </section>

    <!-- TABLA DE ERRORES EN CASO DE INCONSISTENCIAS -->
    <?php if (count($errores) > 0): ?>
    <section class="errors-card">
        <div class="errors-card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            Detalle de Errores e Inconsistencias (<?php echo count($errores); ?> observaciones)
        </div>
        <div style="overflow-x: auto;">
            <table class="table-errors">
                <thead>
                    <tr>
                        <th style="width: 80px;">Fila Excel</th>
                        <th>Titular Observado</th>
                        <th>DNI</th>
                        <th>Descripción del Error / Restricción Cabal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($errores as $err): ?>
                    <tr>
                        <td style="font-weight: 700; font-family: 'JetBrains Mono', monospace; color: #991b1b;">
                            #<?php echo htmlspecialchars($err['fila']); ?>
                        </td>
                        <td style="font-weight: 600;">
                            <?php echo htmlspecialchars($err['titular']); ?>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace;">
                            <?php echo htmlspecialchars($err['dni']); ?>
                        </td>
                        <td style="color: #b91c1c; font-weight: 500;">
                            <?php echo htmlspecialchars($err['error']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- TABLA DETALLADA DE BENEFICIARIOS LEÍDOS -->
    <?php if ($total_filas > 0): ?>
    <section class="preview-section-card">
        <header class="preview-section-header">
            <div>
                <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--navy-900);">
                    Contenido de la Planilla Altas.xlsx
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 2px;">
                    Mostrando <strong id="contador-visibles"><?php echo $total_filas; ?></strong> registros leídos desde el archivo.
                </p>
            </div>

            <!-- BUSCADOR EN VIVO DE LA TABLA -->
            <div class="search-control-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="buscar-tabla" placeholder="Filtrar por titular, DNI, localidad...">
            </div>
        </header>

        <div class="table-scroll-container">
            <table class="altas-data-table" id="tabla-altas">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estado</th>
                        <th>Titular (Apellido y Nombre)</th>
                        <th>DNI</th>
                        <th>CUIT</th>
                        <th>Domicilio</th>
                        <th>Localidad</th>
                        <th>Prov.</th>
                        <th>Sexo</th>
                        <th>F. Nacimiento</th>
                        <th>C.P.</th>
                        <th>Teléfono</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla">
                    <?php foreach ($registros as $idx => $r): ?>
                    <tr class="<?php echo $r['valido'] ? '' : 'row-invalid'; ?>">
                        <td style="font-weight: 700; font-family: 'JetBrains Mono', monospace;">
                            <?php echo $r['fila']; ?>
                        </td>
                        <td>
                            <?php if ($r['valido']): ?>
                                <span class="badge-status-val ok">✓ OK</span>
                            <?php else: ?>
                                <span class="badge-status-val err" title="<?php echo htmlspecialchars(implode(' — ', $r['errores'])); ?>">
                                    ⚠ Error
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600;">
                            <?php echo htmlspecialchars($r['Apellido'] . ' ' . $r['Nombre']); ?>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700;">
                            <?php echo htmlspecialchars($r['DocumentoNumero']); ?>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace;">
                            <?php echo htmlspecialchars($r['CUIT']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($r['DomicilioCalle'] . ' ' . $r['DomicilioNumero'] . ($r['DomicilioPiso'] ? ' P.' . $r['DomicilioPiso'] : '') . ($r['DomicilioDepartamento'] ? ' D.' . $r['DomicilioDepartamento'] : '')); ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['Localidad']); ?></td>
                        <td><?php echo htmlspecialchars($r['Provincia']); ?></td>
                        <td>
                            <span class="badge-gender <?php echo strtolower($r['Sexo']); ?>">
                                <?php echo htmlspecialchars($r['Sexo']); ?>
                            </span>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['FechaNacimiento']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['CodigoPostal']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;">
                            <?php echo htmlspecialchars(($r['TelefonoCaracteristica'] ? $r['TelefonoCaracteristica'] . '-' : '') . $r['TelefonoNumero']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
            <a href="SubirArchivo.html">Subir Archivos</a>
            <a href="GenerarNovedadAltas.php">Generar ALTAUSUCBPRE</a>
            <a href="ayuda.html" target="_blank">Manual de Usuario</a>
            <a href="docs/ARQUITECTURA.md" target="_blank">Arquitectura</a>
            <a href="GEMINI.md" target="_blank">Normas Técnicas</a>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var inputBusqueda = document.getElementById('buscar-tabla');
    var contadorVisibles = document.getElementById('contador-visibles');
    var filas = document.querySelectorAll('#cuerpo-tabla tr');

    if (inputBusqueda && filas.length > 0) {
        inputBusqueda.addEventListener('input', function() {
            var termino = this.value.toLowerCase().trim();
            var visibles = 0;

            for (var i = 0; i < filas.length; i++) {
                var textoFila = filas[i].textContent.toLowerCase();
                if (textoFila.indexOf(termino) !== -1) {
                    filas[i].style.display = '';
                    visibles++;
                } else {
                    filas[i].style.display = 'none';
                }
            }

            if (contadorVisibles) {
                contadorVisibles.textContent = visibles;
            }
        });
    }
});
</script>

</body>
</html>
