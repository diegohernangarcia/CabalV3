<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Importación de Planilla Excel AltaCargas a Base de Datos (Tabla acreditar)
 * Entidad 782 - Cabal
 */

require_once('phpexcel/PHPExcel.php');
require_once('BD/BD.php');
require_once('BD/Funciones.php');

$archivo = "archivos/AltaCargas.xlsx";
$archivoExiste = file_exists($archivo);

$num = 0;
$totalRegistros = 0;
$totalCompras = 0;
$totalCajero = 0;
$filasPreview = array();
$errorMensaje = "";
$exito = false;

date_default_timezone_set("America/Argentina/Buenos_Aires");
$dia = date("d");
$mes = date("m");
$anio = date("y");
$fecha = $dia . $mes . $anio;
$hora = getdate();
$horaactual = str_pad($hora["hours"], 2, "0", STR_PAD_LEFT) . str_pad($hora["minutes"], 2, "0", STR_PAD_LEFT) . str_pad($hora["seconds"], 2, "0", STR_PAD_LEFT);

if ($archivoExiste) {
    try {
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = (int)$sheet->getHighestRow();

        $CargaTarjetaA = array();

        for ($row = 2; $row <= $highestRow; $row++) { 
            $apellido = trim((string)$sheet->getCell("B" . $row)->getValue());
            $nombre = trim((string)$sheet->getCell("C" . $row)->getValue());
            $cuentaRaw = $sheet->getCell("E" . $row)->getValue();
            $cuenta = number_format((float)$cuentaRaw, 0, '.', '');
            $compra = (float)$sheet->getCell("F" . $row)->getValue();
            $cajero = (float)$sheet->getCell("G" . $row)->getValue();
            $dni = trim((string)$sheet->getCell("D" . $row)->getValue());

            if ($apellido === '' && $nombre === '' && $dni === '') {
                continue;
            }

            $num++;
            $totalCompras += $compra;
            $totalCajero += $cajero;

            $idx = $num - 1;
            $CargaTarjetaA[$idx][0] = $apellido;
            $CargaTarjetaA[$idx][1] = $nombre;
            $CargaTarjetaA[$idx][2] = $cuenta;
            $CargaTarjetaA[$idx][3] = $compra;
            $CargaTarjetaA[$idx][4] = $cajero;
            $CargaTarjetaA[$idx][5] = $dni;
            $CargaTarjetaA[$idx][6] = $fecha;
            $CargaTarjetaA[$idx][7] = $horaactual;

            if ($num <= 100) {
                $filasPreview[] = array(
                    'num'      => $num,
                    'apellido' => $apellido,
                    'nombre'   => $nombre,
                    'cuenta'   => $cuenta,
                    'compra'   => $compra,
                    'cajero'   => $cajero,
                    'dni'      => $dni,
                    'fecha'    => $fecha,
                    'hora'     => $horaactual
                );
            }
        }

        $totalRegistros = count($CargaTarjetaA);

        if ($totalRegistros > 0) {
            TruncateTable('cargatarjeta');
            TruncateTable('acreditar');

            require_once("BD/UsarGestion.php");
            $my_Insert_Statement = $conn->prepare("INSERT INTO acreditar(id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) VALUES (:id_beneficiario, :Apellido, :Nombre, :NumeroCuenta, :ImporteCompra, :ImporteCajero, :DNI, :Fecha, :Hora)");

            for ($reg = 0; $reg < $totalRegistros; $reg++) {
                $Apellido      = strval($CargaTarjetaA[$reg][0]); 
                $Nombre        = strval($CargaTarjetaA[$reg][1]); 
                $NumeroCuenta  = strval($CargaTarjetaA[$reg][2]); 
                $ImporteCompra = strval($CargaTarjetaA[$reg][3]); 
                $ImporteCajero = strval($CargaTarjetaA[$reg][4]); 
                $DNI           = strval($CargaTarjetaA[$reg][5]); 
                $IdBenef       = DevuelvoIdBeneficiario($DNI);
                $FechaReg      = strval($CargaTarjetaA[$reg][6]); 
                $HoraReg       = strval($CargaTarjetaA[$reg][7]); 

                $my_Insert_Statement->bindParam(':id_beneficiario', $IdBenef);
                $my_Insert_Statement->bindParam(':Apellido', $Apellido);
                $my_Insert_Statement->bindParam(':Nombre', $Nombre);
                $my_Insert_Statement->bindParam(':NumeroCuenta', $NumeroCuenta);
                $my_Insert_Statement->bindParam(':ImporteCompra', $ImporteCompra);
                $my_Insert_Statement->bindParam(':ImporteCajero', $ImporteCajero);
                $my_Insert_Statement->bindParam(':DNI', $DNI);
                $my_Insert_Statement->bindParam(':Fecha', $FechaReg);
                $my_Insert_Statement->bindParam(':Hora', $HoraReg);
                $my_Insert_Statement->execute();
            }
            $exito = true;
        } else {
            $errorMensaje = "El archivo se abrió correctamente pero no contenía filas con datos procesables.";
        }
    } catch (Exception $e) {
        $errorMensaje = "Error durante el procesamiento del archivo Excel: " . $e->getMessage();
    }
} else {
    $errorMensaje = "No se encontró el archivo 'archivos/AltaCargas.xlsx' en el servidor. Por favor cárguelo desde la sección de subida de archivos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Cargas a BD — Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
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
            <a href="GenerarCargaCredito2.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Generar NOVCAR
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1200px; padding-top: 24px;">

    <?php if ($exito): ?>
    <div class="alert-modern success" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">
            ✅ Lote de Acreditación Importado Correctamente
        </div>
        <div>
            Se procesaron y grabaron <strong><?php echo number_format($totalRegistros, 0, ',', '.'); ?></strong> registros en la tabla <code>acreditar</code>.
        </div>
    </div>
    <?php elseif (!empty($errorMensaje)): ?>
    <div class="alert-modern danger" style="margin-bottom: 24px;">
        <strong>Error:</strong> <?php echo htmlspecialchars($errorMensaje, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <!-- TARJETAS DE MÉTRICAS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Beneficiarios a Acreditar
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                <?php echo number_format($totalRegistros, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Total Crédito Compras
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                $ <?php echo number_format($totalCompras, 2, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Total Crédito Cajero
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #2563eb;">
                $ <?php echo number_format($totalCajero, 2, ',', '.'); ?>
            </div>
        </div>
    </div>

    <!-- TABLA DE REGISTROS -->
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 18px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                    Registros Importados en Tabla Acreditar
                </h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Mostrando primeros registros cargados en base de datos
                </p>
            </div>
            <div>
                <a href="GenerarCargaCredito2.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; border-radius: var(--radius-sm);">
                    Generar NOVCAR Bancario
                </a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Apellido</th>
                        <th>Nombre</th>
                        <th>N° Cuenta</th>
                        <th>Importe Compras</th>
                        <th>Importe Cajero</th>
                        <th>DNI</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($filasPreview)): ?>
                    <?php foreach ($filasPreview as $f): ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $f['num']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($f['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($f['cuenta'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td style="font-weight: 600; color: #059669;">$ <?php echo number_format($f['compra'], 2, ',', '.'); ?></td>
                            <td style="font-weight: 600; color: #2563eb;">$ <?php echo number_format($f['cajero'], 2, ',', '.'); ?></td>
                            <td><code><?php echo htmlspecialchars($f['dni'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><?php echo htmlspecialchars($f['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f['hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 32px; color: var(--text-muted);">
                            No hay registros para mostrar.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<a href="javascript:history.back()" class="btn-modern-flotante" title="Volver atrás">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Volver
</a>

</body>
</html>