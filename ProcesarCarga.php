<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Entidad 782 - Cabal
 * 
 * Módulo: Verificar Respuesta Carga
 * Archivo fuente: archivos/RespuestaCargas.txt (Devolución RCAR / Anexo 3 Cabal)
 * 
 * Especificación de Validación: docs/rcar.md (Anexo 3 - Formato Fijo 80 Bytes)
 * Diccionario de Errores: docs/Cabal.md (Anexo 11 - Matriz de Errores RCAR)
 */

require_once("BD/UsarGestion.php");
require_once("BD/Funciones.php");
require_once("BD/BD.php");

$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0775, true);
}

$archivoAuditado = false;
$queArchivo = "";
$nombreArchivoMostrado = "Ningún archivo seleccionado";
$tamanoArchivo = 0;
$fechaModificacion = "";
$mensajeUpload = "";
$tipoUpload = "";

// 1. Descarga directa del archivo auditado si se solicita por GET
if (isset($_GET['descargar']) && $_GET['descargar'] == '1') {
    $archivoDescarga = isset($_GET['archivo']) ? basename($_GET['archivo']) : 'RespuestaCargas.txt';
    $rutaDescarga = $targetDir . $archivoDescarga;
    if (file_exists($rutaDescarga)) {
        header('Content-Type: text/plain; charset=iso-8859-1');
        header('Content-Disposition: attachment; filename="' . $archivoDescarga . '"');
        header('Content-Length: ' . filesize($rutaDescarga));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($rutaDescarga);
        exit;
    }
}

// 2. Procesamiento de subida directa de archivo vía POST (Exclusivo en el adjunto superior)
if (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["archivo_respuesta"])) {
    $file = $_FILES["archivo_respuesta"];
    if ($file["error"] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if ($ext === "txt" || $ext === "782" || $ext === "dat") {
            $nombreOriginal = basename($file["name"]);
            $fileSize = $file["size"];
            if ($fileSize <= 0) {
                $mensajeUpload = "El archivo adjuntado está vacío (0 bytes).";
                $tipoUpload = "danger";
            } else {
                $destino = $targetDir . "RespuestaCargas_verificar.txt";
                if (move_uploaded_file($file["tmp_name"], $destino)) {
                    @chmod($destino, 0664);
                    $queArchivo = $destino;
                    $archivoAuditado = true;
                    $nombreArchivoMostrado = $nombreOriginal;
                    $tamanoArchivo = filesize($destino);
                    $fechaModificacion = date("d/m/Y H:i:s", filemtime($destino));
                    $mensajeUpload = "Archivo <strong>" . htmlspecialchars($nombreOriginal, ENT_QUOTES, 'UTF-8') . "</strong> recibido y auditado con éxito.";
                    $tipoUpload = "success";
                } else {
                    $mensajeUpload = "Error al mover el archivo subido a la carpeta de destino. Verifique permisos en <code>archivos/</code>.";
                    $tipoUpload = "danger";
                }
            }
        } else {
            $mensajeUpload = "Extensión no permitida (.{$ext}). Por motivos de compatibilidad bancaria solo se admiten archivos <strong>.txt</strong>, <strong>.782</strong> o <strong>.dat</strong>.";
            $tipoUpload = "danger";
        }
    } elseif ($file["error"] !== UPLOAD_ERR_NO_FILE) {
        $mensajeUpload = "Ocurrió un error al subir el archivo (Código de error: " . intval($file["error"]) . ").";
        $tipoUpload = "danger";
    }
}


// 3. Diccionario oficial de códigos de respuesta bancaria Cabal (Anexo 11)
$codigosAnexo11 = array(
    '000' => array('tipo' => 'success', 'titulo' => 'Acreditación Exitosa / Aprobada', 'desc' => 'Crédito aplicado correctamente en la cuenta del beneficiario.'),
    '001' => array('tipo' => 'danger',  'titulo' => 'Falta Header y Trailer', 'desc' => 'El archivo carece de estructuras de control iniciales o de cierre.'),
    '002' => array('tipo' => 'danger',  'titulo' => 'Tarjeta/Cuenta inexistente', 'desc' => 'El número de cuenta ingresado no existe en los maestros de Cabal.'),
    '003' => array('tipo' => 'danger',  'titulo' => 'Operación diferente de 62/63', 'desc' => 'La línea posee una operación prohibida para este canal.'),
    '004' => array('tipo' => 'danger',  'titulo' => 'Importe de Compra erróneo', 'desc' => 'El formato numérico posee caracteres alfabéticos o longitud inválida.'),
    '005' => array('tipo' => 'danger',  'titulo' => 'Importe de Retiros erróneo', 'desc' => 'El formato decimal de extracción en cajeros es inválido.'),
    '006' => array('tipo' => 'warning', 'titulo' => 'Desalineación de Operación', 'desc' => 'El código de operación difiere del tipo declarado en la cabecera.'),
    '007' => array('tipo' => 'warning', 'titulo' => 'Subcódigo erróneo', 'desc' => 'No se utilizó la constante 39 para cargas/descargas ordinarias.'),
    '008' => array('tipo' => 'danger',  'titulo' => 'Inconsistencia de Estado (Bloqueo)', 'desc' => 'La tarjeta se encuentra inactiva o bloqueada en Cabal.'),
    '009' => array('tipo' => 'danger',  'titulo' => 'Importe mayor al disponible', 'desc' => 'Rechazo por saldo insuficiente para cubrir la operación.'),
    '010' => array('tipo' => 'danger',  'titulo' => 'Motivo de carga mal informado', 'desc' => 'Estructura incorrecta en los parámetros de justificación.'),
    '011' => array('tipo' => 'danger',  'titulo' => 'Número de carga mal informado', 'desc' => 'El secuencial de carga está duplicado o mal estructurado.'),
    '012' => array('tipo' => 'danger',  'titulo' => 'Consulte a Cabal', 'desc' => 'Error genérico crítico de base de datos en el Host central.'),
    '017' => array('tipo' => 'warning', 'titulo' => 'Entidades diferentes', 'desc' => 'Conflicto de clearing: el código de banco no coincide.'),
    '099' => array('tipo' => 'danger',  'titulo' => 'Rechazo General Cabal', 'desc' => 'Rechazo operativo no clasificado por la entidad.')
);

