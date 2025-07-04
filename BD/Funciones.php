<?php
$usuario = 'pucho';
$pass = '78900';

function formatearTarjetaCredito($numeroTarjeta)
{
    // Asegurarse de que la longitud del número de tarjeta sea de 16 dígitos
    if (strlen($numeroTarjeta) !== 16) {
        return "Número de tarjeta inválido";
    }

    // Extraer las diferentes partes del número de tarjeta
    $parte1 = substr($numeroTarjeta, 0, 4);
    $parte2 = substr($numeroTarjeta, 4, 2);
    $parte3 = "****"; // Reemplaza con asteriscos
    $parte4 = substr($numeroTarjeta, 12, 4);

    // Formatear el número de tarjeta en el formato deseado
    $tarjetaFormateada = $parte1 . '.' . $parte2 . '**.' . $parte3 . '.' . $parte4;

    return $tarjetaFormateada;
}

function StringTodoIguales($cualRepetir, &$texto, $cantidad)
{
    for ($contador = 1; $contador <= $cantidad; $contador++) {
        $texto = $texto . $cualRepetir;
    }
    return $texto;
}
function AgregarEspacios(&$texto)
{
    $texto = $texto . "&nbsp;";
    //return $texto;
}
function AgregarEspacios2(&$texto)
{
    $texto = $texto . " ";
    //return $texto;
}
function AgregarCeros(&$texto)
{
    $texto = $texto . "0";
    //return $texto;
}
function CompletarMontoConCeros($numero, $cuantos)
{
    //DEVUELVE UN NUMERO CON O SIN DECIMALES, QUE EL LARGO TOTAL SEA DE $cuantos y COMPLETANDO A IZQUIERA CON CEROS
    $Largo = strlen($numero);
    $pos = strpos($numero, '.');
    //
    if ($pos) {
        $centavos = substr($numero, $pos + 1, 2);
        if ($centavos == "5") {
            $centavos = "50";
        }
        $Entero = substr($numero, 0, $pos);
    } else {
        $Entero = $numero;
        $centavos = "00";
    }

    // echo "NUMERO ORIGINAL=".$numero."<br>";
    // echo "ENTERO --> ".$Entero." Decimal --> ".$centavos."<br>";
    $Entero = str_pad($Entero, $cuantos - 2, 0, STR_PAD_LEFT);
    // echo "NUMERO FORMATEADO -->".$Entero.$centavos;
    $devolver = $Entero . $centavos;

    return $devolver;
}
function LargoString($String)
{
    return strlen($String);
}
function CompletarCaracteristica(&$Caracteristica)
{
    $largo = strlen($Caracteristica);
    switch ($largo) {
        case 0:
            $Caracteristica = "00000";
            break;
        case 1:
            $Caracteristica = "0000" . $Caracteristica;
            break;
        case 2:
            $Caracteristica = "000" . $Caracteristica;
            break;
        case 3:
            $Caracteristica = "00" . $Caracteristica;
            break;
        case 4:
            $Caracteristica = "0" . $Caracteristica;
            break;
    }
}
function CompletarNumeroTelefono(&$NumeroTelefono)
{
    $largo = strlen($NumeroTelefono);
    switch ($largo) {
        case 0:
            $NumeroTelefono = "00000000";
            break;
        case 1:
            $NumeroTelefono = "0000000" . $NumeroTelefono;
            break;
        case 2:
            $NumeroTelefono = "000000" . $NumeroTelefono;
            break;
        case 3:
            $NumeroTelefono = "00000" . $NumeroTelefono;
            break;
        case 4:
            $NumeroTelefono = "0000" . $NumeroTelefono;
            break;
        case 5:
            $NumeroTelefono = "000" . $NumeroTelefono;
            break;
        case 6:
            $NumeroTelefono = "00" . $NumeroTelefono;
            break;
        case 7:
            $NumeroTelefono = "0" . $NumeroTelefono;
            break;
    }
}
function CompletarFechaNacimiento(&$NumeroTelefono)
{
    $largo = strlen($NumeroTelefono);
    switch ($largo) {
        case 0:
            $NumeroTelefono = "00000000";
            break;
        case 1:
            $NumeroTelefono = "0000000" . $NumeroTelefono;
            break;
        case 2:
            $NumeroTelefono = "000000" . $NumeroTelefono;
            break;
        case 3:
            $NumeroTelefono = "00000" . $NumeroTelefono;
            break;
        case 4:
            $NumeroTelefono = "0000" . $NumeroTelefono;
            break;
        case 5:
            $NumeroTelefono = "000" . $NumeroTelefono;
            break;
        case 6:
            $NumeroTelefono = "00" . $NumeroTelefono;
            break;
        case 7:
            $NumeroTelefono = "0" . $NumeroTelefono;
            break;
    }
}
function CompletarTipoDocumento(&$TipoDocumento)
{
    $largo = strlen($TipoDocumento);
    switch ($largo) {
        case 0:
            $TipoDocumento = "00";
            break;
        case 1:
            $TipoDocumento = "0" . $TipoDocumento;
            break;
    }
}
function CompletarDocumentoProvincia(&$DocumentoProvincia)
{
    $largo = strlen($DocumentoProvincia);
    switch ($largo) {
        case 0:
            $DocumentoProvincia = "00";
            break;
        case 1:
            $DocumentoProvincia = "0" . $DocumentoProvincia;
            break;
    }
}
function CompletarNumeroDocumento(&$NumeroDocumento)
{
    $largo = strlen($NumeroDocumento);
    switch ($largo) {
        case 0:
            $NumeroDocumento = "00000000000";
            break;
        case 2:
            $NumeroDocumento = "0000000000" . $NumeroDocumento;
            break;
        case 3:
            $NumeroDocumento = "00000000" . $NumeroDocumento;
            break;
        case 4:
            $NumeroDocumento = "0000000" . $NumeroDocumento;
            break;
        case 5:
            $NumeroDocumento = "000000" . $NumeroDocumento;
            break;
        case 6:
            $NumeroDocumento = "00000" . $NumeroDocumento;
            break;
        case 7:
            $NumeroDocumento = "0000" . $NumeroDocumento;
            break;
        case 8:
            $NumeroDocumento = "000" . $NumeroDocumento;
            break;
        case 9:
            $NumeroDocumento = "00" . $NumeroDocumento;
            break;
        case 10:
            $NumeroDocumento = "00" . $NumeroDocumento;
            break;
    }
}
function CompletarCUIT(&$Cuit)
{
    $largo = strlen($Cuit);
    switch ($largo) {
        case 0:
            $Cuit = "00000000000";
            break;
        case 1:
            $Cuit = "0000000000" . $Cuit;
            break;
        case 2:
            $Cuit = "000000000" . $Cuit;
            break;
        case 3:
            $Cuit = "00000000" . $Cuit;
            break;
        case 4:
            $Cuit = "0000000" . $Cuit;
        case 5:
            $Cuit = "000000" . $Cuit;
            break;
        case 6:
            $Cuit = "00000" . $Cuit;
            break;
        case 7:
            $Cuit = "0000" . $Cuit;
            break;
        case 8:
            $Cuit = "000" . $Cuit;
            break;
        case 9:
            $Cuit = "00" . $Cuit;
            break;
        case 10:
            $Cuit = "0" . $Cuit;
            break;
    }
}
function validar_cuit_cuil($cuit_cuil)
{
    // Eliminar caracteres no numéricos
    $cuit_cuil = preg_replace('/\D/', '', $cuit_cuil);

    // Validar longitud
    if (strlen($cuit_cuil) != 11) {
        return false;
    }

    // Validar número verificador
    $acumulado = 0;
    $coeficientes = array(5, 4, 3, 2, 7, 6, 5, 4, 3, 2);
    for ($i = 0; $i < 10; $i++) {
        $acumulado += $coeficientes[$i] * intval(substr($cuit_cuil, $i, 1));
    }
    $resto = $acumulado % 11;
    $digito_verificador = ($resto == 0) ? 0 : (($resto == 1) ? 9 : (11 - $resto));
    return intval(substr($cuit_cuil, 10, 1)) == $digito_verificador;
}
function formatearNumero($numero)
{
    $numeroFormateado = sprintf("%010.2f", $numero);
    $numeroFormateado = str_replace('.', '', $numeroFormateado);

    return $numeroFormateado;
}
function formatearNumeroSumatoria($numero)
{
    // $numeroFormateado = sprintf("%011.2f", $numero);
    // $numeroFormateado = str_replace('.', '', $numeroFormateado);

    // return $numeroFormateado;
    return $numero;
}
function guardarArrayEnArchivo($array, $rutaArchivo)
{
    // Convierte el array a formato JSON
    $contenido = json_encode($array);

    // Abre el archivo en modo de escritura
    $archivo = fopen($rutaArchivo, 'w');

    if ($archivo) {
        // Escribe el contenido JSON en el archivo
        fwrite($archivo, $contenido);

        // Cierra el archivo
        fclose($archivo);

        return true; // Éxito en la escritura
    } else {
        return false; // No se pudo abrir el archivo
    }
}
function guardarArrayEnArchivoConSaltos($array, $rutaArchivo)
{
    // Convierte el array a formato JSON con saltos de línea
    $contenido = json_encode($array, JSON_PRETTY_PRINT);

    // Abre el archivo en modo de escritura
    $archivo = fopen($rutaArchivo, 'w');

    if ($archivo) {
        // Escribe el contenido JSON en el archivo
        fwrite($archivo, $contenido);

        // Cierra el archivo
        fclose($archivo);

        return true; // Éxito en la escritura
    } else {
        return false; // No se pudo abrir el archivo
    }
}

