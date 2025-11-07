# 💜 Monica Portable - Benutzerhandbuch

## Willkommen bei Monica Portable!

Monica ist ein persönliches Relationship Management (PRM) System - denken Sie an ein CRM für Ihr Privatleben. Diese portable Version läuft vollständig auf Ihrem lokalen Computer oder USB-Stick, ohne Installation oder Admin-Rechte.

## 🚀 Schnellstart in 3 Schritten

1. **Setup (nur einmal)**
   ```
   Doppelklick auf: setup-php.bat
   ```

2. **Starten**
   ```
   Doppelklick auf: start-monica.bat
   ```

3. **Nutzen**
   - Browser öffnet sich automatisch
   - Erstellen Sie Ihren ersten Account
   - Fertig!

## 💡 Was kann Monica?

### Kontakte verwalten
- 👥 Alle wichtigen Menschen in Ihrem Leben organisieren
- 📝 Notizen, Geburtstage, wichtige Daten speichern
- 📞 Kontaktinformationen zentral verwalten

### Beziehungen pflegen
- 💬 Gespräche und Interaktionen dokumentieren
- 🎂 Erinnerungen für Geburtstage und Jahrestage
- 💝 Geschenkideen sammeln
- 📅 Aktivitäten und Treffen planen

### Leben dokumentieren
- 📖 Lebensmomente festhalten
- 👨‍👩‍👧‍👦 Familienbeziehungen abbilden
- 📊 Schulden und Geschenke tracken
- 📍 Orte und Adressen verwalten

## 🎯 Hauptfunktionen

### Dashboard
Ihr persönliches Übersichts-Center mit:
- Kommenden Geburtstagen
- Ausstehenden Aufgaben
- Kürzlichen Aktivitäten
- Wichtigen Erinnerungen

### Kontakte
- Detaillierte Profile für jede Person
- Beziehungen und Verbindungen
- Kontaktinformationen
- Soziale Netzwerke
- Notizen und Tags

### Journal
- Tägliche Einträge
- Persönliche Gedanken
- Wichtige Momente
- Durchsuchbar und filterbar

### Aktivitäten
- Treffen dokumentieren
- Anrufe protokollieren
- E-Mails tracken
- Gemeinsame Erlebnisse festhalten

### Erinnerungen
- Automatische Geburtstags-Reminder
- Benutzerdefinierte Erinnerungen
- Jahrestage
- Wichtige Termine

## 🖥️ Bedienung

### Monica starten

**Methode 1: Batch-Datei (Empfohlen)**
```
Doppelklick auf: start-monica.bat
```
- Konsolenfenster bleibt offen (nicht schließen!)
- Browser öffnet sich automatisch
- Warten bis "Monica CRM ist bereit!" erscheint

**Methode 2: HTML-Launcher**
```
1. Starten Sie start-monica.bat
2. Doppelklick auf: start.html
```

### Monica beenden

**Methode 1: Sauber beenden**
- Im Konsolenfenster: `STRG + C` drücken
- Bestätigen mit `J` (Ja)

**Methode 2: Schnell beenden**
- Konsolenfenster einfach schließen

### Erste Schritte nach dem Start

1. **Account erstellen**
   - Öffnen Sie http://127.0.0.1:8000
   - Klicken Sie auf "Register"
   - Geben Sie Ihre Daten ein
   - Hinweis: Nur der erste Account ist möglich (Single-User-Modus)

2. **Ersten Kontakt anlegen**
   - Klicken Sie auf "Add contact"
   - Füllen Sie die Informationen aus
   - Speichern

3. **Dashboard erkunden**
   - Navigieren Sie durch die verschiedenen Bereiche
   - Probieren Sie die Funktionen aus
   - Passen Sie Ihre Einstellungen an

## 📱 Tipps & Tricks

### Effiziente Nutzung

**Schnelle Navigation**
- Nutzen Sie die Suchfunktion (oben rechts)
- Verwenden Sie Tags für Kategorisierung
- Favorisieren Sie wichtige Kontakte

**Regelmäßige Pflege**
- Setzen Sie sich wöchentlich 10 Minuten
- Aktualisieren Sie Ihre Kontakte
- Dokumentieren Sie Gespräche und Treffen
- Überprüfen Sie kommende Geburtstage

