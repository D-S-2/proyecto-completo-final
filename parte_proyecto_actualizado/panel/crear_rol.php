<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=clinica_odontologica;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

$mensaje = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

    // Validación simple
    if (!$nombre) {
        $mensaje = '❌ Por favor ingrese un nombre de rol';
    } else {
        // Verificar si el rol ya existe
        $verificar = $pdo->prepare("SELECT id_rol FROM roles WHERE nombre = ?");
        $verificar->execute([$nombre]);

        if ($verificar->fetch()) {
            $mensaje = '❌ Este rol ya existe';
        } else {
            // Insertar nuevo rol
            $sql = "INSERT INTO roles (nombre) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre]);

            $mensaje = '✅ Rol creado correctamente';
            $exito = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Rol</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<style>
    body { font-family: Arial, sans-serif; background: #f0f2f5; }
    .dashboard-main .container {
        background: #fff;
        width: 100%;
        max-width: 420px;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        margin: 0 auto;
        box-sizing: border-box;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: white;
    }

    .mensaje {
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 6px;
        font-weight: bold;
        text-align: center;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .exito {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    form label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #555;
    }

    form input {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
        box-sizing: border-box;
        margin-bottom: 15px;
    }

    form input:focus {
        outline: none;
        border-color: #1E6F78;
        box-shadow: 0 0 5px rgba(0,123,255,0.3);
    }

    button {
        width: 100%;
        min-width: 140px;
        padding: 12px 24px;
        margin-top: 8px;
        background-color: #1E6F78;
        border: none;
        color: white;
        font-weight: bold;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s;
        box-sizing: border-box;
    }

    button:hover {
        background-color: #0056b3;
    }
</style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>
<div class="dashboard-main">
<div class="container">
<h2>Crear Nuevo Rol</h2>

<?php if ($mensaje): ?>
    <div class="mensaje <?= $exito ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<form method="POST">
    <label>Nombre del Rol:</label>
    <input type="text" name="nombre" required>
    <button type="submit">Crear Rol</button>
</form>

</div>
</div>
</body>
</html>
