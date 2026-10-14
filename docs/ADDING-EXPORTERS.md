# Spätere Erweiterung um weitere Geräte

Der Synchronisationskern bleibt bewusst unabhängig vom Geräteformat. Ausgeliefert wird nur der produktiv getestete Yealink-Exporter.

Ein weiterer Hersteller wird erst ergänzt, wenn ein konkretes Zielgerät vorhanden und testbar ist. Dann gilt:

1. Exporter unter `src/Export/` implementieren und `ExporterInterface` verwenden.
2. Gerätetyp explizit in `DeviceRepository` freigeben.
3. Exporter in `public/export.php` zuordnen.
4. Gerätespezifische Fähigkeiten in der UI nur anzeigen, wenn sie tatsächlich unterstützt werden.
5. Autorisierung, XML/Format und Grenzfälle automatisiert testen.
6. Unterstützte Modelle/Firmwarestände dokumentieren.

Geräte-Zugangsdaten gehören nicht in Exporter. Authentifizierung und Capability-Prüfung bleiben in Geräte-/HTTP-Schicht.
