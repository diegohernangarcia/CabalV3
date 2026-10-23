<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesar Maestro de Plásticos — Cabal FSR</title>
    <meta name="description" content="Ingreso de archivo para procesamiento del Maestro de Plásticos Cabal">
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
            <a href="reporte_tarjetas.php" class="btn-nav-action">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Ver Padrón Tarjetas
            </a>
        </div>
    </div>
</header>

<main class="page-container" style="max-width: 680px; padding-top: 30px;">
    <div class="modern-card">
        <div class="card-header-custom" style="padding: 24px; border-bottom: 1px solid var(--border-light); background: var(--card-header-bg);">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--navy-800); margin-bottom: 6px;">
                💳 Procesar Maestro de Plásticos
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">
                Ingrese el nombre o ruta del archivo de novedades de plásticos generado por Cabal.
            </p>
        </div>

        <div class="card-body-custom" style="padding: 28px;">
            <form action="ProcesarMaestroPlasticos.php" method="GET">
                <div style="margin-bottom: 20px;">
                    <label for="archivo" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-main);">
                        Nombre del Archivo en Servidor:
                    </label>
                    <input type="text" id="archivo" name="archivo" value="archivos/MAESPLAS.txt" required
                           style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-light); border-radius: var(--radius-sm); font-size: 0.95rem; font-family: monospace; background: var(--surface-card); color: var(--text-main); box-sizing: border-box;">
                    <small style="display: block; margin-top: 6px; color: var(--text-muted); font-size: 0.8rem;">
                        Ubicación predeterminada: <code>archivos/MAESPLAS.txt</code>
                    </small>
                </div>

                <div class="alert-modern info" style="margin-bottom: 24px;">
                    <div style="font-weight: 600; margin-bottom: 4px;">Información de Formato:</div>
                    El archivo contiene los números de cuenta, plásticos de 16 dígitos, estado/causa (Habilitada, Extravío, Anulación, etc.) y fechas de vigencia.
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="index.php" class="btn-modern-secondary" style="padding: 10px 18px; border-radius: var(--radius-sm); text-decoration: none; font-size: 0.9rem; font-weight: 600;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-modern-primary" style="padding: 10px 22px; border-radius: var(--radius-sm); font-size: 0.9rem; font-weight: 600; border: none; cursor: pointer;">
                        Procesar Archivo
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
