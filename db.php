<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "plataforma"; // nombre exacto de tu base

// Conexión
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
