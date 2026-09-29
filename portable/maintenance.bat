@echo off
REM ============================================
REM Monica Portable - Wartungs-Menü
REM Zentrale Wartungs- und Verwaltungsfunktionen
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

:menu
cls
echo.
echo ========================================
echo  Monica Portable - Wartung
echo ========================================
echo.
echo Bitte waehlen Sie eine Option:
echo.
echo  [1] Monica starten
echo  [2] Backup erstellen
echo  [3] Backup wiederherstellen
echo  [4] Cache leeren
echo  [5] Optimierung durchfuehren
echo  [6] System-Informationen
echo  [7] Reset (alle Daten loeschen)
echo  [8] PHP neu konfigurieren
echo  [9] Hilfe anzeigen
echo  [0] Beenden
echo.
echo ========================================
echo.

set /p CHOICE=Ihre Wahl (0-9):

if "%CHOICE%"=="1" goto :start_monica
if "%CHOICE%"=="2" goto :backup
if "%CHOICE%"=="3" goto :restore
if "%CHOICE%"=="4" goto :clear_cache
if "%CHOICE%"=="5" goto :optimize
if "%CHOICE%"=="6" goto :info
if "%CHOICE%"=="7" goto :reset
if "%CHOICE%"=="8" goto :setup_php
if "%CHOICE%"=="9" goto :help
if "%CHOICE%"=="0" goto :end

echo Ungueltige Auswahl!
timeout /t 2 > nul
goto :menu

:start_monica
echo.
echo Starte Monica...
call "%~dp0start-monica.bat"
goto :menu

:backup
echo.
call "%~dp0backup.bat"
goto :menu

:restore
echo.
call "%~dp0restore.bat"
goto :menu

:clear_cache
echo.
echo ========================================
echo  Cache leeren
echo ========================================
echo.

set APP_ROOT=%~dp0..
set PHP_EXE=%~dp0php\php.exe

if not exist "%PHP_EXE%" (
    echo FEHLER: PHP nicht gefunden!
    echo Fuehren Sie zuerst setup-php.bat aus.
    pause
    goto :menu
)

cd /d "%APP_ROOT%"

echo [1/4] Leere Application Cache...
"%PHP_EXE%" artisan cache:clear

echo [2/4] Leere Config Cache...
"%PHP_EXE%" artisan config:clear

echo [3/4] Leere Route Cache...
"%PHP_EXE%" artisan route:clear

echo [4/4] Leere View Cache...
"%PHP_EXE%" artisan view:clear

echo.
echo Cache erfolgreich geleert!
pause
goto :menu

:optimize
echo.
echo ========================================
echo  Optimierung
echo ========================================
echo.

set APP_ROOT=%~dp0..
set PHP_EXE=%~dp0php\php.exe

if not exist "%PHP_EXE%" (
    echo FEHLER: PHP nicht gefunden!
    echo Fuehren Sie zuerst setup-php.bat aus.
    pause
    goto :menu
)

cd /d "%APP_ROOT%"

echo [1/4] Optimiere Autoloader...
"%PHP_EXE%" artisan optimize

echo [2/4] Cache Config...
"%PHP_EXE%" artisan config:cache

echo [3/4] Cache Routes...
"%PHP_EXE%" artisan route:cache

echo [4/4] Cache Views...
"%PHP_EXE%" artisan view:cache

echo.
echo Optimierung abgeschlossen!
echo Monica sollte jetzt schneller laden.
pause
goto :menu

:info
echo.
echo ========================================
echo  System-Informationen
echo ========================================
echo.

set APP_ROOT=%~dp0..
set PHP_EXE=%~dp0php\php.exe

echo PHP Installation:
if exist "%PHP_EXE%" (
    echo  Status: Installiert
    echo  Pfad: %PHP_EXE%
    echo.
    echo  Version:
    "%PHP_EXE%" -v
) else (
    echo  Status: NICHT INSTALLIERT
    echo  Bitte fuehren Sie setup-php.bat aus!
)

echo.
echo ----------------------------------------
echo Monica Installation:
echo  Pfad: %APP_ROOT%
echo.

if exist "%APP_ROOT%\database\database.sqlite" (
    echo  Datenbank: Vorhanden
    for %%A in ("%APP_ROOT%\database\database.sqlite") do echo  Groesse: %%~zA Bytes
) else (
    echo  Datenbank: Nicht initialisiert
)

if exist "%APP_ROOT%\.env" (
    echo  Konfiguration: Vorhanden
) else (
    echo  Konfiguration: Nicht konfiguriert
)

echo.
echo ----------------------------------------
echo Backup Status:
if exist "%~dp0backups" (
    set BACKUP_COUNT=0
    for /d %%D in ("%~dp0backups\monica-backup-*") do set /a BACKUP_COUNT+=1
    echo  Gespeicherte Backups: !BACKUP_COUNT!

    if !BACKUP_COUNT! gtr 0 (
        echo  Neuestes Backup:
        for /f "delims=" %%D in ('dir "%~dp0backups\monica-backup-*" /AD /B /O-N') do (
            echo    %%D
            goto :backup_shown
        )
        :backup_shown
    )
) else (
    echo  Keine Backups vorhanden
)

echo.
echo ----------------------------------------
echo System:
echo  OS: %OS%
echo  Computername: %COMPUTERNAME%
echo  Benutzer: %USERNAME%
echo.

pause
goto :menu

:reset
echo.
call "%~dp0reset.bat"
goto :menu

:setup_php
echo.
call "%~dp0setup-php.bat"
goto :menu

:help
echo.
echo ========================================
echo  Hilfe
echo ========================================
echo.
echo SCHNELLSTART:
echo  1. Option [8] - PHP Setup durchfuehren
echo  2. Option [1] - Monica starten
echo  3. Browser oeffnet sich automatisch
echo.
echo REGELMAESSIGE WARTUNG:
echo  - Erstellen Sie regelmaessig Backups [2]
echo  - Leeren Sie gelegentlich den Cache [4]
echo  - Optimieren Sie bei langsamer Performance [5]
echo.
echo BEI PROBLEMEN:
echo  - Pruefen Sie System-Informationen [6]
echo  - Leeren Sie den Cache [4]
echo  - Im Notfall: Reset [7] (loescht alle Daten!)
echo.
echo WEITERE HILFE:
echo  - Lesen Sie README.md im portable/ Ordner
echo  - Besuchen Sie: https://docs.monicahq.com
echo.
pause
goto :menu

:end
echo.
echo Auf Wiedersehen!
timeout /t 2 > nul
exit /b 0
