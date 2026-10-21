/**
 * CABAL FSR - Gestor de Modo Oscuro / Claro
 * Secretaría de Niñez, Adolescencia y Familia (SeNAF) / Gobierno de Río Negro
 * Entidad 782 - Cabal
 */

(function () {
    'use strict';

    var STORAGE_KEY = 'cabal-theme';

    /**
     * Determina el tema inicial (por defecto 'dark', activado a requerimiento)
     */
    function getPreferredTheme() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved === 'light' || saved === 'dark') {
                return saved;
            }
        } catch (e) {
            // Manejo silencioso en caso de restricciones de storage
        }
        return 'dark'; // Modo oscuro habilitado por defecto
    }

    /**
     * Aplica el atributo data-theme y sincroniza los botones
     */
    function applyTheme(theme, animate) {
        if (animate) {
            document.documentElement.classList.add('theme-transition');
            window.setTimeout(function () {
                document.documentElement.classList.remove('theme-transition');
            }, 350);
        }

        document.documentElement.setAttribute('data-theme', theme);

        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {}

        updateToggleButtons(theme);
    }

    /**
     * Sincroniza la etiqueta y accesibilidad de los botones de alternancia.
     * La etiqueta indica expresamente la ACCIÓN que se ejecutará al pulsar el botón:
     * - Si el sistema está en Modo Oscuro -> "Cambiar a Modo Claro"
     * - Si el sistema está en Modo Claro  -> "Cambiar a Modo Oscuro"
     */
    function updateToggleButtons(theme) {
        var buttons = document.querySelectorAll('.theme-toggle-btn, #btn-theme-toggle');
        var actionText = theme === 'dark' ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro';

        for (var i = 0; i < buttons.length; i++) {
            var btn = buttons[i];
            var label = btn.querySelector('.theme-toggle-label');
            if (label) {
                label.textContent = actionText;
            }
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            btn.setAttribute('title', actionText);
        }
    }

    /**
     * Alterna entre modo oscuro y claro
     */
    window.toggleCabalTheme = function () {
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        var next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next, true);
    };

    /**
     * Inicializa o inyecta el botón de tema en la barra de navegación si no existe
     */
    function initThemeUI() {
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        var actionText = currentTheme === 'dark' ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro';
        var existingBtn = document.getElementById('btn-theme-toggle');

        if (!existingBtn) {
            var navActions = document.querySelector('.nav-actions');
            if (navActions) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.id = 'btn-theme-toggle';
                btn.className = 'theme-toggle-btn';
                btn.setAttribute('aria-label', actionText);
                btn.setAttribute('title', actionText);
                btn.innerHTML =
                    '<svg class="sun-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<circle cx="12" cy="12" r="5"></circle>' +
                    '<line x1="12" y1="1" x2="12" y2="3"></line>' +
                    '<line x1="12" y1="21" x2="12" y2="23"></line>' +
                    '<line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>' +
                    '<line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>' +
                    '<line x1="1" y1="12" x2="3" y2="12"></line>' +
                    '<line x1="21" y1="12" x2="23" y2="12"></line>' +
                    '<line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>' +
                    '<line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>' +
                    '</svg>' +
                    '<svg class="moon-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>' +
                    '</svg>' +
                    '<span class="theme-toggle-label">' + actionText + '</span>';

                btn.addEventListener('click', window.toggleCabalTheme);
                navActions.insertBefore(btn, navActions.firstChild);
            }
        } else {
            existingBtn.addEventListener('click', window.toggleCabalTheme);
        }

        updateToggleButtons(currentTheme);
    }

    // Ejecución inmediata para prevenir destellos blancos (FOUC)
    applyTheme(getPreferredTheme(), false);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeUI);
    } else {
        initThemeUI();
    }
})();
