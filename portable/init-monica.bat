@echo off
REM ============================================
REM Monica Portable - Initialisierung
REM Wird beim ersten Start automatisch aufgerufen
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..
set PHP_DIR=%SCRIPT_DIR%php
set PHP_EXE=%PHP_DIR%\php.exe

cd /d "%APP_ROOT%"

echo ========================================
echo  Monica Initialisierung
echo ========================================
echo.

REM Prüfe ob vendor/ existiert
if not exist "%APP_ROOT%\vendor\autoload.php" (
    echo.
    echo ========================================
    echo  FEHLER: Composer Dependencies fehlen!
    echo ========================================
    echo.
    echo Die Monica-Anwendung benoetigt Composer-Pakete.
    echo.
    echo Bitte fuehren Sie diese Schritte aus:
    echo  1. setup-composer.bat ausfuehren
    echo  2. Warten bis Installation abgeschlossen ist
    echo  3. Monica erneut starten
    echo.
    echo Alternativ koennen Sie auch manuell installieren:
    echo  1. PHP Composer herunterladen
    echo  2. Im monica-Ordner ausfuehren: composer install --no-dev
    echo.
    pause
    exit /b 1
)

REM Erstelle notwendige Verzeichnisse
echo [1/8] Erstelle Verzeichnisse...
if not exist "database" mkdir database
if not exist "storage\logs" mkdir storage\logs
if not exist "storage\framework\cache" mkdir storage\framework\cache
if not exist "storage\framework\sessions" mkdir storage\framework\sessions
if not exist "storage\framework\views" mkdir storage\framework\views
if not exist "storage\app\public" mkdir storage\app\public
if not exist "bootstrap\cache" mkdir bootstrap\cache

REM Prüfe PHP Extensions
echo [2/8] Pruefe PHP Extensions...
set MISSING_EXT=0

"%PHP_EXE%" -m | findstr /C:"pdo_sqlite" > nul
if %errorlevel% neq 0 (
    echo    FEHLER: pdo_sqlite Extension fehlt!
    set MISSING_EXT=1
)

"%PHP_EXE%" -m | findstr /C:"mbstring" > nul
if %errorlevel% neq 0 (
    echo    FEHLER: mbstring Extension fehlt!
    set MISSING_EXT=1
)

"%PHP_EXE%" -m | findstr /C:"fileinfo" > nul
if %errorlevel% neq 0 (
    echo    WARNUNG: fileinfo Extension fehlt!
)

if %MISSING_EXT% equ 1 (
    echo.
    echo Kritische PHP Extensions fehlen!
    echo Bitte fuehren Sie setup-php.bat erneut aus.
    echo.
    pause
    exit /b 1
) else (
    echo    Alle kritischen Extensions vorhanden.
)

REM Generiere App Key falls nötig
echo [3/8] Generiere Application Key...
findstr "APP_KEY=base64:" .env > nul
if %errorlevel% neq 0 (
    "%PHP_EXE%" artisan key:generate --force
    if %errorlevel% neq 0 (
        echo    FEHLER bei Key-Generierung!
        exit /b 1
    )
) else (
    echo    Key bereits vorhanden, ueberspringe...
)

REM Erstelle SQLite Datenbank
echo [4/8] Erstelle SQLite Datenbank...
if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    echo    Datenbank erstellt: database\database.sqlite
) else (
    echo    Datenbank existiert bereits.
)

REM Setze Berechtigungen (wichtig für Laravel)
echo [5/8] Konfiguriere Berechtigungen...
if exist "%APP_ROOT%\storage" (
    attrib -R "%APP_ROOT%\storage\*" /S /D 2>nul
    echo    Storage-Berechtigungen gesetzt.
)
if exist "%APP_ROOT%\bootstrap\cache" (
    attrib -R "%APP_ROOT%\bootstrap\cache\*" /S /D 2>nul
    echo    Bootstrap-Cache-Berechtigungen gesetzt.
)

REM Erstelle Storage Link
echo [6/8] Erstelle Storage Link...
"%PHP_EXE%" artisan storage:link 2>nul
if %errorlevel% equ 0 (
    echo    Storage Link erstellt.
) else (
    echo    Storage Link bereits vorhanden oder nicht noetig.
)

REM Führe Migrationen aus
echo [7/8] Fuehre Datenbank-Migrationen aus...
echo    Dies kann einige Minuten dauern...
echo    Bitte haben Sie Geduld...
echo.

"%PHP_EXE%" artisan migrate --force --seed

if %errorlevel% neq 0 (
    echo.
    echo ========================================
    echo  FEHLER: Migration fehlgeschlagen!
    echo ========================================
    echo.
    echo Moegliche Ursachen:
    echo  - PHP Extensions fehlen (pdo_sqlite)
    echo  - Datenbank ist beschaedigt
    echo  - Nicht genuegend Berechtigungen
    echo.
    echo Loesungsversuche:
    echo  1. setup-php.bat erneut ausfuehren
    echo  2. database\database.sqlite loeschen
    echo  3. Monica neu starten
    echo.
    pause
    exit /b 1
)

echo    Migration erfolgreich abgeschlossen!

REM Cache aufbauen
echo [8/8] Baue Cache auf...
"%PHP_EXE%" artisan config:cache 2>nul
"%PHP_EXE%" artisan route:cache 2>nul
"%PHP_EXE%" artisan view:cache 2>nul
echo    Cache erfolgreich erstellt.

echo.
echo ========================================
echo  Initialisierung erfolgreich!
echo ========================================
echo.
echo Monica ist jetzt bereit zur Nutzung!
echo.

exit /b 0
