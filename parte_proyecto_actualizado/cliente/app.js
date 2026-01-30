/**
 * PROCLINIC - LÓGICA DE FRONTEND (Versión Nature con Contadores)
 */

let tablaDT;
let bModalForm;
let bModalVer;

// Paleta de colores ProClinic Nature
const COLORS = {
    primary: '#006d77',      // Verde Esmeralda
    primaryLight: '#83c5be', // Verde Menta
    primaryDark: '#004445',  // Verde Bosque
    accent: '#e0f2f1',       // Verde Glaciar
    female: '#2a9d8f'        // Verde Turquesa (para diferenciar)
};

document.addEventListener('DOMContentLoaded', () => {
    const modalFormEl = document.getElementById('modalPaciente');
    const modalVerEl = document.getElementById('modalVerPaciente');
    
    if (modalFormEl) bModalForm = new bootstrap.Modal(modalFormEl);
    if (modalVerEl) bModalVer = new bootstrap.Modal(modalVerEl);
    
    configurarValidaciones();
    inicializarTabla();

    const form = document.getElementById('formPaciente');
    if (form) {
        form.addEventListener('submit', e => {
            e.preventDefault();
            validarTelefono(document.getElementById('telefono'));
            validarCI(document.getElementById('ci'));

            if (form.checkValidity()) { 
                guardarPaciente(); 
            } else {
                form.classList.add('was-validated');
            }
        });
    }
});

async function inicializarTabla() {
    if ($.fn.DataTable.isDataTable('#tablaPacientesDT')) {
        $('#tablaPacientesDT').DataTable().destroy();
    }

    try {
        const res = await fetch('pacientes_backend.php?accion=listar');
        const data = await res.json();

        // --- ACTUALIZACIÓN DE CONTADORES TRIPLES ---
        const total = data.length;
        const hombres = data.filter(p => p.sexo === 'Masculino').length;
        const mujeres = data.filter(p => p.sexo === 'Femenino').length;

        const updateCounter = (clase, valor) => {
            const el = document.querySelector(clase);
            if (el) el.innerText = valor;
        };

        updateCounter('.counter-total', total);
        updateCounter('.counter-hombres', hombres);
        updateCounter('.counter-mujeres', mujeres);
        // ------------------------------------------

        tablaDT = $('#tablaPacientesDT').DataTable({
            data: data,
            responsive: true,
            columnDefs: [{ targets: [1, 4, 5], orderable: false }],
            // Buscador centrado mediante DOM de DataTables
            dom: `<'row mb-4'<'col-12 d-flex justify-content-center'f>>t<'row mt-4'<'col-sm-6'i><'col-sm-6'p>>`,
            language: {
                url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
                search: "",
                searchPlaceholder: "Buscar paciente por nombre o CI..."
            },
            columns: [
                { data: 'id_paciente', className: 'text-muted small' },
                { 
                    data: null, 
                    render: d => `<div class="fw-bold text-dark">${d.ci}</div><span class="badge" style="background-color: ${COLORS.accent}; color: ${COLORS.primaryDark}; font-size: 10px;">${d.departamento}</span>`
                },
                { 
                    data: null, 
                    render: d => `
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                 style="width:36px; height:36px; background-color: ${COLORS.accent}; color: ${COLORS.primary}; font-weight:700; font-size:12px; border: 1px solid ${COLORS.primaryLight};">
                                ${d.nombres.charAt(0)}${d.apellido_paterno.charAt(0)}
                            </div>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 0.95rem;">${d.nombres} ${d.apellido_paterno}</div>
                                <div class="text-muted small" style="font-size: 11px;">${d.apellido_materno || ''}</div>
                            </div>
                        </div>`
                },
                { 
                    data: 'sexo', 
                    render: s => s === 'Masculino' 
                        ? `<span style="color: ${COLORS.primary}; font-weight: 600;"><i class="fa-solid fa-mars me-1"></i>M</span>` 
                        : `<span style="color: ${COLORS.female}; font-weight: 600;"><i class="fa-solid fa-venus me-1"></i>F</span>`
                },
                { 
                    data: 'telefono',
                    render: t => `<span class="text-nowrap"><i class="fa-solid fa-phone me-2 opacity-50 small"></i>${t}</span>`
                },
                { 
                    data: null, 
                    className: 'text-center',
                    render: d => {
                        const btnEditar = (typeof window.PUEDE_EDITAR_PACIENTE !== 'undefined' && window.PUEDE_EDITAR_PACIENTE)
                            ? `<button class="btn btn-action" style="background-color: ${COLORS.accent}; color: #2a9d8f;" onclick="editarPaciente(${d.id_paciente})" title="Editar"><i class="fa-solid fa-pen-to-square"></i></button>`
                            : '';
                        return `
                        <div class="d-flex justify-content-center gap-2">
                            ${btnEditar}
                            <button class="btn btn-action" style="background-color: ${COLORS.accent}; color: #2a9d8f;" onclick="verPaciente(${d.id_paciente})" title="Ver Ficha"><i class="fa-solid fa-eye" color></i></button>
                            <button class="btn btn-action" style="background-color: ${COLORS.accent}; color: #2a9d8f;" onclick="verHistorial(${d.id_paciente})" title="Historial"><i class="fa-solid fa-clock-rotate-left"></i></button>
                        </div>`;
                    }
                }
            ]
        });
    } catch (error) { console.error("Error cargando tabla:", error); }
}

