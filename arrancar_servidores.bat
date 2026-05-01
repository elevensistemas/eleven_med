@echo off
echo ========================================================
echo       Iniciando Servidores de Desarrollo - Eleven Med
echo ========================================================
echo.
echo Los siguientes servicios se abriran en ventanas separadas:
echo.

:: Iniciar Vite
start "Vite (Frontend)" cmd /k "echo Iniciando Vite... && npm run dev"

:: Iniciar Servidor de Laravel
start "Servidor Laravel (HTTP)" cmd /k "echo Iniciando Servidor Web... && C:\xampp\php\php.exe artisan serve --port=8010"

:: Iniciar Laravel Reverb
start "Laravel Reverb (Websockets)" cmd /k "echo Iniciando Reverb... && C:\xampp\php\php.exe artisan reverb:start"

:: Iniciar Queue Worker
start "Queue Worker (Mensajes/DB)" cmd /k "echo Iniciando Queue Listener... && C:\xampp\php\php.exe artisan queue:listen --timeout=0"

echo Servicios iniciados correctamente. No cierres las ventanas negras que se abrieron.
echo Ya puedes probar la aplicacion en tu navegador.
echo.
pause