// 4. Precarga del padrón institucional de beneficiarios en memoria (O(1) lookup)
$cuentasPadron = array();
try {
    if (isset($conn) && $conn instanceof PDO) {
        $stmtB = $conn->query("SELECT NumeroCuenta, Apellido, Nombre, NumeroDocumento, Cuit FROM beneficiario WHERE NumeroCuenta IS NOT NULL AND NumeroCuenta != ''");
        if ($stmtB) {
            while ($b = $stmtB->fetch(PDO::FETCH_ASSOC)) {
                $cta = trim($b['NumeroCuenta']);
                if ($cta !== '') {
                    $cuentasPadron[$cta] = $b;
                }
            }
        }
    }
} catch (Exception $e) {
    // Si falla la consulta masiva, se recurrirá a la función individual
}

// 5. Lectura y validación estructural exhaustiva contra docs/rcar.md
$archivoExiste = $archivoAuditado && !empty($queArchivo) && file_exists($queArchivo) && is_readable($queArchivo) && filesize($queArchivo) > 0;
$tamanoArchivo = $archivoExiste ? filesize($queArchivo) : 0;
$fechaModificacion = $archivoExiste ? date("d/m/Y H:i:s", filemtime($queArchivo)) : "";

// Variables de auditoría técnica RCAR
$erroresEstructurales = array();
$advertencias = array();
$headerData = null;
$trailerData = null;
$filasDetalle = array();

$statsRCAR = array(
    'total_lineas'          => 0,
    'lineas_detalle'        => 0,
    'lineas_80_ok'          => 0,
    'lineas_80_error'       => 0,
    'lineas_te04_ok'        => 0,
    'lineas_te04_error'     => 0,
    'cant_aprobados'        => 0,
    'cant_rechazados'       => 0,
    'total_compras_calc'    => 0.0,
    'total_cajeros_calc'    => 0.0,
    'cuentas_en_padron'     => 0,
    'cuentas_no_encontradas'=> 0,
    'descuadre_cantidad'    => false,
    'descuadre_monto'       => false,
    'es_crlf'               => false,
    'lote_conforme'         => false
);

