<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar PHPExcel
require_once 'phpexcel/PHPExcel.php';

// Conexión
$conn = mysqli_connect("localhost", "pucho", "78900", "cabal");
mysqli_set_charset($conn, "utf8");

$stats = array('total' => 0, 'exitos' => 0, 'fallidos' => 0, 'dinero' => 0);
$procesados = array();

if (isset($_POST['btn_cargar'])) {
    $archivoTmp = $_FILES['archivo_excel']['tmp_name'];

    try {
        $phpExcel = PHPExcel_IOFactory::load($archivoTmp);
        $hoja = $phpExcel->getSheet(0); 
        $filasMax = $hoja->getHighestRow();

        mysqli_query($conn, "TRUNCATE TABLE acreditar2");

        $sql_busqueda = "SELECT id, Apellido, Nombre, NumeroCuenta, NumeroDocumento FROM beneficiario WHERE Cuit = ? LIMIT 1";
        $stmt_busqueda = mysqli_prepare($conn, $sql_busqueda);

        $sql_ins = "INSERT INTO acreditar2 (id_beneficiario, Apellido, Nombre, NumeroCuenta, ImporteCompra, ImporteCajero, DNI, Fecha, Hora) 
                    VALUES (?, ?, ?, ?, ?, '0', ?, ?, ?)";
        $stmt_ins = mysqli_prepare($conn, $sql_ins);

        // Empezamos en fila 6. 
        for ($fila = 6; $fila <= $filasMax; $fila++) {
            $cuil_excel = trim($hoja->getCellByColumnAndRow(6, $fila)->getValue());
            $monto = $hoja->getCellByColumnAndRow(7, $fila)->getValue();
            $apellido_check = trim($hoja->getCellByColumnAndRow(2, $fila)->getValue());

            // --- FILTRO: Ignorar fila de total o vacías ---
            // Si no hay apellido o el CUIL no es un número válido, saltamos la fila
            if (empty($apellido_check) || empty($cuil_excel) || !is_numeric($cuil_excel)) {
                continue; 
            }

            $stats['total']++;
            mysqli_stmt_bind_param($stmt_busqueda, "s", $cuil_excel);
            mysqli_stmt_execute($stmt_busqueda);
            $resultado = mysqli_stmt_get_result($stmt_busqueda);

            $item = array('fila' => $fila, 'cuil' => $cuil_excel, 'monto' => $monto, 'status' => 'error', 'msg' => 'No encontrado en BD');

            if ($row = mysqli_fetch_assoc($resultado)) {
                $fecha_actual = date("Y-m-d");
                $hora_actual  = date("H:i:s");
                
                mysqli_stmt_bind_param($stmt_ins, "isssssss", 
                    $row['id'], $row['Apellido'], $row['Nombre'], $row['NumeroCuenta'], 
                    $monto, $row['NumeroDocumento'], $fecha_actual, $hora_actual
                );

                if (mysqli_stmt_execute($stmt_ins)) {
                    $item['status'] = 'success';
                    $item['msg'] = $row['Apellido'] . ", " . $row['Nombre'];
                    $stats['exitos']++;
                    $stats['dinero'] += (float)$monto;
                }
            } else {
                $stats['fallidos']++;
            }
            $procesados[] = $item;
        }

    } catch (Exception $e) {
        $error_critico = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Acreditación | Cabal</title>
    <style>
        :root { --primary: #2563eb; --success: #22c55e; --danger: #ef4444; --bg: #f8fafc; }
        body { font-family: 'Inter', system-ui, sans-serif; background-color: var(--bg); color: #1e293b; padding: 40px 20px; }
        .container { max-width: 1100px; margin: auto; }
        
        .header { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        h1 { margin: 0; font-size: 1.5rem; color: #0f172a; }

        .card-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
        .stat-val { display: block; font-size: 1.5rem; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: #64748b; text-transform: uppercase; }

        .upload-box { background: #ffffff; border: 2px dashed #cbd5e1; padding: 30px; border-radius: 15px; text-align: center; margin-bottom: 25px; }
        .btn { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th { background: #f1f5f9; padding: 15px; text-align: left; font-size: 0.85rem; color: #475569; }
        td { padding: 15px; border-top: 1px solid #f1f5f9; font-size: 0.9rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .bg-success { background: #dcfce7; color: #166534; }
        .bg-danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>💳 Sistema de Acreditación Cabal</h1>
        <small><?php echo date("d/m/Y H:i"); ?></small>
    </div>

    <div class="upload-box">
        <form action="" method="post" enctype="multipart/form-data">
            <p>Seleccione la planilla Excel de Suministro para procesar</p>
            <input type="file" name="archivo_excel" required style="margin-bottom: 15px;"><br>
            <button type="submit" name="btn_cargar" class="btn">Procesar y Blanquear Tabla</button>
        </form>
    </div>

    <?php if (!empty($procesados)): ?>
        <div class="card-stats">
            <div class="stat">
                <span class="stat-val"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Filas Leídas</span>
            </div>
            <div class="stat">
                <span class="stat-val" style="color: var(--success);"><?php echo $stats['exitos']; ?></span>
                <span class="stat-label">Cargados OK</span>
            </div>
            <div class="stat">
                <span class="stat-val" style="color: var(--danger);"><?php echo $stats['fallidos']; ?></span>
                <span class="stat-label">No encontrados</span>
            </div>
            <div class="stat">
                <span class="stat-val">$<?php echo number_format($stats['dinero'], 0, ',', '.'); ?></span>
                <span class="stat-label">Total Acreditado</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>FILA</th>
                    <th>CUIL</th>
                    <th>APELLIDO Y NOMBRE</th>
                    <th>MONTO</th>
                    <th>ESTADO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($procesados as $p): ?>
                <tr>
                    <td>#<?php echo $p['fila']; ?></td>
                    <td><strong><?php echo $p['cuil']; ?></strong></td>
                    <td><?php echo $p['msg']; ?></td>
                    <td>$<?php echo number_format($p['monto'], 2, ',', '.'); ?></td>
                    <td>
                        <span class="badge <?php echo ($p['status'] == 'success') ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo ($p['status'] == 'success') ? 'PROCESADO' : 'ERROR'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>