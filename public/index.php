<?php
// Incluir la conexión a la base de datos (PDO)
// Asegúrate de que la ruta sea correcta en tu servidor
require_once __DIR__ . '/../private/db/database.php';

$conn = get_db_connection();
$options_materias = "";
if ($conn) {
    try {
        $sql_materias = "SELECT DISTINCT m.nombre AS Materia FROM Materia m ORDER BY m.nombre ASC";
        $stmt = sqlsrv_query($conn, $sql_materias);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $nombre = $row['Materia'];
                $options_materias .= "<option value='" . htmlspecialchars($nombre, ENT_QUOTES) . "'>" . htmlspecialchars($nombre) . "</option>";
            }
        } else {
            error_log("[index.php] Error al ejecutar consulta materias: " . print_r(sqlsrv_errors(), true));
            $options_materias = "<option value=''>-- Error al cargar materias --</option>";
        }
    } catch (Exception $e) {
        error_log("[index.php] Excepción al obtener materias: " . $e->getMessage());
        $options_materias = "<option value=''>-- Error al cargar materias --</option>";
    }
} else {
    $options_materias = "<option value=''>-- Error: sin conexión a BD --</option>";
}

// Limpiamos las opciones para JS
$js_options_materias = str_replace(["\n", "\r"], "", $options_materias);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMARTEDU - Carga y Filtro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/estilos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>
<body class="py-5">

