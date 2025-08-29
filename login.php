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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    
    // Obtener y limpiar los datos del formulario
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // No sanitizar la contraseña para mantener caracteres especiales
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Formato de email inválido";
        header("Location: login.html");
        exit();
    }
    
    // Preparar consulta SQL para evitar inyección SQL
    // Removemos profile_photo si la columna no existe
    $sql = "SELECT id, first_name, last_name, email, password, specialization FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    // Verificar si prepare() fue exitoso
    if ($stmt === false) {
        die("Error en prepare (login): " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['specialization'] = $user['specialization'];
            $_SESSION['profile_photo'] = null; // Por defecto null hasta que agregues la columna
            $_SESSION['logged_in'] = true;
            
            // Si seleccionó "Recordarme", crear una cookie
            if ($remember) {
                $cookie_name = "user_login";
                $cookie_value = $user['id'];
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); // Cookie válida por 30 días
            }
            
            // Registrar el login en la base de datos (solo si la tabla existe)
            $login_time = date("Y-m-d H:i:s");
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO login_logs (user_id, login_time, ip_address) VALUES (?, ?, ?)";
            $log_stmt = $conn->prepare($log_sql);
            
            if ($log_stmt !== false) {
                $log_stmt->bind_param("iss", $user['id'], $login_time, $ip_address);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            // Redirigir al panel de freelancer
            header("Location: freelancer_panel.php");
            exit();
            
        } else {
            // Contraseña incorrecta
            $_SESSION['error'] = "Contraseña incorrecta";
            header("Location: login.html?error=invalid_password");
            exit();
        }
    } else {
        // Usuario no encontrado
        $_SESSION['error'] = "No existe una cuenta con ese email";
        header("Location: login.html?error=user_not_found");
        exit();
    }
    
    $stmt->close();
    
} else {
    // Si no se enviaron datos POST, redirigir al formulario de login
    header("Location: login.html");
    exit();
}

$conn->close();
?>