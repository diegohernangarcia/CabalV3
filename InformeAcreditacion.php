<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * 2.7 Informe de Acreditación de Saldos (Respuesta de Carga Cabal - RCAR 80 Bytes)
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Genera informe en HTML institucional detallando los resultados del archivo RespuestaCargas (782_RCAR) devuelto por Cabal.
 * 2. Informa fecha de proceso del lote bancario, entidad emisora (782), sucursal y totales monetarios acreditados.
 * 3. Lista beneficiarios con datos filiatorios, DNI, número de cuenta e importe acreditado.
 * 4. Aplica estrictamente enmascaramiento en el número de tarjeta (formatearTarjetaCredito()).
 * 5. Coteja la presencia del titular en la base de datos (tabla `beneficiario`).
 * 6. Genera y descarga archivo .EML estándar (MIME/RFC 822 con X-Unsent: 1) con cabecera institucional SeNAF listo para Thunderbird.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/auth/verificar_sesion.php");
requerirAutenticacion();

require_once(__DIR__ . "/BD/BD.php");
require_once(__DIR__ . "/BD/Funciones.php");
require_once(__DIR__ . "/BD/UsarGestion.php");

// Configuración de zona horaria y cabeceras anti-caché
date_default_timezone_set("America/Argentina/Buenos_Aires");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0777, true);
}

// -----------------------------------------------------------------------------
// FUNCIONES AUXILIARES DE FORMATEO Y PARSING
// -----------------------------------------------------------------------------

if (!function_exists('formatearFechaProcesoCabalRcar')) {
function formatearFechaProcesoCabalRcar($fec) {
    $fec = trim((string)$fec);
    if (strlen($fec) === 6 && is_numeric($fec)) {
        // Formato DDMMAA
        $dd = substr($fec, 0, 2);
        $mm = substr($fec, 2, 2);
        $aa = substr($fec, 4, 2);
        return $dd . '/' . $mm . '/20' . $aa;
    } elseif (strlen($fec) === 8 && is_numeric($fec)) {
        // Formato AAAAMMDD
        $aaaa = substr($fec, 0, 4);
        $mm = substr($fec, 4, 2);
        $dd = substr($fec, 6, 2);
        return $dd . '/' . $mm . '/' . $aaaa;
    }
    return !empty($fec) ? $fec : date('d/m/Y');
}
}

if (!function_exists('formatearHoraCabalRcar')) {
function formatearHoraCabalRcar($hora) {
    $hora = trim((string)$hora);
    if (strlen($hora) === 6 && is_numeric($hora)) {
        return substr($hora, 0, 2) . ':' . substr($hora, 2, 2) . ':' . substr($hora, 4, 2);
    }
    return !empty($hora) ? $hora : '';
}
}

if (!function_exists('formatearNumeroDocumento')) {
function formatearNumeroDocumento($dni) {
    $dniLimpio = ltrim(trim((string)$dni), '0');
    if (is_numeric($dniLimpio) && strlen($dniLimpio) >= 6) {
        return number_format((float)$dniLimpio, 0, '', '.');
    }
    return !empty($dniLimpio) ? $dniLimpio : trim((string)$dni);
}
}

if (!function_exists('formatearCuilCuit')) {
function formatearCuilCuit($cuit) {
    $cuitLimpio = preg_replace('/[^0-9]/', '', (string)$cuit);
    if (strlen($cuitLimpio) === 11) {
        return substr($cuitLimpio, 0, 2) . '-' . substr($cuitLimpio, 2, 8) . '-' . substr($cuitLimpio, 10, 1);
    }
    return (string)$cuit;
}
}

// Diccionario oficial Anexo 11 de Cabal (Matriz de Errores RCAR)
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

// -----------------------------------------------------------------------------
// LOCALIZACIÓN Y SELECCIÓN DEL ARCHIVO DE RESPUESTA CABAL RCAR
// -----------------------------------------------------------------------------

$mensajeFeedback = '';
$tipoFeedback = '';
$archivoSeleccionado = '';
$nombreArchivoMostrado = '';
$fechaArchivoSubida = '';
$tamanoArchivo = 0;

// Revisar metadatos si existen
$metaFile = $targetDir . "RespuestaCargas_meta.json";
$metadata = array();
if (file_exists($metaFile)) {
    $contenidoMeta = @file_get_contents($metaFile);
    if (!empty($contenidoMeta)) {
        $metadata = json_decode($contenidoMeta, true);
    }
}

// 1. Manejo de subida directa por el operador (Formulario / Drag & Drop)
if (isset($_POST['accion']) && $_POST['accion'] === 'subir_archivo') {
    if (isset($_FILES['archivo_rcar']) && $_FILES['archivo_rcar']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['archivo_rcar']['tmp_name'];
        $fileName = basename($_FILES['archivo_rcar']['name']);
        $fileSize = (int)$_FILES['archivo_rcar']['size'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileSize > 0 && in_array($ext, array('txt', '782', 'out', 'dat', 'rcar'))) {
            $destino = $targetDir . "RespuestaCargas_verificar.txt";
            if (move_uploaded_file($fileTmp, $destino)) {
                $archivoSeleccionado = $destino;
                $nombreArchivoMostrado = $fileName;
                $tamanoArchivo = $fileSize;
                $fechaArchivoSubida = date('Y-m-d H:i:s');

                // Actualizar metadata
                $metadata = array(
                    'nombre_original' => $fileName,
                    'fecha_subida'    => $fechaArchivoSubida,
                    'tamano'          => $fileSize
                );
                @file_put_contents($metaFile, json_encode($metadata));

                $mensajeFeedback = "Archivo <strong>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</strong> recibido y auditado con éxito.";
                $tipoFeedback = "success";
            } else {
                $mensajeFeedback = "No se pudo almacenar el archivo en el servidor. Revise permisos en archivos/.";
                $tipoFeedback = "danger";
            }
        } else {
            $mensajeFeedback = "El archivo debe ser en formato de texto plano (.txt, .782 o .dat).";
            $tipoFeedback = "danger";
        }
    }
}

// 2. Manejo de selección de archivo existente en archivos/
if (empty($archivoSeleccionado) && isset($_POST['archivo_existente'])) {
    $archPost = basename($_POST['archivo_existente']);
    if (!empty($archPost) && file_exists($targetDir . $archPost)) {
        $archivoSeleccionado = $targetDir . $archPost;
        $nombreArchivoMostrado = $archPost;
        $tamanoArchivo = filesize($archivoSeleccionado);
        $fechaArchivoSubida = date('Y-m-d H:i:s', filemtime($archivoSeleccionado));
    }
}

