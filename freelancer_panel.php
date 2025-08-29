<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit();
}

require 'conexion.php';

$user_id = $_SESSION['user_id'];

// Obtener datos completos del usuario y perfil
$sql = "SELECT u.*, fp.* 
        FROM users u 
        LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id 
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Preparar variables para el formulario
$full_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
$email = $user_data['email'];
$phone = $user_data['phone'] ?? '';
$specialization = $user_data['specialization'] ?? '';
$experience = $user_data['experience'] ?? '';
$bio = $user_data['bio'] ?? '';
$hourly_rate = $user_data['hourly_rate'] ?? 25;
$skills = $user_data['skills'] ?? '';
$languages = $user_data['languages'] ?? '';
$location = $user_data['location'] ?? 'Lima, Perú';
$linkedin_url = $user_data['linkedin_url'] ?? '';
$portfolio_url = $user_data['portfolio_url'] ?? '';
$professional_title = $user_data['professional_title'] ?? '';
$profile_photo = $user_data['profile_photo'] ?? './images/default-avatar.png';

// Obtener mensajes de la URL
$success_message = '';
$error_message = '';
$active_tab = $_GET['tab'] ?? 'profile';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Perfil actualizado exitosamente';
}
if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carreras - Plataforma de Freelancers</title>
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="icon" href="./images/hanan.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <link href="lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <script src="./js/main.js"></script>
</head>
<body>
    <!-- Header Navigation -->
    <header class="header">
        <a href="#" class="logo"><img src="./images/hanan.png" alt="Logo de la empresa"></a>
        <div class="fas fa-bars"></div>
        <nav class="navbar">
            <ul>
                <li><a href="index.html#home">Inicio</a></li>
                <li><a href="index.html#about">Nosotros</a></li>
                <li><a href="index.html#service">Servicios</a></li>
                <li><a href="index.html#portfolio">Portafolio</a></li>
                <li><a href="index.html#team">Equipo</a></li>
                <li><a href="freelancer_panel.php" class="active">Mi Panel</a></li>
                <li><a href="index.html#contact">Contacto</a></li>
                <li><a href="index.html#faq">Preguntas Frecuentes</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content Container -->
    <div class="career-container" style="margin-top: 100px;">
        <!-- Freelancer Dashboard -->
        <div id="freelancer-dashboard" class="dashboard">
            <div class="dashboard-header">
                <h2><i class="fas fa-tachometer-alt"></i> Panel de Freelancer</h2>
                <div class="user-info">
                    <span>Bienvenido, <strong><?php echo htmlspecialchars($full_name); ?></strong></span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>

            <!-- Mensajes de alerta -->
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="close" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="close" onclick="this.parentElement.style.display='none';">&times;</button>
            </div>
            <?php endif; ?>

            <div class="dashboard-tabs">
                <button class="tab-btn <?php echo $active_tab == 'profile' ? 'active' : ''; ?>" onclick="showTab('profile')">
                    <i class="fas fa-user"></i> Mi Perfil
                </button>
                <button class="tab-btn <?php echo $active_tab == 'cv' ? 'active' : ''; ?>" onclick="showTab('cv')">
                    <i class="fas fa-file-alt"></i> Mi CV
                </button>
                <button class="tab-btn <?php echo $active_tab == 'projects' ? 'active' : ''; ?>" onclick="showTab('projects')">
                    <i class="fas fa-briefcase"></i> Proyectos
                </button>
                <button class="tab-btn <?php echo $active_tab == 'settings' ? 'active' : ''; ?>" onclick="showTab('settings')">
                    <i class="fas fa-cog"></i> Configuración
                </button>
            </div>

            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content <?php echo $active_tab == 'profile' ? 'active' : ''; ?>">
                <div class="profile-section">
                    <h3><i class="fas fa-user-edit"></i> Editar Perfil</h3>
                    <form action="./update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="profile-photo">
                            <div class="photo-container">
                                <img id="profile-image" src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Foto de perfil">
                                <div class="photo-overlay">
                                    <i class="fas fa-camera"></i>
                                </div>
                            </div>
                            <input type="file" id="photo-upload" name="profile_photo" accept="image/*" style="display: none;">
                            <button type="button" onclick="document.getElementById('photo-upload').click()" class="photo-btn">
                                <i class="fas fa-camera"></i> Cambiar Foto
                            </button>
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="profile-name">
                                    <i class="fas fa-user"></i> Nombre Completo
                                </label>
                                <input type="text" id="profile-name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            <div class="form-group half">
                                <label for="profile-title">
                                    <i class="fas fa-briefcase"></i> Título Profesional
                                </label>
                                <input type="text" id="profile-title" name="professional_title" value="<?php echo htmlspecialchars($professional_title); ?>" placeholder="Ej: Desarrollador Web Full Stack" required>
                            </div>
                        </div>

                        <!-- Campo de email solo lectura -->
                        <div class="form-group">
                            <label for="profile-email">
                                <i class="fas fa-envelope"></i> Correo Electrónico (no editable)
                            </label>
                            <input type="email" id="profile-email" value="<?php echo htmlspecialchars($email); ?>" readonly style="background-color: #f0f0f0;">
                        </div>

                        <div class="form-group">
                            <label for="profile-bio">
                                <i class="fas fa-info-circle"></i> Biografía Profesional
                            </label>
                            <textarea id="profile-bio" name="bio" rows="4" placeholder="Cuéntanos sobre tu experiencia, habilidades y objetivos profesionales..." required><?php echo htmlspecialchars($bio); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="profile-rate">
                                    <i class="fas fa-dollar-sign"></i> Tarifa por Hora (USD)
                                </label>
                                <input type="number" id="profile-rate" name="hourly_rate" value="<?php echo htmlspecialchars($hourly_rate); ?>" min="5" max="500" required>
                            </div>
                            <div class="form-group half">
                                <label for="profile-location">
                                    <i class="fas fa-map-marker-alt"></i> Ubicación
                                </label>
                                <input type="text" id="profile-location" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profile-skills">
                                <i class="fas fa-code"></i> Habilidades Principales
                            </label>
                            <input type="text" id="profile-skills" name="skills" value="<?php echo htmlspecialchars($skills); ?>" placeholder="Separa las habilidades con comas" required>
                        </div>

                        <div class="form-group">
                            <label for="profile-languages">
                                <i class="fas fa-language"></i> Idiomas
                            </label>
                            <input type="text" id="profile-languages" name="languages" value="<?php echo htmlspecialchars($languages); ?>" placeholder="Ejemplo: Español (Nativo), Inglés (Intermedio)" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="profile-linkedin">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </label>
                                <input type="url" id="profile-linkedin" name="linkedin" value="<?php echo htmlspecialchars($linkedin_url); ?>" placeholder="https://linkedin.com/in/tuperfil">
                            </div>
                            <div class="form-group half">
                                <label for="profile-portfolio">
                                    <i class="fas fa-globe"></i> Portafolio Web
                                </label>
                                <input type="url" id="profile-portfolio" name="portfolio" value="<?php echo htmlspecialchars($portfolio_url); ?>" placeholder="https://tuportafolio.com">
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>

            <?php
            // Obtener CV actual del usuario
            $cv_sql = "SELECT * FROM cv_files WHERE user_id = ? AND is_active = 1 ORDER BY uploaded_at DESC LIMIT 1";
            $cv_stmt = $conn->prepare($cv_sql);
            $cv_stmt->bind_param("i", $user_id);
            $cv_stmt->execute();
            $cv_result = $cv_stmt->get_result();
            $current_cv = $cv_result->fetch_assoc();
            $cv_stmt->close();
            ?>

            <!-- CV Tab -->
            <div id="cv-tab" class="tab-content <?php echo $active_tab == 'cv' ? 'active' : ''; ?>">
                <div class="cv-section">
                    <h3><i class="fas fa-file-alt"></i> Gestión de CV</h3>
                    
                    <!-- Formulario de subida -->
                    <div class="cv-upload-area">
                        <form id="cv-upload-form" action="upload_cv.php" method="POST" enctype="multipart/form-data">
                            <div class="upload-zone" id="cv-upload-zone">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h4>Arrastra tu CV aquí o haz clic para seleccionar</h4>
                                <p>Formatos aceptados: PDF, DOC, DOCX (Máximo 5MB)</p>
                                <input type="file" id="cv-upload" name="cv_file" accept=".pdf,.doc,.docx" style="display: none;" required>
                            </div>
                            <button type="submit" class="submit-btn" style="margin-top: 15px;">
                                <i class="fas fa-upload"></i> Subir CV
                            </button>
                        </form>
                    </div>

                    <!-- CV Actual -->
                    <?php if ($current_cv): ?>
                    <div class="cv-current" id="cv-current">
                        <h4><i class="fas fa-file-pdf"></i> CV Actual</h4>
                        <div class="cv-item">
                            <div class="cv-info">
                                <span class="cv-name"><?php echo htmlspecialchars($current_cv['original_name']); ?></span>
                                <span class="cv-date">Subido: <?php echo date('d/m/Y H:i', strtotime($current_cv['uploaded_at'])); ?></span>
                                <span class="cv-size"><?php echo round($current_cv['file_size'] / 1024, 2); ?> KB</span>
                            </div>
                            <div class="cv-actions">
                                <button class="cv-btn view" onclick="viewCV('<?php echo htmlspecialchars($current_cv['file_path']); ?>')">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                <button class="cv-btn download" onclick="downloadCV('<?php echo htmlspecialchars($current_cv['file_path']); ?>', '<?php echo htmlspecialchars($current_cv['original_name']); ?>')">
                                    <i class="fas fa-download"></i> Descargar
                                </button>
                                <button class="cv-btn delete" onclick="deleteCV(<?php echo $current_cv['id']; ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="cv-current" id="cv-current">
                        <p style="text-align: center; padding: 20px; color: #666;">
                            <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                            No tienes ningún CV subido aún.
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="cv-templates">
                        <h4><i class="fas fa-file-alt"></i> Plantillas de CV (OPCIONES EN TRABAJO)</h4>
                        <p>Crea un CV profesional usando nuestras plantillas prediseñadas</p>
                        <div class="template-grid">
                            <div class="template-card">
                                <div class="template-preview">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h5>Plantilla Moderna</h5>
                                <button class="template-btn">
                                    <i class="fas fa-plus"></i> Usar Plantilla
                                </button>
                            </div>
                            <div class="template-card">
                                <div class="template-preview">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h5>Plantilla Clásica</h5>
                                <button class="template-btn">
                                    <i class="fas fa-plus"></i> Usar Plantilla
                                </button>
                            </div>
                            <div class="template-card">
                                <div class="template-preview">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h5>Plantilla Creativa</h5>
                                <button class="template-btn">
                                    <i class="fas fa-plus"></i> Usar Plantilla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Tab -->
            <div id="projects-tab" class="tab-content">
                <div class="projects-section">
                    <h3><i class="fas fa-briefcase"></i> Mis Proyectos (OPCIONES EN TRABAJO) </h3>
                    <div class="projects-header">
                        <div class="projects-stats">
                            <div class="stat-item">
                                <span class="stat-number">12</span>
                                <span class="stat-label">Proyectos Completados</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">3</span>
                                <span class="stat-label">En Progreso</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">4.8</span>
                                <span class="stat-label">Calificación Promedio</span>
                            </div>
                        </div>
                        <button class="add-project-btn" onclick="showAddProject()">
                            <i class="fas fa-plus"></i> Agregar Proyecto
                        </button>
                    </div>

                    <div class="projects-grid">
                        <div class="project-card">
                            <div class="project-header">
                                <h4>E-commerce Platform</h4>
                                <span class="project-status completed">Completado</span>
                            </div>
                            <p class="project-description">Desarrollo de plataforma de comercio electrónico con React y Node.js</p>
                            <div class="project-details">
                                <span class="project-client"><i class="fas fa-user"></i> TechCorp</span>
                                <span class="project-date"><i class="fas fa-calendar"></i> Dic 2023</span>
                                <span class="project-rate"><i class="fas fa-dollar-sign"></i> $2,500</span>
                            </div>
                            <div class="project-actions">
                                <button class="project-btn">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </button>
                                <button class="project-btn">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                        </div>

                        <div class="project-card">
                            <div class="project-header">
                                <h4>Mobile App Design</h4>
                                <span class="project-status in-progress">En Progreso</span>
                            </div>
                            <p class="project-description">Diseño UI/UX para aplicación móvil de fitness</p>
                            <div class="project-details">
                                <span class="project-client"><i class="fas fa-user"></i> FitLife</span>
                                <span class="project-date"><i class="fas fa-calendar"></i> Ene 2024</span>
                                <span class="project-rate"><i class="fas fa-dollar-sign"></i> $1,800</span>
                            </div>
                            <div class="project-actions">
                                <button class="project-btn">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </button>
                                <button class="project-btn">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                        </div>

                        <div class="project-card">
                            <div class="project-header">
                                <h4>Corporate Website</h4>
                                <span class="project-status completed">Completado</span>
                            </div>
                            <p class="project-description">Sitio web corporativo con CMS personalizado</p>
                            <div class="project-details">
                                <span class="project-client"><i class="fas fa-user"></i> BusinessPro</span>
                                <span class="project-date"><i class="fas fa-calendar"></i> Nov 2023</span>
                                <span class="project-rate"><i class="fas fa-dollar-sign"></i> $3,200</span>
                            </div>
                            <div class="project-actions">
                                <button class="project-btn">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </button>
                                <button class="project-btn">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settings-tab" class="tab-content">
                <div class="settings-section">
                    <h3><i class="fas fa-cog"></i> Configuración de Cuenta</h3>
                    
                    <div class="settings-group">
                        <h4><i class="fas fa-bell"></i> Notificaciones</h4>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" checked> Notificaciones por email
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" checked> Nuevas ofertas de trabajo
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox"> Mensajes de clientes
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox"> Recordatorios de proyectos
                            </label>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h4><i class="fas fa-eye"></i> Privacidad</h4>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" checked> Perfil público
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox"> Mostrar tarifa por hora
                            </label>
                        </div>
                        <div class="setting-item">
                            <label class="setting-label">
                                <input type="checkbox" checked> Permitir contacto directo
                            </label>
                        </div>
                    </div>

                    <div class="settings-group">
                        <h4><i class="fas fa-lock"></i> Seguridad</h4>
                        <div class="setting-item">
                            <button class="setting-btn">
                                <i class="fas fa-key"></i> Cambiar Contraseña
                            </button>
                        </div>
                        <div class="setting-item">
                            <button class="setting-btn">
                                <i class="fas fa-shield-alt"></i> Activar 2FA
                            </button>
                        </div>
                        <div class="setting-item">
                            <button class="setting-btn">
                                <i class="fas fa-download"></i> Descargar mis datos
                            </button>
                        </div>
                    </div>

                    <div class="settings-group danger">
                        <h4><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h4>
                        <div class="setting-item">
                            <button class="setting-btn danger">
                                <i class="fas fa-user-times"></i> Desactivar Cuenta
                            </button>
                        </div>
                        <div class="setting-item">
                            <button class="setting-btn danger">
                                <i class="fas fa-trash"></i> Eliminar Cuenta
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

