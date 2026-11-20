<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Informe Padrón General de Beneficiarios con Generador de Correo EML
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Consulta y audita el padrón institucional completo desde la tabla `beneficiario`.
 * 2. Genera informe en HTML institucional con KPIs (total titulares, localidades, cuentas, tarjetas activas).
 * 3. Aplica estrictamente enmascaramiento en todos los números de tarjetas (formatearTarjetaCredito()).
 * 4. Genera y descarga archivo .EML estándar (MIME/RFC 822) con cabecera X-Unsent: 1 listo para Thunderbird.
 * 5. Incorpora cabecera institucional dual: Ministerio de Desarrollo Humano y Cabal Entidad 782.
 * 6. Denominación oficial: "Secretaria de Niñez, Adolescencia y Familia".
 * 7. Búsqueda y filtrado interactivo en tiempo real por apellido, nombre, DNI, CUIL, localidad y cuenta.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/auth/verificar_sesion.php");
requerirAutenticacion();

require_once(__DIR__ . "/BD/Funciones.php");

// Configuración de zona horaria y cabeceras anti-caché
date_default_timezone_set("America/Argentina/Buenos_Aires");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Conexión a la base de datos (con fallback robusto)
$db_online = false;
$beneficiarios = array();
$localidades = array();
$stats = array(
    'total_titulares'   => 0,
    'total_localidades' => 0,
    'con_tarjeta'       => 0,
    'con_cuenta'        => 0,
    'fecha_emision'     => date('d/m/Y'),
    'hora_emision'      => date('H:i')
);

// Intentar conexión vía PDO o MySQLi
$usuario_db = 'pucho';
$clave_db = '78900';
$nombre_db = 'cabal';
$host_db = 'localhost';

try {
    if (class_exists('PDO')) {
        $dsn = "mysql:host={$host_db};dbname={$nombre_db};charset=utf8";
        $pdo = new PDO($dsn, $usuario_db, $clave_db, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        $db_online = true;

        $stmt = $pdo->query("SELECT id, Apellido, Nombre, NumeroDocumento, Cuit, NumeroCuenta, NumeroTarjeta, Localidad, Provincia, FechaProceso FROM beneficiario ORDER BY Apellido ASC, Nombre ASC");
        if ($stmt) {
            $beneficiarios = $stmt->fetchAll();
        }
    }
} catch (Exception $e) {
    // Si falla PDO, intentar con mysqli
    if (class_exists('mysqli')) {
        $conn = @new mysqli($host_db, $usuario_db, $clave_db, $nombre_db);
        if (!$conn->connect_error) {
            $conn->set_charset("utf8");
            $db_online = true;
            $res = $conn->query("SELECT id, Apellido, Nombre, NumeroDocumento, Cuit, NumeroCuenta, NumeroTarjeta, Localidad, Provincia, FechaProceso FROM beneficiario ORDER BY Apellido ASC, Nombre ASC");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $beneficiarios[] = $row;
                }
            }
            $conn->close();
        }
    }
}

// Procesar métricas y sanitización de datos
$locs_map = array();
foreach ($beneficiarios as $b) {
    $stats['total_titulares']++;
    $tarjeta = trim(isset($b['NumeroTarjeta']) ? $b['NumeroTarjeta'] : '');
    $cuenta = trim(isset($b['NumeroCuenta']) ? $b['NumeroCuenta'] : '');
    $localidad = trim(isset($b['Localidad']) ? $b['Localidad'] : '');

    if ($tarjeta !== '' && strlen($tarjeta) >= 12) {
        $stats['con_tarjeta']++;
    }
    if ($cuenta !== '' && $cuenta !== '0') {
        $stats['con_cuenta']++;
    }
    if ($localidad !== '' && $localidad !== '-') {
        $locs_map[$localidad] = true;
    }
}
ksort($locs_map);
$localidades = array_keys($locs_map);
$stats['total_localidades'] = count($localidades);

