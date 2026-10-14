@echo off
setlocal

REM ==========================================
REM RESPALDO BASE DE DATOS bienes_informaticos
REM ==========================================

REM Configuración
set DB_NAME=bienes_informaticos
set DB_USER=root
set DB_HOST=127.0.0.1
set DB_PORT=3306

REM Carpeta donde se guardarán los respaldos
set BACKUP_DIR=%~dp0respaldos

REM Crear carpeta si no existe
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM Obtener fecha y hora
for /f "tokens=1-3 delims=/" %%a in ("%date%") do (
    set DD=%%a
    set MM=%%b
    set YYYY=%%c
)

set HH=%time:~0,2%
set MN=%time:~3,2%
set SS=%time:~6,2%

REM Reemplazar espacio inicial de la hora
set HH=%HH: =0%

REM Nombre del archivo
set BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_%YYYY%%MM%%DD%_%HH%%MN%%SS%.sql

echo.
echo ==========================================
echo   RESPALDO DE BASE DE DATOS
echo ==========================================
echo.
echo Base de datos: %DB_NAME%
echo Archivo: %BACKUP_FILE%
echo.

REM Ejecutar mysqldump
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" ^
--host=%DB_HOST% ^
--port=%DB_PORT% ^
--user=%DB_USER% ^
--routines ^
--triggers ^
--events ^
--single-transaction ^
--add-drop-database ^
--databases %DB_NAME% > "%BACKUP_FILE%"

REM Verificar resultado
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==========================================
    echo RESPALDO COMPLETADO CORRECTAMENTE
    echo ==========================================
    echo.
    echo Archivo generado:
    echo %BACKUP_FILE%
) else (
    echo.
    echo ==========================================
    echo ERROR AL GENERAR EL RESPALDO
    echo ==========================================
    echo.
)

echo.
pause
endlocal