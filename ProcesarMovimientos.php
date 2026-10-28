<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * 4.1 Procesamiento de Movimientos de Tarjetas (Movimientos.txt)
 * Carga directa mediante Drag & Drop, auditoría previa, detección de duplicados por período
 * y almacenamiento seguro en tabla movimientos.
 * Entidad 782 - Cabal
 */

require_once("BD/BD.php");
require_once("BD/Funciones.php");
require_once("BD/UsarGestion.php");

// Configuración de zona horaria y cabeceras anti-caché
date_default_timezone_set("America/Argentina/Buenos_Aires");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$mensajeFeedback = '';
$tipoFeedback = '';
$archivoProcesado = false;
$guardadoExitoso = false;
$nombreArchivoMostrado = '';
$tamanoArchivo = 0;
$lineasRaw = array();
$movimientosParseados = array();

// Período seleccionado
$mesesLista = array(
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
);

$mesSeleccionado = isset($_POST['Mes']) ? trim($_POST['Mes']) : 'Enero';
$anioSeleccionado = isset($_POST['Anio']) ? trim($_POST['Anio']) : '2026';
$archivoSeleccionado = isset($_POST['Archivo']) ? trim($_POST['Archivo']) : '1';

// Estadísticas de auditoría
$stats = array(
    'total_movimientos' => 0,
    'total_consumos'    => 0,
    'monto_consumos'    => 0.0,
    'total_cargas'      => 0,
    'monto_cargas'      => 0.0,
    'total_saldos'      => 0,
    'tarjetas_unicas'   => array(),
    'cuentas_unicas'    => array(),
    'fechas_detectadas' => array(),
    'ya_procesado'      => false,
    'filas_existentes'  => 0,
    'fecha_min'         => '',
    'fecha_max'         => ''
);

// Directorio seguro para almacenar archivos
$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0777, true);
}