function escribirArrayEnArchivo($array, $nombreArchivo)
{
    // Abre el archivo en modo de escritura
    $archivo = fopen($nombreArchivo, "w");

    // Recorre el array
    foreach ($array as $valor) {
        // Escribe el valor en el archivo con un salto de línea
        fwrite($archivo, $valor . PHP_EOL);
    }

    // Cierra el archivo
    fclose($archivo);
}
function obtenerSumaImporteCompra()
{
    // Configuración de la conexión a la base de datos
    $servidor = "localhost";
    $usuario = 'pucho';
    $pass = '78900';
   // $usuario = $usuario;
    $contrasena = $pass;
    $basedatos = "cabal";

    // Crear conexión
    $conexion = new mysqli($servidor, $usuario, $contrasena, $basedatos);

    // Verificar conexión
    if ($conexion->connect_error) {
        die("Conexión fallida: " . $conexion->connect_error);
    }

    // Consulta SQL
    $consulta = "SELECT SUM(`ImporteCompra`) AS total FROM acreditar";

    // Ejecutar consulta
    $resultado = $conexion->query($consulta);

    // Verificar si la consulta fue exitosa
    if ($resultado) {
        // Obtener el resultado como arreglo asociativo
        $fila = $resultado->fetch_assoc();

        // Obtener el valor de la suma
        $suma = $fila['total'];

        // Cerrar conexión
        $conexion->close();

        // Devolver la suma obtenida
        return $suma;
    } else {
        // En caso de error en la consulta
        echo "Error al ejecutar la consulta: " . $conexion->error;
        $conexion->close();
        return null;
    }
}

