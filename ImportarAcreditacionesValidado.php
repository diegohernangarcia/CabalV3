<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Módulo: Importar y Subir Acreditaciones Validado
 * Entidad 782 - Cabal
 * 
 * Funcionalidad:
 * 1. Subir o arrastrar archivo (.xlsx, .xls, .ods) o analizar el existente en archivos/Acreditaciones.xlsx
 * 2. Identificar dinámicamente fila y columnas de cabecera:
 *    Nº | DISPOSICIÓN/EXPTE JUDICIAL | APELLIDO | NOMBRE | LOCALIDAD | DNI | CUIL | ACREDITAR
 * 3. Clave principal: CUIL.
 * 4. Cruce con tabla `beneficiario` (donde clave es `Cuit`):
 *    - id_beneficiario <-- id
 *    - Apellido <-- Apellido
 *    - Nombre <-- Nombre
 *    - NumeroCuenta <-- NumeroCuenta
 *    - DNI <-- NumeroDocumento
 * 5. Corroboración de datos entre Excel y Beneficiario:
 *    - Muestra tabla con: Apellido / Nombre / Numero Cuenta / Importe Compra / DNI
 *    - Indica cualquier discrepancia entre los datos del Excel y el cruce con la tabla beneficiario.
 * 6. NO escribe en la tabla acreditar (Modo Validación y Cotejo previo).
 */

ini_set('memory_limit', '512M');
set_time_limit(180);

require_once('phpexcel/PHPExcel.php');
require_once('BD/UsarGestion.php');
require_once('BD/Funciones.php');

$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$archivo_default = $targetDir . "Acreditaciones.xlsx";
$archivo_a_procesar = "";
$nombre_archivo_mostrado = "";
$tipo_origen = ""; // 'subido' o 'existente'

$estado_proceso = 'inicial'; // 'inicial', 'analizado_ok', 'error'
$mensaje_error = '';
$estado_carga = ''; // 'completada' si se ejecutó la carga en BD
$resumen_carga = array('registros' => 0, 'total_monto' => 0.0);

// Conteo actual en tabla acreditar
$infoAcreditarActual = array('total' => 0, 'suma' => 0.0);
try {
    $stmtCount = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(ImporteCompra), 0) AS suma FROM acreditar");
    if ($stmtCount) {
        $infoAcreditarActual = $stmtCount->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Continuar normalmente si hay excepción
}

$filas_datos = array();
$resumen = array(
    'header_row' => 0,
    'cols_detectadas' => array(),
    'total_filas' => 0,
    'coincidentes' => 0,
    'discrepancias' => 0,
    'no_encontrados' => 0,
    'total_monto' => 0.0,
    'total_excel_declarado' => null,
    'total_excel_fila' => null,
    'error_total_descuadre' => false,
    'diferencia_total' => 0.0
);

// Función para normalizar cadenas para comparación (ignora tildes, eñes y mayúsculas)
function normalizarTextoComparacion($str) {
    $str = strtoupper(trim((string)$str));
    $str = str_replace(
        array('Á','É','Í','Ó','Ú','Ü','Ñ','ñ','á','é','í','ó','ú','ü'),
        array('A','E','I','O','U','U','N','N','A','E','I','O','U','U'),
        $str
    );
    $str = preg_replace('/[^A-Z0-9]/', '', $str);
    return $str;
}

// Obtener valor seguro de celda (con cálculo de fórmulas)
function obtenerValorCeldaSeguroCalculado($sheet, $colIndex, $rowIndex) {
    try {
        $cell = $sheet->getCellByColumnAndRow($colIndex, $rowIndex);
        if ($cell === null) return '';
        $val = $cell->getValue();
        if (is_string($val) && strlen($val) > 0 && $val[0] === '=') {
            try {
                $calc = $cell->getCalculatedValue();
                return $calc !== null ? trim((string)$calc) : '';
            } catch (Exception $e) {
                return trim((string)$val);
            }
        }
        return $val !== null ? trim((string)$val) : '';
    } catch (Exception $e) {
        return '';
    }
}

// Manejo de formulario POST
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    if ($accion === 'cargar_acreditar') {
        $archivo_param = isset($_POST['archivo_procesar']) ? trim($_POST['archivo_procesar']) : '';
        $archivo_nombre = basename($archivo_param);
        if (!empty($archivo_nombre) && file_exists($targetDir . $archivo_nombre)) {
            $archivo_a_procesar = $targetDir . $archivo_nombre;
            $nombre_archivo_mostrado = $archivo_nombre;
            $tipo_origen = 'existente';
        } elseif (file_exists($archivo_default)) {
            $archivo_a_procesar = $archivo_default;
            $nombre_archivo_mostrado = "archivos/Acreditaciones.xlsx";
            $tipo_origen = 'existente';
        } else {
            $estado_proceso = 'error';
            $mensaje_error = "No se localizó el archivo de acreditaciones para efectuar la carga.";
        }
    } elseif ($accion === 'subir_archivo') {
        if (isset($_FILES['archivo_acreditaciones']) && $_FILES['archivo_acreditaciones']['error'] === UPLOAD_ERR_OK) {
            $nombreOriginal = basename($_FILES['archivo_acreditaciones']['name']);
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

            if (in_array($extension, array('xlsx', 'xls', 'ods'))) {
                $destino = $targetDir . "Acreditaciones_subido." . $extension;
                if (move_uploaded_file($_FILES['archivo_acreditaciones']['tmp_name'], $destino)) {
                    $archivo_a_procesar = $destino;
                    $nombre_archivo_mostrado = $nombreOriginal;
                    $tipo_origen = 'subido';
                } else {
                    $estado_proceso = 'error';
                    $mensaje_error = "Error al mover el archivo subido a la carpeta de trabajo.";
                }
            } else {
                $estado_proceso = 'error';
                $mensaje_error = "Formato no admitido. Se admiten únicamente archivos de hoja de cálculo con extensiones .xlsx, .xls o .ods.";
            }
        } else {
            $estado_proceso = 'error';
            $errCode = isset($_FILES['archivo_acreditaciones']['error']) ? $_FILES['archivo_acreditaciones']['error'] : -1;
            $mensaje_error = "No se pudo subir el archivo. (Código de error: " . $errCode . ").";
        }
    }
}

