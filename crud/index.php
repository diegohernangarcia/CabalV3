<?php
require_once(__DIR__ . "/../auth/verificar_sesion.php");
requerirAutenticacion();

// Incluir archivo de conexión a la base de datos
include_once 'conexion.php';
include_once '../BD/Funciones.php';

// Comprobar si se ha pulsado el botón "Eliminar" de forma segura con Prepared Statement
if (isset($_POST['eliminar']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conexion->prepare("DELETE FROM beneficiario WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: index.php');
    exit;
}

// Consulta para obtener todos los registros de la tabla beneficiario
$resultado = $conexion->query("SELECT * FROM beneficiario ORDER BY Apellido ASC, Nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexo FRS • Padrón de Beneficiarios</title>
    <link rel="icon" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="../js/theme.js"></script>
    <style>
        .crud-container {
            width: 98%;
            max-width: 1720px;
            margin: 0 auto;
            padding: 24px 16px;
            box-sizing: border-box;
        }
        .modern-card {
            background: var(--surface-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 28px;
        }
        .table-responsive-box {
            padding: 20px 24px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        /* Personalización DataTables para Modo Oscuro y Tema Institucional */
        .dataTables_wrapper {
            padding: 12px 0;
            color: var(--text-main);
            width: 100%;
        }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: var(--surface-card);
            color: var(--text-main);
            border: 1px solid var(--border-light);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-muted) !important;
            margin-top: 16px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-main) !important;
            border-radius: var(--radius-sm) !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-blue) !important;
            color: #ffffff !important;
            border: 1px solid var(--primary-blue) !important;
        }
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 0 auto !important;
        }
        table.dataTable thead th {
            background: var(--card-header-bg) !important;
            color: var(--navy-800) !important;
            border-bottom: 2px solid var(--border-light) !important;
            font-size: 0.85rem;
            padding: 12px 10px;
            vertical-align: middle;
            white-space: nowrap;
        }
        table.dataTable tbody td {
            background: var(--surface-card) !important;
            color: var(--text-main) !important;
            border-bottom: 1px solid var(--border-light) !important;
            font-size: 0.85rem;
            padding: 10px 8px;
            vertical-align: middle;
        }
        table.dataTable tbody tr:hover td {
            background: var(--bg-main) !important;
        }
        .col-acciones {
            min-width: 175px !important;
            width: 175px !important;
            text-align: center !important;
            white-space: nowrap !important;
        }
        .actions-btn-group {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap;
        }
        .btn-action-edit {
            background: var(--emerald-600);
            color: #ffffff !important;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            line-height: 1;
            transition: all var(--transition-fast);
            border: 1px solid transparent;
        }
        .btn-action-edit:hover {
            background: var(--emerald-700);
            transform: translateY(-1px);
        }
        .btn-action-delete {
            background: #dc2626;
            color: #ffffff !important;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            line-height: 1;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all var(--transition-fast);
        }
        .btn-action-delete:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        .alert-info-flow {
            background: var(--surface-card);
            border: 1px solid #3b82f6;
            border-left: 4px solid #2563eb;
            border-radius: var(--radius-md);
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-main);
            font-size: 0.9rem;
            box-shadow: var(--shadow-sm);
        }
    </style>
</head>
<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <div class="top-nav-header-bar">
            <div class="brand-logo-slot left">
                <a href="../index.php" class="brand-logo-wrap nexo-wrap" title="Nexo FRS">
                    <img src="../img/NexoFRS.png" alt="Logo Nexo FRS" onerror="this.src='../img/favicon.png'">
                </a>
            </div>
            <a href="../index.php" class="brand-text-center" id="brand-link">
                <h1>
                    Nexo FRS
                    <span class="brand-badge">Entidad 782</span>
                </h1>
                <p>SeNAF Río Negro • Familia Rionegrina Solidaria</p>
            </a>
            <div class="brand-logo-slot right">
                <div class="brand-logo-wrap cabal-wrap" title="Cabal Entidad 782">
                    <img src="../img/cabal.png" alt="Logo Cabal" onerror="this.src='../img/favicon.png'">
                </div>
            </div>
        </div>

        <div class="nav-actions">
            <a href="../index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>
            <a href="../CargaAltasDeExcelaBD.php" class="btn-nav-action" style="background: var(--blue-600); color: #ffffff;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Circuito de Altas (Paso 1)
            </a>
            <a href="../ExportarExcelBeneficiarios.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Exportar Excel
            </a>
        </div>
    </div>
</header>

<main class="crud-container">
    <?php if (isset($_GET['info']) && $_GET['info'] === 'flujo_altas'): ?>
        <div class="alert-info-flow">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
                <strong>Información Operativa:</strong> Las altas de beneficiarios no se realizan manualmente. Deben gestionarse mediante el circuito institucional oficial de carga masiva en <a href="../CargaAltasDeExcelaBD.php" style="color: #2563eb; font-weight: 600; text-decoration: underline;">Paso 1: Carga de Altas de Excel</a>.
            </div>
        </div>
    <?php endif; ?>

    <div class="modern-card">
        <div class="card-header-custom" style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800); margin: 0;">
                    👥 Padrón General de Beneficiarios
                </h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Visualización, búsqueda y edición de titulares en la base de datos local
                </p>
            </div>
            <div>
                <a href="../CargaAltasDeExcelaBD.php" class="btn-modern-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none; border-radius: var(--radius-sm); display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Circuito de Altas (Paso 1)
                </a>
            </div>
        </div>

        <div class="table-responsive-box">
            <table id="tabla" class="modern-table display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Apellido</th>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>CUIT</th>
                        <th>N° Cuenta</th>
                        <th>N° Tarjeta</th>
                        <th>Localidad</th>
                        <th>Provincia</th>
                        <th class="col-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($resultado): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <?php $tarjetaMask = formatearTarjetaCredito($fila['NumeroTarjeta']); ?>
                        <tr>
                            <td><?php echo (int)$fila['id']; ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($fila['Apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($fila['Nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($fila['TipoDocumento'] . ' ' . $fila['NumeroDocumento'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($fila['Cuit'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><code><?php echo htmlspecialchars($fila['NumeroCuenta'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><code><?php echo htmlspecialchars($tarjetaMask, ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><?php echo htmlspecialchars($fila['Localidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($fila['Provincia'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="col-acciones">
                                <div class="actions-btn-group">
                                    <a href="edit.php?id=<?php echo (int)$fila['id']; ?>" class="btn-action-edit" title="Editar beneficiario">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <button type="button" class="btn-action-delete" onclick="confirmarEliminar(<?php echo (int)$fila['id']; ?>)" title="Eliminar beneficiario">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabla').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[1, 'asc']],
            autoWidth: false,
            scrollX: true,
            columnDefs: [
                { targets: 9, orderable: false, searchable: false, width: '175px', className: 'col-acciones' }
            ]
        });
    });

    function confirmarEliminar(id) {
        if (confirm('¿Está seguro de que desea eliminar al beneficiario con ID ' + id + '?')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php';
            
            var inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id';
            inputId.value = id;
            form.appendChild(inputId);

            var inputEliminar = document.createElement('input');
            inputEliminar.type = 'hidden';
            inputEliminar.name = 'eliminar';
            inputEliminar.value = '1';
            form.appendChild(inputEliminar);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
</body>
</html>
<?php
$conexion->close();
?>