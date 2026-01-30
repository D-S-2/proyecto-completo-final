<?php
/**
 * Sidebar del dashboard - incluir en todas las páginas del proyecto.
 * Antes de incluir: session_start() ya debe estar llamado en la página.
 * Opcional: $sidebar_base = '../' si la página está en pagos/, cliente/, agendar_citas/, odontologos/
 *           $sidebar_base = '../../' si la página está en cliente/real_funcion/
 * Opcional: $sidebar_carpeta = 'pagos'|'cliente'|'agendar_citas'|'odontologos' cuando no estás en panel/
 */
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    $r = (isset($sidebar_base) && $sidebar_base !== '') ? $sidebar_base : '../';
    header("Location: " . $r . "Proyecto/Login.php");
    exit();
}
$base = isset($sidebar_base) ? $sidebar_base : '';
$carpeta = isset($sidebar_carpeta) ? $sidebar_carpeta : 'panel';
// Rutas según carpeta actual
$l = [
    'inicio'     => $base === '' ? 'Inicio.php' : $base . 'panel/Inicio.php',
    'crear_rol'  => $base === '' ? 'crear_rol.php' : $base . 'panel/crear_rol.php',
    'crear_usu'  => $base === '' ? 'crear_usuario.php' : $base . 'panel/crear_usuario.php',
    'pacientes'  => ($base === '' ? '../cliente/index.php' : ($carpeta === 'cliente' ? 'index.php' : $base . 'cliente/index.php')),
    'pagos'      => ($base === '' ? '../pagos/index.php' : ($carpeta === 'pagos' ? 'index.php' : $base . 'pagos/index.php')),
    'ver_citas'  => $base === '' ? '../agendar_citas/citas/calendario.php' : $base . 'agendar_citas/citas/calendario.php',
    'historial'  => $base === '' ? 'solovistahistorialatenciones.php' : $base . 'panel/solovistahistorialatenciones.php',
    'odontologos'=> $base === '' ? '../odontologos/registrar.php' : $base . 'odontologos/registrar.php',
    'atencion'   => $base === '' ? 'atencion_odontologia.php' : $base . 'panel/atencion_odontologia.php',
    'agendar'    => $base === '' ? '../agendar_citas/citas/nueva.php' : $base . 'agendar_citas/citas/nueva.php',
];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.dashboard-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    background: linear-gradient(135deg, #1E6F78, #2E8B8E);
    color: #fff;
    z-index: 1000;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    transition: all 0.3s ease;
}

/* Logo Section */
.sidebar-logo {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
}

.logo-container {
    width: 80px;
    height: 80px;
    margin: 0 auto 15px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.logo-container:hover {
    transform: scale(1.05);
    background: rgba(255, 255, 255, 0.15);
}

.logo-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 50%;
}

.sidebar-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
    color: #fff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.sidebar-subtitle {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 5px 0 0;
    font-weight: 400;
}

/* User Info */
.user-info {
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.05);
    margin: 0 15px 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.user-name {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0;
    color: #fff;
}

.user-role {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 3px 0 0;
    padding: 2px 8px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    display: inline-block;
}

/* Navigation */
.sidebar-nav {
    padding: 0 15px;
}

.nav-section {
    margin-bottom: 25px;
}

.nav-section-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.6);
    margin: 0 0 10px 15px;
    font-weight: 600;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 4px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 0;
    background: rgba(255, 255, 255, 0.1);
    transition: width 0.3s ease;
}

.nav-link:hover::before {
    width: 100%;
}

