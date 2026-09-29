@echo off
REM ============================================
REM Monica Portable - Backup Script
REM Erstellt ein Backup Ihrer Monica-Daten
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable - Backup
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..
set BACKUP_DIR=%SCRIPT_DIR%backups

REM Erstelle Backup-Verzeichnis
if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
    echo Backup-Verzeichnis erstellt: %BACKUP_DIR%
)

REM Generiere Zeitstempel für Backup-Namen
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,8%-%datetime:~8,6%
set BACKUP_NAME=monica-backup-%TIMESTAMP%
set BACKUP_PATH=%BACKUP_DIR%\%BACKUP_NAME%

echo Erstelle Backup: %BACKUP_NAME%
echo.

REM Erstelle Backup-Ordner
mkdir "%BACKUP_PATH%"

REM Prüfe ob Datenbank existiert
if exist "%APP_ROOT%\database\database.sqlite" (
    echo [1/3] Sichere Datenbank...
    copy "%APP_ROOT%\database\database.sqlite" "%BACKUP_PATH%\database.sqlite" > nul
    if %errorlevel% equ 0 (
        echo    Erfolgreich: database.sqlite
    ) else (
        echo    FEHLER beim Sichern der Datenbank!
    )
) else (
    echo [1/3] Keine Datenbank gefunden, ueberspringe...
)

REM Sichere .env
if exist "%APP_ROOT%\.env" (
    echo [2/3] Sichere Konfiguration...
    copy "%APP_ROOT%\.env" "%BACKUP_PATH%\.env" > nul
    if %errorlevel% equ 0 (
        echo    Erfolgreich: .env
    ) else (
        echo    FEHLER beim Sichern der Konfiguration!
    )
) else (
    echo [2/3] Keine Konfiguration gefunden, ueberspringe...
)

REM Sichere Storage (Uploads, etc.)
if exist "%APP_ROOT%\storage\app" (
    echo [3/3] Sichere Dateien...
    xcopy "%APP_ROOT%\storage\app" "%BACKUP_PATH%\storage\" /E /I /Q > nul
    if %errorlevel% equ 0 (
        echo    Erfolgreich: storage/
    ) else (
        echo    FEHLER beim Sichern der Dateien!
    )
) else (
    echo [3/3] Kein Storage gefunden, ueberspringe...
)

REM Erstelle Info-Datei
echo Monica Portable Backup > "%BACKUP_PATH%\backup-info.txt"
echo Erstellt am: %date% %time% >> "%BACKUP_PATH%\backup-info.txt"
echo. >> "%BACKUP_PATH%\backup-info.txt"
echo Wiederherstellen: >> "%BACKUP_PATH%\backup-info.txt"
echo 1. Monica beenden >> "%BACKUP_PATH%\backup-info.txt"
echo 2. Dateien zurueckkopieren >> "%BACKUP_PATH%\backup-info.txt"
echo 3. Monica neu starten >> "%BACKUP_PATH%\backup-info.txt"

echo.
echo ========================================
echo  Backup erfolgreich erstellt!
echo ========================================
echo.
echo Speicherort:
echo %BACKUP_PATH%
echo.
echo Inhalt:
if exist "%BACKUP_PATH%\database.sqlite" echo  - database.sqlite (Ihre Daten)
if exist "%BACKUP_PATH%\.env" echo  - .env (Konfiguration)
if exist "%BACKUP_PATH%\storage" echo  - storage/ (Dateien)
echo  - backup-info.txt (Informationen)
echo.

REM Alte Backups zählen
set BACKUP_COUNT=0
for /d %%D in ("%BACKUP_DIR%\monica-backup-*") do set /a BACKUP_COUNT+=1

echo Gesamt gespeicherte Backups: %BACKUP_COUNT%

if %BACKUP_COUNT% gtr 5 (
    echo.
    echo HINWEIS: Sie haben mehr als 5 Backups.
    echo Erwaegen Sie, alte Backups zu loeschen um Speicherplatz zu sparen.
)

echo.
echo Moechten Sie den Backup-Ordner oeffnen?
choice /C JN /M "Ordner oeffnen"
if %errorlevel% equ 1 explorer "%BACKUP_PATH%"

echo.
pause
