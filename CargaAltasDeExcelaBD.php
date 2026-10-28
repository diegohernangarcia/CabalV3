<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Importación y Validación de Planilla Excel Altas a Base de Datos
 * Entidad 782 - Cabal
 * 
 * Directivas de Negocio y Flujo:
 * 1. El procesamiento se ejecuta EXCLUSIVAMENTE cuando el usuario presiona "Subir y Analizar"
 *    o solicita analizar el archivo disponible en el servidor.
 * 2. Se localiza dinámicamente en el primer libro la celda cabecera donde comienza "Apellido"
 *    (independientemente de la fila o columna inicial).
 * 3. Se mapean las cabeceras con la estructura oficial de la tabla altas.
 * 4. Se aplican las normas de validación Cabal (docs/normas_de_validacion.md).
 * 5. Se verifica si el CUIT de cada registro existe en la tabla beneficiario.
 * 6. Se previsualizan los registros en pantalla con la estructura de la tabla altas.
 * 7. Si hay algún CUIT en beneficiario o error de validación: se informa y se inhabilita "Guardar datos en BD".
 * 8. Si no hay duplicados ni errores: se habilita el botón "Guardar datos en BD".
 * 9. NUNCA guardar sin confirmación explícita. Al confirmar:
 *    Paso 1: TRUNCATE TABLE altas
 *    Paso 2: Cargar datos importados a la tabla altas
 */

// Configuración de límites y memoria para planillas de cálculo
ini_set('memory_limit', '512M');
set_time_limit(180);

require_once('phpexcel/PHPExcel.php');
require_once('BD/UsarGestion.php');
require_once('BD/Funciones.php');

$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$archivo_excel = $targetDir . "Altas.xlsx";
$archivo_existe = file_exists($archivo_excel);

// -----------------------------------------------------------------------------
// DICCIONARIOS Y NORMAS DE VALIDACIÓN CABAL (docs/normas_de_validacion.md)
// -----------------------------------------------------------------------------
$provincias_anexo9 = array(
    '01' => 'CAPITAL FEDERAL',
    '02' => 'PROVINCIA DE BUENOS AIRES',
    '03' => 'PROVINCIA DE CATAMARCA',
    '04' => 'PROVINCIA DE CORDOBA',
    '05' => 'PROVINCIA DE CORRIENTES',
    '06' => 'PROVINCIA DE CHACO',
    '07' => 'PROVINCIA DE CHUBUT',
    '08' => 'PROVINCIA DE ENTRE RIOS',
    '09' => 'PROVINCIA DE FORMOSA',
    '10' => 'PROVINCIA DE JUJUY',
    '11' => 'PROVINCIA DE LA PAMPA',
    '12' => 'PROVINCIA DE LA RIOJA',
    '13' => 'PROVINCIA DE MENDOZA',
    '14' => 'PROVINCIA DE MISIONES',
    '15' => 'PROVINCIA DE NEUQUEN',
    '16' => 'PROVINCIA DE RIO NEGRO',
    '17' => 'PROVINCIA DE SALTA',
    '18' => 'PROVINCIA DE SAN JUAN',
    '19' => 'PROVINCIA DE SAN LUIS',
    '20' => 'PROVINCIA DE SANTA CRUZ',
    '21' => 'PROVINCIA DE SANTA FE',
    '22' => 'PROVINCIA DE SANTIAGO DEL ESTERO',
    '23' => 'PROVINCIA DE TUCUMAN',
    '41' => 'PROVINCIA DE TIERRA DEL FUEGO'
);

$tipos_doc_anexo10 = array(
    '1' => '01', '01' => '01', 'LE' => '01',
    '2' => '02', '02' => '02', 'LC' => '02',
    '3' => '03', '03' => '03', 'DNI' => '03',
    '4' => '04', '04' => '04', 'CI' => '04',
    '5' => '05', '05' => '05', 'DNE' => '05',
    '6' => '06', '06' => '06', 'DIN' => '06',
    '7' => '07', '07' => '07', 'CIM' => '07',
    '8' => '08', '08' => '08', 'PER' => '08',
    '9' => '09', '09' => '09', 'OTR' => '09', 'OTRO' => '09', 'OTROS' => '09'
);

function normalizarEstadoCivil($val) {
    $val = strtoupper(trim((string)$val));
    if ($val === '') return 'S';
    $first = substr($val, 0, 1);
    if (in_array($first, array('C', 'S', 'V', 'O'))) {
        return $first;
    }
    return false;
}

function normalizarFechaNacimiento($val) {
    $val = trim((string)$val);
    if ($val === '') {
        return '01011950'; // Genérica válida según norma
    }
    // Si viene como serial de fecha de Excel
    if (is_numeric($val) && (float)$val > 1000 && (float)$val < 100000) {
        $ts = PHPExcel_Shared_Date::ExcelToPHP((float)$val);
        if ($ts > 0) {
            return date("dmY", $ts);
        }
    }
    // Si viene con barras o guiones (ej. 28/03/1971 o 28-03-1971)
    if (strpos($val, '/') !== false || strpos($val, '-') !== false) {
        $partes = preg_split('/[\/\-]/', $val);
        if (count($partes) === 3) {
            $p1 = (int)$partes[0];
            $p2 = (int)$partes[1];
            $p3 = (int)$partes[2];
            // Si vino AAAA-MM-DD
            if ($p1 > 1900 && $p1 < 2050 && checkdate($p2, $p3, $p1)) {
                return str_pad($p3, 2, '0', STR_PAD_LEFT) . str_pad($p2, 2, '0', STR_PAD_LEFT) . $p1;
            }
            // Si vino DD/MM/AAAA
            if ($p3 > 1900 && $p3 < 2050 && checkdate($p2, $p1, $p3)) {
                return str_pad($p1, 2, '0', STR_PAD_LEFT) . str_pad($p2, 2, '0', STR_PAD_LEFT) . $p3;
            }
        }
    }
    // Si viene sólo dígitos
    $soloNum = preg_replace('/[^0-9]/', '', $val);
    if (strlen($soloNum) === 8) {
        $p1 = (int)substr($soloNum, 0, 2);
        $p2 = (int)substr($soloNum, 2, 2);
        $p3 = (int)substr($soloNum, 4, 4);
        if ($p3 > 1900 && $p3 < 2050 && checkdate($p2, $p1, $p3)) {
            return $soloNum; // DDMMAAAA
        }
        $y = (int)substr($soloNum, 0, 4);
        $m = (int)substr($soloNum, 4, 2);
        $d = (int)substr($soloNum, 6, 2);
        if ($y > 1900 && $y < 2050 && checkdate($m, $d, $y)) {
            return str_pad($d, 2, '0', STR_PAD_LEFT) . str_pad($m, 2, '0', STR_PAD_LEFT) . $y;
        }
    }
    return false;
}

/**
 * Obtiene el valor seguro de una celda sin colapsar por errores de fórmula
 */