function formatCurrency($number)
{
    // Eliminar los ceros iniciales de la parte entera, manteniendo al menos un dígito
    // return ltrim($input, '0');
    // Eliminar espacios en blanco alrededor del número
    $number = trim($number);

    // Si el número contiene un punto decimal
    if (strpos($number, '.') !== false) {
        // Dividir en parte entera y decimal
        list($integerPart, $decimalPart) = explode('.', $number, 2);

        // Eliminar ceros a la izquierda de la parte entera
        $integerPart = ltrim($integerPart, '0');

        // Si la parte entera queda vacía, reemplazarla por "0"
        if ($integerPart === '') {
            $integerPart = '0';
        }

        // Reconstruir el número con la parte decimal
        return $integerPart . '.' . $decimalPart;
    } else {
        // Eliminar ceros a la izquierda de un número entero
        return ltrim($number, '0') ?: '0'; // Retorna '0' si el resultado es vacío
    }
}
function EliminarCerosIzquierda($number)
{
    // Usar ltrim para eliminar ceros a la izquierda
    return ltrim($number, '0');
}
function formatCurrency2($number)
{
    // Eliminar ceros a la izquierda
    $number = removeLeadingZeros($number);

    // Convertir a un número flotante para el formato correcto
    $number = floatval($number);

    // Formatear como moneda con dos decimales, separador de miles y separador decimal
    return '$' . number_format($number, 2, '.', ',');
}

