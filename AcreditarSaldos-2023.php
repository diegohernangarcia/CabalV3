<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Acreditaciones (Acreditaciones.xlsx) a tabla acreditar
 */

require_once('phpexcel/PHPExcel.php');
require_once('BD/BD.php');
require_once("BD/UsarGestion.php");

$archivo = "archivos/Acreditaciones.xlsx";
$archivo_existe = file_exists($archivo);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Acreditaciones 2023 | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1400px;
            margin: 0 auto 50px;
            padding: 0 24px;
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

            <a href="GenerarCargaCredito2.php" class="btn-nav-action" id="btn-generar-novcar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Generar NOVCAR
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

    <section class="help-hero-banner">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                Procesar Archivo Acreditaciones.xlsx
            </h2>
            <p>
                Importación y validación de las acreditaciones de saldo hacia la base de datos (tabla <code>acreditar</code>).
            </p>
        </div>
    </section>

    <?php if (!$archivo_existe): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>No se encontró el archivo:</strong> <code>archivos/Acreditaciones.xlsx</code>.<br>
                Súbalo primero desde el <a href="SubirArchivo.html" style="color: inherit; text-decoration: underline;">Centro de Subida de Archivos</a>.
            </div>
        </div>
    <?php else: 
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = $sheet->getHighestRow(); 
        
        $dia = date("d");
        $mes = date("m");
        $anio = date("y");
        $fecha = $dia . $mes . $anio;
        date_default_timezone_set("America/Mendoza");
        $hora = getdate();
        $horaactual = str_pad($hora["hours"], 2, "0", STR_PAD_LEFT) . str_pad($hora["minutes"], 2, "0", STR_PAD_LEFT) . str_pad($hora["seconds"], 2, "0", STR_PAD_LEFT);

        $num = 0;
        $CargaTarjetaA = array();
        $filas_html = array();
        $error_beneficiario = '';

        for ($row = 2; $row <= $highestRow; $row++) { 
            $num++;
            $apellido = trim((string)$sheet->getCell("C" . $row)->getValue());
            $nombre = trim((string)$sheet->getCell("D" . $row)->getValue());
            $localidad = trim((string)$sheet->getCell("E" . $row)->getValue());
            $dni = trim((string)$sheet->getCell("F" . $row)->getValue());
            $cuit = trim((string)$sheet->getCell("G" . $row)->getValue());
            $monto = $sheet->getCell("H" . $row)->getValue();

            if (empty($dni) && empty($cuit) && empty($apellido)) {
                continue;
            }

            $cuenta = DevuelvoNumeroCuenta($dni, $cuit);
            if (empty($cuenta)) {
                $error_beneficiario = "POR FAVOR VERIFIQUE LOS DATOS DE: " . $apellido . " " . $nombre . " (DNI " . $dni . ", CUIT " . $cuit . ")";
                break;
            }

            $filas_html[] = array(
                'num' => $num,
                'apellido' => $apellido,
                'nombre' => $nombre,
                'localidad' => $localidad,
                'dni' => $dni,
                'cuit' => $cuit,
                'monto' => $monto,
                'cuenta' => $cuenta
            );

            $CargaTarjetaA[] = array(
                'apellido' => $apellido,
                'nombre' => $nombre,
                'localidad' => $localidad,
                'dni' => $dni,
                'cuit' => $cuit,
                'monto' => $monto,
                'cuenta' => $cuenta
            );
        }

        if (!empty($error_beneficiario)): ?>
            <div class="alert-modern danger">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <div>
                    <strong>Validación Interrumpida:</strong> <?php echo htmlspecialchars($error_beneficiario); ?>
                </div>
            </div>
        <?php else: 
            TruncateTable('cargatarjeta');
            TruncateTable('acreditar');

            $my_Insert_Statement = $conn->prepare("INSERT INTO acreditar(id_beneficiario,Apellido,Nombre,NumeroCuenta,ImporteCompra,ImporteCajero,DNI,Fecha,Hora) VALUES (:id_beneficiario,:Apellido,:Nombre,:NumeroCuenta,:ImporteCompra,:ImporteCajero,:DNI,:Fecha,:Hora)");
            
            foreach ($CargaTarjetaA as $c) {
                try {
                    $idBenef = DevuelvoIdBeneficiario($c['dni']);
                    $impCajero = 0;
                    $my_Insert_Statement->bindParam(':id_beneficiario', $idBenef);
                    $my_Insert_Statement->bindParam(':Apellido', $c['apellido']);
                    $my_Insert_Statement->bindParam(':Nombre', $c['nombre']);
                    $my_Insert_Statement->bindParam(':NumeroCuenta', $c['cuenta']);
                    $my_Insert_Statement->bindParam(':ImporteCompra', $c['monto']);
                    $my_Insert_Statement->bindParam(':ImporteCajero', $impCajero);
                    $my_Insert_Statement->bindParam(':DNI', $c['dni']);
                    $my_Insert_Statement->bindParam(':Fecha', $fecha);
                    $my_Insert_Statement->bindParam(':Hora', $horaactual);
                    $my_Insert_Statement->execute();
                } catch (PDOException $e) {
                    echo "<div class='alert-modern danger'>Error al insertar: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        ?>

        <div class="alert-modern success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>
                <strong>¡Acreditación procesada con éxito!</strong> Se importaron <strong><?php echo count($CargaTarjetaA); ?></strong> registros a la tabla <code>acreditar</code>.
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <a href="GenerarCargaCredito2.php" class="btn-modern-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                Continuar a Generar NOVCAR.782 &rarr;
            </a>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Registros Acreditados</h3>
                    <p>Detalle de la planilla importada</p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 520px; overflow-y: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Apellido</th>
                                <th>Nombre</th>
                                <th>Localidad</th>
                                <th>DNI</th>
                                <th>CUIT</th>
                                <th>Monto Acreditar</th>
                                <th>Número de Cuenta</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas_html as $f): ?>
                            <tr>
                                <td><code>#<?php echo $f['num']; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($f['apellido']); ?></strong></td>
                                <td><?php echo htmlspecialchars($f['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($f['localidad']); ?></td>
                                <td><?php echo htmlspecialchars($f['dni']); ?></td>
                                <td><code><?php echo htmlspecialchars($f['cuit']); ?></code></td>
                                <td style="font-weight: 700; color: var(--emerald-600);">$<?php echo number_format((float)$f['monto'], 2, ',', '.'); ?></td>
                                <td><strong><?php echo htmlspecialchars($f['cuenta']); ?></strong></td>
                                <td><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($horaactual); ?></td>
                            </tr>
                            <?php endforeach; ?>
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

</body>
</html>
