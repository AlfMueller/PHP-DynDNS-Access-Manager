# PHP-DynDNS-Access-Manager - Verbesserte Version

## 📋 Übersicht

Der PHP-DynDNS-Access-Manager ist ein robustes Zugriffskontrollsystem für Webressourcen, das feste IP-Adressen, DynDNS-Hostnamen und lokale Netzwerkbereiche unterstützt. Diese verbesserte Version bietet erweiterte Sicherheitsfeatures, bessere Performance und ein Admin-Interface.

## ✨ Neue Features

### 🔒 Sicherheitsverbesserungen
- **IP-Validierung**: Umfassende Validierung aller IP-Adressen
- **Rate Limiting**: Schutz vor Brute-Force-Angriffen
- **Automatisches Blacklisting**: IPs werden automatisch bei zu vielen Fehlversuchen blockiert
- **Sichere Verzeichnisberechtigungen**: 0755 statt 0777
- **Dateisperren**: Verhindert Race Conditions bei gleichzeitigen Zugriffen

### 📊 Monitoring & Logging
- **Detailliertes Logging**: User-Agent, Referer, Request-Method und mehr
- **API-Endpunkte**: JSON-API für Statistiken (`?api=stats`)
- **Admin-Interface**: Web-basierte Verwaltung unter `/admin.php`
- **Automatische Log-Bereinigung**: Alte Logs werden automatisch gelöscht

### ⚡ Performance-Optimierungen
- **Intelligentes Caching**: DynDNS-Auflösungen werden gecacht
- **Lazy Loading**: IP-Auflösung nur bei Bedarf
- **Duplikat-Entfernung**: Automatische Bereinigung von doppelten IPs
- **Optimierte Dateioperationen**: Verwendung von LOCK_EX für Thread-Safety

### 🌐 Benutzerfreundlichkeit
- **Mehrsprachige Fehlermeldungen**: Deutsch und Englisch
- **Responsive Design**: Moderne, benutzerfreundliche Oberfläche
- **Konfigurationsdatei**: Separate `config.php` für einfache Wartung
- **Debug-Modus**: Entwicklungsumgebung mit zusätzlichen Informationen

## 📁 Projektstruktur

```
PHP-DynDNS-Access-Manager/
├── accesscontrol.php           # Verbesserte Hauptdatei
├── accesscontrol_advanced.php  # Erweiterte Version mit Rate Limiting
├── admin.php                   # Admin-Interface
├── config.php                  # Konfigurationsdatei
├── index.php                   # Beispiel-Integration
├── access_logs/                # Log-Verzeichnis
│   ├── allowed_temp_ip.txt     # IP-Cache
│   ├── log_YYYY-MM-DD.txt      # Tägliche Logs
│   ├── rate_limit_YYYY-MM-DD.json # Rate Limiting Daten
│   ├── ip_blacklist.txt        # IP-Blacklist
│   └── ip_failures.json        # Fehlversuch-Zähler
├── README.md                   # Original-Dokumentation
├── README_IMPROVED.md          # Diese Datei
└── LICENSE                     # MIT-Lizenz
```

## 🚀 Installation

### 1. Dateien herunterladen
```bash
git clone https://github.com/your-repo/PHP-DynDNS-Access-Manager.git
cd PHP-DynDNS-Access-Manager
```

### 2. Konfiguration anpassen
Bearbeiten Sie die `config.php` Datei:

```php
// DynDNS-Hostnamen
$dyndns_addresses = array(
    'ihr-hostname.dyndns.org',
    'mein-synology.synology.me'
);

// Feste IP-Adressen
$fixed_ips = array(
    '192.168.1.100',
    '203.0.113.50'
);

// Lokales Netzwerk
$local_network_range = '192.168.1.0/24';

// Cache-Einstellungen
$cache_duration = 300; // 5 Minuten
$log_retention_days = 30;

// Sicherheitseinstellungen
$rate_limit_attempts = 10; // 10 Versuche pro Stunde
$debug_mode = false; // In Produktion auf false setzen
```

### 3. Berechtigungen setzen
```bash
chmod 755 access_logs/
chmod 644 config.php
chmod 644 accesscontrol.php
```

