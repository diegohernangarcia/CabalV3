<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Procesamiento de Devolución de Altas Cabal (MovAltas.txt) hacia tabla beneficiario
 */

require_once("BD/BD.php");
require_once("BD/Funciones.php");
require_once("BD/UsarGestion.php");

$queArchivo = "archivos/MovAltas.txt";
$archivo_existe = file_exists($queArchivo);
$error_duplicado = '';
$datos = array();
$Beneficiario = array();

if ($archivo_existe) {
    $handle = fopen($queArchivo, "r");
    if ($handle) {
        $i = 0;
        while (($line = fgets($handle)) !== false) {
            $datos[$i] = $line;
            $i++;
        }
        fclose($handle);
    }

    $Beneficiario[0][1] = "Apellido";
    $Beneficiario[0][2] = "Nombre";
    $Beneficiario[0][3] = "Calle";
    $Beneficiario[0][4] = "Número";
    $Beneficiario[0][5] = "Piso";
    $Beneficiario[0][6] = "Depto";
    $Beneficiario[0][7] = "Localidad";
    $Beneficiario[0][8] = "Provincia";
    $Beneficiario[0][9] = "Sexo";
    $Beneficiario[0][10] = "Entidad Emisora";
    $Beneficiario[0][11] = "Fecha Proceso";
    $Beneficiario[0][12] = "Número Cuenta";
    $Beneficiario[0][13] = "Número Tarjeta";
    $Beneficiario[0][14] = "Código Postal";
    $Beneficiario[0][15] = "Tel. Caract.";
    $Beneficiario[0][16] = "Tel. Número";
    $Beneficiario[0][17] = "Estado Civil";
    $Beneficiario[0][18] = "Fecha Nacimiento";
    $Beneficiario[0][19] = "Tipo Doc";
    $Beneficiario[0][20] = "Número Doc";
    $Beneficiario[0][21] = "Pcia Doc";
    $Beneficiario[0][22] = "CUIT";
    $Beneficiario[0][23] = "Cód. Ingreso";
    $Beneficiario[0][24] = "Cód. Rechazo";

    $indice = 1;
    $R1 = array();
    $R2 = array();
    $R3 = array();

    for ($x = 0; $x < sizeof($datos); $x++) {
        $unregistro = $datos[$x];
        $Operacion = substr($unregistro, 22, 2);

        if ($Operacion == 1) {
            $R1[1]  = substr($unregistro, 6, 16);
            $R1[2]  = substr($unregistro, 22, 2);
            $R1[3]  = substr($unregistro, 24, 6);
            $R1[4]  = substr($unregistro, 30, 25);
            $R1[5]  = substr($unregistro, 55, 25);
            $R1[6]  = substr($unregistro, 80, 5);
            $R1[7]  = substr($unregistro, 85, 2);
            $R1[8]  = substr($unregistro, 87, 2);
            $R1[9]  = substr($unregistro, 89, 30);
            $R1[10] = substr($unregistro, 119, 2);
            $R1[11] = substr($unregistro, 121, 3);
            $R1[12] = substr($unregistro, 124, 1);
            $R1[13] = substr($unregistro, 125, 1);
            $R1[14] = substr($unregistro, 126, 2);
            $R1[15] = substr($unregistro, 128, 4);
            $R1[16] = substr($unregistro, 132, 1);
            $R1[17] = substr($unregistro, 133, 4);
            $R1[18] = substr($unregistro, 137, 6);
            $R1[19] = substr($unregistro, 143, 10);
            $R1[20] = substr($unregistro, 153, 5);
        }

        if ($Operacion == 2) {
            $R2[1]  = substr($unregistro, 6, 16);
            $R2[2]  = substr($unregistro, 22, 2);
            $R2[3]  = substr($unregistro, 24, 8);
            $R2[4]  = substr($unregistro, 32, 5);
            $R2[5]  = substr($unregistro, 37, 8);
            $R2[6]  = substr($unregistro, 45, 1);
            $R2[7]  = substr($unregistro, 46, 8);
            $R2[8]  = substr($unregistro, 54, 2);
            $R2[9]  = substr($unregistro, 56, 11);
            $R2[10] = substr($unregistro, 67, 2);
            $R2[11] = substr($unregistro, 69, 11);
            $R2[12] = substr($unregistro, 80, 13);
            $R2[13] = substr($unregistro, 93, 1);
            $R2[14] = substr($unregistro, 94, 6);
            $R2[15] = substr($unregistro, 100, 25);
            $R2[16] = substr($unregistro, 125, 1);
            $R2[17] = substr($unregistro, 126, 2);
            $R2[18] = substr($unregistro, 128, 5);

            $DNIaConsultar = $R2[9];
            $DNIaConsultar = strval($DNIaConsultar);
            $DNIaConsultar = ltrim($DNIaConsultar, "0");
            $NombreBeneficiario = $R1[4];

            $esta = EstaDNI($DNIaConsultar);
            if ($esta == true) {
                $error_duplicado = "El Beneficiario " . $NombreBeneficiario . " con DNI " . $DNIaConsultar . " YA SE ENCUENTRA CARGADO en la base de datos.";
                break;
            }
        }

        if ($Operacion == 3) {
            $R3[1] = substr($unregistro, 6, 16);
            $R3[2] = substr($unregistro, 22, 2);
        }

        if (sizeof($R1) != 0) {
            $LargoNombre = strlen($R1[4]);
            $pos = strpos($R1[4], "/");
            $Apellido = substr($R1[4], 0, $pos);
            $Nombre = substr($R1[4], $pos + 1, $LargoNombre - $pos);
            $Beneficiario[$indice][1]  = $Apellido;
            $Beneficiario[$indice][2]  = $Nombre;
            $Beneficiario[$indice][3]  = $R1[5];
            $Beneficiario[$indice][4]  = $R1[6];
            $Beneficiario[$indice][5]  = $R1[7];
            $Beneficiario[$indice][6]  = $R1[8];
            $Beneficiario[$indice][7]  = $R1[9];
            $Beneficiario[$indice][8]  = $R1[10];
            $Beneficiario[$indice][9]  = $R1[12];
            $Beneficiario[$indice][10] = $R1[17];
            $Beneficiario[$indice][11] = $R1[18];
            $Beneficiario[$indice][12] = $R1[19];
            $Beneficiario[$indice][13] = $R1[1];
        }

        if (sizeof($R2) != 0) {
            $Beneficiario[$indice][14] = $R2[3];
            $Beneficiario[$indice][15] = $R2[4];
            $Beneficiario[$indice][16] = $R2[5];
            $Beneficiario[$indice][17] = $R2[6];
            $Beneficiario[$indice][18] = $R2[7];
            $Beneficiario[$indice][19] = $R2[8];
            $Beneficiario[$indice][20] = $R2[9];
            $Beneficiario[$indice][21] = $R2[10];
            $Beneficiario[$indice][22] = $R2[11];
            $Beneficiario[$indice][23] = $R2[16];
            $Beneficiario[$indice][24] = $R2[17];
        }

        if ($Operacion == 3) {
            $indice++;
        }
    }

    $guardados_ok = 0;
    if (empty($error_duplicado) && sizeof($Beneficiario) > 1) {
        $my_Insert_Statement = $conn->prepare("INSERT INTO beneficiario (Apellido,Nombre,Calle,Numero,Piso,Departamento,Localidad,Provincia,Sexo,EntidadEmisora,FechaProceso,NumeroCuenta,NumeroTarjeta,CodigoPostal,TelefonoCaracteristica,TelefonoNumero,EstadoCivil,FechaNacimiento,TipoDocumento,NumeroDocumento,ProvinciaDocumento,Cuit,CodigoIngreso,CodigoRechazo) VALUES (:Apellido,:Nombre,:Calle,:Numero,:Piso,:Departamento,:Localidad,:Provincia,:Sexo,:EntidadEmisora,:FechaProceso,:NumeroCuenta,:NumeroTarjeta,:CodigoPostal,:TelefonoCaracteristica,:TelefonoNumero,:EstadoCivil,:FechaNacimiento,:TipoDocumento,:NumeroDocumento,:ProvinciaDocumento,:Cuit,:CodigoIngreso,:CodigoRechazo)");

        for ($reg = 1; $reg <= sizeof($Beneficiario) - 1; $reg++) {
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
                $guardados_ok++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolución Altas Cabal | Sistema Cabal FSR</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/theme.js"></script>
    <style>
        .page-container {
            max-width: 1440px;
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

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Ver Padrón
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
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <polyline points="17 11 19 13 23 9"></polyline>
                </svg>
                Devolución de Altas Cabal (MovAltas.txt)
            </h2>
            <p>
                Procesamiento de respuestas bancarias con números de cuenta y tarjetas asignadas hacia el padrón de beneficiarios.
            </p>
        </div>
    </section>

    <?php if (!$archivo_existe): ?>
        <div class="alert-modern danger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <div>
                <strong>Archivo no encontrado:</strong> <code>archivos/MovAltas.txt</code>.<br>
                Súbalo primero desde el <a href="SubirArchivo.html" style="color: inherit; text-decoration: underline;">Centro de Subida de Archivos</a>.
            </div>
        </div>
    <?php elseif (!empty($error_duplicado)): ?>
        <div class="alert-modern warning">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <div>
                <strong>Proceso Interrumpido por Validación:</strong><br>
                <?php echo htmlspecialchars($error_duplicado); ?>
                <div style="margin-top: 12px;">
                    <a href="index.php" class="btn-modern-secondary">Volver al Tablero</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert-modern success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>
                <strong>¡Devolución de Altas procesada con éxito!</strong> Se importaron <strong><?php echo $guardados_ok; ?></strong> beneficiarios a la base de datos con sus cuentas y tarjetas.
            </div>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <div class="modern-card-title-group">
                    <h3>Padrón Importado</h3>
                    <p>Total de titulares procesados: <?php echo (sizeof($Beneficiario) - 1); ?></p>
                </div>
            </div>
            <div class="modern-card-body" style="padding: 0;">
                <div class="modern-table-responsive" style="margin: 0; border: none; max-height: 560px; overflow-y: auto;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Apellido y Nombre</th>
                                <th>Localidad</th>
                                <th>Provincia</th>
                                <th>Número Cuenta</th>
                                <th>Número Tarjeta</th>
                                <th>DNI</th>
                                <th>CUIT</th>
                                <th>Ingreso</th>
                                <th>Rechazo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($x = 1; $x <= sizeof($Beneficiario) - 1; $x++): ?>
                            <tr>
                                <td><code>#<?php echo $x; ?></code></td>
                                <td><strong><?php echo htmlspecialchars(trim($Beneficiario[$x][1]) . " " . trim($Beneficiario[$x][2])); ?></strong></td>
                                <td><?php echo htmlspecialchars(trim($Beneficiario[$x][7])); ?></td>
                                <td><?php echo htmlspecialchars(trim($Beneficiario[$x][8])); ?></td>
                                <td><strong><?php echo htmlspecialchars(trim($Beneficiario[$x][12])); ?></strong></td>
                                <td><code><?php echo htmlspecialchars(formatearTarjetaCredito(trim($Beneficiario[$x][13]))); ?></code></td>
                                <td><?php echo htmlspecialchars(trim($Beneficiario[$x][20])); ?></td>
                                <td><code><?php echo htmlspecialchars(trim($Beneficiario[$x][22])); ?></code></td>
                                <td><span class="badge-tag success"><?php echo htmlspecialchars(trim($Beneficiario[$x][23])); ?></span></td>
                                <td><?php echo htmlspecialchars(trim($Beneficiario[$x][24])); ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
