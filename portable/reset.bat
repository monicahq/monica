@echo off
REM ============================================
REM Monica Portable - Reset Script
REM Setzt Monica auf Werkszustand zurueck
REM ============================================
SETLOCAL ENABLEDELAYEDEXPANSION

echo.
echo ========================================
echo  Monica Portable - Reset
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set APP_ROOT=%SCRIPT_DIR%..

echo ========================================
echo  WARNUNG!
echo ========================================
echo.
echo Dieser Vorgang wird ALLE Daten loeschen:
echo - Datenbank (alle Kontakte und Eintraege)
echo - Cache
echo - Sessions
echo - Logs
echo.
echo Monica wird auf den Werkszustand zurueckgesetzt.
echo.
echo ========================================
echo  ACHTUNG: DIES KANN NICHT RUECKGAENGIG GEMACHT WERDEN!
echo ========================================
echo.

REM Biete Backup an
echo Moechten Sie vorher ein Backup erstellen?
choice /C JN /M "Backup erstellen (EMPFOHLEN)"
if %errorlevel% equ 1 (
    echo.
    echo Erstelle Backup...
    call "%SCRIPT_DIR%backup.bat"
    echo.
)

echo.
echo Sind Sie SICHER, dass Sie fortfahren moechten?
choice /C JN /M "Monica wirklich zuruecksetzen"
if %errorlevel% neq 1 goto :end

echo.
echo Bitte geben Sie zur Bestaetigung "RESET" ein:
set /p CONFIRM=

if /i not "%CONFIRM%"=="RESET" (
    echo.
    echo Abgebrochen. Keine Aenderungen vorgenommen.
    goto :end
)

echo.
echo ========================================
echo  Reset wird durchgefuehrt...
echo ========================================
echo.

cd /d "%APP_ROOT%"

REM Lösche Datenbank
echo [1/6] Loesche Datenbank...
if exist "database\database.sqlite" (
    del /F /Q "database\database.sqlite"
    echo    Datenbank geloescht
) else (
    echo    Keine Datenbank gefunden
)

REM Lösche Cache
echo [2/6] Loesche Cache...
if exist "storage\framework\cache" (
    del /F /Q "storage\framework\cache\*" 2>nul
    for /d %%D in ("storage\framework\cache\*") do rmdir /S /Q "%%D" 2>nul
    echo    Cache geloescht
) else (
    echo    Kein Cache gefunden
)

REM Lösche Sessions
echo [3/6] Loesche Sessions...
if exist "storage\framework\sessions" (
    del /F /Q "storage\framework\sessions\*" 2>nul
    echo    Sessions geloescht
) else (
    echo    Keine Sessions gefunden
)

REM Lösche Views
echo [4/6] Loesche kompilierte Views...
if exist "storage\framework\views" (
    del /F /Q "storage\framework\views\*" 2>nul
    echo    Views geloescht
) else (
    echo    Keine Views gefunden
)

REM Lösche Logs
echo [5/6] Loesche Logs...
if exist "storage\logs" (
    del /F /Q "storage\logs\*.log" 2>nul
    echo    Logs geloescht
) else (
    echo    Keine Logs gefunden
)

REM Setze .env zurück
echo [6/6] Setze Konfiguration zurueck...
if exist ".env.portable" (
    copy /Y ".env.portable" ".env" > nul
    echo    Konfiguration zurueckgesetzt
) else (
    echo    Warnung: .env.portable nicht gefunden
)

echo.
echo ========================================
echo  Reset abgeschlossen!
echo ========================================
echo.
echo Monica wurde auf den Werkszustand zurueckgesetzt.
echo.
echo Naechste Schritte:
echo 1. Starten Sie Monica mit start-monica.bat
echo 2. Die Initialisierung laeuft automatisch
echo 3. Erstellen Sie einen neuen Account
echo.
echo Monica ist jetzt wie neu installiert.
echo.

:end
pause
