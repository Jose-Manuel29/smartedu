<?php include 'conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Filtros de Horarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3 class="mb-3 text-center">Filtros para generación de horarios</h3>
    <form id="formFiltros" method="GET" action="mostrar_horarios.php" class="card p-3 shadow-sm">

        <!-- NRC -->
        <div class="mb-3">
            <label class="form-label">NRC</label>
            <select class="form-select" name="nrc">
                <option value="">-- Selecciona un NRC --</option>
                <?php
                $query = "SELECT DISTINCT NRC FROM Horario ORDER BY NRC";
                $result = sqlsrv_query($conn, $query);
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    echo '<option value="'.$row['NRC'].'">'.$row['NRC'].'</option>';
                }
                ?>
            </select>
        </div>

        <!-- Sección -->
        <div class="mb-3">
            <label class="form-label">Sección</label>
            <select class="form-select" name="secc">
                <option value="">-- Selecciona una sección --</option>
                <?php
                $query = "SELECT DISTINCT secc FROM Horario ORDER BY secc";
                $result = sqlsrv_query($conn, $query);
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    echo '<option value="'.$row['secc'].'">'.$row['secc'].'</option>';
                }
                ?>
            </select>
        </div>

        <!-- Materia -->
        <div class="mb-3">
            <label class="form-label">Materia</label>
            <select class="form-select" name="materia">
                <option value="">-- Selecciona una materia --</option>
                <?php
                $query = "SELECT id, nombre FROM Materia ORDER BY nombre";
                $result = sqlsrv_query($conn, $query);
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['nombre']).'</option>';
                }
                ?>
            </select>
        </div>

        <!-- Profesor -->
        <div class="mb-3">
            <label class="form-label">Profesor</label>
            <select class="form-select" name="profesor">
                <option value="">-- Selecciona un profesor --</option>
                <?php
                $query = "SELECT id, nombre FROM Profesor ORDER BY nombre";
                $result = sqlsrv_query($conn, $query);
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['nombre']).'</option>';
                }
                ?>
            </select>
        </div>

        <!-- Días -->
        <div class="mb-3">
            <label class="form-label">Días</label>
            <select class="form-select" name="dias">
                <option value="">-- Selecciona días --</option>
                <?php
                $query = "SELECT DISTINCT dias FROM Horario ORDER BY dias";
                $result = sqlsrv_query($conn, $query);
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    echo '<option value="'.$row['dias'].'">'.$row['dias'].'</option>';
                }
                ?>
            </select>
        </div>

        <!-- Hora -->
        <div class="mb-3">
            <label class="form-label">Hora</label>
            <select class="form-select" name="hora">
                <option value="">-- Selecciona hora --</option>
                <?php
                $query = "SELECT DISTINCT hora FROM Horario ORDER BY hora";
                $result = sqlsrv_query($conn, $query);
                while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                    echo '<option value="'.$row['hora'].'">'.$row['hora'].'</option>';
                }
                ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100">Aplicar filtros</button>
    </form>
</div>
</body>
</html>
