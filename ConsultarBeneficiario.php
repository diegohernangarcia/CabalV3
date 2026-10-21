<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Beneficiario por DNI — Sistema Cabal</title>
    <meta name="description" content="Consulta individual de ficha de beneficiario por número de DNI - Familia Rionegrina Solidaria">
    <link rel="icon" href="img/favicon.png">
    <!-- Estilos del Tablero de Operaciones -->
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .search-card-container {
            max-width: 680px;
            margin: 0 auto 36px;
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .search-card-header {
            padding: 24px 32px;
            background: linear-gradient(180deg, #ffffff 0%, var(--surface-subtle) 100%);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .search-card-title-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .search-card-title-group h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--navy-800);
        }

        .search-card-title-group p {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .search-card-body {
            padding: 32px;
        }

        .form-group-custom {
            margin-bottom: 24px;
        }

        .form-group-custom label {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--navy-800);
            margin-bottom: 8px;
        }

        .form-group-custom .input-wrapper {
            position: relative;
        }

        .form-group-custom .input-wrapper svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
        }

        .form-group-custom input[type="text"] {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 1.1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 600;
            color: var(--navy-900);
            background: #ffffff;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            outline: none;
            transition: all var(--transition-fast);
            letter-spacing: 0.03em;
        }

        .form-group-custom input[type="text"]:focus {
            border-color: var(--primary-600);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .form-group-custom .help-text {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 8px;
        }

        .search-actions-bar {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-submit-search {
            flex: 1;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit-search:hover {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
        }

        .info-hint-box {
            background: var(--surface-subtle);
            border: 1px dashed var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-top: 28px;
            font-size: 0.84rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .info-hint-box strong {
            color: var(--navy-800);
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
            <a href="index.php" class="btn-nav-action primary" id="btn-volver-tablero">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Tablero
            </a>

            <a href="ExportarExcelBeneficiarios.php" class="btn-nav-action" id="btn-padron">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Ver Padrón Completo
            </a>

            <a href="https://transmisiones.cabal.coop/webclient/Login.xhtml" target="_blank" rel="noopener noreferrer" class="btn-nav-action" id="btn-portal-cabal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                Portal Cabal
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

<!-- =======================================================================
     CONTENEDOR PRINCIPAL
     ======================================================================= -->
<main class="dashboard-container">

    <!-- HERO BANNER -->
    <section class="help-hero-banner" style="margin-bottom: 28px;">
        <div class="help-hero-text">
            <h2>
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary-500);">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Consulta de Beneficiario
            </h2>
            <p>
                Búsqueda y visualización de la ficha personal, datos de cuenta bancaria y tarjeta de crédito/débito asignada al titular.
            </p>
        </div>
        <div>
            <a href="index.php" class="btn-nav-action primary" style="padding: 10px 20px; font-size: 0.88rem;">
                Volver al Inicio
            </a>
        </div>
    </section>

    <!-- CARD FORMULARIO DE CONSULTA -->
    <div class="search-card-container">
        <div class="search-card-header">
            <div class="search-card-title-group">
                <div class="help-icon-wrap" style="background: var(--primary-50); color: var(--primary-600);">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h3>Ficha Individual de Beneficiario</h3>
                    <p>Búsqueda en la base de datos de beneficiarios activos</p>
                </div>
            </div>
            <span class="badge-tag accent">Búsqueda Directa</span>
        </div>

        <div class="search-card-body">
            <form action="ConsultaBenefiario2.php" method="POST" id="form-consulta-beneficiario">
                <div class="form-group-custom">
                    <label for="dni">DNI (Tamaño 7 u 8 caracteres):</label>
                    <div class="input-wrapper">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                            <circle cx="9" cy="10" r="2"></circle>
                            <line x1="15" y1="8" x2="17" y2="8"></line>
                            <line x1="15" y1="12" x2="17" y2="12"></line>
                            <line x1="7" y1="16" x2="17" y2="16"></line>
                        </svg>
                        <input 
                            type="text" 
                            id="dni" 
                            name="dni" 
                            placeholder="Ej: 28456123" 
                            required 
                            minlength="7" 
                            maxlength="8" 
                            pattern="[0-9]{7,8}" 
                            title="Ingresá 7 u 8 dígitos numéricos sin puntos" 
                            autocomplete="off" 
                            autofocus
                        >
                    </div>
                    <span class="help-text">
                        Ingresá únicamente los dígitos numéricos del documento, sin puntos ni espacios.
                    </span>
                </div>

                <div class="search-actions-bar">
                    <button type="submit" class="btn-submit-search" id="btn-enviar-consulta">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Consultar Beneficiario
                    </button>

                    <a href="javascript:history.back()" class="btn-nav-action" style="padding: 14px 20px; font-size: 0.95rem; color: var(--navy-800); background: var(--surface-subtle); border-color: var(--border-light);" id="btn-volver-atras">
                        Volver Atrás
                    </a>
                </div>
            </form>

            <div class="info-hint-box">
                <strong>¿Qué datos podés consultar en esta pantalla?</strong><br>
                Nombre completo, fecha de nacimiento, DNI, CUIT, Número de Cuenta Cabal asignada, Número de Tarjeta (enmascarada con norma PCI), Localidad y Domicilio registrado.
            </div>
        </div>
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