if ($archivoExiste) {
    $contenidoCrudo = file_get_contents($queArchivo);
    $statsRCAR['es_crlf'] = (strpos($contenidoCrudo, "\r\n") !== false);

    $lineas = preg_split("/\r\n|\n|\r/", $contenidoCrudo);
    $numLinea = 0;

    foreach ($lineas as $line) {
        // Ignorar líneas vacías al final del archivo
        if ($line === '') {
            continue;
        }

        $numLinea++;
        $statsRCAR['total_lineas']++;
        $len = strlen($line);

        // Validación 1: Longitud exacta de 80 bytes (Anexo 3)
        if ($len === 80) {
            $statsRCAR['lineas_80_ok']++;
        } else {
            $statsRCAR['lineas_80_error']++;
            $erroresEstructurales[] = "Línea #{$numLinea}: Longitud incorrecta ({$len} bytes). La norma RCAR (Anexo 3) exige estrictamente 80 bytes.";
        }

        // Validación 2: Prefijo identificador "TE04" (Bytes 1-4)
        $prefijo = substr($line, 0, 4);
        if ($prefijo === "TE04") {
            $statsRCAR['lineas_te04_ok']++;
        } else {
            $statsRCAR['lineas_te04_error']++;
            $erroresEstructurales[] = "Línea #{$numLinea}: Prefijo inválido ('{$prefijo}'). Debe iniciar obligatoriamente con 'TE04'.";
        }

        // Clasificación de Registro según campo Cuenta (Bytes 11-26, offset 10, len 16)
        $campoCuentaControl = substr($line, 10, 16);

        // A. REGISTRO HEADER (Bytes 11-26 son 16 ceros)
        if ($campoCuentaControl === "0000000000000000" || ($numLinea === 1 && !is_numeric(trim($campoCuentaControl)))) {
            $entidadHeader = substr($line, 4, 3);
            $sucursalHeader = substr($line, 7, 3);
            $fRaw = substr($line, 26, 6); // DDMMAA
            $hRaw = substr($line, 32, 6); // HHMMSS
            $codErrHeader = trim(substr($line, 77, 3));
            if ($codErrHeader === '') $codErrHeader = '000';

            $fFmt = (strlen($fRaw) === 6) ? substr($fRaw, 0, 2) . '/' . substr($fRaw, 2, 2) . '/20' . substr($fRaw, 4, 2) : $fRaw;
            $hFmt = (strlen($hRaw) === 6) ? substr($hRaw, 0, 2) . ':' . substr($hRaw, 2, 2) . ':' . substr($hRaw, 4, 2) : $hRaw;

            $headerData = array(
                'linea'        => $numLinea,
                'entidad'      => $entidadHeader,
                'sucursal'     => $sucursalHeader,
                'fecha_fmt'    => $fFmt,
                'hora_fmt'     => $hFmt,
                'error_codigo' => $codErrHeader
            );

            if ($codErrHeader !== '000') {
                $erroresEstructurales[] = "Header (Línea #{$numLinea}): Cabal devolvió código de error '{$codErrHeader}' en la cabecera.";
            }
            continue;
        }

        // B. REGISTRO TRAILER (Bytes 11-26 son 16 nueves)
        if ($campoCuentaControl === "9999999999999999" || strpos($campoCuentaControl, "999999") === 0) {
            $cantEnviada = intval(substr($line, 26, 5));
            $sumComprasEnv = floatval(substr($line, 31, 8) . '.' . substr($line, 39, 2));
            $sumCajerosEnv = floatval(substr($line, 41, 8) . '.' . substr($line, 49, 2));
            $cantCalculada = intval(substr($line, 51, 5));
            $sumComprasCalc = floatval(substr($line, 56, 8) . '.' . substr($line, 64, 2));
            $codErrTrailer = trim(substr($line, 77, 3));
            if ($codErrTrailer === '') $codErrTrailer = '000';

            $trailerData = array(
                'linea'               => $numLinea,
                'entidad'             => substr($line, 4, 3),
                'sucursal'            => substr($line, 7, 3),
                'cant_enviada'        => $cantEnviada,
                'sum_compras_enviada' => $sumComprasEnv,
                'sum_cajeros_enviada' => $sumCajerosEnv,
                'cant_calculada'      => $cantCalculada,
                'sum_compras_calc'    => $sumComprasCalc,
                'error_codigo'        => $codErrTrailer
            );

            if ($codErrTrailer !== '000') {
                $erroresEstructurales[] = "Trailer (Línea #{$numLinea}): Cabal devolvió código de error '{$codErrTrailer}' en el cierre.";
            }
            continue;
        }

        // C. REGISTRO DETALLE (Bytes 11-26 contienen Número de Cuenta)
        $cuenta = trim($campoCuentaControl);
        $entidadDetalle = substr($line, 4, 6);
        $codOp = substr($line, 26, 2);      // Debe ser 63 (Crédito)
        $subCodOp = substr($line, 28, 2);   // Debe ser 39
        $impCompras = floatval(substr($line, 30, 7) . '.' . substr($line, 37, 2));
        $impCajeros = floatval(substr($line, 39, 7) . '.' . substr($line, 46, 2));
        $motivoCarga = substr($line, 48, 6);
        $nroCarga = intval(substr($line, 54, 8));
        $codError = trim(substr($line, 77, 3));
        if ($codError === '') {
            $codError = '000';
        }

        // Validación de operación crédito 6339 según docs/rcar.md
        if ($codOp !== '63') {
            $erroresEstructurales[] = "Línea #{$numLinea}: Código de operación inválido ('{$codOp}'). En RCAR debe ser '63' (Crédito).";
        }
        if ($subCodOp !== '39') {
            $erroresEstructurales[] = "Línea #{$numLinea}: Subcódigo inválido ('{$subCodOp}'). Debe cumplimentarse con '39'.";
        }

        // Cruce con Padrón institucional
        $nombre = '(No encontrado en padrón)';
        $dni = '-';
        $enPadron = false;

        if (isset($cuentasPadron[$cuenta])) {
            $b = $cuentasPadron[$cuenta];
            $nombre = trim($b['Apellido'] . ' ' . $b['Nombre']);
            $dni = trim($b['NumeroDocumento']);
            $enPadron = true;
            $statsRCAR['cuentas_en_padron']++;
        } else {
            $quienOld = function_exists('DevuelvoApellidoyNombrePorNumeroCuenta') ? DevuelvoApellidoyNombrePorNumeroCuenta($cuenta) : null;
            if (!empty($quienOld)) {
                $nombre = $quienOld;
                $enPadron = true;
                $statsRCAR['cuentas_en_padron']++;
            } else {
                $statsRCAR['cuentas_no_encontradas']++;
                $advertencias[] = "Línea #{$numLinea}: Cuenta '{$cuenta}' no encontrada en la base de datos institucional.";
            }
        }

        $esAprobado = ($codError === '000');
        if ($esAprobado) {
            $statsRCAR['cant_aprobados']++;
        } else {
            $statsRCAR['cant_rechazados']++;
        }

        $infoError = isset($codigosAnexo11[$codError]) ? $codigosAnexo11[$codError] : array(
            'tipo'   => 'danger',
            'titulo' => 'Error Cabal Código ' . $codError,
            'desc'   => 'Código de respuesta retornado por el host central de Cabal.'
        );

        $filasDetalle[] = array(
            'linea'        => $numLinea,
            'cuenta'       => $cuenta,
            'dni'          => $dni,
            'nombre'       => $nombre,
            'en_padron'    => $enPadron,
            'cod_op'       => $codOp,
            'sub_cod_op'   => $subCodOp,
            'importe'      => $impCompras,
            'imp_cajeros'  => $impCajeros,
            'motivo'       => $motivoCarga,
            'nro_carga'    => $nroCarga,
            'error'        => $codError,
            'error_titulo' => $infoError['titulo'],
            'error_desc'   => $infoError['desc'],
            'error_status' => $infoError['tipo'],
            'es_ok'        => $esAprobado
        );

        $statsRCAR['lineas_detalle']++;
        $statsRCAR['total_compras_calc'] += $impCompras;
        $statsRCAR['total_cajeros_calc'] += $impCajeros;
    }

    // Validación 3: Existencia de Header y Trailer obligatorios
    if (!$headerData) {
        $erroresEstructurales[] = "Falta Registro Header: El archivo no contiene la cabecera TE04 con 16 ceros en posición de cuenta.";
    }
    if (!$trailerData) {
        $erroresEstructurales[] = "Falta Registro Trailer: El archivo no contiene el registro de cierre TE04 con 16 nueves.";
    }

    // Validación 4: Conciliación y Cuadre de Totales con Trailer
    if ($trailerData) {
        // Cuadre de cantidad de registros
        $cantEsperada = ($trailerData['cant_enviada'] > 0) ? $trailerData['cant_enviada'] : $trailerData['cant_calculada'];
        if ($cantEsperada > 0 && $cantEsperada !== $statsRCAR['lineas_detalle']) {
            $statsRCAR['descuadre_cantidad'] = true;
            $erroresEstructurales[] = "Descuadre en Cantidad: El Trailer declara {$cantEsperada} registros de detalle, pero se contabilizaron {$statsRCAR['lineas_detalle']} registros reales.";
        }

        // Cuadre de sumatoria de compras
        $sumEsperada = ($trailerData['sum_compras_enviada'] > 0) ? $trailerData['sum_compras_enviada'] : $trailerData['sum_compras_calc'];
        $diffMonto = abs($statsRCAR['total_compras_calc'] - $sumEsperada);
        if ($sumEsperada > 0 && $diffMonto > 0.01) {
            $statsRCAR['descuadre_monto'] = true;
            $erroresEstructurales[] = "Descuadre en Sumatoria de Compras: El Trailer declara $ " . number_format($sumEsperada, 2, ',', '.') . " pero la sumatoria real calculada es $ " . number_format($statsRCAR['total_compras_calc'], 2, ',', '.') . " (Diferencia: $ " . number_format($diffMonto, 2, ',', '.') . ").";
        }
    }

    // Dictamen Final de Conformidad según docs/rcar.md
    $statsRCAR['lote_conforme'] = ($headerData !== null && $trailerData !== null && empty($erroresEstructurales) && $statsRCAR['lineas_80_error'] === 0 && $statsRCAR['lineas_te04_error'] === 0 && !$statsRCAR['descuadre_cantidad'] && !$statsRCAR['descuadre_monto']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Respuesta Carga | Sistema Cabal FSR</title>
    <meta name="description" content="Auditoría y conciliación técnica del archivo RespuestaCargas.txt (RCAR 80 bytes) según especificación Cabal docs/rcar.md">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .meta-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .meta-stat-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .meta-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .meta-stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .meta-stat-val {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--navy-800);
            line-height: 1.2;
        }

        .verdict-card {
            border-radius: var(--radius-xl);
            padding: 24px 28px;
            margin-bottom: 24px;
            border: 2px solid;
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .verdict-card.ok {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, var(--surface-card) 100%);
            border-color: #10b981;
        }
        .verdict-card.bad {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, var(--surface-card) 100%);
            border-color: #ef4444;
        }

        .verdict-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
            color: #ffffff;
        }
        .verdict-icon.ok {
            background: #059669;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }
        .verdict-icon.bad {
            background: #dc2626;
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35);
        }

        .checklist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 0.84rem;
        }

        .check-badge-state {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: bold;
            color: #fff;
            flex-shrink: 0;
        }
        .check-badge-state.ok { background: #059669; }
        .check-badge-state.bad { background: #dc2626; }

        .dropzone-respuesta {
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-xl);
            padding: 30px 24px;
            text-align: center;
            background: var(--surface-card);
            transition: all var(--transition-fast);
            cursor: pointer;
            margin-bottom: 20px;
        }

        .dropzone-respuesta:hover,
        .dropzone-respuesta.dragover {
            border-color: var(--primary-600);
            background: rgba(37, 99, 235, 0.04);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .dropzone-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-600);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: transform var(--transition-fast);
        }

        .dropzone-respuesta:hover .dropzone-icon-wrap {
            transform: scale(1.08);
            background: var(--primary-600);
            color: #ffffff;
        }

        .filter-pill-btn {
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid var(--border-light);
            background: var(--surface-card);
            color: var(--text-muted);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-pill-btn:hover {
            border-color: var(--primary-500);
            color: var(--primary-600);
        }

        .filter-pill-btn.active {
            background: var(--primary-600);
            color: #ffffff;
            border-color: var(--primary-600);
            box-shadow: var(--shadow-sm);
        }

        .account-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--navy-800);
            background: var(--surface-subtle);
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-light);
            letter-spacing: 0.04em;
        }

        .op-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 700;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-600);
            padding: 2px 6px;
            border-radius: var(--radius-sm);
        }

        .dni-text {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.84rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            background: var(--card-header-bg);
        }

        .search-box-wrap {
            position: relative;
            min-width: 280px;
            flex: 1;
            max-width: 420px;
        }

        .search-box-wrap svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .search-box-input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            font-size: 0.86rem;
            border: 1px solid var(--input-border);
            border-radius: var(--radius-md);
            background: var(--input-bg);
            color: var(--text-main);
            outline: none;
            transition: border-color var(--transition-fast);
        }

        .search-box-input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .metadata-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .metadata-box {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            box-shadow: var(--shadow-sm);
        }

        .metadata-box-title {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--navy-800);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .metadata-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.84rem;
            padding: 4px 0;
            border-bottom: 1px dashed var(--border-light);
        }
        .metadata-row:last-child {
            border-bottom: none;
        }

        @media print {
            .top-nav,
            .dropzone-card,
            .table-toolbar,
            .btn-modern-flotante,
            .nav-actions {
                display: none !important;
            }
            .page-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .modern-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }
            .modern-table-responsive {
                max-height: none !important;
                overflow: visible !important;
            }
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

            <a href="GenerarCargaCredito2026.php" class="btn-nav-action" id="btn-lote-novcar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Generar NOVCAR
            </a>

            <a href="SubirArchivo.html" class="btn-nav-action" id="btn-subir">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Subir Archivos
            </a>

            <button type="button" class="btn-nav-action" onclick="toggleCatalogoErroresCarga()" id="btn-toggle-catalogo-carga">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                Ver Catálogo Errores Anexo 11
            </button>

            <button type="button" class="btn-nav-action" id="btn-theme-toggle" onclick="toggleTheme()" title="Alternar tema oscuro / claro">
                🌓
            </button>
        </div>
    </div>
