@echo off
REM ============================================
REM Monica Portable - Manuelle Composer Installation
REM Nutzen Sie dieses Script, wenn setup-composer.bat nicht funktioniert
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable - Composer Manuell
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..
set PHP_DIR=%SCRIPT_DIR%php
set PHP_EXE=%PHP_DIR%\php.exe
set COMPOSER_PHAR=%SCRIPT_DIR%composer.phar

echo Dieses Script arbeitet mit RELATIVEN Pfaden.
echo Es ist egal, wo der Monica-Ordner liegt!
echo.

REM Prüfe Pfadlänge (Windows hat ein Limit von ~260 Zeichen)
set CURRENT_PATH=%CD%
set PATH_LENGTH=0
setlocal enabledelayedexpansion
for /L %%A in (0,1,260) do if "!CURRENT_PATH:~%%A,1!" neq "" set /A PATH_LENGTH=%%A+1

if %PATH_LENGTH% gtr 180 (
    echo.
    echo ========================================
    echo  WARNUNG: Pfad ist zu lang!
    echo ========================================
    echo.
    echo Aktueller Pfad: %CD%
    echo Laenge: %PATH_LENGTH% Zeichen
    echo.
    echo Windows und Git haben Probleme mit langen Pfaden!
    echo.
    echo EMPFEHLUNG:
    echo 1. Benennen Sie den Ordner um zu etwas Kurzem z.B. "monica"
    echo 2. Verschieben Sie ihn nach C:\monica\
    echo.
    echo Beispiel:
    echo   LANG:  C:\Users\...\Downloads\CRM\monica-claude-...\
    echo   KURZ:  C:\monica\
    echo.
    pause
)

echo Erwartete Struktur:
echo   monica-projekt\
echo   +-- portable\
echo   ^|   +-- composer.phar
echo   ^|   +-- setup-composer-manual.bat
echo   ^|   +-- php\
echo   +-- composer.json
echo.

REM Prüfe Struktur
set ERRORS=0

if not exist "%APP_ROOT%\composer.json" (
    echo [FEHLER] composer.json nicht gefunden im Elternverzeichnis!
    echo          Erwarteter relativer Pfad: ..\composer.json
    set ERRORS=1
) else (
    echo [OK] composer.json gefunden: ..\composer.json
)

if not exist "%PHP_EXE%" (
    echo [FEHLER] PHP nicht gefunden!
    echo          Erwarteter relativer Pfad: .\php\php.exe
    echo          Bitte fuehren Sie zuerst setup-php.bat aus.
    set ERRORS=1
) else (
    echo [OK] PHP gefunden: .\php\php.exe
)

REM Prüfe ZIP Extension
"%PHP_EXE%" -m | findstr /C:"zip" > nul
if %errorlevel% neq 0 (
    echo [WARNUNG] ZIP Extension nicht geladen!
    echo           Composer benoetigt die ZIP Extension!
    echo           Bitte fuehren Sie setup-php.bat erneut aus.
    set ERRORS=1
)

if not exist "%COMPOSER_PHAR%" (
    echo [FEHLER] composer.phar nicht gefunden!
    echo          Erwarteter relativer Pfad: .\composer.phar
    echo.
    echo          WICHTIG: Speichern Sie composer.phar direkt in:
    echo          portable\composer.phar
    echo          (im gleichen Ordner wie dieses Script!)
    set ERRORS=1
) else (
    echo [OK] composer.phar gefunden: .\composer.phar
)

echo.

if %ERRORS% gtr 0 (
    echo ========================================
    echo  SETUP ERFORDERLICH
    echo ========================================
    echo.
    goto :download_instructions
)

echo Alle erforderlichen Dateien gefunden!
echo Bereit zur Installation...
echo.
goto :install_dependencies

:download_instructions
echo ========================================
echo  COMPOSER HERUNTERLADEN
echo ========================================
echo.
echo 1. Oeffnen Sie im Browser:
echo    https://getcomposer.org/composer.phar
echo.
echo 2. Speichern Sie die Datei als "composer.phar" in:
echo    portable\composer.phar
echo    (im gleichen Ordner wie dieses Script!)
echo.
echo 3. Fuehren Sie dieses Script erneut aus
echo.

choice /C JN /M "Soll ich versuchen, composer.phar mit curl herunterzuladen"
if errorlevel 2 (
    echo.
    echo Bitte laden Sie composer.phar manuell herunter.
    pause
    exit /b 1
)

echo.
echo Versuche Download mit curl...
curl -sS https://getcomposer.org/installer -o "%TEMP%\composer-setup.php"

if errorlevel 1 (
    echo Curl-Download fehlgeschlagen.
    echo Bitte laden Sie composer.phar manuell herunter.
    pause
    exit /b 1
)

echo Installiere Composer...
"%PHP_EXE%" "%TEMP%\composer-setup.php" --install-dir="%SCRIPT_DIR%" --filename=composer.phar
del "%TEMP%\composer-setup.php" 2>nul

if not exist "%COMPOSER_PHAR%" (
    echo Installation fehlgeschlagen.
    echo Bitte laden Sie composer.phar manuell herunter.
    pause
    exit /b 1
)

echo Composer erfolgreich installiert!
echo.

:install_dependencies
echo ========================================
echo  Monica Dependencies installieren
echo ========================================
echo.

cd /d "%APP_ROOT%"

echo ACHTUNG: Dies kann 5-15 Minuten dauern!
echo Schliessen Sie das Fenster NICHT!
echo.
pause

REM Setze Composer Variablen (relativ!)
set COMPOSER_HOME=%SCRIPT_DIR%.composer
set COMPOSER_CACHE_DIR=%SCRIPT_DIR%.composer\cache

if not exist "%COMPOSER_HOME%" mkdir "%COMPOSER_HOME%"
if not exist "%COMPOSER_CACHE_DIR%" mkdir "%COMPOSER_CACHE_DIR%"

echo Starte Composer Install...
echo Fortschritt wird unten angezeigt:
echo.

"%PHP_EXE%" "%COMPOSER_PHAR%" install --no-dev --optimize-autoloader --no-interaction

if errorlevel 1 (
    echo.
    echo ========================================
    echo  FEHLER!
    echo ========================================
    echo.
    echo Composer Install ist fehlgeschlagen!
    echo.
    echo Moegliche Loesungen:
    echo  1. PFAD ZU LANG - Ordner umbenennen und nach C:\monica\ verschieben
    echo  2. ZIP Extension fehlt - setup-php.bat erneut ausfuehren
    echo  3. Als Administrator ausfuehren
    echo  4. Internetverbindung pruefen
    echo  5. Andere Programme schliessen (mehr RAM)
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Installation erfolgreich!
echo ========================================
echo.

if exist "%APP_ROOT%\vendor\autoload.php" (
    echo [OK] vendor\autoload.php gefunden
    echo.
    echo Monica ist jetzt bereit!
    echo Fuehren Sie start-monica.bat aus.
) else (
    echo [FEHLER] vendor\autoload.php nicht gefunden!
    echo Bitte pruefen Sie die Installation.
)

echo.
pause
