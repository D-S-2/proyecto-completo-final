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

// Obtener roles de la tabla para el select
$roles = $pdo->query("SELECT id_rol, nombre FROM roles ORDER BY nombre")->fetchAll();

$mensaje = '';
$exito = false;
$errores = []; // Array para errores por campo
$valores = [
    'id_rol' => '',
    'usuario' => '',
    'password' => '',
    'nombres' => '',
    'apellidos' => '',
    'email' => '',
    'telefono' => '',
    'activo' => '1'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['id_rol']     = $_POST['id_rol'] ?? '';
    $valores['usuario']    = trim($_POST['usuario'] ?? '');
    $valores['password']   = $_POST['password'] ?? '';
    $valores['nombres']    = trim($_POST['nombres'] ?? '');
    $valores['apellidos']  = trim($_POST['apellidos'] ?? '');
    $valores['email']      = trim($_POST['email'] ?? '');
    $valores['telefono']   = trim($_POST['telefono'] ?? '');
    $valores['activo']     = $_POST['activo'] ?? '1';

    // Validaciones
    if (!$valores['id_rol']) { $errores['id_rol'] = 'Seleccione un rol'; }
    if (!$valores['usuario']) { $errores['usuario'] = 'Ingrese un usuario'; }
    if (!$valores['password']) { $errores['password'] = 'Ingrese una contraseña'; }
    if (!$valores['nombres']) { $errores['nombres'] = 'Ingrese los nombres'; }
    if (!$valores['apellidos']) { $errores['apellidos'] = 'Ingrese los apellidos'; }
    if ($valores['email'] && !filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) { $errores['email'] = 'Email no válido'; }
    if ($valores['telefono'] && !preg_match('/^[0-9+\-\s]*$/', $valores['telefono'])) { $errores['telefono'] = 'Teléfono no válido'; }

    if (empty($errores)) {
        // Verificar usuario duplicado
        $verificar = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
        $verificar->execute([$valores['usuario']]);
        if ($verificar->fetch()) { $errores['usuario'] = 'El usuario ya existe'; }
    }

    if (empty($errores)) {
        // Guardar la contraseña tal cual se ingresó (NO SEGURA)
        $passwordPlain = $valores['password'];

        // Insertar en la base de datos
        $sql = "INSERT INTO usuarios 
            (id_rol, usuario, password_hash, nombres, apellidos, email, telefono, activo, creado_en)
            VALUES (?,?,?,?,?,?,?,?,NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $valores['id_rol'],
            $valores['usuario'],
            $passwordPlain,  // Guardamos la contraseña tal cual
            $valores['nombres'],
            $valores['apellidos'],
            $valores['email'] ?: null,
            $valores['telefono'] ?: null,
            $valores['activo']
        ]);

        $mensaje = '✅ Usuario creado correctamente';
        $exito = true;

        // Limpiar campos
        $valores = [
            'id_rol' => '',
            'usuario' => '',
            'password' => '',
            'nombres' => '',
            'apellidos' => '',
            'email' => '',
            'telefono' => '',
            'activo' => '1'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Usuario</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<style>
body { font-family: Arial, sans-serif; background: #f0f2f5; }
.dashboard-main .container {
    background: #fff;
    width: 100%;
    max-width: 640px;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    margin: 0 auto;
    box-sizing: border-box;
}
h2 { text-align: center; margin-bottom: 20px; color: #f8f8f8fffffff; }
.mensaje { padding: 10px; margin-bottom: 20px; border-radius: 6px; font-weight: bold; text-align: center; }
.error-msg { color: #721c24; font-size: 0.9em; margin-top: 3px; }
.error-border { border-color: red !important; }
.exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
form { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
form label { display: flex; flex-direction: column; font-weight: 600; color: #555; gap: 4px; }
form input, form select { padding: 10px 12px; border-radius: 6px; border: 1px solid #ccc; box-sizing: border-box; width: 100%; }
form input:focus, form select:focus { outline: none; border-color: #007bff; box-shadow: 0 0 5px rgba(0,123,255,0.3); }
.full-width { grid-column: 1 / -1; }
.dashboard-main form button {
    grid-column: 1 / -1;
    padding: 12px 24px;
    margin-top: 10px;
    min-width: 160px;
    width: auto;
    max-width: 100%;
    background-color: #1E6F78;
    border: none;
    color: white;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
    justify-self: start;
}
.dashboard-main form button:hover { background-color: #0056b3; }
</style>
</head>
<body>
<?php require_once __DIR__ . '/sidebar.php'; ?>
<div class="dashboard-main">
<div class="container">
<h2>Crear Usuario</h2>


<?php if ($mensaje): ?>
    <div class="mensaje <?= $exito ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<form method="POST">
    <label>Rol:
        <select name="id_rol" <?= isset($errores['id_rol']) ? 'class="error-border"' : '' ?> required>
            <option value="">Seleccione un rol</option>
            <?php foreach($roles as $rol): ?>
                <option value="<?= $rol['id_rol'] ?>" <?= ($valores['id_rol']==$rol['id_rol'])?'selected':'' ?>>
                    <?= htmlspecialchars($rol['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if(isset($errores['id_rol'])): ?><span class="error-msg"><?= $errores['id_rol'] ?></span><?php endif; ?>
    </label>

    <label>Usuario:
        <input type="text" name="usuario" value="<?= htmlspecialchars($valores['usuario']) ?>" <?= isset($errores['usuario']) ? 'class="error-border"' : '' ?> required>
        <?php if(isset($errores['usuario'])): ?><span class="error-msg"><?= $errores['usuario'] ?></span><?php endif; ?>
    </label>

    <label>Contraseña:
        <input type="text" name="password" value="<?= htmlspecialchars($valores['password']) ?>" <?= isset($errores['password']) ? 'class="error-border"' : '' ?> required>
        <?php if(isset($errores['password'])): ?><span class="error-msg"><?= $errores['password'] ?></span><?php endif; ?>
    </label>

    <label>Nombres:
        <input type="text" name="nombres" value="<?= htmlspecialchars($valores['nombres']) ?>" <?= isset($errores['nombres']) ? 'class="error-border"' : '' ?> required>
        <?php if(isset($errores['nombres'])): ?><span class="error-msg"><?= $errores['nombres'] ?></span><?php endif; ?>
    </label>

    <label>Apellidos:
        <input type="text" name="apellidos" value="<?= htmlspecialchars($valores['apellidos']) ?>" <?= isset($errores['apellidos']) ? 'class="error-border"' : '' ?> required>
        <?php if(isset($errores['apellidos'])): ?><span class="error-msg"><?= $errores['apellidos'] ?></span><?php endif; ?>
    </label>

    <label>Email:
        <input type="email" name="email" value="<?= htmlspecialchars($valores['email']) ?>" <?= isset($errores['email']) ? 'class="error-border"' : '' ?>>
        <?php if(isset($errores['email'])): ?><span class="error-msg"><?= $errores['email'] ?></span><?php endif; ?>
    </label>

    <label>Teléfono:
        <input type="text" name="telefono" value="<?= htmlspecialchars($valores['telefono']) ?>" <?= isset($errores['telefono']) ? 'class="error-border"' : '' ?>>
        <?php if(isset($errores['telefono'])): ?><span class="error-msg"><?= $errores['telefono'] ?></span><?php endif; ?>
    </label>

    <label class="full-width">Activo:
        <select name="activo">
            <option value="1" <?= ($valores['activo']=='1')?'selected':'' ?>>Sí</option>
            <option value="0" <?= ($valores['activo']=='0')?'selected':'' ?>>No</option>
        </select>
    </label>

    <button type="submit">Crear Usuario</button>
</form>
</div>
</div>
</body>
</html>
