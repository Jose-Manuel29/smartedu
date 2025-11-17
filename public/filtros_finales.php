<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Horarios - Filtros Avanzados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; padding: 20px; }
        .container { max-width: 900px; margin-top: 20px; }
        .card { border: none; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .card-header { background-color: #f8f9fa; border-bottom: 2px solid #007bff; padding: 15px; }
        .card-header h4, 
        .card-header h5 { color: #333; margin: 0; font-weight: 600; }
        h1, h2, h3 { color: #333; }
        .form-section { margin: 20px 0; }
        .form-section h5 { color: #333; font-weight: 600; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; }
        .form-label { font-weight: 500; color: #333; }
        .form-select, .form-control { border: 1px solid #dee2e6; border-radius: 6px; }
        .btn-primary { background-color: #007bff; border: none; padding: 10px 25px; font-size: 1em; }
        .btn-primary:hover { background-color: #0056b3; transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
        .btn-success { background-color: #28a745; border: none; padding: 10px 25px; font-size: 1em; }
        .btn-success:hover { background-color: #218838; transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
        .badge { padding: 0.5em 0.75em; font-size: 0.9em; }
        .alert { border-radius: 6px; border: none; }
        table { margin-top: 20px; border-radius: 6px; overflow: hidden; }
        th { background-color: #007bff; color: white; font-weight: 600; }
        .text-muted { font-size: 0.9em; }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <h1 class="text-center mb-4">Generador de Horarios</h1>
        
        <!-- SECCIÓN: Materias Seleccionadas -->
        <div class="card mb-4" id="materiasCard" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0">Materias Seleccionadas</h5>
            </div>
            <div class="card-body">
                <div id="materiasLista" class="d-flex flex-wrap gap-2">
                </div>
                <div class="mt-3">
                    <a href="/proyecto_isi/filtro_materias.php" class="btn btn-sm btn-outline-primary">← Cambiar Materias</a>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: Estadísticas Previas -->
        <div class="card mb-4" id="estadisticasCard" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0">Estadísticas Previas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <strong>Combinaciones Posibles:</strong><br>
                            <h3 id="totalCombinaciones" style="color: #0c5460; margin-top: 10px;">0</h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-light border mb-0">
                            <strong>Profesores Disponibles:</strong><br>
                            <h3 id="totalProfesores" style="color: #333; margin-top: 10px;">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">⚙️ Filtros</h4>
            </div>
            <div class="card-body p-4">
                <!-- SECCIÓN 1: Filtros por Profesor -->
                <div class="form-section">
                    <h5>Filtros por Profesor</h5>
                    
                    <!-- Filtrar por Profesor -->
                    <div class="mb-4">
                        <label class="form-label">Filtrar por Profesor(es):</label>
                        <small class="d-block text-muted mb-2">Solo se mostrarán horarios con TODOS estos profesores</small>
                        <div class="input-group mb-2">
                            <button class="btn btn-outline-primary" type="button" id="btnAgregarProfPrioridad">
                                <strong>+</strong> Agregar
                            </button>
                            <select class="form-select" id="selectProfPrioridad" style="display:none;">
                                <option value="">-- Selecciona Profesor --</option>
                            </select>
                        </div>
                        <div id="chipsProfPrioridad" class="d-flex flex-wrap gap-2 mb-2">
                            <!-- Se llenarán dinámicamente con chips -->
                        </div>
                        <input type="hidden" id="profesorPrioridad" name="profesor_prioridad" value="">
                    </div>

                    <!-- Excluir Profesor -->
                    <div class="mb-3">
                        <label class="form-label">Excluir Profesor(es):</label>
                        <small class="d-block text-muted mb-2">Las combinaciones con estos profesores serán eliminadas</small>
                        <div class="input-group mb-2">
                            <button class="btn btn-outline-danger" type="button" id="btnAgregarProfExcluir">
                                <strong>+</strong> Agregar
                            </button>
                            <select class="form-select" id="selectProfExcluir" style="display:none;">
                                <option value="">-- Selecciona Profesor --</option>
                            </select>
                        </div>
                        <div id="chipsProfExcluir" class="d-flex flex-wrap gap-2 mb-2">
                            <!-- Se llenarán dinámicamente con chips -->
                        </div>
                        <input type="hidden" id="profesorExcluir" name="profesor_excluir" value="">
                    </div>
                </div>

                <!-- SECCIÓN 2: Filtro por Turno -->
                <div class="form-section">
                    <h5>Turno Preferido</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="turno" id="turnoTodos" value="todos" checked>
                                <label class="form-check-label" for="turnoTodos">Todos los turnos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="turno" id="turnoMatutino" value="matutino">
                                <label class="form-check-label" for="turnoMatutino">Matutino (07:00 - 13:00)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="turno" id="turnoVespertino" value="vespertino">
                                <label class="form-check-label" for="turnoVespertino">Vespertino (13:00 - 19:00)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: Ordenamiento -->
                <div class="form-section">
                    <h5>Ordenar Resultados Por</h5>
                    <div class="row">
                        <div class="col-md-12">
                            <select class="form-select" id="ordenamiento" name="ordenamiento">
                                <option value="horas_muertas">Menos horas muertas (Recomendado)</option>
                                <option value="hora_inicio">Hora de inicio más temprana</option>
                                <option value="hora_fin">Hora de fin más temprana</option>
                                <option value="compatibilidad">Mayor compatibilidad con selección</option>
                            </select>
                            <small class="text-muted">Las combinaciones se ordenarán según tu preferencia</small>
                        </div>
                    </div>
                </div>

                <!-- BOTONES PRINCIPALES -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-primary btn-lg" id="btnAplicarFiltros">
                        ✓ Aplicar Filtros
                    </button>
                    <button type="button" class="btn btn-success btn-lg ms-2" id="btnGenerar">
                        Generar Horarios
                    </button>
                </div>
            </div>
        </div>

        <!-- RESULTADOS -->
        <div id="resultadosContainer" style="display:none; margin-top: 30px;">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Resultados</h4>
                </div>
                <div class="card-body">
                    <div id="loadingSpinner" class="text-center" style="display:none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Generando combinaciones...</p>
                    </div>
                    <div id="resultadosContenido"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const API_URL = 'http://localhost/proyecto_isi/private/controllers/generar_horarios.php';
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
                    const badge = `<span class="badge bg-info text-dark" style="font-size: 1em; padding: 0.5em 0.75em;">${materia}</span>`;
                    materiasLista.append(badge);
                });
                // Cargar estadísticas después de mostrar materias
                cargarEstadisticas();
                // Cargar profesores disponibles
                cargarProfesoresPorMateria();
            }
        }

        // Cargar profesores por materia desde el endpoint
        async function cargarProfesoresPorMateria() {
            try {
                const payload = {
                    materias: materiasActuales,
                    action: 'get_profesores_por_materia'
                };

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                
                if (data.status === 'ok' && data.profesores_por_materia) {
                    profesoresDisponibles = data.profesores_por_materia;
                    
                    // Obtener lista única de todos los profesores
                    const todosLosProfesores = new Set();
                    Object.values(profesoresDisponibles).forEach(profs => {
                        profs.forEach(p => todosLosProfesores.add(p));
                    });
                    
                    // Llenar los select con todos los profesores disponibles
                    const profesoresOrdenados = Array.from(todosLosProfesores).sort();
                    $('#selectProfPrioridad, #selectProfExcluir').html('<option value="">-- Selecciona Profesor --</option>');
                    
                    profesoresOrdenados.forEach(prof => {
                        const option = `<option value="${prof}">${prof}</option>`;
                        $('#selectProfPrioridad').append(option);
                        $('#selectProfExcluir').append(option);
                    });
                    
                    // Actualizar selectores para aplicar lógica de ocultamiento
                    actualizarSelectProfPrioridad();
                    actualizarSelectProfExcluir();
                    
                    console.log('Profesores por materia cargados:', profesoresDisponibles);
                } else {
                    console.warn('No se pudieron cargar profesores:', data);
                }
            } catch (error) {
                console.error('Error cargando profesores por materia:', error);
            }
        }

        // Cargar estadísticas: total de combinaciones posibles y profesores disponibles
        async function cargarEstadisticas() {
            try {
                const payload = {
                    materias: materiasActuales,
                    action: 'get_stats' // Acción especial para obtener estadísticas
                };

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                
                if (data.status === 'ok' && data.stats) {
                    $('#totalCombinaciones').text(data.stats.total_combinaciones || 0);
                    $('#totalProfesores').text(data.stats.total_profesores || 0);
                    
                    $('#estadisticasCard').show();
                    console.log('Estadísticas cargadas:', data.stats);
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        }

        // Manejar agregación de profesor a la lista de prioridad
        $('#btnAgregarProfPrioridad').on('click', function() {
            $('#selectProfPrioridad').toggle();
            if ($('#selectProfPrioridad').is(':visible')) {
                $('#selectProfPrioridad').focus();
            }
        });

        $('#selectProfPrioridad').on('change', function() {
            const prof = $(this).val();
            if (prof && !profesoresPrioridad.includes(prof)) {
                profesoresPrioridad.push(prof);
                actualizarChipsPrioridad();
                actualizarInputProfPrioridad();
                actualizarSelectProfPrioridad(); // Actualizar opciones disponibles
                $(this).val('').hide();
            }
        });

        // Manejar agregación de profesor a la lista de exclusión
        $('#btnAgregarProfExcluir').on('click', function() {
            $('#selectProfExcluir').toggle();
            if ($('#selectProfExcluir').is(':visible')) {
                $('#selectProfExcluir').focus();
            }
        });

        $('#selectProfExcluir').on('change', function() {
            const prof = $(this).val();
            if (prof && !profesoresExcluir.includes(prof)) {
                profesoresExcluir.push(prof);
                actualizarChipsExcluir();
                actualizarInputProfExcluir();
                actualizarSelectProfExcluir(); // Actualizar opciones disponibles
                $(this).val('').hide();
            }
        });

        // Actualizar chips para profesores de prioridad
        function actualizarChipsPrioridad() {
            const container = $('#chipsProfPrioridad');
            container.html('');
            profesoresPrioridad.forEach(prof => {
                const chip = `
                    <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                        ${prof}
                        <button type="button" class="btn-close btn-close-white btn-close-sm" 
                                onclick="removerProfPrioridad('${prof}')" style="cursor: pointer; padding: 0;">
                        </button>
                    </span>
                `;
                container.append(chip);
            });
        }

        // Actualizar chips para profesores de exclusión
        function actualizarChipsExcluir() {
            const container = $('#chipsProfExcluir');
            container.html('');
            profesoresExcluir.forEach(prof => {
                const chip = `
                    <span class="badge bg-danger d-inline-flex align-items-center gap-1">
                        ${prof}
                        <button type="button" class="btn-close btn-close-white btn-close-sm" 
                                onclick="removerProfExcluir('${prof}')" style="cursor: pointer; padding: 0;">
                        </button>
                    </span>
                `;
                container.append(chip);
            });
        }

        // Remover profesor de prioridad
        function removerProfPrioridad(prof) {
            profesoresPrioridad = profesoresPrioridad.filter(p => p !== prof);
            actualizarChipsPrioridad();
            actualizarInputProfPrioridad();
            actualizarSelectProfPrioridad(); // Actualizar opciones al remover
        }

        // Remover profesor de exclusión
        function removerProfExcluir(prof) {
            profesoresExcluir = profesoresExcluir.filter(p => p !== prof);
            actualizarChipsExcluir();
            actualizarInputProfExcluir();
            actualizarSelectProfExcluir(); // Actualizar opciones al remover
        }

        // Actualizar input hidden para profesores de prioridad
        function actualizarInputProfPrioridad() {
            const valor = profesoresPrioridad.length > 0 ? JSON.stringify(profesoresPrioridad) : '';
            $('#profesorPrioridad').val(valor);
        }

        // Actualizar input hidden para profesores de exclusión
        function actualizarInputProfExcluir() {
            const valor = profesoresExcluir.length > 0 ? JSON.stringify(profesoresExcluir) : '';
            $('#profesorExcluir').val(valor);
        }

        // Obtener materias enseñadas por un profesor
        function obtenerMateriasDelProfesor(profesor) {
            const materias = [];
            Object.entries(profesoresDisponibles).forEach(([materia, profs]) => {
                if (profs.includes(profesor)) {
                    materias.push(materia);
                }
            });
            return materias;
        }

        // Actualizar opciones disponibles en selectProfPrioridad
        function actualizarSelectProfPrioridad() {
            const materiasOcupadas = new Set();
            
            // Obtener todas las materias enseñadas por profesores ya seleccionados
            profesoresPrioridad.forEach(prof => {
                obtenerMateriasDelProfesor(prof).forEach(mat => {
                    materiasOcupadas.add(mat);
                });
            });

            // Reconstruir opciones del select
            const selectElement = $('#selectProfPrioridad');
            selectElement.html('<option value="">-- Selecciona Profesor --</option>');

            // Obtener lista única de todos los profesores
            const todosLosProfesores = new Set();
            Object.values(profesoresDisponibles).forEach(profs => {
                profs.forEach(p => todosLosProfesores.add(p));
            });

            // Agregar opciones solo si el profesor no está seleccionado ni enseña materias ocupadas
            Array.from(todosLosProfesores).sort().forEach(prof => {
                if (!profesoresPrioridad.includes(prof)) {
                    const materiasDelProf = obtenerMateriasDelProfesor(prof);
                    const tieneMateriasOcupadas = materiasDelProf.some(mat => materiasOcupadas.has(mat));
                    
                    if (!tieneMateriasOcupadas) {
                        const option = `<option value="${prof}">${prof}</option>`;
                        selectElement.append(option);
                    }
                }
            });
        }

        // Actualizar opciones disponibles en selectProfExcluir
        function actualizarSelectProfExcluir() {
            const materiasOcupadas = new Set();
            
            // Obtener todas las materias enseñadas por profesores ya excluidos
            profesoresExcluir.forEach(prof => {
                obtenerMateriasDelProfesor(prof).forEach(mat => {
                    materiasOcupadas.add(mat);
                });
            });

            // Reconstruir opciones del select
            const selectElement = $('#selectProfExcluir');
            selectElement.html('<option value="">-- Selecciona Profesor --</option>');

            // Obtener lista única de todos los profesores
            const todosLosProfesores = new Set();
            Object.values(profesoresDisponibles).forEach(profs => {
                profs.forEach(p => todosLosProfesores.add(p));
            });

            // Agregar opciones solo si el profesor no está excluido ni enseña materias ocupadas
            Array.from(todosLosProfesores).sort().forEach(prof => {
                if (!profesoresExcluir.includes(prof)) {
                    const materiasDelProf = obtenerMateriasDelProfesor(prof);
                    const tieneMateriasOcupadas = materiasDelProf.some(mat => materiasOcupadas.has(mat));
                    
                    if (!tieneMateriasOcupadas) {
                        const option = `<option value="${prof}">${prof}</option>`;
                        selectElement.append(option);
                    }
                }
            });
        }

        // Aplicar filtros: actualizar el número de combinaciones
        $('#btnAplicarFiltros').on('click', async function() {
            if (materiasActuales.length < 2) {
                alert('No se detectaron materias válidas. Por favor vuelve atrás y selecciona nuevamente.');
                return;
            }

            const turno = $('input[name="turno"]:checked').val();
            const ordenamiento = $('#ordenamiento').val();

            try {
                const payload = {
                    materias: materiasActuales,
                    turno: turno,
                    ordenamiento: ordenamiento,
                    profesor_prioridad: profesoresPrioridad.length > 0 ? profesoresPrioridad : null,
                    profesor_excluir: profesoresExcluir.length > 0 ? profesoresExcluir : null
                };

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.status === 'ok') {
                    // Actualizar solo el número de combinaciones
                    $('#totalCombinaciones').text(data.total_combinaciones_validas || 0);
                    
                    // Mostrar alerta de éxito
                    const alertDiv = $(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>✅ Filtros aplicados</strong><br>
                            Se encontraron <strong>${data.total_combinaciones_validas}</strong> combinaciones válidas con los filtros seleccionados.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    $('#estadisticasCard').before(alertDiv);
                    
                    // Desvanecer después de 4 segundos
                    setTimeout(function() {
                        alertDiv.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 4000);
                    
                    console.log('Filtros aplicados:', data);
                } else {
                    alert('Error: ' + (data.msg || 'No se pudieron aplicar los filtros'));
                }
            } catch (error) {
                alert('Error al aplicar filtros: ' + error.message);
                console.error(error);
            }
        });

        $('#btnGenerar').on('click', async function() {
            if (materiasActuales.length < 2) {
                alert('No se detectaron materias válidas. Por favor vuelve atrás y selecciona nuevamente.');
                return;
            }

            const turno = $('input[name="turno"]:checked').val();
            const ordenamiento = $('#ordenamiento').val();

            $('#resultadosContainer').show();
            $('#loadingSpinner').show();
            $('#resultadosContenido').html('');

            try {
                const payload = {
                    materias: materiasActuales,
                    turno: turno,
                    ordenamiento: ordenamiento,
                    profesor_prioridad: profesoresPrioridad.length > 0 ? profesoresPrioridad : null,
                    profesor_excluir: profesoresExcluir.length > 0 ? profesoresExcluir : null
                };

                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                $('#loadingSpinner').hide();

                if (data.status === 'ok' && data.combinaciones && data.combinaciones.length > 0) {
                    // Guardar datos en sessionStorage para pasar a test_horarios.php
                    sessionStorage.setItem('combinacionesData', JSON.stringify(data));
                    
                    // Redirigir a test_horarios.php
                    window.location.href = '/proyecto_isi/public/test_horarios.php';
                } else {
                    $('#resultadosContenido').html(`
                        <div class="alert alert-warning">
                            <strong>No se encontraron combinaciones válidas</strong><br>
                            ${data.msg || 'Intenta con otras materias o ajusta los filtros'}
                        </div>
                    `);
                }
            } catch (error) {
                $('#loadingSpinner').hide();
                $('#resultadosContenido').html(`
                    <div class="alert alert-danger">
                        Error al procesar: ${error.message}
                    </div>
                `);
                console.error(error);
            }
        });

        // Mostrar resultados
        function mostrarResultados(data) {
            let html = `
                <div class="alert alert-success">
                    <strong>✅ Se encontraron ${data.total_combinaciones_validas} combinaciones válidas</strong><br>
                    Materias: ${data.materias_solicitadas.join(', ')}
                </div>
                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <strong>Filtros aplicados:</strong><br>
                    Turno: ${data.filtros_aplicados?.turno || 'todos'} | 
                    Profesor prioridad: ${data.filtros_aplicados?.profesor_prioridad || 'ninguno'} | 
                    Profesor excluido: ${data.filtros_aplicados?.profesor_excluir || 'ninguno'} | 
                    Ordenamiento: ${data.filtros_aplicados?.ordenamiento || 'horas muertas'}
                </div>
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>NRCs</th>
                            <th>Profesores</th>
                            <th>Horario</th>
                            <th>Turno</th>
                            <th>Horas Muertas</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.combinaciones.forEach((comb, idx) => {
                const nrcs = comb.nrcs_incluidos.join(', ');
                const detalle = comb.detalle_horarios || {};
                
                let profesores = comb.profesores || [];
                let horario = '';
                let turno = comb.turno || 'mixto';
                let horasMuertas = comb.horas_muertas || 0;

                // Procesar detalle de horarios
                if (typeof detalle === 'string') {
                    try {
                        const parsed = JSON.parse(detalle);
                        Object.values(parsed).forEach(nrc => {
                            if (nrc.registros) {
                                horario += nrc.registros.map(r => `${r.dia} ${r.inicio}-${r.fin}`).join(', ') + ' ';
                            }
                        });
                    } catch (e) {
                        console.warn('Error parsing detalle:', e);
                    }
                } else if (typeof detalle === 'object') {
                    Object.values(detalle).forEach(nrc => {
                        if (nrc.registros) {
                            horario += nrc.registros.map(r => `${r.dia} ${r.inicio}-${r.fin}`).join(', ') + ' ';
                        }
                    });
                }

                // Badge de turno
                let turno_badge = '';
                if (turno === 'matutino') {
                    turno_badge = '<span class="badge bg-warning text-dark">🌅 Matutino</span>';
                } else if (turno === 'vespertino') {
                    turno_badge = '<span class="badge bg-danger">🌆 Vespertino</span>';
                } else {
                    turno_badge = '<span class="badge bg-secondary">🔄 Mixto</span>';
                }

                html += `
                    <tr>
                        <td><span class="badge bg-primary">${idx + 1}</span></td>
                        <td><code>${nrcs}</code></td>
                        <td><small>${profesores.join(', ') || 'N/A'}</small></td>
                        <td><small style="font-size: 0.85em; color: #666;">${horario.trim() || 'N/A'}</small></td>
                        <td>${turno_badge}</td>
                        <td><strong>${horasMuertas} hrs</strong></td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            $('#resultadosContenido').html(html);
        }

        // Calcular horas muertas
        function calcularHorasMuertas(detalle) {
            if (typeof detalle === 'string') {
                try {
                    detalle = JSON.parse(detalle);
                } catch (e) {
                    return 0;
                }
            }

            let minutoInicio = 24 * 60;
            let minutoFin = 0;

            Object.values(detalle).forEach(nrc => {
                if (nrc.registros) {
                    nrc.registros.forEach(reg => {
                        const [h, m] = reg.inicio.split(':').map(Number);
                        const min = h * 60 + m;
                        minutoInicio = Math.min(minutoInicio, min);

                        const [hf, mf] = reg.fin.split(':').map(Number);
                        const minf = hf * 60 + mf;
                        minutoFin = Math.max(minutoFin, minf);
                    });
                }
            });

            if (minutoInicio >= 24 * 60 || minutoFin === 0) return 0;
            return Math.round((minutoFin - minutoInicio) / 60);
        }

        // Inicializar
        $(document).ready(function() {
            cargarMateriasDelParametro();
        });
    </script>
</body>
</html>