function removeLeadingZeros($number)
{
    // Convertir el número a string si no lo es
    $number = (string) $number;

    // Comprobar si el número contiene un punto decimal
    if (strpos($number, '.') !== false) {
        // Si hay un punto decimal, eliminar ceros a la izquierda de la parte entera
        // Dividir en parte entera y decimal
        list($integerPart, $decimalPart) = explode('.', $number, 2);

        // Eliminar ceros a la izquierda de la parte entera
        $integerPart = ltrim($integerPart, '0');

        // Si la parte entera queda vacía, reemplazarla por "0"
        if ($integerPart === '') {
            $integerPart = '0';
        }

        // Reconstruir el número con la parte decimal
        return $integerPart . '.' . $decimalPart;
    } else {
        // Eliminar ceros a la izquierda de un número entero
        return ltrim($number, '0') ?: '0'; // Retorna '0' si el resultado es vacío
    }
}
function DevueltoTipoDoc($Tipo)
{
    switch ($Tipo) {
        case 1:
            return "LE";
        case 2:
            return "LC";
        case 3:
            return "DNI";
        case 4:
            return "CI";
        case 5:
            return "DNE";
        case 6:
            return "DIN";
        case 7:
            return "CIM";
        case 8:
            return "PER";
        default:
            return "Otro";
    }
}
function convertirFecha($fecha)
{
    // Extraer las partes de la fecha
    $año = substr($fecha, 0, 2);
    $mes = substr($fecha, 2, 2);
    $día = substr($fecha, 4, 2);

    // Formatear la fecha a DD/MM/AA
    $fechaFormateada = $día . '/' . $mes . '/' . $año;

    return $fechaFormateada;
}

