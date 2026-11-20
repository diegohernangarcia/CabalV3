<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * 1.4 Informe de Altas de Tarjetas (782_out_altas_tpc)
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Genera informe en HTML institucional detallando los resultados del archivo 782_out_altas recién procesado.
 * 2. Informa fecha de solicitud / proceso del lote bancario, entidad emisora y metadatos.
 * 3. Lista beneficiarios recién creados con datos filiatorios, de contacto y domicilio.
 * 4. Aplica estrictamente enmascaramiento en el número de tarjeta (formatearTarjetaCredito()).
 * 5. Coteja la presencia del titular en la base de datos (tabla `beneficiario`).
 * 6. Genera y descarga archivo .EML estándar (MIME/RFC 822) con cabecera institucional SeNAF listo para envío.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/auth/verificar_sesion.php");
requerirAutenticacion();

require_once(__DIR__ . "/BD/BD.php");
require_once(__DIR__ . "/BD/Funciones.php");
require_once(__DIR__ . "/BD/UsarGestion.php");
require_once(__DIR__ . "/BD/CabalErrores.php");

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

if (!function_exists('formatearFechaProcesoCabal')) {
function formatearFechaProcesoCabal($fec) {
    $fec = trim((string)$fec);
    if (strlen($fec) === 6 && is_numeric($fec)) {
        $aa = substr($fec, 0, 2);
        $mm = substr($fec, 2, 2);
        $dd = substr($fec, 4, 2);
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

if (!function_exists('formatearFechaNacimiento')) {
function formatearFechaNacimiento($fec) {
    $fec = trim((string)$fec);
    if (strlen($fec) === 8 && is_numeric($fec)) {
        $dd = substr($fec, 0, 2);
        $mm = substr($fec, 2, 2);
        $aaaa = substr($fec, 4, 4);
        return $dd . '/' . $mm . '/' . $aaaa;
    }
    return $fec;
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

if (!function_exists('formatearTelefonoCompleto')) {
function formatearTelefonoCompleto($carac, $num) {
    $carac = ltrim(trim((string)$carac), '0');
    $num = ltrim(trim((string)$num), '0');
    if (!empty($carac) && !empty($num)) {
        return '(0' . $carac . ') ' . $num;
    } elseif (!empty($num)) {
        return $num;
    }
    return 'Sin registrar';
}
}

if (!function_exists('obtenerNombreProvincia')) {
function obtenerNombreProvincia($cod) {
    $cod = trim((string)$cod);
    $provincias = array(
        '16' => 'Río Negro',
        '01' => 'Buenos Aires',
        '02' => 'Catamarca',
        '03' => 'Córdoba',
        '04' => 'Corrientes',
        '05' => 'Entre Ríos',
        '06' => 'Jujuy',
        '07' => 'Mendoza',
        '08' => 'La Rioja',
        '09' => 'Salta',
        '10' => 'San Juan',
        '11' => 'San Luis',
        '12' => 'Santa Fe',
        '13' => 'Santiago del Estero',
        '14' => 'Tucumán',
        '15' => 'Chaco',
        '17' => 'Chubut',
        '18' => 'Formosa',
        '19' => 'Misiones',
        '20' => 'Neuquén',
        '21' => 'La Pampa',
        '22' => 'Santa Cruz',
        '23' => 'Tierra del Fuego',
        '00' => 'Ciudad Autónoma de Bs. As.'
    );
    return isset($provincias[$cod]) ? $provincias[$cod] : ('Provincia ' . $cod);
}
}

if (!function_exists('obtenerEstadoCivilTexto')) {
function obtenerEstadoCivilTexto($cod) {
    $cod = strtoupper(trim((string)$cod));
    switch ($cod) {
        case 'S': return 'Soltero/a';
        case 'C': return 'Casado/a';
        case 'V': return 'Viudo/a';
        case 'D': return 'Divorciado/a';
        case 'O': return 'Otro';
        default: return !empty($cod) ? $cod : 'No informado';
    }
}
}

if (!function_exists('obtenerTipoDocumentoTexto')) {
function obtenerTipoDocumentoTexto($cod) {
    $cod = trim((string)$cod);
    switch ($cod) {
        case '01':
        case '03':
        case '1':
        case '3':
            return 'DNI';
        case '02':
        case '2':
            return 'LC';
        case '04':
        case '4':
            return 'LE';
        case '05':
        case '5':
            return 'Pasaporte';
        default:
            return !empty($cod) ? ('Doc. Tipo ' . $cod) : 'DNI';
    }
}
}

// -----------------------------------------------------------------------------
// LOCALIZACIÓN Y SELECCIÓN DEL ARCHIVO DE RESPUESTA CABAL
// -----------------------------------------------------------------------------

$mensajeFeedback = '';
$tipoFeedback = '';
$archivoSeleccionado = '';
$nombreArchivoMostrado = '';
$fechaArchivoSubida = '';
$tamanoArchivo = 0;
$fueSubidoReciente = false;

// Revisar metadatos si existen
$metaFile = $targetDir . "RespuestaAltas_meta.json";
$metadata = array();
if (file_exists($metaFile)) {
    $contenidoMeta = @file_get_contents($metaFile);
    if (!empty($contenidoMeta)) {
        $metadata = json_decode($contenidoMeta, true);
    }
}

// 1. Manejo de subida directa por el operador (Formulario / Drag & Drop)
if (isset($_POST['accion']) && $_POST['accion'] === 'subir_archivo') {
    if (isset($_FILES['archivo_out_altas']) && $_FILES['archivo_out_altas']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['archivo_out_altas']['tmp_name'];
        $fileName = basename($_FILES['archivo_out_altas']['name']);
        $fileSize = (int)$_FILES['archivo_out_altas']['size'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileSize > 0 && in_array($ext, array('txt', '782', 'out', 'dat'))) {
            $destino = $targetDir . "RespuestaAltas_adjuntado.txt";
            if (move_uploaded_file($fileTmp, $destino)) {
                $archivoSeleccionado = $destino;
                $nombreArchivoMostrado = $fileName;
                $tamanoArchivo = $fileSize;
                $fechaArchivoSubida = date('Y-m-d H:i:s');
                $fueSubidoReciente = true;

                // Actualizar metadata
                $metadata = array(
                    'nombre_original' => $fileName,
                    'fecha_subida'    => $fechaArchivoSubida,
                    'tamano'          => $fileSize
                );
                @file_put_contents($metaFile, json_encode($metadata));

                $mensajeFeedback = "Archivo <strong>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</strong> recibido y analizado con éxito.";
                $tipoFeedback = "success";
            } else {
                $mensajeFeedback = "No se pudo almacenar el archivo en el servidor. Revise permisos en archivos/.";
                $tipoFeedback = "danger";
            }
        } else {
            $mensajeFeedback = "El archivo debe ser en formato de texto plano (.txt o .782).";
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

// 3. Fallback automático a los archivos estándar más recientes
if (empty($archivoSeleccionado)) {
    if (file_exists($targetDir . "RespuestaAltas_adjuntado.txt")) {
        $archivoSeleccionado = $targetDir . "RespuestaAltas_adjuntado.txt";
        $nombreArchivoMostrado = !empty($metadata['nombre_original']) ? $metadata['nombre_original'] : '782_out_altas_tpc.txt';
        $tamanoArchivo = filesize($archivoSeleccionado);
        $fechaArchivoSubida = !empty($metadata['fecha_subida']) ? $metadata['fecha_subida'] : date('Y-m-d H:i:s', filemtime($archivoSeleccionado));
    } elseif (file_exists($targetDir . "MovAltas.txt")) {
        $archivoSeleccionado = $targetDir . "MovAltas.txt";
        $nombreArchivoMostrado = 'MovAltas.txt';
        $tamanoArchivo = filesize($archivoSeleccionado);
        $fechaArchivoSubida = date('Y-m-d H:i:s', filemtime($archivoSeleccionado));
    }
}

// Listar todos los archivos de respuesta disponibles en archivos/
$archivosDisponibles = array();
if (is_dir($targetDir)) {
    $scan = scandir($targetDir);
    if ($scan) {
        foreach ($scan as $f) {
            if ($f === '.' || $f === '..') continue;
            $fLower = strtolower($f);
            if (strpos($fLower, 'altas') !== false || strpos($fLower, 'movaltas') !== false || strpos($fLower, '782') !== false || strpos($fLower, 'respuesta') !== false) {
                if (is_file($targetDir . $f) && (substr($fLower, -4) === '.txt' || substr($fLower, -4) === '.782')) {
                    $archivosDisponibles[] = $f;
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// PARSING DEL ARCHIVO CABAL 782_OUT_ALTAS_TPC (196 BYTES)
// -----------------------------------------------------------------------------

$beneficiarios = array();
$stats = array(
    'total_titulares'   => 0,
    'aceptadas'         => 0,
    'rechazadas'        => 0,
    'en_padron_bd'      => 0,
    'pendientes_bd'     => 0,
    'entidad_detectada' => '782',
    'sucursal_detectada'=> '001',
    'fecha_lote'        => '',
    'fecha_lote_raw'    => '',
    'lineas_totales'    => 0,
    'motivos_rechazo'   => array()
);

if (!empty($archivoSeleccionado) && file_exists($archivoSeleccionado)) {
    $lineasRaw = file($archivoSeleccionado, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineasRaw !== false) {
        $stats['lineas_totales'] = count($lineasRaw);
        $indice = 0;
        $R1 = array();
        $R2 = array();

        // Preparar sentencia PDO para verificar sincronización con BD
        $stmtCheckBD = null;
        if (isset($conn) && $conn instanceof PDO) {
            $stmtCheckBD = $conn->prepare("SELECT id, FechaProceso, NumeroCuenta, NumeroTarjeta FROM beneficiario WHERE NumeroDocumento = :dni OR (Cuit <> '' AND Cuit = :cuit) LIMIT 1");
        }

        foreach ($lineasRaw as $linea) {
            $linea = rtrim($linea, "\r\n");
            if (strlen($linea) < 22) continue;

            // Detección posicional del código de operación (01, 02, 03)
            $op = substr($linea, 20, 2);
            if ($op !== "01" && $op !== "02" && $op !== "03") {
                $op = substr($linea, 22, 2);
            }
            if ($op !== "01" && $op !== "02" && $op !== "03") {
                if (substr($linea, 20, 1) === "1" || substr($linea, 20, 1) === "2" || substr($linea, 20, 1) === "3") {
                    $op = "0" . substr($linea, 20, 1);
                } elseif (substr($linea, 21, 1) === "1" || substr($linea, 21, 1) === "2" || substr($linea, 21, 1) === "3") {
                    $op = "0" . substr($linea, 21, 1);
                }
            }

            // REGISTRO 01: TARJETA, DATOS FILIATORIOS Y DOMICILIO
            if ($op === "01") {
                $R1 = array();
                $R1['tarjeta']       = trim(substr($linea, 4, 16));
                $R1['apynom']        = trim(substr($linea, 28, 25));
                $R1['calle']         = trim(substr($linea, 53, 25));
                $R1['numero']        = trim(substr($linea, 78, 5));
                $R1['piso']          = trim(substr($linea, 83, 2));
                $R1['depto']         = trim(substr($linea, 85, 2));
                $R1['localidad']     = trim(substr($linea, 87, 30));
                $R1['provincia']     = trim(substr($linea, 117, 2));
                $R1['pais']          = trim(substr($linea, 121, 2));
                $R1['sexo']          = trim(substr($linea, 122, 1));
                $R1['formulario']    = trim(substr($linea, 123, 5));
                $R1['ingreso']       = trim(substr($linea, 128, 1));
                $R1['rechazo']       = (strlen($linea) >= 165) ? trim(substr($linea, 129, 36)) : '';
                $R1['entidad']       = (strlen($linea) >= 174) ? trim(substr($linea, 168, 6)) : '782001';
                $R1['fecha_proceso'] = (strlen($linea) >= 180) ? trim(substr($linea, 174, 6)) : '';
                $R1['cuenta']        = (strlen($linea) >= 196) ? trim(substr($linea, 180, 16)) : (strlen($linea) >= 180 ? trim(substr($linea, 0, 16)) : '');

                if (empty($stats['fecha_lote_raw']) && !empty($R1['fecha_proceso'])) {
                    $stats['fecha_lote_raw'] = $R1['fecha_proceso'];
                    $stats['fecha_lote'] = formatearFechaProcesoCabal($R1['fecha_proceso']);
                }
                if (!empty($R1['entidad']) && strlen($R1['entidad']) === 6) {
                    $stats['entidad_detectada'] = substr($R1['entidad'], 0, 3);
                    $stats['sucursal_detectada']= substr($R1['entidad'], 3, 3);
                }
            }

            // REGISTRO 02: TELÉFONO, NACIMIENTO, DOCUMENTO Y CUIT
            if ($op === "02") {
                $R2 = array();
                $R2['cp']        = trim(substr($linea, 22, 8));
                $R2['tel_carac'] = trim(substr($linea, 30, 5));
                $R2['tel_num']   = trim(substr($linea, 35, 8));
                $R2['est_civil'] = trim(substr($linea, 43, 1));
                $R2['fec_nac']   = trim(substr($linea, 44, 8));
                $R2['tipo_doc']  = trim(substr($linea, 52, 2));
                $R2['dni']       = trim(substr($linea, 54, 11));
                $R2['pcia_doc']  = trim(substr($linea, 65, 2));
                $R2['cuit']      = trim(substr($linea, 67, 11));
                $R2['ingreso']   = (strlen($linea) >= 129) ? trim(substr($linea, 128, 1)) : '';
                $R2['rechazo']   = (strlen($linea) >= 165) ? trim(substr($linea, 129, 36)) : '';

                if (empty($R1['cuenta']) && strlen($linea) >= 196) {
                    $R1['cuenta'] = trim(substr($linea, 180, 16));
                }
            }

            // REGISTRO 03: CONSOLIDACIÓN DEL TITULAR
            if ($op === "03") {
                $indice++;

                // Separar Apellido y Nombre
                $apynom = isset($R1['apynom']) ? $R1['apynom'] : '';
                $posBarra = strpos($apynom, '/');
                if ($posBarra !== false) {
                    $apellido = trim(substr($apynom, 0, $posBarra));
                    $nombre   = trim(substr($apynom, $posBarra + 1));
                } else {
                    $apellido = $apynom;
                    $nombre   = '';
                }

                $dniNorm   = isset($R2['dni']) ? ltrim(trim($R2['dni']), '0') : '';
                $cuitNorm  = isset($R2['cuit']) ? preg_replace('/[^0-9]/', '', $R2['cuit']) : '';
                $cuentaAsignada = !empty($R1['cuenta']) ? $R1['cuenta'] : (isset($R1['tarjeta']) ? $R1['tarjeta'] : '');
                $tarjetaAsignada = isset($R1['tarjeta']) ? $R1['tarjeta'] : '';

                // Formateo estricto con enmascaramiento central obligatorio
                $tarjetaEnmascarada = formatearTarjetaCredito($tarjetaAsignada);

                // Detección de Aceptación o Rechazo
                $codIngreso = isset($R2['ingreso']) && $R2['ingreso'] !== '' ? $R2['ingreso'] : (isset($R1['ingreso']) ? $R1['ingreso'] : '0');
                $codRechazo = isset($R2['rechazo']) && $R2['rechazo'] !== '' ? $R2['rechazo'] : (isset($R1['rechazo']) ? $R1['rechazo'] : '');

                $ingresoLimpio = trim($codIngreso);
                $rechazoLimpio = trim($codRechazo);
                $tieneErrorRechazo = (!empty($rechazoLimpio) && preg_replace('/[0\s]/', '', $rechazoLimpio) !== '');

                $erroresDetalle = parsearCodigosRechazoAltas($codRechazo);

                if ($ingresoLimpio === '1' || $tieneErrorRechazo) {
                    $esAceptada = false;
                    if (empty($erroresDetalle) && $ingresoLimpio === '1') {
                        $erroresDetalle[] = array(
                            'codigo' => 'REC',
                            'titulo' => 'Rechazada por Cabal',
                            'desc'   => 'Alta rechazada por control general de validación en la plataforma de Cabal.'
                        );
                    }
                } elseif ($ingresoLimpio === '0') {
                    $esAceptada = true;
                } else {
                    $esAceptada = !$tieneErrorRechazo;
                }

                // Acumulador de motivos de rechazo
                if (!$esAceptada && !empty($erroresDetalle)) {
                    foreach ($erroresDetalle as $errItem) {
                        $cErr = $errItem['codigo'];
                        if (!isset($stats['motivos_rechazo'][$cErr])) {
                            $stats['motivos_rechazo'][$cErr] = array(
                                'codigo'   => $cErr,
                                'titulo'   => $errItem['titulo'],
                                'desc'     => $errItem['desc'],
                                'cantidad' => 0
                            );
                        }
                        $stats['motivos_rechazo'][$cErr]['cantidad']++;
                    }
                }

                // Cotejo con tabla beneficiario
                $estaEnBD = false;
                $idBD = null;
                $fechaProcesoBD = '';
                if ($stmtCheckBD && (!empty($dniNorm) || !empty($cuitNorm))) {
                    try {
                        $stmtCheckBD->execute(array(':dni' => $dniNorm, ':cuit' => $cuitNorm));
                        $rowBD = $stmtCheckBD->fetch(PDO::FETCH_ASSOC);
                        if ($rowBD) {
                            $estaEnBD = true;
                            $idBD = $rowBD['id'];
                            $fechaProcesoBD = $rowBD['FechaProceso'];
                        }
                    } catch (Exception $e) {
                        // Continuar si falla consulta
                    }
                }

                // Estadísticas
                $stats['total_titulares']++;
                if ($esAceptada) {
                    $stats['aceptadas']++;
                } else {
                    $stats['rechazadas']++;
                }

                if ($estaEnBD) {
                    $stats['en_padron_bd']++;
                } else {
                    $stats['pendientes_bd']++;
                }

                $beneficiarios[] = array(
                    'indice'               => $indice,
                    'formulario'           => isset($R1['formulario']) ? $R1['formulario'] : '',
                    'apellido'             => $apellido,
                    'nombre'               => $nombre,
                    'apynom_completo'      => $apynom,
                    'tarjeta_raw'          => $tarjetaAsignada,
                    'tarjeta_enmascarada'  => $tarjetaEnmascarada,
                    'cuenta'               => $cuentaAsignada,
                    'tipo_doc'             => isset($R2['tipo_doc']) ? obtenerTipoDocumentoTexto($R2['tipo_doc']) : 'DNI',
                    'dni'                  => $dniNorm,
                    'dni_formateado'       => formatearNumeroDocumento($dniNorm),
                    'cuit'                 => $cuitNorm,
                    'cuit_formateado'      => formatearCuilCuit($cuitNorm),
                    'calle'                => isset($R1['calle']) ? $R1['calle'] : '',
                    'numero'               => isset($R1['numero']) ? $R1['numero'] : '',
                    'piso'                 => isset($R1['piso']) ? $R1['piso'] : '',
                    'depto'                => isset($R1['depto']) ? $R1['depto'] : '',
                    'localidad'            => isset($R1['localidad']) ? $R1['localidad'] : '',
                    'provincia_cod'        => isset($R1['provincia']) ? $R1['provincia'] : '16',
                    'provincia_nombre'     => isset($R1['provincia']) ? obtenerNombreProvincia($R1['provincia']) : 'Río Negro',
                    'cp'                   => isset($R2['cp']) ? $R2['cp'] : '',
                    'tel_carac'            => isset($R2['tel_carac']) ? $R2['tel_carac'] : '',
                    'tel_num'              => isset($R2['tel_num']) ? $R2['tel_num'] : '',
                    'telefono_formateado'  => formatearTelefonoCompleto(isset($R2['tel_carac']) ? $R2['tel_carac'] : '', isset($R2['tel_num']) ? $R2['tel_num'] : ''),
                    'fec_nac'              => isset($R2['fec_nac']) ? $R2['fec_nac'] : '',
                    'fec_nac_formateada'   => formatearFechaNacimiento(isset($R2['fec_nac']) ? $R2['fec_nac'] : ''),
                    'sexo'                 => (isset($R1['sexo']) && strtoupper($R1['sexo']) === 'M') ? 'Masculino' : 'Femenino',
                    'estado_civil'         => isset($R2['est_civil']) ? obtenerEstadoCivilTexto($R2['est_civil']) : 'No informado',
                    'fecha_proceso_raw'    => isset($R1['fecha_proceso']) ? $R1['fecha_proceso'] : '',
                    'fecha_proceso'        => formatearFechaProcesoCabal(isset($R1['fecha_proceso']) ? $R1['fecha_proceso'] : ''),
                    'es_aceptada'          => $esAceptada,
                    'cod_ingreso'          => $codIngreso,
                    'cod_rechazo'          => $codRechazo,
                    'errores_detalle'      => $erroresDetalle,
                    'esta_en_bd'           => $estaEnBD,
                    'id_bd'                => $idBD,
                    'fecha_proceso_bd'     => $fechaProcesoBD
                );

                $R1 = array();
                $R2 = array();
            }
        }
    }
}

// Fallback de fecha de lote si no vino en los registros
if (empty($stats['fecha_lote'])) {
    if (!empty($fechaArchivoSubida)) {
        $stats['fecha_lote'] = date('d/m/Y', strtotime($fechaArchivoSubida));
    } else {
        $stats['fecha_lote'] = date('d/m/Y');
    }
}

// -----------------------------------------------------------------------------
// GENERACIÓN Y DESCARGA DE ARCHIVO EML (.eml)
// -----------------------------------------------------------------------------

if (isset($_GET['accion']) && $_GET['accion'] === 'descargar_eml') {
    $fechaEmision = $stats['fecha_lote'];
    $fechaHoy = date('d/m/Y H:i');
    $asuntoEmail = "[SeNAF] Notificación de Altas y Emisión de Tarjetas Cabal FSR - Lote " . $fechaEmision;
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
    .header-banner { background: #ffffff; padding: 24px 30px; color: #0f172a; border-bottom: 4px solid #2563eb; }
    .header-banner h1 { margin: 10px 0 0 0; font-size: 19px; font-weight: 700; color: #0f172a; letter-spacing: 0.3px; }
    .header-banner p { margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 600; }
    .badge-institucional { display: inline-block; background: #2563eb; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-top: 8px; text-transform: uppercase; }
    .content-area { padding: 28px 30px; font-size: 14px; line-height: 1.6; }
    .kpi-container { display: table; width: 100%; margin: 20px 0; border-collapse: separate; border-spacing: 10px; }
    .kpi-box { display: table-cell; width: 33.33%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; }
    .kpi-num { font-size: 22px; font-weight: bold; color: #1e3a8a; }
    .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-top: 4px; }
    .table-beneficiarios { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
    .table-beneficiarios th { background: #1e293b; color: #ffffff; text-align: left; padding: 10px; font-size: 12px; }
    .table-beneficiarios td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    .table-beneficiarios tr:nth-child(even) { background-color: #f8fafc; }
    .tarjeta-code { font-family: monospace; font-size: 13px; font-weight: bold; color: #1d4ed8; background: #eff6ff; padding: 2px 6px; border-radius: 4px; border: 1px solid #bfdbfe; }
    .cuenta-code { font-family: monospace; font-size: 12px; color: #475569; }
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
        <p>Por medio de la presente se notifica la recepción y procesamiento del lote de respuesta bancaria de <strong>Altas de Tarjetas Cabal (782_out_altas)</strong> emitido por el host de Cabal con fecha de proceso <strong><?php echo htmlspecialchars($stats['fecha_lote']); ?></strong> (Archivo: <code><?php echo htmlspecialchars($nombreArchivoMostrado); ?></code>).</p>

        <div class="kpi-container">
            <div class="kpi-box">
                <div class="kpi-num"><?php echo $stats['total_titulares']; ?></div>
                <div class="kpi-label">Titulares Procesados</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #059669;">
                <div class="kpi-num" style="color: #059669;"><?php echo $stats['aceptadas']; ?></div>
                <div class="kpi-label">Tarjetas Creadas</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #dc2626;">
                <div class="kpi-num" style="color: #dc2626;"><?php echo $stats['rechazadas']; ?></div>
                <div class="kpi-label">Rechazadas Cabal</div>
            </div>
        </div>

        <h3 style="font-size: 15px; color: #0f172a; margin-top: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px;">
            Nómina de Beneficiarios y Tarjetas Asignadas (Números Enmascarados)
        </h3>

        <table class="table-beneficiarios">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>Titular / Beneficiario</th>
                    <th>DNI / CUIL</th>
                    <th>Tarjeta Asignada</th>
                    <th>N° de Cuenta</th>
                    <th>Localidad</th>
                    <th>Resultado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($beneficiarios as $b): ?>
                <tr>
                    <td><?php echo $b['formulario']; ?></td>
                    <td><strong><?php echo htmlspecialchars($b['apellido'] . ', ' . $b['nombre']); ?></strong><br><small style="color:#64748b;"><?php echo htmlspecialchars($b['calle'] . ' ' . $b['numero']); ?></small></td>
                    <td><?php echo htmlspecialchars($b['tipo_doc'] . ' ' . $b['dni_formateado']); ?><br><small style="color:#64748b;"><?php echo htmlspecialchars($b['cuit_formateado']); ?></small></td>
                    <td><span class="tarjeta-code"><?php echo htmlspecialchars($b['tarjeta_enmascarada']); ?></span></td>
                    <td><span class="cuenta-code"><?php echo htmlspecialchars($b['cuenta']); ?></span></td>
                    <td><?php echo htmlspecialchars($b['localidad']); ?><br><small style="color:#64748b;"><?php echo htmlspecialchars($b['provincia_nombre']); ?></small></td>
                    <td>
                        <?php if ($b['es_aceptada']): ?>
                            <span class="badge-ok">&check; Creada / Aceptada</span>
                        <?php else: ?>
                            <span class="badge-fail">&cross; Rechazada</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($stats['motivos_rechazo'])): ?>
        <div style="margin-top: 24px; padding: 14px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 6px;">
            <strong style="color: #9f1239; font-size: 13px;">Motivos de Rechazo Informados por Cabal (Anexo 12):</strong>
            <ul style="margin: 8px 0 0 18px; padding: 0; font-size: 12px; color: #881337;">
                <?php foreach ($stats['motivos_rechazo'] as $motivo): ?>
                    <li><strong>[Código <?php echo htmlspecialchars($motivo['codigo']); ?>] <?php echo htmlspecialchars($motivo['titulo']); ?></strong> (<?php echo $motivo['cantidad']; ?> casos): <?php echo htmlspecialchars($motivo['desc']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <p style="margin-top: 28px; font-size: 12px; color: #64748b;">
            <strong>Indicaciones de Distribución y Entrega:</strong><br>
            Los plásticos han sido generados bajo la modalidad de entrega en Filial bancaria con sobre PIN telefónico. Se coordinará su remisión a cada delegación zonal según el cronograma operativo de SeNAF.
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

    // Estructura completa del archivo .EML (RFC 822 / MIME)
    // Con X-Unsent: 1 para abrir directamente en modo de redacción/envío en Mozilla Thunderbird
    $eml = "From: \"SeNAF - Cabal FSR\" <senaf-cabal@rionegro.gov.ar>\r\n";
    if (!empty($destinatario)) {
        $eml .= "To: " . $destinatario . "\r\n";
    } else {
        $eml .= "To: \r\n";
    }
    $eml .= "Subject: =?UTF-8?B?" . base64_encode($asuntoEmail) . "?=\r\n";
    $eml .= "Date: " . date('r') . "\r\n";
    $eml .= "Message-ID: <altas-" . time() . "-" . rand(1000, 9999) . "@senaf.rionegro.gov.ar>\r\n";
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

    $nombreDescargaEml = "Informe_Altas_SeNAF_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $stats['fecha_lote']) . ".eml";
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
    <title>Informe de Altas Cabal (782_out_altas) | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        /* MEMBRETE INSTITUCIONAL */
        .institucional-header-card {
            background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-800) 50%, var(--primary-900) 100%);
            border-radius: var(--radius-xl);
            padding: 30px 36px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .institucional-header-card::after {
            content: '';
            position: absolute;
            right: -30px;
            bottom: -30px;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .header-brand-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-logo-box {
            width: 58px;
            height: 58px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            backdrop-filter: blur(8px);
        }

        .header-logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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
            color: var(--primary-200);
            font-weight: 500;
        }

        .header-meta-chips {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .meta-chip {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            background: rgba(16, 185, 129, 0.25);
            border-color: rgba(16, 185, 129, 0.5);
            color: #a7f3d0;
        }

        /* GRID DE KPIS */
        .kpi-grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .stat-card-modern.success-border { border-left: 4px solid var(--emerald-500); }
        .stat-card-modern.danger-border  { border-left: 4px solid #ef4444; }
        .stat-card-modern.primary-border { border-left: 4px solid var(--primary-600); }
        .stat-card-modern.amber-border   { border-left: 4px solid var(--amber-500); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .stat-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
        }

        .stat-subtext {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 8px;
        }

        /* BARRA DE HERRAMIENTAS Y FILTROS */
        .toolbar-panel {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
        }

        .search-group {
            position: relative;
            flex: 1;
            min-width: 280px;
            max-width: 480px;
        }

        .search-group svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input-modern {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border-light);
            background: var(--surface-subtle);
            font-size: 0.88rem;
            color: var(--text-main);
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }

        .search-input-modern:focus {
            outline: none;
            border-color: var(--primary-600);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action-tool {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: var(--radius-md);
            font-size: 0.84rem;
            font-weight: 700;
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
        }

        .btn-action-tool.emerald {
            background: var(--emerald-600);
            color: #ffffff;
        }
        .btn-action-tool.emerald:hover {
            background: var(--emerald-700);
        }

        .btn-action-tool.secondary {
            background: var(--surface-card);
            border-color: var(--border-light);
            color: var(--text-main);
        }
        .btn-action-tool.secondary:hover {
            background: var(--surface-subtle);
            border-color: #cbd5e1;
        }

        /* TABLA DE BENEFICIARIOS */
        .table-responsive-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .table-altas {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            text-align: left;
        }

        .table-altas thead th {
            background: var(--surface-subtle);
            padding: 12px 14px;
            font-weight: 700;
            color: var(--navy-700);
            border-bottom: 2px solid var(--border-light);
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.04em;
        }

        .table-altas tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
            color: var(--text-main);
        }

        .table-altas tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.03);
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

        .badge-status {
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-status.aceptada {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-status.rechazada {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-bd-status {
            font-size: 0.72rem;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            display: inline-block;
            margin-top: 3px;
        }
        .badge-bd-status.ok { background: #dcfce7; color: #15803d; }
        .badge-bd-status.pending { background: #fef9c3; color: #854d0e; }

        /* MODALES */
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
            background: linear-gradient(135deg, var(--navy-900), var(--primary-900));
            padding: 20px 26px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 26px;
        }

        /* PRINT STYLES */
        @media print {
            body { background: #ffffff !important; color: #000000 !important; }
            .top-nav, .toolbar-panel, .btn-action-tool, .nav-actions, .modal-overlay, .btn-modern-flotante { display: none !important; }
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

<!-- CABECERA DE NAVEGACIÓN -->
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
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Volver al Tablero
            </a>
            <a href="ProcesaNovedadesAltas.php" class="btn-nav-action" title="Ir al Paso 3: Validar respuesta de Altas">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                Paso 3: Validar Respuesta
            </a>
            <a href="ReporteAltaBeneficiarios.php" class="btn-nav-action" title="Ver catálogo general de beneficiarios">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                Padrón General
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
                    <h2>Informe Oficial de Altas de Tarjetas y Cuentas Cabal</h2>
                    <p>Secretaria de Niñez, Adolescencia y Familia (SeNAF) • Gobierno de Río Negro</p>
                </div>
            </div>

            <div class="header-meta-chips">
                <span class="meta-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Fecha Solicitud / Proceso: <strong><?php echo htmlspecialchars($stats['fecha_lote']); ?></strong>
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
            Este informe documenta formalmente la recepción y procesamiento de las novedades de alta devueltas por la plataforma bancaria Cabal (Anexo 2 - <code>782_out_altas_tpc</code> / <code>MovAltas.txt</code>). Los números de plástico se presentan estrictamente protegidos mediante enmascaramiento normativo de seguridad.
        </div>
    </div>

    <!-- TARJETAS KPIS -->
    <div class="kpi-grid-stats">
        <div class="stat-card-modern primary-border">
            <div class="stat-header">
                <span class="stat-title">Titulares en Lote</span>
                <span class="stat-icon" style="background:#eff6ff; color:#2563eb;">👥</span>
            </div>
            <div class="stat-value"><?php echo $stats['total_titulares']; ?></div>
            <div class="stat-subtext">Total de registros procesados</div>
        </div>

        <div class="stat-card-modern success-border">
            <div class="stat-header">
                <span class="stat-title">Tarjetas Creadas (Aceptadas)</span>
                <span class="stat-icon" style="background:#ecfdf5; color:#059669;">💳</span>
            </div>
            <div class="stat-value" style="color:#059669;"><?php echo $stats['aceptadas']; ?></div>
            <div class="stat-subtext">Plásticos y cuentas autorizados</div>
        </div>

        <div class="stat-card-modern danger-border">
            <div class="stat-header">
                <span class="stat-title">Altas Rechazadas</span>
                <span class="stat-icon" style="background:#fef2f2; color:#dc2626;">⚠️</span>
            </div>
            <div class="stat-value" style="color:<?php echo $stats['rechazadas'] > 0 ? '#dc2626' : 'var(--text-muted)'; ?>;">
                <?php echo $stats['rechazadas']; ?>
            </div>
            <div class="stat-subtext">Observadas con código Anexo 12</div>
        </div>
    </div>

    <!-- BARRA DE HERRAMIENTAS Y FILTRADO INTERACTIVO -->
    <div class="toolbar-panel">
        <div class="search-group">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="filtroRapido" class="search-input-modern" placeholder="Buscar por Apellido, Nombre, DNI, Localidad o Cuenta..." onkeyup="filtrarTablaBeneficiarios()">
        </div>

        <div class="toolbar-actions">
            <!-- BOTÓN VISTA PREVIA CORREO EML -->
            <button type="button" class="btn-action-tool emerald" onclick="abrirModalEml()" title="Previsualizar correo formal para envío institucional a SeNAF">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Vista Previa Correo SeNAF
            </button>

            <!-- BOTÓN DESCARGAR ARCHIVO EML -->
            <a href="InformeAlta.php?accion=descargar_eml" class="btn-action-tool primary" title="Descargar archivo .eml con cabeceras MIME y cuerpo HTML para Outlook/Thunderbird">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Descargar EML (.eml)
            </a>

            <!-- BOTÓN IMPRIMIR / PDF -->
            <button type="button" class="btn-action-tool secondary" onclick="window.print()" title="Imprimir informe en papel membretado o guardar en PDF">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Imprimir / PDF
            </button>

            <!-- BOTÓN CAMBIAR / SUBIR OTRO ARCHIVO -->
            <button type="button" class="btn-action-tool secondary" onclick="abrirModalArchivos()" title="Cargar otro archivo 782_out_altas o MovAltas">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Cambiar Archivo
            </button>
        </div>
    </div>

    <!-- SECCIÓN DE MOTIVOS DE RECHAZO CABAL (SI EXISTIERAN) -->
    <?php if (!empty($stats['motivos_rechazo'])): ?>
        <div class="modern-card" style="padding: 20px 24px; margin-bottom: 24px; background: #fff1f2; border: 1.5px solid #fecdd3; border-radius: var(--radius-lg);">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <span style="font-size: 1.4rem;">⚠️</span>
                <h4 style="margin: 0; color: #9f1239; font-size: 1.05rem; font-weight: 800;">
                    Observaciones y Motivos de Rechazo Bancario Cabal (Anexo 12)
                </h4>
            </div>
            <p style="margin: 0 0 14px 0; font-size: 0.85rem; color: #881337;">
                Las siguientes solicitudes fueron observadas por el host de Cabal y no generaron tarjeta física. Se requiere subsanar los datos en el padrón para una nueva emisión:
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 12px;">
                <?php foreach ($stats['motivos_rechazo'] as $motivo): ?>
                    <div style="background: #ffffff; padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid #fecaca;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #b91c1c; font-size: 0.9rem;">Código <?php echo htmlspecialchars($motivo['codigo']); ?>: <?php echo htmlspecialchars($motivo['titulo']); ?></strong>
                            <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                                <?php echo $motivo['cantidad']; ?> casos
                            </span>
                        </div>
                        <p style="margin: 6px 0 0 0; font-size: 0.8rem; color: var(--text-muted);">
                            <?php echo htmlspecialchars($motivo['desc']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABLA DETALLADA DE BENEFICIARIOS RECIÉN CREADOS -->
    <div class="table-responsive-card">
        <table class="table-altas" id="tablaBeneficiariosAltas">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 18%;">Titular Beneficiario</th>
                    <th style="width: 12%;">Identificación</th>
                    <th style="width: 16%;">Tarjeta Asignada</th>
                    <th style="width: 15%;">Cuenta Cabal</th>
                    <th style="width: 15%;">Ubicación y Contacto</th>
                    <th style="width: 10%;">F. Nac. / Sexo</th>
                    <th style="width: 10%;">Estado Lote</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($beneficiarios)): ?>
                    <?php foreach ($beneficiarios as $b): ?>
                        <tr class="fila-beneficiario <?php echo $b['es_aceptada'] ? 'fila-aceptada' : 'fila-rechazada'; ?>">
                            <td style="font-weight: 700; color: var(--text-muted);">
                                <?php echo htmlspecialchars($b['formulario']); ?>
                            </td>

                            <td>
                                <strong style="font-size: 0.92rem; color: var(--navy-800);">
                                    <?php echo htmlspecialchars($b['apellido'] . ', ' . $b['nombre']); ?>
                                </strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    <?php echo htmlspecialchars($b['calle'] . ' ' . $b['numero'] . (!empty($b['piso']) ? ' P. ' . $b['piso'] : '') . (!empty($b['depto']) ? ' D. ' . $b['depto'] : '')); ?>
                                </div>
                                <?php if ($b['esta_en_bd']): ?>
                                    <span class="badge-bd-status ok" title="Registrado en base de datos beneficiario">
                                        &check; En Padrón (ID #<?php echo $b['id_bd']; ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="badge-bd-status pending" title="No figura aún en padrón de beneficiarios">
                                        ⚠️ Pendiente de incorporación
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div><strong><?php echo htmlspecialchars($b['tipo_doc'] . ' ' . $b['dni_formateado']); ?></strong></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">CUIL: <?php echo htmlspecialchars($b['cuit_formateado']); ?></div>
                            </td>

                            <td>
                                <span class="badge-tarjeta" title="Número de plástico Cabal (Dígitos centrales enmascarados)">
                                    <?php echo htmlspecialchars($b['tarjeta_enmascarada']); ?>
                                </span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                    BIN: <code><?php echo htmlspecialchars(substr($b['tarjeta_raw'], 0, 6)); ?></code> &bull; Entidad: 782
                                </div>
                            </td>

                            <td>
                                <span class="badge-cuenta" title="Número de cuenta Cabal">
                                    <?php echo htmlspecialchars($b['cuenta']); ?>
                                </span>
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px;">
                                    F. Proceso: <?php echo htmlspecialchars($b['fecha_proceso']); ?>
                                </div>
                            </td>

                            <td>
                                <strong><?php echo htmlspecialchars($b['localidad']); ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($b['provincia_nombre']); ?> (CP <?php echo htmlspecialchars($b['cp']); ?>)
                                </div>
                                <div style="font-size: 0.78rem; color: var(--primary-700); margin-top: 2px;">
                                    📞 <?php echo htmlspecialchars($b['telefono_formateado']); ?>
                                </div>
                            </td>

                            <td>
                                <div><?php echo htmlspecialchars($b['fec_nac_formateada']); ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($b['sexo'] . ' &bull; ' . $b['estado_civil']); ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($b['es_aceptada']): ?>
                                    <span class="badge-status aceptada">
                                        &check; Aceptada / Creada
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status rechazada" title="<?php echo !empty($b['cod_rechazo']) ? htmlspecialchars($b['cod_rechazo']) : 'Rechazada por control Cabal'; ?>">
                                        &cross; Rechazada
                                    </span>
                                    <?php if (!empty($b['errores_detalle'])): ?>
                                        <div style="font-size: 0.72rem; color: #b91c1c; margin-top: 3px;">
                                            <?php foreach ($b['errores_detalle'] as $ed): ?>
                                                <div>&bull; <?php echo htmlspecialchars($ed['codigo'] . ': ' . $ed['titulo']); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 48px 20px;">
                            <div style="font-size: 2.2rem; margin-bottom: 10px;">📂</div>
                            <h4 style="margin: 0; color: var(--navy-800);">No se detectó ningún archivo de respuesta procesado</h4>
                            <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 500px; margin: 8px auto 20px;">
                                Puede arrastrar o seleccionar el archivo <code>782_out_altas_tpc_*.txt</code> o <code>MovAltas.txt</code> emitido por Cabal para generar este informe.
                            </p>
                            <button type="button" class="btn-action-tool primary" onclick="abrirModalArchivos()">
                                Adjuntar Archivo de Respuesta
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
            <a href="ProcesaNovedadesAltas.php" class="btn-nav-action">
                Ir a Paso 3: Validar Respuesta
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
                <div><strong>Asunto:</strong> [SeNAF] Notificación de Altas y Emisión de Tarjetas Cabal FSR - Lote <?php echo htmlspecialchars($stats['fecha_lote']); ?></div>
            </div>

            <!-- CUERPO VISUAL DEL CORREO -->
            <div style="border: 1px solid #cbd5e1; border-radius: var(--radius-md); overflow: hidden; background: #ffffff;">
                <div style="background: #ffffff; padding: 20px 24px; color: #0f172a; border-bottom: 3px solid #2563eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                        <img src="img/cabecera_ministerio.png" alt="Ministerio de Desarrollo Humano - Gobierno de Río Negro" style="max-height: 50px; height: auto; max-width: 70%; display: block; border: 0;">
                        <img src="img/logo_cabal_oficial.png" alt="Cabal" style="max-height: 46px; height: auto; display: block; border: 0;">
                    </div>
                    <h3 style="margin: 0; font-size: 1.15rem; color: #0f172a;">Secretaria de Niñez, Adolescencia y Familia • SeNAF</h3>
                    <p style="margin: 4px 0 0 0; font-size: 0.82rem; color: #64748b;">Programa Familia Rionegrina Solidaria • Cabal Entidad 782</p>
                </div>
                <div style="padding: 20px; font-size: 0.85rem; line-height: 1.6; color: #1e293b;">
                    <p>Se informa el procesamiento exitoso del lote de respuesta bancaria de <strong>Altas de Tarjetas Cabal</strong> correspondiente a la fecha <strong><?php echo htmlspecialchars($stats['fecha_lote']); ?></strong>. Se emitieron <strong><?php echo $stats['aceptadas']; ?> plásticos autorizados</strong> para los titulares detallados a continuación:</p>

                    <table style="width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 0.82rem;">
                        <thead>
                            <tr style="background: #1e293b; color: #ffffff;">
                                <th style="padding: 8px 10px; text-align: left;">Titular</th>
                                <th style="padding: 8px 10px; text-align: left;">DNI / CUIL</th>
                                <th style="padding: 8px 10px; text-align: left;">Tarjeta Asignada</th>
                                <th style="padding: 8px 10px; text-align: left;">Cuenta Cabal</th>
                                <th style="padding: 8px 10px; text-align: left;">Localidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beneficiarios as $b): ?>
                                <?php if ($b['es_aceptada']): ?>
                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 8px 10px;"><strong><?php echo htmlspecialchars($b['apellido'] . ', ' . $b['nombre']); ?></strong></td>
                                    <td style="padding: 8px 10px;"><?php echo htmlspecialchars($b['dni_formateado']); ?></td>
                                    <td style="padding: 8px 10px;"><code style="color: #1d4ed8; font-weight: bold;"><?php echo htmlspecialchars($b['tarjeta_enmascarada']); ?></code></td>
                                    <td style="padding: 8px 10px;"><code style="color: #475569;"><?php echo htmlspecialchars($b['cuenta']); ?></code></td>
                                    <td style="padding: 8px 10px;"><?php echo htmlspecialchars($b['localidad']); ?></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p style="margin-top: 20px; font-size: 0.78rem; color: #64748b;">
                        Modalidad: Entrega en Filial bancaria con sobre PIN telefónico. Se coordinará su remisión a cada delegación zonal.
                    </p>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN DENTRO DEL MODAL -->
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn-action-tool secondary" onclick="cerrarModalEml()">
                    Cerrar
                </button>
                <a href="InformeAlta.php?accion=descargar_eml" id="btnDescargarEmlModal" class="btn-action-tool primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Descargar Archivo EML (.eml)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- =======================================================================
     MODAL 2: SELECCIÓN Y SUBIDA DE ARCHIVO DE RESPUESTA
     ======================================================================= -->
<div class="modal-overlay" id="modalArchivosSelector">
    <div class="modal-card" style="max-width: 640px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.3rem;">📁</span>
                <h3 style="margin: 0; font-size: 1.1rem; color: #ffffff;">
                    Seleccionar o Subir Archivo 782_out_altas
                </h3>
            </div>
            <button type="button" onclick="cerrarModalArchivos()" style="background: none; border: none; color: #ffffff; font-size: 1.4rem; cursor: pointer;">✕</button>
        </div>

        <div class="modal-body">
            <!-- SELECCIONAR EXISTENTE -->
            <?php if (!empty($archivosDisponibles)): ?>
                <form action="InformeAlta.php" method="POST" style="margin-bottom: 24px;">
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
            <form action="InformeAlta.php" method="POST" enctype="multipart/form-data" id="formUploadDropzone">
                <input type="hidden" name="accion" value="subir_archivo">
                <div style="border: 2px dashed var(--primary-500); background: rgba(37, 99, 235, 0.04); border-radius: var(--radius-lg); padding: 32px 20px; text-align: center; cursor: pointer;" onclick="document.getElementById('inputDropzoneAltas').click()">
                    <div style="font-size: 2.2rem; margin-bottom: 8px;">📤</div>
                    <div style="font-weight: 700; color: var(--navy-800); font-size: 0.95rem;">
                        Haga clic o arrastre aquí el archivo de respuesta Cabal
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                        Admite <code>782_out_altas_tpc_*.txt</code> o <code>MovAltas.txt</code> (formato fijo 196 bytes)
                    </div>
                    <input type="file" name="archivo_out_altas" id="inputDropzoneAltas" style="display: none;" onchange="document.getElementById('formUploadDropzone').submit()">
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Filtro interactivo de la tabla de beneficiarios
    function filtrarTablaBeneficiarios() {
        var input = document.getElementById("filtroRapido");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("tablaBeneficiariosAltas");
        var tr = table.getElementsByClassName("fila-beneficiario");

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
        var base = 'InformeAlta.php?accion=descargar_eml';
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
