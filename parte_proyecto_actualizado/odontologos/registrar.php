<?php
session_start();
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "clinica_odontologica";

$conexion = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensajeExito = "";
$mensajeError = "";

// Manejo del envío del formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_usuario   = isset($_POST["id_usuario"]) ? intval($_POST["id_usuario"]) : 0;
    $matricula    = isset($_POST["matricula"]) ? trim($_POST["matricula"]) : "";
    $especialidad = isset($_POST["especialidad"]) ? trim($_POST["especialidad"]) : "";

    if ($id_usuario <= 0 || $matricula === "") {
        $mensajeError = "Debe seleccionar un usuario doctor y escribir la matrícula.";
    } else {
        // Verificar que el usuario exista
        $stmtUsuario = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
        $stmtUsuario->bind_param("i", $id_usuario);
        $stmtUsuario->execute();
        $resultadoUsuario = $stmtUsuario->get_result();

        if ($resultadoUsuario->num_rows === 0) {
            $mensajeError = "El usuario seleccionado no existe.";
        } else {
            // Insertar en la tabla odontologos
            $stmtInsert = $conexion->prepare(
                "INSERT INTO odontologos (id_usuario, matricula, especialidad) VALUES (?, ?, ?)"
            );
            $stmtInsert->bind_param("iss", $id_usuario, $matricula, $especialidad);

            if ($stmtInsert->execute()) {
                $mensajeExito = "Odontólogo registrado correctamente.";
            } else {
                // Error típico: duplicado de matrícula o id_usuario ya usado
                if ($conexion->errno === 1062) {
                     $mensajeError = "La matrícula o el usuario ya están registrados como odontólogo.";
                } else {
                    $mensajeError = "Error al registrar odontólogo: " . $conexion->error;
                }
            }
            $stmtInsert->close();
        }

        $stmtUsuario->close();
    }
}

// Obtener usuarios con rol DOCTOR (id_rol = 3) para el combo
$doctores = [];
$sqlDoctores = "
    SELECT u.id_usuario, u.nombres, u.apellidos, r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r ON r.id_rol = u.id_rol
    WHERE r.nombre = 'DOCTOR'
";
$resultado = $conexion->query($sqlDoctores);
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $doctores[] = $fila;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Odontólogo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<?php $sidebar_base = '../'; $sidebar_carpeta = 'odontologos'; require_once __DIR__ . '/../panel/sidebar.php'; ?>
<div class="dashboard-main">
<div class="container py-4" style="max-width: 720px; margin-left: auto; margin-right: auto;">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #1E6F78; color: white;">
                    <h5 class="mb-0">Registrar Odontólogo</h5>
                </div>
                <div class="card-body">
                    <?php if ($mensajeExito): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($mensajeExito); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensajeError): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($mensajeError); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <div class="mb-3">
                            <label for="id_usuario" class="form-label">Usuario (Doctor)</label>
                            <select name="id_usuario" id="id_usuario" class="form-select" required>
                                <option value="">-- Seleccione un doctor --</option>
                                <?php foreach ($doctores as $doc): ?>
                                    <option value="<?php echo (int)$doc["id_usuario"]; ?>">
                                        <?php echo htmlspecialchars($doc["nombres"] . " " . $doc["apellidos"] . " (ID: " . $doc["id_usuario"] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Este usuario debe tener rol DOCTOR en la tabla <code>usuarios</code>.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="matricula" class="form-label">Matrícula</label>
                            <input type="text" name="matricula" id="matricula" class="form-control" required maxlength="50">
                        </div>

                        <div class="mb-3">
                            <label for="especialidad" class="form-label">Especialidad</label>
                            <input type="text" name="especialidad" id="especialidad" class="form-control" maxlength="80"
                                   placeholder="Ej: Odontopediatra, Cirujano, Ortodoncista, etc.">
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="submit" class="btn px-4 py-2" style="background-color: #1E6F78; color: white; border: none; min-width: 180px;">
                                Registrar odontólogo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conexion->close();
?>