// Obtener los últimos períodos registrados en base de datos para orientación del operador
$ultimosPeriodos = array();
try {
    $stmtUltimos = $conn->query("
        SELECT Mes, Anio, Archivo, COUNT(*) as cantidad 
        FROM movimientos 
        GROUP BY Anio, Mes, Archivo 
        ORDER BY Anio DESC, 
        CASE 
            WHEN Mes = 'Enero' THEN 1 WHEN Mes = 'Febrero' THEN 2 WHEN Mes = 'Marzo' THEN 3
            WHEN Mes = 'Abril' THEN 4 WHEN Mes = 'Mayo' THEN 5 WHEN Mes = 'Junio' THEN 6
            WHEN Mes = 'Julio' THEN 7 WHEN Mes = 'Agosto' THEN 8 WHEN Mes = 'Septiembre' THEN 9
            WHEN Mes = 'Octubre' THEN 10 WHEN Mes = 'Noviembre' THEN 11 WHEN Mes = 'Diciembre' THEN 12
        END DESC, Archivo DESC LIMIT 5
    ");
    if ($stmtUltimos) {
        $ultimosPeriodos = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Si falla consulta de historial, no interrumpe el flujo
}

$accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';

// -------------------------------------------------------------------------
// 1. SUBIDA DIRECTA DE ARCHIVO (DRAG & DROP O EXAMINAR)
// -------------------------------------------------------------------------
if ($accion === 'subir_archivo') {
    if (!isset($_FILES['archivo_movimientos']) || $_FILES['archivo_movimientos']['error'] === UPLOAD_ERR_NO_FILE) {
        $mensajeFeedback = "No se detectó ningún archivo seleccionado. Por favor, arrastre o seleccione el extracto de movimientos de Cabal.";
        $tipoFeedback = "danger";
    } elseif ($_FILES['archivo_movimientos']['error'] !== UPLOAD_ERR_OK) {
        $codErr = $_FILES['archivo_movimientos']['error'];
        $mensajeFeedback = "Ocurrió un error al recibir el archivo desde el navegador (Código PHP: " . $codErr . ").";
        $tipoFeedback = "danger";
    } else {
        $fileTmp  = $_FILES['archivo_movimientos']['tmp_name'];
        $fileName = basename($_FILES['archivo_movimientos']['name']);
        $fileSize = $_FILES['archivo_movimientos']['size'];

        if ($fileSize <= 0) {
            $mensajeFeedback = "El archivo adjuntado está vacío (0 bytes).";
            $tipoFeedback = "danger";
        } else {
            // Respaldar copia limpia en archivos/
            $destinoCanónico = $targetDir . "Movimientos.txt";
            $destinoSubido   = $targetDir . "Movimientos_subido.txt";
            @copy($fileTmp, $destinoCanónico);
            @copy($fileTmp, $destinoSubido);

            // Leer líneas del archivo temporal subido
            $contenido = file_get_contents($fileTmp);
            $lineasRaw = preg_split("/\r\n|\n|\r/", $contenido);
            $nombreArchivoMostrado = $fileName . " (Recién subido: " . date("H:i:s") . ")";
            $tamanoArchivo = $fileSize;
            $archivoProcesado = true;
        }
    }
}

// -------------------------------------------------------------------------
// 2. PROCESAMIENTO DE ARCHIVO EXISTENTE EN EL SERVIDOR
// -------------------------------------------------------------------------
elseif ($accion === 'procesar_servidor') {
    $archivoServidor = $targetDir . "Movimientos.txt";
    if (!file_exists($archivoServidor)) {
        $mensajeFeedback = "No se encontró el archivo 'archivos/Movimientos.txt' en el servidor. Puede adjuntar uno nuevo arrastrándolo a la zona de carga.";
        $tipoFeedback = "danger";
    } else {
        $contenido = file_get_contents($archivoServidor);
        $lineasRaw = preg_split("/\r\n|\n|\r/", $contenido);
        $nombreArchivoMostrado = "Movimientos.txt (Archivo residente en el servidor)";
        $tamanoArchivo = filesize($archivoServidor);
        $archivoProcesado = true;
    }
}

// -------------------------------------------------------------------------
// 3. PARSING POSICIONAL CABAL (ENTIDAD 782)
// -------------------------------------------------------------------------
if ($archivoProcesado && !empty($lineasRaw)) {
    $correlativo = 1;

    foreach ($lineasRaw as $line) {
        $lineClean = rtrim($line, "\r\n");
        if (strlen($lineClean) < 70) {
            continue; // Ignorar líneas en blanco o de trailer muy cortas
        }

        $banco           = substr($lineClean, 0, 3);
        $sucursal        = substr($lineClean, 3, 3);
        $cuenta          = trim(substr($lineClean, 6, 13));
        $tarjeta         = trim(substr($lineClean, 19, 16));
        $tipoDoc         = substr($lineClean, 35, 1);
        $documento       = trim(substr($lineClean, 36, 11));
        $nombreApellido  = trim(substr($lineClean, 47, 25));
        $codigoOperacion = substr($lineClean, 72, 3);

        if ($codigoOperacion === "999") {
            $codigoDesc = "SALDO INICIAL";
            $categoriaOp = "saldo";
        } elseif ($codigoOperacion === "671") {
            $codigoDesc = "CARGA/DESCARGA";
            $categoriaOp = "carga";
        } elseif ($codigoOperacion === "491") {
            $codigoDesc = "AUTORIZACIONES";
            $categoriaOp = "consumo";
        } elseif ($codigoOperacion === "241") {
            $codigoDesc = "SALDO FINAL";
            $categoriaOp = "saldo";
        } else {
            $codigoDesc = $codigoOperacion;
            $categoriaOp = "otro";
        }

        $fechaInicial = substr($lineClean, 75, 8);
        $fecha = substr($fechaInicial, 0, 2) . "/" . substr($fechaInicial, 2, 2) . "/" . substr($fechaInicial, 4, 4);
        $comercio = trim(substr($lineClean, 83, 25));

        $importeInicial = substr($lineClean, 108, 11);
        $importeValor   = floatval(substr($importeInicial, 0, 9) . "." . substr($importeInicial, 9, 2));
        $signo          = substr($lineClean, 119, 1);
        if ($signo !== '+' && $signo !== '-') {
            $signo = '+';
        }

        $importeInicial2 = substr($lineClean, 131, 17);
        $importe2        = substr($importeInicial2, 0, 15) . "." . substr($importeInicial2, 15, 2);
        $signo2          = substr($lineClean, 148, 1);
        if ($signo2 !== '+' && $signo2 !== '-') {
            $signo2 = '+';
        }

        // Métricas
        if ($categoriaOp === 'consumo') {
            $stats['total_consumos']++;
            $stats['monto_consumos'] += $importeValor;
        } elseif ($categoriaOp === 'carga') {
            $stats['total_cargas']++;
            $stats['monto_cargas'] += $importeValor;
        } elseif ($categoriaOp === 'saldo') {
            $stats['total_saldos']++;
        }

        if (!empty($tarjeta)) {
            $stats['tarjetas_unicas'][$tarjeta] = true;
        }
        if (!empty($cuenta)) {
            $stats['cuentas_unicas'][$cuenta] = true;
        }
        if (!empty($fecha)) {
            $stats['fechas_detectadas'][$fecha] = true;
        }

        $movimientosParseados[] = array(
            'nro'            => $correlativo++,
            'banco'          => $banco,
            'sucursal'       => $sucursal,
            'cuenta'         => $cuenta,
            'tarjeta'        => $tarjeta,
            'tipo_doc'       => $tipoDoc,
            'documento'      => $documento,
            'nombre'         => $nombreApellido,
            'codigo'         => $codigoDesc,
            'codigo_raw'     => $codigoOperacion,
            'categoria'      => $categoriaOp,
            'fecha'          => $fecha,
            'comercio'       => $comercio,
            'importe'        => sprintf("%.2f", $importeValor),
            'signo'          => $signo,
            'importe2'       => $importe2,
            'signo2'         => $signo2
        );
    }

    $stats['total_movimientos'] = count($movimientosParseados);

    if ($stats['total_movimientos'] === 0) {
        $mensajeFeedback = "El archivo fue leído pero no contiene registros posicionales de movimientos válidos de Cabal.";
        $tipoFeedback = "warning";
        $archivoProcesado = false;
    } else {
        // Chequeo de duplicados en base de datos para el período seleccionado
        try {
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM movimientos WHERE Mes = :mes AND Anio = :anio AND Archivo = :archivo");
            $stmtCheck->execute(array(
                ':mes'     => $mesSeleccionado,
                ':anio'    => $anioSeleccionado,
                ':archivo' => $archivoSeleccionado
            ));
            $stats['filas_existentes'] = (int)$stmtCheck->fetchColumn();
            $stats['ya_procesado'] = ($stats['filas_existentes'] > 0);
        } catch (Exception $e) {
            $stats['ya_procesado'] = false;
        }

        // Rango de fechas detectadas
        if (!empty($stats['fechas_detectadas'])) {
            $fechasKeys = array_keys($stats['fechas_detectadas']);
            $stats['fecha_min'] = reset($fechasKeys);
            $stats['fecha_max'] = end($fechasKeys);
        }
    }
}

// -------------------------------------------------------------------------
// 4. CONFIRMACIÓN Y GUARDADO TRANSACCIONAL EN BASE DE DATOS
// -------------------------------------------------------------------------
if ($accion === 'guardar_movimientos') {
    $datosJson = isset($_POST['datos_json']) ? $_POST['datos_json'] : '';
    $reemplazar = isset($_POST['reemplazar']) && $_POST['reemplazar'] === '1';
    $movimientosAGuardar = json_decode($datosJson, true);

    if (!is_array($movimientosAGuardar) || empty($movimientosAGuardar)) {
        $mensajeFeedback = "No se recibieron datos de movimientos válidos para almacenar.";
        $tipoFeedback = "danger";
    } else {
        try {
            $conn->beginTransaction();

            // Si se confirmó reemplazo, eliminar registros previos del mismo período
            $eliminadosPrevios = 0;
            if ($reemplazar) {
                $stmtDel = $conn->prepare("DELETE FROM movimientos WHERE Mes = :mes AND Anio = :anio AND Archivo = :archivo");
                $stmtDel->execute(array(
                    ':mes'     => $mesSeleccionado,
                    ':anio'    => $anioSeleccionado,
                    ':archivo' => $archivoSeleccionado
                ));
                $eliminadosPrevios = $stmtDel->rowCount();
            }

            // Preparar sentencia de inserción masiva
            $sqlInsert = "INSERT INTO movimientos 
                (Banco, Sucursal, Cuenta, Tarjeta, TipoDocumento, Documento, NombreApellido, CodigoOperacion, Fecha, Comercio, Importe, Signo, Importe2, Signo2, Mes, Anio, Archivo) 
                VALUES 
                (:Banco, :Sucursal, :Cuenta, :Tarjeta, :TipoDocumento, :Documento, :NombreApellido, :CodigoOperacion, :Fecha, :Comercio, :Importe, :Signo, :Importe2, :Signo2, :Mes, :Anio, :Archivo)";

            $stmtInsert = $conn->prepare($sqlInsert);

            $guardadosOk = 0;
            foreach ($movimientosAGuardar as $m) {
                $stmtInsert->bindValue(':Banco',           $m['banco']);
                $stmtInsert->bindValue(':Sucursal',        $m['sucursal']);
                $stmtInsert->bindValue(':Cuenta',          $m['cuenta']);
                $stmtInsert->bindValue(':Tarjeta',         $m['tarjeta']);
                $stmtInsert->bindValue(':TipoDocumento',   $m['tipo_doc']);
                $stmtInsert->bindValue(':Documento',       $m['documento']);
                $stmtInsert->bindValue(':NombreApellido',  $m['nombre']);
                $stmtInsert->bindValue(':CodigoOperacion', $m['codigo']);
                $stmtInsert->bindValue(':Fecha',           $m['fecha']);
                $stmtInsert->bindValue(':Comercio',        $m['comercio']);
                $stmtInsert->bindValue(':Importe',         $m['importe']);
                $stmtInsert->bindValue(':Signo',           $m['signo']);
                $stmtInsert->bindValue(':Importe2',        $m['importe2']);
                $stmtInsert->bindValue(':Signo2',          $m['signo2']);
                $stmtInsert->bindValue(':Mes',             $mesSeleccionado);
                $stmtInsert->bindValue(':Anio',            $anioSeleccionado);
                $stmtInsert->bindValue(':Archivo',         $archivoSeleccionado);

                $stmtInsert->execute();
                $guardadosOk++;
            }

            $conn->commit();
            $guardadoExitoso = true;
            $mensajeFeedback = "¡Operación completada con éxito! Se insertaron <strong>" . number_format($guardadosOk, 0, ',', '.') . "</strong> movimientos para el período <strong>" . htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado) . " (Carga #" . htmlspecialchars($archivoSeleccionado) . ")</strong>.";
            if ($eliminadosPrevios > 0) {
                $mensajeFeedback .= " Se reemplazaron " . number_format($eliminadosPrevios, 0, ',', '.') . " registros anteriores.";
            }
            $tipoFeedback = "success";

        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $mensajeFeedback = "Error crítico al guardar los movimientos en la base de datos: " . $e->getMessage();
            $tipoFeedback = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1480px;
            margin: 0 auto 60px;
            padding: 0 24px;
        }

        /* Zona interactiva Drag & Drop */
        .dropzone-movimientos {
            display: block;
            border: 2px dashed var(--primary-400);
            border-radius: var(--radius-xl);
            padding: 38px 24px;
            text-align: center;
            background: var(--surface-subtle);
            cursor: pointer;
            transition: all 0.22s ease-in-out;
            margin-top: 18px;
            position: relative;
        }

        .dropzone-movimientos:hover,
        .dropzone-movimientos.dragover {
            border-color: var(--primary-600);
            background: rgba(37, 99, 235, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.12);
        }

        .dropzone-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.12);
            color: var(--primary-600);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            transition: transform 0.2s;
        }

        .dropzone-movimientos:hover .dropzone-icon-wrap {
            transform: scale(1.08);
        }

        .dropzone-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .dropzone-subtitle {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 14px;
        }

        .dropzone-badge-tag {
            display: inline-block;
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            padding: 4px 14px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* Selector de período integrado */
        .periodo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 12px;
        }

        /* Panel de Métricas KPI */
        .stats-kpi-mov {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }

        .kpi-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kpi-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .kpi-subtext {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 600;
        }

        /* Badges de Operación */
        .badge-op {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: var(--radius-full);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .badge-op-consumo {
            background: rgba(147, 51, 234, 0.14);
            color: #9333ea;
            border: 1px solid rgba(147, 51, 234, 0.28);
        }
        .badge-op-carga {
            background: rgba(16, 185, 129, 0.14);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.28);
        }
        .badge-op-saldo {
            background: rgba(100, 116, 139, 0.14);
            color: #64748b;
            border: 1px solid rgba(100, 116, 139, 0.28);
        }

        /* Barra de herramientas y filtros */
        .filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--border-light);
        }

        .filter-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-pill {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .filter-pill:hover,
        .filter-pill.active {
            background: var(--primary-600);
            border-color: var(--primary-600);
            color: #ffffff;
        }

        .search-box-wrap {
            min-width: 280px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 8px 14px 8px 36px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            background: var(--surface-card);
            color: var(--text-main);
            font-size: 0.86rem;
            outline: none;
            transition: border-color 0.15s;
        }

        .search-input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        /* Modal interactivo */
        .modal-backdrop-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
            padding: 20px;
        }

        .modal-card-custom {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            max-width: 580px;
            width: 100%;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            animation: modalFadeIn 0.2s ease-out;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(-10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .recent-period-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-full);
            font-size: 0.76rem;
            color: var(--text-muted);
            margin: 3px;
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

            <a href="ReporteMovimientos.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Reporte Mensual
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

<main class="page-container" style="margin-top: 32px;">

    <!-- BANNER HERO -->
    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--purple-600);">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                Procesar Movimientos de Tarjetas
            </h2>
            <p>
                Importación y conciliación mensual de extractos bancarios Cabal hacia la base de datos de movimientos.
            </p>
        </div>
    </section>

    <!-- MENSAJE DE FEEDBACK -->
    <?php if (!empty($mensajeFeedback)): ?>
        <div class="alert-modern <?php echo $tipoFeedback; ?>" style="margin-bottom: 24px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?php if ($tipoFeedback === 'success'): ?>
                    <polyline points="20 6 9 17 4 12"></polyline>
                <?php elseif ($tipoFeedback === 'danger'): ?>
                    <circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>
                <?php else: ?>
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
                <?php endif; ?>
            </svg>
            <div>
                <?php echo $mensajeFeedback; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- PANTALLA DE ÉXITO TRAS GUARDADO -->
    <?php if ($guardadoExitoso): ?>
        <div class="modern-card" style="text-align: center; padding: 40px 24px;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: rgba(16, 185, 129, 0.15); color: var(--emerald-600); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
                ¡Movimientos Registrados en la Base de Datos!
            </h3>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 28px;">
                El extracto mensual correspondiente a <strong><?php echo htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado); ?> (Carga #<?php echo htmlspecialchars($archivoSeleccionado); ?>)</strong> fue procesado y archivado correctamente para auditoría y consulta.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="consultaMovimiento.php?mes=<?php echo urlencode($mesSeleccionado); ?>&anio=<?php echo urlencode($anioSeleccionado); ?>&archivo=<?php echo urlencode($archivoSeleccionado); ?>" class="btn-modern-primary" style="padding: 10px 22px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Consultar Extracto del Período
                </a>
                <a href="ReporteMovimientos.php" class="btn-modern-secondary" style="padding: 10px 22px;">
                    Ver Catálogo Mensual
                </a>
                <a href="ProcesarMovimientos.php" class="btn-modern-secondary" style="padding: 10px 22px;">
                    Procesar Otro Archivo
                </a>
                <a href="index.php" class="btn-modern-secondary" style="padding: 10px 22px;">
                    Volver al Tablero
                </a>
            </div>
        </div>

    <!-- PANTALLA PRINCIPAL: FORMULARIO Y DRAG & DROP -->
    <?php elseif (!$archivoProcesado): ?>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Parámetros y Selección de Archivo</h3>
                    <p>Indique el período de liquidación y adjunte el archivo <code>Movimientos.txt</code> devuelto por Cabal</p>
                </div>
            </div>
            <div class="modern-card-body">
                
                <form id="formSubirMovimientos" action="ProcesarMovimientos.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="subir_archivo">

                    <!-- SELECCIÓN DE PERÍODO -->
                    <div class="periodo-grid">
                        <div class="form-group-modern">
                            <label for="selectMes" style="font-weight: 700; font-size: 0.85rem; color: var(--text-main); margin-bottom: 6px; display: block;">Mes de Liquidación:</label>
                            <select name="Mes" id="selectMes" class="form-control-modern" style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-light); background: var(--surface-card); color: var(--text-main);" required>
                                <?php foreach ($mesesLista as $m): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($m === $mesSeleccionado ? 'selected' : ''); ?>><?php echo $m; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group-modern">
                            <label for="selectAnio" style="font-weight: 700; font-size: 0.85rem; color: var(--text-main); margin-bottom: 6px; display: block;">Año:</label>
                            <select name="Anio" id="selectAnio" class="form-control-modern" style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-light); background: var(--surface-card); color: var(--text-main);" required>
                                <?php for ($y = 2024; $y <= 2030; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ((string)$y === $anioSeleccionado ? 'selected' : ''); ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group-modern">
                            <label for="selectArchivo" style="font-weight: 700; font-size: 0.85rem; color: var(--text-main); margin-bottom: 6px; display: block;">Número de Carga / Archivo:</label>
                            <select name="Archivo" id="selectArchivo" class="form-control-modern" style="width: 100%; padding: 10px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-light); background: var(--surface-card); color: var(--text-main);" required>
                                <option value="1" <?php echo ($archivoSeleccionado === '1' ? 'selected' : ''); ?>>Carga 01</option>
                                <option value="2" <?php echo ($archivoSeleccionado === '2' ? 'selected' : ''); ?>>Carga 02</option>
                                <option value="3" <?php echo ($archivoSeleccionado === '3' ? 'selected' : ''); ?>>Carga 03</option>
                                <option value="4" <?php echo ($archivoSeleccionado === '4' ? 'selected' : ''); ?>>Carga 04</option>
                                <option value="5" <?php echo ($archivoSeleccionado === '5' ? 'selected' : ''); ?>>Carga 05</option>
                            </select>
                        </div>
                    </div>

                    <!-- INPUT FILE DESACOPLADO -->
                    <input type="file" name="archivo_movimientos" id="fileInputMovimientos" style="display: none;" accept=".txt,.dat,.782,text/plain">

                    <!-- ZONA INTERACTIVA DRAG & DROP -->
                    <label for="fileInputMovimientos" class="dropzone-movimientos" id="dropzoneMovimientos">
                        <div id="dropzoneNormalState">
                            <div class="dropzone-icon-wrap">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <div class="dropzone-title">
                                Arrastre el archivo Movimientos.txt aquí o haga clic para examinar
                            </div>
                            <div class="dropzone-subtitle">
                                Formatos admitidos: <code>Movimientos.txt</code>, <code>.txt</code>, <code>.dat</code> o lotes devueltos por la plataforma bancaria Cabal
                            </div>
                            <span class="dropzone-badge-tag">
                                Subida directa e instantánea sin alterar base de datos
                            </span>
                        </div>

                        <!-- ESTADO DE CARGA ANIMADO -->
                        <div id="dropzoneLoadingState" style="display: none; padding: 20px 0;">
                            <div style="width: 48px; height: 48px; border: 4px solid rgba(37, 99, 235, 0.2); border-top-color: var(--primary-600); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px;"></div>
                            <div class="dropzone-title" id="loadingFileName">Analizando extracto de movimientos...</div>
                            <div class="dropzone-subtitle">Decodificando posiciones bancarias y comprobando duplicados</div>
                        </div>
                    </label>
                </form>

                <!-- BOTÓN ALTERNATIVO: PROCESAR ARCHIVO DEL SERVIDOR -->
                <?php if (file_exists($targetDir . "Movimientos.txt")): ?>
                    <div style="margin-top: 18px; padding-top: 18px; border-top: 1px dashed var(--border-light); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                            ¿Desea auditar el archivo que ya se encuentra en el servidor?
                        </span>
                        <form action="ProcesarMovimientos.php" method="post" style="margin: 0;">
                            <input type="hidden" name="accion" value="procesar_servidor">
                            <input type="hidden" name="Mes" id="hiddenMesServidor" value="<?php echo htmlspecialchars($mesSeleccionado); ?>">
                            <input type="hidden" name="Anio" id="hiddenAnioServidor" value="<?php echo htmlspecialchars($anioSeleccionado); ?>">
                            <input type="hidden" name="Archivo" id="hiddenArchivoServidor" value="<?php echo htmlspecialchars($archivoSeleccionado); ?>">
                            <button type="submit" class="btn-modern-secondary" style="padding: 7px 16px; font-size: 0.82rem;" onclick="sincronizarParametrosServidor();">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                Procesar archivo del servidor (archivos/Movimientos.txt)
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- HISTORIAL DE ÚLTIMOS PERÍODOS CARGADOS -->
        <?php if (!empty($ultimosPeriodos)): ?>
            <div class="modern-card" style="margin-top: 24px;">
                <div class="modern-card-header" style="padding: 14px 20px;">
                    <div class="modern-card-title-group">
                        <h4 style="font-size: 0.95rem; font-weight: 700; margin: 0; color: var(--text-main);">
                            Últimos Períodos Registrados en Base de Datos
                        </h4>
                    </div>
                    <a href="ReporteMovimientos.php" style="font-size: 0.82rem; font-weight: 600; color: var(--primary-600); text-decoration: none;">
                        Ver todos los lotes &rarr;
                    </a>
                </div>
                <div class="modern-card-body" style="padding: 14px 20px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        <?php foreach ($ultimosPeriodos as $up): ?>
                            <div class="recent-period-badge">
                                <span style="font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($up['Mes'] . " " . $up['Anio']); ?></span>
                                <span>(Carga #<?php echo htmlspecialchars($up['Archivo']); ?>)</span>
                                <span style="background: var(--primary-600); color: #fff; padding: 1px 7px; border-radius: var(--radius-full); font-size: 0.72rem; font-weight: 700;">
                                    <?php echo number_format($up['cantidad'], 0, ',', '.'); ?> movs
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <!-- PANTALLA DE AUDITORÍA Y PREVISUALIZACIÓN DE MOVIMIENTOS -->
    <?php else: ?>

        <!-- RESUMEN DE ARCHIVO ANALIZADO -->
        <div class="modern-card" style="margin-bottom: 24px;">
            <div class="modern-card-header" style="flex-wrap: wrap; gap: 14px;">
                <div class="modern-card-title-group">
                    <h3>Archivo Auditado: <?php echo htmlspecialchars($nombreArchivoMostrado); ?></h3>
                    <p>
                        Período seleccionado: <strong><?php echo htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado); ?> (Carga #<?php echo htmlspecialchars($archivoSeleccionado); ?>)</strong>
                        • Tamaño: <?php echo number_format($tamanoArchivo / 1024, 2, ',', '.'); ?> KB
                        <?php if (!empty($stats['fecha_min'])): ?>
                            • Rango de fechas: <strong><?php echo htmlspecialchars($stats['fecha_min'] . " a " . $stats['fecha_max']); ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <a href="ProcesarMovimientos.php" class="btn-modern-secondary" style="padding: 8px 16px; font-size: 0.84rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Cambiar Parámetros / Archivo
                    </a>
                </div>
            </div>

            <!-- ALERTA DE ESTADO DEL PERÍODO (DUPLICIDAD) -->
            <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-light);">
                <?php if ($stats['ya_procesado']): ?>
                    <div class="alert-modern warning" style="margin: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <div>
                            <strong>Período ya existente en base de datos:</strong> Ya existen <strong><?php echo number_format($stats['filas_existentes'], 0, ',', '.'); ?></strong> movimientos registrados para <strong><?php echo htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado); ?> (Carga #<?php echo htmlspecialchars($archivoSeleccionado); ?>)</strong>.<br>
                            Si confirma la importación, puede optar por <strong>sobrescribir y reemplazar</strong> los registros existentes para mantener la integridad contable.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert-modern success" style="margin: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <div>
                            <strong>Período libre para nueva importación:</strong> No se registran movimientos previos para <strong><?php echo htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado); ?> (Carga #<?php echo htmlspecialchars($archivoSeleccionado); ?>)</strong>. Puede proceder con la grabación directa.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TARJETAS DE MÉTRICAS KPI -->
            <div class="stats-kpi-mov" style="padding: 20px 24px 8px;">
                <div class="kpi-card">
                    <div class="kpi-label">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        Movimientos Totales
                    </div>
                    <div class="kpi-value"><?php echo number_format($stats['total_movimientos'], 0, ',', '.'); ?></div>
                    <div class="kpi-subtext"><?php echo count($stats['cuentas_unicas']); ?> cuentas involucradas</div>
                </div>

                <div class="kpi-card" style="border-left: 4px solid #9333ea;">
                    <div class="kpi-label">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        Consumos (Op. 491)
                    </div>
                    <div class="kpi-value" style="color: #9333ea;">
                        $ <?php echo number_format($stats['monto_consumos'], 2, ',', '.'); ?>
                    </div>
                    <div class="kpi-subtext"><?php echo number_format($stats['total_consumos'], 0, ',', '.'); ?> operaciones de compra</div>
                </div>

                <div class="kpi-card" style="border-left: 4px solid var(--emerald-600);">
                    <div class="kpi-label">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        Cargas / Descargas (671)
                    </div>
                    <div class="kpi-value" style="color: var(--emerald-600);">
                        $ <?php echo number_format($stats['monto_cargas'], 2, ',', '.'); ?>
                    </div>
                    <div class="kpi-subtext"><?php echo number_format($stats['total_cargas'], 0, ',', '.'); ?> movimientos de saldo</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Plásticos Únicos
                    </div>
                    <div class="kpi-value"><?php echo number_format(count($stats['tarjetas_unicas']), 0, ',', '.'); ?></div>
                    <div class="kpi-subtext"><?php echo number_format($stats['total_saldos'], 0, ',', '.'); ?> registros de saldos</div>
                </div>
            </div>

            <!-- BARRA DE ACCIÓN Y CONFIRMACIÓN -->
            <div style="padding: 16px 24px; background: var(--surface-subtle); border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div>
                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-main);">
                        ¿Confirma el almacenamiento de estos movimientos en la base de datos?
                    </span>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 2px 0 0;">
                        Los registros quedarán vinculados al período <?php echo htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado); ?> (Carga #<?php echo htmlspecialchars($archivoSeleccionado); ?>)
                    </p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="ProcesarMovimientos.php" class="btn-modern-secondary" style="padding: 9px 18px;">
                        Descartar
                    </a>
                    <?php if ($stats['ya_procesado']): ?>
                        <button type="button" class="btn-modern-primary" style="background: #d97706; border-color: #d97706; padding: 9px 20px;" onclick="abrirModalConfirmacion(true);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            Reemplazar Movimientos Existentes
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-modern-primary" style="padding: 9px 22px;" onclick="abrirModalConfirmacion(false);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Confirmar y Guardar Movimientos en BD
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TABLA DINÁMICA DE PREVISUALIZACIÓN DE MOVIMIENTOS -->
        <div class="modern-card">
            <div class="filter-toolbar">
                <div class="filter-pills">
                    <button type="button" class="filter-pill active" data-filter="all" onclick="filtrarTabla('all', this)">
                        Todos (<?php echo $stats['total_movimientos']; ?>)
                    </button>
                    <button type="button" class="filter-pill" data-filter="consumo" onclick="filtrarTabla('consumo', this)">
                        Consumos / Compras 491 (<?php echo $stats['total_consumos']; ?>)
                    </button>
                    <button type="button" class="filter-pill" data-filter="carga" onclick="filtrarTabla('carga', this)">
                        Cargas / Descargas 671 (<?php echo $stats['total_cargas']; ?>)
                    </button>
                    <button type="button" class="filter-pill" data-filter="saldo" onclick="filtrarTabla('saldo', this)">
                        Saldos Inicial/Final (<?php echo $stats['total_saldos']; ?>)
                    </button>
                </div>

                <div class="search-box-wrap">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="inputBuscarMov" class="search-input" placeholder="Buscar por titular, documento, tarjeta..." onkeyup="buscarEnTabla();">
                </div>
            </div>

            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 600px; overflow-y: auto;">
                    <table class="modern-table" id="tablaMovimientos">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>Fecha</th>
                                <th>Cuenta</th>
                                <th>Tarjeta Enmascarada</th>
                                <th>Documento</th>
                                <th>Titular</th>
                                <th>Operación</th>
                                <th>Comercio</th>
                                <th style="text-align: right;">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientosParseados as $m): ?>
                                <tr class="fila-movimiento" data-categoria="<?php echo $m['categoria']; ?>">
                                    <td style="color: var(--text-muted); font-size: 0.78rem;"><?php echo $m['nro']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($m['fecha']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($m['cuenta']); ?></code></td>
                                    <td>
                                        <code><?php echo htmlspecialchars(formatearTarjetaCredito($m['tarjeta'])); ?></code>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['documento']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($m['nombre']); ?></strong></td>
                                    <td>
                                        <?php if ($m['categoria'] === 'consumo'): ?>
                                            <span class="badge-op badge-op-consumo">
                                                🛒 <?php echo htmlspecialchars($m['codigo']); ?>
                                            </span>
                                        <?php elseif ($m['categoria'] === 'carga'): ?>
                                            <span class="badge-op badge-op-carga">
                                                💳 <?php echo htmlspecialchars($m['codigo']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-op badge-op-saldo">
                                                ⚖️ <?php echo htmlspecialchars($m['codigo']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['comercio']); ?></td>
                                    <td style="text-align: right; font-weight: 800; font-family: 'JetBrains Mono', monospace; color: <?php echo ($m['signo'] === '-' ? '#ef4444' : 'var(--emerald-600)'); ?>;">
                                        <?php echo $m['signo'] . " $" . number_format((float)$m['importe'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<!-- MODAL INTERACTIVO DE CONFIRMACIÓN EN DOS PASOS -->
<div class="modal-backdrop-custom" id="modalConfirmarGuardar">
    <div class="modal-card-custom">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 18px;">
            <div id="modalIconWrap" style="width: 48px; height: 48px; border-radius: 50%; background: rgba(37, 99, 235, 0.12); color: var(--primary-600); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div>
                <h3 id="modalTitulo" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main);">
                    Confirmar Grabación de Movimientos
                </h3>
                <p id="modalSubtitulo" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-muted);">
                    Se almacenarán los extractos del período seleccionado
                </p>
            </div>
        </div>

        <div style="background: var(--surface-subtle); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.86rem;">
                <span style="color: var(--text-muted);">Período a registrar:</span>
                <strong style="color: var(--text-main);"><?php echo htmlspecialchars($mesSeleccionado . " " . $anioSeleccionado . " (Carga #" . $archivoSeleccionado . ")"); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.86rem;">
                <span style="color: var(--text-muted);">Total de movimientos:</span>
                <strong style="color: var(--text-main);"><?php echo number_format($stats['total_movimientos'], 0, ',', '.'); ?> registros</strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.86rem;">
                <span style="color: var(--text-muted);">Total consumos comercio (491):</span>
                <strong style="color: #9333ea;">$ <?php echo number_format($stats['monto_consumos'], 2, ',', '.'); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.86rem;">
                <span style="color: var(--text-muted);">Total recargas de saldo (671):</span>
                <strong style="color: var(--emerald-600);">$ <?php echo number_format($stats['monto_cargas'], 2, ',', '.'); ?></strong>
            </div>
        </div>

        <div id="modalAvisoReemplazo" style="display: none; margin-bottom: 20px;">
            <div class="alert-modern warning" style="margin: 0; font-size: 0.84rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                <div>
                    <strong>Atención:</strong> Se eliminarán los <?php echo number_format($stats['filas_existentes'], 0, ',', '.'); ?> movimientos previos de este período y se insertarán los <?php echo number_format($stats['total_movimientos'], 0, ',', '.'); ?> nuevos registros del archivo.
                </div>
            </div>
        </div>

        <!-- FORMULARIO DE ENVÍO FINAL -->
        <form action="ProcesarMovimientos.php" method="post" id="formConfirmarGuardado">
            <input type="hidden" name="accion" value="guardar_movimientos">
            <input type="hidden" name="Mes" value="<?php echo htmlspecialchars($mesSeleccionado); ?>">
            <input type="hidden" name="Anio" value="<?php echo htmlspecialchars($anioSeleccionado); ?>">
            <input type="hidden" name="Archivo" value="<?php echo htmlspecialchars($archivoSeleccionado); ?>">
            <input type="hidden" name="reemplazar" id="inputReemplazar" value="0">
            <textarea name="datos_json" style="display: none;"><?php echo htmlspecialchars(json_encode($movimientosParseados)); ?></textarea>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn-modern-secondary" onclick="cerrarModalConfirmacion();">
                    Cancelar
                </button>
                <button type="submit" class="btn-modern-primary" id="btnEjecutarGuardado">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Confirmar e Insertar en BD
                </button>
            </div>
        </form>
    </div>
</div>

<a href="index.php" class="btn-modern-flotante" title="Volver al Tablero Principal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver al Tablero
</a>

<script>
// Manejadores interactivos de la zona Drag & Drop
(function() {
    var dropzone = document.getElementById('dropzoneMovimientos');
    var fileInput = document.getElementById('fileInputMovimientos');
    var form = document.getElementById('formSubirMovimientos');
    var normalState = document.getElementById('dropzoneNormalState');
    var loadingState = document.getElementById('dropzoneLoadingState');
    var loadingFileName = document.getElementById('loadingFileName');

    if (!dropzone || !fileInput || !form) return;

    // Evitar que el navegador abra el archivo al soltar fuera de la zona
    window.addEventListener('dragover', function(e) { e.preventDefault(); }, false);
    window.addEventListener('drop', function(e) { e.preventDefault(); }, false);

    dropzone.addEventListener('dragenter', function(e) {
        e.preventDefault();
        dropzone.classList.add('dragover');
    }, false);

    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropzone.classList.add('dragover');
    }, false);

    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');
    }, false);

    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('dragover');

        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            activarEstadoCargando(e.dataTransfer.files[0].name);
            form.submit();
        }
    }, false);

    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files.length > 0) {
            activarEstadoCargando(fileInput.files[0].name);
            form.submit();
        }
    });

    function activarEstadoCargando(nombre) {
        if (normalState) normalState.style.display = 'none';
        if (loadingState) {
            loadingState.style.display = 'block';
            if (loadingFileName) loadingFileName.textContent = 'Analizando ' + nombre + '...';
        }
    }
})();

