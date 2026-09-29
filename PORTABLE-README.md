# Monica Portable Edition

## 🎯 Was ist das?

Dies ist eine **vollständig portable Version** der Monica CRM-Anwendung für Windows.

Monica ist ein persönliches Relationship Management System - ein CRM für Ihr Privatleben. Mit Monica können Sie:
- Kontakte und Beziehungen verwalten
- Geburtstage und wichtige Termine tracken
- Gespräche und Interaktionen dokumentieren
- Geschenkideen sammeln
- Ihr soziales Leben organisieren

## 🚀 Schnellstart

### Erste Nutzung

1. **Navigieren Sie zu**: `portable/`
2. **Öffnen Sie**: `START-HIER.txt` für detaillierte Anweisungen
3. **Führen Sie aus**: `setup-php.bat` (nur einmalig)
4. **Starten Sie**: `start-monica.bat`

### Alternative: Root-Level Start

Doppelklick auf: `MONICA-PORTABLE-START.bat` (in diesem Ordner)

## 📁 Verzeichnisstruktur

```
monica/
├── MONICA-PORTABLE-START.bat    ⭐ Schnellstart (hier klicken!)
├── PORTABLE-README.md           📖 Diese Datei
│
├── portable/                    🎯 HAUPTVERZEICHNIS
│   ├── START-HIER.txt          📋 Schnellstart-Anleitung
│   ├── start-monica.bat        🚀 Monica starten
│   ├── start.html              🌐 Browser-Launcher
│   ├── setup-php.bat           📥 PHP Setup
│   ├── maintenance.bat         🔧 Wartungsmenü
│   ├── backup.bat              💾 Backup erstellen
│   ├── restore.bat             ♻️  Backup wiederherstellen
│   ├── reset.bat               🔄 Zurücksetzen
│   ├── SETUP.md                📖 Setup-Anleitung
│   ├── README.md               📚 Benutzerhandbuch
│   └── php/                    (wird erstellt)
│
├── database/
│   └── database.sqlite         💾 Ihre Daten (wird erstellt)
│
├── .env.portable               ⚙️  Portable Konfiguration
├── .env                        ⚙️  Aktive Konfiguration (wird erstellt)
│
└── ... (Monica Core-Dateien)
```

## ✨ Features der Portable-Version

- ✅ **Keine Installation nötig** - Einfach entpacken und starten
- ✅ **Keine Admin-Rechte** - Läuft ohne erhöhte Berechtigungen
- ✅ **Portable** - Auf USB-Stick nutzbar
- ✅ **SQLite-basiert** - Keine externe Datenbank erforderlich
- ✅ **Offline-fähig** - Funktioniert ohne Internet
- ✅ **Datenschutz** - Alle Daten bleiben lokal
- ✅ **Backup-Tools** - Integrierte Backup/Restore-Funktionen
- ✅ **Auto-Setup** - Automatische Initialisierung beim ersten Start

## 🎮 Nutzungsszenarien

### Als Desktop-Anwendung
Entpacken Sie Monica in einen Ordner und nutzen Sie es wie eine normale Anwendung.

### Auf USB-Stick
1. Kopieren Sie den gesamten Monica-Ordner auf einen USB-Stick
2. Stecken Sie den Stick in jeden Windows-Computer
3. Starten Sie `portable/start-monica.bat`
4. Fertig!

### In Cloud-Ordnern (Dropbox, OneDrive)
Platzieren Sie Monica in Ihrem Cloud-Ordner für Zugriff von mehreren Computern.
⚠️ **Wichtig**: Immer nur auf einem Computer gleichzeitig nutzen!

## 📦 Anforderungen

- **Betriebssystem**: Windows 7 oder höher
- **Speicherplatz**: ~500 MB (inkl. PHP)
- **RAM**: Mind. 512 MB (empfohlen: 2 GB)
- **Berechtigungen**: Keine Admin-Rechte erforderlich
- **Internet**: Nur für initiales PHP-Setup (oder manueller Download)

## 🔧 Setup

### Automatisches Setup (Empfohlen)

```batch
cd portable
setup-php.bat
```

