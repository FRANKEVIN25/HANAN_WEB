<?php
session_start();

// Verifica si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

require 'conexion.php';

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Función para limpiar datos de entrada
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y limpiar los datos del formulario
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $professional_title = sanitize_input($_POST['professional_title'] ?? '');
    $bio = sanitize_input($_POST['bio'] ?? '');
    $hourly_rate = floatval($_POST['hourly_rate'] ?? 0);
    $location = sanitize_input($_POST['location'] ?? 'Lima, Perú');
    $skills = sanitize_input($_POST['skills'] ?? '');
    $languages = sanitize_input($_POST['languages'] ?? '');
    $linkedin = sanitize_input($_POST['linkedin'] ?? '');
    $portfolio = sanitize_input($_POST['portfolio'] ?? '');
    
    // Separar nombre completo en nombre y apellido
    $name_parts = explode(' ', $full_name, 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';
    
    // Validar datos
    if (empty($full_name)) {
        $error = "El nombre completo es obligatorio";
    } elseif ($hourly_rate < 0) {
        $error = "La tarifa por hora no puede ser negativa";
    } else {
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Actualizar tabla users (solo nombre y apellido)
            $sql_user = "UPDATE users SET first_name = ?, last_name = ? WHERE id = ?";
            $stmt_user = $conn->prepare($sql_user);
            
            if (!$stmt_user) {
                throw new Exception("Error en la preparación de la consulta users: " . $conn->error);
            }
            
            $stmt_user->bind_param("ssi", $first_name, $last_name, $user_id);
            
            if (!$stmt_user->execute()) {
                throw new Exception("Error al actualizar datos del usuario: " . $stmt_user->error);
            }
            
            $stmt_user->close();
            
            // Verificar si existe un perfil de freelancer
            $sql_check = "SELECT id FROM freelancer_profiles WHERE user_id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                // Actualizar perfil existente
                $sql_profile = "UPDATE freelancer_profiles SET 
                    bio = ?, 
                    hourly_rate = ?, 
                    skills = ?, 
                    languages = ?, 
                    location = ?, 
                    linkedin_url = ?, 
                    portfolio_url = ?, 
                    professional_title = ?
                WHERE user_id = ?";
                
                $stmt_profile = $conn->prepare($sql_profile);
                
                if (!$stmt_profile) {
                    throw new Exception("Error en la preparación de la consulta profile: " . $conn->error);
                }
                
                $stmt_profile->bind_param("sdssssssi", 
                    $bio, 
                    $hourly_rate, 
                    $skills, 
                    $languages, 
                    $location, 
                    $linkedin, 
                    $portfolio, 
                    $professional_title, 
                    $user_id
                );
            } else {
                // Insertar nuevo perfil
                $sql_profile = "INSERT INTO freelancer_profiles 
                    (user_id, bio, hourly_rate, skills, languages, location, linkedin_url, portfolio_url, professional_title) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt_profile = $conn->prepare($sql_profile);
                
                if (!$stmt_profile) {
                    throw new Exception("Error en la preparación de la consulta insert profile: " . $conn->error);
                }
                
                $stmt_profile->bind_param("isdssssss", 
                    $user_id,
                    $bio, 
                    $hourly_rate, 
                    $skills, 
                    $languages, 
                    $location, 
                    $linkedin, 
                    $portfolio, 
                    $professional_title
                );
            }
            
            $stmt_check->close();
            
            if (!$stmt_profile->execute()) {
                throw new Exception("Error al actualizar perfil: " . $stmt_profile->error);
            }
            
            $stmt_profile->close();
            
            // Manejar la foto de perfil si se subió una nueva
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
                    throw new Exception("Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF.");
                }
                
                if ($_FILES['profile_photo']['size'] > $max_size) {
                    throw new Exception("El archivo es demasiado grande. Máximo 5MB.");
                }
                
                // Crear directorio si no existe
                $upload_dir = 'uploads/profile_photos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generar nombre único para el archivo
                $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // CORRECCIÓN: Verificar si la columna existe antes de actualizar
                    // Primero, comprobar si existe la columna profile_photo
                    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
                    
                    if ($check_column->num_rows == 0) {
                        // Si no existe la columna, crearla
                        $alter_sql = "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL";
                        if (!$conn->query($alter_sql)) {
                            throw new Exception("Error al crear la columna profile_photo: " . $conn->error);
                        }
                    }
                    
                    // Actualizar la ruta de la foto en la base de datos
                    $sql_photo = "UPDATE users SET profile_photo = ? WHERE id = ?";
                    $stmt_photo = $conn->prepare($sql_photo);
                    
                    if (!$stmt_photo) {
                        throw new Exception("Error en la preparación de la consulta photo: " . $conn->error);
                    }
                    
                    $stmt_photo->bind_param("si", $upload_path, $user_id);
                    
                    if (!$stmt_photo->execute()) {
                        throw new Exception("Error al actualizar la foto de perfil: " . $stmt_photo->error);
                    }
                    
                    $stmt_photo->close();
                    $_SESSION['profile_photo'] = $upload_path;
                } else {
                    throw new Exception("Error al subir la foto de perfil.");
                }
            }
            
            // Confirmar transacción
            $conn->commit();
            
            // Actualizar datos de sesión
            $_SESSION['user_name'] = $full_name;
            
            $message = "Perfil actualizado exitosamente";
            
            // Redirigir con mensaje de éxito
            header("Location: freelancer_panel.php?success=1&tab=profile");
            exit();
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conn->rollback();
            $error = $e->getMessage();
            
            // Log del error (opcional)
            error_log("Error en update_profile.php: " . $e->getMessage());
            
            // Redirigir con mensaje de error
            header("Location: freelancer_panel.php?error=" . urlencode($error) . "&tab=profile");
            exit();
        }
    }
} else {
    // Si no es POST, redirigir al panel
    header("Location: freelancer_panel.php");
    exit();
}

$conn->close();
?>