// Sincronizar selectores con el botón de procesar servidor
function sincronizarParametrosServidor() {
    var sMes = document.getElementById('selectMes');
    var sAnio = document.getElementById('selectAnio');
    var sArchivo = document.getElementById('selectArchivo');
    var hMes = document.getElementById('hiddenMesServidor');
    var hAnio = document.getElementById('hiddenAnioServidor');
    var hArchivo = document.getElementById('hiddenArchivoServidor');

    if (sMes && hMes) hMes.value = sMes.value;
    if (sAnio && hAnio) hAnio.value = sAnio.value;
    if (sArchivo && hArchivo) hArchivo.value = sArchivo.value;
}

// Filtros por categoría de operación en la tabla
function filtrarTabla(categoria, btn) {
    var pills = document.querySelectorAll('.filter-pill');
    for (var i = 0; i < pills.length; i++) {
        pills[i].classList.remove('active');
    }
    if (btn) btn.classList.add('active');

    var filas = document.querySelectorAll('.fila-movimiento');
    for (var j = 0; j < filas.length; j++) {
        var fila = filas[j];
        if (categoria === 'all' || fila.getAttribute('data-categoria') === categoria) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    }
}

// Buscador dinámico en tiempo real
function buscarEnTabla() {
    var q = document.getElementById('inputBuscarMov').value.toLowerCase();
    var filas = document.querySelectorAll('.fila-movimiento');

    for (var i = 0; i < filas.length; i++) {
        var textoFila = filas[i].textContent.toLowerCase();
        if (textoFila.indexOf(q) !== -1) {
            filas[i].style.display = '';
        } else {
            filas[i].style.display = 'none';
        }
    }
}

