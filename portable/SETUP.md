# Monica Portable - Setup Anleitung

## 🎯 Übersicht

Monica Portable ist eine vollständig portable Version des Monica CRM-Systems, die auf jedem Windows-Computer **ohne Administratorrechte** läuft. Sie können es auf einem USB-Stick mitnehmen und überall nutzen!

## 📋 Voraussetzungen

- Windows 7 oder höher
- Mindestens 500 MB freier Speicherplatz
- Internetverbindung (nur für erstmalige PHP-Installation)
- Keine Administratorrechte erforderlich!

## 🚀 Schnellstart

### Option 1: Automatische Installation (Empfohlen)

1. **Doppelklick auf**: `setup-php.bat`
2. Folgen Sie den Anweisungen auf dem Bildschirm
3. Warten Sie, bis PHP heruntergeladen und konfiguriert wurde
4. **Doppelklick auf**: `start-monica.bat`
5. Die Anwendung startet automatisch im Browser!

### Option 2: Manuelle Installation

Wenn die automatische Installation nicht funktioniert:

1. **PHP herunterladen**:
   - Besuchen Sie: https://windows.php.net/download/
   - Laden Sie "PHP 8.3.x VS16 x64 Thread Safe" (ZIP) herunter
   - Entpacken Sie die ZIP-Datei in: `portable/php/`
   - Der Pfad sollte sein: `portable/php/php.exe`

2. **PHP konfigurieren**:
   - Führen Sie `setup-php.bat` aus, um die Konfiguration abzuschließen

3. **Monica starten**:
   - Doppelklick auf `start-monica.bat`

## 📁 Verzeichnisstruktur

```
monica/
├── portable/                    # Portable App Ordner
│   ├── start-monica.bat        # HAUPTSTARTER - Hier klicken!
│   ├── start.html              # Browser-Launcher (Alternative)
│   ├── setup-php.bat           # PHP Setup-Hilfe
│   ├── init-monica.bat         # Initialisierung (automatisch)
│   ├── php/                    # PHP Installation (wird erstellt)
│   │   └── php.exe
│   ├── SETUP.md               # Diese Datei
│   └── README.md              # Benutzerhandbuch
├── database/
│   └── database.sqlite        # Ihre Daten (wird erstellt)
├── .env                       # Konfiguration (wird erstellt)
└── ... (Laravel Dateien)
```

## 🎮 Nutzung

### Erste Schritte

1. **Monica starten**:
   - Doppelklick auf `portable/start-monica.bat`
   - Ein Konsolenfenster öffnet sich (nicht schließen!)
   - Der Browser öffnet sich automatisch

2. **Erster Login**:
   - Monica erstellt automatisch die Datenbank
   - Registrieren Sie Ihren ersten Benutzer
   - Hinweis: Weitere Registrierungen sind deaktiviert (Single-User-Modus)

3. **Benutzer erstellen**:
   - Nutzen Sie die Monica-Oberfläche wie gewohnt

### Tägliche Nutzung

1. Doppelklick auf `start-monica.bat`
2. Warten Sie, bis "Monica CRM ist bereit!" erscheint
3. Browser öffnet sich automatisch
4. Arbeiten Sie mit Monica
5. Zum Beenden: STRG+C im Konsolenfenster oder Fenster schließen

### Als Portable App (USB-Stick)

1. Kopieren Sie den gesamten `monica/` Ordner auf Ihren USB-Stick
2. Auf jedem Computer: Stecken Sie den USB-Stick ein
3. Navigieren Sie zu `monica/portable/`
4. Doppelklick auf `start-monica.bat`
5. Fertig!

**Wichtig**: Schließen Sie das Konsolenfenster nicht, während Sie Monica nutzen!

## 🔧 Fehlerbehebung

### Problem: "PHP wurde nicht gefunden"

**Lösung**:
- Führen Sie `setup-php.bat` aus
- Oder installieren Sie PHP manuell (siehe Option 2 oben)