function obtenerValorCeldaSeguro($sheet, $col, $row) {
    try {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
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

// -----------------------------------------------------------------------------
// ESTADO Y CONTROLADOR DE FLUJO
// -----------------------------------------------------------------------------
$registros = array();
$errores = array();
$cuits_en_beneficiario = array();
$total_filas = 0;
$total_validos = 0;
$total_cuits_en_beneficiario = 0;
$puede_guardar = false;

// Estados posibles:
// 'inicial': Entró a la página por GET, esperando acción del usuario.
// 'previsualizacion_ok': Se procesó el archivo y 100% registros están listos para guardar.
// 'error_validacion': Se procesó el archivo y se detectaron inconsistencias o CUITs en padrón.
// 'guardado_exito': Se confirmó el guardado (TRUNCATE + INSERT ejecutados).
// 'error_lectura': Hubo un error de lectura o formato en el archivo Excel.
$estado_proceso = 'inicial';
$mensaje_proceso = '';
$nombre_archivo_procesado = '';

$accion_solicitada = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["accion"])) {
        $accion_solicitada = $_POST["accion"];
    } elseif (isset($_FILES["excel_file"])) {
        $accion_solicitada = "subir_analizar";
    }
}

// -----------------------------------------------------------------------------
// 1. MANEJO DE SUBIDA DEL ARCHIVO EXCEL
// -----------------------------------------------------------------------------
if ($accion_solicitada === "subir_analizar") {
    if (isset($_FILES["excel_file"]) && $_FILES["excel_file"]["error"] === UPLOAD_ERR_OK) {
        $nombreOriginal = basename($_FILES["excel_file"]["name"]);
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        
        if ($extension === "xlsx") {
            if (move_uploaded_file($_FILES["excel_file"]["tmp_name"], $archivo_excel)) {
                $nombre_archivo_procesado = $nombreOriginal;
                $archivo_existe = true;
                // Marcado para procesar inmediatamente a continuación
            } else {
                $estado_proceso = 'error_lectura';
                $mensaje_proceso = 'No se pudo mover el archivo subido al directorio de trabajo en el servidor.';
            }
        } else {
            $estado_proceso = 'error_lectura';
            $mensaje_proceso = 'Formato no admitido. Se requiere exclusivamente una planilla con extensión <b>.xlsx</b> (Excel 2007/365).';
        }
    } else {
        $estado_proceso = 'error_lectura';
        $errCode = isset($_FILES["excel_file"]["error"]) ? $_FILES["excel_file"]["error"] : -1;
        if ($errCode === UPLOAD_ERR_NO_FILE) {
            $mensaje_proceso = 'No se seleccionó ningún archivo para subir. Por favor elija un archivo .xlsx.';
        } elseif ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $mensaje_proceso = 'El archivo supera el tamaño máximo permitido por el servidor.';
        } else {
            $mensaje_proceso = 'Ocurrió un error en la subida del archivo (Código de error: ' . $errCode . ').';
        }
    }
}

// -----------------------------------------------------------------------------
// 2. PROCESAMIENTO Y ANÁLISIS DE LA PLANILLA EXCEL
//    Solo se procesa si se subió un archivo, si se pidió analizar el existente,
//    o si se confirmó la acción de guardado.
// -----------------------------------------------------------------------------
$debe_procesar = (
    ($accion_solicitada === "subir_analizar" && $estado_proceso !== 'error_lectura') ||
    ($accion_solicitada === "confirmar_guardar" && $archivo_existe)
);

