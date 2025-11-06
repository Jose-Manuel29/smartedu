--CREATE LOGIN EquipoUsuario WITH PASSWORD = 'Hola';
--USE SMARTEDU;
--CREATE USER EquipoUsuario FOR LOGIN EquipoUsuario;
--ALTER ROLE db_datareader ADD MEMBER EquipoUsuario;
--ALTER ROLE db_datawriter ADD MEMBER EquipoUsuario;

IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'SMARTEDU')
BEGIN   
    CREATE DATABASE SMARTEDU;
END
GO

USE SMARTEDU;
GO

-- ============================================

----se hixo ksksk KDNKDKDD pruebaASHOIDHASHFIAHFAÑEI

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='horarios' AND xtype='U')
BEGIN
    CREATE TABLE horarios (
        NRC NVARCHAR(10),
        Clave NVARCHAR(20),
        Materia NVARCHAR(150),
        Secc NVARCHAR(10),
        Dias NVARCHAR(10),
        Hora NVARCHAR(20),
        Profesor NVARCHAR(100),
        Salon NVARCHAR(50),
        session_id UNIQUEIDENTIFIER DEFAULT NEWID() -- ?? Identificador único por carga
    );
END
GO
select * from horarios


IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Materia' AND xtype='U')
BEGIN
    CREATE TABLE Materia (
        id INT IDENTITY PRIMARY KEY,
        clave NVARCHAR(20) UNIQUE,
        nombre NVARCHAR(150)
    );
END
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Profesor' AND xtype='U')
BEGIN
    CREATE TABLE Profesor (
        id INT IDENTITY PRIMARY KEY,
        nombre NVARCHAR(100) UNIQUE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Salon' AND xtype='U')
BEGIN
    CREATE TABLE Salon (
        id INT IDENTITY PRIMARY KEY,
        nombre NVARCHAR(50) UNIQUE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='Horario' AND xtype='U')
BEGIN   
    CREATE TABLE Horario (
        id INT IDENTITY PRIMARY KEY,
        NRC NVARCHAR(10),
        secc NVARCHAR(10),
        dias NVARCHAR(10),
        hora NVARCHAR(20),
        id_materia INT FOREIGN KEY REFERENCES Materia(id),
        id_profesor INT FOREIGN KEY REFERENCES Profesor(id),
        id_salon INT FOREIGN KEY REFERENCES Salon(id),
        session_id UNIQUEIDENTIFIER  -- ?? Mismo ID que la carga original
    );
END
GO
-- 1?? Insertar materias únicas
WITH cte_materia AS (
    SELECT Clave, Materia,
           ROW_NUMBER() OVER (PARTITION BY Clave ORDER BY Materia) AS rn
    FROM horarios
)
INSERT INTO Materia (clave, nombre)
SELECT Clave, Materia
FROM cte_materia
WHERE rn = 1
  AND Clave NOT IN (SELECT clave FROM Materia);
PRINT '? Materias insertadas';
GO

-- 2?? Insertar profesores únicos
WITH cte_prof AS (
    SELECT Profesor,
           ROW_NUMBER() OVER (PARTITION BY Profesor ORDER BY Profesor) AS rn
    FROM horarios
)
INSERT INTO Profesor (nombre)
SELECT Profesor
FROM cte_prof
WHERE rn = 1
  AND Profesor NOT IN (SELECT nombre FROM Profesor);
PRINT '? Profesores insertados';
GO

-- 3?? Insertar salones únicos
WITH cte_salon AS (
    SELECT Salon,
           ROW_NUMBER() OVER (PARTITION BY Salon ORDER BY Salon) AS rn
    FROM horarios
)
INSERT INTO Salon (nombre)
SELECT Salon
FROM cte_salon
WHERE rn = 1
  AND Salon NOT IN (SELECT nombre FROM Salon);
PRINT '? Salones insertados';
GO

INSERT INTO Horario (NRC, secc, dias, hora, id_materia, id_profesor, id_salon, session_id)
SELECT
    h.NRC,
    h.Secc,
    h.Dias,
    h.Hora,
    m.id,
    p.id,
    s.id,
    h.session_id
FROM horarios h
JOIN Materia m ON h.Clave = m.clave
JOIN Profesor p ON h.Profesor = p.nombre
JOIN Salon s ON h.Salon = s.nombre;
PRINT '? Horarios normalizados insertados';
GO



PRINT '? DATOS NORMALIZADOS CORRECTAMENTE';
SELECT COUNT(*) AS TotalHorarios, session_id FROM horarios GROUP BY session_id;
SELECT COUNT(*) AS TotalNormalizados, session_id FROM Horario GROUP BY session_id;

SELECT  * FROM Materia;
SELECT  * FROM Profesor;
SELECT  * FROM Salon;
SELECT * FROM Horario;
SELECT * FROM horarios;

-- DROP TABLE Horario;
-- DROP TABLE Materia;
-- DROP TABLE Profesor;
-- DROP TABLE Salon;
-- DROP TABLE horarios;
GO
DELETE FROM Horario;
DELETE FROM Materia;
DELETE FROM Profesor;
DELETE FROM Salon;
delete from horarios;
GO