**Privatsphäre**
- Alle Daten bleiben lokal auf Ihrem Gerät
- Keine Cloud-Synchronisation
- Keine Telemetrie oder Tracking
- Offline-fähig

### Produktivitäts-Hacks

1. **Wöchentliches Review**
   - Jeden Sonntag: Dashboard checken
   - Kommende Geburtstage vorbereiten
   - Kontakte aktualisieren

2. **Geschenkideen**
   - Notieren Sie Geschenkideen sofort
   - Bei jedem Kontakt im "Gifts"-Bereich
   - Nie wieder ratlos sein!

3. **Beziehungspflege**
   - Setzen Sie Erinnerungen für regelmäßigen Kontakt
   - Dokumentieren Sie wichtige Gespräche
   - Bleiben Sie in Verbindung

## 💾 Datenverwaltung

### Backup erstellen

**Vollständiges Backup:**
```
Kopieren Sie diese Dateien:
├── database/database.sqlite  (Alle Ihre Daten!)
└── .env                       (Ihre Konfiguration)
```

**Empfehlung:**
- Wöchentliches Backup auf externem Speicher
- Cloud-Backup (verschlüsselt!)
- USB-Stick als Zweitlocation

### Daten wiederherstellen

1. Monica beenden
2. Ersetzen Sie `database/database.sqlite` mit Ihrer Backup-Datei
3. Monica neu starten
4. Fertig!

### Daten exportieren

Monica bietet Export-Funktionen:
- Kontakte als vCard
- Daten als JSON
- Im Dashboard unter "Settings" → "Export"

### Neustart (Daten löschen)

**Achtung: Löscht alle Daten!**

1. Monica beenden
2. Löschen Sie `database/database.sqlite`
3. Starten Sie Monica neu
4. Neue Datenbank wird erstellt

## 🔐 Sicherheit

### Ihre Daten sind sicher

✅ **Lokal gespeichert**: Alle Daten auf Ihrem Gerät
✅ **Keine Cloud**: Keine automatische Synchronisation
✅ **Keine Telemetrie**: Keine Datensammlung
✅ **Offline-fähig**: Keine Internetverbindung nötig
✅ **Open Source**: Transparenter Code

### Zusätzliche Sicherheit

**Verschlüsselung (Empfohlen):**
- Nutzen Sie BitLocker (Windows)
- Oder VeraCrypt für USB-Sticks
- Verschlüsseln Sie den gesamten Monica-Ordner

**Passwort-Sicherheit:**
- Nutzen Sie ein starkes Passwort
- Aktivieren Sie 2FA (in Settings)
- Ändern Sie das Passwort regelmäßig

**USB-Stick Tipps:**
- Verschlüsselter USB-Stick empfohlen
- Physische Sicherheit beachten
- Backup auf separatem Medium

## ⚙️ Erweiterte Konfiguration

### .env Datei bearbeiten

Öffnen Sie `.env` mit einem Texteditor:

**App Name ändern:**
```env
APP_NAME="Mein Monica"
```

**Debug-Modus aktivieren:**
```env
APP_DEBUG=true
```

**Registrierung aktivieren (Multi-User):**
```env
APP_DISABLE_SIGNUP=false
```

### Port ändern

Bearbeiten Sie `portable/start-monica.bat`:
```batch
set PORT=9000
```

### Performance-Optimierung

**Für schnellere Ladezeiten:**
```env
CACHE_STORE=file
SESSION_DRIVER=file
```

**Für viele Kontakte:**
```env
SCOUT_DRIVER=database
FULL_TEXT_INDEX=true
```

## 🐛 Probleme lösen

### Monica startet nicht

**Checkliste:**
- [ ] Ist PHP installiert? (`portable/php/php.exe` vorhanden?)
- [ ] Führen Sie `setup-php.bat` aus
- [ ] Ist Port 8000 frei? (andere Programme prüfen)
- [ ] Schreibrechte im Ordner vorhanden?

### Seite lädt nicht

**Lösungen:**
1. Warten Sie 15-20 Sekunden nach dem Start
2. Versuchen Sie http://127.0.0.1:8001
3. Öffnen Sie `start.html` manuell
4. Prüfen Sie das Konsolenfenster auf Fehlermeldungen

### Datenbank-Fehler

**Häufige Ursachen:**
- Datenbank gesperrt (Monica noch im Hintergrund?)
- Schreibrechte fehlen
- Korrupte Datenbank

