@echo off
REM ============================================
REM Monica Portable - Composer Setup
REM Installiert Composer und Monica Dependencies
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable - Composer Setup
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..
set PHP_DIR=%SCRIPT_DIR%php
set PHP_EXE=%PHP_DIR%\php.exe
set COMPOSER_PHAR=%SCRIPT_DIR%composer.phar

echo Dieses Script arbeitet mit RELATIVEN Pfaden.
echo Der Monica-Ordner kann ueberall liegen!
echo.

REM Prüfe Struktur
if not exist "%PHP_EXE%" (
    echo [FEHLER] PHP nicht gefunden!
    echo          Relativer Pfad: .\php\php.exe
    echo.
    echo Bitte fuehren Sie zuerst setup-php.bat aus.
    echo.
    pause
    exit /b 1
)

if not exist "%APP_ROOT%\composer.json" (
    echo [FEHLER] composer.json nicht gefunden!
    echo          Relativer Pfad: ..\composer.json
    echo.
    echo Sind Sie im richtigen Verzeichnis?
    echo Dieses Script muss aus portable\ ausgefuehrt werden!
    echo.
    pause
    exit /b 1
)

echo [OK] PHP gefunden: .\php\php.exe
echo [OK] composer.json gefunden: ..\composer.json
echo.

REM Prüfe ob Composer bereits vorhanden
if exist "%COMPOSER_PHAR%" (
    echo Composer ist bereits installiert!
    echo.

    choice /C JN /M "Composer neu installieren"
    if errorlevel 2 goto :install_dependencies
    if errorlevel 1 goto :download_composer
)

:download_composer
echo.
echo ========================================
echo  Composer Download
echo ========================================
echo.

echo Lade Composer herunter...
echo Dies kann einige Minuten dauern...
echo.

REM Download Composer Installer
set COMPOSER_SETUP=%TEMP%\composer-setup.php

powershell -Command "& { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; try { Invoke-WebRequest -Uri 'https://getcomposer.org/installer' -OutFile '%COMPOSER_SETUP%' -UseBasicParsing; Write-Host 'Composer Installer heruntergeladen!'; } catch { Write-Host 'Fehler beim Download!'; throw } }"

if errorlevel 1 (
    echo.
    echo FEHLER: Composer Download fehlgeschlagen!
    echo.
    echo Moegliche Ursachen:
    echo  - Keine Internetverbindung
    echo  - Firewall blockiert Download
    echo  - Proxy-Einstellungen erforderlich
    echo.
    echo MANUELLE INSTALLATION:
    echo  1. Besuchen Sie: https://getcomposer.org/download/
    echo  2. Laden Sie composer.phar herunter
    echo  3. Speichern Sie es in: %SCRIPT_DIR%
    echo  4. Fuehren Sie dieses Script erneut aus
    echo.
    pause
    exit /b 1
)

REM Installiere Composer
echo Installiere Composer...
"%PHP_EXE%" "%COMPOSER_SETUP%" --install-dir="%SCRIPT_DIR%" --filename=composer.phar

if errorlevel 1 (
    echo.
    echo FEHLER: Composer Installation fehlgeschlagen!
    del "%COMPOSER_SETUP%" 2>nul
    pause
    exit /b 1
)

REM Bereinige
del "%COMPOSER_SETUP%" 2>nul

echo.
echo Composer erfolgreich installiert!

:install_dependencies
echo.
echo ========================================
echo  Monica Dependencies installieren
echo ========================================
echo.

cd /d "%APP_ROOT%"

echo ACHTUNG: Dies kann 5-15 Minuten dauern!
echo Bitte haben Sie Geduld und schliessen Sie das Fenster nicht.
echo.

REM Prüfe ob composer.json existiert
if not exist "composer.json" (
    echo FEHLER: composer.json nicht gefunden!
    echo Sind Sie im richtigen Verzeichnis?
    pause
    exit /b 1
)

echo Starte Composer Install...
echo (Fortschritt wird angezeigt)
echo.

REM Setze Composer Variablen
set COMPOSER_HOME=%SCRIPT_DIR%\.composer
set COMPOSER_CACHE_DIR=%SCRIPT_DIR%\.composer\cache

REM Erstelle .composer Verzeichnis
if not exist "%COMPOSER_HOME%" mkdir "%COMPOSER_HOME%"
if not exist "%COMPOSER_CACHE_DIR%" mkdir "%COMPOSER_CACHE_DIR%"

REM Führe Composer Install aus
"%PHP_EXE%" "%COMPOSER_PHAR%" install --no-dev --optimize-autoloader --no-interaction

if errorlevel 1 (
    echo.
    echo ========================================
    echo  FEHLER!
    echo ========================================
    echo.
    echo Composer Install ist fehlgeschlagen!
    echo.
    echo Moegliche Ursachen:
    echo  - Keine Internetverbindung
    echo  - Zu wenig Arbeitsspeicher
    echo  - PHP Extensions fehlen
    echo.
    echo Versuchen Sie:
    echo  1. PHP Extensions pruefen: setup-php.bat erneut ausfuehren
    echo  2. Internetverbindung pruefen
    echo  3. Als Administrator ausfuehren
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Installation erfolgreich!
echo ========================================
echo.

REM Zeige installierte Pakete
echo Installierte Hauptpakete:
"%PHP_EXE%" "%COMPOSER_PHAR%" show --installed --name-only | findstr "laravel\|monica" | head -10

echo.
echo Vendor-Verzeichnis:
if exist "vendor\autoload.php" (
    echo  [OK] vendor\autoload.php gefunden
) else (
    echo  [FEHLER] vendor\autoload.php NICHT gefunden!
)

echo.
echo Monica ist jetzt bereit zum Starten!
echo.
echo Naechster Schritt: start-monica.bat ausfuehren
echo.

pause