### 4. Integration in Ihre Website
```php
<?php
// Einfache Integration
include 'accesscontrol.php';

// Ihr Website-Inhalt hier
echo "Willkommen auf meiner Website!";
?>
```

## 🔧 Konfiguration

### Grundlegende Einstellungen

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| `$dyndns_addresses` | Array von DynDNS-Hostnamen | `[]` |
| `$fixed_ips` | Array von festen IP-Adressen | `[]` |
| `$local_network_range` | Lokales Netzwerk in CIDR-Notation | `'10.10.55.0/24'` |
| `$cache_duration` | Cache-Dauer in Sekunden | `300` |
| `$log_retention_days` | Log-Aufbewahrung in Tagen | `30` |

### Sicherheitseinstellungen

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| `$debug_mode` | Debug-Modus aktivieren | `false` |
| `$rate_limit_attempts` | Rate Limiting (0 = deaktiviert) | `10` |
| `$extended_logging` | Erweiterte Logging-Funktionen | `true` |

### Erweiterte Einstellungen

| Einstellung | Beschreibung | Standard |
|-------------|--------------|----------|
| `$dns_timeout` | DNS-Timeout in Sekunden | `10` |
| `$max_dyndns_hostnames` | Maximale DynDNS-Hostnamen | `50` |
| `$max_fixed_ips` | Maximale feste IPs | `100` |
| `$enable_file_locking` | Dateisperren aktivieren | `true` |

## 📊 Admin-Interface

### Zugriff
Das Admin-Interface ist unter `/admin.php` verfügbar.

**Standard-Anmeldedaten:**
- Benutzername: `admin`
- Passwort: `your_secure_password_here`

**⚠️ Wichtig:** Ändern Sie das Passwort in der `admin.php` Datei!

### Features
- **Statistiken**: Übersicht über Zugriffe, erlaubte/verweigerte Anfragen
- **IP-Blacklist**: Verwalten von blockierten IP-Adressen
- **Cache-Verwaltung**: Manuelles Leeren des IP-Caches
- **Konfiguration**: Anzeige der aktuellen Einstellungen
- **Live-Aktivität**: Echtzeit-Überwachung der Zugriffe

## 🔌 API-Endpunkte

### Statistiken abrufen
```bash
GET /your-script.php?api=stats
```

**Antwort:**
```json
{
    "total_requests": 1250,
    "granted_requests": 1180,
    "denied_requests": 70,
    "unique_ips": ["192.168.1.100", "203.0.113.50"],
    "top_denied_ips": {
        "192.168.1.200": 15,
        "10.0.0.50": 8
    },
    "recent_activity": [...]
}
```

## 📝 Logging

### Log-Dateien
- `access_logs/log_YYYY-MM-DD.txt`: Tägliche Zugriffslogs
- `access_logs/rate_limit_YYYY-MM-DD.json`: Rate Limiting Daten
- `access_logs/ip_blacklist.txt`: IP-Blacklist
- `access_logs/ip_failures.json`: Fehlversuch-Zähler

### Log-Format
```
[Zeit] IP - Skript - Status - User-Agent - Referer - Method - URI - Zusatzdaten - Remote-IP
```

### Beispiel
```
[14:30:25] 192.168.1.100 - /index.php - granted - Mozilla/5.0... - https://google.com - GET - /index.php - {"allowed_ips_count":5} - 192.168.1.100
```

## 🛡️ Sicherheitsfeatures

### IP-Validierung
- Filterung von privaten und reservierten IP-Bereichen
- Validierung der CIDR-Notation
- Überprüfung auf gültige IP-Format

### Rate Limiting
- Konfigurierbare Anzahl von Versuchen pro Stunde
- Automatische Sperrung bei Überschreitung
- Separate Tracking-Dateien pro Tag

### Automatisches Blacklisting
- IPs werden nach 50 fehlgeschlagenen Versuchen automatisch blockiert
- Manuelle Verwaltung über Admin-Interface
- Persistente Speicherung in separater Datei