.nav-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.nav-link i {
    width: 20px;
    margin-right: 12px;
    font-size: 1rem;
    text-align: center;
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Logout Button */
.logout-section {
    padding: 20px 15px;
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 12px;
    background: rgba(220, 53, 69, 0.8);
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 600;
}

.logout-btn:hover {
    background: rgba(220, 53, 69, 1);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
}

.logout-btn i {
    margin-right: 8px;
}

/* Main Content */
.dashboard-main {
    margin-left: 280px;
    padding: 30px;
    min-height: 100vh;
    background: #f8f9fa;
    box-sizing: border-box;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-sidebar {
        width: 100%;
        transform: translateX(-100%);
    }
    
    .dashboard-sidebar.active {
        transform: translateX(0);
    }
    
    .dashboard-main {
        margin-left: 0;
        padding: 20px;
    }
}
</style>
<div class="dashboard-sidebar">
    <!-- Logo Section -->
    <div class="sidebar-logo">
        <div class="logo-container">
            <img src="../Proyecto/logoU.png" alt="Dr. Muelitas Logo">
        </div>
        <h2 class="sidebar-title">Dr. Muelitas</h2>
        <p class="sidebar-subtitle">Panel de Administración</p>
    </div>

    <!-- User Info -->
    <div class="user-info">
        <div class="user-name"><?php echo htmlspecialchars($_SESSION['NombreUsuario'] ?? 'Usuario'); ?></div>
        <div class="user-role"><?php echo htmlspecialchars($_SESSION['rol'] ?? 'SIN ROL'); ?></div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php if ($_SESSION['rol'] == 'ADMIN') { ?>
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['inicio']); ?>" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                </ul>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Administración</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['crear_rol']); ?>" class="nav-link"><i class="fas fa-user-shield"></i> Crear Rol</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['crear_usu']); ?>" class="nav-link"><i class="fas fa-user-plus"></i> Crear Usuario</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['odontologos']); ?>" class="nav-link"><i class="fas fa-user-md"></i> Registrar Odontólogo</a></li>
                </ul>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Gestión Clínica</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['pacientes']); ?>" class="nav-link"><i class="fas fa-users"></i> Ver Pacientes</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['ver_citas']); ?>" class="nav-link"><i class="fas fa-calendar-alt"></i> Ver Citas</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['agendar']); ?>" class="nav-link"><i class="fas fa-calendar-plus"></i> Agendar Citas</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['atencion']); ?>" class="nav-link"><i class="fas fa-tooth"></i> Atención Odontología</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['historial']); ?>" class="nav-link"><i class="fas fa-history"></i> Ver Historial</a></li>
                </ul>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Finanzas</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['pagos']); ?>" class="nav-link"><i class="fas fa-dollar-sign"></i> Pagos</a></li>
                </ul>
            </div>
            
        <?php } elseif ($_SESSION['rol'] == 'DOCTOR') { ?>
            <div class="nav-section">
                <div class="nav-section-title">Menú Médico</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['inicio']); ?>" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['pacientes']); ?>" class="nav-link"><i class="fas fa-users"></i> Ver Pacientes</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['ver_citas']); ?>" class="nav-link"><i class="fas fa-calendar-alt"></i> Ver Citas</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['atencion']); ?>" class="nav-link"><i class="fas fa-tooth"></i> Atención Odontología</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['historial']); ?>" class="nav-link"><i class="fas fa-history"></i> Ver Historial</a></li>
                </ul>
            </div>
            
        <?php } elseif ($_SESSION['rol'] == 'RECEPCIONISTA') { ?>
            <div class="nav-section">
                <div class="nav-section-title">Menú Recepción</div>
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['inicio']); ?>" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['pacientes']); ?>" class="nav-link"><i class="fas fa-users"></i> Pacientes</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['pagos']); ?>" class="nav-link"><i class="fas fa-dollar-sign"></i> Pagos</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['agendar']); ?>" class="nav-link"><i class="fas fa-calendar-plus"></i> Agendar Citas</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['ver_citas']); ?>" class="nav-link"><i class="fas fa-calendar-alt"></i> Ver Citas</a></li>
                    <li class="nav-item"><a href="<?php echo htmlspecialchars($l['odontologos']); ?>" class="nav-link"><i class="fas fa-user-md"></i> Odontólogos</a></li>
                </ul>
            </div>
        <?php } ?>
    </nav>

    <!-- Logout Section -->
    <div class="logout-section">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>
</div>
