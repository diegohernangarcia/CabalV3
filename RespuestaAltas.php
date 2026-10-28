<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Devolución y Respuesta de Altas Cabal (782.txt)
 * Entidad 782 - Cabal
 */

require_once("BD/Funciones.php");
require_once("BD/BD.php");

$queArchivo = file_exists("archivos/782.txt") ? "archivos/782.txt" : (file_exists("782.txt") ? "782.txt" : "");
$archivoExiste = !empty($queArchivo) && file_exists($queArchivo);

$Beneficiario = array();
$totalRegistros = 0;
$grabados = 0;
$errores = 0;
$errorMensaje = "";

if ($archivoExiste) {
    $datos = file($queArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($datos !== false) {
        $indice = 1;
        $Beneficiario[0][1]  = "Apellido";
        $Beneficiario[0][2]  = "Nombre";
        $Beneficiario[0][3]  = "Calle";
        $Beneficiario[0][4]  = "Numero";
        $Beneficiario[0][5]  = "Piso";
        $Beneficiario[0][6]  = "Departamento";
        $Beneficiario[0][7]  = "Localidad";
        $Beneficiario[0][8]  = "Provincia";
        $Beneficiario[0][9]  = "Sexo";
        $Beneficiario[0][10] = "Entidad Emisora";
        $Beneficiario[0][11] = "Fecha Proceso";
        $Beneficiario[0][12] = "Numero Cuenta";
        $Beneficiario[0][13] = "Numero Tarjeta";
        $Beneficiario[0][14] = "Codigo Postal";
        $Beneficiario[0][15] = "Telefono Caracteristica";
        $Beneficiario[0][16] = "Telefono Numero";
        $Beneficiario[0][17] = "Estado Civil";
        $Beneficiario[0][18] = "Fecha Nacimiento";
        $Beneficiario[0][19] = "Tipo Documento";
        $Beneficiario[0][20] = "Numero Documento";
        $Beneficiario[0][21] = "Provincia Documento";
        $Beneficiario[0][22] = "CUIT";
        $Beneficiario[0][23] = "Codigo Ingreso";
        $Beneficiario[0][24] = "Codigo Rechazo";

        foreach ($datos as $valor) {
            $Operacion = substr($valor, 20, 2);
            $R1 = array();
            $R2 = array();

            if ($Operacion == "01") {
                $R1[1]  = trim(substr($valor, 26, 16));  // Apellido
                $R1[2]  = trim(substr($valor, 42, 9));   // Nombre
                $R1[3]  = trim(substr($valor, 51, 25));  // Calle
                $R1[4]  = trim(substr($valor, 76, 5));   // Numero
                $R1[5]  = trim(substr($valor, 81, 2));   // Piso
                $R1[6]  = trim(substr($valor, 83, 2));   // Depto
                $R1[7]  = trim(substr($valor, 85, 30));  // Localidad
                $R1[8]  = trim(substr($valor, 115, 2));  // Provincia
                $R1[9]  = trim(substr($valor, 120, 1));  // Sexo
                $R1[10] = trim(substr($valor, 20, 6));   // Entidad
                $R1[11] = trim(substr($valor, 6, 8));    // Fecha Proceso
                $R1[12] = trim(substr($valor, 0, 16));   // Cuenta
                $R1[13] = trim(substr($valor, 0, 16));   // Tarjeta

                $Beneficiario[$indice][1]  = $R1[1];
                $Beneficiario[$indice][2]  = $R1[2];
                $Beneficiario[$indice][3]  = $R1[3];
                $Beneficiario[$indice][4]  = $R1[4];
                $Beneficiario[$indice][5]  = $R1[5];
                $Beneficiario[$indice][6]  = $R1[6];
                $Beneficiario[$indice][7]  = $R1[7];
                $Beneficiario[$indice][8]  = $R1[8];
                $Beneficiario[$indice][9]  = $R1[9];
                $Beneficiario[$indice][10] = $R1[10];
                $Beneficiario[$indice][11] = $R1[11];
                $Beneficiario[$indice][12] = $R1[12];
                $Beneficiario[$indice][13] = $R1[13];
            }

            if ($Operacion == "02") {
                $R2[1]  = trim(substr($valor, 22, 8));   // CP
                $R2[2]  = trim(substr($valor, 30, 5));   // Tel Carac
                $R2[3]  = trim(substr($valor, 35, 8));   // Tel Num
                $R2[4]  = trim(substr($valor, 43, 1));   // Est Civil
                $R2[5]  = trim(substr($valor, 44, 8));   // Fec Nac
                $R2[8]  = trim(substr($valor, 52, 2));   // Tipo Doc
                $R2[9]  = trim(substr($valor, 54, 11));  // Num Doc
                $R2[10] = trim(substr($valor, 65, 2));   // Prov Doc
                $R2[11] = trim(substr($valor, 67, 11));  // CUIT
                $R2[16] = trim(substr($valor, 102, 2));  // Cod Ingreso
                $R2[17] = trim(substr($valor, 104, 2));  // Cod Rechazo

                $Beneficiario[$indice][14] = $R2[1];
                $Beneficiario[$indice][15] = $R2[2];
                $Beneficiario[$indice][16] = $R2[3];
                $Beneficiario[$indice][17] = $R2[4];
                $Beneficiario[$indice][18] = $R2[5];
                $Beneficiario[$indice][19] = $R2[8];
                $Beneficiario[$indice][20] = $R2[9];
                $Beneficiario[$indice][21] = $R2[10];
                $Beneficiario[$indice][22] = $R2[11];
                $Beneficiario[$indice][23] = $R2[16];
                $Beneficiario[$indice][24] = $R2[17];
            }

            if ($Operacion == "03" || $Operacion == 3) {
                $indice++;
            }
        }

        $totalRegistros = count($Beneficiario) - 1;

        if ($totalRegistros > 0) {
            require_once("BD/UsarGestion.php");
            $my_Insert_Statement = $conn->prepare("INSERT INTO beneficiario (Apellido,Nombre,Calle,Numero,Piso,Departamento,Localidad,Provincia,Sexo,EntidadEmisora,FechaProceso,NumeroCuenta,NumeroTarjeta,CodigoPostal,TelefonoCaracteristica,TelefonoNumero,EstadoCivil,FechaNacimiento,TipoDocumento,NumeroDocumento,ProvinciaDocumento,Cuit,CodigoIngreso,CodigoRechazo) VALUES (:Apellido,:Nombre,:Calle,:Numero,:Piso,:Departamento,:Localidad,:Provincia,:Sexo,:EntidadEmisora,:FechaProceso,:NumeroCuenta,:NumeroTarjeta,:CodigoPostal,:TelefonoCaracteristica,:TelefonoNumero,:EstadoCivil,:FechaNacimiento,:TipoDocumento,:NumeroDocumento,:ProvinciaDocumento,:Cuit,:CodigoIngreso,:CodigoRechazo)");

            for ($reg = 1; $reg <= $totalRegistros; $reg++) {
                $my_Insert_Statement->bindParam(':Apellido', $Beneficiario[$reg][1]);
                $my_Insert_Statement->bindParam(':Nombre', $Beneficiario[$reg][2]);
                $my_Insert_Statement->bindParam(':Calle', $Beneficiario[$reg][3]);
                $my_Insert_Statement->bindParam(':Numero', $Beneficiario[$reg][4]);
                $my_Insert_Statement->bindParam(':Piso', $Beneficiario[$reg][5]);
                $my_Insert_Statement->bindParam(':Departamento', $Beneficiario[$reg][6]);
                $my_Insert_Statement->bindParam(':Localidad', $Beneficiario[$reg][7]);
                $my_Insert_Statement->bindParam(':Provincia', $Beneficiario[$reg][8]);
                $my_Insert_Statement->bindParam(':Sexo', $Beneficiario[$reg][9]);
                $my_Insert_Statement->bindParam(':EntidadEmisora', $Beneficiario[$reg][10]);
                $my_Insert_Statement->bindParam(':FechaProceso', $Beneficiario[$reg][11]);
                $my_Insert_Statement->bindParam(':NumeroCuenta', $Beneficiario[$reg][12]);
                $my_Insert_Statement->bindParam(':NumeroTarjeta', $Beneficiario[$reg][13]);
                $my_Insert_Statement->bindParam(':CodigoPostal', $Beneficiario[$reg][14]);
                $my_Insert_Statement->bindParam(':TelefonoCaracteristica', $Beneficiario[$reg][15]);
                $my_Insert_Statement->bindParam(':TelefonoNumero', $Beneficiario[$reg][16]);
                $my_Insert_Statement->bindParam(':EstadoCivil', $Beneficiario[$reg][17]);
                $my_Insert_Statement->bindParam(':FechaNacimiento', $Beneficiario[$reg][18]);
                $my_Insert_Statement->bindParam(':TipoDocumento', $Beneficiario[$reg][19]);
                $my_Insert_Statement->bindParam(':NumeroDocumento', $Beneficiario[$reg][20]);
                $my_Insert_Statement->bindParam(':ProvinciaDocumento', $Beneficiario[$reg][21]);
                $my_Insert_Statement->bindParam(':Cuit', $Beneficiario[$reg][22]);
                $my_Insert_Statement->bindParam(':CodigoIngreso', $Beneficiario[$reg][23]);
                $my_Insert_Statement->bindParam(':CodigoRechazo', $Beneficiario[$reg][24]);
                if ($my_Insert_Statement->execute()) {
                    $grabados++;
                } else {
                    $errores++;
                }
            }
        }
    }
} else {
    $errorMensaje = "No se encontró el archivo de devolución <code>782.txt</code> ni en raíz ni en <code>archivos/</code>.";
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
            <a href="ProcesaNovedadesAltas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Procesar MovAltas.txt
            </a>
            <a href="ReporteAltaBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Padrón Beneficiarios
            </a>
            <button type="button" class="btn-nav-action" onclick="toggleCatalogoErrores()" id="btn-toggle-catalogo">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                Ver Catálogo Errores Anexo 12
            </button>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1300px; padding-top: 24px;">

    <!-- PANEL EXPANDIBLE CON EL DICCIONARIO OFICIAL DE ERRORES DE ALTAS (ANEXO 12) -->
    <div id="panelCatalogoErrores" class="modern-card" style="display: none; margin-bottom: 24px; border: 2px solid var(--primary-600); background: var(--surface-card);">
        <div class="modern-card-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-light); background: rgba(37, 99, 235, 0.05); display: flex; justify-content: space-between; align-items: center;">
            <div class="modern-card-title-group">
                <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--navy-800);">
                    📖 Catálogo Oficial de Errores de Altas — Anexo 12 (docs/Cabal.md)
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

    <?php if ($archivoExiste && $grabados > 0): ?>
    <div class="alert-modern success" style="margin-bottom: 24px;">
        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">
            ✅ Lote de Devolución Procesado Exitosamente
        </div>
        <div>
            Se importaron <strong><?php echo number_format($grabados, 0, ',', '.'); ?></strong> registros de beneficiarios en la tabla <code>beneficiario</code>.
        </div>
    </div>
    <?php elseif (!empty($errorMensaje)): ?>
    <div class="alert-modern danger" style="margin-bottom: 24px;">
        <strong>Atención:</strong> <?php echo $errorMensaje; ?>
    </div>
    <?php endif; ?>

    <!-- TARJETAS DE MÉTRICAS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Total Titulares en Devolución
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--navy-800);">
                <?php echo number_format($totalRegistros, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Guardados en Padrón
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #059669;">
                <?php echo number_format($grabados, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="modern-card" style="padding: 20px;">
            <div style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">
                Registros con Error
            </div>
            <div style="font-size: 1.8rem; font-weight: 800; color: <?php echo $errores > 0 ? '#dc2626' : '#059669'; ?>;">
                <?php echo number_format($errores, 0, ',', '.'); ?>
            </div>
        </div>
    </div>

    <!-- TABLA DE RESULTADOS -->
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 18px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                Titulares y Cuentas Asignadas por Cabal
            </h3>
            <a href="ReporteAltaBeneficiarios.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; border-radius: var(--radius-sm);">
                Ver Padrón Completo
            </a>
        </div>

        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Apellido y Nombre</th>
                        <th>N° Documento</th>
                        <th>CUIT</th>
                        <th>N° Cuenta</th>
                        <th>N° Tarjeta</th>
                        <th>Localidad</th>
                        <th>Cod. Ingreso</th>
                        <th>Respuesta / Motivo de Rechazo (Anexo 12)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($totalRegistros > 0): ?>
                    <?php for ($x = 1; $x <= $totalRegistros; $x++): ?>
                        <?php 
                            $tarjetaMask = formatearTarjetaCredito($Beneficiario[$x][13]); 
                            $rawRechazo = trim($Beneficiario[$x][24]);
                            $ingreso = trim($Beneficiario[$x][23]);
                            $errList = parsearCodigosRechazoAltas($rawRechazo);
                        ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $x; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($Beneficiario[$x][1] . ' ' . $Beneficiario[$x][2], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($Beneficiario[$x][20], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($Beneficiario[$x][22], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><code><?php echo htmlspecialchars($Beneficiario[$x][12], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><code><?php echo htmlspecialchars($tarjetaMask, ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><?php echo htmlspecialchars($Beneficiario[$x][7], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($ingreso === '0'): ?>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #059669;">0 (Aceptada)</span>
                                <?php elseif ($ingreso === '1'): ?>
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #dc2626;">1 (Rechazada)</span>
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars($ingreso, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ingreso === '0' && empty($errList)): ?>
                                    <span style="font-size: 0.78rem; padding: 3px 8px; border-radius: var(--radius-full); background: rgba(16, 185, 129, 0.15); color: #059669; font-weight: 700;">✓ Aprobada</span>
                                <?php else: ?>
                                    <span style="font-size: 0.78rem; padding: 3px 8px; border-radius: var(--radius-full); background: rgba(220, 38, 38, 0.15); color: #dc2626; font-weight: 700;">✕ Rechazada</span>
                                    <?php if (!empty($errList)): ?>
                                        <div style="margin-top: 5px; display: flex; flex-direction: column; gap: 3px;">
                                            <?php foreach ($errList as $errItem): ?>
                                                <div style="font-size: 0.72rem; background: rgba(220, 38, 38, 0.08); border-left: 2px solid #dc2626; padding: 3px 6px; border-radius: 2px;" title="<?php echo htmlspecialchars($errItem['desc'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <strong style="color: #b91c1c;">Cód. <?php echo htmlspecialchars($errItem['codigo'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
                                                    <span><?php echo htmlspecialchars($errItem['titulo'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($rawRechazo !== ''): ?>
                                        <div style="font-size: 0.74rem; color: #dc2626; margin-top: 2px;">
                                            <?php echo htmlspecialchars($rawRechazo, ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 32px; color: var(--text-muted);">
                            No hay datos para mostrar en este lote de devolución.
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
<script>
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

