# Architektur

## Datenfluss

```text
Google People API
      ↓
GoogleContactsClient
      ↓
normalisierte Kontakte
      ↓
ContactRepository
(storage/cache/contacts.json)
      ↓
export.php + Geräteautorisierung
      ↓
YealinkExporter
```

Admin-Downloads für JSON/CSV verwenden denselben Kontaktcache. Es gibt keine zweite Kontakt-Wahrheit und keinen dauerhaft erzeugten Yealink-XML-Cache.

## Kontaktmodell

```json
{
  "name": "Max Mustermann",
  "numbers": [
    {"number": "+491701234567", "label": "Mobil", "type": "mobile"},
    {"number": "+492159123456", "label": "Büro", "type": "work"}
  ]
}
```

Die Anwendung verwendet ausschließlich dieses strukturierte Nummernmodell.

## Gerätemodell

`storage/devices.json` enthält pro Gerät:

- zufällige Geräte-ID
- Anzeigename
- Aktiv/Inaktiv
- zufälligen 256-Bit-Token
- Freigabe für das Kontaktverzeichnis
- optionale Freigabe des Yealink-Sperrlisten-Verzeichnisses
- Notiz
- letzten Abruf, zuletzt abgerufenes Verzeichnis und Abrufzähler

Telefonseitige Exporte werden ausschließlich über den individuellen Gerätetoken autorisiert. Globale Telefonbuch-Zugangsdaten existieren nicht.

## Sperrliste

`storage/blocklist.json` ist die zentrale Quelle für exakt gesperrte Rufnummern. Die Rufnummern werden mit derselben Länderlogik normalisiert wie Google-Kontakte.

Für Yealink kann daraus ein zweites Remote Phone Book erzeugt werden. Dieses Remote-Verzeichnis befüllt nicht automatisch die lokale T54W-Blocklist; die tatsächliche Anrufabweisung bleibt eine Gerätefunktion.

## Sync-Historie

`storage/sync_history.json` hält die letzten 100 Läufe mit:

- Zeitpunkt
- Quelle (`manual`, `cron`, `oauth`)
- Erfolg/Fehler
- Kontakt- und Rufnummernanzahl
- Laufzeit
- gekürzter Fehlermeldung

## Sicherheitsgrenzen

Nur `public/` ist Web-DocumentRoot. Konfiguration, Programmcode außerhalb `public/`, Tests, Tools und Laufzeitdaten bleiben nicht öffentlich erreichbar.