### Dateisicherheit
- Sichere Verzeichnisberechtigungen (0755)
- Dateisperren für Thread-Safety
- Validierung aller Dateioperationen

## 🔄 Migration von der ursprünglichen Version

### 1. Backup erstellen
```bash
cp accesscontrol.php accesscontrol.php.backup
```

### 2. Neue Dateien installieren
```bash
# Neue Dateien hinzufügen
cp config.php /path/to/your/website/
cp admin.php /path/to/your/website/
```

### 3. Konfiguration migrieren
Kopieren Sie Ihre Einstellungen aus der alten `accesscontrol.php` in die neue `config.php`:

```php
// Alte Einstellungen aus accesscontrol.php
$dyndns_addresses = array('ihr-hostname.dyndns.org');
$fixed_ips = array('192.168.1.100');
$local_network_range = '192.168.1.0/24';

// In config.php eintragen
```

### 4. Integration aktualisieren
```php
<?php
// Alte Integration
include 'accesscontrol.php';

// Neue Integration (identisch)
include 'accesscontrol.php';
?>
```

## 🐛 Troubleshooting

### Häufige Probleme

**Problem:** "Configuration file not found"
```
Lösung: Stellen Sie sicher, dass config.php im gleichen Verzeichnis liegt
```

**Problem:** "Failed to create log directory"
```
Lösung: Überprüfen Sie die Schreibberechtigungen für das Verzeichnis
```

**Problem:** DynDNS-Auflösung funktioniert nicht
```
Lösung: 
1. Überprüfen Sie die Internetverbindung
2. Prüfen Sie die DynDNS-Hostnamen in config.php
3. Aktivieren Sie Debug-Modus für detaillierte Fehlermeldungen
```

**Problem:** Rate Limiting zu streng
```
Lösung: Erhöhen Sie $rate_limit_attempts in config.php oder setzen Sie es auf 0
```

### Debug-Modus aktivieren
```php
// In config.php
$debug_mode = true;
```

### Logs überprüfen
```bash
# Letzte Log-Einträge anzeigen
tail -f access_logs/log_$(date +%Y-%m-%d).txt

# PHP-Fehlerlog überprüfen
tail -f /var/log/apache2/error.log
```

## 📈 Performance-Tipps

### Optimale Cache-Einstellungen
```php
// Für häufige IP-Änderungen
$cache_duration = 60; // 1 Minute

// Für stabile IPs
$cache_duration = 1800; // 30 Minuten
```

### Log-Rotation
```php
// Kürzere Aufbewahrung für bessere Performance
$log_retention_days = 7; // 1 Woche
```

### Rate Limiting anpassen
```php
// Für öffentliche APIs
$rate_limit_attempts = 100; // 100 Versuche pro Stunde

// Für private Bereiche
$rate_limit_attempts = 5; // 5 Versuche pro Stunde
```

## 🤝 Beitragen

### Entwicklungsumgebung einrichten
1. Repository klonen
2. Debug-Modus aktivieren: `$debug_mode = true;`
3. Lokale Entwicklungsumgebung verwenden

### Code-Standards
- PSR-4 Autoloading
- PSR-12 Coding Style
- PHPDoc-Kommentare für alle Funktionen
- Unit Tests für kritische Funktionen

### Pull Request erstellen
1. Feature-Branch erstellen
2. Änderungen implementieren
3. Tests hinzufügen
4. Dokumentation aktualisieren
5. Pull Request erstellen

## 📄 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Siehe [LICENSE](LICENSE) für Details.

## 🙏 Danksagungen

- **Alf Müller** - Original-Entwickler
- **PHP-Community** - Für die großartigen Tools und Bibliotheken
- **Alle Mitwirkenden** - Für Feedback und Verbesserungsvorschläge

## 📞 Support

Bei Fragen oder Problemen:

1. **Issues**: GitHub Issues verwenden
2. **Dokumentation**: Diese README und die Inline-Kommentare
3. **Community**: Diskussionsbereich auf GitHub

---

**Version:** 2.0.0  
**Letzte Aktualisierung:** Dezember 2024  
**PHP-Version:** 7.4+  
**Lizenz:** MIT 