<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Ficha Oficial de Beneficiario
 * Entidad 782 - Cabal
 */

require_once('BD/BD.php');
require_once('BD/Funciones.php');

$dni = isset($_POST['dni']) ? trim($_POST['dni']) : (isset($_GET['dni']) ? trim($_GET['dni']) : '');
// Sanitizar DNI para permitir solo dígitos numéricos
$dni_limpio = preg_replace('/[^0-9]/', '', $dni);

$encontrado = false;
$Quien = array();

if (!empty($dni_limpio)) {
    $Quien = DevuelvoDatosCuenta($dni_limpio);
    if (!empty($Quien) && isset($Quien[0]) && $Quien[0] != 0) {
        $encontrado = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $encontrado ? htmlspecialchars($Quien[1] . ' ' . $Quien[2]) . ' — Ficha de Beneficiario' : 'Beneficiario no encontrado'; ?> | Sistema Cabal</title>
    <meta name="description" content="Ficha técnica e individual de beneficiario de tarjeta Cabal FSR">
    <link rel="icon" href="img/favicon.png">
    <!-- Estilos del Tablero de Operaciones -->
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .profile-container {
            max-width: 880px;
            margin: 0 auto 36px;
        }

        .beneficiary-card {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .profile-hero-header {
            padding: 32px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .profile-avatar-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-600), var(--primary-800));
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            border: 3px solid rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
        }

        .profile-title-texts h2 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .profile-title-texts p {
            color: #94a3b8;
            font-size: 0.88rem;
        }

        .profile-body {
            padding: 32px;
        }

        .profile-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary-700);
            margin: 20px 0 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-section-title:first-child {
            margin-top: 0;
        }

        .profile-details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.92rem;
            margin-bottom: 24px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .profile-details-table th {
            width: 32%;
            background: var(--surface-subtle);
            color: var(--navy-800);
            font-weight: 700;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-light);
            border-right: 1px solid var(--border-light);
            text-align: left;
        }

        .profile-details-table td {
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-light);
            color: var(--navy-900);
            font-weight: 500;
        }

        .profile-details-table tr:last-child th,
        .profile-details-table tr:last-child td {
            border-bottom: none;
        }

        .badge-card-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            background: #eff6ff;
            color: var(--primary-700);
            border: 1px solid var(--primary-200);
            letter-spacing: 0.04em;
        }

        .badge-account-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            background: var(--surface-subtle);
            color: var(--navy-800);
            border: 1px solid var(--border-light);
        }

        .profile-actions-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-light);
        }

        .btn-print-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all var(--transition-fast);
        }

        .btn-print-action:hover {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
        }

        /* ESTILOS PARA IMPRESIÓN OFICIAL */
        @media print {
            .top-nav,
            .help-hero-banner,
            .profile-actions-bar,
            .footer-main,
            .no-print {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }

            .dashboard-container {
                max-width: 100% !important;
                padding: 0 !important;
            }

            .beneficiary-card {
                border: 2px solid #000000 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .profile-hero-header {
                background: #f1f5f9 !important;
                color: #000000 !important;
                border-bottom: 2px solid #000000 !important;
                padding: 20px !important;
            }

            .profile-avatar-badge {
                display: none !important;
            }

            .profile-title-texts h2 {
                color: #000000 !important;
                font-size: 1.4rem !important;
            }

            .profile-title-texts p {
                color: #333333 !important;
            }

            .profile-details-table {
                border: 1px solid #000000 !important;
            }

            .profile-details-table th,
            .profile-details-table td {
                border-color: #000000 !important;
                padding: 8px 12px !important;
                font-size: 0.9rem !important;
            }

            .profile-details-table th {
                background: #f8fafc !important;
                color: #000000 !important;
            }

            .badge-card-number,
            .badge-account-number {
                background: transparent !important;
                border: none !important;
                color: #000000 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

<!-- =======================================================================
     BARRA DE NAVEGACIÓN INSTITUCIONAL
     ======================================================================= -->
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
            <a href="ConsultarBeneficiario.php" class="btn-nav-action primary" id="btn-nueva-consulta">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Nueva Consulta
            </a>

            <a href="index.php" class="btn-nav-action" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Tablero
            </a>

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action" id="btn-padron">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Padrón
            </a>
        </div>
    </div>
</header>

<!-- =======================================================================
     CONTENEDOR PRINCIPAL
     ======================================================================= -->
<main class="dashboard-container">

    <div class="profile-container">

        <?php if ($encontrado): ?>
            <!-- FICHA ENCONTRADA -->
            <article class="beneficiary-card" id="ficha-beneficiario">
                <header class="profile-hero-header">
                    <div class="profile-avatar-group">
                        <div class="profile-avatar-badge">
                            <?php 
                                $iniciales = mb_substr($Quien[1], 0, 1, 'UTF-8') . mb_substr($Quien[2], 0, 1, 'UTF-8');
                                echo htmlspecialchars(strtoupper($iniciales)); 
                            ?>
                        </div>
                        <div class="profile-title-texts">
                            <h2><?php echo htmlspecialchars($Quien[1] . " " . $Quien[2]); ?></h2>
                            <p>Titular de Tarjeta Social • Programa Familia Rionegrina Solidaria</p>
                        </div>
                    </div>
                    <div>
                        <span class="badge-tag success" style="font-size: 0.8rem; padding: 6px 14px;">
                            &bull; Beneficiario Activo
                        </span>
                    </div>
                </header>

                <div class="profile-body">
                    <!-- SECCIÓN 1: DATOS PERSONALES -->
                    <div class="profile-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Datos Personales y de Identificación
                    </div>

                    <table class="profile-details-table">
                        <tr>
                            <th>Nombre Completo</th>
                            <td><strong><?php echo htmlspecialchars($Quien[1] . " " . $Quien[2] . " (" . $Quien[9] . ")"); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Fecha de Nacimiento</th>
                            <td><?php echo htmlspecialchars($Quien[12]); ?></td>
                        </tr>
                        <tr>
                            <th>Documento de Identidad</th>
                            <td><strong><?php echo htmlspecialchars($Quien[13] . " " . $Quien[14]); ?></strong></td>
                        </tr>
                        <tr>
                            <th>CUIT / CUIL</th>
                            <td><code><?php echo htmlspecialchars($Quien[15]); ?></code></td>
                        </tr>
                    </table>

                    <!-- SECCIÓN 2: DATOS BANCARIOS CABAL -->
                    <div class="profile-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        Datos Bancarios y Tarjeta (Cabal Entidad 782)
                    </div>

                    <table class="profile-details-table">
                        <tr>
                            <th>Número de Cuenta</th>
                            <td>
                                <span class="badge-account-number"><?php echo htmlspecialchars($Quien[10]); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Número de Tarjeta</th>
                            <td>
                                <span class="badge-card-number"><?php echo htmlspecialchars(formatearTarjetaCredito($Quien[11])); ?></span>
                                <small style="display: block; margin-top: 4px; color: var(--text-muted); font-size: 0.76rem;">
                                    Dígitos centrales enmascarados conforme a norma PCI-DSS
                                </small>
                            </td>
                        </tr>
                    </table>

                    <!-- SECCIÓN 3: DOMICILIO Y RADICACIÓN -->
                    <div class="profile-section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Domicilio Registrado
                    </div>

                    <table class="profile-details-table">
                        <tr>
                            <th>Ubicación</th>
                            <td><?php echo htmlspecialchars(trim($Quien[8] . " " . $Quien[7])); ?></td>
                        </tr>
                        <tr>
                            <th>Dirección</th>
                            <td><?php echo htmlspecialchars(trim($Quien[3] . " " . $Quien[4] . " " . $Quien[5] . " " . $Quien[6])); ?></td>
                        </tr>
                    </table>

                    <!-- BARRA DE ACCIONES -->
                    <div class="profile-actions-bar no-print">
                        <button type="button" class="btn-print-action" onclick="window.print()" id="btn-imprimir">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Imprimir Ficha Oficial
                        </button>

                        <a href="ConsultarBeneficiario.php" class="btn-nav-action" style="padding: 12px 20px; font-size: 0.92rem; color: var(--navy-800); background: var(--surface-subtle); border-color: var(--border-light);">
                            Consultar Otro DNI
                        </a>

                        <a href="javascript:history.back()" class="btn-nav-action" style="padding: 12px 20px; font-size: 0.92rem; color: var(--text-muted); background: transparent; border-color: transparent;">
                            Volver Atrás
                        </a>
                    </div>
                </div>
            </article>

        <?php else: ?>
            <!-- BENEFICIARIO NO ENCONTRADO -->
            <article class="beneficiary-card">
                <div class="profile-hero-header" style="background: linear-gradient(135deg, #450a0a 0%, #1e1b4b 100%);">
                    <div class="profile-avatar-group">
                        <div class="profile-avatar-badge" style="background: #dc2626; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4);">
                            !
                        </div>
                        <div class="profile-title-texts">
                            <h2>No se encuentra el beneficiario solicitado</h2>
                            <p>No existe ningún registro en la base de datos con el documento ingresado</p>
                        </div>
                    </div>
                    <div>
                        <span class="badge-tag warning" style="font-size: 0.8rem; padding: 6px 14px;">
                            Sin Coincidencias
                        </span>
                    </div>
                </div>

                <div class="profile-body">
                    <div style="background: var(--surface-subtle); border-radius: var(--radius-lg); padding: 24px; border: 1px dashed var(--border-light); margin-bottom: 24px;">
                        <p style="font-size: 0.92rem; color: var(--navy-800); margin-bottom: 8px;">
                            <strong>DNI consultado:</strong> <code><?php echo !empty($dni) ? htmlspecialchars($dni) : 'Ninguno'; ?></code>
                        </p>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                            Verificá que el número de documento no contenga letras, puntos ni caracteres especiales. Si se trata de un nuevo titular, comprobá que el archivo <code>MovAltas.txt</code> haya sido previamente procesado en el sistema.
                        </p>
                    </div>

                    <div class="profile-actions-bar">
                        <a href="ConsultarBeneficiario.php" class="btn-nav-action primary" style="padding: 12px 24px; font-size: 0.92rem;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            Volver a Intentar
                        </a>

                        <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action" style="padding: 12px 20px; font-size: 0.92rem; color: var(--navy-800); background: var(--surface-subtle); border-color: var(--border-light);">
                            Revisar Padrón Completo
                        </a>

                        <a href="index.php" class="btn-nav-action" style="padding: 12px 20px; font-size: 0.92rem; color: var(--text-muted); background: transparent; border-color: transparent;">
                            Volver al Tablero
                        </a>
                    </div>
                </div>
            </article>
        <?php endif; ?>

    </div>

</main>

<!-- =======================================================================
     FOOTER INSTITUCIONAL
     ======================================================================= -->
<footer class="footer-main">
    <div class="footer-inner">
        <div>
            <strong>Sistema CABAL FSR</strong> — Secretaría de Niñez, Adolescencia y Familia (SeNAF)
            <div style="font-size: 0.75rem; margin-top: 4px; color: #64748b;">
                Gobierno de la Provincia de Río Negro • Entidad Financiera 782 • Sucursal 000
            </div>
        </div>
        <div class="footer-links">
            <a href="index.php">Inicio</a>
            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer">Plataforma Cabal</a>
            <a href="ayuda.html" target="_blank">Manual de Usuario</a>
            <a href="docs/ARQUITECTURA.md" target="_blank">Arquitectura</a>
            <a href="GEMINI.md" target="_blank">Normas Técnicas</a>
        </div>
    </div>
</footer>

</body>
</html>
