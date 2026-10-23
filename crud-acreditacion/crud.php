<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Manual de Acreditaciones — Cabal FSR</title>
    <link rel="icon" href="../img/favicon.png">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="styles.css">
    <script src="../js/theme.js"></script>
</head>

<body>

<header class="top-nav">
    <div class="top-nav-inner">
        <a href="../index.php" class="brand-group" id="brand-link">
            <div class="brand-logo-wrap">
                <img src="../img/cabal.png" alt="Logo Cabal" onerror="this.src='../img/favicon.png'">
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
            <a href="../index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>
            <a href="../GenerarCargaCredito2026.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Generar NOVCAR
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 1100px; padding-top: 24px;">
    <div class="container">
        <h2 class="page-title">
            💳 Formulario de Ingreso de Acreditaciones Individuales
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: -12px; margin-bottom: 20px;">
            Busque al beneficiario por CUIT para incorporar créditos al lote manual de acreditación.
        </p>

        <form id="cuitForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label for="cuit">CUIT del Beneficiario (sin guiones):</label>
                    <input type="text" id="cuit" name="cuit" placeholder="Ej: 27123456789" required autocomplete="off">
                </div>

                <div>
                    <label for="monto">Monto a Acreditar ($):</label>
                    <input type="number" id="monto" name="monto" required step="0.01" placeholder="0.00">
                </div>
            </div>

            <div class="btn-group-custom">
                <input type="submit" value="🔍 Buscar Beneficiario y Agregar" class="btn">
                <button type="button" onclick="history.back();" class="btn btn-secondary">Volver</button>
            </div>
        </form>

        <div id="beneficiarioInfo"></div>

        <div class="tabla-container">
            <table id="beneficiarioTable" style="display:none;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID Beneficiario</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Número de Cuenta</th>
                        <th>Monto</th>
                        <th>Número de Documento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: right; padding-right: 15px;">TOTAL LOTE ($):</td>
                        <td id="totalImporte">0.00</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 20px;">
                <button id="terminar" style="display:none;" class="btn">
                    💾 Terminar y Guardar en Lote
                </button>
            </div>
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

<script src="script.js"></script>
</body>
</html>