async function guardarPaciente() {
    const form = document.getElementById('formPaciente');
    const data = Object.fromEntries(new FormData(form));
    const btnSubmit = form.querySelector('button[type="submit"]');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>`;

    try {
        const res = await fetch('pacientes_backend.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' }
        });
        const r = await res.json();
        if(r.success) {
            Swal.fire({ icon: 'success', title: 'Éxito', text: r.message, timer: 1500, showConfirmButton: false });
            bModalForm.hide();
            inicializarTabla();
        } else { Swal.fire({ icon: 'warning', title: 'Atención', text: r.message, confirmButtonColor: COLORS.primary }); }
    } catch (error) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión', confirmButtonColor: COLORS.primary }); }
    finally { btnSubmit.disabled = false; btnSubmit.innerHTML = `Guardar Registro`; }
}

window.abrirModal = function() {
    const form = document.getElementById('formPaciente');
    const modalEl = document.getElementById('modalPaciente'); // Referencia al elemento HTML del modal
    
    if (form) {
        form.reset();
        document.getElementById('id_paciente').value = '';
        document.getElementById('modalTitulo').innerText = 'Registrar Nuevo Paciente';
        form.classList.remove('was-validated');
        
        // 1. Mostramos el modal
        bModalForm.show();

        // 2. Esperamos a que el modal sea visible para enfocar el CI
        // 'shown.bs.modal' es el evento de Bootstrap que dispara cuando termina la animación
        modalEl.addEventListener('shown.bs.modal', () => {
            const inputCI = document.getElementById('ci');
            if (inputCI) {
                inputCI.focus();
            }
        }, { once: true }); // '{ once: true }' asegura que este evento se limpie solo
    }
};

window.verPaciente = async function(id) {
    try {
        const res = await fetch(`pacientes_backend.php?accion=obtener&id=${id}`);
        const p = await res.json();
        
        let edad = "N/A";
        if (p.fecha_nacimiento) {
            const nac = new Date(p.fecha_nacimiento);
            const hoy = new Date();
            edad = hoy.getFullYear() - nac.getFullYear();
            if (hoy < new Date(hoy.getFullYear(), nac.getMonth(), nac.getDate())) edad--;
        }

        document.getElementById('fichaNombreTitular').innerText = `${p.nombres} ${p.apellido_paterno}`;
        document.getElementById('fichaIDTitular').innerText = `CI: ${p.ci} ${p.departamento}`;
        
        document.getElementById('fichaContenido').innerHTML = `
            <div class="row g-3 text-start">
                <div class="col-6">
                    <span class="ficha-label">Edad actual</span>
                    <span class="ficha-valor d-block">${edad} años</span>
                </div>
                <div class="col-6">
                    <span class="ficha-label">Género</span>
                    <span class="ficha-valor d-block">${p.sexo}</span>
                </div>
                <div class="col-6">
                    <span class="ficha-label">Teléfono</span>
                    <span class="ficha-valor d-block"><i class="fa-solid fa-phone me-2 small" style="color: ${COLORS.primary};"></i>${p.telefono}</span>
                </div>
                <div class="col-6">
                    <span class="ficha-label">Dirección</span>
                    <span class="ficha-valor d-block text-truncate" title="${p.direccion || ''}">${p.direccion || 'No registrada'}</span>
                </div>
            </div>`;
        bModalVer.show();
    } catch (e) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la ficha', confirmButtonColor: COLORS.primary }); }
};

window.editarPaciente = async function(id) {
    try {
        const res = await fetch(`pacientes_backend.php?accion=obtener&id=${id}`);
        const p = await res.json();
        const f = document.getElementById('formPaciente');
        f.id_paciente.value = p.id_paciente;
        f.ci.value = p.ci;
        f.departamento.value = p.departamento;
        f.nombres.value = p.nombres;
        f.apellido_paterno.value = p.apellido_paterno;
        f.apellido_materno.value = p.apellido_materno;
        f.fecha_nacimiento.value = p.fecha_nacimiento;
        f.sexo.value = p.sexo;
        f.telefono.value = p.telefono;
        f.direccion.value = p.direccion;
        document.getElementById('modalTitulo').innerText = 'Actualizar Datos';
        bModalForm.show();
    } catch (e) { Swal.fire({ icon: 'error', title: 'Error', text: 'Error al cargar datos', confirmButtonColor: COLORS.primary }); }
};

window.verHistorial = async function(id) {
    try {
        const res = await fetch(`pacientes_backend.php?accion=historial&id=${id}`);
        const historial = await res.json();

        if (historial.length === 0) {
            return Swal.fire({ title: 'Sin registros', text: 'Este paciente aún no tiene atenciones.', icon: 'info', confirmButtonColor: COLORS.primary });
        }

        let tablaHistorial = `
            <div class="table-responsive mt-3" style="max-height: 400px; border-radius: 15px;">
                <table class="table table-hover align-middle text-start" style="font-size: 0.9rem;">
                    <thead>
                        <tr style="background-color: ${COLORS.accent};">
                            <th class="border-0 text-muted small fw-bold text-uppercase p-3">Fecha y Hora</th>
                            <th class="border-0 text-muted small fw-bold text-uppercase p-3">Diagnóstico / Servicio</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        historial.forEach(h => {
            const fechaObj = new Date(h.fecha_atencion);
            const fecha = fechaObj.toLocaleDateString();
            const hora = fechaObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            tablaHistorial += `
                <tr>
                    <td class="p-3">
                        <div class="fw-bold text-dark">${fecha}</div>
                        <div class="text-muted small"><i class="fa-regular fa-clock me-1"></i>${hora}</div>
                    </td>
                    <td class="p-3">
                        <div class="fw-bold" style="color: ${COLORS.primary}; font-size: 0.85rem;">${h.servicios}</div>
                        <div class="text-muted small" style="line-height: 1.2;">${h.diagnostico || 'Sin diagnóstico'}</div>
                    </td>
                </tr>
            `;
        });

        tablaHistorial += `</tbody></table></div>`;

        Swal.fire({
            title: `<div style="color: ${COLORS.primaryDark}; font-weight: 800;"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Historial Clínico</div>`,
            html: tablaHistorial,
            width: '850px',
            showCloseButton: true,
            confirmButtonText: 'Cerrar Ventana',
            confirmButtonColor: COLORS.primary,
            customClass: { popup: 'card-custom', confirmButton: 'btn btn-primary rounded-pill px-5 py-2 fw-bold' }
        });
    } catch (e) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el historial', confirmButtonColor: COLORS.primary }); }
};

function configurarValidaciones() {
    const inputCI = document.getElementById('ci');
    const inputTel = document.getElementById('telefono');
    [inputCI, inputTel].forEach(el => {
        if(el) el.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
}

function validarTelefono(input) {
    if (!input) return;
    const valid = /^[67]\d{7}$/.test(input.value);
    input.setCustomValidity(valid ? "" : "Inválido");
}

function validarCI(input) {
    if (!input) return;
    input.setCustomValidity(input.value.length >= 7 ? "" : "Inválido");
}