<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

require 'conexion.php';

$user_id = $_SESSION['user_id'];

if (isset($_GET['cv_id'])) {
    $cv_id = intval($_GET['cv_id']);
    
    // Verificar que el CV pertenece al usuario actual
    $sql = "SELECT file_path, original_name, file_type FROM cv_files WHERE id = ? AND user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cv_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cv_data = $result->fetch_assoc();
        $file_path = $cv_data['file_path'];
        $original_name = $cv_data['original_name'];
        $file_type = $cv_data['file_type'];
        
        if (file_exists($file_path)) {
            // Configurar headers para descarga
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $file_type);
            header('Content-Disposition: attachment; filename="' . basename($original_name) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            
            // Limpiar buffer de salida
            ob_clean();
            flush();
            
            // Leer y enviar archivo
            readfile($file_path);
            exit();
        } else {
            http_response_code(404);
            echo "Archivo no encontrado.";
        }
    } else {
        http_response_code(403);
        echo "Acceso denegado.";
    }
} else {
    http_response_code(400);
    echo "Parámetros inválidos.";
}

$conn->close();
?>