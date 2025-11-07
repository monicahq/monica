@echo off
REM ============================================
REM Monica Portable - Windows Starter
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable CRM wird gestartet...
echo ========================================
echo.

REM Setze Pfade
set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..
set PHP_DIR=%SCRIPT_DIR%php
set PHP_EXE=%PHP_DIR%\php.exe

REM Prüfe ob PHP vorhanden ist
if not exist "%PHP_EXE%" (
    echo FEHLER: PHP wurde nicht gefunden!
    echo Bitte stellen Sie sicher, dass PHP im Ordner "%PHP_DIR%" installiert ist.
    echo.
    echo Siehe SETUP.md fuer Installationsanweisungen.
    pause
    exit /b 1
)

REM Prüfe ob .env existiert, falls nicht verwende .env.portable
if not exist "%APP_ROOT%\.env" (
    if exist "%APP_ROOT%\.env.portable" (
        echo Kopiere .env.portable zu .env...
        copy "%APP_ROOT%\.env.portable" "%APP_ROOT%\.env" > nul
    ) else (
        echo FEHLER: Keine .env Konfigurationsdatei gefunden!
        pause
        exit /b 1
    )
)

REM Wechsle ins App-Verzeichnis
cd /d "%APP_ROOT%"

REM Prüfe ob bereits initialisiert
if not exist "%APP_ROOT%\database\database.sqlite" (
    echo.
    echo ========================================
    echo  Erste Initialisierung erkannt!
    echo  Bitte warten, dies kann etwas dauern...
    echo ========================================
    echo.

    REM Rufe Initialisierungsscript auf
    call "%SCRIPT_DIR%init-monica.bat"

    if errorlevel 1 (
        echo.
        echo FEHLER bei der Initialisierung!
        pause
        exit /b 1
    )
)

REM Finde einen freien Port (Standard: 8000)
set PORT=8000
netstat -an | findstr ":%PORT%" > nul
if %errorlevel% equ 0 (
    echo Port %PORT% ist bereits belegt, versuche Port 8001...
    set PORT=8001
)

echo.
echo Starte PHP Development Server auf Port %PORT%...
echo.
echo ========================================
echo  Monica CRM ist bereit!
echo ========================================
echo.
echo  URL: http://127.0.0.1:%PORT%
echo.
echo  Der Browser sollte sich automatisch oeffnen.
echo  Falls nicht, klicken Sie auf: start.html
echo.
echo  Zum Beenden: Druecken Sie STRG+C oder
echo  schliessen Sie dieses Fenster.
echo ========================================
echo.

REM Öffne Browser nach kurzer Verzögerung
start "" "%SCRIPT_DIR%start.html"

REM Starte PHP Server
"%PHP_EXE%" -S 127.0.0.1:%PORT% -t public

REM Cleanup bei Beendigung
echo.
echo Monica wurde beendet.
pause
