@echo off
REM ============================================
REM Monica Portable - Haupt-Launcher
REM Schneller Zugriff von der Root-Ebene
REM ============================================

echo.
echo ========================================
echo  Monica Portable - Launcher
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set PORTABLE_DIR=%SCRIPT_DIR%portable

REM Prüfe ob portable-Verzeichnis existiert
if not exist "%PORTABLE_DIR%" (
    echo FEHLER: Portable-Verzeichnis nicht gefunden!
    echo Erwarteter Pfad: %PORTABLE_DIR%
    pause
    exit /b 1
)

REM Prüfe ob Monica-Starter existiert
if not exist "%PORTABLE_DIR%\start-monica.bat" (
    echo FEHLER: start-monica.bat nicht gefunden!
    echo.
    echo Bitte stellen Sie sicher, dass die portable Installation
    echo vollstaendig ist.
    pause
    exit /b 1
)

echo Monica Portable wird gestartet...
echo.

REM Wechsle ins portable-Verzeichnis und starte Monica
cd /d "%PORTABLE_DIR%"
call start-monica.bat

REM Kehre zurück ins ursprüngliche Verzeichnis
cd /d "%SCRIPT_DIR%"