<!-- Footer -->
    <div class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3 col-md-6 footer-links">
                        <h4>Acerca de Nosotros</h4>
                        <ul>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#home">Inicio</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#about">Sobre nosotros</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#service">Nuestros servicios</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#home">Términos y condiciones</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#home">Política de privacidad</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-3 col-md-6 footer-links">
                        <h4>Enlaces Útiles</h4>
                        <ul>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#portfolio">Portafolio</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#team">Equipo</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="login.html">Freelancers </a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#contact">Contacto</a></li>
                            <li><i class="ion-ios-arrow-forward"></i> <a href="index.html#faq">Preguntas Frecuentes</a></li>
                        </ul>
                    </div>

                    <div class="col-lg-3 col-md-6 footer-contact" style="font-size: 1.5rem;">
                        <h4>Contáctanos</h4>
                        <p>
                            Av. Libertadores 1234<br>
                            Lima, Perú<br>
                            <strong>Teléfono:</strong> +51 123-456-7890<br>
                            <strong>Email:</strong> info@empresatech.com<br>
                        </p>

                        <div class="social-links">
                            <a href="https://www.facebook.com/" aria-label="Facebook"><i class="ion-logo-facebook"></i></a>
                            <a href="https://twitter.com/login?lang=es" aria-label="Twitter"><i class="ion-logo-twitter"></i></a>
                            <a href="https://www.linkedin.com/" aria-label="LinkedIn"><i class="ion-logo-linkedin"></i></a>
                            <a href="https://www.instagram.com/" aria-label="Instagram"><i class="ion-logo-instagram"></i></a>
                            <a href="https://accounts.google.com/servicelogin" aria-label="Google"><i class="ion-logo-googleplus"></i></a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 footer-newsletter">
                        <h4>Suscripción</h4>
                        <p>Con nuestras habilidades combinadas, obtienes un conjunto capaz de hacer todo lo que tu marca necesita. Suscríbete aquí para recibir nuestras últimas actualizaciones.</p>
                        <form action="" method="post">
                            <input type="email" name="email" placeholder="Tu email" required>
                            <input type="submit" value="Suscribirse">
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 copyright" style="color: #fff; font-size: 1.3rem;">
                    Copyright &copy; 2024 Empresa de Tecnología. Todos los Derechos Reservados.
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top"><i class="ion-ios-arrow-up"></i></a>

    <!-- Additional Scripts -->
    <script src="lib/jquery/jquery.min.js"></script>
    <script src="lib/jquery/jquery-migrate.min.js"></script>
    <script src="lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>

    <script>
        // Función para cambiar tabs del dashboard 
        function showTab(tabName) {
            // Ocultar todos los contenidos de tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover clase active de todos los botones
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar el tab seleccionado
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activar el botón correspondiente
            event.target.classList.add('active');
        }

        // Funciones para CV
        function viewCV(filePath) {
            if (filePath) {
                window.open(filePath, '_blank');
            } else {
                alert('Abriendo CV en una nueva ventana...');
            }
        }

        function downloadCV(filePath, originalName) {
            if (filePath && originalName) {
                const link = document.createElement('a');
                link.href = filePath;
                link.download = originalName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('Descargando CV...');
            }
        }

        function deleteCV(cvId) {
            if (confirm('¿Estás seguro de que quieres eliminar tu CV?')) {
                if (cvId) {
                    const formData = new FormData();
                    formData.append('cv_id', cvId);
                    
                    fetch('delete_cv.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('CV eliminado exitosamente');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar el CV');
                    });
                } else {
                    alert('CV eliminado exitosamente');
                }
            }
        }

        // Función para agregar proyecto
        function showAddProject() {
            alert('Abriendo formulario para agregar nuevo proyecto...');
        }

        // Manejo de upload de CV y foto de perfil
        document.addEventListener('DOMContentLoaded', function() {
            // Variables para controlar el estado de upload
            let isUploadingCV = false;
            
            // Elementos del CV
            const cvUploadZone = document.getElementById('cv-upload-zone');
            const cvUpload = document.getElementById('cv-upload');
            const cvForm = document.getElementById('cv-upload-form');
            
            // Elementos de foto de perfil
            const photoUpload = document.getElementById('photo-upload');
            const profileImage = document.getElementById('profile-image');

            // === MANEJO DE CV ===
            if (cvUploadZone && cvUpload && cvForm) {
                
                // Prevenir envío automático del formulario
                cvForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (isUploadingCV) {
                        return false;
                    }
                    
                    const fileInput = document.getElementById('cv-upload');
                    if (!fileInput.files || fileInput.files.length === 0) {
                        alert('Por favor selecciona un archivo CV.');
                        return false;
                    }
                    
                    handleCVUpload();
                    return false;
                });
                
                // Clic en la zona de upload
                cvUploadZone.addEventListener('click', function(e) {
                    if (e.target !== cvUpload && !isUploadingCV) {
                        cvUpload.click();
                    }
                });
                
                // Cambio de archivo
                cvUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validaciones básicas
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        
                        if (file.size > maxSize) {
                            alert('El archivo es demasiado grande. Máximo 5MB.');
                            cvUpload.value = '';
                            resetCVUploadZone();
                            return;
                        }
                        
                        const extension = file.name.split('.').pop().toLowerCase();
                        if (!['pdf', 'doc', 'docx'].includes(extension)) {
                            alert('Tipo de archivo no permitido. Solo PDF, DOC y DOCX.');
                            cvUpload.value = '';
                            resetCVUploadZone();
                            return;
                        }
                        
                        // Mostrar nombre del archivo seleccionado
                        updateCVUploadZone(file.name);
                    }
                });
                
                // Drag and drop para CV
                cvUploadZone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (!isUploadingCV) {
                        cvUploadZone.style.backgroundColor = '#e8f4f8';
                        cvUploadZone.style.borderColor = '#007bff';
                    }
                });
                
                cvUploadZone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    if (!isUploadingCV) {
                        resetCVUploadZoneStyle();
                    }
                });
                
                cvUploadZone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    resetCVUploadZoneStyle();
                    
                    if (isUploadingCV) return;
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        cvUpload.files = files;
                        cvUpload.dispatchEvent(new Event('change'));
                    }
                });
            }

            // === MANEJO DE FOTO DE PERFIL ===
            if (photoUpload && profileImage) {
                photoUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            profileImage.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // === FUNCIONES AUXILIARES PARA CV ===
            function handleCVUpload() {
                if (isUploadingCV) return;
                
                isUploadingCV = true;
                const submitBtn = cvForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Cambiar estado del botón
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
                submitBtn.disabled = true;
                
                // Cambiar zona de upload
                updateCVUploadZone('Subiendo archivo...', true);
                
                const formData = new FormData(cvForm);
                
                fetch('upload_cv.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('CV subido exitosamente');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + data.message);
                        resetAfterCVUpload(submitBtn, originalText);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al subir el archivo: ' + error.message);
                    resetAfterCVUpload(submitBtn, originalText);
                });
            }
            
            function resetAfterCVUpload(submitBtn, originalText) {
                isUploadingCV = false;
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                cvUpload.value = '';
                resetCVUploadZone();
            }
            
            function updateCVUploadZone(fileName, isUploading = false) {
                const nameElement = cvUploadZone.querySelector('h4');
                if (isUploading) {
                    nameElement.textContent = fileName;
                    cvUploadZone.style.backgroundColor = '#fff3cd';
                    cvUploadZone.style.borderColor = '#ffc107';
                } else {
                    nameElement.textContent = `Archivo seleccionado: ${fileName}`;
                    cvUploadZone.style.backgroundColor = '#d4edda';
                    cvUploadZone.style.borderColor = '#28a745';
                }
            }
            
            function resetCVUploadZone() {
                const nameElement = cvUploadZone.querySelector('h4');
                nameElement.textContent = 'Arrastra tu CV aquí o haz clic para seleccionar';
                resetCVUploadZoneStyle();
            }
            
            function resetCVUploadZoneStyle() {
                cvUploadZone.style.backgroundColor = '';
                cvUploadZone.style.borderColor = '';
            }

            // Activar el tab correcto si viene de un parámetro
            <?php if ($active_tab && $active_tab != 'profile'): ?>
            const activeTab = '<?php echo $active_tab; ?>';
            // Buscar el botón correspondiente y hacer clic
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.textContent.toLowerCase().includes(activeTab)) {
                    btn.click();
                }
            });
            <?php endif; ?>
        });
    </script>