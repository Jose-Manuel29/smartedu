<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Horarios - Filtros Avanzados</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/estilos.css">

    <style>
        body {
            padding: 20px;
        }
        
        /* Ajustes para el título sobre fondo oscuro */
        h1.main-title {
            color: #ffffff;
            font-weight: 700;
            text-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* Ajustes finos a las tarjetas */
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px;
        }
        .card-header h4, .card-header h5 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
        }

        /* Formularios y Badges */
        .form-section { margin: 20px 0; }
        .form-section h5 { 
            color: #495057; 
            font-weight: 600; 
            border-bottom: 2px solid #e9ecef; 
            padding-bottom: 10px; 
            margin-bottom: 20px;
        }
        
        /* Chips/Badges más estéticos */
        .badge { 
            padding: 0.6em 0.9em; 
            font-weight: 500; 
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Botones de cerrar en badges */
        .btn-close-white { 
            filter: invert(1) grayscale(100%) brightness(200%); 
        }

        /* Tabla de resultados */
        table { border-radius: 10px; overflow: hidden; }
        thead th { 
            background-color: #0d6efd !important; 
            color: white !important; 
            font-weight: 600; 
            border: none;
        }
        tbody tr:hover {
            background-color: #f1f3f5 !important;
        }
        
        /* Loading Spinner */
        .spinner-container {
            padding: 40px;
            background: rgba(255,255,255,0.9);
            border-radius: 15px;
        }
    </style>
</head>
<body> <div class="container main-container">
        <h1 class="text-center mb-5 main-title"><i class="fas fa-sliders-h me-3"></i>Generador de Horarios</h1>
        
        <div class="card custom-card mb-4" id="materiasCard" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book-open text-primary me-2"></i>Materias Seleccionadas</h5>
                <a href="/PROYECTO_ISII/public/filtro_materias.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="fas fa-edit me-1"></i> Modificar
                </a>
            </div>
            <div class="card-body p-4">
                <div id="materiasLista" class="d-flex flex-wrap gap-2">
                    </div>
            </div>
        </div>

        <div class="card custom-card mb-4" id="estadisticasCard" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie text-info me-2"></i>Estadísticas Previas</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0 border-0 shadow-sm rounded-3">
                            <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem;">Combinaciones Posibles</small>
                            <h3 id="totalCombinaciones" class="display-6 fw-bold text-primary mb-0 mt-1">0</h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-light border mb-0 shadow-sm rounded-3">
                            <small class="text-uppercase fw-bold text-muted" style="font-size: 0.75rem;">Profesores Disponibles</small>
                            <h3 id="totalProfesores" class="display-6 fw-bold text-dark mb-0 mt-1">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-cog text-secondary me-2"></i>Configuración de Horario</h4>
            </div>
            <div class="card-body p-4">
                <div class="form-section">
                    <h5><i class="fas fa-chalkboard-teacher me-2"></i>Profesores</h5>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small">PROFESORES OBLIGATORIOS</label>
                            <div class="input-group mb-2">
                                <button class="btn btn-outline-primary" type="button" id="btnAgregarProfPrioridad">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <select class="form-select" id="selectProfPrioridad" style="display:none;">
                                    <option value="">-- Selecciona Profesor --</option>
                                </select>
                            </div>
                            <div id="chipsProfPrioridad" class="d-flex flex-wrap gap-2 mb-2 min-h-30"></div>
                            <input type="hidden" id="profesorPrioridad" name="profesor_prioridad" value="">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small">PROFESORES EXCLUIDOS</label>
                            <div class="input-group mb-2">
                                <button class="btn btn-outline-danger" type="button" id="btnAgregarProfExcluir">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <select class="form-select" id="selectProfExcluir" style="display:none;">
                                    <option value="">-- Selecciona Profesor --</option>
                                </select>
                            </div>
                            <div id="chipsProfExcluir" class="d-flex flex-wrap gap-2 mb-2 min-h-30"></div>
                            <input type="hidden" id="profesorExcluir" name="profesor_excluir" value="">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h5><i class="fas fa-clock me-2"></i>Preferencias de Tiempo</h5>
                    <div class="row">
                        <div class="col-md-6">
                             <label class="form-label mb-3">Turno Preferido</label>
                             <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="turno" id="turnoTodos" value="todos" checked>
                                    <label class="form-check-label" for="turnoTodos">Todos</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="turno" id="turnoMatutino" value="matutino">
                                    <label class="form-check-label" for="turnoMatutino">Matutino</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="turno" id="turnoVespertino" value="vespertino">
                                    <label class="form-check-label" for="turnoVespertino">Vespertino</label>
                                </div>
                             </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ordenar Resultados Por</label>
                            <select class="form-select" id="ordenamiento" name="ordenamiento">
                                <option value="horas_muertas">Menos horas muertas (Recomendado)</option>
                                <option value="hora_inicio">Hora de inicio más temprana</option>
                                <option value="hora_fin">Hora de fin más temprana</option>
                                <option value="compatibilidad">Mayor compatibilidad</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-5">
                    <button type="button" class="btn btn-primary btn-custom-primary btn-lg shadow px-5" id="btnAplicarFiltros">
                        <i class="fas fa-filter me-2"></i>Aplicar Filtros
                    </button>
                    <button type="button" class="btn btn-success btn-custom-success btn-lg shadow px-5" id="btnGenerar">
                        <i class="fas fa-rocket me-2"></i>Generar Horarios
                    </button>
                </div>
            </div>
        </div>

        <div id="resultadosContainer" style="display:none; margin-top: 30px;">
            <div class="card custom-card">
                <div class="card-header">
                </div>
                <div class="card-body p-4">
                    <div id="loadingSpinner" class="text-center spinner-container" style="display:none;">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-muted fw-bold">Calculando mejores combinaciones...</p>
                    </div>
                    <div id="resultadosContenido"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // MANTUVE TU LÓGICA DE JAVASCRIPT EXACTAMENTE IGUAL
        const API_URL = 'http://localhost/PROYECTO_ISII/private/controllers/generar_horarios.php';
        let profesoresDisponibles = {};
        let profesoresPrioridad = [];
        let profesoresExcluir = [];
        let materiasActuales = [];

        // Las materias se reciben por parámetro GET o se pasan directamente
        function cargarMateriasDelParametro() {
            const urlParams = new URLSearchParams(window.location.search);
            const materiasParam = urlParams.get('materias');
            if (materiasParam) {
                materiasActuales = materiasParam.split(',').map(m => m.trim());
                console.log('Materias cargadas desde parámetro:', materiasActuales);
                mostrarMateriasSeleccionadas();
            }
        }

        // Mostrar las materias seleccionadas
        function mostrarMateriasSeleccionadas() {
            const materiasLista = $('#materiasLista');
            materiasLista.html('');
            
            if (materiasActuales.length > 0) {
                $('#materiasCard').show();
                materiasActuales.forEach(materia => {
                    const badge = `
                        <span class="badge bg-info text-dark d-inline-flex align-items-center gap-2 shadow-sm">
                            <i class="fas fa-book small"></i> ${materia}
                            <button type="button" class="btn-close btn-close-sm" 
                                    onclick="removerMateria('${materia}')" style="cursor: pointer; padding: 0.25rem;">
                            </button>
                        </span>
                    `;
                    materiasLista.append(badge);
                });
                cargarEstadisticas();
                cargarProfesoresPorMateria();
            }
        }

        function removerMateria(materia) {
            materiasActuales = materiasActuales.filter(m => m !== materia);
            if (materiasActuales.length === 0) {
                $('#materiasCard').hide();
                $('#estadisticasCard').hide();
                profesoresDisponibles = {};
                profesoresPrioridad = [];
                profesoresExcluir = [];
            } else {
                mostrarMateriasSeleccionadas();
            }
        }

        async function cargarProfesoresPorMateria() {
            try {
                const payload = { materias: materiasActuales, action: 'get_profesores_por_materia' };
                const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await response.json();
                
                if (data.status === 'ok' && data.profesores_por_materia) {
                    profesoresDisponibles = data.profesores_por_materia;
                    const todosLosProfesores = new Set();
                    Object.values(profesoresDisponibles).forEach(profs => { profs.forEach(p => todosLosProfesores.add(p)); });
                    
                    const profesoresOrdenados = Array.from(todosLosProfesores).sort();
                    $('#selectProfPrioridad, #selectProfExcluir').html('<option value="">-- Selecciona Profesor --</option>');
                    
                    profesoresOrdenados.forEach(prof => {
                        const option = `<option value="${prof}">${prof}</option>`;
                        $('#selectProfPrioridad').append(option);
                        $('#selectProfExcluir').append(option);
                    });
                    
                    actualizarSelectProfPrioridad();
                    actualizarSelectProfExcluir();
                }
            } catch (error) { console.error('Error cargando profesores:', error); }
        }

        async function cargarEstadisticas() {
            try {
                const payload = { materias: materiasActuales, action: 'get_stats' };
                const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await response.json();
                if (data.status === 'ok' && data.stats) {
                    $('#totalCombinaciones').text(data.stats.total_combinaciones || 0);
                    $('#totalProfesores').text(data.stats.total_profesores || 0);
                    $('#estadisticasCard').show();
                }
            } catch (error) { console.error('Error estadísticas:', error); }
        }

        // Event Listeners Botones +
        $('#btnAgregarProfPrioridad').on('click', function() { $('#selectProfPrioridad').toggle().focus(); });
        $('#btnAgregarProfExcluir').on('click', function() { $('#selectProfExcluir').toggle().focus(); });

        $('#selectProfPrioridad').on('change', function() {
            const prof = $(this).val();
            if (prof && !profesoresPrioridad.includes(prof)) {
                profesoresPrioridad.push(prof);
                actualizarChipsPrioridad();
                actualizarInputProfPrioridad();
                actualizarSelectProfPrioridad();
                $(this).val('').hide();
            }
        });

        $('#selectProfExcluir').on('change', function() {
            const prof = $(this).val();
            if (prof && !profesoresExcluir.includes(prof)) {
                profesoresExcluir.push(prof);
                actualizarChipsExcluir();
                actualizarInputProfExcluir();
                actualizarSelectProfExcluir();
                $(this).val('').hide();
            }
        });

        function actualizarChipsPrioridad() {
            const container = $('#chipsProfPrioridad');
            container.html('');
            profesoresPrioridad.forEach(prof => {
                const chip = `
                    <span class="badge bg-primary d-inline-flex align-items-center gap-2">
                        ${prof}
                        <button type="button" class="btn-close btn-close-white btn-close-sm" onclick="removerProfPrioridad('${prof}')"></button>
                    </span>`;
                container.append(chip);
            });
        }

        function actualizarChipsExcluir() {
            const container = $('#chipsProfExcluir');
            container.html('');
            profesoresExcluir.forEach(prof => {
                const chip = `
                    <span class="badge bg-danger d-inline-flex align-items-center gap-2">
                        ${prof}
                        <button type="button" class="btn-close btn-close-white btn-close-sm" onclick="removerProfExcluir('${prof}')"></button>
                    </span>`;
                container.append(chip);
            });
        }

        function removerProfPrioridad(prof) {
            profesoresPrioridad = profesoresPrioridad.filter(p => p !== prof);
            actualizarChipsPrioridad();
            actualizarInputProfPrioridad();
            actualizarSelectProfPrioridad();
        }

        function removerProfExcluir(prof) {
            profesoresExcluir = profesoresExcluir.filter(p => p !== prof);
            actualizarChipsExcluir();
            actualizarInputProfExcluir();
            actualizarSelectProfExcluir();
        }

        function actualizarInputProfPrioridad() { $('#profesorPrioridad').val(profesoresPrioridad.length > 0 ? JSON.stringify(profesoresPrioridad) : ''); }
        function actualizarInputProfExcluir() { $('#profesorExcluir').val(profesoresExcluir.length > 0 ? JSON.stringify(profesoresExcluir) : ''); }

        function obtenerMateriasDelProfesor(profesor) {
            const materias = [];
            Object.entries(profesoresDisponibles).forEach(([materia, profs]) => {
                if (profs.includes(profesor)) materias.push(materia);
            });
            return materias;
        }

        function actualizarSelectProfPrioridad() {
            const materiasOcupadas = new Set();
            profesoresPrioridad.forEach(prof => { obtenerMateriasDelProfesor(prof).forEach(mat => materiasOcupadas.add(mat)); });
            
            const selectElement = $('#selectProfPrioridad');
            selectElement.html('<option value="">-- Selecciona Profesor --</option>');
            
            const todosLosProfesores = new Set();
            Object.values(profesoresDisponibles).forEach(profs => profs.forEach(p => todosLosProfesores.add(p)));

            Array.from(todosLosProfesores).sort().forEach(prof => {
                if (!profesoresPrioridad.includes(prof)) {
                    const materiasDelProf = obtenerMateriasDelProfesor(prof);
                    const tieneMateriasOcupadas = materiasDelProf.some(mat => materiasOcupadas.has(mat));
                    if (!tieneMateriasOcupadas) selectElement.append(`<option value="${prof}">${prof}</option>`);
                }
            });
        }

        function actualizarSelectProfExcluir() {
            const materiasOcupadas = new Set();
            profesoresExcluir.forEach(prof => { obtenerMateriasDelProfesor(prof).forEach(mat => materiasOcupadas.add(mat)); });

            const selectElement = $('#selectProfExcluir');
            selectElement.html('<option value="">-- Selecciona Profesor --</option>');
            
            const todosLosProfesores = new Set();
            Object.values(profesoresDisponibles).forEach(profs => profs.forEach(p => todosLosProfesores.add(p)));

            Array.from(todosLosProfesores).sort().forEach(prof => {
                if (!profesoresExcluir.includes(prof)) {
                    const materiasDelProf = obtenerMateriasDelProfesor(prof);
                    const tieneMateriasOcupadas = materiasDelProf.some(mat => materiasOcupadas.has(mat));
                    if (!tieneMateriasOcupadas) selectElement.append(`<option value="${prof}">${prof}</option>`);
                }
            });
        }

        $('#btnAplicarFiltros').on('click', async function() {
            if (materiasActuales.length < 2) { alert('Selecciona al menos 2 materias.'); return; }
            const turno = $('input[name="turno"]:checked').val();
            const ordenamiento = $('#ordenamiento').val();

            try {
                const payload = {
                    materias: materiasActuales, turno: turno, ordenamiento: ordenamiento,
                    profesor_prioridad: profesoresPrioridad.length > 0 ? profesoresPrioridad : null,
                    profesor_excluir: profesoresExcluir.length > 0 ? profesoresExcluir : null
                };
                const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await response.json();

                if (data.status === 'ok') {
                    $('#totalCombinaciones').text(data.total_combinaciones_validas || 0);
                    const alertDiv = $(`
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-2"></i><strong>Filtros aplicados:</strong> ${data.total_combinaciones_validas} combinaciones encontradas.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>`);
                    $('#estadisticasCard').before(alertDiv);
                    setTimeout(() => alertDiv.fadeOut(500, () => $(this).remove()), 4000);
                } else { alert('Error: ' + (data.msg || 'Fallo al filtrar')); }
            } catch (error) { alert('Error de conexión.'); }
        });

        $('#btnGenerar').on('click', async function() {
            if (materiasActuales.length < 2) { alert('Selecciona materias primero.'); return; }
            const turno = $('input[name="turno"]:checked').val();
            const ordenamiento = $('#ordenamiento').val();

            $('#resultadosContainer').show();
            $('#loadingSpinner').show();
            $('#resultadosContenido').html('');
            
            // Scroll suave hacia resultados
            document.getElementById('resultadosContainer').scrollIntoView({ behavior: 'smooth' });

            try {
                const payload = {
                    materias: materiasActuales, turno: turno, ordenamiento: ordenamiento,
                    profesor_prioridad: profesoresPrioridad.length > 0 ? profesoresPrioridad : null,
                    profesor_excluir: profesoresExcluir.length > 0 ? profesoresExcluir : null
                };
                const response = await fetch(API_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await response.json();
                $('#loadingSpinner').hide();

                if (data.status === 'ok' && data.combinaciones && data.combinaciones.length > 0) {
                    sessionStorage.setItem('combinacionesData', JSON.stringify(data));
                    window.location.href = '/PROYECTO_ISII/public/test_horarios.php';
                } else {
                    $('#resultadosContenido').html(`<div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i>No se encontraron combinaciones válidas. Intenta relajar los filtros.</div>`);
                }
            } catch (error) {
                $('#loadingSpinner').hide();
                $('#resultadosContenido').html(`<div class="alert alert-danger">Error de servidor: ${error.message}</div>`);
            }
        });

        $(document).ready(function() { cargarMateriasDelParametro(); });
    </script>
</body>
</html>