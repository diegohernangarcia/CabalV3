<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal - Familia Rionegrina Solidaria</title>
    <link rel="stylesheet" href="css/estilos-menu.css">
    <!-- <link rel="stylesheet" type="text/css" href="css/estilos2.css" /> -->
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/estilos-menu.css">
    <script src="js/theme.js"></script>
</head>
<body>
    
<nav role="navigation" class="primary-navigation" >
    <ul>
    <li><a href="index.php" style="color: var(--primary-500); font-weight: bold;">&larr; Tablero Principal</a></li>
    <li><a href="#">Beneficiarios &dtrif;</a>
        <ul class="dropdown">
            <li><a href="SubirArchivo.html">Cargar Archivo de Altas</a></li>
            <li><a href="CargaAltasDeExcelaBD.php">Importar Excel Con Altas y Generar ALTASUCBPRE</a></li>
            <li><a href="GenerarNovedadAltas.php">Generar ALTAUSUCBPRE</a></li>
            <li><a href="ValidarAltas.php">Verificar y Validar ALTASUCBPRE</a></li>
            <li><a href="ProcesaNovedadesAltas.php">Validar respuesta de Altas</a></li>
            <li><a href="RespuestaAltas.php">Devolución de Altas Cabal</a></li>
            <li><a href="ConsultarBeneficiario.php">Consultar Beneficiario por DNI</a></li>
            <li><a href="crud/index.php">Gestión de Beneficiarios (CRUD)</a></li>
            <li><a href="ReporteAltaBeneficiarios.php">Reporte de Beneficiarios General</a></li>
            <li><a href="ExportarExcelBeneficiarios.php">Exportar Beneficiarios</a></li>
          </ul>
    </li>
    <li><a href="#">Acreditaciones &dtrif;</a>
      <ul class="dropdown">
        <li><a href="SubirArchivo.html">Cargar Archivo de Acreditaciones</a></li>
        <li><a href="AcreditarSaldos-2023.php">Procesar Excel 2023(Acreditaciones.xlsx)</a></li>
        <!-- <li><a href="AcreditarSaldos2026.php">Procesar Excel 2026</a></li> -->
        <li><a href="GenerarCargaCredito2026.php">Generar NOVCAR2026</a></li>
        <li><a href="GenerarCargaCredito2.php">Generar NOVCAR</a></li>
        <li><a href="subir_novcar.php">Cargar Archivo NOVCAR</a></li>
        <li><a href="VerificarNovcar.php">Verificar NOVCAR</a></li>
        <li><a href="SubirArchivo.html">Subir Respuesta Cargas (Dev. Banco)</a></li>
        <li><a href="ProcesarCarga.php">Verificar Respuesta Carga</a></li>
        
        
      </ul>
    </li>
    <li><a href="#">Descarga Saldos &dtrif;</a>
      <ul class="dropdown">
        <li><a href="ProcesarDescargaSaldos.php">Procesar DescargaSaldos.xlsx</a></li>
       
      </ul>
    </li>
    <li><a href="#">Movimientos &dtrif;</a>
        <ul class="dropdown">
          <li><a href="ProcesarMovimientos.html">Procesar Movimientos.txt</a></li>
          <!-- <li><a href="ReporteMovimientos.php">Reporte Movimientos <br>Mensual</a></li>
          <li><a href="ReporteMovimientosPorNombre.php">Reporte Movimientos Por Beneficiario</a></li> -->
          <!-- <li><a href="#">Iconography</a></li> -->
        </ul>
      </li>
      <li><a href="#">Maestros Tarjetas &dtrif;</a>
        <ul class="dropdown">
          <li><a href="ProcesarMaestroPlasticosArchivo.php">Procesar Maestro Tarjetas</a></li>
          <!-- <li><a href="ReporteMovimientos.php">Reporte Movimientos <br>Mensual</a></li>
          <li><a href="ReporteMovimientosPorNombre.php">Reporte Movimientos Por Beneficiario</a></li> -->
          <!-- <li><a href="#">Iconography</a></li> -->
        </ul>
      </li>
      <li><a href="#">Reportes &dtrif;</a>
        <ul class="dropdown">
        <li><a href="ConsultarBeneficiario.php">Consultar Beneficiario <br>Por DNI</a></li>
          <li><a href="ReporteAltaBeneficiarios.php">Reporte Beneficiarios</a></li>
          <li><a href="ReporteMovimientos.php">Reporte Movimientos <br>Mensual</a></li>
          <li><a href="ReporteMovimientosPorNombre.php">Reporte Movimientos Por Beneficiario</a></li>
          <li><a href="reporte_tarjetas.php">Reporte Master Tarjetas</a></li>
          

        </ul>
      </li>
    <li><a href="SubirArchivo.html">Archivos</a></li>
    
    <li><a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank">Cabal</a></li>
    <li><a href="index_old.php">Sistema Anterior</a></li>
    <li><a href="ayuda.html" target="_blank">Ayuda</a></li>
  </ul>
</nav>
<div class="menu-fondo-wrap">
<img src="img/Cabal_fondo2.jpg" class="fondo" alt="Fondo Cabal">
</div>
</body>
</html>