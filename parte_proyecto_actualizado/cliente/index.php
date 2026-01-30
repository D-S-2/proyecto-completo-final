<?php session_start(); $sidebar_base = '../'; $sidebar_carpeta = 'cliente'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. Muelitas | Gestión de Pacientes</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root { 
            /* Paleta ProClinic Nature */
            --primary-color: #006d77;      /* Verde Esmeralda */
            --primary-light: #83c5be;      /* Verde Menta Suave */
            --primary-dark: #004445;       /* Verde Bosque */
            --bg-body: #f8f9fa;            /* Blanco Humo */
            --text-main: #2d3436;          /* Gris Carbón */
            --accent-green: #e0f2f1;       /* Verde Glaciar */
            --female-color: #2a9d8f;       /* Verde Turquesa */
        }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main);
        }

        .main-container { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Card Personalizada con estilo Nature */
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 109, 119, 0.06);
            background: #fff;
            transition: transform 0.3s ease;
        }

        .card-custom:hover {
            transform: translateY(-5px);
        }

        /* Botones ProClinic */
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            box-shadow: 0 8px 20px rgba(0, 109, 119, 0.25);
            transform: translateY(-2px);
        }

        /* Botones de Acción en Tabla */
        .btn-action {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.9rem;
        }

        .btn-action:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 5px 12px rgba(0,0,0,0.1);
        }

        .bg-primary-subtle { background-color: var(--accent-green) !important; }
        .bg-female-subtle { background-color: #f0fdfa !important; }
        .text-primary { color: var(--primary-color) !important; }
        .text-female { color: var(--female-color) !important; }

        /* Estados de Hover de acciones */
        .btn-action.text-primary:hover { background-color: var(--primary-color) !important; color: white !important; }
        .btn-action.text-info:hover { background-color: var(--primary-color) !important; color: white !important; }
        .btn-action.text-secondary:hover { background-color: var(--primary-dark) !important; color: white !important; }

        /* Personalización de DataTable */
        .dataTables_filter input { 
            width: 100% !important; 
            height: 55px; 
            border-radius: 18px !important; 
            padding: 10px 20px 10px 50px !important;
            border: 2px solid #edf2f4 !important;
            font-size: 0.95rem;
            background: #fff url('https://cdn-icons-png.flaticon.com/512/149/149852.png') no-repeat 18px center;
            background-size: 16px;
            transition: all 0.3s;
        }
        
        .dataTables_filter input:focus {
            border-color: var(--primary-light) !important;
            box-shadow: 0 0 0 0.25rem rgba(131, 197, 190, 0.2);
            outline: none;
        }
        
        .table thead th {
            background-color: #fcfcfd;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            font-weight: 700;
            color: #64748b;
            padding: 18px;
            border-bottom: 2px solid var(--accent-green);
        }

        /* Modales */
        .modal-content { border-radius: 28px; border: none; overflow: hidden; }
        .section-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--primary-color);
            text-transform: uppercase;
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
        }
        .section-title::after { content: ""; flex: 1; height: 2px; background: var(--accent-green); margin-left: 15px; }

        .ficha-label { font-size: 0.65rem; text-transform: uppercase; color: #94a3b8; font-weight: 800; margin-bottom: 2px; }
        .ficha-valor { font-size: 0.95rem; color: var(--text-main); font-weight: 600; }

        /* Estilo para los inputs del formulario */
        .form-control, .form-select {
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            padding: 0.6rem 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(131, 197, 190, 0.2);
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../panel/sidebar.php'; ?>
<div class="dashboard-main">
<div class="container py-5 main-container">
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h2 class="fw-bold mb-0" style="color: var(--primary-dark)">
                <i class="fa-solid fa-tooth text-primary me-2"></i>Dr. Muelitas
            </h2>
            <p class="text-muted small">Panel de Gestión y Control de Expedientes</p>
        </div>
        <div class="col-md-6 text-md-end d-flex justify-content-end align-items-center gap-2">
            <?php if (in_array($_SESSION['rol'] ?? '', ['ADMIN', 'RECEPCIONISTA'])): ?>
            <button class="btn btn-primary btn-lg shadow-sm" onclick="abrirModal()">
                <i class="fa-solid fa-plus-circle me-2"></i> Nuevo Paciente
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-5 g-4">
        <div class="col-md-4">
            <div class="card card-custom p-4 border-start border-4" style="border-color: var(--primary-color) !important;">
                <div class="d-flex align-items-center">
                    <div class="bg-primary-subtle p-3 rounded-4 me-3 text-primary">
                        <i class="fa-solid fa-hospital-user fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small fw-bold">Total Pacientes</h6>
                        <h3 class="fw-bold mb-0 counter-total" style="color: var(--primary-dark)">0</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-4 border-start border-4" style="border-color: var(--primary-light) !important;">
                <div class="d-flex align-items-center">
                    <div class="p-3 rounded-4 me-3" style="background-color: #e0f2f1; color: var(--primary-color);">
                        <i class="fa-solid fa-mars fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small fw-bold">Hombres</h6>
                        <h3 class="fw-bold mb-0 counter-hombres" style="color: var(--primary-dark)">0</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-4 border-start border-4" style="border-color: var(--female-color) !important;">
                <div class="d-flex align-items-center">
                    <div class="bg-female-subtle p-3 rounded-4 me-3 text-female">
                        <i class="fa-solid fa-venus fa-xl"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small fw-bold">Mujeres</h6>
                        <h3 class="fw-bold mb-0 counter-mujeres" style="color: var(--primary-dark)">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table id="tablaPacientesDT" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th class="border-0">ID</th>
                        <th class="border-0">Identificación</th>
                        <th class="border-0">Nombre del Paciente</th>
                        <th class="border-0">Género</th>
                        <th class="border-0">Contacto</th>
                        <th class="border-0 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPaciente" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header px-4 pt-4 border-0">
                <h5 class="fw-bold text-dark mb-0" id="modalTitulo">Registrar Nuevo Paciente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPaciente" class="needs-validation" novalidate>
                <div class="modal-body p-4 pt-0">
                    <input type="hidden" id="id_paciente" name="id_paciente">
                    
                    <div class="section-title">Información Principal</div>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">N° de Documento (CI) *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="ci" name="ci" required>
                                <select class="form-select bg-light" id="departamento" name="departamento" style="max-width: 90px;">
                                    <option value="SC">SC</option><option value="LP">LP</option>
                                    <option value="CB">CB</option><option value="CH">CH</option>
                                    <option value="OR">OR</option><option value="PT">PT</option>
                                    <option value="TJ">TJ</option><option value="BN">BN</option>
                                    <option value="PA">PA</option><option value="EX">EX</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellido Paterno *</label>
                            <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellido Materno</label>
                            <input type="text" class="form-control" id="apellido_materno" name="apellido_materno">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de Nacimiento *</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Género</label>
                            <select class="form-select" id="sexo" name="sexo">
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title">Contacto y Domicilio</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Teléfono Celular *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="text" class="form-control" id="telefono" name="telefono" required maxlength="8">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Calle, Nro, Zona">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary shadow">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVerPaciente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body p-5">
                <div class="text-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark" id="fichaNombreTitular">Nombre</h4>
                    <span class="badge bg-light text-muted border-0 rounded-pill px-3 py-2" id="fichaIDTitular">CI</span>
                </div>
                <div id="fichaContenido" class="mt-4"></div>
                <div class="d-grid mt-4">
                    <button class="btn btn-light rounded-pill fw-bold py-2" data-bs-dismiss="modal">Cerrar Ficha</button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.PUEDE_EDITAR_PACIENTE = <?php echo json_encode(in_array($_SESSION['rol'] ?? '', ['ADMIN', 'RECEPCIONISTA'])); ?>;
</script>
<script src="app.js"></script>

</body>
</html>