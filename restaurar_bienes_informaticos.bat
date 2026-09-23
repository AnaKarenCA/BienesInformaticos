@echo off
setlocal

set DB_NAME=bienes_informaticos
set DB_USER=root
set DB_HOST=127.0.0.1
set DB_PORT=3306

set MYSQL="C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"

echo.
echo ==========================================
echo RESTAURACION DE bienes_informaticos
echo ==========================================
echo.

set /p BACKUP_FILE=Escribe la ruta completa del archivo .sql:

if not exist "%BACKUP_FILE%" (
    echo.
    echo ERROR: El archivo no existe.
    pause
    exit /b 1
)

echo.
echo Se restaurara:
echo %BACKUP_FILE%
echo.
pause

%MYSQL% ^
--host=%DB_HOST% ^
--port=%DB_PORT% ^
--user=%DB_USER% ^
< "%BACKUP_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==========================================
    echo RESTAURACION COMPLETADA
    echo ==========================================
) else (
    echo.
    echo ==========================================
    echo ERROR EN LA RESTAURACION
    echo ==========================================
)

echo.
pause
endlocal