</header>

<main class="page-container" style="margin-top: 28px;">

    <!-- HERO HEADER -->
    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                Verificar Respuesta de Cargas (RCAR)
            </h2>
            <p>
                Auditoría y conciliación técnica del archivo bancario emitido por Cabal (<code>RespuestaCargas.txt</code> / lote <code>782_RCAR</code>).
                Validación estricta de estructura posicional de 80 bytes según <strong>docs/rcar.md</strong> (Anexo 3) y matriz de errores <strong>Anexo 11 (docs/Cabal.md)</strong>.
            </p>
        </div>
    </section>

    <!-- PANEL EXPANDIBLE CON EL DICCIONARIO OFICIAL DE ERRORES DE CARGA (ANEXO 11) -->
    <div id="panelCatalogoErroresCarga" class="modern-card" style="display: none; margin-bottom: 24px; border: 2px solid var(--primary-600); background: var(--surface-card);">
        <div class="modern-card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); background: rgba(37, 99, 235, 0.05); display: flex; justify-content: space-between; align-items: center;">
            <div class="modern-card-title-group">
                <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--navy-800);">
                    📖 Diccionario de Errores en Operaciones de Carga y Descarga — Anexo 11 (docs/Cabal.md)
                </h4>
                <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: var(--text-muted);">
                    Códigos de respuesta y rechazo emitidos por el Host Central de Cabal (posiciones 78-80 del registro).
                </p>
            </div>
            <button type="button" onclick="toggleCatalogoErroresCarga()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>
        <div style="padding: 16px 20px; max-height: 380px; overflow-y: auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 10px;">
                <?php foreach (obtenerDiccionarioErroresCargasCabal() as $cErr => $itemErr): ?>
                    <div style="padding: 8px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); background: var(--surface-card);">
                        <div style="display: flex; gap: 8px; align-items: baseline; margin-bottom: 2px;">
                            <span style="font-weight: 800; color: <?php echo $itemErr['tipo'] === 'success' ? '#059669' : ($itemErr['tipo'] === 'warning' ? '#d97706' : '#dc2626'); ?>; font-size: 0.82rem; font-family: monospace;">
                                [<?php echo $itemErr['codigo']; ?> <?php echo isset($itemErr['segmento']) ? $itemErr['segmento'] : ''; ?>]
                            </span>
                            <strong style="font-size: 0.82rem; color: var(--navy-800);">
                                <?php echo htmlspecialchars($itemErr['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                            </strong>
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.25;">
                            <?php echo htmlspecialchars($itemErr['desc'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- MENSAJES DE ALERTA DE SUBIDA -->
    <?php if (!empty($mensajeUpload)): ?>
        <div class="alert-modern <?php echo htmlspecialchars($tipoUpload); ?>" style="margin-bottom: 24px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <?php if ($tipoUpload === 'success'): ?>
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>
                <?php else: ?>
                    <circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>
                <?php endif; ?>
            </svg>
            <div><?php echo $mensajeUpload; ?></div>
        </div>
    <?php endif; ?>

    <!-- SECCIÓN DE SUBIDA Y ORIGEN DEL ARCHIVO (DROPZONE) -->
    <div class="modern-card dropzone-card" style="margin-bottom: 24px;">
        <div class="modern-card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <div class="modern-card-title-group">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                    📁 Adjuntar o Arrastrar Archivo de Respuesta (782_RCAR / RespuestaCargas.txt)
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-muted); margin: 0;">
                    Audite el lote devuelto por la plataforma Cabal para certificar el cumplimiento de 80 bytes fijos y cuadre de totales.
                </p>
            </div>
        </div>

        <div style="padding: 20px;">
            <form action="ProcesarCarga.php" method="POST" enctype="multipart/form-data" id="formUploadRespuesta">
                <div class="dropzone-respuesta" id="dropzoneRespuesta" onclick="document.getElementById('fileInputRespuesta').click();">
                    <input type="file" name="archivo_respuesta" id="fileInputRespuesta" accept=".txt,.782,.dat" style="display:none;" onchange="document.getElementById('formUploadRespuesta').submit();">
                    <div class="dropzone-icon-wrap">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: var(--navy-800); margin-bottom: 4px;">
                        Haga clic aquí o arrastre su archivo <code>RespuestaCargas.txt</code> para auditar
                    </div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Formato normado: <strong>80 bytes fijos</strong> &bull; Prefijo <strong>TE04</strong> &bull; Saltos <strong>Windows CRLF</strong>
                    </div>
                </div>
            </form>

            <?php if ($archivoAuditado): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 6px;">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Archivo auditado: <strong><?php echo htmlspecialchars($nombreArchivoMostrado, ENT_QUOTES, 'UTF-8'); ?></strong>
                        &bull; Tamaño: <strong><?php echo number_format($tamanoArchivo, 0, ',', '.'); ?> bytes</strong>
                        &bull; Procesado: <strong><?php echo htmlspecialchars($fechaModificacion); ?></strong>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <a href="ProcesarCarga.php" class="btn-modern-secondary" style="padding: 8px 14px; font-size: 0.84rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            Adjuntar Otro Archivo
                        </a>
                        <a href="ProcesarCarga.php?descargar=1&archivo=<?php echo urlencode(basename($queArchivo)); ?>" class="btn-modern-secondary" style="padding: 8px 14px; font-size: 0.84rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Descargar Archivo Auditado
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding-top: 6px; font-size: 0.84rem; color: var(--text-muted);">
                    ℹ️ Adjunte o arrastre el archivo en el recuadro superior para iniciar la auditoría de respuesta de cargas.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$archivoAuditado): ?>
        <!-- ESTADO ESPERANDO ARCHIVO -->
        <div class="alert-modern info" style="margin-bottom: 30px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div>
                <strong>Esperando archivo de respuesta para auditar:</strong><br>
                Por directiva estricta de validación del sistema, los datos a validar se deben adjuntar en el recuadro superior. <strong>No se validan archivos cargados previamente en el servidor.</strong> Arrastre o adjunte el archivo devuelto por Cabal (<code>782_RCAR</code> o <code>RespuestaCargas.txt</code>) para auditar 80 bytes fijos, consistencia TE04 y catálogo de errores Anexo 11.
            </div>
        </div>
    <?php elseif (!$archivoExiste): ?>
        <div class="alert-modern danger" style="margin-bottom: 30px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>El archivo adjuntado está vacío o no se pudo leer.</strong><br>
                Por favor verifique el archivo devuelto por Cabal y vuelva a adjuntarlo en el recuadro superior.
            </div>
        </div>
    <?php else: ?>

        <!-- DICTAMEN DE CONFORMIDAD SEGÚN docs/rcar.md -->
        <div class="verdict-card <?php echo $statsRCAR['lote_conforme'] ? 'ok' : 'bad'; ?>">
            <div class="verdict-icon <?php echo $statsRCAR['lote_conforme'] ? 'ok' : 'bad'; ?>">
                <?php echo $statsRCAR['lote_conforme'] ? '✓' : '✕'; ?>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 6px;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: <?php echo $statsRCAR['lote_conforme'] ? '#065f46' : '#991b1b'; ?>;">
                        <?php if ($statsRCAR['lote_conforme']): ?>
                            ✅ DICTAMEN: LOTE RCAR 100% VÁLIDO CONFORME A NORMA CABAL (ANEXO 3)
                        <?php else: ?>
                            ⛔ DICTAMEN: LOTE RCAR CON INCONSISTENCIAS / DESCUADRE ESTRUCTURAL
                        <?php endif; ?>
                    </h3>
                    <span style="font-size: 0.82rem; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-full); background: <?php echo $statsRCAR['lote_conforme'] ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; color: <?php echo $statsRCAR['lote_conforme'] ? '#059669' : '#dc2626'; ?>;">
                        <?php echo $statsRCAR['lote_conforme'] ? 'NORMATIVA docs/rcar.md CUMPLIDA' : count($erroresEstructurales) . ' ERROR(ES) DETECTADO(S)'; ?>
                    </span>
                </div>
                <p style="margin: 0 0 10px 0; font-size: 0.92rem; color: var(--text-main); line-height: 1.5;">
                    <?php if ($statsRCAR['lote_conforme']): ?>
                        El archivo cumple estrictamente con las especificaciones del lote de respuesta Cabal (RCAR): registros con ancho uniforme de 80 bytes, identificador "TE04", cabecera y cierre presentes, operación crédito 6339 y coincidencia exacta en cantidad e importes con el Trailer.
                    <?php else: ?>
                        Se detectaron discrepancias técnicas o contables con respecto al manual normativo de Cabal:
                    <?php endif; ?>
                </p>

                <?php if (!empty($erroresEstructurales)): ?>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 0.88rem; color: #b91c1c; font-weight: 600;">
                        <?php foreach ($erroresEstructurales as $err): ?>
                            <li style="margin-bottom: 4px;"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- MATRIZ DE VERIFICACIÓN DE 6 PUNTOS NORMATIVOS -->
                <div class="checklist-grid">
                    <div class="checklist-item">
                        <span class="check-badge-state <?php echo ($statsRCAR['lineas_80_error'] === 0) ? 'ok' : 'bad'; ?>">
                            <?php echo ($statsRCAR['lineas_80_error'] === 0) ? '✓' : '✕'; ?>
                        </span>
                        <div><strong>Longitud 80 Bytes:</strong> <?php echo $statsRCAR['lineas_80_ok']; ?> / <?php echo $statsRCAR['total_lineas']; ?> registros conformes</div>
                    </div>

                    <div class="checklist-item">
                        <span class="check-badge-state <?php echo ($statsRCAR['lineas_te04_error'] === 0) ? 'ok' : 'bad'; ?>">
                            <?php echo ($statsRCAR['lineas_te04_error'] === 0) ? '✓' : '✕'; ?>
                        </span>
                        <div><strong>Identificador TE04:</strong> <?php echo $statsRCAR['lineas_te04_ok']; ?> / <?php echo $statsRCAR['total_lineas']; ?> líneas con prefijo correcto</div>
                    </div>

                    <div class="checklist-item">
                        <span class="check-badge-state <?php echo $headerData ? 'ok' : 'bad'; ?>">
                            <?php echo $headerData ? '✓' : '✕'; ?>
                        </span>
                        <div><strong>Registro Header:</strong> <?php echo $headerData ? "Entidad {$headerData['entidad']} • Fecha {$headerData['fecha_fmt']}" : "Ausente / No detectado"; ?></div>
                    </div>

                    <div class="checklist-item">
                        <span class="check-badge-state <?php echo $trailerData ? 'ok' : 'bad'; ?>">
                            <?php echo $trailerData ? '✓' : '✕'; ?>
                        </span>
                        <div><strong>Registro Trailer:</strong> <?php echo $trailerData ? "Presente (16 nueves en cuenta)" : "Ausente / No detectado"; ?></div>
                    </div>

                    <div class="checklist-item">
                        <span class="check-badge-state <?php echo !$statsRCAR['descuadre_cantidad'] ? 'ok' : 'bad'; ?>">
                            <?php echo !$statsRCAR['descuadre_cantidad'] ? '✓' : '✕'; ?>
                        </span>
                        <div><strong>Cuadre de Registros:</strong> <?php echo $statsRCAR['lineas_detalle']; ?> detalles contabilizados</div>
                    </div>

                    <div class="checklist-item">
                        <span class="check-badge-state <?php echo !$statsRCAR['descuadre_monto'] ? 'ok' : 'bad'; ?>">
                            <?php echo !$statsRCAR['descuadre_monto'] ? '✓' : '✕'; ?>
                        </span>
                        <div><strong>Cuadre de Compras:</strong> $<?php echo number_format($statsRCAR['total_compras_calc'], 2, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- METADATA Y DETALLE DE HEADER Y TRAILER -->
        <div class="metadata-strip">
            <!-- FICHA TÉCNICA DEL HEADER -->
            <div class="metadata-box">
                <div class="metadata-box-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Cabecera de Devolución (Registro Header)
                </div>
                <?php if ($headerData): ?>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Entidad / Sucursal:</span>
                        <strong><?php echo htmlspecialchars($headerData['entidad']); ?> / <?php echo htmlspecialchars($headerData['sucursal']); ?></strong>
                    </div>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Fecha Proceso Original:</span>
                        <strong><?php echo htmlspecialchars($headerData['fecha_fmt']); ?></strong>
                    </div>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Hora Proceso Original:</span>
                        <strong><?php echo htmlspecialchars($headerData['hora_fmt']); ?></strong>
                    </div>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Retorno Cabecera:</span>
                        <?php if ($headerData['error_codigo'] === '000'): ?>
                            <span class="badge-tag success" style="padding: 1px 6px; font-size: 0.72rem;">OK (000)</span>
                        <?php else: ?>
                            <span class="badge-tag danger" style="padding: 1px 6px; font-size: 0.72rem;">Error <?php echo htmlspecialchars($headerData['error_codigo']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="color: #dc2626; font-size: 0.85rem;">No se detectó registro Header en el lote.</div>
                <?php endif; ?>
            </div>

            <!-- FICHA TÉCNICA DEL TRAILER -->
            <div class="metadata-box">
                <div class="metadata-box-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-600);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    Cierre Contable (Registro Trailer)
                </div>
                <?php if ($trailerData): ?>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Registros Declarados / Reales:</span>
                        <strong><?php echo $trailerData['cant_enviada']; ?> / <?php echo $statsRCAR['lineas_detalle']; ?></strong>
                    </div>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Sumatoria Declarada en Compras:</span>
                        <strong style="color: var(--emerald-600);">$<?php echo number_format($trailerData['sum_compras_enviada'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Sumatoria Real Calculada:</span>
                        <strong style="color: var(--emerald-600);">$<?php echo number_format($statsRCAR['total_compras_calc'], 2, ',', '.'); ?></strong>
                    </div>
                    <div class="metadata-row">
                        <span style="color: var(--text-muted);">Retorno Trailer:</span>
                        <?php if ($trailerData['error_codigo'] === '000'): ?>
                            <span class="badge-tag success" style="padding: 1px 6px; font-size: 0.72rem;">OK (000)</span>
                        <?php else: ?>
                            <span class="badge-tag danger" style="padding: 1px 6px; font-size: 0.72rem;">Error <?php echo htmlspecialchars($trailerData['error_codigo']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="color: #dc2626; font-size: 0.85rem;">No se detectó registro Trailer en el lote.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TARJETAS DE MÉTRICAS GENERALES -->
        <div class="meta-stats-grid">
            <div class="meta-stat-card">
                <div class="meta-stat-label">
                    <span>Cuentas Procesadas</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="meta-stat-val"><?php echo number_format($statsRCAR['lineas_detalle'], 0, ',', '.'); ?></div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 6px;">
                    Cuentas auditadas en el archivo
                </div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">
                    <span>Total Depositado Efectivo</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-600);"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
                <div class="meta-stat-val" style="color: var(--emerald-600);">$<?php echo number_format($statsRCAR['total_compras_calc'], 2, ',', '.'); ?></div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 6px;">
                    Sumatoria neta para compras (Pos. 31-39)
                </div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">
                    <span>Acreditaciones Exitosas</span>
                    <span class="badge-tag success" style="font-size: 0.72rem; padding: 2px 6px;">OK 000</span>
                </div>
                <div class="meta-stat-val" style="color: var(--emerald-600);"><?php echo number_format($statsRCAR['cant_aprobados'], 0, ',', '.'); ?></div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 6px;">
                    <?php echo $statsRCAR['lineas_detalle'] > 0 ? round(($statsRCAR['cant_aprobados'] / $statsRCAR['lineas_detalle']) * 100, 1) : 0; ?>% de efectividad de acreditación
                </div>
            </div>

            <div class="meta-stat-card">
                <div class="meta-stat-label">
                    <span>Rechazos / Con Error</span>
                    <?php if ($statsRCAR['cant_rechazados'] > 0): ?>
                        <span class="badge-tag danger" style="font-size: 0.72rem; padding: 2px 6px;">Observados</span>
                    <?php else: ?>
                        <span class="badge-tag success" style="font-size: 0.72rem; padding: 2px 6px;">0 Rechazos</span>
                    <?php endif; ?>
                </div>
                <div class="meta-stat-val" style="<?php echo $statsRCAR['cant_rechazados'] > 0 ? 'color: #dc2626;' : 'color: var(--emerald-600);'; ?>">
                    <?php echo number_format($statsRCAR['cant_rechazados'], 0, ',', '.'); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 6px;">
                    <?php echo $statsRCAR['cant_rechazados'] > 0 ? 'Cuentas con motivos de rechazo Anexo 11' : 'Todas las cuentas fueron acreditadas sin error'; ?>
                </div>
            </div>
        </div>

        <!-- CONTENEDOR PRINCIPAL DE LA TABLA Y HERRAMIENTAS -->
        <div class="modern-card">
            <!-- BARRA DE HERRAMIENTAS INTERACTIVA -->
            <div class="table-toolbar">
                <div class="search-box-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="filtroTexto" class="search-box-input" placeholder="Buscar por Nº Cuenta, DNI, Titular o Código de Error..." onkeyup="filtrarTabla();">
                </div>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="filter-pill-btn active" id="btnFiltroTodos" onclick="cambiarFiltroEstado('todos');">
                        Todos (<?php echo count($filasDetalle); ?>)
                    </button>
                    <button type="button" class="filter-pill-btn" id="btnFiltroOk" onclick="cambiarFiltroEstado('ok');">
                        ✓ Exitosos (<?php echo $statsRCAR['cant_aprobados']; ?>)
                    </button>
                    <button type="button" class="filter-pill-btn" id="btnFiltroError" onclick="cambiarFiltroEstado('error');">
                        ⚠ Con Rechazo (<?php echo $statsRCAR['cant_rechazados']; ?>)
                    </button>
                </div>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <button type="button" class="btn-modern-secondary" onclick="exportarCSV();" style="padding: 8px 14px; font-size: 0.84rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Exportar CSV
                    </button>
                    <button type="button" class="btn-modern-secondary" onclick="window.print();" style="padding: 8px 14px; font-size: 0.84rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Imprimir
                    </button>
                </div>
            </div>

            <!-- TABLA DE DETALLE AUDITADA SEGÚN docs/rcar.md -->
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 620px; overflow-y: auto;">
                    <table class="modern-table" id="tablaDetalleRespuesta">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th style="width: 175px;">Número de Cuenta (11-26)</th>
                                <th style="width: 120px;">DNI / Doc.</th>
                                <th>Titular Beneficiario (Padrón Institucional)</th>
                                <th style="width: 90px; text-align: center;">Op. / Sub.</th>
                                <th style="width: 155px; text-align: right;">Importe Compras</th>
                                <th style="width: 100px; text-align: center;">Nº Carga</th>
                                <th style="width: 240px;">Código y Dictamen (Anexo 11)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $idx = 1; foreach ($filasDetalle as $m): ?>
                            <tr class="fila-registro" data-estado="<?php echo $m['es_ok'] ? 'ok' : 'error'; ?>" data-cuenta="<?php echo htmlspecialchars($m['cuenta']); ?>" data-dni="<?php echo htmlspecialchars($m['dni']); ?>" data-nombre="<?php echo htmlspecialchars(mb_strtolower($m['nombre'], 'UTF-8')); ?>" data-error="<?php echo htmlspecialchars($m['error']); ?>">
                                <td style="text-align: center; color: var(--text-muted); font-size: 0.82rem; font-weight: 600;">
                                    <?php echo $idx++; ?>
                                </td>
                                <td>
                                    <span class="account-badge"><?php echo htmlspecialchars($m['cuenta']); ?></span>
                                </td>
                                <td>
                                    <span class="dni-text"><?php echo htmlspecialchars($m['dni']); ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--navy-800);">
                                        <?php echo htmlspecialchars($m['nombre']); ?>
                                    </div>
                                    <?php if (!$m['en_padron']): ?>
                                        <span style="font-size: 0.72rem; color: #d97706; display: inline-flex; align-items: center; gap: 4px;">
                                            ⚠ No coincide con el padrón local
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="op-badge"><?php echo htmlspecialchars($m['cod_op'] . $m['sub_cod_op']); ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.96rem; color: var(--emerald-600);">
                                    $<?php echo number_format($m['importe'], 2, ',', '.'); ?>
                                </td>
                                <td style="text-align: center; font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; color: var(--text-muted);">
                                    #<?php echo $m['nro_carga']; ?>
                                </td>
                                <td>
                                    <?php if ($m['es_ok']): ?>
                                        <span class="badge-tag success" title="<?php echo htmlspecialchars($m['error_desc']); ?>">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            OK (000) Acreditado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-tag <?php echo htmlspecialchars($m['error_status']); ?>" title="<?php echo htmlspecialchars($m['error_desc']); ?>">
                                            Error <?php echo htmlspecialchars($m['error']); ?> &bull; <?php echo htmlspecialchars($m['error_titulo']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="filaSinResultados" style="display: none;">
                                <td colspan="8" style="text-align: center; padding: 36px 20px; color: var(--text-muted);">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 8px; color: var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    <div style="font-weight: 600;">No se encontraron registros que coincidan con la búsqueda.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<a href="index.php" class="btn-modern-flotante" title="Volver al Tablero Principal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver al Tablero
</a>

<script>
var estadoFiltroActivo = 'todos';

// Filtro en vivo por texto y estado
function filtrarTabla() {
    var query = document.getElementById('filtroTexto').value.toLowerCase().trim();
    var filas = document.querySelectorAll('#tablaDetalleRespuesta tbody tr.fila-registro');
    var visibles = 0;

    for (var i = 0; i < filas.length; i++) {
        var fila = filas[i];
        var estado = fila.getAttribute('data-estado');
        var cuenta = (fila.getAttribute('data-cuenta') || '').toLowerCase();
        var dni = (fila.getAttribute('data-dni') || '').toLowerCase();
        var nombre = (fila.getAttribute('data-nombre') || '').toLowerCase();
        var errCod = (fila.getAttribute('data-error') || '').toLowerCase();

        var cumpleEstado = (estadoFiltroActivo === 'todos') || (estadoFiltroActivo === estado);
        var cumpleTexto = (query === '') || (cuenta.indexOf(query) !== -1) || (dni.indexOf(query) !== -1) || (nombre.indexOf(query) !== -1) || (errCod.indexOf(query) !== -1);

        if (cumpleEstado && cumpleTexto) {
            fila.style.display = '';
            visibles++;
        } else {
            fila.style.display = 'none';
        }
    }

    var filaVacia = document.getElementById('filaSinResultados');
    if (filaVacia) {
        filaVacia.style.display = (visibles === 0) ? '' : 'none';
    }
}

// Alternar filtro de estado (todos, ok, error)
function cambiarFiltroEstado(nuevoEstado) {
    estadoFiltroActivo = nuevoEstado;

    document.getElementById('btnFiltroTodos').classList.toggle('active', nuevoEstado === 'todos');
    document.getElementById('btnFiltroOk').classList.toggle('active', nuevoEstado === 'ok');
    document.getElementById('btnFiltroError').classList.toggle('active', nuevoEstado === 'error');

    filtrarTabla();
}

// Drag and drop para la zona de subida
(function() {
    var dropzone = document.getElementById('dropzoneRespuesta');
    var fileInput = document.getElementById('fileInputRespuesta');
    var form = document.getElementById('formUploadRespuesta');

    if (!dropzone || !fileInput || !form) return;

    ['dragenter', 'dragover'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(function(evt) {
        dropzone.addEventListener(evt, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            form.submit();
        }
    });
})();

// Exportar listado visible a CSV con compatibilidad Excel (BOM UTF-8)
function exportarCSV() {
    var filas = document.querySelectorAll('#tablaDetalleRespuesta tbody tr.fila-registro');
    if (filas.length === 0) {
        alert('No hay registros para exportar.');
        return;
    }

    var csvContent = '\uFEFF"Numero Cuenta";"DNI";"Titular Beneficiario";"Codigo Operacion";"Importe Compras";"Numero Carga";"Codigo Error";"Dictamen"\r\n';

    for (var i = 0; i < filas.length; i++) {
        var f = filas[i];
        if (f.style.display === 'none') continue; // Solo exportar filas visibles

        var cols = f.querySelectorAll('td');
        if (cols.length >= 8) {
            var cta = cols[1].innerText.trim();
            var dni = cols[2].innerText.trim();
            var nom = cols[3].innerText.replace(/[\r\n]+/g, ' ').trim();
            var op = cols[4].innerText.trim();
            var imp = cols[5].innerText.replace('$', '').trim();
            var carga = cols[6].innerText.replace('#', '').trim();
            var est = cols[7].innerText.replace(/[\r\n]+/g, ' ').trim();

            csvContent += '"' + cta + '";"' + dni + '";"' + nom.replace(/"/g, '""') + '";"' + op + '";"' + imp + '";"' + carga + '";"' + f.getAttribute('data-error') + '";"' + est.replace(/"/g, '""') + '"\r\n';
        }
    }

    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'RespuestaCargas_Auditado_RCAR.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Alternar visibilidad del catálogo oficial de errores Anexo 11
function toggleCatalogoErroresCarga() {
    var panel = document.getElementById('panelCatalogoErroresCarga');
    var btn = document.getElementById('btn-toggle-catalogo-carga');
    if (!panel) return;
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        if (btn) btn.classList.add('active');
    } else {
        panel.style.display = 'none';
        if (btn) btn.classList.remove('active');
    }
}
</script>

</body>
</html>