**Lösung:**
1. Alle Monica-Prozesse beenden
2. Backup von `database/database.sqlite` erstellen
3. Monica neu starten

### Langsame Performance

**Optimierungen:**
1. Alte Einträge archivieren
2. Cache leeren: `php artisan cache:clear`
3. Mehr RAM freigeben
4. USB-Stick mit USB 3.0 nutzen

## 🌍 Auf verschiedenen Computern nutzen

### USB-Stick als Portable App

**Setup:**
1. Kopieren Sie den gesamten `monica/` Ordner auf USB-Stick
2. Auf jedem Computer: USB-Stick einstecken
3. Zu `monica/portable/` navigieren
4. `start-monica.bat` starten

**Vorteile:**
- Nehmen Sie Ihre Daten überall hin mit
- Keine Installation nötig
- Funktioniert ohne Admin-Rechte
- Plug & Play

**Hinweise:**
- USB 3.0 für bessere Performance
- Verschlüsselten USB-Stick verwenden
- Immer sauber beenden (STRG+C)

### Cloud-Ordner (Dropbox, OneDrive)

**Setup:**
1. Monica-Ordner in Cloud-Ordner verschieben
2. Auf anderen Geräten: Warten bis synchronisiert
3. Starten wie gewohnt

**⚠️ Wichtig:**
- Nur auf einem Computer gleichzeitig nutzen!
- SQLite unterstützt keinen gleichzeitigen Zugriff
- Vor dem Start: Synchronisation abwarten

## 📊 Updates & Wartung

### Monica aktualisieren

**Vorsicht: Backup erstellen!**

1. Backup von `database/database.sqlite` und `.env`
2. Neue Monica-Version herunterladen
3. Portable-Setup in neue Version kopieren
4. Backup-Dateien in neue Version kopieren
5. Testen

### Datenbank optimieren

Führen Sie gelegentlich aus:
```batch
cd /d "C:\Pfad\zu\monica"
portable\php\php.exe artisan optimize
portable\php\php.exe artisan cache:clear
```

## 📞 Hilfe & Support

### Ressourcen

- **Offizielle Dokumentation**: https://docs.monicahq.com
- **GitHub Repository**: https://github.com/monicahq/monica
- **Community Forum**: https://github.com/monicahq/monica/discussions

### Häufige Fragen (FAQ)

**F: Kostet Monica etwas?**
A: Nein, Monica ist komplett kostenlos und Open Source.

**F: Kann ich Monica mit meinem Handy synchronisieren?**
A: Diese Portable-Version nicht. Dafür benötigen Sie die Server-Version mit Cloud-Zugang.

**F: Wie viele Kontakte kann ich speichern?**
A: Theoretisch unbegrenzt. SQLite kommt problemlos mit Tausenden Kontakten klar.

**F: Kann ich Monica für meine Familie nutzen?**
A: Ja! Aktivieren Sie Multi-User in der `.env` Datei.

**F: Ist meine Datenbank verschlüsselt?**
A: Standardmäßig nein. Nutzen Sie BitLocker oder VeraCrypt für Verschlüsselung.

**F: Kann ich meine Daten später migrieren?**
A: Ja! Monica bietet Export/Import-Funktionen.

## 🎉 Los geht's!

Sie sind jetzt bereit, Monica zu nutzen!

**Erste Schritte:**
1. Starten Sie `start-monica.bat`
2. Erstellen Sie Ihren Account
3. Fügen Sie Ihre ersten Kontakte hinzu
4. Erkunden Sie die Funktionen

**Pro-Tipps für Anfänger:**
- Nehmen Sie sich Zeit, die Oberfläche zu erkunden
- Starten Sie mit 5-10 wichtigen Kontakten
- Nutzen Sie Tags zur Organisation
- Aktivieren Sie Benachrichtigungen für Geburtstage

---

## ❤️ Viel Erfolg mit Monica!

Monica hilft Ihnen, die Beziehungen in Ihrem Leben besser zu pflegen und zu organisieren.

**Feedback & Verbesserungen**
Haben Sie Vorschläge für diese Portable-Version? Öffnen Sie ein Issue auf GitHub!

---

*"The personal CRM to remember everything about your friends, family and business relationships."*

**Version**: Portable Edition
**Lizenz**: AGPL-3.0
**Website**: https://www.monicahq.com