<div class="container main-container">
    
    <div class="text-center mb-5">
        <h1 class="app-title"><i class="fas fa-graduation-cap text-primary me-2"></i>SMARTEDU</h1>
        <p class="text-muted">Sistema Inteligente de Gestión de Horarios</p>
    </div>
    
    <div id="seccionCarga" class="card custom-card mb-4">
        <div class="card-body p-4">
            <h5 class="card-title mb-4 d-flex align-items-center">
                <span class="step-badge">1</span> Subir PDF de Horarios
            </h5>
            
            <div class="mb-3">
                <label for="pdfInput" class="form-label text-muted small fw-bold">Seleccionar archivo PDF</label>
                <div class="input-group">
                    <input type="file" id="pdfInput" accept="application/pdf" class="form-control" />
                    <button id="guardar" class="btn btn-primary btn-custom-primary" type="button">
                        <i class="fas fa-cloud-upload-alt me-2"></i>Cargar PDF
                    </button>
                </div>
            </div>
            
            <div id="mensajeCargaContainer" class="mt-3" style="display:none;">
                 <div id="mensajeCargaAlert" class="alert d-flex align-items-center rounded-3" role="alert">
                    <i id="mensajeIcono" class="fas fa-info-circle me-2"></i>
                    <div id="mensajeTexto"></div>
                 </div>
            </div>
        </div>
    </div>
    
    <div id="seccionFiltro" class="card custom-card" style="display:none;">
        <div class="card-body p-4">
            <h5 class="card-title mb-4 d-flex align-items-center">
                <span class="step-badge">2</span> Selección de Materias
            </h5>
            
            <form id="formMaterias" method="POST" action="guardar_materias.php">

                <div class="mb-4">
                    <label class="form-label fw-bold text-secondary">Número de materias que deseas agregar:</label>
                    <select id="numMaterias" class="form-select" name="numMaterias" required>
                        <option value="">-- Selecciona cantidad --</option>
                        <?php 
                        for ($i = 1; $i <= 6; $i++) {
                            echo "<option value='{$i}'>{$i}</option>";
                        }
                        ?>
                    </select>
                </div>

                <hr class="text-muted opacity-25 my-4">

                <div id="contenedorMaterias" class="row g-3"></div>

                <div class="d-grid gap-2 mt-5">
                    <button type="submit" class="btn btn-success btn-custom-success btn-lg shadow-sm">
                        <i class="fas fa-cogs me-2"></i>Generar Horario
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    // Configuración de PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
    
    // Opciones de materia cargadas desde PHP
    const MATERIAS_OPTIONS_HTML = "<?= $js_options_materias ?>";

    // Función para generar UUID
    function generarUUID() {
        return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(
            /[xy]/g,
            function (c) {
                const r = (Math.random() * 16) | 0,
                    v = c === "x" ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            }
        );
    }

    // Lógica de Carga de PDF
    document.getElementById("guardar").addEventListener("click", async () => {
        const fileInput = document.getElementById("pdfInput");
        const file = fileInput.files[0];
        
        // Referencias para el feedback visual
        const msgContainer = document.getElementById("mensajeCargaContainer");
        const msgAlert = document.getElementById("mensajeCargaAlert");
        const msgText = document.getElementById("mensajeTexto");
        const msgIcon = document.getElementById("mensajeIcono");

        // Función helper para mostrar mensajes
        const mostrarMensaje = (tipo, texto) => {
            msgContainer.style.display = 'block';
            msgAlert.className = `alert alert-${tipo} d-flex align-items-center rounded-3`;
            msgText.textContent = texto;
            if(tipo === 'danger') msgIcon.className = "fas fa-exclamation-triangle me-2";
            else if(tipo === 'success') msgIcon.className = "fas fa-check-circle me-2";
            else msgIcon.className = "fas fa-spinner fa-spin me-2";
        };

        if (!file) {
            mostrarMensaje('danger', "Por favor, selecciona un archivo PDF primero.");
            return;
        }

        mostrarMensaje('info', "Procesando PDF, por favor espera...");

        try {
            const arrayBuffer = await file.arrayBuffer();
            const pdfDoc = await pdfjsLib.getDocument(arrayBuffer).promise;
            let textoCompleto = "";

            for (let i = 1; i <= pdfDoc.numPages; i++) {
                const page = await pdfDoc.getPage(i);
                const content = await page.getTextContent();
                const strings = content.items
                    .map((item) => item.str.trim())
                    .filter((s) => s.length > 0);
                textoCompleto += strings.join(" ") + " ";
            }

            const bloques = textoCompleto
                .split(/(?=\d{5}\s[A-Z]{4,})/g)
                .map((b) => b.trim())
                .filter((b) => b.length > 0);
            
            const horarios = [];
            const regex = /(\d{5})\s+([A-Z]{4,})\s+(\d{3})\s+([A-Za-zÁÉÍÓÚÜÑ.\s]+?)\s+(OO\d|[A-Z]{2}\d?)\s+([A-Z])\s+(\d{4})\s*-\s*(\d{4})\s+([A-ZÁÉÍÓÚÜÑ.\-\s]+?)\s+(\w+\/\d+)/;

            for (const bloque of bloques) {
                const match = bloque.match(regex);
                if (match) {
                    horarios.push({
                        NRC: match[1],
                        Clave: `${match[2]} ${match[3]}`,
                        Materia: match[4].trim(),
                        Secc: match[5],
                        Días: match[6],
                        Hora: `${match[7]}-${match[8]}`,
                        Profesor: match[9].trim(),
                        Salón: match[10],
                    });
                }
            }

            if (horarios.length === 0) {
                mostrarMensaje('danger', "No se detectaron registros válidos de horarios en el PDF.");
                return;
            }

            const session_id = generarUUID();
            
            // Envío al servidor
            const response = await fetch("enpoint.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ horarios, session_id }),
            });

            const resultText = await response.text();
            if (!response.ok) {
                console.error("Error del servidor:", resultText);
                mostrarMensaje('danger', "Error al guardar los datos en BD: " + resultText);
                return;
            }

            // ÉXITO
            mostrarMensaje('success', "Carga exitosa. Ahora selecciona tus materias abajo.");
            
            // Mostrar la sección 2 con animación
            const seccionFiltro = document.getElementById('seccionFiltro');
            seccionFiltro.style.display = 'block';
            seccionFiltro.scrollIntoView({ behavior: 'smooth', block: 'start' });

        } catch (error) {
            console.error("Error al procesar:", error);
            mostrarMensaje('danger', "Ocurrió un error inesperado al procesar el archivo.");
        }
    });


    // Lógica de Generación Dinámica de Inputs (Adaptada al nuevo diseño)
    $(document).ready(function(){
        $('#numMaterias').on('change', function(){
            let cantidad = $(this).val();
            let contenedor = $('#contenedorMaterias');
            contenedor.empty(); // Limpiar anteriores

            if(cantidad > 0){
                for(let i = 1; i <= cantidad; i++){
                    // Aquí usamos el HTML con estilo Bootstrap Input-Group e Icono
                    let htmlTemplate = `
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Materia ${i}</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-book text-muted"></i></span>
                                <select name="materia_${i}" class="form-select border-start-0 ps-0" required>
                                    <option value="">-- Selecciona Materia --</option>
                                    ${MATERIAS_OPTIONS_HTML}
                                </select>
                            </div>
                        </div>
                    `;
                    contenedor.append(htmlTemplate);
                }
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>