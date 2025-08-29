<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

require 'conexion.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

// Función para limpiar nombre de archivo
function sanitizeFileName($filename) {
    // Remover caracteres especiales y espacios
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['cv_file'])) {
    
    try {
        $file = $_FILES['cv_file'];
        
        // Debug: Mostrar información del archivo
        error_log("Upload attempt - File info: " . print_r($file, true));
        
        // Verificar si hay errores en la subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta directorio temporal',
                UPLOAD_ERR_CANT_WRITE => 'No se puede escribir el archivo',
                UPLOAD_ERR_EXTENSION => 'Extensión no permitida'
            ];
            
            $error_msg = isset($error_messages[$file['error']]) ? 
                        $error_messages[$file['error']] : 
                        'Error desconocido: ' . $file['error'];
            
            throw new Exception($error_msg);
        }
        
        // Validar que el archivo no esté vacío
        if ($file['size'] == 0) {
            throw new Exception('El archivo está vacío');
        }
        
        // Validar extensión del archivo
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Tipo de archivo no permitido. Solo se permiten PDF, DOC y DOCX.');
        }
        
        // Validar tamaño (5MB máximo)
        $max_size = 5 * 1024 * 1024; // 5MB en bytes
        if ($file['size'] > $max_size) {
            throw new Exception('El archivo es demasiado grande. Máximo permitido: 5MB. Tamaño actual: ' . round($file['size'] / 1024 / 1024, 2) . 'MB');
        }
        
        // Crear directorio si no existe
        $upload_dir = 'uploads/cv_files/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception('No se pudo crear el directorio: ' . $upload_dir . '. Verifica permisos.');
            }
        }
        
        // Verificar que el directorio sea escribible
        if (!is_writable($upload_dir)) {
            throw new Exception('El directorio no tiene permisos de escritura: ' . $upload_dir);
        }
        
        // Generar nombre único para el archivo
        $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $safe_original_name = sanitizeFileName($original_name);
        $new_filename = 'cv_' . $user_id . '_' . time() . '_' . $safe_original_name . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Mover archivo subido
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Error al mover el archivo al directorio final. Verifica permisos del directorio uploads.');
        }
        
        // Verificar que el archivo se movió correctamente
        if (!file_exists($upload_path)) {
            throw new Exception('El archivo no se guardó correctamente en: ' . $upload_path);
        }
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Verificar si existe la tabla cv_files, si no, crearla
            $check_table = $conn->query("SHOW TABLES LIKE 'cv_files'");
            if ($check_table->num_rows == 0) {
                $create_table = "CREATE TABLE cv_files (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    file_name VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_size INT NOT NULL,
                    file_type VARCHAR(100) NOT NULL,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active TINYINT(1) DEFAULT 1,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                
                if (!$conn->query($create_table)) {
                    throw new Exception('Error al crear la tabla de CVs: ' . $conn->error);
                }
            }
            
            // Desactivar CVs anteriores del usuario
            $deactivate_sql = "UPDATE cv_files SET is_active = 0 WHERE user_id = ?";
            $stmt_deactivate = $conn->prepare($deactivate_sql);
            if (!$stmt_deactivate) {
                throw new Exception('Error en la preparación de consulta deactivate: ' . $conn->error);
            }
            $stmt_deactivate->bind_param("i", $user_id);
            $stmt_deactivate->execute();
            $stmt_deactivate->close();
            
            // Insertar nuevo CV
            $insert_sql = "INSERT INTO cv_files (user_id, file_name, original_name, file_path, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            if (!$stmt_insert) {
                throw new Exception('Error en la preparación de consulta insert: ' . $conn->error);
            }
            
            // Detectar tipo MIME
            $file_type = mime_content_type($upload_path);
            if (!$file_type) {
                $file_type = $file['type']; // Fallback al tipo original
            }
            
            $stmt_insert->bind_param("isssis", 
                $user_id,
                $new_filename,
                $file['name'],
                $upload_path,
                $file['size'],
                $file_type
            );
            
            if (!$stmt_insert->execute()) {
                throw new Exception('Error al guardar información del CV: ' . $stmt_insert->error);
            }
            
            $stmt_insert->close();
            
            // Confirmar transacción
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'CV subido exitosamente';
            $response['file_info'] = [
                'name' => $file['name'],
                'size' => round($file['size'] / 1024, 2) . ' KB',
                'uploaded_at' => date('d/m/Y H:i:s'),
                'path' => $upload_path
            ];
            
        } catch (Exception $e) {
            // Revertir transacción
            $conn->rollback();
            
            // Eliminar archivo si fue subido
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            
            throw $e;
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Error en upload_cv.php: " . $e->getMessage());
        error_log("Usuario: " . $user_id);
        error_log("Archivo: " . ($file['name'] ?? 'No definido'));
    }
    
} else {
    $response['message'] = 'No se recibió ningún archivo o método incorrecto';
}

// Siempre responder con JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
exit();
?>
