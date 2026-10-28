<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Entidad 782 - Cabal
 * 
 * Módulo: 2.4 - Verificar Lote NOVCAR (Acreditación de Saldo)
 * Archivo fuente: Novcar/novcar.782
 * 
 * Especificación Técnica de Validación Oficial: docs/NOVCAR.md
 * Anexo 3 - Novedad de Carga de tarjetas (Formato Fijo 80 Bytes)
 */

require_once("BD/Funciones.php");
require_once("BD/UsarGestion.php");

$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0775, true);
}

// Estado de archivo y auditoría (Validación estricta de archivos adjuntados)
$archivoAuditado = false;
$nombreArchivo = '';
$nombreArchivoMostrado = 'Ningún archivo adjuntado';
$tipoOrigen = 'ninguno';
$mensajeUpload = '';
$tipoUpload = '';

// Manejo de Descarga Directa del archivo auditado
if (isset($_GET['descargar']) && $_GET['descargar'] == '1') {
    $archivoDescarga = isset($_GET['archivo']) ? basename($_GET['archivo']) : '';
    $rutaDescarga = $targetDir . $archivoDescarga;
    if (!empty($archivoDescarga) && file_exists($rutaDescarga)) {
        header('Content-Type: text/plain; charset=ISO-8859-1');
        header('Content-Disposition: attachment; filename="' . $archivoDescarga . '"');
        header('Content-Length: ' . filesize($rutaDescarga));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($rutaDescarga);
        exit;
    }
}

// Manejo de formulario POST (Subida exclusiva por el operador en el adjunto superior)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    if ($accion === 'subir_novcar') {
        if (isset($_FILES['archivo_novcar']) && $_FILES['archivo_novcar']['error'] === UPLOAD_ERR_OK) {
            $nombreOriginal = basename($_FILES['archivo_novcar']['name']);
            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

            if (in_array($ext, array('782', 'txt', 'dat'))) {
                $fileTmp = $_FILES['archivo_novcar']['tmp_name'];
                $fileSize = $_FILES['archivo_novcar']['size'];

                if ($fileSize <= 0) {
                    $tipoUpload = 'danger';
                    $mensajeUpload = "El archivo adjuntado está vacío (0 bytes).";
                } else {
                    $destino = $targetDir . "novcar_verificar.782";
                    if (move_uploaded_file($fileTmp, $destino)) {
                        @chmod($destino, 0664);
                        $nombreArchivo = $destino;
                        $nombreArchivoMostrado = $nombreOriginal;
                        $tipoOrigen = 'subido';
                        $tipoUpload = 'success';
                        $archivoAuditado = true;
                        $mensajeUpload = "Archivo <strong>" . htmlspecialchars($nombreOriginal, ENT_QUOTES, 'UTF-8') . "</strong> recibido y auditado con éxito.";
                    } else {
                        $tipoUpload = 'danger';
                        $mensajeUpload = "Error al mover el archivo subido a la carpeta de trabajo.";
                    }
                }
            } else {
                $tipoUpload = 'danger';
                $mensajeUpload = "Formato no admitido. Se admiten únicamente archivos de lote bancario con extensiones <code>.782</code>, <code>.txt</code> o <code>.dat</code>.";
            }
        } else {
            $tipoUpload = 'danger';
            $errCode = isset($_FILES['archivo_novcar']['error']) ? $_FILES['archivo_novcar']['error'] : -1;
            $mensajeUpload = "No se pudo subir el archivo. (Código de error: {$errCode}).";
        }
    }
}

// Cargar padrón de beneficiarios para cruce ultrarrápido por Número de Cuenta
$cuentasPadron = array();
try {
    if (isset($conn) && $conn instanceof PDO) {
        $stmtB = $conn->query("SELECT NumeroCuenta, Apellido, Nombre, NumeroDocumento FROM beneficiario WHERE NumeroCuenta IS NOT NULL AND NumeroCuenta != ''");
        if ($stmtB) {
            while ($b = $stmtB->fetch(PDO::FETCH_ASSOC)) {
                $ctaClean = trim($b['NumeroCuenta']);
                if ($ctaClean !== '') {
                    $cuentasPadron[$ctaClean] = $b;
                }
            }
        }
    }
} catch (Exception $e) {
    // Continuar si hay error de DB
}

// Función auxiliar para formatear moneda
function pasaramoneda($valor) {
    $numero = (float)$valor;
    return "$" . number_format($numero, 2, ',', '.');
}

// Variables de auditoría técnica según docs/NOVCAR.md (Solo si hay archivo auditado)
$archivoExiste = $archivoAuditado && !empty($nombreArchivo) && file_exists($nombreArchivo) && is_readable($nombreArchivo) && filesize($nombreArchivo) > 0;
$tamanoArchivo = $archivoExiste ? filesize($nombreArchivo) : 0;
$esWindows = false;
$erroresGenerales = array();
$advertencias = array();
$filasDetalle = array();
$headerData = null;
$trailerData = null;

$statsAuditoria = array(
    'total_lineas'             => 0,
    'lineas_detalle'           => 0,
    'lineas_correctas_80'      => 0,
    'lineas_error_80'          => 0,
    'lineas_te04_ok'           => 0,
    'lineas_te04_error'        => 0,
    'total_compras_calculado'  => 0.0,
    'total_cajeros_calculado'  => 0.0,
    'total_trailer_compras'    => 0.0,
    'total_trailer_cajeros'    => 0.0,
    'cantidad_trailer'         => 0,
    'descuadre_cantidad'       => false,
    'descuadre_monto'          => false,
    'cuentas_en_padron'        => 0,
    'cuentas_no_encontradas'   => 0,
    'es_crlf'                  => false,
    'termina_con_salto'        => false,
    'ultimo_salto_desc'        => '',
    'lote_valido'              => false
);

