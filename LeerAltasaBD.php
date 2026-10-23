<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Importación de Planilla Altas a Base de Datos
 * Entidad 782 - Cabal
 */

require_once('phpexcel/PHPExcel.php');
require_once('BD/BD.php');
require_once('BD/Funciones.php');

$archivo = file_exists("archivos/Altas.xlsx") ? "archivos/Altas.xlsx" : "Altas.xlsx";
$archivoExiste = file_exists($archivo);

$num = 0;
$totalRegistros = 0;
$filasPreview = array();
$errorMensaje = "";
$exito = false;

if ($archivoExiste) {
    try {
        $inputFileType = PHPExcel_IOFactory::identify($archivo);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($archivo);
        $sheet = $objPHPExcel->getSheet(0); 
        $highestRow = (int)$sheet->getHighestRow();

        $AltasA = array();

        for ($row = 2; $row <= $highestRow; $row++) {
            $apellido = trim((string)$sheet->getCell("A" . $row)->getValue());
            $nombre   = trim((string)$sheet->getCell("B" . $row)->getValue());
            $dni      = trim((string)$sheet->getCell("P" . $row)->getValue());

            if ($apellido === '' && $nombre === '' && $dni === '') {
                continue;
            }

            $num++;
            $idx = $num - 1;

            $AltasA[$idx][0]  = $apellido;
            $AltasA[$idx][1]  = $nombre;
            $AltasA[$idx][2]  = trim((string)$sheet->getCell("C" . $row)->getValue());
            $AltasA[$idx][3]  = trim((string)$sheet->getCell("D" . $row)->getValue());
            $AltasA[$idx][4]  = trim((string)$sheet->getCell("E" . $row)->getValue());
            $AltasA[$idx][5]  = trim((string)$sheet->getCell("F" . $row)->getValue());
            $AltasA[$idx][6]  = trim((string)$sheet->getCell("G" . $row)->getValue());
            $AltasA[$idx][7]  = trim((string)$sheet->getCell("H" . $row)->getValue());
            $AltasA[$idx][8]  = trim((string)$sheet->getCell("I" . $row)->getValue());
            $AltasA[$idx][9]  = trim((string)$sheet->getCell("J" . $row)->getValue());
            $AltasA[$idx][10] = trim((string)$sheet->getCell("K" . $row)->getValue());
            $AltasA[$idx][11] = trim((string)$sheet->getCell("L" . $row)->getValue());
            $AltasA[$idx][12] = trim((string)$sheet->getCell("M" . $row)->getValue());
            $AltasA[$idx][13] = trim((string)$sheet->getCell("N" . $row)->getValue());
            $AltasA[$idx][14] = trim((string)$sheet->getCell("O" . $row)->getValue());
            $AltasA[$idx][15] = $dni;
            $AltasA[$idx][16] = trim((string)$sheet->getCell("Q" . $row)->getValue());
            $AltasA[$idx][17] = trim((string)$sheet->getCell("R" . $row)->getValue());

            if ($num <= 50) {
                $filasPreview[] = array(
                    'num'       => $num,
                    'apellido'  => $apellido,
                    'nombre'    => $nombre,
                    'domicilio' => $AltasA[$idx][2] . ' ' . $AltasA[$idx][3],
                    'localidad' => $AltasA[$idx][6],
                    'dni'       => $dni,
                    'cuit'      => $AltasA[$idx][17]
                );
            }
        }

        $totalRegistros = count($AltasA);

        if ($totalRegistros > 0) {
            TruncateTable('altas');
            require_once("BD/UsarGestion.php");

            $my_Insert_Statement = $conn->prepare("INSERT INTO altas(Apellido,Nombre,DomicilioCalle,DomicilioNumero,DomicilioPiso,DomicilioDepartamento,Localidad,Provincia,Sexo,CodigoPostal,TelefonoCaracteristica,TelefonoNumero,EstadoCivil,FechaNacimiento,DocumentoTipo,DocumentoNumero,DocumentoProvincia,CUIT) VALUES (:Apellido,:Nombre,:DomicilioCalle,:DomicilioNumero,:DomicilioPiso,:DomicilioDepartamento,:Localidad,:Provincia,:Sexo,:CodigoPostal,:TelefonoCaracteristica,:TelefonoNumero,:EstadoCivil,:FechaNacimiento,:DocumentoTipo,:DocumentoNumero,:DocumentoProvincia,:CUIT)");

            for ($reg = 0; $reg < $totalRegistros; $reg++) {
                $my_Insert_Statement->bindParam(':Apellido', $AltasA[$reg][0]);
                $my_Insert_Statement->bindParam(':Nombre', $AltasA[$reg][1]);
                $my_Insert_Statement->bindParam(':DomicilioCalle', $AltasA[$reg][2]);
                $my_Insert_Statement->bindParam(':DomicilioNumero', $AltasA[$reg][3]);
                $my_Insert_Statement->bindParam(':DomicilioPiso', $AltasA[$reg][4]);
                $my_Insert_Statement->bindParam(':DomicilioDepartamento', $AltasA[$reg][5]);
                $my_Insert_Statement->bindParam(':Localidad', $AltasA[$reg][6]);
                $my_Insert_Statement->bindParam(':Provincia', $AltasA[$reg][7]);
                $my_Insert_Statement->bindParam(':Sexo', $AltasA[$reg][8]);
                $my_Insert_Statement->bindParam(':CodigoPostal', $AltasA[$reg][9]);
                $my_Insert_Statement->bindParam(':TelefonoCaracteristica', $AltasA[$reg][10]);
                $my_Insert_Statement->bindParam(':TelefonoNumero', $AltasA[$reg][11]);
                $my_Insert_Statement->bindParam(':EstadoCivil', $AltasA[$reg][12]);
                $my_Insert_Statement->bindParam(':FechaNacimiento', $AltasA[$reg][13]);
                $my_Insert_Statement->bindParam(':DocumentoTipo', $AltasA[$reg][14]);
                $my_Insert_Statement->bindParam(':DocumentoNumero', $AltasA[$reg][15]);
                $my_Insert_Statement->bindParam(':DocumentoProvincia', $AltasA[$reg][16]);
                $my_Insert_Statement->bindParam(':CUIT', $AltasA[$reg][17]);
                $my_Insert_Statement->execute();
            }
            $exito = true;
        } else {
            $errorMensaje = "El archivo se abrió pero no contenía datos procesables.";
        }
    } catch (Exception $e) {
        $errorMensaje = "Error al procesar el archivo Excel: " . $e->getMessage();
    }
} else {
    $errorMensaje = "No se encontró el archivo 'Altas.xlsx' en el servidor.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Altas a BD — Cabal FSR</title>
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
            <a href="CargaAltasDeExcelaBD.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Módulo Avanzado Altas
            </a>
            <a href="GenerarNovedadAltas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Generar ALTAUSUCBPRE
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1200px; padding-top: 24px;">

    <?php if ($exito): ?>
    <div class="alert-modern success" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">
            ✅ Planilla de Altas Importada Exitosamente
        </div>
        <div>
            Se procesaron y guardaron <strong><?php echo number_format($totalRegistros, 0, ',', '.'); ?></strong> titulares en la tabla <code>altas</code>.
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
                Total Titulares Leídos
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                <?php echo number_format($totalRegistros, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Estado Tabla Altas
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                <?php echo $exito ? 'Actualizada' : 'Sin Cambios'; ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Archivo Procesado
            </div>
            <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); word-break: break-all;">
                <code><?php echo htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8'); ?></code>
            </div>
        </div>
    </div>

    <!-- TABLA DE VISTA PREVIA -->
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 18px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                    Titulares Registrados en la Tabla de Altas
                </h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Vista previa de los primeros registros importados
                </p>
            </div>
            <div>
                <a href="GenerarNovedadAltas.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; border-radius: var(--radius-sm);">
                    Generar Lote ALTAUSUCBPRE.782
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
                        <th>Domicilio</th>
                        <th>Localidad</th>
                        <th>DNI</th>
                        <th>CUIT</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($filasPreview)): ?>
                    <?php foreach ($filasPreview as $f): ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $f['num']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($f['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f['domicilio'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f['localidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($f['dni'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><code><?php echo htmlspecialchars($f['cuit'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">
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
