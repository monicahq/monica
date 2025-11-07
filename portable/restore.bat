@echo off
REM ============================================
REM Monica Portable - Restore Script
REM Stellt ein Backup wieder her
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable - Wiederherstellung
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..
set BACKUP_DIR=%SCRIPT_DIR%backups

REM Prüfe ob Backup-Verzeichnis existiert
if not exist "%BACKUP_DIR%" (
    echo FEHLER: Kein Backup-Verzeichnis gefunden!
    echo Erstellen Sie zuerst ein Backup mit backup.bat
    pause
    exit /b 1
)

REM Liste verfügbare Backups
echo Verfuegbare Backups:
echo.

set INDEX=0
set BACKUP_FOUND=0

for /d %%D in ("%BACKUP_DIR%\monica-backup-*") do (
    set /a INDEX+=1
    set BACKUP_!INDEX!=%%~nxD
    set BACKUP_FOUND=1

    echo [!INDEX!] %%~nxD
    if exist "%%D\backup-info.txt" (
        for /f "skip=1 delims=" %%L in (%%D\backup-info.txt) do (
            echo     %%L
            goto :next_backup
        )
    )
    :next_backup
    echo.
)

if %BACKUP_FOUND% equ 0 (
    echo Keine Backups gefunden!
    echo Erstellen Sie zuerst ein Backup mit backup.bat
    pause
    exit /b 1
)

echo.
echo Waehlen Sie ein Backup (1-%INDEX%) oder druecken Sie X zum Abbrechen:
set /p CHOICE=Ihre Wahl:

if /i "%CHOICE%"=="X" goto :end

REM Validiere Eingabe
if %CHOICE% lss 1 goto :invalid_choice
if %CHOICE% gtr %INDEX% goto :invalid_choice

REM Hole gewähltes Backup
call set SELECTED_BACKUP=%%BACKUP_%CHOICE%%%
set RESTORE_PATH=%BACKUP_DIR%\%SELECTED_BACKUP%

echo.
echo ========================================
echo  WARNUNG!
echo ========================================
echo.
echo Sie sind dabei, Monica aus folgendem Backup wiederherzustellen:
echo %SELECTED_BACKUP%
echo.
echo ACHTUNG: Die aktuellen Daten werden ueberschrieben!
echo.
echo Moechten Sie ein Backup der aktuellen Daten erstellen?
choice /C JN /M "Aktuelles Backup erstellen"
if %errorlevel% equ 1 (
    echo.
    echo Erstelle Sicherheits-Backup...
    call "%SCRIPT_DIR%backup.bat"
)

echo.
echo Fortfahren mit Wiederherstellung?
choice /C JN /M "Daten wirklich wiederherstellen"
if %errorlevel% neq 1 goto :end

echo.
echo ========================================
echo  Wiederherstellung laeuft...
echo ========================================
echo.

REM Stelle Datenbank wieder her
if exist "%RESTORE_PATH%\database.sqlite" (
    echo [1/3] Stelle Datenbank wieder her...

    REM Erstelle Zielverzeichnis falls nötig
    if not exist "%APP_ROOT%\database" mkdir "%APP_ROOT%\database"

    copy /Y "%RESTORE_PATH%\database.sqlite" "%APP_ROOT%\database\database.sqlite" > nul
    if %errorlevel% equ 0 (
        echo    Erfolgreich: database.sqlite
    ) else (
        echo    FEHLER beim Wiederherstellen der Datenbank!
        goto :restore_error
    )
) else (
    echo [1/3] Keine Datenbank im Backup, ueberspringe...
)

REM Stelle .env wieder her
if exist "%RESTORE_PATH%\.env" (
    echo [2/3] Stelle Konfiguration wieder her...
    copy /Y "%RESTORE_PATH%\.env" "%APP_ROOT%\.env" > nul
    if %errorlevel% equ 0 (
        echo    Erfolgreich: .env
    ) else (
        echo    FEHLER beim Wiederherstellen der Konfiguration!
        goto :restore_error
    )
) else (
    echo [2/3] Keine Konfiguration im Backup, ueberspringe...
)

REM Stelle Storage wieder her
if exist "%RESTORE_PATH%\storage" (
    echo [3/3] Stelle Dateien wieder her...
    xcopy "%RESTORE_PATH%\storage" "%APP_ROOT%\storage\app\" /E /I /Y /Q > nul
    if %errorlevel% equ 0 (
        echo    Erfolgreich: storage/
    ) else (
        echo    FEHLER beim Wiederherstellen der Dateien!
        goto :restore_error
    )
) else (
    echo [3/3] Kein Storage im Backup, ueberspringe...
)

echo.
echo ========================================
echo  Wiederherstellung erfolgreich!
echo ========================================
echo.
echo Ihre Daten wurden wiederhergestellt.
echo.
echo Naechste Schritte:
echo 1. Starten Sie Monica mit start-monica.bat
echo 2. Pruefen Sie Ihre Daten
echo.
goto :end

:restore_error
echo.
echo ========================================
echo  FEHLER!
echo ========================================
echo.
echo Die Wiederherstellung ist fehlgeschlagen.
echo Bitte pruefen Sie:
echo - Sind die Backup-Dateien vorhanden?
echo - Haben Sie Schreibrechte?
echo - Laeuft Monica aktuell?
echo.
goto :end

:invalid_choice
echo.
echo Ungueltige Auswahl!
pause
exit /b 1

:end
pause