// -----------------------------------------------------------------------------
// GENERACIÓN Y DESCARGA DE ARCHIVO EML (.eml)
// -----------------------------------------------------------------------------
if (isset($_GET['accion']) && $_GET['accion'] === 'descargar_eml') {
    $fechaHoy = date('d/m/Y');
    $asuntoEmail = "[SeNAF] Padrón General de Beneficiarios Cabal FSR - Actualizado al " . $fechaHoy;
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

    // Filtro opcional por localidad para el EML si se solicitó
    $filtroLoc = isset($_GET['localidad']) ? trim($_GET['localidad']) : '';
    $beneficiariosEml = array();
    foreach ($beneficiarios as $b) {
        if ($filtroLoc === '' || strcasecmp(trim($b['Localidad']), $filtroLoc) === 0) {
            $beneficiariosEml[] = $b;
        }
    }

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
    .email-wrapper { max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .header-banner { background: #ffffff; padding: 24px 30px; color: #0f172a; border-bottom: 4px solid #2563eb; }
    .header-banner h1 { margin: 10px 0 0 0; font-size: 19px; font-weight: 700; color: #0f172a; letter-spacing: 0.3px; }
    .header-banner p { margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 600; }
    .badge-institucional { display: inline-block; background: #2563eb; color: #ffffff; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-top: 8px; text-transform: uppercase; }
    .content-area { padding: 28px 30px; font-size: 14px; line-height: 1.6; }
    .kpi-container { display: table; width: 100%; margin: 20px 0; border-collapse: separate; border-spacing: 10px; }
    .kpi-box { display: table-cell; width: 25%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; }
    .kpi-num { font-size: 22px; font-weight: bold; color: #1e3a8a; }
    .kpi-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-top: 4px; }
    .table-beneficiarios { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
    .table-beneficiarios th { background: #1e293b; color: #ffffff; text-align: left; padding: 10px; font-size: 11px; text-transform: uppercase; }
    .table-beneficiarios td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
    .table-beneficiarios tr:nth-child(even) { background-color: #f8fafc; }
    .tarjeta-code { font-family: monospace; font-size: 12px; font-weight: bold; color: #1d4ed8; background: #eff6ff; padding: 2px 6px; border-radius: 4px; border: 1px solid #bfdbfe; }
    .cuenta-code { font-family: monospace; font-size: 11px; color: #475569; }
    .badge-localidad { display: inline-block; padding: 2px 7px; background: #f1f5f9; color: #334155; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid #e2e8f0; }
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
        <p>Por medio de la presente se remite el informe institucional consolidado del <strong>Padrón General de Beneficiarios Cabal FSR</strong> correspondiente a la Entidad 782, actualizado al <strong><?php echo $fechaHoy; ?></strong><?php echo !empty($filtroLoc) ? " (Filtrado por Localidad: <strong>" . htmlspecialchars($filtroLoc, ENT_QUOTES, 'UTF-8') . "</strong>)" : ""; ?>.</p>

        <div class="kpi-container">
            <div class="kpi-box">
                <div class="kpi-num"><?php echo count($beneficiariosEml); ?></div>
                <div class="kpi-label">Titulares Reportados</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #2563eb;">
                <div class="kpi-num" style="color: #2563eb;"><?php echo $stats['total_localidades']; ?></div>
                <div class="kpi-label">Localidades Activas</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #059669;">
                <div class="kpi-num" style="color: #059669;"><?php echo $stats['con_tarjeta']; ?></div>
                <div class="kpi-label">Tarjetas Asignadas</div>
            </div>
            <div class="kpi-box" style="border-top: 3px solid #7c3aed;">
                <div class="kpi-num" style="color: #7c3aed;"><?php echo $stats['con_cuenta']; ?></div>
                <div class="kpi-label">Cuentas Registradas</div>
            </div>
        </div>

        <h3 style="font-size: 14px; color: #0f172a; margin-top: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px;">
            Nómina de Beneficiarios Titulares
        </h3>

        <table class="table-beneficiarios">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Apellido y Nombre</th>
                    <th>Documento / CUIT</th>
                    <th>Localidad</th>
                    <th>Nº Cuenta</th>
                    <th>Tarjeta (Enmascarada)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                foreach ($beneficiariosEml as $b): 
                    $tarjetaRaw = trim(isset($b['NumeroTarjeta']) ? $b['NumeroTarjeta'] : '');
                    $tarjetaFmt = !empty($tarjetaRaw) ? formatearTarjetaCredito($tarjetaRaw) : 'XXXX.XX**.****.XXXX';
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($b['Apellido'] . ', ' . $b['Nombre'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td>
                        <span>DNI: <?php echo htmlspecialchars($b['NumeroDocumento'], ENT_QUOTES, 'UTF-8'); ?></span><br>
                        <span style="font-size: 10px; color: #64748b;">CUIL: <?php echo htmlspecialchars($b['Cuit'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    <td><span class="badge-localidad"><?php echo htmlspecialchars($b['Localidad'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><code class="cuenta-code"><?php echo htmlspecialchars($b['NumeroCuenta'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><code class="tarjeta-code"><?php echo htmlspecialchars($tarjetaFmt, ENT_QUOTES, 'UTF-8'); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top: 25px; font-size: 12px; color: #64748b; line-height: 1.5;">
            * <em>Por directiva estricta de seguridad y confidencialidad bancaria (norma PCI-DSS / Ley 25.326), los dígitos centrales de las tarjetas de crédito y débito se encuentran debidamente enmascarados.</em>
        </p>
    </div>

    <div class="footer-email">
        <strong>Secretaria de Niñez, Adolescencia y Familia &bull; SeNAF</strong><br>
        Ministerio de Desarrollo Humano &bull; Gobierno de la Provincia de Río Negro<br>
        Sistema Cabal FSR &bull; Entidad Emisora 782 &bull; Generado automáticamente el <?php echo date('d/m/Y H:i'); ?>
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
    $eml .= "Message-ID: <padron-" . time() . "-" . rand(1000, 9999) . "@senaf.rionegro.gov.ar>\r\n";
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

    $nombreDescarga = "Informe_Padron_General_SeNAF_" . date('Ymd_His') . ".eml";
    header('Content-Type: message/rfc822; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
    header('Content-Length: ' . strlen($eml));
    echo $eml;
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Padrón General &bull; Cabal FSR - SeNAF</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-dark: #1e40af;
            --cabal-blue: #004b93;
            --cabal-green: #059669;
        }

        body {
            background-color: var(--bg-main, #f8fafc);
            color: var(--text-main, #0f172a);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .page-container {
            max-width: 1440px;
            margin: 0 auto 60px;
            padding: 0 24px;
        }

        /* Banner Institucional Dual */
        .institucional-banner {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-light, #e2e8f0);
            padding: 24px 30px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border-left: 6px solid var(--primary-blue);
        }

        .banner-logos {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .banner-logos img.logo-min {
            max-height: 52px;
            width: auto;
            max-width: 320px;
            object-fit: contain;
        }

        .banner-logos img.logo-cabal {
            max-height: 48px;
            width: auto;
            object-fit: contain;
        }

        .banner-titles h2 {
            margin: 0 0 4px 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .banner-titles p {
            margin: 0;
            font-size: 0.88rem;
            color: #64748b;
            font-weight: 600;
        }

        /* Barra de herramientas / Botonera */
        .toolbar-box {
            background: #ffffff;
            border: 1px solid var(--border-light, #e2e8f0);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }

        .toolbar-group-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .toolbar-group-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input-box {
            position: relative;
            min-width: 280px;
        }

        .search-input-box input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            font-size: 0.88rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            color: #1e293b;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .search-input-box input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .search-input-box svg {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .select-filter {
            padding: 9px 14px;
            font-size: 0.88rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            color: #1e293b;
            cursor: pointer;
        }

        .select-filter:focus {
            outline: none;
            border-color: var(--primary-blue);
        }

        .btn-action-tool {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-action-tool.primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #1d4ed8;
        }

        .btn-action-tool.primary:hover {
            background: #1d4ed8;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }

        .btn-action-tool.eml {
            background: #059669;
            color: #ffffff;
            border-color: #047857;
        }

        .btn-action-tool.eml:hover {
            background: #047857;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3);
        }

        .btn-action-tool.secondary {
            background: #f8fafc;
            color: #334155;
            border-color: #cbd5e1;
        }

        .btn-action-tool.secondary:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* KPI Cards Grid */
        .kpi-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .kpi-stat-card {
            background: #ffffff;
            border: 1px solid var(--border-light, #e2e8f0);
            border-radius: 10px;
            padding: 18px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            border-top: 4px solid var(--primary-blue);
        }

        .kpi-stat-card.green { border-top-color: #059669; }
        .kpi-stat-card.purple { border-top-color: #7c3aed; }
        .kpi-stat-card.amber { border-top-color: #d97706; }

        .kpi-stat-num {
            font-size: 1.85rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .kpi-stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 700;
            margin-top: 6px;
        }

        /* Tabla de Padrón */
        .modern-card-padron {
            background: #ffffff;
            border-radius: 10px;
            border: 1px solid var(--border-light, #e2e8f0);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .card-padron-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-light, #e2e8f0);
            background: #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-padron-header h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .card-padron-header p {
            margin: 2px 0 0;
            font-size: 0.82rem;
            color: #64748b;
        }

        .table-responsive-padron {
            overflow-x: auto;
            max-height: 650px;
            overflow-y: auto;
        }

        .table-padron {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
            text-align: left;
        }

        .table-padron th {
            background: #1e293b;
            color: #ffffff;
            font-weight: 700;
            padding: 12px 14px;
            position: sticky;
            top: 0;
            z-index: 10;
            text-transform: uppercase;
            font-size: 0.76rem;
            letter-spacing: 0.4px;
        }

        .table-padron td {
            padding: 10px 14px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .table-padron tr:hover {
            background-color: #f1f5f9;
        }

        .tarjeta-badge {
            font-family: monospace;
            font-size: 0.88rem;
            font-weight: 700;
            color: #1d4ed8;
            background: #eff6ff;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            display: inline-block;
        }

        .cuenta-badge {
            font-family: monospace;
            font-size: 0.84rem;
            color: #475569;
            font-weight: 600;
        }

        .localidad-tag {
            background: #f1f5f9;
            color: #334155;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }

        /* Modal Previsualización EML */
        .modal-eml-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.75);
            z-index: 9999;
            backdrop-filter: blur(4px);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-eml-window {
            background: #ffffff;
            border-radius: 12px;
            max-width: 860px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }

        .modal-eml-header {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-eml-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .modal-eml-body {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-eml-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Botón Flotante Volver */
        .btn-modern-flotante {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e293b;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            transition: all 0.2s ease;
            z-index: 900;
        }

        .btn-modern-flotante:hover {
            background: #0f172a;
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -3px rgba(0, 0, 0, 0.4);
        }

        @media print {
            .top-nav, .toolbar-box, .btn-modern-flotante, .modal-eml-backdrop {
                display: none !important;
            }
            .page-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .institucional-banner {
                box-shadow: none;
                border: none;
                border-bottom: 2px solid #0f172a;
            }
            .table-responsive-padron {
                max-height: none;
                overflow: visible;
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
                <p>Familia Rionegrina Solidaria &bull; SeNAF Río Negro</p>
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

            <a href="ReporteAltaBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                Padrón Interactivo
            </a>

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Exportar Excel
            </a>

            <button type="button" class="btn-nav-action" id="btn-theme-toggle" onclick="toggleTheme()" title="Alternar tema oscuro / claro">
                🌓
            </button>
        </div>
    </div>
</header>

<main class="page-container" style="margin-top: 28px;">

    <!-- CABECERA INSTITUCIONAL OFICIAL -->
    <section class="institucional-banner">
        <div class="banner-logos">
            <img src="img/cabecera_ministerio.png" alt="Ministerio de Desarrollo Humano - Gobierno de Río Negro" class="logo-min" onerror="this.style.display='none'">
            <img src="img/logo_cabal_oficial.png" alt="Cabal Entidad 782" class="logo-cabal" onerror="this.src='img/cabal.png'">
        </div>
        <div class="banner-titles">
            <h2>Secretaria de Niñez, Adolescencia y Familia &bull; SeNAF</h2>
            <p>Programa Familia Rionegrina Solidaria &bull; Padrón General de Beneficiarios Titulares</p>
        </div>
    </section>

    <!-- BARRA DE HERRAMIENTAS Y ACCIONES -->
    <div class="toolbar-box">
        <div class="toolbar-group-left">
            <div class="search-input-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="filtroTexto" placeholder="Buscar por Apellido, Nombre, DNI, CUIL, Cuenta..." onkeyup="filtrarTablaPadron()">
            </div>

            <select id="filtroLocalidad" class="select-filter" onchange="filtrarTablaPadron()">
                <option value="">Todas las Localidades (<?php echo count($localidades); ?>)</option>
                <?php foreach ($localidades as $loc): ?>
                    <option value="<?php echo htmlspecialchars($loc, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($loc, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="toolbar-group-right">
            <button type="button" class="btn-action-tool primary" onclick="abrirModalEml()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                Vista Previa Correo EML
            </button>

            <a href="InformePadronGeneral.php?accion=descargar_eml" id="btnDescargaDirectaEml" class="btn-action-tool eml" title="Descarga directa para Thunderbird">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Descargar EML (.eml)
            </a>

            <button type="button" class="btn-action-tool secondary" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Imprimir / PDF
            </button>
        </div>
    </div>

    <!-- TARJETAS DE INDICADORES (KPIs) -->
    <div class="kpi-cards-grid">
        <div class="kpi-stat-card">
            <div class="kpi-stat-num" id="kpiTitulares"><?php echo number_format($stats['total_titulares'], 0, ',', '.'); ?></div>
            <div class="kpi-stat-label">Titulares en Padrón</div>
        </div>

        <div class="kpi-stat-card green">
            <div class="kpi-stat-num"><?php echo number_format($stats['con_tarjeta'], 0, ',', '.'); ?></div>
            <div class="kpi-stat-label">Tarjetas Asignadas (Enmascaradas)</div>
        </div>

        <div class="kpi-stat-card purple">
            <div class="kpi-stat-num"><?php echo number_format($stats['con_cuenta'], 0, ',', '.'); ?></div>
            <div class="kpi-stat-label">Cuentas Registradas</div>
        </div>

        <div class="kpi-stat-card amber">
            <div class="kpi-stat-num"><?php echo number_format($stats['total_localidades'], 0, ',', '.'); ?></div>
            <div class="kpi-stat-label">Localidades con Cobertura</div>
        </div>
    </div>

    <!-- TABLA PRINCIPAL DE BENEFICIARIOS -->
    <div class="modern-card-padron">
        <div class="card-padron-header">
            <div>
                <h3>Nómina Oficial del Padrón de Beneficiarios</h3>
                <p>Mostrando <span id="cantFilasMostradas"><?php echo count($beneficiarios); ?></span> registros activos &bull; Tarjetas enmascaradas bajo norma PCI-DSS</p>
            </div>
            <div style="font-size: 0.82rem; color: #64748b;">
                Actualizado al: <strong><?php echo $stats['fecha_emision']; ?></strong>
            </div>
        </div>

        <div class="table-responsive-padron">
            <table class="table-padron" id="tablaPadron">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th>Apellido y Nombre</th>
                        <th>DNI / CUIT</th>
                        <th>Localidad</th>
                        <th>Provincia</th>
                        <th>Nº Cuenta</th>
                        <th>Tarjeta Enmascarada</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($beneficiarios)): ?>
                        <?php 
                        $n = 1;
                        foreach ($beneficiarios as $b): 
                            $tarjetaRaw = trim(isset($b['NumeroTarjeta']) ? $b['NumeroTarjeta'] : '');
                            $tarjetaFmt = !empty($tarjetaRaw) ? formatearTarjetaCredito($tarjetaRaw) : 'XXXX.XX**.****.XXXX';
                        ?>
                        <tr class="fila-padron" 
                            data-titular="<?php echo htmlspecialchars(strtolower($b['Apellido'] . ' ' . $b['Nombre'])); ?>"
                            data-dni="<?php echo htmlspecialchars(strtolower($b['NumeroDocumento'])); ?>"
                            data-cuit="<?php echo htmlspecialchars(strtolower($b['Cuit'])); ?>"
                            data-cuenta="<?php echo htmlspecialchars(strtolower($b['NumeroCuenta'])); ?>"
                            data-localidad="<?php echo htmlspecialchars($b['Localidad']); ?>">
                            <td style="text-align: center; color: #94a3b8; font-weight: 600;"><?php echo $n++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($b['Apellido'] . ', ' . $b['Nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <td>
                                <div>DNI: <strong><?php echo htmlspecialchars($b['NumeroDocumento'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div style="font-size: 0.78rem; color: #64748b;">CUIL: <?php echo htmlspecialchars($b['Cuit'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <span class="localidad-tag"><?php echo htmlspecialchars($b['Localidad'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td style="color: #64748b; font-size: 0.82rem;">
                                <?php echo htmlspecialchars(!empty($b['Provincia']) ? $b['Provincia'] : 'RÍO NEGRO', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <span class="cuenta-badge"><?php echo htmlspecialchars($b['NumeroCuenta'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td>
                                <span class="tarjeta-badge"><?php echo htmlspecialchars($tarjetaFmt, ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td style="text-align: right;">
                                <a href="consultaBeneficiario.php?Cuit=<?php echo urlencode($b['Cuit']); ?>" class="btn-action-tool secondary" style="padding: 4px 10px; font-size: 0.78rem;">
                                    Ficha
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No se encontraron registros en el padrón o la base de datos se encuentra desconectada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- VENTANA MODAL PREVIA DEL CORREO EML -->
<div class="modal-eml-backdrop" id="modalEml">
    <div class="modal-eml-window">
        <div class="modal-eml-header">
            <h3>✉️ Vista Previa del Correo SeNAF (.eml para Thunderbird)</h3>
            <button type="button" onclick="cerrarModalEml()" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <div class="modal-eml-body">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                    <strong style="width: 90px; color: #475569; font-size: 0.85rem;">De (From):</strong>
                    <span style="font-size: 0.85rem; color: #0f172a;">"SeNAF - Cabal FSR" &lt;senaf-cabal@rionegro.gov.ar&gt;</span>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px;">
                    <strong style="width: 90px; color: #475569; font-size: 0.85rem;">Para (To):</strong>
                    <input type="text" id="inputDestinatarioEml" placeholder="Dejar en blanco para completar en Thunderbird o escribir email..." 
                           style="flex: 1; padding: 6px 10px; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 4px;"
                           oninput="actualizarEnlaceDescargaEml()">
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <strong style="width: 90px; color: #475569; font-size: 0.85rem;">Asunto:</strong>
                    <span style="font-size: 0.85rem; color: #0f172a; font-weight: 600;">[SeNAF] Padrón General de Beneficiarios Cabal FSR - Actualizado al <?php echo date('d/m/Y'); ?></span>
                </div>
            </div>

            <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 20px; background: #ffffff; max-height: 420px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <tr>
                        <td style="text-align: left; vertical-align: middle;">
                            <img src="img/cabecera_ministerio.png" alt="Gobierno de Río Negro" style="max-height: 44px;">
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <img src="img/logo_cabal_oficial.png" alt="Cabal" style="max-height: 40px;">
                        </td>
                    </tr>
                </table>

                <h4 style="margin: 0 0 4px; color: #0f172a; font-size: 1.05rem;">Secretaria de Niñez, Adolescencia y Familia &bull; SeNAF</h4>
                <p style="margin: 0 0 14px; font-size: 0.82rem; color: #64748b;">Programa Familia Rionegrina Solidaria &bull; Gobierno de Río Negro</p>

                <p style="font-size: 0.88rem; line-height: 1.5; color: #334155;">
                    Se remite el informe institucional con la nómina activa de <strong><?php echo number_format($stats['total_titulares'], 0, ',', '.'); ?> titulares</strong> del Padrón Cabal FSR (Entidad 782).
                </p>

                <div style="display: flex; gap: 10px; margin: 15px 0;">
                    <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold; color: #1e40af;"><?php echo count($beneficiarios); ?></div>
                        <div style="font-size: 0.72rem; color: #64748b; text-transform: uppercase;">Titulares</div>
                    </div>
                    <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold; color: #059669;"><?php echo $stats['con_tarjeta']; ?></div>
                        <div style="font-size: 0.72rem; color: #64748b; text-transform: uppercase;">Tarjetas</div>
                    </div>
                    <div style="flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold; color: #d97706;"><?php echo $stats['total_localidades']; ?></div>
                        <div style="font-size: 0.72rem; color: #64748b; text-transform: uppercase;">Localidades</div>
                    </div>
                </div>

                <div style="font-size: 0.78rem; color: #64748b; font-style: italic; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                    El archivo .eml generado incluye cabecera <code>X-Unsent: 1</code> y tablas con números de tarjeta enmascarados, listo para abrir directamente como borrador en Mozilla Thunderbird.
                </div>
            </div>
        </div>
        <div class="modal-eml-footer">
            <button type="button" class="btn-action-tool secondary" onclick="cerrarModalEml()">Cerrar</button>
            <a href="InformePadronGeneral.php?accion=descargar_eml" id="btnDescargarModal" class="btn-action-tool eml">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Descargar Archivo EML (.eml)
            </a>
        </div>
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
    function filtrarTablaPadron() {
        var texto = document.getElementById('filtroTexto').value.toLowerCase().trim();
        var localidad = document.getElementById('filtroLocalidad').value.toLowerCase().trim();
        var filas = document.querySelectorAll('.fila-padron');
        var visibles = 0;

        filas.forEach(function(fila) {
            var titular = fila.getAttribute('data-titular') || '';
            var dni = fila.getAttribute('data-dni') || '';
            var cuit = fila.getAttribute('data-cuit') || '';
            var cuenta = fila.getAttribute('data-cuenta') || '';
            var loc = (fila.getAttribute('data-localidad') || '').toLowerCase().trim();

            var coincideTexto = (texto === '' || 
                                 titular.indexOf(texto) !== -1 || 
                                 dni.indexOf(texto) !== -1 || 
                                 cuit.indexOf(texto) !== -1 || 
                                 cuenta.indexOf(texto) !== -1);

            var coincideLoc = (localidad === '' || loc === localidad);

            if (coincideTexto && coincideLoc) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });

        document.getElementById('cantFilasMostradas').textContent = visibles;
    }

    function abrirModalEml() {
        var modal = document.getElementById('modalEml');
        if (modal) {
            modal.style.display = 'flex';
            actualizarEnlaceDescargaEml();
        }
    }

    function cerrarModalEml() {
        var modal = document.getElementById('modalEml');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function actualizarEnlaceDescargaEml() {
        var destInput = document.getElementById('inputDestinatarioEml');
        var dest = destInput ? destInput.value.trim() : '';
        var locSelect = document.getElementById('filtroLocalidad');
        var loc = locSelect ? locSelect.value.trim() : '';

        var url = 'InformePadronGeneral.php?accion=descargar_eml';
        if (dest !== '') {
            url += '&destinatario=' + encodeURIComponent(dest);
        }
        if (loc !== '') {
            url += '&localidad=' + encodeURIComponent(loc);
        }

        var btnModal = document.getElementById('btnDescargarModal');
        if (btnModal) {
            btnModal.href = url;
        }

        var btnDirecto = document.getElementById('btnDescargaDirectaEml');
        if (btnDirecto) {
            btnDirecto.href = url;
        }
    }

    // Cerrar modal al presionar ESC o hacer clic en backdrop
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModalEml();
        }
    });

    document.getElementById('modalEml').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalEml();
        }
    });
</script>

</body>
</html>