Das Skript lädt PHP automatisch herunter und konfiguriert alles.

### Manuelles Setup

1. PHP 8.3+ von https://windows.php.net/download/ herunterladen
2. Entpacken nach `portable/php/`
3. `setup-php.bat` zur Konfiguration ausführen

## 🎯 Hauptfunktionen

### Kontakte & Beziehungen
- Detaillierte Kontaktprofile
- Familienbeziehungen und Netzwerke
- Social-Media-Links
- Notizen und Tags

### Aktivitäten & Journal
- Treffen dokumentieren
- Gespräche protokollieren
- Tägliche Journaleinträge
- Aktivitäten-Historie

### Erinnerungen & Termine
- Geburtstags-Erinnerungen
- Benutzerdefinierte Reminder
- Jahrestage
- Wichtige Termine

### Geschenke & Schulden
- Geschenkideen sammeln
- Erhaltene/Gegebene Geschenke
- Schulden tracken
- Ausgaben dokumentieren

## 💾 Datenverwaltung

### Backup erstellen
```batch
cd portable
backup.bat
```

Oder nutzen Sie das Wartungsmenü:
```batch
cd portable
maintenance.bat
```

### Daten wiederherstellen
```batch
cd portable
restore.bat
```

### Ihre Daten finden

Alle persönlichen Daten sind in:
- `database/database.sqlite` - Hauptdatenbank
- `.env` - Konfiguration
- `storage/app/` - Hochgeladene Dateien

## 🔐 Sicherheit

### Lokale Datenspeicherung
- Alle Daten bleiben auf Ihrem Gerät
- Keine Cloud-Synchronisation (außer Sie platzieren es in einem Cloud-Ordner)
- Keine Telemetrie oder Tracking

### Empfohlene Sicherheitsmaßnahmen
1. Regelmäßige Backups erstellen
2. Bei USB-Stick: Verschlüsselung nutzen (BitLocker, VeraCrypt)
3. Starke Passwörter verwenden
4. 2-Faktor-Authentifizierung aktivieren (in Monica Settings)

## 🐛 Fehlerbehebung

### Häufige Probleme

**Problem**: PHP nicht gefunden
- **Lösung**: `setup-php.bat` ausführen

**Problem**: Port bereits belegt
- **Lösung**: Automatisch gelöst (nutzt Port 8001)

**Problem**: Seite lädt nicht
- **Lösung**: 10-15 Sekunden warten, dann http://127.0.0.1:8000 öffnen

**Problem**: Datenbank-Fehler
- **Lösung**: Monica beenden, `database/database.sqlite` löschen, neu starten

### Weitere Hilfe

Siehe detaillierte Dokumentation:
- `portable/SETUP.md` - Setup-Anleitung
- `portable/README.md` - Vollständiges Handbuch
- `portable/START-HIER.txt` - Schnellstart

## 📊 Performance-Tipps

1. **USB 3.0 verwenden** für USB-Stick-Installation
2. **Cache leeren** bei Problemen (maintenance.bat → Option 4)
3. **Optimierung** regelmäßig ausführen (maintenance.bat → Option 5)
4. **Alte Daten archivieren** bei vielen Einträgen

## 🌐 Links

- **Offizielle Website**: https://www.monicahq.com
- **Dokumentation**: https://docs.monicahq.com
- **GitHub**: https://github.com/monicahq/monica
- **Community**: https://github.com/monicahq/monica/discussions

## 📝 Lizenz

Monica ist Open Source Software, lizenziert unter der **AGPL-3.0 License**.

## ❤️ Credits

- **Monica CRM**: Entwickelt von der Monica Community
- **Portable Edition**: Community Contribution
- **Website**: https://www.monicahq.com

## 🚀 Erste Schritte

Bereit? Los geht's:

1. Öffnen Sie `portable/START-HIER.txt`
2. Führen Sie `portable/setup-php.bat` aus
3. Starten Sie `portable/start-monica.bat`
4. Erstellen Sie Ihren ersten Account
5. Fügen Sie Ihre ersten Kontakte hinzu

**Viel Erfolg mit Monica Portable!** 💜

---

*"Remember everything about your friends, family and business relationships."*
