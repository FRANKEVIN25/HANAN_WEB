<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "plataforma"; // Usa el mismo nombre de tu base de datos

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
