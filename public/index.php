<?php
// Incluir la conexión a la base de datos (PDO)
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
    <title>SMARTEDU - Carga y Filtro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="text-center mb-4">SMARTEDU </h3>
    
    <div id="seccionCarga" class="card p-4 shadow-lg rounded-4 mb-5">
        <h4 class="mb-3">1. Subir PDF de Horarios</h4>
        <div class="mb-3">
            <input type="file" id="pdfInput" accept="application/pdf" class="form-control" />
        </div>
        <button id="guardar" class="btn btn-primary w-100">Cargar PDF </button>
        <p id="mensajeCarga" class="mt-3 text-center"></p>
    </div>
    
    <hr class="my-5">

    <div id="seccionFiltro" class="card p-4 shadow-lg rounded-4" style="display:none;">
        <h4 class="mb-3">2. Selección de Materias (por Nombre)</h4>
        <form id="formMaterias" method="POST" action="guardar_materias.php">

            <div class="mb-4">
                <label class="form-label">Número de materias que deseas agregar:</label>
                <select id="numMaterias" class="form-select" name="numMaterias" required>
                    <option value="">-- Selecciona cantidad --</option>
                    <?php 
                    for ($i = 1; $i <= 6; $i++) {
                        echo "<option value='{$i}'>{$i}</option>";
                    }
                    ?>
                </select>
            </div>

            <div id="contenedorMaterias"></div>

            <button type="submit" class="btn btn-success w-100 mt-4">Generar Horario</button>
        </form>
    </div>

</div>

<script>
    // Configuración de PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js";
    
    // Opciones de materia cargadas desde PHP
    const MATERIAS_OPTIONS_HTML = "<?= $js_options_materias ?>";

    // Función para generar UUID (usada por el front-end)
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

    document.getElementById("guardar").addEventListener("click", async () => {
        const file = document.getElementById("pdfInput").files[0];
        const mensajeCarga = document.getElementById("mensajeCarga");
        
        if (!file) {
            mensajeCarga.className = 'mt-3 text-danger text-center';
            mensajeCarga.textContent = "Selecciona un PDF primero.";
            return;
        }

        mensajeCarga.className = 'mt-3 text-info text-center';
        mensajeCarga.textContent = "Procesando PDF, por favor espera...";

        try {
            // ... (Lógica de extracción de texto y parseo de horarios - Igual que antes) ...
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

            const regex =
                /(\d{5})\s+([A-Z]{4,})\s+(\d{3})\s+([A-Za-zÁÉÍÓÚÜÑ.\s]+?)\s+(OO\d|[A-Z]{2}\d?)\s+([A-Z])\s+(\d{4})\s*-\s*(\d{4})\s+([A-ZÁÉÍÓÚÜÑ.\-\s]+?)\s+(\w+\/\d+)/;

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
                mensajeCarga.className = 'mt-3 text-danger text-center';
                mensajeCarga.textContent = " No se detectaron registros válidos en el PDF.";
                return;
            }

            const session_id = generarUUID();
            
            // Envío de datos al servidor (database.php - Debe incluir la normalización)
            const response = await fetch("enpoint.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ horarios, session_id }),
            });

            const resultText = await response.text();
            if (!response.ok) {
                console.error("Error del servidor:", resultText);
                mensajeCarga.className = 'mt-3 text-danger text-center';
                mensajeCarga.textContent = " Error al guardar los datos: " + resultText;
                return;
            }

            // Éxito:
            mensajeCarga.className = 'mt-3 text-success text-center';
            mensajeCarga.textContent = "Carga exitosa. Ahora selecciona tus materias.";

            // 🔑 CAMBIO CLAVE: Solo mostramos la sección de filtro (no ocultamos la de carga)
            document.getElementById('seccionFiltro').style.display = 'block';

            // Opcional: Desplazarse hacia el formulario de filtro.
            document.getElementById('seccionFiltro').scrollIntoView({ behavior: 'smooth' });


        } catch (error) {
            console.error("Error al procesar:", error);
            mensajeCarga.className = 'mt-3 text-danger text-center';
            mensajeCarga.textContent = " Error al guardar los datos.";
        }
    });


    // Lógica de Selección de Materias (Igual que antes, usa jQuery)
    $(document).ready(function(){
        $('#numMaterias').on('change', function(){
            let cantidad = $(this).val();
            let contenedor = $('#contenedorMaterias');
            contenedor.empty(); // limpiar anteriores

            if(cantidad > 0){
                for(let i = 1; i <= cantidad; i++){
                    contenedor.append(`
                        <div class="mb-3">
                            <label class="form-label">Materia ${i}</label>
                            <select name="materia_${i}" class="form-select" required>
                                <option value="">-- Selecciona Materia --</option>
                                ${MATERIAS_OPTIONS_HTML}
                            </select>
                        </div>
                    `);
                }
            }
        });
    });
</script>
</body>
</html>