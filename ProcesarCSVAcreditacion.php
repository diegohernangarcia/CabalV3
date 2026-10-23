<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Archivo CSV de Acreditaciones
 * Entidad 782 - Cabal
 */

require_once("BD/UsarGestion.php");
require_once("BD/BD.php");
require_once("BD/Funciones.php");

$csvNombre = isset($nombre) ? $nombre : 'Acreditaciones.csv';
$csvFile = 'archivos/' . basename($csvNombre);

$totalRegistros = 0;
$totalMonto = 0;
$erroresCuit = array();
$filasExitosas = 0;

if (file_exists($csvFile)) {
    $file = fopen($csvFile, 'r');
    if ($file) {
        $headers = fgetcsv($file, 1000, ',');
        
        $data = array();
        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            if ($headers && count($headers) === count($row)) {
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($file);

        if (!empty($data)) {
            TruncateTable('cargatarjeta');
            TruncateTable('acreditar');

            date_default_timezone_set("America/Argentina/Buenos_Aires");
            $dia = date("d");
            $mes = date("m");
            $anio = date("y");
            $fecha = $dia . $mes . $anio;
            $hora = getdate();
            $horaactual = str_pad($hora["hours"], 2, "0", STR_PAD_LEFT) . str_pad($hora["minutes"], 2, "0", STR_PAD_LEFT) . str_pad($hora["seconds"], 2, "0", STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO acreditar(id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) VALUES (:id_beneficiario, :Apellido, :Nombre, :NumeroCuenta, :ImporteCompra, :ImporteCajero, :DNI, :Fecha, :Hora)");

            foreach ($data as $clave) {
                $totalRegistros++;
                $apellido = isset($clave["Apellido"]) ? trim($clave["Apellido"]) : '';
                $nombreBenef = isset($clave["Nombre"]) ? trim($clave["Nombre"]) : '';
                $dni = isset($clave["DNI"]) ? trim($clave["DNI"]) : '';
                $cuit = isset($clave["Cuit"]) ? trim($clave["Cuit"]) : (isset($clave["CUIT"]) ? trim($clave["CUIT"]) : '');
                $monto = isset($clave['Monto a Acreditar']) ? (float)$clave['Monto a Acreditar'] : (isset($clave['Monto']) ? (float)$clave['Monto'] : 0);

                if (!empty($cuit) && !validar_cuit_cuil($cuit)) {
                    $erroresCuit[] = "$apellido $nombreBenef (CUIT: $cuit)";
                }

                $cuenta = DevuelvoNumeroCuenta($dni, $cuit);
                $idBenef = DevuelvoIdBeneficiario($dni);

                $stmt->bindParam(':id_beneficiario', $idBenef);
                $stmt->bindParam(':Apellido', $apellido);
                $stmt->bindParam(':Nombre', $nombreBenef);
                $stmt->bindParam(':NumeroCuenta', $cuenta);
                $stmt->bindParam(':ImporteCompra', $monto);
                $importeCajeroCero = 0;
                $stmt->bindParam(':ImporteCajero', $importeCajeroCero);
                $stmt->bindParam(':DNI', $dni);
                $stmt->bindParam(':Fecha', $fecha);
                $stmt->bindParam(':Hora', $horaactual);
                $stmt->execute();

                $totalMonto += $monto;
                $filasExitosas++;
            }
        }
    }
}
?>

<div class="modern-card" style="margin-bottom: 24px;">
    <div class="card-header-custom" style="padding: 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
            📊 Resultado de la Importación de CSV
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
            Archivo procesado: <code><?php echo htmlspecialchars(basename($csvFile), ENT_QUOTES, 'UTF-8'); ?></code>
        </p>
    </div>

    <div class="card-body-custom" style="padding: 28px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Registros Acreditados
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                    <?php echo number_format($filasExitosas, 0, ',', '.'); ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    Monto Total Acreditado
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                    $ <?php echo number_format($totalMonto, 2, ',', '.'); ?>
                </div>
            </div>

            <div class="modern-card" style="padding: 20px;">
                <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                    CUITs con Advertencia
                </div>
                <div style="font-size: 1.8rem; font-weight: 800; color: <?php echo count($erroresCuit) > 0 ? '#dc2626' : '#059669'; ?>;">
                    <?php echo count($erroresCuit); ?>
                </div>
            </div>
        </div>

        <?php if (!empty($erroresCuit)): ?>
        <div class="alert-modern warning" style="margin-bottom: 20px;">
            <strong>Advertencias de CUIT/CUIL:</strong>
            <ul style="margin: 8px 0 0 20px; padding: 0;">
                <?php foreach ($erroresCuit as $err): ?>
                    <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 14px; justify-content: flex-end; flex-wrap: wrap;">
            <a href="AcreditarCSV.php" class="btn-modern-secondary" style="padding: 10px 20px; border-radius: var(--radius-sm); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                Cargar Otro Archivo
            </a>
            <a href="GenerarCargaCredito2026.php" class="btn-modern-primary" style="padding: 10px 24px; border-radius: var(--radius-sm); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                Continuar a Generar NOVCAR
            </a>
        </div>
    </div>
</div>