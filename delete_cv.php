<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

require 'conexion.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cv_id'])) {
    
    $cv_id = intval($_POST['cv_id']);
    
    try {
        // Verificar que el CV pertenece al usuario actual
        $check_sql = "SELECT file_path FROM cv_files WHERE id = ? AND user_id = ?";
        $stmt_check = $conn->prepare($check_sql);
        
        if (!$stmt_check) {
            throw new Exception('Error en la preparación de consulta: ' . $conn->error);
        }
        
        $stmt_check->bind_param("ii", $cv_id, $user_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('CV no encontrado o no pertenece al usuario');
        }
        
        $cv_data = $result->fetch_assoc();
        $file_path = $cv_data['file_path'];
        $stmt_check->close();
        
        // Eliminar registro de la base de datos
        $delete_sql = "DELETE FROM cv_files WHERE id = ? AND user_id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        
        if (!$stmt_delete) {
            throw new Exception('Error en la preparación de consulta delete: ' . $conn->error);
        }
        
        $stmt_delete->bind_param("ii", $cv_id, $user_id);
        
        if (!$stmt_delete->execute()) {
            throw new Exception('Error al eliminar CV de la base de datos: ' . $stmt_delete->error);
        }
        
        $stmt_delete->close();
        
        // Eliminar archivo físico si existe
        if (file_exists($file_path)) {
            if (!unlink($file_path)) {
                error_log("No se pudo eliminar el archivo: " . $file_path);
                // No lanzamos excepción aquí porque el registro ya se eliminó
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'CV eliminado exitosamente';
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Error en delete_cv.php: " . $e->getMessage());
    }
    
} else {
    $response['message'] = 'Solicitud inválida';
}

// Responder con JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
exit();
?>