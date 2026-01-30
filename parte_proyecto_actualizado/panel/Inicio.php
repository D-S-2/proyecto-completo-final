<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Consultorio Dental</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f0f0; }
        .card { margin: 20px; padding: 20px; border: none; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>
    <div class="dashboard-main">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="text-center mt-5">Bienvenido al Dashboard</h1>
            <div class="text-end">
                <span class="badge bg-secondary">Usuario: <?php echo $_SESSION['NombreUsuario']; ?> (<?php echo $_SESSION['rol']; ?>)</span>
                <a href="logout.php" class="btn btn-sm btn-danger ms-2">Cerrar sesión</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Resumen del día</h5>
                        <p>No hay información disponible.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