// Control del Modal de Confirmación
function abrirModalConfirmacion(esReemplazo) {
    var modal = document.getElementById('modalConfirmarGuardar');
    var inputReemplazar = document.getElementById('inputReemplazar');
    var avisoReemplazo = document.getElementById('modalAvisoReemplazo');
    var titulo = document.getElementById('modalTitulo');
    var btn = document.getElementById('btnEjecutarGuardado');
    var iconWrap = document.getElementById('modalIconWrap');

    if (esReemplazo) {
        inputReemplazar.value = '1';
        avisoReemplazo.style.display = 'block';
        titulo.textContent = 'Confirmar Reemplazo de Movimientos';
        btn.textContent = 'Reemplazar y Guardar en BD';
        btn.style.background = '#d97706';
        btn.style.borderColor = '#d97706';
        if (iconWrap) {
            iconWrap.style.background = 'rgba(217, 119, 6, 0.15)';
            iconWrap.style.color = '#d97706';
        }
    } else {
        inputReemplazar.value = '0';
        avisoReemplazo.style.display = 'none';
        titulo.textContent = 'Confirmar Grabación de Movimientos';
        btn.textContent = 'Confirmar e Insertar en BD';
        btn.style.background = '';
        btn.style.borderColor = '';
        if (iconWrap) {
            iconWrap.style.background = 'rgba(37, 99, 235, 0.12)';
            iconWrap.style.color = 'var(--primary-600)';
        }
    }

    modal.style.display = 'flex';
}

function cerrarModalConfirmacion() {
    var modal = document.getElementById('modalConfirmarGuardar');
    if (modal) modal.style.display = 'none';
}
</script>

</body>
</html>
