<?php
/**
 * CABAL - Familia Rionegrina Solidaria (FSR / SeNAF)
 * Alta Manual Deshabilitada
 * 
 * Directiva Institucional: Las altas de beneficiarios se procesan
 * exclusivamente a través del circuito oficial de Beneficiarios (Paso 1).
 */

require_once(__DIR__ . "/../auth/verificar_sesion.php");
requerirAutenticacion();

header("Location: index.php?info=flujo_altas");
exit;