if ($debe_procesar) {
    if (!$archivo_existe) {
        $estado_proceso = 'error_lectura';
        $mensaje_proceso = 'No existe el archivo archivos/Altas.xlsx para procesar. Por favor suba un archivo.';
    } else {
        try {
            $inputFileType = PHPExcel_IOFactory::identify($archivo_excel);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            // setReadDataOnly para máxima velocidad y evitar colgarse con estilos pesados
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($archivo_excel);

            // Siempre procesar el primer libro (hoja índice 0)
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = (int)$sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = PHPExcel_Cell::columnIndexFromString($highestCol);

            // -----------------------------------------------------------------
            // PASO A: LOCALIZAR LA CABECERA DONDE COMIENZA "Apellido"
            //         "La tabla del excel comienza con Apellido"
            // -----------------------------------------------------------------
            $headerRow = -1;
            $startCol = -1;
            $maxSearchRow = min($highestRow, 40);
            $maxSearchCol = min($highestColIndex, 40);

            for ($r = 1; $r <= $maxSearchRow; $r++) {
                for ($c = 0; $c < $maxSearchCol; $c++) {
                    $val = obtenerValorCeldaSeguro($sheet, $c, $r);
                    if ($val === '') continue;
                    $clean = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $val));
                    $clean = preg_replace('/[^a-z0-9]/', '', $clean);
                    if ($clean === 'apellido') {
                        $headerRow = $r;
                        $startCol = $c;
                        break 2;
                    }
                }
            }

            if ($headerRow === -1) {
                throw new Exception("No se encontró la cabecera 'Apellido' en la primera hoja de la planilla. Asegúrese de que la tabla contenga la columna 'Apellido'.");
            }

            // -----------------------------------------------------------------
            // PASO B: MAPEO DINÁMICO DE CABECERAS A LA ESTRUCTURA DE LA TABLA ALTAS
            // -----------------------------------------------------------------
            $colMap = array();
            for ($c = $startCol; $c < $highestColIndex; $c++) {
                $rawH = obtenerValorCeldaSeguro($sheet, $c, $headerRow);
                if ($rawH === '') continue;
                $h = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $rawH));
                $h = preg_replace('/[^a-z0-9]/', '', $h);
                if ($h === '') continue;

                if (strpos($h, 'apellido') !== false && !isset($colMap['apellido'])) {
                    $colMap['apellido'] = $c;
                } elseif (strpos($h, 'nombre') !== false && !isset($colMap['nombre'])) {
                    $colMap['nombre'] = $c;
                } elseif ((strpos($h, 'calle') !== false || strpos($h, 'domicilio') !== false) && strpos($h, 'n') === false && strpos($h, 'piso') === false && strpos($h, 'dep') === false && !isset($colMap['calle'])) {
                    $colMap['calle'] = $c;
                } elseif ((strpos($h, 'num') !== false || strpos($h, 'n') !== false || strpos($h, 'nro') !== false) && strpos($h, 'tel') === false && strpos($h, 'doc') === false && !isset($colMap['numero'])) {
                    $colMap['numero'] = $c;
                } elseif (strpos($h, 'piso') !== false && !isset($colMap['piso'])) {
                    $colMap['piso'] = $c;
                } elseif ((strpos($h, 'depto') !== false || strpos($h, 'dep') !== false) && !isset($colMap['depto'])) {
                    $colMap['depto'] = $c;
                } elseif (strpos($h, 'loc') !== false && !isset($colMap['localidad'])) {
                    $colMap['localidad'] = $c;
                } elseif ((strpos($h, 'pcia') !== false || strpos($h, 'prov') !== false) && strpos($h, 'doc') === false && !isset($colMap['provincia'])) {
                    $colMap['provincia'] = $c;
                } elseif (strpos($h, 'sex') !== false && !isset($colMap['sexo'])) {
                    $colMap['sexo'] = $c;
                } elseif ((strpos($h, 'cp') !== false || strpos($h, 'postal') !== false) && !isset($colMap['cp'])) {
                    $colMap['cp'] = $c;
                } elseif ((strpos($h, 'carac') !== false || strpos($h, 'caract') !== false) && !isset($colMap['tel_carac'])) {
                    $colMap['tel_carac'] = $c;
                } elseif (strpos($h, 'tel') !== false && !isset($colMap['tel_num'])) {
                    $colMap['tel_num'] = $c;
                } elseif (strpos($h, 'civil') !== false && !isset($colMap['estado_civil'])) {
                    $colMap['estado_civil'] = $c;
                } elseif ((strpos($h, 'fechanac') !== false || strpos($h, 'nac') !== false) && !isset($colMap['fecha_nac'])) {
                    $colMap['fecha_nac'] = $c;
                } elseif ((strpos($h, 'tipodoc') !== false || (strpos($h, 'tipo') !== false && strpos($h, 'doc') !== false)) && !isset($colMap['doc_tipo'])) {
                    $colMap['doc_tipo'] = $c;
                } elseif ((strpos($h, 'numerodoc') !== false || strpos($h, 'numdoc') !== false || strpos($h, 'dni') !== false) && !isset($colMap['doc_num'])) {
                    $colMap['doc_num'] = $c;
                } elseif (strpos($h, 'prov') !== false && strpos($h, 'doc') !== false && !isset($colMap['doc_prov'])) {
                    $colMap['doc_prov'] = $c;
                } elseif ((strpos($h, 'cuit') !== false || strpos($h, 'cuil') !== false) && !isset($colMap['cuit'])) {
                    $colMap['cuit'] = $c;
                }
            }

            // Fallback relativo a partir de startCol si alguna columna no tuvo coincidencia textual
            $defaults_rel = array(
                'apellido' => 0, 'nombre' => 1, 'calle' => 2, 'numero' => 3, 'piso' => 4,
                'depto' => 5, 'localidad' => 6, 'provincia' => 7, 'sexo' => 8, 'cp' => 9,
                'tel_carac' => 10, 'tel_num' => 11, 'estado_civil' => 12, 'fecha_nac' => 13,
                'doc_tipo' => 14, 'doc_num' => 15, 'doc_prov' => 16, 'cuit' => 17
            );
            foreach ($defaults_rel as $k => $relOffset) {
                if (!isset($colMap[$k])) {
                    $colMap[$k] = $startCol + $relOffset;
                }
            }

            $dnis_en_archivo = array();
            $cuits_en_archivo = array();
            $emptyConsecutiveRows = 0;

            // Prepared statement para chequear CUIT en tabla beneficiario
            $stmt_check_cuit = $conn->prepare("SELECT id, Apellido, Nombre, NumeroDocumento, Cuit FROM beneficiario WHERE REPLACE(REPLACE(TRIM(Cuit), '-', ''), ' ', '') = ? LIMIT 1");

            // -----------------------------------------------------------------
            // PASO C: RECORRIDO DE DATOS A PARTIR DE ($headerRow + 1)
            // -----------------------------------------------------------------
            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $apellido     = obtenerValorCeldaSeguro($sheet, $colMap['apellido'], $row);
                $nombre       = obtenerValorCeldaSeguro($sheet, $colMap['nombre'], $row);
                $calle        = obtenerValorCeldaSeguro($sheet, $colMap['calle'], $row);
                $numero       = obtenerValorCeldaSeguro($sheet, $colMap['numero'], $row);
                $piso         = obtenerValorCeldaSeguro($sheet, $colMap['piso'], $row);
                $depto        = obtenerValorCeldaSeguro($sheet, $colMap['depto'], $row);
                $localidad    = obtenerValorCeldaSeguro($sheet, $colMap['localidad'], $row);
                $provincia    = obtenerValorCeldaSeguro($sheet, $colMap['provincia'], $row);
                $sexo         = strtoupper(obtenerValorCeldaSeguro($sheet, $colMap['sexo'], $row));
                $cp           = obtenerValorCeldaSeguro($sheet, $colMap['cp'], $row);
                $tel_carac    = obtenerValorCeldaSeguro($sheet, $colMap['tel_carac'], $row);
                $tel_num      = obtenerValorCeldaSeguro($sheet, $colMap['tel_num'], $row);
                $estado_civil = obtenerValorCeldaSeguro($sheet, $colMap['estado_civil'], $row);
                $fecha_nac    = obtenerValorCeldaSeguro($sheet, $colMap['fecha_nac'], $row);
                $doc_tipo     = obtenerValorCeldaSeguro($sheet, $colMap['doc_tipo'], $row);
                $doc_num      = obtenerValorCeldaSeguro($sheet, $colMap['doc_num'], $row);
                $doc_prov     = obtenerValorCeldaSeguro($sheet, $colMap['doc_prov'], $row);
                $cuit         = obtenerValorCeldaSeguro($sheet, $colMap['cuit'], $row);

                // Detección de fila vacía
                if ($apellido === '' && $nombre === '' && $doc_num === '' && $cuit === '') {
                    $emptyConsecutiveRows++;
                    if ($emptyConsecutiveRows >= 5) {
                        // 5 filas vacías consecutivas marcan el final real de la tabla
                        break;
                    }
                    continue;
                }
                $emptyConsecutiveRows = 0;

                $total_filas++;
                $fila_errores = array();
                $cuit_en_padron = false;
                $datos_beneficiario = null;

                // -------------------------------------------------------------
                // APLICACIÓN DE NORMAS DE VALIDACIÓN CABAL (docs/normas_de_validacion.md)
                // -------------------------------------------------------------
                
                // Apellido: No puede superar los 16 caracteres
                if ($apellido === '') {
                    $fila_errores[] = "Apellido obligatorio no puede estar vacío";
                } elseif (mb_strlen($apellido, 'UTF-8') > 16) {
                    $fila_errores[] = "Apellido supera 16 caracteres (tiene " . mb_strlen($apellido, 'UTF-8') . ")";
                }

                // Apellido y Nombre: Entre ambos no pueden superar los 24 caracteres
                $largo_apynom = mb_strlen(trim($apellido . ' ' . $nombre), 'UTF-8');
                if ($largo_apynom > 24) {
                    $fila_errores[] = "Apellido y Nombre combinados superan los 24 caracteres (tiene {$largo_apynom})";
                }

                // Domicilio Calle: Máximo 25 Caracteres
                if (mb_strlen($calle, 'UTF-8') > 25) {
                    $fila_errores[] = "Domicilio Calle supera 25 caracteres (tiene " . mb_strlen($calle, 'UTF-8') . ")";
                }

                // Domicilio Número: Máximo 5 Caracteres
                if (mb_strlen($numero, 'UTF-8') > 5) {
                    $fila_errores[] = "Domicilio Número supera 5 caracteres";
                }

                // Domicilio Piso: Máximo 2 Caracteres (dejar en blanco si no posee)
                if (mb_strlen($piso, 'UTF-8') > 2) {
                    $fila_errores[] = "Domicilio Piso supera 2 caracteres";
                }

                // Domicilio Departamento: Máximo 2 Caracteres (dejar en blanco si no posee)
                if (mb_strlen($depto, 'UTF-8') > 2) {
                    $fila_errores[] = "Domicilio Departamento supera 2 caracteres";
                }

                // Localidad: Máximo 30 Caracteres
                if (mb_strlen($localidad, 'UTF-8') > 30) {
                    $fila_errores[] = "Localidad supera 30 caracteres";
                }

                // Provincia: Según Anexo 9
                $prov_cod = str_pad(preg_replace('/[^0-9]/', '', $provincia), 2, '0', STR_PAD_LEFT);
                if (!isset($provincias_anexo9[$prov_cod])) {
                    $fila_errores[] = "Provincia inválida (valor: '{$provincia}'). Debe corresponder al Anexo 9 (Ej: 16 = Río Negro)";
                } else {
                    $provincia = $prov_cod;
                }

                // Sexo: SOLO M o F
                if ($sexo !== 'M' && $sexo !== 'F') {
                    $fila_errores[] = "Sexo debe ser estrictamente 'M' o 'F' (recibido: '{$sexo}')";
                }

                // Código Postal: Máximo 8 caracteres
                if (mb_strlen($cp, 'UTF-8') > 8) {
                    $fila_errores[] = "Código Postal supera 8 caracteres";
                }

                // Teléfono Característica: Máximo 5 Caracteres
                if (mb_strlen($tel_carac, 'UTF-8') > 5) {
                    $fila_errores[] = "Teléfono Característica supera 5 caracteres";
                }

                // Teléfono Número: Máximo 8 Caracteres
                if (mb_strlen($tel_num, 'UTF-8') > 8) {
                    $fila_errores[] = "Teléfono Número supera 8 caracteres";
                }

                // Estado Civil: Solo C=Casado S=Soltero V=Viudo O=Otro
                $ec_norm = normalizarEstadoCivil($estado_civil);
                if ($ec_norm === false) {
                    $fila_errores[] = "Estado Civil inválido ('{$estado_civil}'). Valores admitidos: C (Casado), S (Soltero), V (Viudo), O (Otro)";
                } else {
                    $estado_civil = $ec_norm;
                }

                // Fecha Nacimiento: Formato DDMMAAAA (o genérica 01/01/1950)
                $fn_norm = normalizarFechaNacimiento($fecha_nac);
                if ($fn_norm === false) {
                    $fila_errores[] = "Fecha de Nacimiento inválida ('{$fecha_nac}'). Formato requerido: DDMMAAAA";
                } else {
                    $fecha_nac = $fn_norm;
                }

                // Tipo Documento: Según Anexo 10 (1=LE, 2=LC, 3=DNI, etc.)
                $td_upper = strtoupper(trim((string)$doc_tipo));
                if (isset($tipos_doc_anexo10[$td_upper])) {
                    $doc_tipo = $tipos_doc_anexo10[$td_upper];
                } elseif (is_numeric($td_upper) && strlen($td_upper) <= 2) {
                    $doc_tipo = str_pad($td_upper, 2, '0', STR_PAD_LEFT);
                } else {
                    $fila_errores[] = "Tipo de Documento inválido ('{$doc_tipo}'). Debe corresponder al Anexo 10 (Ej: DNI o 03)";
                }

                // Número Documento: Máximo 11 Caracteres
                $clean_doc = preg_replace('/[^0-9]/', '', (string)$doc_num);
                if ($clean_doc === '') {
                    $fila_errores[] = "Número de Documento obligatorio no puede estar vacío";
                } elseif (strlen($clean_doc) > 11) {
                    $fila_errores[] = "Número de Documento supera 11 caracteres";
                } else {
                    $doc_num = $clean_doc;
                }

                // Control de duplicados de DNI en el mismo archivo
                if ($doc_num !== '') {
                    if (isset($dnis_en_archivo[$doc_num])) {
                        $fila_errores[] = "Número de Documento duplicado en la fila " . $dnis_en_archivo[$doc_num] . " del archivo";
                    } else {
                        $dnis_en_archivo[$doc_num] = $row;
                    }
                }

                // Provincia Documento: Si tipo 4 (CI) anexo 9, sino 0
                if ($doc_tipo === '04' || $doc_tipo === '4') {
                    $doc_prov = str_pad(preg_replace('/[^0-9]/', '', $doc_prov), 2, '0', STR_PAD_LEFT);
                } else {
                    $doc_prov = '0';
                }

                // CUIT: Máximo 11 Caracteres. Se puede completar con todos 0 si no se valida
                $clean_cuit = preg_replace('/[^0-9]/', '', (string)$cuit);
                if ($clean_cuit === '') {
                    $clean_cuit = '00000000000';
                }
                if (strlen($clean_cuit) > 11) {
                    $fila_errores[] = "CUIT supera 11 dígitos";
                } else {
                    $cuit = str_pad($clean_cuit, 11, '0', STR_PAD_LEFT);
                }

                // Control de CUIT duplicado en el mismo archivo (si no son todos ceros)
                if ($cuit !== '00000000000') {
                    if (isset($cuits_en_archivo[$cuit])) {
                        $fila_errores[] = "CUIT duplicado en la fila " . $cuits_en_archivo[$cuit] . " del mismo archivo";
                    } else {
                        $cuits_en_archivo[$cuit] = $row;
                    }
                }

                // -------------------------------------------------------------
                // VALIDACIÓN CRÍTICA: ¿EL CUIT EXISTE EN LA TABLA BENEFICIARIO?
                // -------------------------------------------------------------
                if ($cuit !== '00000000000' && strlen($cuit) >= 8) {
                    $stmt_check_cuit->execute(array($cuit));
                    $bene = $stmt_check_cuit->fetch(PDO::FETCH_ASSOC);
                    if ($bene) {
                        $cuit_en_padron = true;
                        $datos_beneficiario = $bene;
                        $total_cuits_en_beneficiario++;
                        $error_cuit_msg = "El CUIT {$cuit} YA SE ENCUENTRA REGISTRADO en Beneficiarios (Titular: " . trim($bene['Apellido']) . " " . trim($bene['Nombre']) . " - DNI: " . trim($bene['NumeroDocumento']) . ")";
                        $fila_errores[] = $error_cuit_msg;
                        $cuits_en_beneficiario[] = array(
                            'fila'            => $row,
                            'cuit'            => $cuit,
                            'titular_excel'   => $apellido . ' ' . $nombre,
                            'dni_excel'       => $doc_num,
                            'beneficiario_bd' => trim($bene['Apellido']) . ' ' . trim($bene['Nombre']),
                            'dni_bd'          => trim($bene['NumeroDocumento'])
                        );
                    }
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
                            'cuit'    => $cuit,
                            'error'   => $err
                        );
                    }
                }

                $registros[] = array(
                    'fila'                   => $row,
                    'valido'                 => $es_valido,
                    'cuit_en_padron'         => $cuit_en_padron,
                    'datos_beneficiario'     => $datos_beneficiario,
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

            // Condición estricta para permitir guardar
            $puede_guardar = ($total_filas > 0 && count($errores) === 0 && $total_cuits_en_beneficiario === 0);

            // -----------------------------------------------------------------
            // PASO D: ACCIÓN DE CONFIRMACIÓN EXPLÍCITA DE GUARDADO EN BD
            // -----------------------------------------------------------------
            if ($accion_solicitada === "confirmar_guardar") {
                if ($puede_guardar) {
                    try {
                        $conn->beginTransaction();

                        // PASO 1: TRUNCATE a la tabla altas
                        $conn->exec("TRUNCATE TABLE altas");

                        // PASO 2: Cargar los datos importados a la tabla altas
                        $sql_ins = "INSERT INTO altas (
                            Apellido, Nombre, DomicilioCalle, DomicilioNumero, DomicilioPiso, DomicilioDepartamento,
                            Localidad, Provincia, Sexo, CodigoPostal, TelefonoCaracteristica, TelefonoNumero,
                            EstadoCivil, FechaNacimiento, DocumentoTipo, DocumentoNumero, DocumentoProvincia, CUIT
                        ) VALUES (
                            :Apellido, :Nombre, :DomicilioCalle, :DomicilioNumero, :DomicilioPiso, :DomicilioDepartamento,
                            :Localidad, :Provincia, :Sexo, :CodigoPostal, :TelefonoCaracteristica, :TelefonoNumero,
                            :EstadoCivil, :FechaNacimiento, :DocumentoTipo, :DocumentoNumero, :DocumentoProvincia, :CUIT
                        )";

                        $stmt_ins = $conn->prepare($sql_ins);

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

                        $conn->commit();
                        $estado_proceso = 'guardado_exito';
                        $mensaje_proceso = '¡Datos guardados con éxito en la base de datos! Se ejecutó TRUNCATE sobre la tabla <code>altas</code> y se insertaron ' . count($registros) . ' registros validados.';
                    } catch (Exception $e) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        $estado_proceso = 'error_lectura';
                        $mensaje_proceso = 'Error crítico al guardar los registros en la base de datos: ' . $e->getMessage();
                    }
                } else {
                    $estado_proceso = 'error_validacion';
                    $mensaje_proceso = 'No se puede guardar: se detectaron CUITs existentes en Beneficiarios o inconsistencias normativas.';
                }
            } else {
                // Modo previsualización posterior al análisis
                if ($puede_guardar) {
                    $estado_proceso = 'previsualizacion_ok';
                    $mensaje_proceso = 'Planilla analizada con éxito. Todos los registros cumplen las normas y ningún CUIT coincide con Beneficiarios. Presione el botón <b>"Guardar datos en BD"</b> para confirmar la carga.';
                } else {
                    $estado_proceso = 'error_validacion';
                    if ($total_cuits_en_beneficiario > 0) {
                        $mensaje_proceso = 'Se detectaron <b>' . $total_cuits_en_beneficiario . ' CUIT(s)</b> que ya existen en el padrón de Beneficiarios. La opción <b>"Guardar datos en BD"</b> se encuentra inhabilitada.';
                    } else {
                        $mensaje_proceso = 'Se detectaron <b>' . count($errores) . ' inconsistencia(s)</b> con las normas de validación Cabal. Corrija la planilla para habilitar el guardado.';
                    }
                }
            }

        } catch (Exception $e) {
            $estado_proceso = 'error_lectura';
            $mensaje_proceso = 'Error al procesar el archivo Excel: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal 2026</title>
    <meta name="description" content="Módulo de validación de longitud reglamentaria Cabal, control de CUITs en padrón y carga confirmada a la tabla altas">
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1540px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .status-hero-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            padding: 26px 30px;
            margin-bottom: 24px;
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

        .banner-outcome.info {
            background: #eff6ff;
            border: 1px solid rgba(59, 130, 246, 0.35);
            color: #1e40af;
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

        .banner-outcome.success .outcome-icon { background: var(--emerald-600); }
        .banner-outcome.error .outcome-icon { background: #e11d48; }
        .banner-outcome.warning .outcome-icon { background: var(--amber-500); }
        .banner-outcome.info .outcome-icon { background: #2563eb; }

        .outcome-texts h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .outcome-texts p {
            font-size: 0.88rem;
            margin: 0;
            line-height: 1.45;
        }

        .summary-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-stat-box {
            background: var(--surface-subtle);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 16px 20px;
        }

        .stat-box-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-box-value {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--navy-900);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .stat-box-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .errors-card {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: var(--radius-lg);
            padding: 22px;
            margin-bottom: 24px;
        }

        [data-theme="dark"] .errors-card {
            background: #2a1215;
            border-color: #7f1d1d;
        }

        .errors-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: #991b1b;
            margin-bottom: 14px;
        }

        [data-theme="dark"] .errors-card-header { color: #fca5a5; }

        .table-errors {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            background: #ffffff;
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        [data-theme="dark"] .table-errors { background: #1e1315; }

        .table-errors th {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
            padding: 10px 14px;
            text-align: left;
        }

        [data-theme="dark"] .table-errors th {
            background: #451a1a;
            color: #fecaca;
        }

        .table-errors td {
            padding: 9px 14px;
            border-bottom: 1px solid #fecaca;
            color: #7f1d1d;
        }

        [data-theme="dark"] .table-errors td {
            border-bottom-color: #551c1c;
            color: #fca5a5;
        }

        .preview-section-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .preview-section-header {
            padding: 18px 24px;
            background: var(--surface-subtle);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .table-scroll-container {
            max-height: 580px;
            overflow-y: auto;
            overflow-x: auto;
        }

        .altas-data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.80rem;
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
            padding: 11px 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .altas-data-table td {
            padding: 9px 12px;
            border-bottom: 1px solid var(--border-light);
            white-space: nowrap;
            color: var(--text-main);
        }

        .altas-data-table tr.row-invalid {
            background: #fff1f2;
        }

        [data-theme="dark"] .altas-data-table tr.row-invalid {
            background: #331518;
        }

        .altas-data-table tr:hover {
            background: var(--surface-subtle);
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

        .badge-status-val.cuit-dup {
            background: #fef08a;
            color: #854d0e;
            border: 1px solid #facc15;
        }

        .btn-confirmar-guardar {
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: var(--radius-md);
            color: #ffffff;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
            transition: all var(--transition-fast);
        }

        .btn-confirmar-guardar:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.45);
        }

        .btn-guardar-disabled {
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: var(--radius-md);
            color: #94a3b8;
            background: #334155;
            border: 1px solid #475569;
            cursor: not-allowed;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            opacity: 0.65;
        }

        /* Spinner de carga */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ==========================================================================
           VENTANA EMERGENTE MODAL DE CONFIRMACIÓN (COLORES INSTITUCIONALES CABAL FSR)
           ========================================================================== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.68);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.22s ease-out;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-container {
            background: var(--surface-card, #ffffff);
            color: var(--text-main, #0f172a);
            width: 100%;
            max-width: 540px;
            border-radius: var(--radius-xl, 16px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px var(--border-light, rgba(226, 232, 240, 0.8));
            overflow: hidden;
            transform: translateY(18px) scale(0.97);
            transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-overlay.active .modal-container {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .modal-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        .modal-header-text h3 {
            margin: 0;
            font-size: 1.12rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .modal-header-text p {
            margin: 3px 0 0;
            font-size: 0.80rem;
            color: #bfdbfe;
            font-weight: 500;
        }

        .modal-btn-close {
            background: rgba(255, 255, 255, 0.14);
            border: none;
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: background 0.15s ease, transform 0.15s ease;
        }

        .modal-btn-close:hover {
            background: rgba(255, 255, 255, 0.28);
            transform: scale(1.08);
        }

        .modal-body {
            padding: 24px;
            font-size: 0.90rem;
            line-height: 1.5;
        }

        .modal-alert-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: var(--radius-md, 10px);
            padding: 14px 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px;
            color: #92400e;
        }

        [data-theme="dark"] .modal-alert-box {
            background: #2b1f0c;
            border-color: #854d0e;
            color: #fde68a;
        }

        .modal-detail-list {
            background: var(--surface-subtle, #f8fafc);
            border: 1px solid var(--border-light, #e2e8f0);
            border-radius: var(--radius-md, 10px);
            padding: 12px 18px;
            margin-bottom: 18px;
        }

        .modal-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-light, #e2e8f0);
            font-size: 0.85rem;
        }

        .modal-detail-row:last-child {
            border-bottom: none;
            padding-bottom: 2px;
        }

        .modal-detail-label {
            color: var(--text-muted, #64748b);
            font-weight: 600;
        }

        .modal-detail-value {
            font-weight: 700;
            color: var(--navy-900, #0f172a);
        }

        [data-theme="dark"] .modal-detail-value {
            color: #f8fafc;
        }

        .modal-footer {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--border-light, #e2e8f0);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: var(--surface-subtle, #f8fafc);
            border-radius: 0 0 var(--radius-xl, 16px) var(--radius-xl, 16px);
        }

        .modal-btn-cancel {
            padding: 11px 20px;
            border-radius: var(--radius-md, 8px);
            font-size: 0.90rem;
            font-weight: 600;
            background: transparent;
            border: 1px solid var(--border-light, #cbd5e1);
            color: var(--text-main, #334155);
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }

        .modal-btn-cancel:hover {
            background: var(--border-light, #e2e8f0);
            color: var(--navy-900, #0f172a);
        }

        .modal-btn-confirm {
            padding: 11px 22px;
            border-radius: var(--radius-md, 8px);
            font-size: 0.90rem;
            font-weight: 700;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
            transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
        }

        .modal-btn-confirm:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.45);
        }
    </style>
</head>
<body>

<!-- BARRA DE NAVEGACIÓN INSTITUCIONAL -->
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
            <a href="SubirArchivo.html" class="btn-nav-action" id="btn-subir-archivo">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Centro de Subidas
            </a>

            <a href="GenerarNovedadAltas.php" class="btn-nav-action" id="btn-generar-altas">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                Generar ALTAUSUCBPRE
            </a>

            <a href="index.php" class="btn-nav-action primary" id="btn-inicio">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Tablero
            </a>
        </div>
    </div>
</header>

<!-- CONTENEDOR PRINCIPAL -->
<main class="page-container" style="margin-top: 32px;">

    <!-- HERO HEADER DEL MÓDULO -->
    <section class="help-hero-banner" style="margin-bottom: 24px;">
        <div class="help-hero-content">
            <div class="help-badge-topic">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Módulo Beneficiarios • Importación de Nuevas Altas
            </div>
            <h2>4. Importar y Subir Altas Validado</h2>
            <p>
                Cargue su planilla Excel (.xlsx). El sistema localizará automáticamente la tabla que comienza con <b>Apellido</b>, validará las normas reglamentarias Cabal y comprobará que ningún CUIT esté registrado en el padrón de <b>Beneficiarios</b> antes de permitir la carga a la base de datos.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <span class="badge-tag accent" style="padding: 8px 14px; font-size: 0.82rem;">
                Entidad 782 • Anexo 1 &bull; 9 &bull; 10
            </span>
        </div>
    </section>

    <!-- FORMULARIO PARA SUBIR EXCEL DIRECTAMENTE -->
    <section class="modern-card" style="margin-bottom: 24px; padding: 22px 26px;">
        <form action="CargaAltasDeExcelaBD.php" method="POST" enctype="multipart/form-data" id="form-upload" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <input type="hidden" name="accion" value="subir_analizar">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: var(--radius-sm); background: rgba(37,99,235,0.1); color: var(--primary-600); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                </div>
                <div>
                    <h4 style="font-size: 1rem; font-weight: 700; color: var(--navy-900); margin: 0 0 4px 0;">Subir Planilla de Altas (.xlsx)</h4>
                    <p style="font-size: 0.84rem; color: var(--text-muted); margin: 0;">Seleccione el archivo Excel para subirlo y analizarlo en pantalla con las normas Cabal.</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx" required style="font-size: 0.86rem; padding: 8px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); background: var(--surface-card); color: var(--text-main);">
                <button type="submit" class="btn-nav-action primary" id="btn-subir" style="padding: 10px 20px; font-size: 0.88rem; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path><polyline points="16 16 12 12 8 16"></polyline></svg>
                    <span id="btn-subir-texto">Subir y Analizar</span>
                </button>
            </div>
        </form>


    </section>

    <!-- ESTADO DEL PROCESO & RESULTADO -->
    <section class="status-hero-card">
        
        <?php if ($estado_proceso === 'guardado_exito'): ?>
            <div class="banner-outcome success">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div class="outcome-texts">
                    <h3>¡Datos Guardados Exitosamente en la Base de Datos!</h3>
                    <p><?php echo $mensaje_proceso; ?></p>
                </div>
            </div>
        <?php elseif ($estado_proceso === 'previsualizacion_ok'): ?>
            <div class="banner-outcome success">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="9 12 11 14 15 10"></polyline></svg>
                </div>
                <div class="outcome-texts">
                    <h3>¡Planilla Validada Correctamente! Apta para Carga en BD</h3>
                    <p><?php echo $mensaje_proceso; ?></p>
                </div>
            </div>
        <?php elseif ($estado_proceso === 'error_validacion'): ?>
            <div class="banner-outcome error">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                </div>
                <div class="outcome-texts">
                    <h3>Inconsistencias Detectadas — Guardado en BD Inhabilitado</h3>
                    <p><?php echo $mensaje_proceso; ?></p>
                </div>
            </div>
        <?php elseif ($estado_proceso === 'error_lectura'): ?>
            <div class="banner-outcome error">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                </div>
                <div class="outcome-texts">
                    <h3>Error al Cargar / Leer el Archivo Excel</h3>
                    <p><?php echo htmlspecialchars($mensaje_proceso); ?></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Estado Inicial de Bienvenida -->
            <div class="banner-outcome info">
                <div class="outcome-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                </div>
                <div class="outcome-texts">
                    <h3>Esperando Planilla para Analizar</h3>
                    <p>Seleccione un archivo <b>.xlsx</b> y presione <b>"Subir y Analizar"</b> para iniciar la verificación automática.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- TARJETAS MÉTRICAS DE RESUMEN -->
        <div class="summary-stats-grid">
            <div class="summary-stat-box">
                <div class="stat-box-label">Total Filas Leídas</div>
                <div class="stat-box-value"><?php echo number_format($total_filas, 0, ',', '.'); ?></div>
                <div class="stat-box-desc">Registros procesados de la tabla</div>
            </div>

            <div class="summary-stat-box">
                <div class="stat-box-label">Registros Aprobados</div>
                <div class="stat-box-value" style="color: <?php echo ($total_validos === $total_filas && $total_filas > 0) ? 'var(--emerald-600)' : ($total_filas > 0 ? '#e11d48' : 'var(--text-main)'); ?>;">
                    <?php echo number_format($total_validos, 0, ',', '.'); ?>
                </div>
                <div class="stat-box-desc"><?php echo ($total_filas > 0 && count($errores) === 0) ? '100% registros conformes' : (count($errores) > 0 ? count($errores) . ' observaciones a corregir' : 'Sin procesar'); ?></div>
            </div>

            <div class="summary-stat-box">
                <div class="stat-box-label">CUITs en Beneficiarios</div>
                <div class="stat-box-value" style="color: <?php echo $total_cuits_en_beneficiario === 0 ? 'var(--emerald-600)' : '#dc2626'; ?>;">
                    <?php echo number_format($total_cuits_en_beneficiario, 0, ',', '.'); ?>
                </div>
                <div class="stat-box-desc"><?php echo $total_cuits_en_beneficiario === 0 ? 'Sin coincidencias con padrón' : 'Bloquea guardado en BD'; ?></div>
            </div>

            <div class="summary-stat-box">
                <div class="stat-box-label">Estado de Base de Datos</div>
                <div class="stat-box-value" style="font-size: 1.15rem; color: <?php echo $estado_proceso === 'guardado_exito' ? 'var(--emerald-600)' : '#f59e0b'; ?>;">
                    <?php echo $estado_proceso === 'guardado_exito' ? 'Tabla altas Actualizada' : 'Tabla altas SIN MODIFICAR'; ?>
                </div>
                <div class="stat-box-desc"><?php echo $estado_proceso === 'guardado_exito' ? 'TRUNCATE y carga completados' : 'Esperando confirmación con botón'; ?></div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS Y BOTÓN DE CONFIRMACIÓN EN BD -->
        <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center; padding-top: 14px; border-top: 1px solid var(--border-light);">
            
            <?php if ($estado_proceso === 'guardado_exito'): ?>
                <a href="GenerarNovedadAltas.php" class="btn-nav-action primary" style="padding: 11px 22px; font-size: 0.92rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                    Paso Siguiente: Generar ALTAUSUCBPRE.782
                </a>
                <a href="index.php" class="btn-nav-action" style="padding: 11px 22px; font-size: 0.92rem;">
                    Volver al Tablero Principal
                </a>
            <?php else: ?>
                <!-- BOTÓN DE CONFIRMACIÓN CONDICIONAL -->
                <?php if ($puede_guardar): ?>
                    <form id="form-guardar-altas" method="POST" action="CargaAltasDeExcelaBD.php" style="display: inline-block;">
                        <input type="hidden" name="accion" value="confirmar_guardar">
                        <button type="button" class="btn-confirmar-guardar" id="btn-guardar-bd" onclick="abrirModalConfirmacion();">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            Guardar datos en BD (Vaciar altas y Guardar <?php echo count($registros); ?> registros)
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn-guardar-disabled" disabled title="Inhabilitado: Se requiere analizar una planilla sin inconsistencias ni CUITs existentes en Beneficiarios">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        Guardar datos en BD (Inhabilitado)
                    </button>
                <?php endif; ?>

                <a href="index.php" class="btn-nav-action" style="padding: 11px 20px; font-size: 0.92rem;">
                    Volver al Tablero
                </a>
            <?php endif; ?>

        </div>

    </section>

    <!-- DETALLE DE CUITS YA EXISTENTES EN BENEFICIARIOS -->
    <?php if ($total_cuits_en_beneficiario > 0): ?>
    <section class="errors-card" style="background: #fffbeb; border-color: #fde68a;">
        <div class="errors-card-header" style="color: #92400e;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Alerta Crítica: CUITs ya registrados en la Tabla Beneficiarios (<?php echo $total_cuits_en_beneficiario; ?> caso(s) bloqueante(s))
        </div>
        <p style="font-size: 0.84rem; color: #78350f; margin-bottom: 12px;">
            Los siguientes registros del archivo poseen un CUIT que ya está dado de alta en la tabla oficial de <code>beneficiario</code>. Por política del sistema, la opción <b>Guardar datos en BD</b> se encuentra estrictamente inhabilitada hasta resolver estas coincidencias:
        </p>
        <div style="overflow-x: auto;">
            <table class="table-errors" style="border: 1px solid #fde68a;">
                <thead>
                    <tr style="background: #fef3c7; color: #78350f;">
                        <th>Fila Excel</th>
                        <th>Titular en Planilla</th>
                        <th>DNI Planilla</th>
                        <th>CUIT Detectado</th>
                        <th>Beneficiario Existente en BD</th>
                        <th>DNI en BD</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuits_en_beneficiario as $cb): ?>
                    <tr>
                        <td style="font-weight: 700; font-family: 'JetBrains Mono', monospace; color: #92400e;">
                            #<?php echo $cb['fila']; ?>
                        </td>
                        <td style="font-weight: 600; color: #92400e;">
                            <?php echo htmlspecialchars($cb['titular_excel']); ?>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace;">
                            <?php echo htmlspecialchars($cb['dni_excel']); ?>
                        </td>
                        <td style="font-weight: 700; font-family: 'JetBrains Mono', monospace; color: #b45309;">
                            <?php echo htmlspecialchars($cb['cuit']); ?>
                        </td>
                        <td style="font-weight: 600; color: #b91c1c;">
                            <?php echo htmlspecialchars($cb['beneficiario_bd']); ?>
                        </td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #b91c1c;">
                            <?php echo htmlspecialchars($cb['dni_bd']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- TABLA DE ERRORES E INCONSISTENCIAS NORMATIVAS -->
    <?php if (count($errores) > 0): ?>
    <section class="errors-card">
        <div class="errors-card-header">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            Detalle de Inconsistencias con Normas de Validación Cabal (<?php echo count($errores); ?> observación/es)
        </div>
        <div style="overflow-x: auto;">
            <table class="table-errors">
                <thead>
                    <tr>
                        <th style="width: 80px;">Fila Excel</th>
                        <th>Titular Observado</th>
                        <th>DNI</th>
                        <th>CUIT</th>
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
                        <td style="font-family: 'JetBrains Mono', monospace;">
                            <?php echo htmlspecialchars($err['cuit']); ?>
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

    <!-- TABLA DE DATOS IMPORTADOS UTILIZANDO LA ESTRUCTURA DE LA TABLA ALTAS -->
    <?php if ($total_filas > 0): ?>
    <section class="preview-section-card">
        <header class="preview-section-header">
            <div>
                <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--navy-900);">
                    Estructura de la Tabla Altas — Datos a Importar
                </h3>
                <p style="font-size: 0.84rem; color: var(--text-muted); margin-top: 2px;">
                    Mostrando <strong id="contador-visibles"><?php echo $total_filas; ?></strong> registros procesados con correspondencia de campos de la tabla <code>altas</code>.
                </p>
            </div>

            <!-- BUSCADOR EN VIVO DE LA TABLA -->
            <div class="search-control-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="buscar-tabla" placeholder="Filtrar por titular, DNI, CUIT, calle, localidad...">
            </div>
        </header>

        <div class="table-scroll-container">
            <table class="altas-data-table" id="tabla-altas">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estado / Padrón</th>
                        <th>Apellido</th>
                        <th>Nombre</th>
                        <th>DomicilioCalle</th>
                        <th>DomicilioNumero</th>
                        <th>DomicilioPiso</th>
                        <th>DomicilioDepartamento</th>
                        <th>Localidad</th>
                        <th>Provincia</th>
                        <th>Sexo</th>
                        <th>CodigoPostal</th>
                        <th>TelefonoCaracteristica</th>
                        <th>TelefonoNumero</th>
                        <th>EstadoCivil</th>
                        <th>FechaNacimiento</th>
                        <th>DocumentoTipo</th>
                        <th>DocumentoNumero</th>
                        <th>DocumentoProvincia</th>
                        <th>CUIT</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla">
                    <?php foreach ($registros as $idx => $r): ?>
                    <tr class="<?php echo $r['valido'] ? '' : 'row-invalid'; ?>">
                        <td style="font-weight: 700; font-family: 'JetBrains Mono', monospace;">
                            <?php echo $r['fila']; ?>
                        </td>
                        <td>
                            <?php if ($r['cuit_en_padron']): ?>
                                <span class="badge-status-val cuit-dup" title="CUIT ya registrado en Beneficiario: <?php echo htmlspecialchars($r['datos_beneficiario']['Apellido'] . ' ' . $r['datos_beneficiario']['Nombre']); ?>">
                                    ⚠ Ya en Padrón
                                </span>
                            <?php elseif ($r['valido']): ?>
                                <span class="badge-status-val ok">✓ Válido</span>
                            <?php else: ?>
                                <span class="badge-status-val err" title="<?php echo htmlspecialchars(implode(' — ', $r['errores'])); ?>">
                                    ✕ Inconsistente
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($r['Apellido']); ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($r['Nombre']); ?></td>
                        <td><?php echo htmlspecialchars($r['DomicilioCalle']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['DomicilioNumero']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['DomicilioPiso']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['DomicilioDepartamento']); ?></td>
                        <td><?php echo htmlspecialchars($r['Localidad']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700;"><?php echo htmlspecialchars($r['Provincia']); ?></td>
                        <td style="text-align: center; font-weight: 700;"><?php echo htmlspecialchars($r['Sexo']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['CodigoPostal']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['TelefonoCaracteristica']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['TelefonoNumero']); ?></td>
                        <td style="text-align: center; font-weight: 700;"><?php echo htmlspecialchars($r['EstadoCivil']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['FechaNacimiento']); ?></td>
                        <td style="text-align: center; font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['DocumentoTipo']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700;"><?php echo htmlspecialchars($r['DocumentoNumero']); ?></td>
                        <td style="text-align: center; font-family: 'JetBrains Mono', monospace;"><?php echo htmlspecialchars($r['DocumentoProvincia']); ?></td>
                        <td style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: <?php echo $r['cuit_en_padron'] ? '#dc2626' : 'var(--primary-600)'; ?>;">
                            <?php echo htmlspecialchars($r['CUIT']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php if ($puede_guardar): ?>
<!-- VENTANA EMERGENTE MODAL DE CONFIRMACIÓN (COLORES OFICIALES CABAL FSR) -->
<div id="modalConfirmarGuardar" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
    <div class="modal-container">
        <!-- CABECERA DE LA VENTANA EMERGENTE -->
        <div class="modal-header">
            <div class="modal-header-info">
                <div class="modal-header-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                </div>
                <div class="modal-header-text">
                    <h3 id="modal-titulo">Confirmar Grabación en Base de Datos</h3>
                    <p>Módulo 1.4 — Importar y Subir Altas Validado</p>
                </div>
            </div>
            <button type="button" class="modal-btn-close" onclick="cerrarModalConfirmacion()" aria-label="Cerrar ventana">
                ✕
            </button>
        </div>

        <!-- CUERPO DE LA VENTANA EMERGENTE -->
        <div class="modal-body">
            <div class="modal-alert-box">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div style="font-size: 0.88rem; line-height: 1.45;">
                    ¿Confirma que desea <strong>vaciar la tabla altas (TRUNCATE)</strong> y cargar los <strong><?php echo count($registros); ?> registros validados</strong>?
                </div>
            </div>

            <div class="modal-detail-list">
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Registros a Ingresar:</span>
                    <span class="modal-detail-value" style="color: #059669;">
                        <?php echo count($registros); ?> beneficiarios validados
                    </span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Tabla de Destino:</span>
                    <span class="modal-detail-value">
                        <code>altas</code> (Base de Datos Cabal)
                    </span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Acción Operativa:</span>
                    <span class="modal-detail-value" style="color: #b91c1c;">
                        TRUNCATE (Vaciado atómico previo)
                    </span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Auditoría Previa:</span>
                    <span class="modal-detail-value" style="color: #059669;">
                        ✓ 0 errores • 0 CUITs duplicados
                    </span>
                </div>
            </div>

            <p style="font-size: 0.80rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                ℹ️ <em>Los registros ingresados quedarán listos para la emisión del archivo batch <code>ALTAUSUCBPRE.782</code> (Entidad 782).</em>
            </p>
        </div>

        <!-- PIE DE LA VENTANA EMERGENTE -->
        <div class="modal-footer">
            <button type="button" class="modal-btn-cancel" onclick="cerrarModalConfirmacion()">
                Cancelar
            </button>
            <button type="button" class="modal-btn-confirm" id="btn-modal-confirmar" onclick="confirmarGuardadoBD()">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Sí, Vaciar y Guardar en BD
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- PIE DE PÁGINA INSTITUCIONAL -->
<footer class="app-footer">
    <div class="footer-inner">
        <div>
            <strong>CABAL FSR • Sistema de Gestión de Tarjetas Sociales</strong> — Provincia de Río Negro
        </div>
        <div>
            Módulo de Validación de Altas • Entidad 782 • Normas Cabal
        </div>
    </div>
</footer>

<script>
// Funciones de la Ventana Emergente Modal de Confirmación
function abrirModalConfirmacion() {
    var modal = document.getElementById('modalConfirmarGuardar');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(function() {
            modal.classList.add('active');
        }, 10);
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalConfirmacion() {
    var modal = document.getElementById('modalConfirmarGuardar');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 220);
    }
}

function confirmarGuardadoBD() {
    var btnConf = document.getElementById('btn-modal-confirmar');
    var formGuardar = document.getElementById('form-guardar-altas');
    if (btnConf) {
        btnConf.disabled = true;
        btnConf.style.opacity = '0.75';
        btnConf.style.cursor = 'wait';
        btnConf.innerHTML = '<span class="spinner"></span> Guardando registros en BD...';
    }
    if (formGuardar) {
        formGuardar.submit();
    }
}

window.addEventListener('click', function(e) {
    var modal = document.getElementById('modalConfirmarGuardar');
    if (e.target === modal) {
        cerrarModalConfirmacion();
    }
});

window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        cerrarModalConfirmacion();
    }
});

// Indicador de carga al subir y analizar
document.addEventListener('DOMContentLoaded', function() {
    var formUpload = document.getElementById('form-upload');
    var btnSubir = document.getElementById('btn-subir');
    var btnSubirTexto = document.getElementById('btn-subir-texto');
    var fileInput = document.getElementById('excel_file');

    if (formUpload && btnSubir && fileInput) {
        formUpload.addEventListener('submit', function(e) {
            if (fileInput.files.length > 0) {
                btnSubir.disabled = true;
                btnSubir.style.opacity = '0.75';
                btnSubir.style.cursor = 'wait';
                btnSubirTexto.innerHTML = '<span class="spinner"></span> Procesando planilla...';
                // Permitir submit natural
            }
        });
    }

    // Buscador en vivo de la tabla de altas
    var buscador = document.getElementById('buscar-tabla');
    var tabla = document.getElementById('tabla-altas');
    var contador = document.getElementById('contador-visibles');
    
    if (buscador && tabla) {
        buscador.addEventListener('input', function() {
            var termino = this.value.toLowerCase().trim();
            var filas = tabla.querySelectorAll('tbody tr');
            var visibles = 0;
            
            filas.forEach(function(fila) {
                var texto = fila.textContent.toLowerCase();
                if (texto.indexOf(termino) !== -1) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });
            
            if (contador) {
                contador.textContent = visibles;
            }
        });
    }
});
</script>

</body>
</html>
