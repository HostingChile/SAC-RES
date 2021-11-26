<?php
session_start();

require_once __DIR__ . '/include/config.php';

unset($_SESSION[md5($system_name)]);

// -------- ELIMINAR OTRAS VARIABLES DE SESION NO USADAS -------- //

unset($_SESSION['level']);

// -------------------------------------------------------------- //

echo "<script>window.history.back()</script>";
?>