function ExportaraExcelMovimientosPorMes($ConsultaSQL)
{

    $usuario = 'pucho';
    $pass = '78900';
    //$meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    $fecha_actual = date("d-m-Y");

    $conexion = new mysqli("localhost", $usuario, $pass, "cabal");
    // Verificar la conexión
    if ($conexion->connect_error) {
        die("Error en la conexión: " . $conexion->connect_error);
    }

    //echo "Esta es la consulta :" . $ConsultaSQL."<br>";
    $resultado = $conexion->query($ConsultaSQL);
    //var_dump($resultado);die();

    if ($resultado->num_rows > 0) {
        // Crear un objeto de la librería PHPExcel
        require_once "./phpexcel/PHPExcel.php";
        $style = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );
        $objPHPExcel = new PHPExcel();
        // Establecer el nombre de la hoja de cálculo
        $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("C")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("D")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("E")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("F")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("G")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("H")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("I")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("J")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("K")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("L")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("M")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("N")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("O")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("P")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("Q")->setAutoSize(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension("R")->setAutoSize(true);
        $objPHPExcel->getDefaultStyle()->applyFromArray($style);



        $objPHPExcel->getActiveSheet()->setTitle('Movimientos');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Id');
        $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Banco');
        $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Cod. Sucursal');
        $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Cuenta');
        $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Tarjeta');
        $objPHPExcel->getActiveSheet()->setCellValue('F1', 'Tipo Documento');
        $objPHPExcel->getActiveSheet()->setCellValue('G1', 'Documento');
        $objPHPExcel->getActiveSheet()->setCellValue('H1', 'Apellido y Nombre');
        $objPHPExcel->getActiveSheet()->setCellValue('I1', 'Operacion');
        $objPHPExcel->getActiveSheet()->setCellValue('J1', 'Fecha');
        $objPHPExcel->getActiveSheet()->setCellValue('K1', 'Comercio');
        $objPHPExcel->getActiveSheet()->setCellValue('L1', 'Importe');
        $objPHPExcel->getActiveSheet()->setCellValue('M1', 'Signo');
        $objPHPExcel->getActiveSheet()->setCellValue('N1', 'Importe2');
        $objPHPExcel->getActiveSheet()->setCellValue('O1', 'Signo2');
        $objPHPExcel->getActiveSheet()->setCellValue('P1', 'Mes');
        $objPHPExcel->getActiveSheet()->setCellValue('Q1', 'Año');
        $objPHPExcel->getActiveSheet()->setCellValue('R1', 'Archivo');
        $fila = 2; // Iniciar desde la fila 2 para los datos
        while ($row = $resultado->fetch_assoc()) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $fila, $row['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $fila, $row['Banco']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $fila, $row['Sucursal']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $fila, $row['Cuenta']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $fila, formatearTarjetaCredito($row['Tarjeta']));
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $fila, $row['TipoDocumento']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $fila, $row['Documento']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $fila, $row['NombreApellido']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $fila, $row['CodigoOperacion']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $fila, $row['Fecha']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $fila, $row['Comercio']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $fila, $row['Importe']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $fila, $row['Signo']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $fila, $row['Importe2']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $fila, $row['Signo2']);
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $fila, $row['Mes']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $fila, $row['Anio']);
            $objPHPExcel->getActiveSheet()->setCellValue('R' . $fila, $row['Archivo']);
            $FechaArchivo = $row['Mes'] . $row['Anio'] . "-" . $row['Archivo'];
            $fila++;
        }
        // Configurar el formato de la hoja de cálculo
        $objPHPExcel->getActiveSheet()->getStyle('A1:R1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(15);
        // Crear el objeto de escritura del archivo Excel
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        // Guardar el archivo en el servidor
        $fecha_actual = date("d-m-Y");
        $archivo = "Movimientos$fecha_actual.xlsx";
        $archivo = "Movimientos.$FechaArchivo.xlsx";
        $objWriter->save($archivo);
        // Generar el enlace para descargar el archivo

        $urlArchivo = '' . $archivo;

        echo "La tabla de Movimientos se ha exportado correctamente. Descarga el archivo <a href='$urlArchivo'>$archivo</a>.";
    } else {
        echo "No se encontraron registros en la tabla";
    }
    $resultado->free_result();
    $conexion->close();
}
function formatearNumero9Digitos($numero)
{
    // Separar la parte entera de la parte decimal
    //$partes = explode('.', $numero);

    // $pos = strpos($numero, '.');
    // echo "Numero $numero Posicion - ".$pos."<br>";
    // Manejar la parte entera

    //return $numeroFormateado;



    // Separar la parte entera de la parte decimal
    $partes = explode('.', $numero);

    // Manejar la parte entera
    $parteEntera = isset($partes[0]) ? $partes[0] : '0';
    $parteEntera = str_pad($parteEntera, 7, '0', STR_PAD_LEFT);

    // Manejar la parte decimal
    $parteDecimal = isset($partes[1]) ? $partes[1] : '0';
    $parteDecimal = str_pad($parteDecimal, 2, '0', STR_PAD_RIGHT);

    // Concatenar la parte entera y la parte decimal con un punto
    $numeroFormateado = $parteEntera . $parteDecimal;

    return $numeroFormateado;
}
function formatearNumero10Digitos($numero)
{
    // Separar la parte entera de la parte decimal
    //$partes = explode('.', $numero);

    // $pos = strpos($numero, '.');
    // echo "Numero $numero Posicion - ".$pos."<br>";
    // Manejar la parte entera

    //return $numeroFormateado;



    // Separar la parte entera de la parte decimal
    $partes = explode('.', $numero);

    // Manejar la parte entera
    $parteEntera = isset($partes[0]) ? $partes[0] : '0';
    $parteEntera = str_pad($parteEntera, 8, '0', STR_PAD_LEFT);

    // Manejar la parte decimal
    $parteDecimal = isset($partes[1]) ? $partes[1] : '0';
    $parteDecimal = str_pad($parteDecimal, 2, '0', STR_PAD_RIGHT);

    // Concatenar la parte entera y la parte decimal con un punto
    $numeroFormateado = $parteEntera . $parteDecimal;

    return $numeroFormateado;
}
function validarFechaDDMMAAAA($fecha) { 
    // Verificar que la longitud sea exactamente 8 caracteres
    if (strlen($fecha) != 8) {
        return "Error: La fecha debe tener 8 caracteres.";
    }

    // Extraer el día, mes y año de la fecha
    $dia = substr($fecha, 0, 2);  // Primeros dos caracteres
    $mes = substr($fecha, 2, 2);  // Caracteres 3 y 4
    $anio = substr($fecha, 4, 4); // Últimos cuatro caracteres

    // Verificar que el día, mes y año sean numéricos
    if (!ctype_digit($dia) || !ctype_digit($mes) || !ctype_digit($anio)) {
        return "Error: El formato de la fecha debe ser numérico (DDMMAAAA).";
    }

    // Convertir día, mes y año a enteros
    $dia = intval($dia);
    $mes = intval($mes);
    $anio = intval($anio);

    // Verificar que el mes esté entre 1 y 12
    if ($mes < 1 || $mes > 12) {
        return "Error: El mes debe estar entre 01 y 12.";
    }

    // Verificar si el día es válido para el mes y año dados
    if (!checkdate($mes, $dia, $anio)) {
        return "Error: La fecha no es válida para el mes y año dados.";
    }

    // Si todo es válido
    return "Menu - Acreditaciones - Procesar Excel (Acreditaciones.xlsx)";
}