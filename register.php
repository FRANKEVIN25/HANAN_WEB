<?php
session_start();

// Configuración de la base de datos
$host = "localhost";
$username = "root";
$password = "";
$database = "plataforma";

// Crear conexión
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Función para limpiar datos de entrada
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verificar si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtener y limpiar los datos del formulario
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $specialization = sanitize_input($_POST['specialization']);
    $experience = sanitize_input($_POST['experience']);
    $terms = isset($_POST['terms']) ? 1 : 0;
    
    // Validaciones
    $errors = [];
    
    // Validar nombre
    if (empty($first_name) || empty($last_name)) {
        $errors[] = "Nombre y apellido son requeridos";
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Formato de email inválido";
    }
    
    // Verificar si el email ya existe
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    if ($stmt === false) {
        die("Error en prepare (check email): " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Este email ya está registrado";
    }
    $stmt->close();
    
    // Validar teléfono (9 dígitos para Perú)
    if (!preg_match("/^[0-9]{9}$/", $phone)) {
        $errors[] = "El teléfono debe tener 9 dígitos";
    }
    
    // Validar contraseña
    if (strlen($password) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    // Validar términos y condiciones
    if (!$terms) {
        $errors[] = "Debes aceptar los términos y condiciones";
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errors)) {
        
        // Encriptar contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Fecha de registro
        $registration_date = date("Y-m-d H:i:s");
        
        // Preparar consulta SQL para insertar usuario
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, specialization, experience, registration_date, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error en prepare (insert user): " . $conn->error);
        }
        
        $stmt->bind_param("ssssssss", $first_name, $last_name, $email, $phone, $hashed_password, $specialization, $experience, $registration_date);

        if ($stmt->execute()) {
            // Registro exitoso
            $user_id = $conn->insert_id;
            $stmt->close();

            // Crear perfil inicial del freelancer
            $profile_sql = "INSERT INTO freelancer_profiles (user_id, hourly_rate, bio, skills, languages) 
                            VALUES (?, ?, ?, ?, ?)";
            $profile_stmt = $conn->prepare($profile_sql);

            if ($profile_stmt === false) {
                die("Error en prepare (insert profile): " . $conn->error);
            }

            $default_rate = 0.00;
            $default_bio = '';
            $default_skills = '';
            $default_languages = 'Español';
            
            $profile_stmt->bind_param("idsss", $user_id, $default_rate, $default_bio, $default_skills, $default_languages);
            
            if ($profile_stmt->execute()) {
                $profile_stmt->close();
                
                // Iniciar sesión automáticamente
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['specialization'] = $specialization;
                $_SESSION['logged_in'] = true;
                
                // Redirigir al dashboard
                header("Location: freelancer_panel.php?welcome=1");
                exit();
            } else {
                $profile_stmt->close();
                $_SESSION['error'] = "Error al crear el perfil. Por favor, intenta de nuevo.";
                header("Location: login.html?error=profile_creation_failed");
                exit();
            }
            
        } else {
            $stmt->close();
            $_SESSION['error'] = "Error al crear la cuenta. Por favor, intenta de nuevo.";
            header("Location: login.html?error=registration_failed");
            exit();
        }
        
    } else {
        // Si hay errores, guardarlos en sesión y redirigir
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Guardar datos del formulario para no perderlos
        header("Location: login.html?error=validation_failed");
        exit();
    }
    
} else {
    // Si no se enviaron datos POST, redirigir al formulario
    header("Location: login.html");
    exit();
}

$conn->close();
?>