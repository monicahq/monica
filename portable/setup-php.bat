@echo off
REM ============================================
REM Monica Portable - PHP Setup Helper
REM Hilft beim Download und Setup von PHP
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable - PHP Setup
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set PHP_DIR=%SCRIPT_DIR%php

REM Prüfe ob PHP bereits vorhanden
if exist "%PHP_DIR%\php.exe" (
    echo PHP ist bereits installiert!
    echo.
    echo Version:
    "%PHP_DIR%\php.exe" -v
    echo.

    choice /C JN /M "Moechten Sie PHP neu installieren"
    if errorlevel 2 goto :end
    if errorlevel 1 goto :download
)

:download
echo.
echo ========================================
echo  PHP Download Anleitung
echo ========================================
echo.
echo AUTOMATISCHER DOWNLOAD:
echo.
echo Schritt 1: PowerShell wird verwendet um PHP herunterzuladen
echo           Dies kann einige Minuten dauern...
echo.

choice /C JN /M "Automatisch herunterladen"
if errorlevel 2 goto :manual
if errorlevel 1 goto :auto_download

:auto_download
echo.
echo Lade PHP 8.3 herunter...
echo.

REM Erstelle temporaeres Verzeichnis
set TEMP_DIR=%TEMP%\monica-php-download
if not exist "%TEMP_DIR%" mkdir "%TEMP_DIR%"

REM Download PHP mit PowerShell
echo Downloading... Bitte warten...

powershell -Command "& { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $ProgressPreference = 'SilentlyContinue'; Write-Host 'Pruefe neueste PHP Version...'; try { Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/php-8.3.14-Win32-vs16-x64.zip' -OutFile '%TEMP_DIR%\php.zip' -UseBasicParsing; Write-Host 'Download abgeschlossen!'; } catch { Write-Host 'Fehler beim Download. Bitte manuell herunterladen.'; exit 1; } }"

if errorlevel 1 goto :manual

echo Entpacke PHP...
powershell -Command "& { Expand-Archive -Path '%TEMP_DIR%\php.zip' -DestinationPath '%PHP_DIR%' -Force }"

if errorlevel 1 (
    echo Fehler beim Entpacken!
    goto :manual
)

REM Bereinige temporaere Dateien
rmdir /S /Q "%TEMP_DIR%"

goto :configure

:manual
echo.
echo ========================================
echo  MANUELLE INSTALLATION
echo ========================================
echo.
echo Bitte folgen Sie diesen Schritten:
echo.
echo 1. Oeffnen Sie im Browser:
echo    https://windows.php.net/download/
echo.
echo 2. Laden Sie herunter:
echo    "PHP 8.3.x VS16 x64 Thread Safe" (ZIP)
echo.
echo 3. Entpacken Sie die ZIP-Datei in:
echo    %PHP_DIR%
echo.
echo 4. Der Ordner sollte php.exe direkt enthalten, z.B.:
echo    %PHP_DIR%\php.exe
echo.
pause
echo.
echo Pruefe Installation...

if not exist "%PHP_DIR%\php.exe" (
    echo.
    echo FEHLER: php.exe wurde nicht gefunden!
    echo Bitte stellen Sie sicher, dass PHP korrekt installiert wurde.
    pause
    exit /b 1
)

:configure
echo.
echo ========================================
echo  PHP Konfiguration
echo ========================================
echo.

REM Erstelle php.ini falls nicht vorhanden
if not exist "%PHP_DIR%\php.ini" (
    echo Erstelle php.ini...

    if exist "%PHP_DIR%\php.ini-development" (
        copy "%PHP_DIR%\php.ini-development" "%PHP_DIR%\php.ini" > nul
    ) else (
        echo ; Monica Portable PHP Configuration > "%PHP_DIR%\php.ini"
    )
)

REM Setze extension_dir auf lokalen Pfad (WICHTIG!)
echo Konfiguriere extension_dir...
powershell -Command "$ini = Get-Content '%PHP_DIR%\php.ini'; if ($ini -match '^extension_dir') { $ini = $ini -replace '^extension_dir.*', 'extension_dir = \"ext\"' } else { $ini = @('extension_dir = \"ext\"') + $ini }; $ini | Set-Content '%PHP_DIR%\php.ini'"

REM Aktiviere benoetigte Extensions
echo Aktiviere PHP Extensions...
powershell -Command "(Get-Content '%PHP_DIR%\php.ini') -replace ';extension=fileinfo', 'extension=fileinfo' -replace ';extension=intl', 'extension=intl' -replace ';extension=mbstring', 'extension=mbstring' -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite' -replace ';extension=sqlite3', 'extension=sqlite3' -replace ';extension=openssl', 'extension=openssl' -replace ';extension=curl', 'extension=curl' -replace ';extension=gd', 'extension=gd' -replace ';extension=zip', 'extension=zip' | Set-Content '%PHP_DIR%\php.ini'"

REM Erhoehe memory_limit fuer Composer
echo Erhoehe Memory Limit...
powershell -Command "(Get-Content '%PHP_DIR%\php.ini') -replace '^memory_limit.*', 'memory_limit = 512M' | Set-Content '%PHP_DIR%\php.ini'"

REM Setze max_execution_time
echo Setze Max Execution Time...
powershell -Command "(Get-Content '%PHP_DIR%\php.ini') -replace '^max_execution_time.*', 'max_execution_time = 300' | Set-Content '%PHP_DIR%\php.ini'"

echo.
echo Pruefe PHP Konfiguration...
echo.
echo Extension Directory:
"%PHP_DIR%\php.exe" -r "echo ini_get('extension_dir');"
echo.
echo.

echo Geladene Extensions:
"%PHP_DIR%\php.exe" -m | findstr /C:"sqlite" /C:"pdo_sqlite" /C:"mbstring" /C:"intl" /C:"curl" /C:"fileinfo" /C:"zip"

echo.
echo ========================================
echo  Installation abgeschlossen!
echo ========================================
echo.
echo PHP Version:
"%PHP_DIR%\php.exe" -v
echo.
echo WICHTIG: Fuehren Sie nun setup-composer.bat aus,
echo um die Monica-Abhaengigkeiten zu installieren!
echo.

:end
pause
