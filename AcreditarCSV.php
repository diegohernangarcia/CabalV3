<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir CSV de Acreditaciones — Cabal FSR</title>
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
            <a href="AcreditarSaldos2026.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Acreditar desde Excel
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 640px; padding-top: 36px;">
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800); margin: 0 0 4px 0;">
                📥 Subir Archivo CSV de Acreditaciones
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                Carga directa de importes de crédito mediante archivo delimitado por comas (.csv)
            </p>
        </div>

        <div class="card-body-custom" style="padding: 28px;">
            <form action="uploadcsv.php" method="post" enctype="multipart/form-data">
                <div style="margin-bottom: 24px;">
                    <label for="archivo" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-main);">
                        Seleccione el archivo .csv:
                    </label>
                    <input type="file" name="archivo" id="archivo" accept=".csv" required
                           style="width: 100%; padding: 12px; border: 2px dashed var(--border-light); border-radius: var(--radius-md); background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                </div>

                <div class="alert-modern info" style="margin-bottom: 24px;">
                    El archivo CSV debe contener las columnas de titulares, importes de compras, cajero y cuentas correspondientes.
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="index.php" class="btn-modern-secondary" style="padding: 10px 18px; border-radius: var(--radius-sm); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-modern-primary" style="padding: 10px 24px; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer;">
                        Subir y Procesar CSV
                    </button>
                </div>
            </form>
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