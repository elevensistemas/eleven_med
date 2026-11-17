@echo off
chcp 65001 >nul
title Configurar Inicio Automatico con Windows - WhatsApp Eleven Med
color 0A

set "TARGET_BAT=%~dp0Vincular_WhatsApp_Consultorio.bat"
set "STARTUP_DIR=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
set "SHORTCUT_PATH=%STARTUP_DIR%\WhatsApp_ElevenMed.lnk"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ws = New-Object -ComObject WScript.Shell; $s = $ws.CreateShortcut('%SHORTCUT_PATH%'); $s.TargetPath = '%TARGET_BAT%'; $s.Arguments = '--silent'; $s.WorkingDirectory = '%~dp0'; $s.WindowStyle = 7; $s.Description = 'Conector WhatsApp Eleven Med'; $s.Save()"

echo ====================================================================
echo  LISTO: El conector de WhatsApp de Eleven Med se iniciara solo
echo  y minimizado cada vez que enciendan esta PC en el consultorio.
echo ====================================================================
echo.
pause