### Problem: Port bereits belegt

**Lösung**:
- Das Startscript versucht automatisch Port 8001
- Oder: Schließen Sie andere Anwendungen, die Port 8000/8001 nutzen

### Problem: Seite lädt nicht

**Lösung**:
1. Prüfen Sie, ob das Konsolenfenster noch offen ist
2. Warten Sie 10-15 Sekunden nach dem Start
3. Öffnen Sie manuell: http://127.0.0.1:8000
4. Falls das nicht funktioniert: http://127.0.0.1:8001

### Problem: Datenbank-Fehler

**Lösung**:
1. Schließen Sie Monica
2. Löschen Sie `database/database.sqlite`
3. Starten Sie Monica neu (Datenbank wird neu erstellt)

### Problem: "Access Denied" Fehler

**Lösung**:
- Stellen Sie sicher, dass der Ordner nicht schreibgeschützt ist
- Auf USB-Stick: Prüfen Sie den Schreibschutz-Schalter

## 🔐 Sicherheit & Datenschutz

- **Alle Daten lokal**: Monica speichert alles in `database/database.sqlite`
- **Keine Cloud**: Keine Verbindung zu externen Servern (außer gewünschte Integrationen)
- **Offline-fähig**: Funktioniert komplett ohne Internet
- **Verschlüsselt**: Die Datenbank kann zusätzlich mit Tools wie VeraCrypt verschlüsselt werden

### Backup Ihrer Daten

Einfach diese Dateien kopieren:
- `database/database.sqlite` (Ihre gesamten Daten)
- `.env` (Ihre Konfiguration)

## 🎨 Anpassungen

### Port ändern

Bearbeiten Sie `start-monica.bat` und ändern Sie:
```batch
set PORT=8000
```
zu gewünschtem Port, z.B.:
```batch
set PORT=9000
```

### Registrierung aktivieren (Multi-User)

Bearbeiten Sie `.env` und ändern Sie:
```
APP_DISABLE_SIGNUP=true
```
zu:
```
APP_DISABLE_SIGNUP=false
```

### Externe Services aktivieren

Bearbeiten Sie `.env` und fügen Sie API-Keys hinzu für:
- Uploadcare (Datei-Uploads)
- LocationIQ (Geocoding)
- Mapbox (Karten)

## 📊 Systemanforderungen

### Minimum
- CPU: Jeder moderne Prozessor
- RAM: 512 MB
- Speicher: 500 MB

### Empfohlen
- CPU: Dual-Core oder besser
- RAM: 2 GB
- Speicher: 1 GB

## 🆘 Support

### Offizielle Dokumentation
- Monica Docs: https://docs.monicahq.com
- GitHub: https://github.com/monicahq/monica

### Häufige Fragen

**F: Kann ich Monica auf mehreren Computern gleichzeitig nutzen?**
A: Nein, die SQLite-Datenbank unterstützt keinen gleichzeitigen Zugriff. Für Multi-User nutzen Sie die Server-Version.

**F: Kann ich meine Daten später in die Server-Version migrieren?**
A: Ja! Sie können die SQLite-Datenbank exportieren und in MySQL/PostgreSQL importieren.

**F: Wie aktualisiere ich Monica?**
A: Laden Sie die neue Version herunter, kopieren Sie `database/database.sqlite` und `.env` in die neue Version.

**F: Funktioniert das auch auf einem Mac oder Linux?**
A: Diese Portable-Version ist für Windows optimiert. Für Mac/Linux nutzen Sie die native Installation.

## 📝 Lizenz

Monica ist unter der AGPL-3.0 Lizenz veröffentlicht.

## ❤️ Credits

- Monica CRM: https://www.monicahq.com
- Entwickelt von: Monica Contributors
- Portable Version: Community Contribution

---

**Viel Spaß mit Monica Portable! ❤️**
