# MeetGuide 📅📍
---
### Autor
Erstellt von [Benjamin Matzmorr/Stadtverwaltung-Castrop-Rauxel].
Dieses Projekt steht unter der [MIT License](LICENSE).

**MeetGuide** ist ein schlankes, PHP-basiertes Digital-Signage-System, das speziell für die Anzeige von Raumbelegungen und Orientierungshilfen (Wayfinding) in öffentlichen Gebäuden, Behörden oder Bürokomplexen entwickelt wurde.
<img width="1253" height="900" alt="meetguide" src="https://github.com/user-attachments/assets/ad26fd21-89d2-4e77-82eb-54e23af51bb0" />
<img width="1174" height="847" alt="image" src="https://github.com/user-attachments/assets/a9d50989-57ec-400a-9ff8-8443a9bd97c3" />

## ✨ Features

- **Echtzeit-Synchronisation:** Automatische Abfrage von Terminen über CalDAV-Schnittstellen (z. B. Nextcloud, Baikal).
- **Intelligentes Wayfinding:** Integrierte Liste für statische Etagen-Infos oder Wegweiser unterhalb der aktuellen Termine.
- **Dynamisches Layout:** Wechselt automatisch zwischen Listen- und Grid-Ansicht und nutzt Paginierung bei vielen Terminen.
- **Admin-Panel:** Komfortable Verwaltung von Kalenderquellen und Display-Konfigurationen über ein Web-Interface.
- **Responsive Design:** Optimiert für große Info-Stelen und TV-Monitore mit automatischem Dark/Light-Mode.
- **Anpassbare Limits:** Individuelle Einstellung, wie viele Termine pro Seite angezeigt werden sollen.

## 🚀 Installation

1. **Repository klonen/hochladen:**
   Lade die Dateien auf deinen PHP-Webserver hoch.
   
2. **Voraussetzungen:**
   - PHP 7.4 oder höher
   - Webserver (Apache mit `.htaccess` Support empfohlen)
   - Schreibrechte für den Ordner `/cache` und `/config`

3. **Konfiguration:**   
   - Trage deine Zugangsdaten für den Adminbereich in auth_check.php ein bzw. ändere es.
   -  Google Font und Bootstrap werden aus CDN geladen - ggf. ändern wenn IP-Adressen nicht ins Ausland gehen sollen.

4. **Einrichtung:**
   - Rufe `admin.php` auf, um deine ersten Kalender-Accounts und Displays anzulegen.
   - Die Anzeige erfolgt über `display.php?id=DEINE_ID`.

## 🛠 Technologien

- **Backend:** PHP (für API-Anbindung und Datenverarbeitung)
- **Frontend:** HTML5, CSS3 (Flexbox/Grid), JavaScript (ES6)
- **Styling:** Bootstrap 5 für das Admin-Panel, Custom CSS mit CSS-Variables für die Dashboards.

## 🔒 Sicherheit

- Der Zugriff auf Konfigurationsdateien wird durch `.htaccess`-Regeln geschützt.
---

Erstellt mit Fokus auf minimale Anforderungen, Performance und Benutzerfreundlichkeit.

