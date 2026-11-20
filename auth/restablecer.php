<?php
/**
 * Restablecimiento público desactivado por directiva institucional.
 * El blanqueo y restablecimiento de claves se realiza exclusivamente a través
 * del Panel de Administración de Usuarios por el Administrador General.
 */
header("Location: login.php");
exit;