// Procesar el archivo si se seleccionó uno válido
if (!empty($archivo_a_procesar) && file_exists($archivo_a_procesar) && $estado_proceso !== 'error') {
    try {
        $inputFileType = PHPExcel_IOFactory::identify($archivo_a_procesar);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(false); // Necesitamos evaluar fórmulas si las hay
        $objPHPExcel = $objReader->load($archivo_a_procesar);
        $sheet = $objPHPExcel->getSheet(0);

        $highestRow = (int)$sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = PHPExcel_Cell::columnIndexFromString($highestCol);

        // PASO 1: Identificar en qué fila y columna se encuentran los encabezados
        // Cabecera esperada: Nº | DISPOSICIÓN/EXPTE JUDICIAL | APELLIDO | NOMBRE | LOCALIDAD | DNI | CUIL | ACREDITAR
        $headerRow = -1;
        $headerColMap = array();

        $maxSearchRow = min(50, $highestRow);
        for ($r = 1; $r <= $maxSearchRow; $r++) {
            $rowKeywords = array();
            for ($c = 0; $c < $highestColIndex; $c++) {
                $rawVal = obtenerValorCeldaSeguroCalculado($sheet, $c, $r);
                if ($rawVal === '') continue;

                $clean = normalizarTextoComparacion($rawVal);

                if (strpos($clean, 'CUIL') !== false || strpos($clean, 'CUIT') !== false) {
                    $rowKeywords['cuil'] = $c;
                }
                if (strpos($clean, 'ACREDITAR') !== false || strpos($clean, 'MONTO') !== false || strpos($clean, 'IMPORTE') !== false) {
                    $rowKeywords['acreditar'] = $c;
                }
                if (strpos($clean, 'APELLIDO') !== false) {
                    $rowKeywords['apellido'] = $c;
                }
                if (strpos($clean, 'NOMBRE') !== false) {
                    $rowKeywords['nombre'] = $c;
                }
                if (strpos($clean, 'DNI') !== false || (strpos($clean, 'DOC') !== false && strpos($clean, 'TIPO') === false)) {
                    $rowKeywords['dni'] = $c;
                }
                if (strpos($clean, 'EXPTE') !== false || strpos($clean, 'DISPOSICION') !== false || strpos($clean, 'JUDICIAL') !== false) {
                    $rowKeywords['expte'] = $c;
                }
                if (strpos($clean, 'LOCALIDAD') !== false || strpos($clean, 'CIUDAD') !== false) {
                    $rowKeywords['localidad'] = $c;
                }
                if ($clean === 'N' || $clean === 'NO' || strpos($clean, 'NUM') !== false || strpos($clean, 'ORDEN') !== false) {
                    $rowKeywords['num'] = $c;
                }
            }

            // Si encontramos la clave CUIL, el monto ACREDITAR y APELLIDO o DNI, tenemos la cabecera
            if (isset($rowKeywords['cuil']) && isset($rowKeywords['acreditar']) && (isset($rowKeywords['apellido']) || isset($rowKeywords['dni']))) {
                $headerRow = $r;
                $headerColMap = $rowKeywords;
                break;
            }
        }

        if ($headerRow === -1 || !isset($headerColMap['cuil']) || !isset($headerColMap['acreditar'])) {
            throw new Exception("No se pudo identificar automáticamente la fila de encabezados. Asegúrese de que la planilla contenga las columnas requeridas: CUIL y ACREDITAR (junto con APELLIDO, NOMBRE, DNI).");
        }

        $resumen['header_row'] = $headerRow;
        $resumen['cols_detectadas'] = $headerColMap;

        // PASO 2: Cargar el padrón de beneficiarios para cruce ultrarrápido por Cuit
        $stmtBen = $conn->query("SELECT id, Apellido, Nombre, NumeroCuenta, NumeroDocumento, Cuit FROM beneficiario");
        $padronBeneficiarios = array();
        while ($b = $stmtBen->fetch(PDO::FETCH_ASSOC)) {
            $cuitClean = preg_replace('/[^0-9]/', '', $b['Cuit']);
            if ($cuitClean !== '') {
                $padronBeneficiarios[$cuitClean] = $b;
            }
        }

        // PASO 3: Recorrer filas desde $headerRow + 1 hasta el final
        $cuilCol = $headerColMap['cuil'];
        $acreditarCol = $headerColMap['acreditar'];
        $apeCol = isset($headerColMap['apellido']) ? $headerColMap['apellido'] : null;
        $nomCol = isset($headerColMap['nombre']) ? $headerColMap['nombre'] : null;
        $dniCol = isset($headerColMap['dni']) ? $headerColMap['dni'] : null;
        $expteCol = isset($headerColMap['expte']) ? $headerColMap['expte'] : null;
        $locCol = isset($headerColMap['localidad']) ? $headerColMap['localidad'] : null;
        $numCol = isset($headerColMap['num']) ? $headerColMap['num'] : null;

        $total_excel_declarado = null;
        $total_excel_fila = null;

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            $rawCuil = obtenerValorCeldaSeguroCalculado($sheet, $cuilCol, $r);
            $rawApe  = ($apeCol !== null) ? obtenerValorCeldaSeguroCalculado($sheet, $apeCol, $r) : '';
            $rawNom  = ($nomCol !== null) ? obtenerValorCeldaSeguroCalculado($sheet, $nomCol, $r) : '';
            $rawDni  = ($dniCol !== null) ? obtenerValorCeldaSeguroCalculado($sheet, $dniCol, $r) : '';
            $rawExpte = ($expteCol !== null) ? obtenerValorCeldaSeguroCalculado($sheet, $expteCol, $r) : '';
            $rawLoc   = ($locCol !== null) ? obtenerValorCeldaSeguroCalculado($sheet, $locCol, $r) : '';
            $rawNum   = ($numCol !== null) ? obtenerValorCeldaSeguroCalculado($sheet, $numCol, $r) : ($r - $headerRow);

            // Detenerse si es fila de totales
            $esFilaTotal = false;
            if (stripos($rawCuil, 'TOTAL') !== false || stripos($rawApe, 'TOTAL') !== false || stripos($rawNom, 'TOTAL') !== false || stripos($rawExpte, 'TOTAL') !== false || stripos($rawLoc, 'TOTAL') !== false) {
                $esFilaTotal = true;
            } else {
                for ($c = 0; $c < min(12, $highestColIndex); $c++) {
                    $cellVal = obtenerValorCeldaSeguroCalculado($sheet, $c, $r);
                    if (stripos($cellVal, 'TOTAL') !== false) {
                        $esFilaTotal = true;
                        break;
                    }
                }
            }

            if ($esFilaTotal) {
                // Capturar el valor total declarado en la columna ACREDITAR
                $rawTotalEnFila = obtenerValorCeldaSeguroCalculado($sheet, $acreditarCol, $r);
                $cleanTotalEnFila = (float)str_replace(array('$', ' ', ','), array('', '', '.'), $rawTotalEnFila);
                $total_excel_declarado = $cleanTotalEnFila;
                $total_excel_fila = $r;
                break;
            }

            // Ignorar filas totalmente vacías
            $cleanCuil = preg_replace('/[^0-9]/', '', $rawCuil);
            if ($cleanCuil === '' && $rawApe === '' && $rawDni === '') {
                continue;
            }

            $rawMonto = obtenerValorCeldaSeguroCalculado($sheet, $acreditarCol, $r);
            $monto = (float)str_replace(array('$', ' ', ','), array('', '', '.'), $rawMonto);

            $resumen['total_filas']++;
            $resumen['total_monto'] += $monto;

            // Datos a preparar para Acreditar desde Beneficiario
            $id_beneficiario = null;
            $apellido_bd = '';
            $nombre_bd = '';
            $numero_cuenta_bd = '';
            $dni_bd = '';

            $estado_fila = 'OK'; // 'OK', 'DISCREPANCIA', 'NO_ENCONTRADO'
            $discrepancias = array();

            // Cruce con tabla beneficiario por Cuit
            if (isset($padronBeneficiarios[$cleanCuil])) {
                $ben = $padronBeneficiarios[$cleanCuil];
                $id_beneficiario = $ben['id'];
                $apellido_bd = trim($ben['Apellido']);
                $nombre_bd = trim($ben['Nombre']);
                $numero_cuenta_bd = trim($ben['NumeroCuenta']);
                // DNI en BD suele tener ceros a la izquierda (11 caracteres posicionales Cabal)
                $dni_bd_limpio = ltrim(preg_replace('/[^0-9]/', '', $ben['NumeroDocumento']), '0');
                $dni_bd = $dni_bd_limpio;

                // Cotejo DNI
                $excel_dni_limpio = ltrim(preg_replace('/[^0-9]/', '', $rawDni), '0');
                if ($excel_dni_limpio !== '' && $dni_bd_limpio !== '' && $excel_dni_limpio !== $dni_bd_limpio) {
                    $discrepancias[] = "DNI difiere: Excel tiene '" . htmlspecialchars($rawDni, ENT_QUOTES, 'UTF-8') . "' vs BD tiene '" . htmlspecialchars($dni_bd_limpio, ENT_QUOTES, 'UTF-8') . "'";
                }

                // Cotejo Apellido
                $normApeExcel = normalizarTextoComparacion($rawApe);
                $normApeBD    = normalizarTextoComparacion($apellido_bd);
                if ($rawApe !== '' && $normApeExcel !== $normApeBD) {
                    $discrepancias[] = "Apellido difiere: Excel tiene '" . htmlspecialchars($rawApe, ENT_QUOTES, 'UTF-8') . "' vs BD tiene '" . htmlspecialchars($apellido_bd, ENT_QUOTES, 'UTF-8') . "'";
                }

                // Cotejo Nombre
                $normNomExcel = normalizarTextoComparacion($rawNom);
                $normNomBD    = normalizarTextoComparacion($nombre_bd);
                if ($rawNom !== '' && $normNomExcel !== $normNomBD) {
                    $discrepancias[] = "Nombre difiere: Excel tiene '" . htmlspecialchars($rawNom, ENT_QUOTES, 'UTF-8') . "' vs BD tiene '" . htmlspecialchars($nombre_bd, ENT_QUOTES, 'UTF-8') . "'";
                }

                if (!empty($discrepancias)) {
                    $estado_fila = 'DISCREPANCIA';
                    $resumen['discrepancias']++;
                } else {
                    $estado_fila = 'OK';
                    $resumen['coincidentes']++;
                }
            } else {
                $estado_fila = 'NO_ENCONTRADO';
                $discrepancias[] = "El CUIL '" . htmlspecialchars($rawCuil, ENT_QUOTES, 'UTF-8') . "' no existe en el padrón de beneficiarios.";
                $resumen['no_encontrados']++;
            }

            $filas_datos[] = array(
                'num_orden'       => $rawNum,
                'fila_excel'      => $r,
                'cuil_excel'      => $rawCuil,
                'cuil_limpio'     => $cleanCuil,
                'expte_excel'     => $rawExpte,
                'localidad_excel' => $rawLoc,
                'apellido_excel'  => $rawApe,
                'nombre_excel'    => $rawNom,
                'dni_excel'       => $rawDni,
                'monto_acreditar' => $monto,
                // Datos traídos de la tabla beneficiario para la tabla acreditar:
                'id_beneficiario' => $id_beneficiario,
                'apellido_bd'     => $apellido_bd,
                'nombre_bd'       => $nombre_bd,
                'numero_cuenta'   => $numero_cuenta_bd,
                'dni_bd'          => $dni_bd,
                'estado'          => $estado_fila,
                'discrepancias'   => $discrepancias
            );
        }

        // Si aún no encontramos el total declarado, buscar en filas subsiguientes
        if ($total_excel_declarado === null) {
            $ultimaFila = !empty($filas_datos) ? ($headerRow + count($filas_datos)) : $headerRow;
            for ($r = $ultimaFila + 1; $r <= min($highestRow, $ultimaFila + 30); $r++) {
                $esFilaTotal = false;
                for ($c = 0; $c < min(12, $highestColIndex); $c++) {
                    $cellVal = obtenerValorCeldaSeguroCalculado($sheet, $c, $r);
                    if (stripos($cellVal, 'TOTAL') !== false) {
                        $esFilaTotal = true;
                        break;
                    }
                }
                if ($esFilaTotal) {
                    $rawTotalEnFila = obtenerValorCeldaSeguroCalculado($sheet, $acreditarCol, $r);
                    $cleanTotalEnFila = (float)str_replace(array('$', ' ', ','), array('', '', '.'), $rawTotalEnFila);
                    $total_excel_declarado = $cleanTotalEnFila;
                    $total_excel_fila = $r;
                    break;
                }
            }
        }

        // Cotejo de la sumatoria calculada vs celda TOTAL en Excel
        if ($total_excel_declarado !== null) {
            $resumen['total_excel_declarado'] = $total_excel_declarado;
            $resumen['total_excel_fila']      = $total_excel_fila;
            $diff = round($resumen['total_monto'], 2) - round($total_excel_declarado, 2);
            $resumen['diferencia_total'] = $diff;
            if (abs($diff) > 0.01) {
                $resumen['error_total_descuadre'] = true;
            }
        }

        // Si la acción solicitada es ejecutar la carga a la tabla acreditar
        if ($accion === 'cargar_acreditar') {
            if ($resumen['error_total_descuadre']) {
                throw new Exception("Avance bloqueado: El Total declarado en Excel ($ " . number_format($resumen['total_excel_declarado'], 2, ',', '.') . ") no coincide con la sumatoria calculada de los montos a depositar ($ " . number_format($resumen['total_monto'], 2, ',', '.') . ").");
            }
            if (empty($filas_datos)) {
                throw new Exception("No hay registros disponibles para cargar en la tabla acreditar.");
            }

            // Iniciar transacción PDO atómica
            $conn->beginTransaction();

            // 1. TRUNCATE a tabla acreditar y sincronización con acreditar2
            $conn->exec("TRUNCATE TABLE acreditar");
            $conn->exec("TRUNCATE TABLE acreditar2");

            $sqlIns = "INSERT INTO acreditar (id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) VALUES (:id_beneficiario, :Apellido, :Nombre, :NumeroCuenta, :ImporteCompra, '0', :DNI, :Fecha, :Hora)";
            $stmtAcreditar = $conn->prepare($sqlIns);

            $sqlIns2 = "INSERT INTO acreditar2 (id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) VALUES (:id_beneficiario, :Apellido, :Nombre, :NumeroCuenta, :ImporteCompra, '0', :DNI, :Fecha, :Hora)";
            $stmtAcreditar2 = $conn->prepare($sqlIns2);

            $fechaActual = date("Y-m-d");
            $horaActual = date("H:i:s");
            $cargados = 0;
            $montoCargado = 0.0;

            foreach ($filas_datos as $f) {
                if (!empty($f['id_beneficiario'])) {
                    $montoEntero = (string)round($f['monto_acreditar']);
                    $params = array(
                        ':id_beneficiario' => $f['id_beneficiario'],
                        ':Apellido'        => $f['apellido_bd'],
                        ':Nombre'          => $f['nombre_bd'],
                        ':NumeroCuenta'    => $f['numero_cuenta'],
                        ':ImporteCompra'   => $montoEntero,
                        ':DNI'             => $f['dni_bd'],
                        ':Fecha'           => $fechaActual,
                        ':Hora'            => $horaActual
                    );
                    $stmtAcreditar->execute($params);
                    $stmtAcreditar2->execute($params);
                    $cargados++;
                    $montoCargado += (float)$f['monto_acreditar'];
                }
            }

            $conn->commit();

            // Actualizar conteo actual en BD
            $infoAcreditarActual['total'] = $cargados;
            $infoAcreditarActual['suma'] = $montoCargado;

            $esAjax = (isset($_POST['ajax']) && $_POST['ajax'] === '1') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(array(
                    'success'                => true,
                    'mensaje'                => "Se vació la tabla acreditar y se insertaron " . $cargados . " registros con éxito.",
                    'registros'              => $cargados,
                    'total_monto'            => $montoCargado,
                    'total_monto_formateado' => '$ ' . number_format($montoCargado, 2, ',', '.')
                ));
                exit;
            }

            $estado_carga = 'completada';
            $resumen_carga = array(
                'registros'   => $cargados,
                'total_monto' => $montoCargado
            );
        }

        $estado_proceso = 'analizado_ok';
    } catch (Exception $e) {
        if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $esAjax = (isset($_POST['ajax']) && $_POST['ajax'] === '1') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'success' => false,
                'mensaje' => $e->getMessage()
            ));
            exit;
        }
        $estado_proceso = 'error';
        $mensaje_error = "Error al procesar el archivo: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <meta name="description" content="Validación y cotejo de planillas de acreditaciones contra el padrón de beneficiarios Cabal">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .dropzone-upload-box {
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
        .dropzone-upload-box:hover,
        .dropzone-upload-box.dragover {
            border-color: var(--primary-blue);
            background: rgba(37, 99, 235, 0.04);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .dropzone-icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            transition: transform var(--transition-fast);
        }
        .dropzone-upload-box:hover .dropzone-icon-circle {
            transform: scale(1.08);
            background: var(--primary-blue);
            color: #ffffff;
        }
        .badge-validation {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .badge-validation.ok {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-validation.discrepancia {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        .badge-validation.no-encontrado {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        [data-theme="dark"] .badge-validation.ok {
            color: #34d399;
        }
        [data-theme="dark"] .badge-validation.discrepancia {
            color: #fbbf24;
        }
        [data-theme="dark"] .badge-validation.no-encontrado {
            color: #f87171;
        }
        .diff-text {
            display: block;
            font-size: 0.78rem;
            color: #dc2626;
            margin-top: 3px;
            font-weight: 600;
        }
        [data-theme="dark"] .diff-text {
            color: #fca5a5;
        }
        .filter-pill-btn {
            padding: 6px 14px;
            border-radius: 9999px;
            border: 1px solid var(--border-light);
            background: var(--surface-card);
            color: var(--text-main);
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .filter-pill-btn:hover,
        .filter-pill-btn.active {
            background: var(--primary-blue);
            color: #ffffff;
            border-color: var(--primary-blue);
        }

        /* Maximizar ancho disponible sin romper el diseño */
        .page-container {
            max-width: 98% !important;
            width: 98% !important;
            margin: 0 auto 50px !important;
            padding: 0 16px !important;
        }
        .top-nav-inner {
            max-width: 98% !important;
            width: 98% !important;
        }
        .modern-table {
            width: 100%;
            table-layout: auto;
        }
        .modern-table th, 
        .modern-table td {
            padding: 12px 14px;
        }

        /* Select de discrepancia interactivo */
        .select-discrepancia {
            padding: 7px 10px;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: var(--radius-sm);
            border: 1.5px solid #f59e0b;
            background: rgba(245, 158, 11, 0.08);
            color: #b45309;
            cursor: pointer;
            transition: all var(--transition-fast);
            outline: none;
            width: 100%;
            min-width: 180px;
        }
        .select-discrepancia:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
        }
        .select-discrepancia.aceptado {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
        }
        .select-discrepancia.aceptado:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        }
        [data-theme="dark"] .select-discrepancia {
            background: #1e293b;
            color: #fbbf24;
            border-color: #d97706;
        }
        [data-theme="dark"] .select-discrepancia.aceptado {
            background: #064e3b;
            color: #6ee7b7;
            border-color: #10b981;
        }

        .btn-batch-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(16, 185, 129, 0.4);
            background: rgba(16, 185, 129, 0.12);
            color: #047857;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .btn-batch-action:hover {
            background: #059669;
            color: #ffffff;
            border-color: #059669;
        }
        .btn-batch-action.secondary {
            border-color: var(--border-light);
            background: var(--surface-card);
            color: var(--text-muted);
        }
        .btn-batch-action.secondary:hover {
            background: var(--surface-subtle);
            color: var(--text-main);
        }
        [data-theme="dark"] .btn-batch-action {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }
        [data-theme="dark"] .btn-batch-action:hover {
            background: #10b981;
            color: #0f172a;
        }

        .next-step-card {
            background: var(--surface-card);
            border: 1.5px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
        }
        .next-step-card.unlocked {
            border-color: #10b981;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, var(--surface-card) 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.15);
        }
        .next-step-card.locked {
            border-color: #f59e0b;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, var(--surface-card) 100%);
        }
        .next-step-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .next-step-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        .next-step-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--navy-800);
            margin-bottom: 2px;
        }
        .next-step-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .btn-next-step {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            color: #ffffff;
            box-shadow: var(--shadow-md);
        }
        .btn-next-step.disabled {
            background: #94a3b8 !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
            opacity: 0.7;
        }
        .btn-next-step.active {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }
        .btn-next-step.active:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-dialog-box {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            max-width: 580px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            border: 1px solid var(--border-light);
            overflow: hidden;
            animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-dialog-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            line-height: 1;
        }
        .modal-dialog-body {
            padding: 24px;
        }
        .modal-dialog-footer {
            padding: 16px 24px;
            background: var(--surface-subtle);
            border-top: 1px solid var(--border-light);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .next-step-card.danger {
            border-color: #ef4444 !important;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, var(--surface-card) 100%) !important;
        }
        .modal-danger {
            border: 2.5px solid #ef4444 !important;
            box-shadow: 0 25px 60px rgba(239, 68, 68, 0.35) !important;
        }
        .btn-danger-modal {
            padding: 10px 22px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .btn-danger-modal:hover {
            background: #991b1b;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.45);
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            <a href="GenerarCargaCredito2026.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                Generar NOVCAR
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="padding-top: 24px;">

    <!-- TÍTULO DE LA PÁGINA -->
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--navy-800); margin: 0 0 6px 0; display: flex; align-items: center; gap: 10px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--emerald-600);">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
            Importar y Subir Acreditaciones Validado
        </h2>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">
            Cotejo automático de planillas de acreditación (.xlsx, .xls, .ods) contra el padrón de beneficiarios cruzando por CUIL.
        </p>
    </div>

    <?php if ($estado_proceso === 'error'): ?>
    <div class="alert-modern danger" style="margin-bottom: 24px;">
        <strong>Error:</strong> <?php echo htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN DE SUBIDA Y ARRASTRE DE ARCHIVOS -->
    <div class="modern-card" style="margin-bottom: 28px;">
        <div class="card-header-custom" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                📁 Selección o Carga de Planilla de Acreditación
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                Suba o arrastre el archivo (.xlsx, .xls, .ods) o analice la planilla ya disponible en el servidor.
            </p>
        </div>

        <div style="padding: 24px;">
            <form action="ImportarAcreditacionesValidado.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="accion" value="subir_archivo">

                <div class="dropzone-upload-box" id="dropzoneBox" onclick="document.getElementById('fileInput').click();">
                    <input type="file" name="archivo_acreditaciones" id="fileInput" accept=".xlsx,.xls,.ods" style="display:none;" onchange="document.getElementById('uploadForm').submit();">
                    <div class="dropzone-icon-circle">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin-bottom: 6px;">
                        Haga clic aquí o arrastre su archivo para subir y validar
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Formatos soportados: <strong>.XLSX</strong>, <strong>.XLS</strong> o <strong>.ODS</strong> (OpenDocument)
                    </div>
                </div>
            </form>

            <div style="padding-top: 8px;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Cabecera requerida esperada: <code>Nº</code> &bull; <code>DISPOSICIÓN/EXPTE JUDICIAL</code> &bull; <code>APELLIDO</code> &bull; <code>NOMBRE</code> &bull; <code>LOCALIDAD</code> &bull; <code>DNI</code> &bull; <code>CUIL</code> &bull; <code>ACREDITAR</code>
                </div>
            </div>
        </div>
    </div>

    <!-- RESULTADO DEL ANÁLISIS -->
    <?php if ($estado_proceso === 'analizado_ok'): ?>
        
        <!-- AVISO DE MODO DE OPERACIÓN Y ESTADO -->
        <div id="contenedorBannerEstado">
            <?php if ($estado_carga === 'completada'): ?>
            <div class="alert-modern success" style="margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0; margin-top:2px; color: #059669;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <div>
                    <strong>¡Tabla Acreditar Cargada con Éxito!</strong> Se realizó el <code>TRUNCATE</code> de la tabla y se insertaron <strong><?php echo $resumen_carga['registros']; ?></strong> registros cotejados por un monto total de <strong>$ <?php echo number_format($resumen_carga['total_monto'], 2, ',', '.'); ?></strong>.
                    <a href="GenerarCargaCredito2026.php" style="color: inherit; font-weight: 800; text-decoration: underline; margin-left: 8px;">Avanzar al Paso 3: Generar Lote NOVCAR &rarr;</a>
                </div>
            </div>
            <?php else: ?>
            <div class="alert-modern info" style="margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div>
                    <strong>Modo Cotejo y Preparación Activo:</strong> Se identificaron las cabeceras y se cruzó la nómina por <strong>CUIL</strong> contra la tabla <code>beneficiario</code>. 
                    Al aceptar todas las discrepancias y corroborar el total, use <strong>"Continuar al Siguiente Paso"</strong> para vaciar (TRUNCATE) y cargar los registros en la tabla <code>acreditar</code>.
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- TARJETAS DE MÉTRICAS -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Cabecera Detectada
                </div>
                <div style="font-size: 1.45rem; font-weight: 800; color: var(--navy-800);">
                    Fila <?php echo $resumen['header_row']; ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    CUIL Col. <?php echo isset($resumen['cols_detectadas']['cuil']) ? PHPExcel_Cell::stringFromColumnIndex($resumen['cols_detectadas']['cuil']) : '-'; ?> &bull; Acreditar Col. <?php echo isset($resumen['cols_detectadas']['acreditar']) ? PHPExcel_Cell::stringFromColumnIndex($resumen['cols_detectadas']['acreditar']) : '-'; ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Total Titulares Leídos
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: var(--navy-800);">
                    <?php echo number_format($resumen['total_filas'], 0, ',', '.'); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    En archivo <?php echo htmlspecialchars($nombre_archivo_mostrado, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Coincidentes 100%
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: #059669;">
                    <?php echo number_format($resumen['coincidentes'], 0, ',', '.'); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    Datos idénticos con padrón
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Con Discrepancias
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: <?php echo $resumen['discrepancias'] > 0 ? '#d97706' : '#059669'; ?>;">
                    <?php echo number_format($resumen['discrepancias'], 0, ',', '.'); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    Variación en Nombre / Apellido / DNI
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    No Encontrados
                </div>
                <div style="font-size: 1.7rem; font-weight: 800; color: <?php echo $resumen['no_encontrados'] > 0 ? '#dc2626' : '#059669'; ?>;">
                    <?php echo number_format($resumen['no_encontrados'], 0, ',', '.'); ?>
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">
                    CUIL no existe en beneficiario
                </div>
            </div>

            <div class="modern-card" style="padding: 20px; <?php if ($resumen['error_total_descuadre']) echo 'border: 2px solid #ef4444; background: rgba(239, 68, 68, 0.04);'; ?>">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: <?php echo $resumen['error_total_descuadre'] ? '#dc2626' : 'var(--text-muted)'; ?>; margin-bottom: 6px;">
                    Monto Total a Acreditar <?php if ($resumen['error_total_descuadre']) echo '⚠️ Descuadre'; ?>
                </div>
                <div style="font-size: 1.5rem; font-weight: 800; color: <?php echo $resumen['error_total_descuadre'] ? '#dc2626' : '#2563eb'; ?>;">
                    $ <?php echo number_format($resumen['total_monto'], 2, ',', '.'); ?>
                </div>
                <div style="font-size: 0.78rem; color: <?php echo $resumen['error_total_descuadre'] ? '#dc2626' : 'var(--text-muted)'; ?>; margin-top: 4px;">
                    <?php if ($resumen['error_total_descuadre']): ?>
                        ⛔ Total Excel: $ <?php echo number_format($resumen['total_excel_declarado'], 2, ',', '.'); ?>
                        <button type="button" onclick="abrirModalErrorTotal()" style="display:inline; background:none; border:none; color:#dc2626; font-weight:bold; cursor:pointer; text-decoration:underline; padding:0 4px;">Ver Detalle</button>
                    <?php elseif ($resumen['total_excel_declarado'] !== null): ?>
                        ✅ Coincide con celda TOTAL en Excel (Fila <?php echo $resumen['total_excel_fila']; ?>)
                    <?php else: ?>
                        Suma calculada de fórmulas
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PANEL DE CONTROL DE AVANCE PARA SIGUIENTE PASO (SUPERIOR) -->
        <?php if ($resumen['error_total_descuadre']): ?>
        <div class="next-step-card danger" id="nextStepCard">
            <div class="next-step-info">
                <div class="next-step-icon" id="nextStepIcon" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div>
                    <div class="next-step-title" id="nextStepTitle" style="color: #991b1b;">
                        ⛔ Error Crítico: Descuadre en Totales de la Planilla Excel
                    </div>
                    <div class="next-step-desc" id="nextStepDesc" style="color: #b91c1c;">
                        El Total declarado en el Excel ($ <?php echo number_format($resumen['total_excel_declarado'], 2, ',', '.'); ?>) no coincide con la sumatoria de los montos a depositar ($ <?php echo number_format($resumen['total_monto'], 2, ',', '.'); ?>). Avance bloqueado.
                        <button type="button" onclick="abrirModalErrorTotal()" style="background:none; border:none; color:#991b1b; font-weight:800; text-decoration:underline; cursor:pointer; padding:0 4px;">Ver Detalle del Error</button>
                    </div>
                </div>
            </div>
            <div class="next-step-actions">
                <button type="button" class="btn-next-step disabled" id="btnSiguientePaso" disabled title="Bloqueado por descuadre de importes">
                    <span>⛔ Avance Bloqueado</span>
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="next-step-card <?php echo $resumen['discrepancias'] === 0 ? 'unlocked' : 'locked'; ?>" id="nextStepCard">
            <div class="next-step-info">
                <div class="next-step-icon" id="nextStepIcon" style="<?php echo $resumen['discrepancias'] === 0 ? 'background: rgba(16, 185, 129, 0.15); color: #059669;' : 'background: rgba(245, 158, 11, 0.15); color: #d97706;'; ?>">
                    <?php if ($resumen['discrepancias'] === 0): ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <?php else: ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="next-step-title" id="nextStepTitle">
                        <?php if ($resumen['discrepancias'] === 0): ?>
                            ✅ Validación Completa: 100% de Registros Coincidentes
                        <?php else: ?>
                            ⚠️ Acción Requerida: <?php echo $resumen['discrepancias']; ?> Discrepancias Pendientes de Aceptación
                        <?php endif; ?>
                    </div>
                    <div class="next-step-desc" id="nextStepDesc">
                        <?php if ($resumen['discrepancias'] === 0): ?>
                            Todos los datos coinciden exactamente con el padrón de beneficiarios. Puede avanzar al siguiente paso.
                        <?php else: ?>
                            Para habilitar el siguiente paso, seleccione <strong>"Acepto Discrepancia"</strong> en cada registro observado (<span id="contadorDiscrepanciasPendientes"><?php echo $resumen['discrepancias']; ?></span> pendientes).
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="next-step-actions">
                <button type="button" 
                        class="btn-next-step <?php echo $resumen['discrepancias'] === 0 ? 'active' : 'disabled'; ?>" 
                        id="btnSiguientePaso" 
                        <?php echo $resumen['discrepancias'] === 0 ? '' : 'disabled'; ?> 
                        onclick="abrirModalSiguientePaso()">
                    <span>Continuar al Siguiente Paso: Cargar en Acreditar</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- TABLA DE COTEJO Y RESULTADOS (MÁXIMO ANCHO) -->
        <div class="modern-card">
            <div class="card-header-custom" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        📋 Tabla de Acreditaciones y Cotejo con Beneficiario
                    </h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
                        Datos a incorporar en la tabla acreditar (traídos de la tabla beneficiario a partir del CUIL)
                    </p>
                </div>

                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <?php if ($resumen['discrepancias'] > 0): ?>
                    <!-- ACCIONES MASIVAS DE ACEPTACIÓN -->
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <button type="button" class="btn-batch-action" onclick="aceptarTodasLasDiscrepancias()" id="btnAceptarTodas" title="Aceptar todas las discrepancias de una sola vez">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Aceptar Todas (<?php echo $resumen['discrepancias']; ?>)
                        </button>
                        <button type="button" class="btn-batch-action secondary" onclick="restablecerDiscrepancias()" id="btnRestablecer" title="Volver a estado pendiente">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                            Restablecer
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- BOTONES DE FILTRO RÁPIDO -->
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;" id="filtrosContainer">
                        <button type="button" class="filter-pill-btn active" onclick="filtrarTabla('todos')">
                            Todos (<?php echo count($filas_datos); ?>)
                        </button>
                        <button type="button" class="filter-pill-btn" onclick="filtrarTabla('OK')">
                            Coinciden 100% (<?php echo $resumen['coincidentes']; ?>)
                        </button>
                        <button type="button" class="filter-pill-btn" onclick="filtrarTabla('DISCREPANCIA')">
                            Con Discrepancias (<?php echo $resumen['discrepancias']; ?>)
                        </button>
                        <?php if ($resumen['no_encontrados'] > 0): ?>
                        <button type="button" class="filter-pill-btn" onclick="filtrarTabla('NO_ENCONTRADO')">
                            No Encontrados (<?php echo $resumen['no_encontrados']; ?>)
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- BUSCADOR EN VIVO -->
            <div style="padding: 14px 24px; background: var(--surface-card); border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <input type="text" id="buscadorTabla" onkeyup="buscarEnTabla()" placeholder="🔍 Buscar por Apellido, Nombre, CUIL, DNI o Cuenta..."
                       style="width: 100%; max-width: 420px; padding: 8px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.88rem; background: var(--bg-main); color: var(--text-main);">
                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                    Mostrando <span id="contadorVisibles"><?php echo count($filas_datos); ?></span> registros
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="modern-table" id="tablaValidada">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th style="min-width: 120px;">CUIL (Clave)</th>
                            <th style="min-width: 160px;">Apellido (Beneficiario / Excel)</th>
                            <th style="min-width: 170px;">Nombre (Beneficiario / Excel)</th>
                            <th style="min-width: 130px;">Número Cuenta (BD)</th>
                            <th style="min-width: 130px; text-align: right;">Importe Compra ($)</th>
                            <th style="min-width: 120px;">DNI (BD / Excel)</th>
                            <th style="min-width: 130px; text-align: center;">Estado Cotejo</th>
                            <th style="min-width: 220px;">Detalle de Verificación</th>
                            <th style="min-width: 190px; text-align: center;">Aceptación Discrepancia</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filas_datos as $idx => $f): ?>
                        <tr class="fila-registro" data-estado="<?php echo $f['estado']; ?>" id="fila-<?php echo $idx; ?>">
                            <td style="color: var(--text-muted); font-weight: 600; text-align: center;">
                                <?php echo htmlspecialchars($f['num_orden'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>

                            <td>
                                <code><strong><?php echo htmlspecialchars($f['cuil_excel'], ENT_QUOTES, 'UTF-8'); ?></strong></code>
                            </td>

                            <!-- APELLIDO -->
                            <td>
                                <?php if ($f['estado'] === 'NO_ENCONTRADO'): ?>
                                    <span style="color: #dc2626; font-weight: 600;"><?php echo htmlspecialchars($f['apellido_excel'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <small style="display:block; color: var(--text-muted);">(En Excel - No en BD)</small>
                                <?php else: ?>
                                    <strong><?php echo htmlspecialchars($f['apellido_bd'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if ($f['apellido_excel'] !== '' && normalizarTextoComparacion($f['apellido_excel']) !== normalizarTextoComparacion($f['apellido_bd'])): ?>
                                        <span class="diff-text">⚠️ Excel: <?php echo htmlspecialchars($f['apellido_excel'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <!-- NOMBRE -->
                            <td>
                                <?php if ($f['estado'] === 'NO_ENCONTRADO'): ?>
                                    <span style="color: #dc2626; font-weight: 600;"><?php echo htmlspecialchars($f['nombre_excel'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars($f['nombre_bd'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($f['nombre_excel'] !== '' && normalizarTextoComparacion($f['nombre_excel']) !== normalizarTextoComparacion($f['nombre_bd'])): ?>
                                        <span class="diff-text">⚠️ Excel: <?php echo htmlspecialchars($f['nombre_excel'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <!-- NÚMERO DE CUENTA -->
                            <td>
                                <?php if (!empty($f['numero_cuenta'])): ?>
                                    <code><?php echo htmlspecialchars($f['numero_cuenta'], ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Sin cuenta</span>
                                <?php endif; ?>
                            </td>

                            <!-- IMPORTE COMPRA -->
                            <td style="font-weight: 700; color: #059669; text-align: right; white-space: nowrap;">
                                $ <?php echo number_format($f['monto_acreditar'], 2, ',', '.'); ?>
                            </td>

                            <!-- DNI -->
                            <td>
                                <?php if ($f['estado'] === 'NO_ENCONTRADO'): ?>
                                    <code><?php echo htmlspecialchars($f['dni_excel'], ENT_QUOTES, 'UTF-8'); ?></code>
                                <?php else: ?>
                                    <code><?php echo htmlspecialchars($f['dni_bd'], ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php 
                                        $cleanExDni = ltrim(preg_replace('/[^0-9]/', '', $f['dni_excel']), '0');
                                        if ($cleanExDni !== '' && $cleanExDni !== $f['dni_bd']): 
                                    ?>
                                        <span class="diff-text">⚠️ Excel: <?php echo htmlspecialchars($f['dni_excel'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <!-- ESTADO -->
                            <td style="text-align: center;">
                                <?php if ($f['estado'] === 'OK'): ?>
                                    <span class="badge-validation ok">✅ Coincide 100%</span>
                                <?php elseif ($f['estado'] === 'DISCREPANCIA'): ?>
                                    <span class="badge-validation discrepancia">⚠️ Discrepancia</span>
                                <?php else: ?>
                                    <span class="badge-validation no-encontrado">❌ No Encontrado</span>
                                <?php endif; ?>
                            </td>

                            <!-- DETALLE -->
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?php if (!empty($f['discrepancias'])): ?>
                                    <ul style="margin: 0; padding-left: 16px; color: #dc2626;">
                                        <?php foreach ($f['discrepancias'] as $d): ?>
                                            <li><?php echo $d; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span style="color: #059669;">Datos cruzados y validados correctamente.</span>
                                <?php endif; ?>
                            </td>

                            <!-- ACEPTACIÓN DE DISCREPANCIA -->
                            <td style="text-align: center;">
                                <?php if ($f['estado'] === 'DISCREPANCIA'): ?>
                                    <select class="select-discrepancia" data-id="<?php echo $idx; ?>" onchange="evaluarEstadoDiscrepancias(this)">
                                        <option value="PENDIENTE">⏳ Pendiente</option>
                                        <option value="ACEPTADO">✅ Acepto Discrepancia</option>
                                    </select>
                                <?php elseif ($f['estado'] === 'OK'): ?>
                                    <span style="color: #059669; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Conforme
                                    </span>
                                <?php else: ?>
                                    <span style="color: #dc2626; font-size: 0.82rem; font-weight: 700;">
                                        No en Padrón
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PANEL DE CONTROL DE AVANCE PARA SIGUIENTE PASO (INFERIOR) -->
        <?php if ($resumen['error_total_descuadre']): ?>
        <div class="next-step-card danger" id="nextStepCardBottom" style="margin-top: 24px;">
            <div class="next-step-info">
                <div>
                    <div class="next-step-title" id="nextStepTitleBottom" style="color: #991b1b;">
                        ⛔ Error Crítico: Descuadre en Totales
                    </div>
                    <div class="next-step-desc" id="nextStepDescBottom" style="color: #b91c1c;">
                        El Total declarado en el Excel ($ <?php echo number_format($resumen['total_excel_declarado'], 2, ',', '.'); ?>) no coincide con la sumatoria de los montos a depositar ($ <?php echo number_format($resumen['total_monto'], 2, ',', '.'); ?>).
                        <button type="button" onclick="abrirModalErrorTotal()" style="background:none; border:none; color:#991b1b; font-weight:800; text-decoration:underline; cursor:pointer; padding:0 4px;">Ver Detalle del Error</button>
                    </div>
                </div>
            </div>
            <div class="next-step-actions">
                <button type="button" class="btn-next-step disabled" id="btnSiguientePasoBottom" disabled title="Bloqueado por descuadre de importes">
                    <span>⛔ Avance Bloqueado</span>
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="next-step-card <?php echo $resumen['discrepancias'] === 0 ? 'unlocked' : 'locked'; ?>" id="nextStepCardBottom" style="margin-top: 24px;">
            <div class="next-step-info">
                <div>
                    <div class="next-step-title" id="nextStepTitleBottom">
                        <?php if ($resumen['discrepancias'] === 0): ?>
                            ✅ Nómina Lista para el Siguiente Paso
                        <?php else: ?>
                            ⚠️ Aceptación de Discrepancias Requerida
                        <?php endif; ?>
                    </div>
                    <div class="next-step-desc" id="nextStepDescBottom">
                        Total a acreditar auditado: <strong>$ <?php echo number_format($resumen['total_monto'], 2, ',', '.'); ?></strong> en <strong><?php echo count($filas_datos); ?></strong> beneficiarios.
                    </div>
                </div>
            </div>
            <div class="next-step-actions">
                <button type="button" 
                        class="btn-next-step <?php echo $resumen['discrepancias'] === 0 ? 'active' : 'disabled'; ?>" 
                        id="btnSiguientePasoBottom" 
                        <?php echo $resumen['discrepancias'] === 0 ? '' : 'disabled'; ?> 
                        onclick="abrirModalSiguientePaso()">
                    <span>Continuar al Siguiente Paso: Cargar en Acreditar</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- MODAL DE CONFIRMACIÓN DE SIGUIENTE PASO -->
        <div id="modalSiguientePaso" class="modal-overlay" style="display: none;">
            <div class="modal-dialog-box">
                <!-- ESTADO 1: CONFIRMACIÓN DE TRUNCATE Y CARGA EN ACREDITAR -->
                <div id="modalContentConfirmacion">
                    <div class="modal-dialog-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(37, 99, 235, 0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                ⚡
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--navy-800);">Confirmar Carga en Tabla Acreditar</h3>
                                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">Nómina validada y lista para impactar en la base de datos</p>
                            </div>
                        </div>
                        <button type="button" class="modal-close-btn" onclick="cerrarModalSiguientePaso()">&times;</button>
                    </div>
                    <div class="modal-dialog-body">
                        <div style="background: var(--surface-subtle); padding: 16px; border-radius: var(--radius-md); margin-bottom: 18px; border: 1px solid var(--border-light);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.88rem;">
                                <div><strong>Titulares a Cargar:</strong> <?php echo count($filas_datos); ?> beneficiarios</div>
                                <div><strong>Discrepancias:</strong> Aceptadas al 100%</div>
                                <div><strong>Monto Total a Acreditar:</strong> $ <?php echo number_format($resumen['total_monto'], 2, ',', '.'); ?></div>
                                <div><strong>Acción en Base de Datos:</strong> TRUNCATE + INSERT masivo</div>
                            </div>
                        </div>
                        <div style="background: rgba(37, 99, 235, 0.05); border-left: 3px solid #2563eb; padding: 12px 14px; border-radius: 4px; margin-bottom: 14px; font-size: 0.86rem; color: var(--text-main);">
                            ℹ️ Al confirmar se ejecutará un <strong><code>TRUNCATE TABLE acreditar</code></strong> para vaciar el lote previo y se insertarán los <strong><?php echo count($filas_datos); ?> registros</strong> con sus datos correspondientes del Padrón de Beneficiarios.
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0;">
                            ¿Desea proceder con la carga en la tabla <code>acreditar</code> para continuar al Paso 3 (Generación del lote bancario NOVCAR)?
                        </p>
                    </div>
                    <div class="modal-dialog-footer">
                        <button type="button" class="btn-modern-secondary" onclick="cerrarModalSiguientePaso()" style="padding: 10px 18px; font-size: 0.88rem; cursor: pointer;">
                            Volver a la Tabla
                        </button>
                        <button type="button" class="btn-next-step active" id="btnConfirmarCarga" onclick="ejecutarCargaAcreditar()" style="padding: 10px 22px; font-size: 0.88rem; cursor: pointer;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Confirmar: Truncate y Cargar en Acreditar</span>
                        </button>
                    </div>
                </div>

                <!-- ESTADO 2: ÉXITO TRAS LA CARGA -->
                <div id="modalContentExito" style="display: none;">
                    <div class="modal-dialog-header" style="background: rgba(16, 185, 129, 0.1); border-bottom: 1px solid rgba(16, 185, 129, 0.25);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #059669; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: bold;">
                                ✓
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #065f46;">¡Tabla Acreditar Cargada con Éxito!</h3>
                                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: #047857;">La base de datos fue truncada y actualizada con la nómina cotejada</p>
                            </div>
                        </div>
                        <button type="button" class="modal-close-btn" onclick="cerrarModalSiguientePaso()">&times;</button>
                    </div>
                    <div class="modal-dialog-body">
                        <div style="background: rgba(16, 185, 129, 0.08); border: 1.5px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-md); padding: 16px; margin-bottom: 18px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem;">
                                <div><strong>Registros Insertados:</strong> <span id="exitoCantRegistros" style="color: #059669; font-weight: 800;">-</span> beneficiarios</div>
                                <div><strong>Monto Acreditado:</strong> <span id="exitoMontoTotal" style="color: #059669; font-weight: 800;">-</span></div>
                                <div><strong>Tabla Acreditar:</strong> ✅ Actualizada</div>
                                <div><strong>Tabla Acreditar2:</strong> ✅ Sincronizada</div>
                            </div>
                        </div>
                        <p style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 6px;">
                            Los datos ya se encuentran alojados en la base de datos y listos para generar el lote bancario reglamentario Cabal NOVCAR.782.
                        </p>
                    </div>
                    <div class="modal-dialog-footer" style="background: rgba(16, 185, 129, 0.04);">
                        <button type="button" class="btn-modern-secondary" onclick="cerrarModalSiguientePaso()" style="padding: 10px 18px; font-size: 0.88rem; cursor: pointer;">
                            Ver Tabla
                        </button>
                        <a href="GenerarCargaCredito2026.php" class="btn-next-step active" style="text-decoration: none; padding: 10px 22px; font-size: 0.9rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                            <span>Continuar a Generar NOVCAR (Paso 3)</span>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- VENTANA EMERGENTE DE ERROR DANGER (DESCUADRE DE TOTALES) -->
        <div id="modalErrorTotal" class="modal-overlay" style="display: <?php echo $resumen['error_total_descuadre'] ? 'flex' : 'none'; ?>;">
            <div class="modal-dialog-box modal-danger">
                <div class="modal-dialog-header" style="background: rgba(239, 68, 68, 0.12); border-bottom: 2px solid rgba(239, 68, 68, 0.35);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: #dc2626; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.35);">
                            ⚠️
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.22rem; font-weight: 800; color: #991b1b;">
                                ¡Error Crítico: Descuadre en Totales!
                            </h3>
                            <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: #b91c1c; font-weight: 600;">
                                El Total declarado en el Excel no coincide con la sumatoria de los montos a depositar
                            </p>
                        </div>
                    </div>
                    <button type="button" class="modal-close-btn" onclick="cerrarModalErrorTotal()" style="color: #991b1b; font-size: 1.8rem;">&times;</button>
                </div>
                <div class="modal-dialog-body" style="padding: 24px;">
                    <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: var(--radius-md); padding: 18px; margin-bottom: 20px;">
                        <p style="margin: 0 0 14px 0; font-size: 0.95rem; color: #991b1b; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            El Total no coincide con la sumatoria de los montos a depositar.
                        </p>
                        
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                            <tbody>
                                <tr style="border-bottom: 1px solid #fee2e2;">
                                    <td style="padding: 10px 0; color: #4b5563;"><strong>Sumatoria Real de Filas (ACREDITAR):</strong></td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 800; color: #111827; font-size: 1.05rem;">
                                        $ <?php echo number_format($resumen['total_monto'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid #fee2e2;">
                                    <td style="padding: 10px 0; color: #4b5563;"><strong>Celda TOTAL en Excel (Fila <?php echo $resumen['total_excel_fila']; ?>):</strong></td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 800; color: #dc2626; font-size: 1.05rem;">
                                        $ <?php echo number_format($resumen['total_excel_declarado'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; color: #991b1b; font-weight: 700;"><strong>Diferencia / Desfasaje:</strong></td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 800; color: #dc2626; font-size: 1.15rem;">
                                        <?php echo ($resumen['diferencia_total'] > 0 ? '+ ' : '- ') . '$ ' . number_format(abs($resumen['diferencia_total']), 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p style="font-size: 0.88rem; color: var(--text-main); line-height: 1.5; margin-bottom: 0;">
                        Por estrictas razones de seguridad bancaria, <strong>el proceso se encuentra bloqueado</strong>. Revise la planilla de cálculo Excel, asegúrese de que la fórmula de totalización sume todos los registros sin omitir filas y vuelva a subir el archivo.
                    </p>
                </div>
                <div class="modal-dialog-footer" style="background: #fff5f5; border-top: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <span style="font-size: 0.82rem; color: #991b1b; font-weight: 700;">
                        ⛔ Avance Bloqueado por Seguridad Financiera
                    </span>
                    <button type="button" class="btn-danger-modal" onclick="cerrarModalErrorTotal()">
                        Entendido, Revisar Planilla
                    </button>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<a href="javascript:history.back()" class="btn-modern-flotante" title="Volver atrás">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver
</a>

<script>
    // Drag & Drop handlers
    var dropzone = document.getElementById('dropzoneBox');
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
                var fileInput = document.getElementById('fileInput');
                fileInput.files = files;
                document.getElementById('uploadForm').submit();
            }
        });
    }

    // Filtrado por botones de estado
    function filtrarTabla(estado) {
        var buttons = document.querySelectorAll('#filtrosContainer .filter-pill-btn');
        buttons.forEach(function(btn) { btn.classList.remove('active'); });
        if (event && event.target) {
            event.target.classList.add('active');
        }

        var filas = document.querySelectorAll('.fila-registro');
        var visibles = 0;
        filas.forEach(function(fila) {
            if (estado === 'todos' || fila.getAttribute('data-estado') === estado) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });
        document.getElementById('contadorVisibles').innerText = visibles;
    }

    // Búsqueda en vivo
    function buscarEnTabla() {
        var input = document.getElementById('buscadorTabla').value.toLowerCase();
        var filas = document.querySelectorAll('.fila-registro');
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
        document.getElementById('contadorVisibles').innerText = visibles;
    }

    // Estado de descuadre de totales
    var errorTotalDescuadre = <?php echo ($resumen['error_total_descuadre'] ? 'true' : 'false'); ?>;

    function abrirModalErrorTotal() {
        var modal = document.getElementById('modalErrorTotal');
        if (modal) modal.style.display = 'flex';
    }

    function cerrarModalErrorTotal() {
        var modal = document.getElementById('modalErrorTotal');
        if (modal) modal.style.display = 'none';
    }

    // Evaluación en tiempo real de aceptación de discrepancias
    function evaluarEstadoDiscrepancias(selectElem) {
        if (selectElem) {
            if (selectElem.value === 'ACEPTADO') {
                selectElem.classList.add('aceptado');
            } else {
                selectElem.classList.remove('aceptado');
            }
        }

        var nextStepCard = document.getElementById('nextStepCard');
        var nextStepCardBottom = document.getElementById('nextStepCardBottom');
        var btnSiguientePaso = document.getElementById('btnSiguientePaso');
        var btnSiguientePasoBottom = document.getElementById('btnSiguientePasoBottom');
        var nextStepIcon = document.getElementById('nextStepIcon');
        var nextStepTitle = document.getElementById('nextStepTitle');
        var nextStepDesc = document.getElementById('nextStepDesc');
        var nextStepTitleBottom = document.getElementById('nextStepTitleBottom');
        var nextStepDescBottom = document.getElementById('nextStepDescBottom');
        var contadorBadge = document.getElementById('contadorDiscrepanciasPendientes');

        // Si hay error de descuadre de totales en Excel, el avance se bloquea de forma absoluta
        if (errorTotalDescuadre) {
            if (nextStepCard) nextStepCard.className = 'next-step-card danger';
            if (nextStepCardBottom) nextStepCardBottom.className = 'next-step-card danger';
            if (btnSiguientePaso) {
                btnSiguientePaso.className = 'btn-next-step disabled';
                btnSiguientePaso.disabled = true;
                btnSiguientePaso.title = 'Bloqueado por descuadre de importes';
            }
            if (btnSiguientePasoBottom) {
                btnSiguientePasoBottom.className = 'btn-next-step disabled';
                btnSiguientePasoBottom.disabled = true;
                btnSiguientePasoBottom.title = 'Bloqueado por descuadre de importes';
            }
            return;
        }

        var selects = document.querySelectorAll('.select-discrepancia');
        var totalDiscrepancias = selects.length;
        var aceptadas = 0;

        selects.forEach(function(sel) {
            if (sel.value === 'ACEPTADO') {
                aceptadas++;
                sel.classList.add('aceptado');
            } else {
                sel.classList.remove('aceptado');
            }
        });

        var pendientes = totalDiscrepancias - aceptadas;

        if (contadorBadge) {
            contadorBadge.innerText = pendientes;
        }

        if (totalDiscrepancias === 0 || pendientes === 0) {
            // Todas las discrepancias han sido aceptadas!
            if (nextStepCard) {
                nextStepCard.className = 'next-step-card unlocked';
            }
            if (nextStepCardBottom) {
                nextStepCardBottom.className = 'next-step-card unlocked';
            }
            if (nextStepIcon) {
                nextStepIcon.style.background = 'rgba(16, 185, 129, 0.15)';
                nextStepIcon.style.color = '#059669';
                nextStepIcon.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            }
            if (nextStepTitle) {
                nextStepTitle.innerHTML = '✅ Validación Completa: Discrepancias Aceptadas (' + aceptadas + '/' + totalDiscrepancias + ')';
            }
            if (nextStepTitleBottom) {
                nextStepTitleBottom.innerHTML = '✅ Validación Completa: Todas las Discrepancias Aceptadas';
            }
            if (nextStepDesc) {
                nextStepDesc.innerHTML = 'Ha aceptado todas las discrepancias de la nómina. Puede continuar al siguiente paso del proceso.';
            }

            if (btnSiguientePaso) {
                btnSiguientePaso.className = 'btn-next-step active';
                btnSiguientePaso.disabled = false;
            }
            if (btnSiguientePasoBottom) {
                btnSiguientePasoBottom.className = 'btn-next-step active';
                btnSiguientePasoBottom.disabled = false;
            }
        } else {
            // Aún hay discrepancias pendientes
            if (nextStepCard) {
                nextStepCard.className = 'next-step-card locked';
            }
            if (nextStepCardBottom) {
                nextStepCardBottom.className = 'next-step-card locked';
            }
            if (nextStepIcon) {
                nextStepIcon.style.background = 'rgba(245, 158, 11, 0.15)';
                nextStepIcon.style.color = '#d97706';
                nextStepIcon.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            }
            if (nextStepTitle) {
                nextStepTitle.innerHTML = '⚠️ Acción Requerida: ' + pendientes + ' Discrepancia' + (pendientes > 1 ? 's' : '') + ' Pendiente' + (pendientes > 1 ? 's' : '') + ' de Aceptación';
            }
            if (nextStepTitleBottom) {
                nextStepTitleBottom.innerHTML = '⚠️ Aceptación de Discrepancias Requerida (' + pendientes + ' pendientes)';
            }
            if (nextStepDesc) {
                nextStepDesc.innerHTML = 'Para habilitar el siguiente paso, seleccione <strong>"Acepto Discrepancia"</strong> en cada registro observado (' + aceptadas + '/' + totalDiscrepancias + ' aceptadas).';
            }

            if (btnSiguientePaso) {
                btnSiguientePaso.className = 'btn-next-step disabled';
                btnSiguientePaso.disabled = true;
            }
            if (btnSiguientePasoBottom) {
                btnSiguientePasoBottom.className = 'btn-next-step disabled';
                btnSiguientePasoBottom.disabled = true;
            }
        }
    }

    // Aceptar todas las discrepancias con 1 clic
    function aceptarTodasLasDiscrepancias() {
        if (errorTotalDescuadre) return;
        var selects = document.querySelectorAll('.select-discrepancia');
        selects.forEach(function(sel) {
            sel.value = 'ACEPTADO';
            sel.classList.add('aceptado');
        });
        evaluarEstadoDiscrepancias();
    }

    // Restablecer todas a pendientes
    function restablecerDiscrepancias() {
        if (errorTotalDescuadre) return;
        var selects = document.querySelectorAll('.select-discrepancia');
        selects.forEach(function(sel) {
            sel.value = 'PENDIENTE';
            sel.classList.remove('aceptado');
        });
        evaluarEstadoDiscrepancias();
    }

    // Modales de siguiente paso
    function abrirModalSiguientePaso() {
        if (errorTotalDescuadre) {
            abrirModalErrorTotal();
            return;
        }
        var modal = document.getElementById('modalSiguientePaso');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function cerrarModalSiguientePaso() {
        var modal = document.getElementById('modalSiguientePaso');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Ejecutar Truncate y Carga en tabla acreditar
    function ejecutarCargaAcreditar() {
        if (errorTotalDescuadre) {
            abrirModalErrorTotal();
            return;
        }

        var btn = document.getElementById('btnConfirmarCarga');
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-block; width:14px; height:14px; border:2px solid #ffffff; border-top-color:transparent; border-radius:50%; animation:spin 0.6s linear infinite; margin-right:8px; vertical-align:middle;"></span> Procesando Carga...';

        var fd = new FormData();
        fd.append('accion', 'cargar_acreditar');
        fd.append('archivo_procesar', '<?php echo htmlspecialchars(basename($archivo_a_procesar), ENT_QUOTES, 'UTF-8'); ?>');
        fd.append('ajax', '1');

        fetch('ImportarAcreditacionesValidado.php', {
            method: 'POST',
            body: fd
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (data.success) {
                // Cambiar al estado de éxito en el modal
                document.getElementById('modalContentConfirmacion').style.display = 'none';
                document.getElementById('modalContentExito').style.display = 'block';

                var cantElem = document.getElementById('exitoCantRegistros');
                var montoElem = document.getElementById('exitoMontoTotal');
                if (cantElem) cantElem.innerText = data.registros;
                if (montoElem) montoElem.innerText = data.total_monto_formateado;

                // Actualizar los botones de la página
                var btnTop = document.getElementById('btnSiguientePaso');
                var btnBottom = document.getElementById('btnSiguientePasoBottom');
                if (btnTop) {
                    btnTop.className = 'btn-next-step active';
                    btnTop.disabled = false;
                    btnTop.innerHTML = '<span>Avanzar a Generar NOVCAR (Paso 3)</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                    btnTop.onclick = function() { window.location.href = 'GenerarCargaCredito2026.php'; };
                }
                if (btnBottom) {
                    btnBottom.className = 'btn-next-step active';
                    btnBottom.disabled = false;
                    btnBottom.innerHTML = '<span>Avanzar a Generar NOVCAR (Paso 3)</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                    btnBottom.onclick = function() { window.location.href = 'GenerarCargaCredito2026.php'; };
                }

                // Inyectar banner de éxito permanente en la página
                var bannerContainer = document.getElementById('contenedorBannerEstado');
                if (bannerContainer) {
                    bannerContainer.innerHTML = '<div class="alert-modern success" style="margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0; margin-top:2px; color: #059669;"><polyline points="20 6 9 17 4 12"></polyline></svg><div><strong>¡Tabla Acreditar Cargada con Éxito!</strong> Se realizó el <code>TRUNCATE</code> e insertaron <strong>' + data.registros + '</strong> registros por <strong>' + data.total_monto_formateado + '</strong>. <a href="GenerarCargaCredito2026.php" style="color: inherit; font-weight: 800; text-decoration: underline; margin-left: 8px;">Avanzar al Paso 3: Generar Lote NOVCAR &rarr;</a></div></div>';
                }
            } else {
                alert('Error al realizar la carga: ' + (data.mensaje || 'Error desconocido'));
                btn.disabled = false;
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Reintentar: Truncate y Cargar en Acreditar</span>';
            }
        })
        .catch(function(err) {
            alert('Error de comunicación con el servidor: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Reintentar: Truncate y Cargar en Acreditar</span>';
        });
    }

    // Evaluación inicial al cargar
    document.addEventListener('DOMContentLoaded', function() {
        evaluarEstadoDiscrepancias();
    });
</script>

</body>
</html>