if ($archivoExiste) {
    $contenidoCrudo = file_get_contents($nombreArchivo);
    $largoBytes = strlen($contenidoCrudo);

    // 1. Verificación: El archivo debe terminar obligatoriamente en un salto de línea CRLF (\r\n)
    $terminaEnCRLF = ($largoBytes >= 2 && substr($contenidoCrudo, -2) === "\r\n");
    $terminaEnLF = ($largoBytes >= 1 && substr($contenidoCrudo, -1) === "\n" && !$terminaEnCRLF);
    $terminaEnCR = ($largoBytes >= 1 && substr($contenidoCrudo, -1) === "\r");
    
    $statsAuditoria['termina_con_salto'] = $terminaEnCRLF;

    if ($terminaEnCRLF) {
        $statsAuditoria['ultimo_salto_desc'] = 'Windows CRLF (\r\n)';
    } elseif ($terminaEnLF) {
        $statsAuditoria['ultimo_salto_desc'] = 'UNIX (LF, \n) Inválido';
        $erroresGenerales[] = "Fin de archivo inválido: El archivo finaliza con un salto UNIX (LF, \\n) en lugar de Windows CRLF (\\r\\n). El protocolo Cabal exige estrictamente CRLF al finalizar el lote.";
    } elseif ($terminaEnCR) {
        $statsAuditoria['ultimo_salto_desc'] = 'Mac (CR, \r) Inválido';
        $erroresGenerales[] = "Fin de archivo inválido: El archivo finaliza con un retorno de carro Mac clásico (CR, \\r). Debe concluir con CRLF (\\r\\n).";
    } else {
        $statsAuditoria['ultimo_salto_desc'] = 'Sin salto final (Error)';
        $erroresGenerales[] = "Fin de archivo sin salto de línea: El archivo no termina en un salto de línea. El protocolo oficial de Cabal exige que el registro Trailer concluya obligatoriamente con salto CRLF (\\r\\n).";
    }

    // 2. Verificación: Formato uniforme CRLF en todo el archivo (sin saltos LF ni CR aislados)
    $tieneCRLF = (strpos($contenidoCrudo, "\r\n") !== false);
    $tieneLFAislado = (bool)preg_match('/(?<!\r)\n/', $contenidoCrudo);
    $tieneCRAislado = (bool)preg_match('/\r(?!\n)/', $contenidoCrudo);

    $esCRLFEstricto = $tieneCRLF && !$tieneLFAislado && !$tieneCRAislado;
    $statsAuditoria['es_crlf'] = $esCRLFEstricto;

    if (!$tieneCRLF) {
        $erroresGenerales[] = "Codificación CRLF incorrecta: El archivo no utiliza saltos de línea Windows (CRLF, \\r\\n). El mainframe de Cabal rechazará el lote.";
    } elseif ($tieneLFAislado || $tieneCRAislado) {
        $erroresGenerales[] = "Formato de saltos mixto: Se detectaron saltos de línea UNIX (LF) o Mac (CR) aislados dentro del archivo. Todos los registros deben usar exclusivamente Windows CRLF (\\r\\n).";
    }

    // Dividir líneas
    $lineasBrutas = preg_split("/\r\n|\n|\r/", $contenidoCrudo);
    // Si la última posición está vacía debido al salto de línea final reglamentario, se retira para procesar los registros
    if (!empty($lineasBrutas) && end($lineasBrutas) === '') {
        array_pop($lineasBrutas);
    }

    // Alerta si hay múltiples líneas vacías al final
    $lineasVaciasExtra = 0;
    while (!empty($lineasBrutas) && end($lineasBrutas) === '') {
        $lineasVaciasExtra++;
        array_pop($lineasBrutas);
    }
    if ($lineasVaciasExtra > 0) {
        $advertencias[] = "Líneas vacías adicionales al final: Se detectaron {$lineasVaciasExtra} salto(s) de línea vacío(s) sobrante(s) al final del archivo.";
    }

    $statsAuditoria['total_lineas'] = count($lineasBrutas);

    if ($statsAuditoria['total_lineas'] < 3) {
        $erroresGenerales[] = "Estructura incompleta: El archivo debe contener al menos 3 registros (1 Cabecera Header, al menos 1 Detalle y 1 Cierre Trailer según docs/NOVCAR.md).";
    }

    $numLinea = 0;
    foreach ($lineasBrutas as $linea) {
        $numLinea++;
        $largo = strlen($linea);

        // Validación 1: Longitud exacta de 80 bytes (docs/NOVCAR.md)
        if ($largo === 80) {
            $statsAuditoria['lineas_correctas_80']++;
        } else {
            $statsAuditoria['lineas_error_80']++;
            $erroresGenerales[] = "Línea #{$numLinea}: Longitud incorrecta de {$largo} bytes (docs/NOVCAR.md exige estrictamente 80 bytes por registro).";
        }

        // Validación 2: Prefijo "TE04" (Bytes 1-4)
        $tipoReg = substr($linea, 0, 4);
        if ($tipoReg === 'TE04') {
            $statsAuditoria['lineas_te04_ok']++;
        } else {
            $statsAuditoria['lineas_te04_error']++;
            $erroresGenerales[] = "Línea #{$numLinea}: Prefijo inválido ('{$tipoReg}'). Debe iniciar obligatoriamente con 'TE04'.";
        }

        // Clasificación de Registro según campo Cuenta/Control (Bytes 11-26, offset 10, len 16)
        $campoControl = substr($linea, 10, 16);

        // A. REGISTRO HEADER (Bytes 11-26 son 16 ceros)
        if ($campoControl === '0000000000000000' || ($numLinea === 1 && !is_numeric(trim($campoControl)))) {
            $entidad = substr($linea, 4, 3);
            $sucursal = substr($linea, 7, 3);
            $ceros = substr($linea, 10, 16);
            $fechaRaw = substr($linea, 26, 6);
            $horaRaw = substr($linea, 32, 6);
            $relleno = substr($linea, 38, 42);

            $dia = substr($fechaRaw, 0, 2);
            $mes = substr($fechaRaw, 2, 2);
            $anio = substr($fechaRaw, 4, 2);
            $horaH = substr($horaRaw, 0, 2);
            $horaM = substr($horaRaw, 2, 2);
            $horaS = substr($horaRaw, 4, 2);

            $fechaFormateada = "{$dia}/{$mes}/20{$anio}";
            $horaFormateada = "{$horaH}:{$horaM}:{$horaS}";

            // Reglas docs/NOVCAR.md para Header:
            if ($tipoReg !== 'TE04') {
                $erroresGenerales[] = "Header (Línea #{$numLinea}): Identificador de registro inválido ('{$tipoReg}'). Debe ser 'TE04'.";
            }
            if ($entidad !== '782') {
                $erroresGenerales[] = "Header (Línea #{$numLinea}): Código de entidad inválido ('{$entidad}'). Debe ser estrictamente '782' (Cabal FSR).";
            }
            if ($sucursal !== '000') {
                $erroresGenerales[] = "Header (Línea #{$numLinea}): Sucursal emisora inválida ('{$sucursal}'). docs/NOVCAR.md exige fijo '000'.";
            }
            if ($ceros !== '0000000000000000') {
                $erroresGenerales[] = "Header (Línea #{$numLinea}): Las posiciones 11-26 deben contener estrictamente 16 ceros ('0000000000000000').";
            }
            if (!is_numeric($fechaRaw) || strlen($fechaRaw) !== 6) {
                $erroresGenerales[] = "Header (Línea #{$numLinea}): Fecha de proceso inválida ('{$fechaRaw}'). Debe ser numérica en formato DDMMAA.";
            }
            if (!is_numeric($horaRaw) || strlen($horaRaw) !== 6) {
                $erroresGenerales[] = "Header (Línea #{$numLinea}): Hora de proceso inválida ('{$horaRaw}'). Debe ser numérica en formato HHMMSS.";
            }

            $headerData = array(
                'linea'     => $linea,
                'largo'     => $largo,
                'tipo_reg'  => $tipoReg,
                'entidad'   => $entidad,
                'sucursal'  => $sucursal,
                'ceros'     => $ceros,
                'fecha'     => $fechaFormateada,
                'hora'      => $horaFormateada,
                'es_valido' => ($tipoReg === 'TE04' && $entidad === '782' && $sucursal === '000' && $ceros === '0000000000000000' && $largo === 80)
            );
        }
        // B. REGISTRO TRAILER (Bytes 11-26 son 16 nueves)
        elseif ($campoControl === '9999999999999999' || ($numLinea === $statsAuditoria['total_lineas'] && strpos($campoControl, '999999') === 0)) {
            $entidad = substr($linea, 4, 3);
            $sucursal = substr($linea, 7, 3);
            $marcaCierre = substr($linea, 10, 16);
            $cantRegRaw = substr($linea, 26, 5);
            $montoComprasRaw = substr($linea, 31, 10);
            $montoCajerosRaw = substr($linea, 41, 10);
            $fillerTrailer = substr($linea, 51, 29);

            $cantRegInt = (int)$cantRegRaw;
            $montoComprasFloat = (float)($montoComprasRaw / 100);
            $montoCajerosFloat = (float)($montoCajerosRaw / 100);

            $statsAuditoria['cantidad_trailer'] = $cantRegInt;
            $statsAuditoria['total_trailer_compras'] = $montoComprasFloat;
            $statsAuditoria['total_trailer_cajeros'] = $montoCajerosFloat;

            // Reglas docs/NOVCAR.md para Trailer:
            if ($tipoReg !== 'TE04') {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Identificador inválido ('{$tipoReg}'). Debe ser 'TE04'.";
            }
            if ($entidad !== '782') {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Entidad emisora inválida ('{$entidad}'). Debe ser '782'.";
            }
            if ($sucursal !== '000') {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Sucursal emisora inválida ('{$sucursal}'). docs/NOVCAR.md exige fijo '000'.";
            }
            if ($marcaCierre !== '9999999999999999') {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Marca fija de cierre inválida ('{$marcaCierre}'). Debe ser exactamente 16 nueves ('9999999999999999').";
            }
            if (!is_numeric($cantRegRaw)) {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Cantidad de registros no numérica ('{$cantRegRaw}').";
            }
            if (!is_numeric($montoComprasRaw)) {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Sumatoria de compras no numérica ('{$montoComprasRaw}').";
            }
            if (!is_numeric($montoCajerosRaw)) {
                $erroresGenerales[] = "Trailer (Línea #{$numLinea}): Sumatoria de cajeros no numérica ('{$montoCajerosRaw}').";
            }

            $trailerData = array(
                'linea'          => $linea,
                'largo'          => $largo,
                'tipo_reg'       => $tipoReg,
                'entidad'        => $entidad,
                'sucursal'       => $sucursal,
                'marca_cierre'   => $marcaCierre,
                'cant_registros' => $cantRegInt,
                'monto_compras'  => $montoComprasFloat,
                'monto_cajeros'  => $montoCajerosFloat,
                'es_valido'      => ($tipoReg === 'TE04' && $entidad === '782' && $sucursal === '000' && $marcaCierre === '9999999999999999' && $largo === 80)
            );
        }
        // C. REGISTROS DE DETALLE (MOVIMIENTOS)
        else {
            $statsAuditoria['lineas_detalle']++;
            $entidad = substr($linea, 4, 3);
            $sucursal = substr($linea, 7, 3);
            $cuenta = trim(substr($linea, 10, 16));
            $codOp = substr($linea, 26, 2);      // Debe ser 63 (Crédito)
            $subCodOp = substr($linea, 28, 2);   // Debe ser 39
            $montoRaw = substr($linea, 30, 9);
            $cajeroRaw = substr($linea, 39, 9);
            $motivoCarga = substr($linea, 48, 6);
            $numCarga = substr($linea, 54, 8);
            $fillerDetalle = substr($linea, 62, 18);

            $montoFloat = (float)($montoRaw / 100);
            $cajeroFloat = (float)($cajeroRaw / 100);

            $statsAuditoria['total_compras_calculado'] += $montoFloat;
            $statsAuditoria['total_cajeros_calculado'] += $cajeroFloat;

            // Reglas docs/NOVCAR.md para Detalle:
            if ($tipoReg !== 'TE04') {
                $erroresGenerales[] = "Línea #{$numLinea}: Identificador de detalle inválido ('{$tipoReg}'). Debe ser 'TE04'.";
            }
            if ($entidad !== '782') {
                $erroresGenerales[] = "Línea #{$numLinea}: Entidad inválida ('{$entidad}'). Debe ser '782'.";
            }
            if (!is_numeric($cuenta) || strlen($cuenta) < 6) {
                $erroresGenerales[] = "Línea #{$numLinea}: Número de cuenta inválido o no numérico ('{$cuenta}'). Debe tener 16 posiciones numéricas.";
            }
            if ($codOp !== '63') {
                $erroresGenerales[] = "Línea #{$numLinea}: Código de operación inválido ('{$codOp}'). docs/NOVCAR.md exige '63' (Crédito).";
            }
            if ($subCodOp !== '39') {
                $erroresGenerales[] = "Línea #{$numLinea}: Subcódigo de operación inválido ('{$subCodOp}'). docs/NOVCAR.md exige '39'.";
            }
            if (!is_numeric($montoRaw)) {
                $erroresGenerales[] = "Línea #{$numLinea}: Importe de compras no numérico ('{$montoRaw}').";
            }
            if (!is_numeric($cajeroRaw)) {
                $erroresGenerales[] = "Línea #{$numLinea}: Importe de retiros cajeros no numérico ('{$cajeroRaw}').";
            }
            if (!is_numeric($motivoCarga)) {
                $erroresGenerales[] = "Línea #{$numLinea}: Motivo de carga no numérico ('{$motivoCarga}').";
            }
            if (!is_numeric($numCarga)) {
                $erroresGenerales[] = "Línea #{$numLinea}: Número de carga no numérico ('{$numCarga}').";
            }

            $titularNombre = '<span style="color:var(--text-light);">(No encontrado en padrón)</span>';
            $titularDni = '-';
            $estaEnPadron = false;

            if (isset($cuentasPadron[$cuenta])) {
                $b = $cuentasPadron[$cuenta];
                $titularNombre = htmlspecialchars(trim($b['Apellido']) . " " . trim($b['Nombre']), ENT_QUOTES, 'UTF-8');
                $titularDni = ltrim(preg_replace('/[^0-9]/', '', $b['NumeroDocumento']), '0');
                $estaEnPadron = true;
                $statsAuditoria['cuentas_en_padron']++;
            } else {
                $statsAuditoria['cuentas_no_encontradas']++;
                $advertencias[] = "Línea #{$numLinea}: Cuenta '{$cuenta}' no se encuentra registrada en el padrón local de beneficiarios.";
            }

            $filasDetalle[] = array(
                'num_fila'     => $numLinea,
                'largo'        => $largo,
                'tipo_reg'     => $tipoReg,
                'entidad'      => $entidad,
                'sucursal'     => $sucursal,
                'cuenta'       => $cuenta,
                'cod_op'       => $codOp . $subCodOp,
                'monto'        => $montoFloat,
                'cajero'       => $cajeroFloat,
                'motivo_carga' => $motivoCarga,
                'num_carga'    => $numCarga,
                'titular'      => $titularNombre,
                'dni'          => $titularDni,
                'en_padron'    => $estaEnPadron,
                'es_80'        => ($largo === 80)
            );
        }
    }

    // Validación de existencia obligatoria de Header y Trailer
    if (!$headerData) {
        $erroresGenerales[] = "Falta Registro Header: El archivo no contiene la cabecera TE04 con 16 ceros en posición de cuenta (docs/NOVCAR.md).";
    }
    if (!$trailerData) {
        $erroresGenerales[] = "Falta Registro Trailer: El archivo no contiene el registro de cierre TE04 con 16 nueves (docs/NOVCAR.md).";
    }

    // Conciliación Matemática contra el Trailer según docs/NOVCAR.md
    if ($trailerData !== null) {
        if ($statsAuditoria['lineas_detalle'] !== $statsAuditoria['cantidad_trailer']) {
            $statsAuditoria['descuadre_cantidad'] = true;
            $erroresGenerales[] = "Descuadre en Cantidad de Registros: El Trailer declara {$statsAuditoria['cantidad_trailer']} registros pero el archivo contiene {$statsAuditoria['lineas_detalle']} registros de detalle.";
        }

        $diffMonto = round($statsAuditoria['total_compras_calculado'], 2) - round($statsAuditoria['total_trailer_compras'], 2);
        if (abs($diffMonto) > 0.01) {
            $statsAuditoria['descuadre_monto'] = true;
            $erroresGenerales[] = "Descuadre en Importe Total de Compras: El Trailer declara $ " . number_format($statsAuditoria['total_trailer_compras'], 2, ',', '.') . " pero la sumatoria real de compras calculada es $ " . number_format($statsAuditoria['total_compras_calculado'], 2, ',', '.') . " (Diferencia: $ " . number_format($diffMonto, 2, ',', '.') . ").";
        }
    }

    // Dictamen final de conformidad oficial
    $statsAuditoria['lote_valido'] = ($headerData !== null && $trailerData !== null && empty($erroresGenerales) && $statsAuditoria['lineas_error_80'] === 0 && $statsAuditoria['es_crlf'] && $statsAuditoria['termina_con_salto'] && !$statsAuditoria['descuadre_cantidad'] && !$statsAuditoria['descuadre_monto']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <meta name="description" content="Auditoría de consistencia posicional de 80 bytes y saltos CRLF para lotes de acreditación NOVCAR.782 según docs/NOVCAR.md">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .dropzone-novcar {
            border: 2px dashed var(--border-light);
            border-radius: var(--radius-xl);
            padding: 36px 24px;
            text-align: center;
            background: var(--surface-card);
            transition: all var(--transition-fast);
            cursor: pointer;
            position: relative;
            margin-bottom: 20px;
        }
        .dropzone-novcar:hover,
        .dropzone-novcar.dragover {
            border-color: var(--primary-600);
            background: rgba(37, 99, 235, 0.04);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .dropzone-novcar-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-600);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            transition: transform var(--transition-fast);
        }
        .dropzone-novcar:hover .dropzone-novcar-icon {
            transform: scale(1.08);
            background: var(--primary-600);
            color: #ffffff;
        }

        .audit-badge-ok {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            font-weight: 700;
            font-size: 0.78rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .audit-badge-bad {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            font-weight: 700;
            font-size: 0.78rem;
            border: 1px solid rgba(239, 68, 68, 0.3);
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
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
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

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.88rem;
        }
        .checklist-item:last-child {
            border-bottom: none;
        }
        .check-badge-state {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            color: #fff;
            flex-shrink: 0;
        }
        .check-badge-state.ok { background: #059669; }
        .check-badge-state.bad { background: #dc2626; }

        .spec-box-container {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
            margin-top: 10px;
        }
        .spec-table th {
            background: var(--surface-subtle);
            padding: 8px 12px;
            text-align: left;
            font-weight: 700;
            border: 1px solid var(--border-light);
            color: var(--navy-800);
        }
        .spec-table td {
            padding: 6px 12px;
            border: 1px solid var(--border-light);
            color: var(--text-main);
        }
        .spec-table code {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(59, 130, 246, 0.08);
            padding: 2px 5px;
            border-radius: 4px;
            color: var(--primary-600);
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

            <a href="GenerarCargaCredito2026.php" class="btn-nav-action" id="btn-generar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Generar NOVCAR
            </a>

            <a href="ProcesarCarga.php" class="btn-nav-action" id="btn-respuesta-carga">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                Verificar Respuesta Carga
            </a>

            <button type="button" class="btn-nav-action" id="btn-theme-toggle" onclick="toggleTheme()" title="Alternar tema oscuro / claro">
                🌓
            </button>
        </div>
    </div>
</header>

<main class="page-container" style="margin-top: 28px;">

    <!-- TÍTULO DE LA PÁGINA -->
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--navy-800); margin: 0 0 6px 0; display: flex; align-items: center; gap: 10px;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Verificación y Auditoría de Lote NOVCAR.782
            </h2>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">
                Auditoría posicional de 80 bytes según la norma oficial de Cabal <strong>Anexo 3 (docs/NOVCAR.md)</strong>.
                Valida estructura Header, Detalles (6339), Trailer y conciliación de sumatorias.
            </p>
        </div>

        <button type="button" class="btn-modern-secondary" onclick="toggleNormativaNovcar()" id="btnNormativaNovcar" style="padding: 8px 14px; font-size: 0.84rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Ver Reglas de Validación (docs/NOVCAR.md)
        </button>
    </div>

    <!-- PANEL EXPANDIBLE CON LAS REGLAS DE VALIDACIÓN DE docs/NOVCAR.md -->
    <div class="spec-box-container" id="panelNormativaNovcar" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
            <h4 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--navy-800);">
                📜 Especificación Técnica Oficial Cabal — Anexo 3: Novedad de Carga (docs/NOVCAR.md)
            </h4>
            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">Longitud: 80 BYTES • Formato: FIJO</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 16px;">
            <div>
                <strong style="font-size: 0.86rem; color: var(--primary-600);">1. Registro Header (Cabecera)</strong>
                <table class="spec-table">
                    <tr><th>Pos</th><th>Campo</th><th>Long</th><th>Tipo</th></tr>
                    <tr><td>001-004</td><td><code>"TE04"</code></td><td>4</td><td>X</td></tr>
                    <tr><td>005-007</td><td>Entidad emisora (<code>782</code>)</td><td>3</td><td>N-0</td></tr>
                    <tr><td>008-010</td><td>Sucursal emisora (Fijo <code>000</code>)</td><td>3</td><td>N-0</td></tr>
                    <tr><td>011-026</td><td>Todos ceros (<code>16 ceros</code>)</td><td>16</td><td>N-0</td></tr>
                    <tr><td>027-032</td><td>Fecha proceso (<code>DDMMAA</code>)</td><td>6</td><td>N-0</td></tr>
                    <tr><td>033-038</td><td>Hora proceso (<code>HHMMSS</code>)</td><td>6</td><td>N-0</td></tr>
                    <tr><td>039-080</td><td>Filler (Relleno)</td><td>42</td><td>X</td></tr>
                </table>
            </div>

            <div>
                <strong style="font-size: 0.86rem; color: var(--emerald-600);">2. Registro Detalle (Acreditaciones)</strong>
                <table class="spec-table">
                    <tr><th>Pos</th><th>Campo</th><th>Long</th><th>Tipo</th></tr>
                    <tr><td>001-004</td><td><code>"TE04"</code></td><td>4</td><td>X</td></tr>
                    <tr><td>005-010</td><td>Entidad/Sucursal (<code>EEESSS</code>)</td><td>6</td><td>N-0</td></tr>
                    <tr><td>011-026</td><td>Número de Cuenta</td><td>16</td><td>N-0</td></tr>
                    <tr><td>027-028</td><td>Código de operación (<code>63</code> = Crédito)</td><td>2</td><td>N-0</td></tr>
                    <tr><td>029-030</td><td>Subcódigo de operación (<code>39</code>)</td><td>2</td><td>N-0</td></tr>
                    <tr><td>031-039</td><td>Importe para compras (7 int + 2 dec)</td><td>9</td><td>N-2</td></tr>
                    <tr><td>040-048</td><td>Importe retiros cajeros</td><td>9</td><td>N-2</td></tr>
                    <tr><td>049-054</td><td>Motivo de carga (<code>006339</code>)</td><td>6</td><td>N</td></tr>
                    <tr><td>055-062</td><td>Número de carga secuencial</td><td>8</td><td>N</td></tr>
                    <tr><td>063-080</td><td>Filler (Relleno)</td><td>18</td><td>X</td></tr>
                </table>
            </div>

            <div style="grid-column: 1 / -1;">
                <strong style="font-size: 0.86rem; color: var(--navy-800);">3. Registro Trailer (Cierre Contable)</strong>
                <table class="spec-table">
                    <tr><th>Pos</th><th>Campo</th><th>Long</th><th>Tipo</th></tr>
                    <tr><td>001-004</td><td><code>"TE04"</code></td><td>4</td><td>X</td></tr>
                    <tr><td>005-007</td><td>Entidad emisora (<code>782</code>)</td><td>3</td><td>N-0</td></tr>
                    <tr><td>008-010</td><td>Sucursal emisora (Fijo <code>000</code>)</td><td>3</td><td>N-0</td></tr>
                    <tr><td>011-026</td><td>Todos nueves (<code>9999999999999999</code>)</td><td>16</td><td>N-0</td></tr>
                    <tr><td>027-031</td><td>Cantidad de registros de detalle enviados</td><td>5</td><td>N-0</td></tr>
                    <tr><td>032-041</td><td>Sumatoria de los importes para compras (8 int + 2 dec)</td><td>10</td><td>N-2</td></tr>
                    <tr><td>042-051</td><td>Sumatoria de los importes para cajeros (8 int + 2 dec)</td><td>10</td><td>N-2</td></tr>
                    <tr><td>052-080</td><td>Filler (Relleno)</td><td>29</td><td>X</td></tr>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($mensajeUpload)): ?>
        <div class="alert-modern <?php echo $tipoUpload; ?>" style="margin-bottom: 24px;">
            <?php echo $mensajeUpload; ?>
        </div>
    <?php endif; ?>

    <!-- SECCIÓN DE SUBIDA Y ARRASTRE DE ARCHIVO (DROPZONE) -->
    <div class="modern-card" style="margin-bottom: 28px;">
        <div class="modern-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <div class="modern-card-title-group">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                    📁 Adjuntar o Arrastrar Archivo <code>novcar.782</code> para Chequeo
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                    Suba el archivo generado que desea auditar para certificar si cumple con todas las exigencias reglamentarias de <strong>docs/NOVCAR.md</strong>.
                </p>
            </div>
        </div>

        <div style="padding: 24px;">
            <form action="VerificarNovcar.php" method="POST" enctype="multipart/form-data" id="formUploadNovcar">
                <input type="hidden" name="accion" value="subir_novcar">

                <div class="dropzone-novcar" id="dropzoneNovcar" onclick="document.getElementById('fileInputNovcar').click();">
                    <input type="file" name="archivo_novcar" id="fileInputNovcar" accept=".782,.txt,.dat" style="display:none;" onchange="document.getElementById('formUploadNovcar').submit();">
                    <div class="dropzone-novcar-icon">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin-bottom: 6px;">
                        Haga clic aquí o arrastre su archivo <code>novcar.782</code> para auditar
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Formatos soportados: <strong>.782</strong> (Lote oficial Cabal) o <strong>.TXT</strong> (Posicional 80 bytes)
                    </div>
                </div>
            </form>

            <?php if ($archivoAuditado): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; padding-top: 8px;">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Archivo auditado: <strong><?php echo htmlspecialchars($nombreArchivoMostrado, ENT_QUOTES, 'UTF-8'); ?></strong>
                        &bull; Tamaño: <strong><?php echo number_format($tamanoArchivo, 0, ',', '.'); ?> bytes</strong>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <a href="VerificarNovcar.php" class="btn-modern-secondary" style="padding: 9px 16px; font-size: 0.86rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            Adjuntar Otro Archivo
                        </a>
                        <a href="VerificarNovcar.php?descargar=1&archivo=<?php echo urlencode(basename($nombreArchivo)); ?>" class="btn-modern-secondary" style="padding: 9px 16px; font-size: 0.86rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            Descargar Archivo Auditado
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div style="padding-top: 6px; font-size: 0.84rem; color: var(--text-muted);">
                    ℹ️ Adjunte o arrastre el archivo en el recuadro superior para iniciar la auditoría posicional.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$archivoAuditado): ?>
        <div class="alert-modern info" style="margin-bottom: 24px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div>
                <strong>Esperando archivo para auditar:</strong><br>
                Por directiva estricta de validación del sistema, los datos a auditar se deben adjuntar en el recuadro superior. <strong>No se validan archivos cargados previamente en el servidor.</strong> Arrastre o seleccione su archivo de lote bancario <code>.782</code>, <code>.txt</code> o <code>.dat</code> para iniciar la verificación automática contra <code>docs/NOVCAR.md</code>.
            </div>
        </div>
    <?php elseif (!$archivoExiste || empty($statsAuditoria['total_lineas'])): ?>
        <div class="alert-modern danger" style="margin-bottom: 24px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>El archivo adjuntado está vacío o no se pudo leer.</strong><br>
                Por favor verifique el contenido del archivo y vuelva a adjuntarlo en el recuadro superior.
            </div>
        </div>
    <?php else: ?>

        <!-- DICTAMEN GLOBAL DE AUDITORÍA SEGÚN docs/NOVCAR.md -->
        <div class="verdict-card <?php echo $statsAuditoria['lote_valido'] ? 'ok' : 'bad'; ?>">
            <div class="verdict-icon <?php echo $statsAuditoria['lote_valido'] ? 'ok' : 'bad'; ?>">
                <?php echo $statsAuditoria['lote_valido'] ? '✓' : '✕'; ?>
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 6px;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: <?php echo $statsAuditoria['lote_valido'] ? '#065f46' : '#991b1b'; ?>;">
                        <?php if ($statsAuditoria['lote_valido']): ?>
                            ✅ DICTAMEN: LOTE NOVCAR 100% VÁLIDO CONFORME A NORMA CABAL (docs/NOVCAR.md)
                        <?php else: ?>
                            ⛔ DICTAMEN: LOTE NOVCAR CON INCONSISTENCIAS / RECHAZO BANCARIO
                        <?php endif; ?>
                    </h3>
                    <span style="font-size: 0.82rem; font-weight: 700; padding: 4px 10px; border-radius: var(--radius-full); background: <?php echo $statsAuditoria['lote_valido'] ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)'; ?>; color: <?php echo $statsAuditoria['lote_valido'] ? '#059669' : '#dc2626'; ?>;">
                        <?php echo $statsAuditoria['lote_valido'] ? 'CONFORME PROTOCOLO 782' : count($erroresGenerales) . ' ERROR(ES) DETECTADO(S)'; ?>
                    </span>
                </div>
                <p style="margin: 0 0 10px 0; font-size: 0.92rem; color: var(--text-main); line-height: 1.5;">
                    <?php if ($statsAuditoria['lote_valido']): ?>
                        El archivo cumple estrictamente con las especificaciones del manual <strong>docs/NOVCAR.md (Anexo 3)</strong>: registros con ancho uniforme de 80 bytes, identificador "TE04", sucursal "000" y 16 ceros en Header, operación 6339 en detalles, 16 nueves en Trailer y cuadre exacto de importes y cantidades.
                    <?php else: ?>
                        Se han detectado problemas técnicos o contables que provocarán el rechazo inmediato del lote en la plataforma de Cabal. Revise los puntos observados a continuación antes de remitir el archivo:
                    <?php endif; ?>
                </p>

                <?php if (!empty($erroresGenerales)): ?>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 0.88rem; color: #b91c1c; font-weight: 600;">
                        <?php foreach ($erroresGenerales as $err): ?>
                            <li style="margin-bottom: 4px;"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($advertencias) && $statsAuditoria['lote_valido']): ?>
                    <div style="margin-top: 10px; padding: 10px 14px; background: rgba(245, 158, 11, 0.12); border-left: 3px solid #d97706; border-radius: 4px; font-size: 0.85rem; color: #92400e;">
                        <strong>Advertencia Operativa:</strong> <?php echo count($advertencias); ?> cuenta(s) no fueron encontradas en el padrón local de beneficiarios. Esto no invalida el lote ante Cabal si las cuentas fueron creadas recientemente, pero verifique que pertenezcan al programa.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TARJETAS DE MÉTRICAS CLAVE DE AUDITORÍA -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Total de Registros
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: var(--navy-800);">
                    <?php echo $statsAuditoria['total_lineas']; ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    1 Header + <?php echo $statsAuditoria['lineas_detalle']; ?> Detalles + 1 Trailer
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Longitud Fija (80 Bytes)
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: <?php echo $statsAuditoria['lineas_error_80'] === 0 ? '#059669' : '#dc2626'; ?>;">
                    <?php echo $statsAuditoria['lineas_correctas_80']; ?> / <?php echo $statsAuditoria['total_lineas']; ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    <?php echo $statsAuditoria['lineas_error_80'] === 0 ? '✅ 100% registros conformes' : '⛔ ' . $statsAuditoria['lineas_error_80'] . ' con longitud errónea'; ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Formato CRLF & Salto Final
                </div>
                <div style="font-size: 1.5rem; font-weight: 800; color: <?php echo ($statsAuditoria['es_crlf'] && $statsAuditoria['termina_con_salto']) ? '#059669' : '#dc2626'; ?>;">
                    <?php echo ($statsAuditoria['es_crlf'] && $statsAuditoria['termina_con_salto']) ? 'CRLF Conforme' : 'No Conforme'; ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    <?php 
                    if ($statsAuditoria['es_crlf'] && $statsAuditoria['termina_con_salto']) {
                        echo '✅ CRLF estricto y salto final \r\n';
                    } elseif (!$statsAuditoria['termina_con_salto']) {
                        echo '⛔ Falta salto \r\n al final del lote';
                    } else {
                        echo '⛔ Saltos UNIX (LF) detectados';
                    }
                    ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Importe Total en Compras
                </div>
                <div style="font-size: 1.5rem; font-weight: 800; color: <?php echo $statsAuditoria['descuadre_monto'] ? '#dc2626' : '#2563eb'; ?>;">
                    <?php echo pasaramoneda($statsAuditoria['total_compras_calculado']); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    <?php echo $statsAuditoria['descuadre_monto'] ? '⛔ Descuadre con Trailer' : '✅ Coincide con Trailer ($T6)'; ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Cierre Trailer ($T5)
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: <?php echo $statsAuditoria['descuadre_cantidad'] ? '#dc2626' : '#059669'; ?>;">
                    <?php echo $statsAuditoria['cantidad_trailer']; ?> reg.
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    <?php echo $statsAuditoria['descuadre_cantidad'] ? '⛔ Difiere de ' . $statsAuditoria['lineas_detalle'] . ' detalles' : '✅ Conciliación de cantidad exacta'; ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Cuentas en Padrón
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: <?php echo $statsAuditoria['cuentas_no_encontradas'] === 0 ? '#059669' : '#d97706'; ?>;">
                    <?php echo $statsAuditoria['cuentas_en_padron']; ?> / <?php echo $statsAuditoria['lineas_detalle']; ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    <?php echo $statsAuditoria['cuentas_no_encontradas'] === 0 ? '✅ 100% en beneficiarios' : '⚠️ ' . $statsAuditoria['cuentas_no_encontradas'] . ' no registradas localmente'; ?>
                </div>
            </div>
        </div>

        <!-- CHECKLIST DE CONFORMIDAD NORMATIVA CABAL (docs/NOVCAR.md) -->
        <div class="modern-card" style="margin-bottom: 24px;">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        📋 Checklist de Validación Técnica Oficial — Cabal Entidad 782 (docs/NOVCAR.md)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                        Verificación exhaustiva según la especificación posicional del manual Anexo 3
                    </p>
                </div>
            </div>
            <div style="padding: 16px 24px;">
                <div class="checklist-item">
                    <div class="check-badge-state <?php echo $statsAuditoria['lineas_error_80'] === 0 ? 'ok' : 'bad'; ?>">
                        <?php echo $statsAuditoria['lineas_error_80'] === 0 ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Ancho Fijo Posicional de 80 Bytes:</strong> Todas las líneas del lote deben contener exactamente 80 caracteres (docs/NOVCAR.md).
                    </div>
                    <div>
                        <span class="<?php echo $statsAuditoria['lineas_error_80'] === 0 ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo $statsAuditoria['lineas_correctas_80']; ?> / <?php echo $statsAuditoria['total_lineas']; ?> OK
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo ($statsAuditoria['lineas_te04_error'] === 0) ? 'ok' : 'bad'; ?>">
                        <?php echo ($statsAuditoria['lineas_te04_error'] === 0) ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Identificador de Registro "TE04":</strong> Posiciones 001-004 deben contener obligatoriamente la constante <code>TE04</code> en Header, Detalles y Trailer.
                    </div>
                    <div>
                        <span class="<?php echo ($statsAuditoria['lineas_te04_error'] === 0) ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo $statsAuditoria['lineas_te04_ok']; ?> / <?php echo $statsAuditoria['total_lineas']; ?> OK
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo $statsAuditoria['es_crlf'] ? 'ok' : 'bad'; ?>">
                        <?php echo $statsAuditoria['es_crlf'] ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Formato de Saltos Windows CRLF (<code>\r\n</code>):</strong> Cada registro del lote debe estar delimitado exclusivamente por retorno de carro y salto de línea Windows, sin mezclas con UNIX (LF) ni Mac (CR).
                    </div>
                    <div>
                        <span class="<?php echo $statsAuditoria['es_crlf'] ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo $statsAuditoria['es_crlf'] ? 'CRLF Estricto' : 'Formato No Conforme'; ?>
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo $statsAuditoria['termina_con_salto'] ? 'ok' : 'bad'; ?>">
                        <?php echo $statsAuditoria['termina_con_salto'] ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Terminación con Salto de Línea Final:</strong> El archivo debe concluir obligatoriamente con un salto de línea Windows CRLF (<code>\r\n</code>) inmediatamente después del registro Trailer (Cierre).
                    </div>
                    <div>
                        <span class="<?php echo $statsAuditoria['termina_con_salto'] ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo $statsAuditoria['termina_con_salto'] ? 'Termina en \r\n (OK)' : 'Sin Salto Final (Error)'; ?>
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo ($headerData && $headerData['es_valido']) ? 'ok' : 'bad'; ?>">
                        <?php echo ($headerData && $headerData['es_valido']) ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Estructura del Registro 1 (Header):</strong> Identificador <code>TE04</code>, Entidad <code>782</code>, Sucursal <code>000</code>, 16 ceros en cuenta, fecha DDMMAA y hora HHMMSS válidas.
                    </div>
                    <div>
                        <span class="<?php echo ($headerData && $headerData['es_valido']) ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo ($headerData && $headerData['es_valido']) ? 'Header Conforme' : 'Header Inválido'; ?>
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo ($trailerData && $trailerData['es_valido']) ? 'ok' : 'bad'; ?>">
                        <?php echo ($trailerData && $trailerData['es_valido']) ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Estructura del Registro Final (Trailer):</strong> Identificador <code>TE04</code>, Entidad <code>782</code>, Sucursal <code>000</code> y marca fija de cierre <code>9999999999999999</code>.
                    </div>
                    <div>
                        <span class="<?php echo ($trailerData && $trailerData['es_valido']) ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo ($trailerData && $trailerData['es_valido']) ? 'Trailer Conforme' : 'Trailer Inválido'; ?>
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo !$statsAuditoria['descuadre_cantidad'] ? 'ok' : 'bad'; ?>">
                        <?php echo !$statsAuditoria['descuadre_cantidad'] ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Conciliación de Cantidad de Registros:</strong> El campo <code>$T5</code> (pos 27-31) del Trailer coincide con las filas de detalle contabilizadas.
                    </div>
                    <div>
                        <span class="<?php echo !$statsAuditoria['descuadre_cantidad'] ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo $statsAuditoria['lineas_detalle']; ?> vs <?php echo $statsAuditoria['cantidad_trailer']; ?>
                        </span>
                    </div>
                </div>

                <div class="checklist-item">
                    <div class="check-badge-state <?php echo !$statsAuditoria['descuadre_monto'] ? 'ok' : 'bad'; ?>">
                        <?php echo !$statsAuditoria['descuadre_monto'] ? '✓' : '✕'; ?>
                    </div>
                    <div style="flex: 1;">
                        <strong>Conciliación de Importe Total:</strong> El campo <code>$T6</code> (pos 32-41) del Trailer coincide exactamente con la suma matemática de compras.
                    </div>
                    <div>
                        <span class="<?php echo !$statsAuditoria['descuadre_monto'] ? 'audit-badge-ok' : 'audit-badge-bad'; ?>">
                            <?php echo pasaramoneda($statsAuditoria['total_compras_calculado']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. REGISTRO DE CABECERA (HEADER) SEGÚN docs/NOVCAR.md -->
        <?php if ($headerData): ?>
        <div class="modern-card" style="margin-bottom: 24px;">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        1. Registro de Cabecera (Header) — Fila #1 (docs/NOVCAR.md)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                        Identificación institucional de la entidad 782, sucursal 000, 16 ceros, fecha y hora de emisión del lote
                    </p>
                </div>
                <div>
                    <?php if ($headerData['largo'] === 80): ?>
                        <span class="audit-badge-ok">✓ 80 Bytes Reglamentarios</span>
                    <?php else: ?>
                        <span class="audit-badge-bad">✕ Error: <?php echo $headerData['largo']; ?> Bytes</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Identificador (001-004)</th>
                                <th>Entidad (005-007)</th>
                                <th>Sucursal (008-010)</th>
                                <th>Ceros (011-026)</th>
                                <th>Fecha Proceso (027-032)</th>
                                <th>Hora Proceso (033-038)</th>
                                <th>Estado Normativo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code><?php echo htmlspecialchars($headerData['tipo_reg']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($headerData['entidad']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($headerData['sucursal']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($headerData['ceros']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($headerData['fecha']); ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($headerData['hora']); ?></strong></td>
                                <td>
                                    <?php if ($headerData['es_valido']): ?>
                                        <span class="audit-badge-ok">Header Conforme</span>
                                    <?php else: ?>
                                        <span class="audit-badge-bad">Inconsistente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. REGISTROS DE DETALLE -->
        <div class="modern-card" style="margin-bottom: 24px;">
            <div class="modern-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div class="modern-card-title-group">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        2. Registros de Detalle de Acreditación (<?php echo count($filasDetalle); ?> movimientos)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                        Movimientos monetarios código <code>6339</code> (Crédito) cruzados con el Padrón de Beneficiarios
                    </p>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" id="buscadorTabla" placeholder="Buscar por cuenta, titular, DNI..." onkeyup="buscarEnTabla()" style="padding: 8px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-md); font-size: 0.88rem; width: 280px; background: var(--surface-card); color: var(--text-main);">
                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                        Visibles: <strong id="contadorVisibles"><?php echo count($filasDetalle); ?></strong>
                    </span>
                </div>
            </div>

            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 520px; overflow-y: auto;">
                    <table class="modern-table" id="tablaDetallesNovcar">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th>Prefijo (1-4)</th>
                                <th>Ent/Suc (5-10)</th>
                                <th>Número de Cuenta (11-26)</th>
                                <th>Titular Beneficiario (Padrón)</th>
                                <th>DNI</th>
                                <th style="text-align: center;">Op/Sub (27-30)</th>
                                <th style="text-align: right;">Importe Compras (31-39)</th>
                                <th style="text-align: center;">Carga N° (55-62)</th>
                                <th style="text-align: center;">Longitud</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filasDetalle as $det): ?>
                            <tr class="fila-registro-novcar">
                                <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem; font-weight: 600;"><?php echo $det['num_fila']; ?></td>
                                <td><code><?php echo htmlspecialchars($det['tipo_reg']); ?></code></td>
                                <td><?php echo htmlspecialchars($det['entidad'] . '/' . $det['sucursal']); ?></td>
                                <td>
                                    <strong style="font-family: 'JetBrains Mono', monospace; letter-spacing: 0.04em; color: var(--navy-800);">
                                        <?php echo htmlspecialchars($det['cuenta']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo $det['titular']; ?>
                                    <?php if ($det['en_padron']): ?>
                                        <span style="color: #059669; font-size: 0.75rem; margin-left: 4px;" title="Verificado en padrón institucional">✓</span>
                                    <?php else: ?>
                                        <span style="color: #d97706; font-size: 0.75rem; margin-left: 4px;" title="No registrado localmente">⚠️</span>
                                    <?php endif; ?>
                                </td>
                                <td><span style="font-family: 'JetBrains Mono', monospace; font-size: 0.82rem; color: var(--text-muted);"><?php echo htmlspecialchars($det['dni']); ?></span></td>
                                <td style="text-align: center;"><span style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #2563eb; background: rgba(37,99,235,0.08); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($det['cod_op']); ?></span></td>
                                <td style="text-align: right; color: var(--emerald-600); font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem;">
                                    <?php echo pasaramoneda($det['monto']); ?>
                                </td>
                                <td style="text-align: center;"><code style="font-family: 'JetBrains Mono', monospace;">#<?php echo htmlspecialchars($det['num_carga']); ?></code></td>
                                <td style="text-align: center;">
                                    <?php if ($det['es_80']): ?>
                                        <span class="audit-badge-ok">80 B</span>
                                    <?php else: ?>
                                        <span class="audit-badge-bad"><?php echo $det['largo']; ?> B</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. REGISTRO DE CIERRE (TRAILER) SEGÚN docs/NOVCAR.md -->
        <?php if ($trailerData): ?>
        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        3. Registro de Cierre (Trailer) — Fila #<?php echo $statsAuditoria['total_lineas']; ?> (docs/NOVCAR.md)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                        Totales de control del lote bancario para validación contra el mainframe de compensación
                    </p>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php if ($trailerData['largo'] === 80): ?>
                        <span class="audit-badge-ok">✓ 80 Bytes Reglamentarios</span>
                    <?php else: ?>
                        <span class="audit-badge-bad">✕ Error: <?php echo $trailerData['largo']; ?> Bytes</span>
                    <?php endif; ?>
                    <?php if ($statsAuditoria['termina_con_salto']): ?>
                        <span class="audit-badge-ok">✓ Salto Final CRLF (\r\n)</span>
                    <?php else: ?>
                        <span class="audit-badge-bad">✕ Falta Salto Final (\r\n)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Identificador (001-004)</th>
                                <th>Entidad (005-007)</th>
                                <th>Sucursal (008-010)</th>
                                <th>Marca Cierre (011-026)</th>
                                <th>Cantidad Registros (027-031)</th>
                                <th style="text-align: right;">Sumatoria Compras (032-041)</th>
                                <th style="text-align: right;">Sumatoria Cajeros (042-051)</th>
                                <th>Estado Normativo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code><?php echo htmlspecialchars($trailerData['tipo_reg']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($trailerData['entidad']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($trailerData['sucursal']); ?></code></td>
                                <td><code><?php echo htmlspecialchars($trailerData['marca_cierre']); ?></code></td>
                                <td>
                                    <strong style="font-size: 1.05rem; color: <?php echo $statsAuditoria['descuadre_cantidad'] ? '#dc2626' : 'var(--navy-800)'; ?>;">
                                        <?php echo $trailerData['cant_registros']; ?>
                                    </strong>
                                    <?php if ($statsAuditoria['descuadre_cantidad']): ?>
                                        <div style="font-size: 0.75rem; color: #dc2626;">Error: archivo tiene <?php echo $statsAuditoria['lineas_detalle']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <strong style="font-size: 1.15rem; color: <?php echo $statsAuditoria['descuadre_monto'] ? '#dc2626' : 'var(--emerald-600)'; ?>;">
                                        <?php echo pasaramoneda($trailerData['monto_compras']); ?>
                                    </strong>
                                    <?php if ($statsAuditoria['descuadre_monto']): ?>
                                        <div style="font-size: 0.75rem; color: #dc2626;">Error: suma calculada <?php echo pasaramoneda($statsAuditoria['total_compras_calculado']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;"><?php echo pasaramoneda($trailerData['monto_cajeros']); ?></td>
                                <td>
                                    <?php if ($trailerData['es_valido']): ?>
                                        <span class="audit-badge-ok">Trailer Conforme</span>
                                    <?php else: ?>
                                        <span class="audit-badge-bad">Inconsistente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
    // Alternar visibilidad del panel con la normativa docs/NOVCAR.md
    function toggleNormativaNovcar() {
        var panel = document.getElementById('panelNormativaNovcar');
        var btn = document.getElementById('btnNormativaNovcar');
        if (!panel) return;

        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Ocultar Reglas docs/NOVCAR.md';
        } else {
            panel.style.display = 'none';
            btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> Ver Reglas de Validación (docs/NOVCAR.md)';
        }
    }

    // Drag & Drop para subir novcar.782
    var dropzone = document.getElementById('dropzoneNovcar');
    if (dropzone) {
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
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                var fileInput = document.getElementById('fileInputNovcar');
                fileInput.files = files;
                document.getElementById('formUploadNovcar').submit();
            }
        });
    }

    // Buscador en vivo en la tabla de detalles
    function buscarEnTabla() {
        var input = document.getElementById('buscadorTabla').value.toLowerCase();
        var filas = document.querySelectorAll('.fila-registro-novcar');
        var visibles = 0;

        filas.forEach(function(fila) {
            var texto = fila.innerText.toLowerCase();
            if (texto.indexOf(input) > -1) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        var contElem = document.getElementById('contadorVisibles');
        if (contElem) contElem.innerText = visibles;
    }
</script>

</body>
</html>