@echo off
chcp 65001 >nul
title Conector WhatsApp - Consultorio Dr. Cortalezzi (Eleven Med)
color 0B

echo ====================================================================
echo    CORTALEZZI VISION / ELEVEN MED - VINCULADOR DE WHATSAPP (PC)
echo ====================================================================
echo.

:: Detectar carpeta del servicio (funciona tanto en raiz como dentro de whatsapp-service)
set "SERVICE_DIR=%~dp0whatsapp-service"
if exist "%~dp0server.js" set "SERVICE_DIR=%~dp0"

if not exist "%SERVICE_DIR%\server.js" (
    color 0C
    echo [ERROR] No se encontro server.js en "%SERVICE_DIR%".
    echo Asegurese de descomprimir toda la carpeta completa antes de abrir este archivo.
    echo.
    pause
    exit /b 1
)

cd /d "%SERVICE_DIR%"

:: Verificar si Node.js esta instalado (o en ruta estandar de Windows)
where node >nul 2>nul
if "%ERRORLEVEL%" NEQ "0" (
    if exist "%ProgramFiles%\nodejs\node.exe" (
        set "PATH=%ProgramFiles%\nodejs;%PATH%"
    )
)

where node >nul 2>nul
if "%ERRORLEVEL%" NEQ "0" (
    color 0E
    echo [AVISO] Node.js no esta instalado todavia en esta PC.
    echo Intentando instalar Node.js LTS automaticamente con Windows Winget...
    echo.
    winget install OpenJS.NodeJS.LTS --accept-package-agreements --accept-source-agreements --silent
    if exist "%ProgramFiles%\nodejs\node.exe" (
        set "PATH=%ProgramFiles%\nodejs;%PATH%"
    )
)

where node >nul 2>nul
if "%ERRORLEVEL%" NEQ "0" (
    color 0C
    echo.
    echo ====================================================================
    echo  ATENCION: Esta PC necesita instalar Node.js ^(1 minuto por unica vez^).
    echo  Se abrira la pagina oficial https://nodejs.org/es
    echo  Descargue el boton verde "Instalar Node.js (LTS)", instalelo con
    echo  "Siguiente -> Siguiente" y vuelva a abrir este archivo .BAT.
    echo ====================================================================
    start "" "https://nodejs.org/es"
    echo.
    pause
    exit /b 1
)

:: Instalar dependencias automaticamente la primera vez
if not exist "%SERVICE_DIR%\node_modules\@whiskeysockets\baileys" (
    echo [1/2] Preparando componentes de WhatsApp por primera vez ^(aguarde 30 a 60 seg^)...
    call npm install --no-audit --no-fund
    if "%ERRORLEVEL%" NEQ "0" (
        color 0C
        echo [ERROR] Hubo un inconveniente descargando dependencias. Verifique su conexion a Internet.
        pause
        exit /b 1
    )
)

:: Configurar conexion automatica hacia Eleven Med en produccion
set "PORT=3001"
if "%CLOUD_URL%"=="" set "CLOUD_URL=https://elevenmed.elevensistemas.com"
if "%API_KEY%"=="" set "API_KEY=eleven_med_secret_wa_key_2026"

echo.
echo [2/2] Iniciando puente seguro con %CLOUD_URL% ...
echo       Abriendo pantalla de Codigo QR en su navegador en 3 segundos...
echo       ^(Mantenga esta ventana minimizada mientras use WhatsApp en el consultorio^)
echo.

:: Abrir navegador automaticamente en el panel local del QR (una sola vez al iniciar)
start "" cmd /c "timeout /t 3 /nobreak >nul && start http://127.0.0.1:3001/vincular"

:loop
node server.js
echo.
echo [AVISO] El servicio se reiniciara automaticamente en 5 segundos...
timeout /t 5 /nobreak >nul
goto loop
