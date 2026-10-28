<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * 1.3 Procesamiento de Respuesta de Altas Cabal (MovAltas.txt / 782.txt)
 * Carga mediante Drag & Drop, auditoría previa y vuelco seguro a tabla beneficiario
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
$nombreArchivoMostrado = '';
$tamanoArchivo = 0;
$lineasRaw = array();
$beneficiariosParseados = array();
$stats = array(
    'total_titulares' => 0,
    'aceptados' => 0,
    'rechazados' => 0,
    'aptos_guardar' => 0,
    'duplicados' => 0,
    'guardados' => 0,
    'motivos_rechazo' => array()
);

// Carpeta de almacenamiento para archivos adjuntados
$targetDir = __DIR__ . "/archivos/";
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0777, true);
}

// Variable de acción recibida
$accion = isset($_POST['accion']) ? trim($_POST['accion']) : '';

// MANEJO EXCLUSIVO DEL ARCHIVO ADJUNTADO / SUBIDO POR EL OPERADOR
if ($accion === 'subir_archivo') {
    if (!isset($_FILES['archivo_altas']) || $_FILES['archivo_altas']['error'] === UPLOAD_ERR_NO_FILE) {
        $mensajeFeedback = "No se detectó ningún archivo seleccionado. Por favor, arrastre o elija el archivo devuelto por Cabal.";
        $tipoFeedback = "danger";
    } elseif ($_FILES['archivo_altas']['error'] !== UPLOAD_ERR_OK) {
        $codErr = $_FILES['archivo_altas']['error'];
        $mensajeFeedback = "Ocurrió un error al recibir el archivo desde el navegador (Código PHP: " . $codErr . "). Verifique que el archivo no supere el tamaño permitido.";
        $tipoFeedback = "danger";
    } else {
        $fileTmp  = $_FILES['archivo_altas']['tmp_name'];
        $fileName = basename($_FILES['archivo_altas']['name']);
        $fileSize = $_FILES['archivo_altas']['size'];

        if ($fileSize <= 0) {
            $mensajeFeedback = "El archivo adjuntado está vacío (0 bytes).";
            $tipoFeedback = "danger";
        } elseif ($fileSize > 25 * 1024 * 1024) {
            $mensajeFeedback = "El archivo excede el tamaño máximo permitido (25 MB).";
            $tipoFeedback = "danger";
        } else {
            $archivoDestino = $targetDir . "RespuestaAltas_adjuntado.txt";
            if (move_uploaded_file($fileTmp, $archivoDestino)) {
                // Leemos única y exclusivamente las líneas del archivo recién adjuntado
                $lineasRaw = file($archivoDestino, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $nombreArchivoMostrado = $fileName;
                $tamanoArchivo = $fileSize;
                $archivoProcesado = true;
                $mensajeFeedback = "Archivo adjunto <strong>" . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . "</strong> (" . number_format($fileSize / 1024, 1, ',', '.') . " KB) recibido y procesado exitosamente.";
                $tipoFeedback = "success";
            } else {
                $mensajeFeedback = "No se pudo guardar el archivo adjuntado en el servidor. Verifique los permisos de escritura en la carpeta <code>archivos/</code>.";
                $tipoFeedback = "danger";
            }
        }
    }
}

// 4. PARSING DE REGISTROS (CABAL TIPO 01, 02, 03)
if ($archivoProcesado && !empty($lineasRaw)) {
    $indice = 0;
    $R1 = array();
    $R2 = array();

    $stmtVerificar = $conn->prepare("SELECT id, Apellido, Nombre, NumeroCuenta, NumeroTarjeta FROM beneficiario WHERE NumeroDocumento = :dni OR (Cuit <> '' AND Cuit = :cuit) LIMIT 1");

    foreach ($lineasRaw as $linea) {
        $linea = rtrim($linea, "\r\n");
        if (strlen($linea) < 22) continue;

        // Detección de código de operación (posición 20 o 22)
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
        if ($op === "01" || $op === "1") {
            $R1 = array();
            $R1['tarjeta'] = trim(substr($linea, 4, 16));
            $R1['apynom']   = trim(substr($linea, 28, 25));
            $R1['calle']    = trim(substr($linea, 53, 25));
            $R1['numero']   = trim(substr($linea, 78, 5));
            $R1['piso']     = trim(substr($linea, 83, 2));
            $R1['depto']    = trim(substr($linea, 85, 2));
            $R1['localidad']= trim(substr($linea, 87, 30));
            $R1['provincia']= trim(substr($linea, 117, 2));
            $R1['pais']     = trim(substr($linea, 121, 2));
            $R1['sexo']     = trim(substr($linea, 122, 1));
            $R1['formulario']= trim(substr($linea, 123, 5));
            $R1['ingreso']  = trim(substr($linea, 128, 1));
            $R1['rechazo']  = (strlen($linea) >= 165) ? trim(substr($linea, 129, 36)) : '';
            $R1['entidad']  = (strlen($linea) >= 174) ? trim(substr($linea, 168, 6)) : '047820';
            $R1['fecha_proceso'] = (strlen($linea) >= 180) ? trim(substr($linea, 174, 6)) : '';
            $R1['cuenta']   = (strlen($linea) >= 196) ? trim(substr($linea, 180, 16)) : (strlen($linea) >= 180 ? trim(substr($linea, 0, 16)) : '');
        }

        // REGISTRO 02: TELÉFONO, NACIMIENTO, DOCUMENTO Y CUIT
        if ($op === "02" || $op === "2") {
            $R2 = array();
            $R2['cp']       = trim(substr($linea, 22, 8));
            $R2['tel_carac']= trim(substr($linea, 30, 5));
            $R2['tel_num']  = trim(substr($linea, 35, 8));
            $R2['est_civil']= trim(substr($linea, 43, 1));
            $R2['fec_nac']  = trim(substr($linea, 44, 8));
            $R2['tipo_doc'] = trim(substr($linea, 52, 2));
            $R2['dni']      = trim(substr($linea, 54, 11));
            $R2['pcia_doc'] = trim(substr($linea, 65, 2));
            $R2['cuit']     = trim(substr($linea, 67, 11));
            $R2['ingreso']  = (strlen($linea) >= 129) ? trim(substr($linea, 128, 1)) : '';
            $R2['rechazo']  = (strlen($linea) >= 165) ? trim(substr($linea, 129, 36)) : '';
            if (empty($R1['cuenta']) && strlen($linea) >= 196) {
                $R1['cuenta'] = trim(substr($linea, 180, 16));
            }
        }

        // REGISTRO 03: CONSOLIDACIÓN DEL TITULAR
        if ($op === "03" || $op === "3") {
            $indice++;

            // Separar Apellido y Nombre
            $apynom = isset($R1['apynom']) ? $R1['apynom'] : '';
            $posBarra = strpos($apynom, '/');
            if ($posBarra !== false) {
                $apellido = trim(substr($apynom, 0, $posBarra));
                $nombre = trim(substr($apynom, $posBarra + 1));
            } else {
                $apellido = $apynom;
                $nombre = '';
            }

            $dniNorm = isset($R2['dni']) ? ltrim(trim($R2['dni']), '0') : '';
            $cuitNorm = isset($R2['cuit']) ? preg_replace('/[^0-9]/', '', $R2['cuit']) : '';
            $cuentaAsignada = !empty($R1['cuenta']) ? $R1['cuenta'] : (isset($R1['tarjeta']) ? $R1['tarjeta'] : '');
            $tarjetaAsignada = isset($R1['tarjeta']) ? $R1['tarjeta'] : '';
            // Código de ingreso y rechazo
            $codIngreso = isset($R2['ingreso']) && $R2['ingreso'] !== '' ? $R2['ingreso'] : (isset($R1['ingreso']) ? $R1['ingreso'] : '0');
            $codRechazo = isset($R2['rechazo']) && $R2['rechazo'] !== '' ? $R2['rechazo'] : (isset($R1['rechazo']) ? $R1['rechazo'] : '');

            $ingresoLimpio = trim($codIngreso);
            $rechazoLimpio = trim($codRechazo);
            $tieneErrorRechazo = (!empty($rechazoLimpio) && preg_replace('/[0\s]/', '', $rechazoLimpio) !== '');

            // Interpretar códigos de error según Anexo 12 (docs/Cabal.md)
            $erroresDetalle = parsearCodigosRechazoAltas($codRechazo);

            // En Cabal: 0 = Aceptado, 1 = Rechazado
            // Si el ingreso es '1' o tiene código de motivo de rechazo, está RECHAZADO por Cabal
            if ($ingresoLimpio === '1' || $tieneErrorRechazo) {
                $esAceptado = false;
                if (empty($erroresDetalle) && $ingresoLimpio === '1') {
                    $erroresDetalle[] = array(
                        'codigo' => 'REC',
                        'titulo' => 'Rechazada por Cabal',
                        'desc'   => 'Alta rechazada por control general de ingreso en el host de Cabal (Código de ingreso 1).'
                    );
                }
            } elseif ($ingresoLimpio === '0') {
                $esAceptado = true;
            } else {
                $esAceptado = !$tieneErrorRechazo;
            }

            // Registrar en el acumulador de motivos de rechazo
            if (!$esAceptado && !empty($erroresDetalle)) {
                foreach ($erroresDetalle as $errItem) {
                    $cErr = $errItem['codigo'];
                    if (!isset($stats['motivos_rechazo'][$cErr])) {
                        $stats['motivos_rechazo'][$cErr] = array(
                            'codigo' => $cErr,
                            'titulo' => $errItem['titulo'],
                            'desc'   => $errItem['desc'],
                            'cantidad' => 0
                        );
                    }
                    $stats['motivos_rechazo'][$cErr]['cantidad']++;
                }
            }

            // Verificar si ya existe en padrón
            $yaEnBD = false;
            $idExistente = null;
            if (!empty($dniNorm) || !empty($cuitNorm)) {
                $stmtVerificar->execute(array(':dni' => $dniNorm, ':cuit' => $cuitNorm));
                $rowBD = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                if ($rowBD) {
                    $yaEnBD = true;
                    $idExistente = $rowBD['id'];
                }
            }

            // REGLA CRÍTICA DE NEGOCIO:
            // Solo es APTO PARA GUARDAR si es ACEPTADO por Cabal y NO está duplicado en BD
            $aptoParaGuardar = ($esAceptado && !$yaEnBD);

            // Estadísticas
            $stats['total_titulares']++;
            if ($esAceptado) {
                $stats['aceptados']++;
            } else {
                $stats['rechazados']++;
            }

            if ($yaEnBD) {
                $stats['duplicados']++;
            }

            if ($aptoParaGuardar) {
                $stats['aptos_guardar']++;
            }

            $beneficiariosParseados[] = array(
                'index' => $indice,
                'apellido' => $apellido,
                'nombre' => $nombre,
                'calle' => isset($R1['calle']) ? $R1['calle'] : '',
                'numero' => isset($R1['numero']) ? $R1['numero'] : '',
                'piso' => isset($R1['piso']) ? $R1['piso'] : '',
                'depto' => isset($R1['depto']) ? $R1['depto'] : '',
                'localidad' => isset($R1['localidad']) ? $R1['localidad'] : '',
                'provincia' => isset($R1['provincia']) ? $R1['provincia'] : '',
                'sexo' => isset($R1['sexo']) ? $R1['sexo'] : '',
                'entidad' => isset($R1['entidad']) ? $R1['entidad'] : '047820',
                'fecha_proceso' => isset($R1['fecha_proceso']) ? $R1['fecha_proceso'] : date('dmy'),
                'cuenta' => $cuentaAsignada,
                'tarjeta' => $tarjetaAsignada,
                'cp' => isset($R2['cp']) ? $R2['cp'] : '',
                'tel_carac' => isset($R2['tel_carac']) ? $R2['tel_carac'] : '',
                'tel_num' => isset($R2['tel_num']) ? $R2['tel_num'] : '',
                'est_civil' => isset($R2['est_civil']) ? $R2['est_civil'] : '',
                'fec_nac' => isset($R2['fec_nac']) ? $R2['fec_nac'] : '',
                'tipo_doc' => isset($R2['tipo_doc']) ? $R2['tipo_doc'] : '01',
                'dni' => $dniNorm,
                'pcia_doc' => isset($R2['pcia_doc']) ? $R2['pcia_doc'] : '',
                'cuit' => $cuitNorm,
                'ingreso' => $codIngreso,
                'rechazo' => $codRechazo,
                'errores_detalle' => $erroresDetalle,
                'es_aceptado' => $esAceptado,
                'ya_en_bd' => $yaEnBD,
                'apto_para_guardar' => $aptoParaGuardar,
                'id_bd' => $idExistente
            );

            $R1 = array();
            $R2 = array();
        }
    }
}

// 5. GUARDADO EN BASE DE DATOS TRAS CONFIRMACIÓN (SOLO APTOS ACEPTADOS POR CABAL)
if ($accion === 'guardar_beneficiarios' && isset($_POST['datos_json'])) {
    $datosGuardar = json_decode($_POST['datos_json'], true);
    if (!empty($datosGuardar) && is_array($datosGuardar)) {
        try {
            $conn->beginTransaction();

            $sqlInsert = "INSERT INTO beneficiario (
                Apellido, Nombre, Calle, Numero, Piso, Departamento, Localidad, Provincia,
                Sexo, EntidadEmisora, FechaProceso, NumeroCuenta, NumeroTarjeta,
                CodigoPostal, TelefonoCaracteristica, TelefonoNumero, EstadoCivil,
                FechaNacimiento, TipoDocumento, NumeroDocumento, ProvinciaDocumento,
                Cuit, CodigoIngreso, CodigoRechazo
            ) VALUES (
                :Apellido, :Nombre, :Calle, :Numero, :Piso, :Departamento, :Localidad, :Provincia,
                :Sexo, :EntidadEmisora, :FechaProceso, :NumeroCuenta, :NumeroTarjeta,
                :CodigoPostal, :TelefonoCaracteristica, :TelefonoNumero, :EstadoCivil,
                :FechaNacimiento, :TipoDocumento, :NumeroDocumento, :ProvinciaDocumento,
                :Cuit, :CodigoIngreso, :CodigoRechazo
            )";

            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtCheck = $conn->prepare("SELECT id FROM beneficiario WHERE NumeroDocumento = :dni OR (Cuit <> '' AND Cuit = :cuit) LIMIT 1");

            $guardadosCount = 0;
            $duplicadosOmitidos = 0;
            $rechazadosOmitidos = 0;

            foreach ($datosGuardar as $b) {
                // RESTRICCIÓN DE NEGOCIO ESTRICTA:
                // Si fue rechazado por Cabal, NUNCA se guarda en beneficiario
                if (empty($b['es_aceptado']) || $b['es_aceptado'] === false || $b['es_aceptado'] === '0' || $b['es_aceptado'] === 0) {
                    $rechazadosOmitidos++;
                    continue;
                }

                // Doble chequeo anti-duplicado en la misma transacción
                $stmtCheck->execute(array(':dni' => $b['dni'], ':cuit' => $b['cuit']));
                if ($stmtCheck->fetchColumn()) {
                    $duplicadosOmitidos++;
                    continue;
                }

                $stmtInsert->execute(array(
                    ':Apellido' => $b['apellido'],
                    ':Nombre' => $b['nombre'],
                    ':Calle' => $b['calle'],
                    ':Numero' => $b['numero'],
                    ':Piso' => $b['piso'],
                    ':Departamento' => $b['depto'],
                    ':Localidad' => $b['localidad'],
                    ':Provincia' => $b['provincia'],
                    ':Sexo' => $b['sexo'],
                    ':EntidadEmisora' => !empty($b['entidad']) ? $b['entidad'] : '047820',
                    ':FechaProceso' => !empty($b['fecha_proceso']) ? $b['fecha_proceso'] : date('dmy'),
                    ':NumeroCuenta' => $b['cuenta'],
                    ':NumeroTarjeta' => $b['tarjeta'],
                    ':CodigoPostal' => $b['cp'],
                    ':TelefonoCaracteristica' => $b['tel_carac'],
                    ':TelefonoNumero' => $b['tel_num'],
                    ':EstadoCivil' => $b['est_civil'],
                    ':FechaNacimiento' => $b['fec_nac'],
                    ':TipoDocumento' => !empty($b['tipo_doc']) ? $b['tipo_doc'] : '01',
                    ':NumeroDocumento' => $b['dni'],
                    ':ProvinciaDocumento' => $b['pcia_doc'],
                    ':Cuit' => $b['cuit'],
                    ':CodigoIngreso' => $b['ingreso'],
                    ':CodigoRechazo' => $b['rechazo']
                ));
                $guardadosCount++;
            }

            $conn->commit();

            $detallesOmitidos = array();
            if ($rechazadosOmitidos > 0) {
                $detallesOmitidos[] = "<strong>" . $rechazadosOmitidos . " rechazados por Cabal</strong>";
            }
            if ($duplicadosOmitidos > 0) {
                $detallesOmitidos[] = "<strong>" . $duplicadosOmitidos . " duplicados existentes</strong>";
            }

            $mensajeFeedback = "¡Operación completada con éxito! Se incorporaron <strong>" . $guardadosCount . " nuevos titulares aceptados</strong> al padrón de beneficiarios." . (!empty($detallesOmitidos) ? " (Se omitieron " . implode(' y ', $detallesOmitidos) . ")." : "");
            $tipoFeedback = "success";
            $stats['guardados'] = $guardadosCount;
            
            // Recargar vista para actualizar estados
            $beneficiariosParseados = array();
            $archivoProcesado = false;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $mensajeFeedback = "Error al intentar guardar los beneficiarios en la base de datos: " . htmlspecialchars($e->getMessage());
            $tipoFeedback = "danger";
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
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
            margin: 0 auto 50px;
            padding: 0 24px;
        }

        .dropzone-altas {
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
        .dropzone-altas:hover,
        .dropzone-altas.dragover {
            border-color: var(--primary-600);
            background: rgba(37, 99, 235, 0.04);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .dropzone-altas-icon {
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
        .dropzone-altas:hover .dropzone-altas-icon {
            transform: scale(1.08);
            background: var(--primary-600);
            color: #ffffff;
        }

        .stats-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .kpi-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 18px 20px;
            box-shadow: var(--shadow-sm);
        }
        .kpi-label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .kpi-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .kpi-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-status.success {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-status.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .badge-status.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .badge-status.info {
            background: rgba(37, 99, 235, 0.15);
            color: #2563eb;
            border: 1px solid rgba(37, 99, 235, 0.3);
        }

        .filter-btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 6px 14px;
            border-radius: var(--radius-full);
            border: 1px solid var(--border-light);
            background: var(--surface-card);
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .filter-btn:hover {
            border-color: var(--primary-400);
            color: var(--primary-600);
        }
        .filter-btn.active {
            background: var(--primary-600);
            color: #ffffff;
            border-color: var(--primary-600);
        }

        /* Modal de confirmación */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            max-width: 540px;
            width: 100%;
            padding: 30px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            text-align: center;
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

            <a href="CargaAltasDeExcelaBD.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                Importar Altas
            </a>

            <a href="GenerarNovedadAltas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                Generar ALTAUSUCBPRE
            </a>

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Ver Padrón
            </a>

            <button type="button" class="theme-toggle-btn" id="btn-theme-toggle" title="Alternar tema">
                🌓
            </button>
        </div>
    </div>
</header>

<main class="page-container" style="margin-top: 28px;">

    <!-- CABECERA DE LA PÁGINA -->
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--navy-800); margin: 0 0 6px 0; display: flex; align-items: center; gap: 10px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <polyline points="17 11 19 13 23 9"></polyline>
            </svg>
            1.3 Procesamiento de Respuesta de Altas Cabal (MovAltas.txt)
        </h2>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">
            Importación y vinculación en el padrón de beneficiarios de las cuentas y tarjetas emitidas por la plataforma bancaria Cabal, con auditoría de códigos de rechazo según <strong>Anexo 12 (docs/Cabal.md)</strong>.
        </p>
    </div>

    <!-- PANEL EXPANDIBLE CON EL DICCIONARIO OFICIAL DE ERRORES DE ALTAS (ANEXO 12) -->
    <div id="panelCatalogoErrores" class="modern-card" style="display: none; margin-bottom: 24px; border: 2px solid var(--primary-600); background: var(--surface-card);">
        <div class="modern-card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); background: rgba(37, 99, 235, 0.05); display: flex; justify-content: space-between; align-items: center;">
            <div class="modern-card-title-group">
                <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--navy-800);">
                    📖 Catálogo Oficial de Errores de Altas y Modificaciones — Anexo 12 (docs/Cabal.md)
                </h4>
                <p style="margin: 4px 0 0 0; font-size: 0.8rem; color: var(--text-muted);">
                    Códigos de rechazo emitidos por el Host Central de Cabal en la posición 130 a 165 del archivo de respuesta.
                </p>
            </div>
            <button type="button" onclick="toggleCatalogoErrores()" style="background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted); line-height: 1;">&times;</button>
        </div>
        <div style="padding: 16px 20px; max-height: 380px; overflow-y: auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 10px;">
                <?php foreach (obtenerDiccionarioErroresAltasCabal() as $cErr => $itemErr): ?>
                    <div style="padding: 8px 12px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); background: var(--surface-card);">
                        <div style="display: flex; gap: 8px; align-items: baseline; margin-bottom: 2px;">
                            <span style="font-weight: 800; color: #dc2626; font-size: 0.82rem; font-family: monospace;">
                                [<?php echo $itemErr['codigo']; ?>]
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

    <?php if (!empty($mensajeFeedback)): ?>
        <div class="alert-modern <?php echo $tipoFeedback; ?>" style="margin-bottom: 24px;">
            <?php echo $mensajeFeedback; ?>
        </div>
    <?php endif; ?>

    <?php if (!$archivoProcesado): ?>
        <!-- SECCIÓN DE SUBIDA Y ARRASTRE DE ARCHIVO (DROPZONE) -->
        <div class="modern-card" style="margin-bottom: 28px;">
            <div class="modern-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
                <div class="modern-card-title-group">
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        📁 Adjuntar o Arrastrar Archivo de Respuesta de Altas
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                        Seleccione o arrastre el archivo devuelto por el banco para procesar las cuentas otorgadas y validar rechazos.
                    </p>
                </div>
            </div>

            <div style="padding: 24px;">
                <form action="ProcesaNovedadesAltas.php" method="POST" enctype="multipart/form-data" id="formUploadAltas">
                    <input type="hidden" name="accion" value="subir_archivo">
                    <!-- Input de archivo nativo fuera de contenedores dinámicos -->
                    <input type="file" name="archivo_altas" id="fileInputAltas" accept=".txt,.782,.dat,text/*" style="display:none;">

                    <label for="fileInputAltas" class="dropzone-altas" id="dropzoneAltas" style="display: block; cursor: pointer; margin-bottom: 0;">
                        <div id="dropzoneNormalState">
                            <div class="dropzone-altas-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <div style="font-size: 1.15rem; font-weight: 800; color: var(--navy-800); margin-bottom: 6px;">
                                Haga clic aquí o arrastre el archivo devuelto por Cabal para procesar
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 14px;">
                                Formatos soportados: <strong>.TXT</strong> (Respuesta de Altas), <strong>.782</strong>, <strong>.DAT</strong> o devueltos por Cabal
                            </div>
                            <span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 22px; border-radius: var(--radius-full); background: rgba(37, 99, 235, 0.08); color: var(--primary-600); font-size: 0.85rem; font-weight: 700;">
                                📂 Examinar archivo de mi computadora
                            </span>
                        </div>

                        <div id="dropzoneLoadingState" style="display: none; padding: 18px 0;">
                            <div class="dropzone-altas-icon" style="background: var(--primary-600); color: #fff; margin-bottom: 12px;">
                                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                            </div>
                            <div id="loadingFileName" style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin-bottom: 6px;">
                                Subiendo y procesando archivo...
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                Auditoría posicional de registros Cabal en curso. Por favor, aguarde...
                            </div>
                        </div>
                    </label>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- BANNER DE ARCHIVO ADJUNTO PROCESADO -->
        <div class="modern-card" style="padding: 18px 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; background: rgba(37, 99, 235, 0.05); border: 1.5px solid rgba(37, 99, 235, 0.25);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--primary-600); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    📄
                </div>
                <div>
                    <div style="font-size: 1rem; font-weight: 800; color: var(--navy-800);">
                        Archivo adjunto procesado: <code><?php echo htmlspecialchars($nombreArchivoMostrado, ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Tamaño: <strong><?php echo number_format($tamanoArchivo / 1024, 1, ',', '.'); ?> KB</strong> (<?php echo number_format($tamanoArchivo, 0, ',', '.'); ?> bytes) &bull; Procesado en este momento
                    </div>
                </div>
            </div>
            <a href="ProcesaNovedadesAltas.php" class="btn-modern-secondary" style="padding: 10px 18px; font-size: 0.88rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                Adjuntar Otro Archivo
            </a>
        </div>
    <?php endif; ?>

    <!-- RESULTADOS Y AUDITORÍA DE REGISTROS -->
    <?php if ($archivoProcesado && !empty($beneficiariosParseados)): ?>

        <!-- TARJETAS DE MÉTRICAS KPI -->
        <div class="stats-kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Titulares en Lote</div>
                <div class="kpi-value" style="color: var(--navy-800);"><?php echo number_format($stats['total_titulares'], 0, ',', '.'); ?></div>
                <div class="kpi-desc">Total procesados en el archivo</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Aceptados por Cabal</div>
                <div class="kpi-value" style="color: #059669;"><?php echo number_format($stats['aceptados'], 0, ',', '.'); ?></div>
                <div class="kpi-desc">Cuentas y plásticos aprobados</div>
            </div>

            <div class="kpi-card" style="border-left: 4px solid #dc2626;">
                <div class="kpi-label">Rechazados por Cabal</div>
                <div class="kpi-value" style="color: #dc2626;"><?php echo number_format($stats['rechazados'], 0, ',', '.'); ?></div>
                <div class="kpi-desc" style="color: #dc2626; font-weight: 600;">⛔ NO se suben a beneficiarios</div>
            </div>

            <div class="kpi-card" style="border-left: 4px solid var(--primary-600);">
                <div class="kpi-label">Aptos para Incorporar</div>
                <div class="kpi-value" style="color: var(--primary-600);"><?php echo number_format($stats['aptos_guardar'], 0, ',', '.'); ?></div>
                <div class="kpi-desc">Aceptados y no en BD</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-label">Ya Registrados en BD</div>
                <div class="kpi-value" style="color: #d97706;"><?php echo number_format($stats['duplicados'], 0, ',', '.'); ?></div>
                <div class="kpi-desc">Omitidos por duplicidad</div>
            </div>
        </div>

        <!-- PANEL DE ACCIÓN DESTACADA (GUARDADO) -->
        <?php if ($stats['aptos_guardar'] > 0): ?>
            <div class="modern-card" style="padding: 24px 28px; margin-bottom: 24px; border: 2px solid var(--primary-500); background: linear-gradient(135deg, rgba(37, 99, 235, 0.08) 0%, var(--surface-card) 100%);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--navy-800); margin: 0 0 4px 0;">
                            ✨ Listo para Incorporar <?php echo $stats['aptos_guardar']; ?> Nuevos Beneficiarios Aceptados
                        </h3>
                        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                            Se guardarán únicamente los titulares aprobados por Cabal que no existen en el padrón.
                            <?php if ($stats['rechazados'] > 0): ?>
                                <strong style="color: #dc2626;">(Los <?php echo $stats['rechazados']; ?> titulares rechazados NO serán incorporados a la base de datos).</strong>
                            <?php endif; ?>
                            <?php if ($stats['duplicados'] > 0): ?>
                                <span style="color: #d97706;">(Los <?php echo $stats['duplicados']; ?> ya existentes serán omitidos).</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button type="button" class="btn-nav-action primary" id="btnAbrirModalGuardar" style="padding: 12px 24px; font-size: 0.95rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);">
                        💾 Confirmar e Incorporar (<?php echo $stats['aptos_guardar']; ?>) al Padrón
                    </button>
                </div>
            </div>
        <?php elseif ($stats['rechazados'] > 0 && $stats['aptos_guardar'] == 0): ?>
            <div class="alert-modern danger" style="margin-bottom: 24px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <div>
                    <strong style="font-size: 0.95rem;">Ningún titular es apto para ser incorporado a la tabla beneficiario.</strong><br>
                    Se detectaron <strong><?php echo $stats['rechazados']; ?> titulares con respuesta de rechazo en Cabal</strong><?php echo $stats['duplicados'] > 0 ? " y " . $stats['duplicados'] . " duplicados ya registrados" : ""; ?>. Por normativa estricta, los registros rechazados <strong>no pueden ser incorporados</strong> a la base de datos.
                </div>
            </div>
        <?php else: ?>
            <div class="alert-modern info" style="margin-bottom: 24px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <div>
                    <strong>Todos los titulares analizados ya se encuentran en el padrón de beneficiarios.</strong><br>
                    No se detectaron nuevos registros para incorporar. Puede consultar el padrón activo en <a href="ExportarExcelBeneficiarios.php" style="color: inherit; text-decoration: underline; font-weight: bold;">Ver Padrón de Beneficiarios</a>.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($stats['rechazados'] > 0 && !empty($stats['motivos_rechazo'])): ?>
            <!-- RESUMEN DE CAUSAS DE RECHAZO CABAL (ANEXO 12) -->
            <div class="modern-card" style="margin-bottom: 24px; border-left: 4px solid #dc2626;">
                <div style="padding: 14px 20px; border-bottom: 1px solid var(--border-light); background: rgba(220, 38, 38, 0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="margin: 0; font-size: 0.98rem; font-weight: 800; color: #b91c1c;">
                            ⚠️ Diagnóstico de Rechazos Cabal — Anexo 12 (docs/Cabal.md)
                        </h4>
                        <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: var(--text-muted);">
                            Desglose de motivos oficiales de rechazo devueltos por el Host central para corregir y reemitir
                        </p>
                    </div>
                    <span style="font-size: 0.78rem; font-weight: 700; color: #b91c1c; background: rgba(220, 38, 38, 0.12); padding: 4px 10px; border-radius: var(--radius-full);">
                        <?php echo $stats['rechazados']; ?> titular(es) rechazado(s)
                    </span>
                </div>
                <div style="padding: 16px 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;">
                    <?php foreach ($stats['motivos_rechazo'] as $motivo): ?>
                        <div style="padding: 10px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-md); background: var(--surface-card);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-weight: 800; color: #dc2626; font-size: 0.88rem; font-family: monospace;">
                                    Código <?php echo htmlspecialchars($motivo['codigo'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span style="font-size: 0.76rem; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                                    <?php echo $motivo['cantidad']; ?> titular(es)
                                </span>
                            </div>
                            <div style="font-size: 0.84rem; font-weight: 700; color: var(--navy-800); margin-bottom: 2px;">
                                <?php echo htmlspecialchars($motivo['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.3;">
                                <?php echo htmlspecialchars($motivo['desc'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- TABLA DE DETALLE DE TITULARES -->
        <div class="modern-card">
            <div class="modern-card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                        📋 Detalle de Titulares Analizados en el Archivo
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                        Mostrando <span id="contadorVisibles"><?php echo count($beneficiariosParseados); ?></span> de <?php echo count($beneficiariosParseados); ?> registros
                    </p>
                </div>

                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div class="filter-btn-group">
                        <button type="button" class="filter-btn active" onclick="filtrarEstado('todos', this)">Todos (<?php echo count($beneficiariosParseados); ?>)</button>
                        <button type="button" class="filter-btn" onclick="filtrarEstado('aptos', this)">Aptos para Guardar (<?php echo $stats['aptos_guardar']; ?>)</button>
                        <button type="button" class="filter-btn" onclick="filtrarEstado('rechazados', this)">Rechazados Cabal (<?php echo $stats['rechazados']; ?>)</button>
                        <button type="button" class="filter-btn" onclick="filtrarEstado('duplicados', this)">Ya en BD (<?php echo $stats['duplicados']; ?>)</button>
                    </div>

                    <div style="position: relative; width: 240px;">
                        <input type="text" id="buscadorTabla" onkeyup="buscarEnTabla()" placeholder="Buscar por DNI, Apellido..." style="width: 100%; padding: 8px 12px 8px 32px; border-radius: var(--radius-md); border: 1px solid var(--border-light); font-size: 0.85rem; background: var(--surface-card); color: var(--text-main);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 10px; top: 10px; color: var(--text-muted);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 600px; overflow-y: auto;">
                    <table class="modern-table" id="tablaBeneficiarios">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Titular</th>
                                <th>DNI / CUIT</th>
                                <th>Cuenta Asignada</th>
                                <th>Tarjeta Asignada</th>
                                <th>Localidad / CP</th>
                                <th>Respuesta Cabal</th>
                                <th>Estado Padrón / Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beneficiariosParseados as $b): ?>
                                <?php 
                                    $estadoFila = !$b['es_aceptado'] ? 'rechazado' : ($b['ya_en_bd'] ? 'duplicado' : 'apto');
                                ?>
                                <tr class="fila-titular" data-estado="<?php echo $estadoFila; ?>">
                                    <td><code>#<?php echo $b['index']; ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($b['apellido'] . ' ' . $b['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($b['calle'] . ' ' . $b['numero'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><strong>DNI:</strong> <?php echo htmlspecialchars($b['dni'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);"><strong>CUIT:</strong> <?php echo htmlspecialchars($b['cuit'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--primary-600);"><?php echo htmlspecialchars($b['cuenta'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars(formatearTarjetaCredito($b['tarjeta']), ENT_QUOTES, 'UTF-8'); ?></code>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($b['localidad'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">CP: <?php echo htmlspecialchars($b['cp'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($b['es_aceptado']): ?>
                                            <span class="badge-status success">✓ Aceptado</span>
                                        <?php else: ?>
                                            <span class="badge-status danger">✕ Rechazado</span>
                                            <?php if (!empty($b['errores_detalle'])): ?>
                                                <div style="margin-top: 6px; display: flex; flex-direction: column; gap: 4px;">
                                                    <?php foreach ($b['errores_detalle'] as $errD): ?>
                                                        <div style="font-size: 0.75rem; background: rgba(220, 38, 38, 0.08); border-left: 3px solid #dc2626; padding: 4px 8px; border-radius: 3px;" title="<?php echo htmlspecialchars($errD['desc'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <div style="font-weight: 800; color: #b91c1c;">
                                                                Cód. <?php echo htmlspecialchars($errD['codigo'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                            <div style="color: var(--navy-800); font-weight: 600; line-height: 1.2;">
                                                                <?php echo htmlspecialchars($errD['titulo'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                                                <?php echo htmlspecialchars($errD['desc'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-size: 0.75rem; color: #dc2626; margin-top: 4px; font-weight: 700;">
                                                    Cód: <?php echo htmlspecialchars($b['rechazo'] ? $b['rechazo'] : 'Rechazo Cabal', ENT_QUOTES, 'UTF-8'); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$b['es_aceptado']): ?>
                                            <span class="badge-status danger" style="font-weight: 700;">⛔ Rechazado</span>
                                            <div style="font-size: 0.74rem; color: #dc2626; font-weight: 600; margin-top: 2px;">No se sube a BD</div>
                                        <?php elseif ($b['ya_en_bd']): ?>
                                            <span class="badge-status warning">⚠️ Ya en Padrón</span>
                                            <div style="font-size: 0.74rem; color: #d97706; margin-top: 2px;">Omitido (Duplicado)</div>
                                        <?php else: ?>
                                            <span class="badge-status success">✅ Apto para Guardar</span>
                                            <div style="font-size: 0.74rem; color: #059669; font-weight: 600; margin-top: 2px;">Listo para Incorporar</div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($archivoProcesado && empty($beneficiariosParseados)): ?>
        <div class="alert-modern warning" style="margin-bottom: 24px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <div>
                <strong>No se detectaron registros válidos en el archivo analizado.</strong><br>
                El archivo recibido contiene <strong><?php echo count($lineasRaw); ?> líneas</strong>, pero ninguna coincide con la estructura de registros 01, 02 y 03 requerida por Cabal.<br>
                <?php if (!empty($lineasRaw)): ?>
                    <div style="margin-top: 10px; font-family: monospace; font-size: 0.8rem; background: rgba(0,0,0,0.04); padding: 8px 12px; border-radius: 4px; word-break: break-all;">
                        <strong>Muestra de línea 1 (<?php echo strlen($lineasRaw[0]); ?> bytes):</strong> <?php echo htmlspecialchars(substr($lineasRaw[0], 0, 100), ENT_QUOTES, 'UTF-8'); ?>...
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</main>

<!-- MODAL DE CONFIRMACIÓN EN DOS PASOS (SOLO REGISTROS APTOS Y ACEPTADOS) -->
<div class="modal-overlay" id="modalConfirmarGuardar">
    <div class="modal-card">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(37, 99, 235, 0.1); color: var(--primary-600); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
        </div>
        <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--navy-800); margin: 0 0 8px 0;">
            ¿Confirmar Incorporación al Padrón?
        </h3>
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">
            Se insertarán únicamente <strong><?php echo $stats['aptos_guardar']; ?> titulares aceptados por Cabal</strong> en la tabla <code>beneficiario</code> con sus cuentas y números de plástico asignados.<br>
            <span style="font-size: 0.82rem; color: #dc2626; font-weight: 600;">(Los registros rechazados y los duplicados NO serán subidos).</span>
        </p>

        <form action="ProcesaNovedadesAltas.php" method="POST" id="formConfirmarGuardado">
            <input type="hidden" name="accion" value="guardar_beneficiarios">
            <?php 
                $soloAptos = array();
                foreach ($beneficiariosParseados as $bp) {
                    if ($bp['es_aceptado'] && !$bp['ya_en_bd']) {
                        $soloAptos[] = $bp;
                    }
                }
            ?>
            <input type="hidden" name="datos_json" value="<?php echo htmlspecialchars(json_encode($soloAptos), ENT_QUOTES, 'UTF-8'); ?>">

            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn-modern-secondary" onclick="document.getElementById('modalConfirmarGuardar').classList.remove('active');" style="padding: 10px 20px; font-weight: 600;">
                    Cancelar
                </button>
                <button type="submit" class="btn-nav-action primary" style="padding: 10px 24px; font-weight: 700; cursor: pointer;">
                    Sí, Guardar Beneficiarios Aceptados
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Prevenir apertura por defecto de archivos en la ventana del navegador
    window.addEventListener('dragover', function(e) { e.preventDefault(); }, false);
    window.addEventListener('drop', function(e) { e.preventDefault(); }, false);

    // Drag & Drop para subir archivo de altas
    var dropzone = document.getElementById('dropzoneAltas');
    var formAltas = document.getElementById('formUploadAltas');
    var fileInput = document.getElementById('fileInputAltas');

    function activarEstadoCargando(nombre) {
        var normal = document.getElementById('dropzoneNormalState');
        var loading = document.getElementById('dropzoneLoadingState');
        var txt = document.getElementById('loadingFileName');
        if (normal && loading) {
            normal.style.display = 'none';
            loading.style.display = 'block';
            if (txt && nombre) {
                txt.innerText = 'Subiendo ' + nombre + '...';
            }
        }
    }

    if (dropzone && formAltas && fileInput) {
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
            if (files && files.length > 0) {
                try {
                    fileInput.files = files;
                } catch(err) {
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(files[0]);
                        fileInput.files = dt.files;
                    } catch(err2) {}
                }
                activarEstadoCargando(files[0].name);
                formAltas.submit();
            }
        });

        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files.length > 0) {
                activarEstadoCargando(fileInput.files[0].name);
                formAltas.submit();
            }
        });
    }

    // Modal de confirmación
    var btnAbrirModal = document.getElementById('btnAbrirModalGuardar');
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', function() {
            document.getElementById('modalConfirmarGuardar').classList.add('active');
        });
    }

    // Buscador en vivo en la tabla de titulares
    function buscarEnTabla() {
        var input = document.getElementById('buscadorTabla').value.toLowerCase();
        var filas = document.querySelectorAll('.fila-titular');
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

    // Filtrar por estado (todos, aptos, rechazados, duplicados)
    function filtrarEstado(estado, btn) {
        document.querySelectorAll('.filter-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');

        var filas = document.querySelectorAll('.fila-titular');
        var visibles = 0;

        filas.forEach(function(fila) {
            var est = fila.getAttribute('data-estado');
            var mostrar = false;

            if (estado === 'todos') {
                mostrar = true;
            } else if (estado === 'aptos') {
                mostrar = (est === 'apto');
            } else if (estado === 'rechazados') {
                mostrar = (est === 'rechazado');
            } else if (estado === 'duplicados') {
                mostrar = (est === 'duplicado');
            }

            if (mostrar) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        var contElem = document.getElementById('contadorVisibles');
        if (contElem) contElem.innerText = visibles;
    }

    // Alternar visibilidad del catálogo oficial de errores Anexo 12
    function toggleCatalogoErrores() {
        var panel = document.getElementById('panelCatalogoErrores');
        var btn = document.getElementById('btn-toggle-catalogo');
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
