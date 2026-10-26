@echo off
echo ========================================================
echo       Iniciando Servidores de Desarrollo - Eleven Med
echo ========================================================
echo.
echo Los siguientes servicios se abriran en ventanas separadas:
echo.

:: Iniciar MySQL si no esta corriendo
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="1" (
    echo Iniciando MySQL (XAMPP)...
    start "MySQL" cmd /c "C:\xampp\mysql_start.bat"
    timeout /t 3 /nobreak >nul
)

:: Iniciar Vite
start "Vite (Frontend)" cmd /k "echo Iniciando Vite... && npm run dev"

:: Iniciar Servidor de Laravel
start "Servidor Laravel (HTTP)" cmd /k "echo Iniciando Servidor Web... && C:\xampp\php\php.exe artisan serve --port=8010"

:: Iniciar Laravel Reverb
start "Laravel Reverb (Websockets)" cmd /k "echo Iniciando Reverb... && C:\xampp\php\php.exe artisan reverb:start"

:: Iniciar Queue Worker
start "Queue Worker (Mensajes/DB)" cmd /k "echo Iniciando Queue Listener... && C:\xampp\php\php.exe artisan queue:listen --timeout=0"

:: Iniciar WhatsApp Gateway (Baileys)
start "WhatsApp Service (QR & Gateway)" cmd /k "cd whatsapp-service && echo Iniciando WhatsApp Gateway... && node server.js"

echo Servicios iniciados correctamente. No cierres las ventanas negras que se abrieron.
echo Ya puedes probar la aplicacion en tu navegador.
echo.
pause