// 3. Fallback automático a los archivos estándar de respuesta de cargas
if (empty($archivoSeleccionado)) {
    if (file_exists($targetDir . "RespuestaCargas_verificar.txt")) {
        $archivoSeleccionado = $targetDir . "RespuestaCargas_verificar.txt";
        $nombreArchivoMostrado = !empty($metadata['nombre_original']) ? $metadata['nombre_original'] : 'RespuestaCargas.txt';
        $tamanoArchivo = filesize($archivoSeleccionado);
        $fechaArchivoSubida = !empty($metadata['fecha_subida']) ? $metadata['fecha_subida'] : date('Y-m-d H:i:s', filemtime($archivoSeleccionado));
    } elseif (file_exists($targetDir . "RespuestaCargas.txt")) {
        $archivoSeleccionado = $targetDir . "RespuestaCargas.txt";
        $nombreArchivoMostrado = 'RespuestaCargas.txt';
        $tamanoArchivo = filesize($archivoSeleccionado);
        $fechaArchivoSubida = date('Y-m-d H:i:s', filemtime($archivoSeleccionado));
    }
}

// Listar todos los archivos de respuesta de cargas disponibles en archivos/
$archivosDisponibles = array();
if (is_dir($targetDir)) {
    $scan = scandir($targetDir);
    if ($scan) {
        foreach ($scan as $f) {
            if ($f === '.' || $f === '..') continue;
            $fLower = strtolower($f);
            if (strpos($fLower, 'respuestacarga') !== false || strpos($fLower, 'rcar') !== false || strpos($fLower, 'carga') !== false) {
                if (is_file($targetDir . $f) && (substr($fLower, -4) === '.txt' || substr($fLower, -4) === '.782' || substr($fLower, -4) === '.dat')) {
                    $archivosDisponibles[] = $f;
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// PRECÓMPUTO DEL PADRÓN INSTITUCIONAL EN MEMORIA (O(1))
// -----------------------------------------------------------------------------

$padronCuentas = array();
try {
    if (isset($conn) && $conn instanceof PDO) {
        $stmtB = $conn->query("SELECT id, Apellido, Nombre, NumeroDocumento, Cuit, NumeroCuenta, NumeroTarjeta, Localidad, Calle, Numero FROM beneficiario WHERE NumeroCuenta IS NOT NULL AND NumeroCuenta != ''");
        if ($stmtB) {
            while ($b = $stmtB->fetch(PDO::FETCH_ASSOC)) {
                $cta = trim($b['NumeroCuenta']);
                if ($cta !== '') {
                    $padronCuentas[$cta] = $b;
                }
            }
        }
    }
} catch (Exception $e) {
    // Continuar si la BD no está disponible temporalmente
}

// -----------------------------------------------------------------------------
// PARSING DEL ARCHIVO CABAL RCAR (80 BYTES)
// -----------------------------------------------------------------------------

$acreditaciones = array();
$headerInfo = null;
$trailerInfo = null;
$stats = array(
    'total_cuentas'      => 0,
    'cant_aprobadas'     => 0,
    'cant_rechazadas'    => 0,
    'monto_total'        => 0.0,
    'monto_aprobado'     => 0.0,
    'monto_rechazado'    => 0.0,
    'monto_cajeros'      => 0.0,
    'entidad_detectada'  => '782',
    'sucursal_detectada' => '001',
    'fecha_lote'         => '',
    'fecha_lote_raw'     => '',
    'hora_lote'          => '',
    'lineas_totales'     => 0,
    'motivos_rechazo'    => array()
);

if (!empty($archivoSeleccionado) && file_exists($archivoSeleccionado)) {
    $contenidoCrudo = file_get_contents($archivoSeleccionado);
    if ($contenidoCrudo !== false) {
        $lineas = preg_split("/\r\n|\n|\r/", $contenidoCrudo);
        $numDetalle = 0;

        foreach ($lineas as $linea) {
            $linea = rtrim($linea, "\r\n");
            if ($linea === '') continue;

            $stats['lineas_totales']++;
            $len = strlen($linea);
            if ($len < 40) continue;

            $campoCuenta = substr($linea, 10, 16);

            // A. REGISTRO HEADER (Bytes 11-26 son 16 ceros)
            if ($campoCuenta === "0000000000000000" || strpos($campoCuenta, "0000000000") === 0) {
                $stats['entidad_detectada'] = substr($linea, 4, 3);
                $stats['sucursal_detectada'] = substr($linea, 7, 3);
                $fRaw = substr($linea, 26, 6);
                $hRaw = substr($linea, 32, 6);
                $errHeader = (strlen($linea) >= 80) ? trim(substr($linea, 77, 3)) : '000';
                if ($errHeader === '') $errHeader = '000';

                $stats['fecha_lote_raw'] = $fRaw;
                $stats['fecha_lote'] = formatearFechaProcesoCabalRcar($fRaw);
                $stats['hora_lote'] = formatearHoraCabalRcar($hRaw);

                $headerInfo = array(
                    'entidad'   => $stats['entidad_detectada'],
                    'sucursal'  => $stats['sucursal_detectada'],
                    'fecha'     => $stats['fecha_lote'],
                    'hora'      => $stats['hora_lote'],
                    'cod_error' => $errHeader
                );
                continue;
            }

            // B. REGISTRO TRAILER (Bytes 11-26 son 16 nueves)
            if ($campoCuenta === "9999999999999999" || strpos($campoCuenta, "999999") === 0) {
                $cantEnv = (strlen($linea) >= 31) ? intval(substr($linea, 26, 5)) : 0;
                $sumComprasEnv = (strlen($linea) >= 41) ? floatval(substr($linea, 31, 8) . '.' . substr($linea, 39, 2)) : 0.0;
                $errTrailer = (strlen($linea) >= 80) ? trim(substr($linea, 77, 3)) : '000';
                if ($errTrailer === '') $errTrailer = '000';

                $trailerInfo = array(
                    'cant_enviada' => $cantEnv,
                    'monto_compras'=> $sumComprasEnv,
                    'cod_error'    => $errTrailer
                );
                continue;
            }

            // C. REGISTRO DE DETALLE (Crédito aplicado a cuenta)
            $cuenta = trim($campoCuenta);
            $codOp = substr($linea, 26, 2);
            $subCodOp = substr($linea, 28, 2);

            // Importe Compras: 7 enteros + 2 decimales (offset 30, len 9)
            $impCompras = 0.0;
            if (strlen($linea) >= 39) {
                $impCompras = floatval(substr($linea, 30, 7) . '.' . substr($linea, 37, 2));
            }

            // Importe Cajeros: 7 enteros + 2 decimales (offset 39, len 9)
            $impCajeros = 0.0;
            if (strlen($linea) >= 48) {
                $impCajeros = floatval(substr($linea, 39, 7) . '.' . substr($linea, 46, 2));
            }

            $motivoCarga = (strlen($linea) >= 54) ? trim(substr($linea, 48, 6)) : '';
            $nroCarga = (strlen($linea) >= 62) ? intval(substr($linea, 54, 8)) : 0;

            // Código de Error Cabal (offset 77, len 3)
            $codError = (strlen($linea) >= 80) ? trim(substr($linea, 77, 3)) : '000';
            if ($codError === '') {
                $codError = '000';
            }

            $esAprobada = ($codError === '000');
            $numDetalle++;
            $stats['total_cuentas']++;
            $stats['monto_total'] += $impCompras;
            $stats['monto_cajeros'] += $impCajeros;

            if ($esAprobada) {
                $stats['cant_aprobadas']++;
                $stats['monto_aprobado'] += $impCompras;
            } else {
                $stats['cant_rechazadas']++;
                $stats['monto_rechazado'] += $impCompras;

                // Catalogar motivo de rechazo
                $infoError = isset($codigosAnexo11[$codError]) ? $codigosAnexo11[$codError] : array(
                    'tipo'   => 'danger',
                    'titulo' => 'Error Cabal ' . $codError,
                    'desc'   => 'Código de observación retornado por la plataforma central de Cabal.'
                );

                if (!isset($stats['motivos_rechazo'][$codError])) {
                    $stats['motivos_rechazo'][$codError] = array(
                        'codigo'   => $codError,
                        'titulo'   => $infoError['titulo'],
                        'desc'     => $infoError['desc'],
                        'cantidad' => 0,
                        'monto'    => 0.0
                    );
                }
                $stats['motivos_rechazo'][$codError]['cantidad']++;
                $stats['motivos_rechazo'][$codError]['monto'] += $impCompras;
            }

            // Cruce con Padrón
            $titular = '(Titular no identificado en padrón)';
            $dni = '-';
            $cuit = '-';
            $tarjeta = '';
            $localidad = '-';
            $estaEnBD = false;

            if (isset($padronCuentas[$cuenta])) {
                $b = $padronCuentas[$cuenta];
                $titular = trim($b['Apellido'] . ', ' . $b['Nombre']);
                $dni = trim($b['NumeroDocumento']);
                $cuit = trim($b['Cuit']);
                $tarjeta = trim($b['NumeroTarjeta']);
                $localidad = trim($b['Localidad']);
                $estaEnBD = true;
            }

            // Enmascarar tarjeta obligatoriamente
            $tarjetaEnmascarada = !empty($tarjeta) ? formatearTarjetaCredito($tarjeta) : 'XXXX.XX**.****.XXXX';

            $acreditaciones[] = array(
                'num'                 => $numDetalle,
                'cuenta'              => $cuenta,
                'titular'             => $titular,
                'dni'                 => $dni,
                'dni_fmt'             => formatearNumeroDocumento($dni),
                'cuit'                => $cuit,
                'cuit_fmt'            => formatearCuilCuit($cuit),
                'tarjeta_raw'         => $tarjeta,
                'tarjeta_enmascarada' => $tarjetaEnmascarada,
                'localidad'           => $localidad,
                'monto'               => $impCompras,
                'monto_fmt'           => '$ ' . number_format($impCompras, 2, ',', '.'),
                'cod_error'           => $codError,
                'es_aprobada'         => $esAprobada,
                'motivo_carga'        => $motivoCarga,
                'nro_carga'           => $nroCarga,
                'esta_en_bd'          => $estaEnBD
            );
        }
    }
}

// Fallback de fecha de lote si no vino en el Header
if (empty($stats['fecha_lote'])) {
    if (!empty($fechaArchivoSubida)) {
        $stats['fecha_lote'] = date('d/m/Y', strtotime($fechaArchivoSubida));
    } else {
        $stats['fecha_lote'] = date('d/m/Y');
    }
}

// Formateo de totales monetarios
$stats['monto_total_fmt'] = '$ ' . number_format($stats['monto_total'], 2, ',', '.');
$stats['monto_aprobado_fmt'] = '$ ' . number_format($stats['monto_aprobado'], 2, ',', '.');
$stats['monto_rechazado_fmt'] = '$ ' . number_format($stats['monto_rechazado'], 2, ',', '.');

// -----------------------------------------------------------------------------
// GENERACIÓN Y DESCARGA DE ARCHIVO EML (.eml)
// -----------------------------------------------------------------------------

if (isset($_GET['accion']) && $_GET['accion'] === 'descargar_eml') {
    $fechaEmision = $stats['fecha_lote'];
    $fechaHoy = date('d/m/Y H:i');
    $asuntoEmail = "[SeNAF] Notificación de Acreditación de Saldos y Carga Cabal FSR - Lote " . $fechaEmision;
    $boundary = "----=_Part_" . md5(uniqid(time(), true));

    // Destinatario opcional provisto por el operador o en blanco para completar en Thunderbird
    $destinatario = isset($_GET['destinatario']) ? trim($_GET['destinatario']) : '';
    $destinatario = str_replace(array("\r", "\n"), '', $destinatario);

    $pathImagenCabecera = __DIR__ . "/img/cabecera_ministerio.png";
    $tieneImagen = file_exists($pathImagenCabecera);
    $imagenBase64 = $tieneImagen ? base64_encode(file_get_contents($pathImagenCabecera)) : '';

    $pathImagenCabal = __DIR__ . "/img/logo_cabal_oficial.png";
    $tieneCabal = file_exists($pathImagenCabal);
    $cabalBase64 = $tieneCabal ? base64_encode(file_get_contents($pathImagenCabal)) : '';

    // Armado del cuerpo HTML del correo institucional
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($asuntoEmail, ENT_QUOTES, 'UTF-8'); ?></title>
<style>
    body { font-family: Arial, Helvetica, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
    .email-wrapper { max-width: 800px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .header-banner { background: #ffffff; padding: 24px 30px; color: #0f172a; border-bottom: 4px solid #059669; }
    .header-banner h1 { margin: 10px 0 0 0; font-size: 19px; font-weight: 700; color: #0f172a; letter-spacing: 0.3px; }
    .header-banner p { margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 600; }
    .badge-institucional { display: inline-block; background: #059669; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-top: 8px; text-transform: uppercase; }
    .content-area { padding: 28px 30px; font-size: 14px; line-height: 1.6; }
    .kpi-container { display: table; width: 100%; margin: 20px 0; border-collapse: separate; border-spacing: 10px; }
    .kpi-box { display: table-cell; width: 33.33%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; }
    .kpi-num { font-size: 22px; font-weight: bold; color: #065f46; }
    .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-top: 4px; }
    .table-beneficiarios { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
    .table-beneficiarios th { background: #1e293b; color: #ffffff; text-align: left; padding: 10px; font-size: 12px; }
    .table-beneficiarios td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    .table-beneficiarios tr:nth-child(even) { background-color: #f8fafc; }
    .tarjeta-code { font-family: monospace; font-size: 13px; font-weight: bold; color: #1e40af; background: #eff6ff; padding: 2px 6px; border-radius: 4px; border: 1px solid #bfdbfe; }
    .cuenta-code { font-family: monospace; font-size: 12px; color: #475569; }
    .monto-code { font-family: monospace; font-size: 13px; font-weight: bold; color: #047857; }
    .badge-ok { color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 11px; display: inline-block; }
    .badge-fail { color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 12px; font-weight: bold; font-size: 11px; display: inline-block; }
    .footer-email { background: #f8fafc; padding: 20px 30px; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; text-align: center; }
</style>
</head>
<body>
<div class="email-wrapper">
    <div class="header-banner">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
            <tr>
                <td style="vertical-align: middle; text-align: left;">
                    <?php if ($tieneImagen): ?>
                    <img src="cid:cabecera-logo" alt="Ministerio de Desarrollo Humano - Gobierno de Río Negro" style="max-height: 52px; height: auto; max-width: 320px; display: block; border: 0;" />
                    <?php endif; ?>
                </td>
                <td style="vertical-align: middle; text-align: right;">
                    <?php if ($tieneCabal): ?>
                    <img src="cid:cabal-logo" alt="Cabal Entidad 782" style="max-height: 48px; height: auto; display: inline-block; border: 0;" />
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <h1>Secretaria de Niñez, Adolescencia y Familia &bull; SeNAF</h1>
        <p>Programa Familia Rionegrina Solidaria &bull; Gobierno de Río Negro</p>
        <div class="badge-institucional">Cabal Entidad 782 &bull; Sistema de Tarjetas Precargadas</div>
    </div>

    <div class="content-area">
        <p>Por medio de la presente se notifica la recepción y confirmación del lote de respuesta bancaria de <strong>Acreditación de Saldos Cabal (RespuestaCargas / RCAR)</strong> con fecha de proceso <strong><?php echo htmlspecialchars($stats['fecha_lote']); ?></strong> (Archivo: <code><?php echo htmlspecialchars($nombreArchivoMostrado); ?></code>).</p>

        <div class="kpi-container">
            <div class="kpi-box">
                <div class="kpi-num"><?php echo $stats['total_cuentas']; ?></div>
                <div class="kpi-label">Cuentas Procesadas</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #059669;">
                <div class="kpi-num" style="color: #059669;"><?php echo $stats['cant_aprobadas']; ?></div>
                <div class="kpi-label">Aprobadas (<?php echo $stats['monto_aprobado_fmt']; ?>)</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #dc2626;">
                <div class="kpi-num" style="color: #dc2626;"><?php echo $stats['cant_rechazadas']; ?></div>
                <div class="kpi-label">Rechazadas (<?php echo $stats['monto_rechazado_fmt']; ?>)</div>
            </div>
        </div>

        <h3 style="font-size: 15px; color: #0f172a; margin-top: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px;">
            Nómina de Cuentas y Acreditaciones de Fondos (Tarjetas Enmascaradas)
        </h3>

        <table class="table-beneficiarios">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>Titular / Beneficiario</th>
                    <th>DNI</th>
                    <th>Tarjeta Asignada</th>
                    <th>N° de Cuenta</th>
                    <th style="text-align: right;">Monto Acreditado</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($acreditaciones as $a): ?>
                <tr>
                    <td><?php echo $a['num']; ?></td>
                    <td><strong><?php echo htmlspecialchars($a['titular']); ?></strong><br><small style="color:#64748b;"><?php echo htmlspecialchars($a['localidad']); ?></small></td>
                    <td><?php echo htmlspecialchars($a['dni_fmt']); ?></td>
                    <td><span class="tarjeta-code"><?php echo htmlspecialchars($a['tarjeta_enmascarada']); ?></span></td>
                    <td><span class="cuenta-code"><?php echo htmlspecialchars($a['cuenta']); ?></span></td>
                    <td style="text-align: right;"><span class="monto-code"><?php echo htmlspecialchars($a['monto_fmt']); ?></span></td>
                    <td>
                        <?php if ($a['es_aprobada']): ?>
                            <span class="badge-ok">&check; Aplicada</span>
                        <?php else: ?>
                            <span class="badge-fail">&cross; Error <?php echo htmlspecialchars($a['cod_error']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($stats['motivos_rechazo'])): ?>
        <div style="margin-top: 24px; padding: 14px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 6px;">
            <strong style="color: #9f1239; font-size: 13px;">Observaciones y Motivos de Rechazo Cabal (Anexo 11):</strong>
            <ul style="margin: 8px 0 0 18px; padding: 0; font-size: 12px; color: #881337;">
                <?php foreach ($stats['motivos_rechazo'] as $motivo): ?>
                    <li><strong>[Código <?php echo htmlspecialchars($motivo['codigo']); ?>] <?php echo htmlspecialchars($motivo['titulo']); ?></strong> (<?php echo $motivo['cantidad']; ?> cuentas &bull; $ <?php echo number_format($motivo['monto'], 2, ',', '.'); ?>): <?php echo htmlspecialchars($motivo['desc']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <p style="margin-top: 28px; font-size: 12px; color: #64748b;">
            <strong>Disponibilidad de Fondos:</strong><br>
            Los montos aprobados se encuentran impactados en la plataforma Cabal y disponibles para su utilización por los titulares mediante tarjeta de compra en los comercios adheridos de la provincia.
        </p>
    </div>

    <div class="footer-email">
        Documento generado automáticamente por el Sistema Integral Cabal FSR &bull; Gobierno de Río Negro.<br>
        Confidencialidad amparada por la Ley Nacional 25.326 de Protección de Datos Personales. Los dígitos centrales de las tarjetas se encuentran protegidos y enmascarados.<br>
        Fecha de reporte: <?php echo $fechaHoy; ?> hs.
    </div>
</div>
</body>
</html>
    <?php
    $cuerpoHtml = ob_get_clean();
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    // Estructura completa del archivo .EML (RFC 822 / MIME) con X-Unsent: 1 para Thunderbird
    $eml = "From: \"SeNAF - Cabal FSR\" <senaf-cabal@rionegro.gov.ar>\r\n";
    if (!empty($destinatario)) {
        $eml .= "To: " . $destinatario . "\r\n";
    } else {
        $eml .= "To: \r\n";
    }
    $eml .= "Subject: =?UTF-8?B?" . base64_encode($asuntoEmail) . "?=\r\n";
    $eml .= "Date: " . date('r') . "\r\n";
    $eml .= "Message-ID: <carga-" . time() . "-" . rand(1000, 9999) . "@senaf.rionegro.gov.ar>\r\n";
    $eml .= "X-Unsent: 1\r\n";
    $eml .= "MIME-Version: 1.0\r\n";

    if ($tieneImagen || $tieneCabal) {
        $eml .= "Content-Type: multipart/related; boundary=\"" . $boundary . "\"\r\n";
        $eml .= "X-Mailer: Sistema Cabal FSR - SeNAF Rio Negro (PHP 5.6)\r\n\r\n";

        // Parte HTML
        $eml .= "--" . $boundary . "\r\n";
        $eml .= "Content-Type: text/html; charset=UTF-8\r\n";
        $eml .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $eml .= $cuerpoHtml . "\r\n\r\n";

        // Parte Imagen 1: Cabecera Ministerio
        if ($tieneImagen) {
            $eml .= "--" . $boundary . "\r\n";
            $eml .= "Content-Type: image/png; name=\"cabecera_ministerio.png\"\r\n";
            $eml .= "Content-Transfer-Encoding: base64\r\n";
            $eml .= "Content-ID: <cabecera-logo>\r\n";
            $eml .= "Content-Disposition: inline; filename=\"cabecera_ministerio.png\"\r\n\r\n";
            $eml .= chunk_split($imagenBase64) . "\r\n";
        }

        // Parte Imagen 2: Logo Cabal
        if ($tieneCabal) {
            $eml .= "--" . $boundary . "\r\n";
            $eml .= "Content-Type: image/png; name=\"logo_cabal_oficial.png\"\r\n";
            $eml .= "Content-Transfer-Encoding: base64\r\n";
            $eml .= "Content-ID: <cabal-logo>\r\n";
            $eml .= "Content-Disposition: inline; filename=\"logo_cabal_oficial.png\"\r\n\r\n";
            $eml .= chunk_split($cabalBase64) . "\r\n";
        }

        $eml .= "--" . $boundary . "--\r\n";
    } else {
        $eml .= "Content-Type: text/html; charset=UTF-8\r\n";
        $eml .= "Content-Transfer-Encoding: 8bit\r\n";
        $eml .= "X-Mailer: Sistema Cabal FSR - SeNAF Rio Negro (PHP 5.6)\r\n\r\n";
        $eml .= $cuerpoHtml;
    }

    $nombreDescargaEml = "Informe_Acreditacion_SeNAF_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $stats['fecha_lote']) . ".eml";
    header('Content-Type: message/rfc822; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombreDescargaEml . '"');
    header('Content-Length: ' . strlen($eml));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $eml;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Acreditación de Saldos (Respuesta de Carga) | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .institucional-header-card {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%);
            border-radius: var(--radius-xl);
            padding: 30px 36px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 18px;
        }

        .header-brand-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-titles h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .header-titles p {
            margin: 4px 0 0 0;
            font-size: 0.85rem;
            color: #a7f3d0;
            font-weight: 500;
        }

        .header-meta-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .meta-chip {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 6px 14px;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 600;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .meta-chip.highlight {
            background: rgba(16, 185, 129, 0.35);
            border-color: rgba(16, 185, 129, 0.6);
            color: #ecfdf5;
        }

        .kpi-grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card-modern {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }

        .stat-card-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card-modern.primary-border { border-top: 4px solid var(--primary-600); }
        .stat-card-modern.success-border { border-top: 4px solid #059669; }
        .stat-card-modern.danger-border  { border-top: 4px solid #dc2626; }
        .stat-card-modern.emerald-border { border-top: 4px solid #10b981; }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .stat-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .stat-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--navy-800);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .stat-subtext {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .toolbar-panel {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 14px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            box-shadow: var(--shadow-sm);
        }

        .search-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 280px;
        }

        .search-input-modern {
            width: 100%;
            padding: 9px 14px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            font-size: 0.88rem;
            background: var(--surface-subtle);
            color: var(--text-main);
            outline: none;
            transition: all var(--transition-fast);
        }

        .search-input-modern:focus {
            background: #ffffff;
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action-tool {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition-fast);
            border: 1px solid transparent;
        }

        .btn-action-tool.primary {
            background: var(--primary-600);
            color: #ffffff;
        }
        .btn-action-tool.primary:hover {
            background: var(--primary-700);
            box-shadow: var(--shadow-sm);
        }

        .btn-action-tool.emerald {
            background: #059669;
            color: #ffffff;
        }
        .btn-action-tool.emerald:hover {
            background: #047857;
            box-shadow: var(--shadow-sm);
        }

        .btn-action-tool.secondary {
            background: var(--surface-card);
            border-color: var(--border-light);
            color: var(--text-main);
        }
        .btn-action-tool.secondary:hover {
            background: var(--surface-subtle);
        }

        .table-responsive-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table-cargas {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .table-cargas thead th {
            background: var(--surface-subtle);
            padding: 12px 14px;
            font-weight: 700;
            color: var(--navy-700);
            border-bottom: 2px solid var(--border-light);
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.04em;
        }

        .table-cargas tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
            color: var(--text-main);
        }

        .table-cargas tbody tr:hover {
            background-color: rgba(5, 150, 105, 0.04);
        }

        .badge-tarjeta {
            font-family: 'JetBrains Mono', monospace;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
            letter-spacing: 0.02em;
        }

        .badge-cuenta {
            font-family: 'JetBrains Mono', monospace;
            color: #475569;
            font-size: 0.82rem;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            display: inline-block;
        }

        .badge-monto {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            font-weight: 800;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            display: inline-block;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-status.aprobada {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-status.rechazada {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            max-width: 860px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
        }

        .modal-header {
            background: linear-gradient(135deg, #064e3b, #047857);
            padding: 20px 26px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 26px;
        }

        @media print {
            body { background: #ffffff !important; color: #000000 !important; }
            .top-nav, .toolbar-panel, .btn-action-tool, .nav-actions, .modal-overlay { display: none !important; }
            .institucional-header-card { background: #f8fafc !important; color: #000000 !important; border: 1px solid #cbd5e1 !important; box-shadow: none !important; }
            .institucional-header-card h2 { color: #000000 !important; }
            .institucional-header-card p { color: #475569 !important; }
            .meta-chip { background: #ffffff !important; color: #000000 !important; border: 1px solid #94a3b8 !important; }
            .table-responsive-card { border: none !important; box-shadow: none !important; }
            .badge-tarjeta { border: 1px solid #000 !important; background: #fff !important; color: #000 !important; }
            .badge-status { border: 1px solid #000 !important; }
            .page-container { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <div class="top-nav-header-bar">
            <div class="brand-logo-slot left">
                <a href="index.php" class="brand-logo-wrap nexo-wrap" title="Nexo FRS">
                    <img src="img/NexoFRS.png" alt="Logo Nexo FRS" onerror="this.src='img/favicon.png'">
                </a>
            </div>
            <a href="index.php" class="brand-text-center" id="brand-link">
                <h1>
                    Nexo FRS
                    <span class="brand-badge">Entidad 782</span>
                </h1>
                <p>SeNAF Río Negro • Familia Rionegrina Solidaria</p>
            </a>
            <div class="brand-logo-slot right">
                <div class="brand-logo-wrap cabal-wrap" title="Cabal Entidad 782">
                    <img src="img/cabal.png" alt="Logo Cabal" onerror="this.src='img/favicon.png'">
                </div>
            </div>
        </div>

        <div class="nav-actions">
            <a href="index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver al Tablero
            </a>
            <a href="ProcesarCarga.php" class="btn-nav-action" title="Ir a Paso 6: Verificar Respuesta Carga">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                Paso 6: Verificar Respuesta Carga
            </a>
            <a href="GenerarCargaCredito2026.php" class="btn-nav-action" title="Ir a Generación de NOVCAR">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                Generar NOVCAR
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="padding-top: 24px;">

    <!-- FEEDBACK ALERT -->
    <?php if (!empty($mensajeFeedback)): ?>
        <div class="alert-modern <?php echo $tipoFeedback; ?>" style="margin-bottom: 20px;">
            <?php echo $mensajeFeedback; ?>
        </div>
    <?php endif; ?>

    <!-- MEMBRETE OFICIAL E INFORME INSTITUCIONAL -->
    <div class="institucional-header-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
            <div style="background: #ffffff; padding: 10px 18px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <img src="img/cabecera_ministerio.png" alt="Ministerio de Desarrollo Humano - Gobierno de Río Negro" style="max-height: 54px; height: auto; max-width: 100%; display: block;">
            </div>
            <div style="background: #ffffff; padding: 8px 14px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <img src="img/logo_cabal_oficial.png" alt="Logo Cabal" style="max-height: 50px; height: auto; display: block;">
            </div>
        </div>

        <div class="header-top-row">
            <div class="header-brand-wrap">
                <div class="header-titles">
                    <h2>Informe Oficial de Acreditación de Saldos (Respuesta de Carga Cabal)</h2>
                    <p>Secretaria de Niñez, Adolescencia y Familia (SeNAF) • Gobierno de Río Negro</p>
                </div>
            </div>

            <div class="header-meta-chips">
                <span class="meta-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Fecha Solicitud / Proceso: <strong><?php echo htmlspecialchars($stats['fecha_lote']); ?></strong> <?php if (!empty($stats['hora_lote'])): ?>(<?php echo htmlspecialchars($stats['hora_lote']); ?> hs)<?php endif; ?>
                </span>
                <span class="meta-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    Entidad: <strong><?php echo htmlspecialchars($stats['entidad_detectada']); ?></strong> (Suc. <?php echo htmlspecialchars($stats['sucursal_detectada']); ?>)
                </span>
                <span class="meta-chip highlight">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                    Archivo: <code><?php echo htmlspecialchars($nombreArchivoMostrado); ?></code>
                </span>
            </div>
        </div>

        <div style="font-size: 0.88rem; color: #e2e8f0; line-height: 1.5; max-width: 1000px;">
            Este informe documenta la respuesta bancaria de acreditación de fondos devuelta por la plataforma central Cabal (formato fijo 80 bytes RCAR / Anexo 3). Todos los números de tarjeta se presentan estrictamente enmascarados conforme a las normas de seguridad.
        </div>
    </div>

    <!-- TARJETAS KPIS -->
    <div class="kpi-grid-stats">
        <div class="stat-card-modern primary-border">
            <div class="stat-header">
                <span class="stat-title">Cuentas en Lote</span>
                <span class="stat-icon" style="background:#eff6ff; color:#2563eb;">👥</span>
            </div>
            <div class="stat-value"><?php echo $stats['total_cuentas']; ?></div>
            <div class="stat-subtext">Total de cuentas procesadas</div>
        </div>

        <div class="stat-card-modern success-border">
            <div class="stat-header">
                <span class="stat-title">Acreditaciones Exitosas</span>
                <span class="stat-icon" style="background:#ecfdf5; color:#059669;">💳</span>
            </div>
            <div class="stat-value" style="color:#059669;"><?php echo $stats['cant_aprobadas']; ?></div>
            <div class="stat-subtext">Monto acreditado: <strong><?php echo $stats['monto_aprobado_fmt']; ?></strong></div>
        </div>

        <div class="stat-card-modern danger-border">
            <div class="stat-header">
                <span class="stat-title">Acreditaciones Rechazadas</span>
                <span class="stat-icon" style="background:#fef2f2; color:#dc2626;">⚠️</span>
            </div>
            <div class="stat-value" style="color:<?php echo $stats['cant_rechazadas'] > 0 ? '#dc2626' : 'var(--text-muted)'; ?>;">
                <?php echo $stats['cant_rechazadas']; ?>
            </div>
            <div class="stat-subtext">Monto observado: <strong><?php echo $stats['monto_rechazado_fmt']; ?></strong></div>
        </div>

        <div class="stat-card-modern emerald-border">
            <div class="stat-header">
                <span class="stat-title">Monto Total Procesado</span>
                <span class="stat-icon" style="background:#ecfdf5; color:#059669;">💰</span>
            </div>
            <div class="stat-value" style="color:#065f46; font-size: 1.45rem;"><?php echo $stats['monto_total_fmt']; ?></div>
            <div class="stat-subtext">Sumatoria general de compras (Op 6339)</div>
        </div>
    </div>

    <!-- BARRA DE HERRAMIENTAS Y FILTRADO INTERACTIVO -->
    <div class="toolbar-panel">
        <div class="search-group">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="filtroRapidoCargas" class="search-input-modern" placeholder="Buscar por Titular, DNI, Tarjeta, Cuenta o Estado..." onkeyup="filtrarTablaCargas()">
        </div>

        <div class="toolbar-actions">
            <button type="button" class="btn-action-tool emerald" onclick="abrirModalEml()" title="Previsualizar correo formal para envío a SeNAF">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Vista Previa Correo SeNAF
            </button>

            <a href="InformeAcreditacion.php?accion=descargar_eml" class="btn-action-tool primary" title="Descargar archivo .eml con cabeceras MIME y cuerpo HTML para Thunderbird">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Descargar EML (.eml)
            </a>

            <button type="button" class="btn-action-tool secondary" onclick="window.print()" title="Imprimir informe oficial">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Imprimir
            </button>

            <button type="button" class="btn-action-tool secondary" onclick="abrirModalArchivos()" title="Cambiar de archivo RespuestaCargas">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Seleccionar Archivo
            </button>
        </div>
    </div>

    <!-- TABLA DE DETALLE DE ACREDITACIONES -->
    <div class="table-responsive-card">
        <table class="table-cargas" id="tablaCargas">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th>Titular / Beneficiario</th>
                    <th>DNI</th>
                    <th>Tarjeta Asignada</th>
                    <th>N° de Cuenta</th>
                    <th style="text-align: right;">Importe Acreditado</th>
                    <th>Resultado Cabal</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($acreditaciones)): ?>
                    <?php foreach ($acreditaciones as $a): ?>
                    <tr class="fila-acreditacion">
                        <td style="font-weight: 700; color: var(--text-muted);"><?php echo $a['num']; ?></td>
                        <td>
                            <strong style="color: var(--navy-800);"><?php echo htmlspecialchars($a['titular']); ?></strong>
                            <?php if ($a['localidad'] !== '-'): ?>
                                <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($a['localidad']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo htmlspecialchars($a['dni_fmt']); ?></span>
                        </td>
                        <td>
                            <span class="badge-tarjeta"><?php echo htmlspecialchars($a['tarjeta_enmascarada']); ?></span>
                        </td>
                        <td>
                            <span class="badge-cuenta"><?php echo htmlspecialchars($a['cuenta']); ?></span>
                        </td>
                        <td style="text-align: right;">
                            <span class="badge-monto"><?php echo htmlspecialchars($a['monto_fmt']); ?></span>
                        </td>
                        <td>
                            <?php if ($a['es_aprobada']): ?>
                                <span class="badge-status aprobada">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    Aprobada
                                </span>
                            <?php else: ?>
                                <span class="badge-status rechazada">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    Error <?php echo htmlspecialchars($a['cod_error']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a['es_aprobada']): ?>
                                <small style="color: #059669; font-weight: 600;">Crédito aplicado</small>
                            <?php else: ?>
                                <?php $descErr = isset($codigosAnexo11[$a['cod_error']]) ? $codigosAnexo11[$a['cod_error']]['titulo'] : 'Rechazo Cabal'; ?>
                                <small style="color: #dc2626; font-weight: 600;"><?php echo htmlspecialchars($descErr); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <div style="font-size: 2.5rem; margin-bottom: 10px;">📄</div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800);">No se encontraron registros de acreditación para mostrar</div>
                            <div style="font-size: 0.85rem; margin-top: 6px;">Por favor, adjunte o seleccione un archivo de respuesta <code>RespuestaCargas.txt</code> devuelto por el banco.</div>
                            <button type="button" class="btn-action-tool primary" style="margin-top: 16px;" onclick="abrirModalArchivos()">
                                Seleccionar Archivo
                            </button>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- NOTA AL PIE Y RESUMEN LEGAL -->
    <div style="margin-top: 24px; padding: 18px 22px; background: var(--surface-card); border: 1px solid var(--border-light); border-radius: var(--radius-lg); font-size: 0.82rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <strong>Secretaria de Niñez, Adolescencia y Familia (SeNAF) &bull; Gobierno de Río Negro</strong><br>
            Programa Familia Rionegrina Solidaria &bull; Entidad Cabal 782 &bull; Sistema de Tarjetas Precargadas.<br>
            Protección de datos amparada por la Ley Nacional 25.326. Los datos de tarjeta se presentan enmascarados conforme a las mejores prácticas PCI-DSS.
        </div>
        <div>
            <a href="ProcesarCarga.php" class="btn-nav-action">
                Ir a Paso 6: Verificar Carga
            </a>
            <a href="index.php" class="btn-nav-action primary">
                Volver al Tablero
            </a>
        </div>
    </div>

</main>

<!-- =======================================================================
     MODAL 1: VISTA PREVIA DE CORREO INSTITUCIONAL SeNAF (PLANTILLA EML)
     ======================================================================= -->
<div class="modal-overlay" id="modalEmlPreview">
    <div class="modal-card">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.3rem;">✉️</span>
                <h3 style="margin: 0; font-size: 1.1rem; color: #ffffff;">
                    Vista Previa de Correo Institucional SeNAF (Formato EML)
                </h3>
            </div>
            <button type="button" onclick="cerrarModalEml()" style="background: none; border: none; color: #ffffff; font-size: 1.4rem; cursor: pointer;">✕</button>
        </div>

        <div class="modal-body">
            <!-- CABECERAS DE EMAIL -->
            <div style="background: var(--surface-subtle); padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-light); font-size: 0.84rem; margin-bottom: 20px;">
                <div style="margin-bottom: 8px;"><strong>De:</strong> SeNAF - Cabal FSR &lt;senaf-cabal@rionegro.gov.ar&gt;</div>
                <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <label for="inputDestinatarioEml" style="font-weight: 700; color: var(--navy-800); min-width: 45px;">Para:</label>
                    <input type="text" id="inputDestinatarioEml" placeholder="Escribir destinatario (ej. delegacion@senaf.rionegro.gov.ar) o dejar en blanco..." style="flex: 1; min-width: 280px; padding: 7px 12px; border: 1.5px solid var(--border-light); border-radius: 6px; font-size: 0.84rem; background: var(--surface-card); color: var(--text-main);" oninput="actualizarEnlaceDescargaEml()">
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 8px; padding-left: 55px;">
                    💡 <em>Compatible con <strong>Mozilla Thunderbird</strong>: Se genera con <code>X-Unsent: 1</code> para abrir directamente en la ventana de redacción, con el campo destinatario editable y el botón <strong>"Enviar"</strong> habilitado.</em>
                </div>
                <div><strong>Asunto:</strong> [SeNAF] Notificación de Acreditación de Saldos y Carga Cabal FSR - Lote <?php echo htmlspecialchars($stats['fecha_lote']); ?></div>
            </div>

            <!-- CUERPO VISUAL DEL CORREO -->
            <div style="border: 1px solid #cbd5e1; border-radius: var(--radius-md); overflow: hidden; background: #ffffff;">
                <div style="background: #ffffff; padding: 20px 24px; color: #0f172a; border-bottom: 3px solid #059669;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <img src="img/cabecera_ministerio.png" alt="Ministerio de Desarrollo Humano - Gobierno de Río Negro" style="max-height: 50px; height: auto; max-width: 70%; display: block; border: 0;">
                        <img src="img/logo_cabal_oficial.png" alt="Cabal" style="max-height: 46px; height: auto; display: block; border: 0;">
                    </div>
                    <h3 style="margin: 0; font-size: 1.15rem; color: #0f172a;">Secretaria de Niñez, Adolescencia y Familia • SeNAF</h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.82rem; color: #64748b;">Programa Familia Rionegrina Solidaria • Cabal Entidad 782</p>
                </div>
                <div style="padding: 20px; font-size: 0.85rem; line-height: 1.6; color: #1e293b;">
                    <p>Por medio de la presente se notifica la recepción y confirmación del lote de respuesta bancaria de <strong>Acreditación de Saldos Cabal (RespuestaCargas / RCAR)</strong> con fecha de proceso <strong><?php echo htmlspecialchars($stats['fecha_lote']); ?></strong> (Archivo: <code><?php echo htmlspecialchars($nombreArchivoMostrado); ?></code>).</p>

                    <div style="display: flex; gap: 10px; margin: 18px 0; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 150px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #065f46;"><?php echo $stats['total_cuentas']; ?></div>
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600;">Cuentas Procesadas</div>
                        </div>
                        <div style="flex: 1; min-width: 150px; background: #f8fafc; border: 1px solid #e2e8f0; border-top: 3px solid #059669; border-radius: 6px; padding: 12px; text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #059669;"><?php echo $stats['cant_aprobadas']; ?></div>
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600;">Aprobadas (<?php echo $stats['monto_aprobado_fmt']; ?>)</div>
                        </div>
                        <div style="flex: 1; min-width: 150px; background: #f8fafc; border: 1px solid #e2e8f0; border-top: 3px solid #dc2626; border-radius: 6px; padding: 12px; text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #dc2626;"><?php echo $stats['cant_rechazadas']; ?></div>
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600;">Rechazadas (<?php echo $stats['monto_rechazado_fmt']; ?>)</div>
                        </div>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 0.82rem;">
                        <thead>
                            <tr style="background: #1e293b; color: #ffffff;">
                                <th style="padding: 8px 10px; text-align: left;">Titular</th>
                                <th style="padding: 8px 10px; text-align: left;">DNI</th>
                                <th style="padding: 8px 10px; text-align: left;">Tarjeta Asignada</th>
                                <th style="padding: 8px 10px; text-align: left;">Cuenta</th>
                                <th style="padding: 8px 10px; text-align: right;">Monto</th>
                                <th style="padding: 8px 10px; text-align: left;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($acreditaciones as $a): ?>
                                <tr>
                                    <td style="padding: 8px 10px;"><strong><?php echo htmlspecialchars($a['titular']); ?></strong></td>
                                    <td style="padding: 8px 10px;"><?php echo htmlspecialchars($a['dni_fmt']); ?></td>
                                    <td style="padding: 8px 10px;"><code style="color: #1e40af; font-weight: bold;"><?php echo htmlspecialchars($a['tarjeta_enmascarada']); ?></code></td>
                                    <td style="padding: 8px 10px;"><code style="color: #475569;"><?php echo htmlspecialchars($a['cuenta']); ?></code></td>
                                    <td style="padding: 8px 10px; text-align: right; font-weight: bold; color: #047857;"><?php echo htmlspecialchars($a['monto_fmt']); ?></td>
                                    <td style="padding: 8px 10px;">
                                        <?php if ($a['es_aprobada']): ?>
                                            <span style="color: #047857; font-weight: bold;">✔ Aplicada</span>
                                        <?php else: ?>
                                            <span style="color: #dc2626; font-weight: bold;">✖ Error <?php echo htmlspecialchars($a['cod_error']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p style="margin-top: 20px; font-size: 0.78rem; color: #64748b;">
                        Los fondos acreditados se encuentran operativos y disponibles para su uso inmediato en la red de comercios asociados de Cabal.
                    </p>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN DENTRO DEL MODAL -->
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn-action-tool secondary" onclick="cerrarModalEml()">
                    Cerrar
                </button>
                <a href="InformeAcreditacion.php?accion=descargar_eml" id="btnDescargarEmlModal" class="btn-action-tool primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Descargar Archivo EML (.eml)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- =======================================================================
     MODAL 2: SELECCIÓN Y SUBIDA DE ARCHIVO DE RESPUESTA DE CARGAS
     ======================================================================= -->
<div class="modal-overlay" id="modalArchivosSelector">
    <div class="modal-card" style="max-width: 640px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.3rem;">📁</span>
                <h3 style="margin: 0; font-size: 1.1rem; color: #ffffff;">
                    Seleccionar o Subir Archivo Respuesta de Cargas (RCAR)
                </h3>
            </div>
            <button type="button" onclick="cerrarModalArchivos()" style="background: none; border: none; color: #ffffff; font-size: 1.4rem; cursor: pointer;">✕</button>
        </div>

        <div class="modal-body">
            <!-- SELECCIONAR EXISTENTE -->
            <?php if (!empty($archivosDisponibles)): ?>
                <form action="InformeAcreditacion.php" method="POST" style="margin-bottom: 24px;">
                    <label style="display: block; font-weight: 700; font-size: 0.88rem; margin-bottom: 8px;">
                        Archivos disponibles en la carpeta archivos/:
                    </label>
                    <div style="display: flex; gap: 10px;">
                        <select name="archivo_existente" class="search-input-modern" style="padding-left: 14px; flex: 1;">
                            <?php foreach ($archivosDisponibles as $arch): ?>
                                <option value="<?php echo htmlspecialchars($arch); ?>" <?php echo ($nombreArchivoMostrado === $arch) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($arch); ?> (<?php echo number_format(filesize($targetDir . $arch) / 1024, 1, ',', '.'); ?> KB)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-action-tool primary">
                            Cargar
                        </button>
                    </div>
                </form>
                <div style="text-align: center; position: relative; margin: 20px 0;">
                    <hr style="border: none; border-top: 1px solid var(--border-light);">
                    <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--surface-card); padding: 0 12px; font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">O SUBIR UNO NUEVO</span>
                </div>
            <?php endif; ?>

            <!-- SUBIDA DRAG & DROP -->
            <form action="InformeAcreditacion.php" method="POST" enctype="multipart/form-data" id="formUploadDropzone">
                <input type="hidden" name="accion" value="subir_archivo">
                <div style="border: 2px dashed #059669; background: rgba(5, 150, 105, 0.04); border-radius: var(--radius-lg); padding: 32px 20px; text-align: center; cursor: pointer;" onclick="document.getElementById('inputDropzoneRcar').click()">
                    <div style="font-size: 2.2rem; margin-bottom: 8px;">📤</div>
                    <div style="font-weight: 700; color: var(--navy-800); font-size: 0.95rem;">
                        Haga clic o arrastre aquí el archivo devuelto por Cabal
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                        Admite <code>RespuestaCargas.txt</code>, <code>782_RCAR_*.txt</code> o formato fijo de 80 bytes
                    </div>
                    <input type="file" name="archivo_rcar" id="inputDropzoneRcar" style="display: none;" onchange="document.getElementById('formUploadDropzone').submit()">
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Filtro interactivo de la tabla de acreditaciones
    function filtrarTablaCargas() {
        var input = document.getElementById("filtroRapidoCargas");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("tablaCargas");
        var tr = table.getElementsByClassName("fila-acreditacion");

        for (var i = 0; i < tr.length; i++) {
            var text = tr[i].textContent || tr[i].innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }

    // Modal EML
    function abrirModalEml() {
        actualizarEnlaceDescargaEml();
        document.getElementById('modalEmlPreview').style.display = 'flex';
    }
    function cerrarModalEml() {
        document.getElementById('modalEmlPreview').style.display = 'none';
    }

    // Actualizar enlace de descarga de EML según destinatario escrito
    function actualizarEnlaceDescargaEml() {
        var input = document.getElementById('inputDestinatarioEml');
        var btn = document.getElementById('btnDescargarEmlModal');
        var val = input ? input.value.trim() : '';
        var base = 'InformeAcreditacion.php?accion=descargar_eml';
        if (btn) {
            if (val !== '') {
                btn.href = base + '&destinatario=' + encodeURIComponent(val);
            } else {
                btn.href = base;
            }
        }
    }

    // Modal Archivos
    function abrirModalArchivos() {
        document.getElementById('modalArchivosSelector').style.display = 'flex';
    }
    function cerrarModalArchivos() {
        document.getElementById('modalArchivosSelector').style.display = 'none';
    }

    // Cerrar modales con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEml();
            cerrarModalArchivos();
        }
    });
</script>

</body>
</html>
