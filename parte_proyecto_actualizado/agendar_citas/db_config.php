<?php
/**
 * Conexión PDO para agendar_citas (compatibilidad con crear.php, solovistacitas.php)
 */
$host = 'localhost';
$db   = 'clinica_odontologica';
$user = 'root';
$pass = '';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
