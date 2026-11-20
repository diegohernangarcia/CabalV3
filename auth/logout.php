<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Cierre de Sesión Seguro (Logout)
 * Entidad 782 - Cabal
 *
 * Funcionalidad:
 * 1. Registra el evento de desconexión en la tabla forense de auditoría.
 * 2. Destruye variables de sesión y la cookie de sesión del navegador.
 * 3. Redirige al login con mensaje confirmatorio.
 *
 * Compatibilidad: PHP 5.6.40+ (XAMPP LAMPP)
 */

require_once(__DIR__ . "/config_sesion.php");
require_once(__DIR__ . "/funciones_auth.php");

iniciarSesionSegura();

if (!empty($_SESSION['usuario_id'])) {
    $idUsuario = intval($_SESSION['usuario_id']);
    $usuario   = isset($_SESSION['usuario_login']) ? $_SESSION['usuario_login'] : 'Desconocido';
    registrarAuditoria($idUsuario, $usuario, 'LOGOUT');
}

// Destruir sesión y cookies de forma segura
cerrarSesionSegura();

// Redirigir al inicio de sesión con confirmación
header("Location: login.php?motivo=logout");
exit;
