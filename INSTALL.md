# Installationsanleitung - PHP-DynDNS-Access-Manager

## 🚀 Schnellstart

### Voraussetzungen
- PHP 7.4 oder höher
- Webserver (Apache, Nginx, etc.)
- Schreibberechtigungen für das Webverzeichnis
- Internetverbindung für DynDNS-Auflösungen

### 1. Dateien herunterladen
```bash
# Git Repository klonen
git clone https://github.com/your-repo/PHP-DynDNS-Access-Manager.git
cd PHP-DynDNS-Access-Manager

# Oder ZIP-Datei herunterladen und entpacken
```

### 2. Dateien auf Webserver kopieren
```bash
# Alle Dateien in Ihr Webverzeichnis kopieren
cp -r * /var/www/html/your-website/

# Oder nur die benötigten Dateien
cp accesscontrol.php config.php /var/www/html/your-website/
```

### 3. Berechtigungen setzen
```bash
# Verzeichnis erstellen und Berechtigungen setzen
mkdir -p /var/www/html/your-website/access_logs
chmod 755 /var/www/html/your-website/access_logs
chmod 644 /var/www/html/your-website/config.php
chmod 644 /var/www/html/your-website/accesscontrol.php

# Webserver-Benutzer als Besitzer setzen (falls nötig)
chown www-data:www-data /var/www/html/your-website/access_logs
```

### 4. Konfiguration anpassen
Bearbeiten Sie die `config.php` Datei:

```php
<?php
// DynDNS-Hostnamen (Ihre DynDNS-Hostnamen hier eintragen)
$dyndns_addresses = array(
    'ihr-hostname.dyndns.org',
    'mein-synology.synology.me'
);

// Feste IP-Adressen (Ihre statischen IPs hier eintragen)
$fixed_ips = array(
    '192.168.1.100',
    '203.0.113.50'
);

// Lokales Netzwerk (Ihr lokales Netzwerk hier eintragen)
$local_network_range = '192.168.1.0/24';

// Cache-Einstellungen
$cache_duration = 300; // 5 Minuten
$log_retention_days = 30;

// Sicherheitseinstellungen
$rate_limit_attempts = 10; // 10 Versuche pro Stunde
$debug_mode = false; // In Produktion auf false setzen
?>
```

### 5. Integration testen
Erstellen Sie eine Test-Datei `test.php`:

```php
<?php
// Zugriffskontrolle einbinden
include 'accesscontrol.php';

// Test-Ausgabe
echo "<h1>Zugriffskontrolle funktioniert!</h1>";
echo "<p>Ihre IP: " . $_SERVER['REMOTE_ADDR'] . "</p>";
echo "<p>Zeit: " . date('Y-m-d H:i:s') . "</p>";
?>
```

### 6. Test durchführen
Öffnen Sie `http://ihre-domain.com/test.php` in Ihrem Browser.

## 🔧 Detaillierte Installation

### Apache-Konfiguration

#### .htaccess-Datei (optional)
Erstellen Sie eine `.htaccess`-Datei für zusätzliche Sicherheit:

```apache
# Zugriffskontrolle für alle PHP-Dateien
<Files "*.php">
    # Nur lokale Zugriffe erlauben (optional)
    # Order Deny,Allow
    # Deny from all
    # Allow from 127.0.0.1
    # Allow from ::1
</Files>

# Verzeichnislisting deaktivieren
Options -Indexes

# PHP-Fehler verstecken
php_flag display_errors off
php_flag log_errors on

# Maximale Ausführungszeit erhöhen (für DynDNS-Auflösungen)
php_value max_execution_time 30
```

#### Virtual Host-Konfiguration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/your-website
    
    <Directory /var/www/html/your-website>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs für Debugging
    ErrorLog ${APACHE_LOG_DIR}/your-domain_error.log
    CustomLog ${APACHE_LOG_DIR}/your-domain_access.log combined
</VirtualHost>
```

### Nginx-Konfiguration

#### Server-Block
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/your-website;
    index index.php index.html;

    # PHP-Verarbeitung
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Verzeichnislisting deaktivieren
    location ~ /\. {
        deny all;
    }

    # Logs
    access_log /var/log/nginx/your-domain_access.log;
    error_log /var/log/nginx/your-domain_error.log;
}
```

## 🔒 Sicherheitskonfiguration

### Admin-Interface absichern

#### 1. Passwort ändern
Bearbeiten Sie die `admin.php` Datei:

```php
// Ändern Sie diese Zeilen
$admin_username = 'ihr-benutzername';
$admin_password = 'ihr-sicheres-passwort';
```

#### 2. Admin-Verzeichnis schützen
Erstellen Sie ein separates Verzeichnis für das Admin-Interface:

```bash
mkdir /var/www/html/your-website/admin
cp admin.php /var/www/html/your-website/admin/
```

#### 3. .htaccess für Admin-Bereich
```apache
# /var/www/html/your-website/admin/.htaccess
AuthType Basic
AuthName "Admin-Bereich"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

#### 4. .htpasswd erstellen
```bash
htpasswd -c /path/to/.htpasswd admin
```

### SSL/TLS konfigurieren

#### Let's Encrypt (empfohlen)
```bash
# Certbot installieren
sudo apt install certbot python3-certbot-apache

# SSL-Zertifikat erstellen
sudo certbot --apache -d your-domain.com

# Automatische Erneuerung
sudo crontab -e
# Fügen Sie hinzu: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 📊 Monitoring einrichten

### 1. Admin-Interface konfigurieren
```php
// In config.php
$debug_mode = false; // Produktionsmodus
$extended_logging = true; // Erweiterte Logs aktivieren
```

### 2. Log-Rotation konfigurieren
```bash
# /etc/logrotate.d/access-control
/var/www/html/your-website/access_logs/*.txt {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

### 3. Monitoring-Skript erstellen
```bash
#!/bin/bash
# /usr/local/bin/check-access-control.sh

LOG_DIR="/var/www/html/your-website/access_logs"
TODAY=$(date +%Y-%m-%d)
LOG_FILE="$LOG_DIR/log_$TODAY.txt"

# Prüfe ob Log-Datei existiert
if [ ! -f "$LOG_FILE" ]; then
    echo "WARNUNG: Log-Datei für heute nicht gefunden: $LOG_FILE"
    exit 1
fi

# Zähle verweigerte Zugriffe
DENIED_COUNT=$(grep -c "denied" "$LOG_FILE" 2>/dev/null || echo "0")

# Sende Warnung bei zu vielen verweigerten Zugriffen
if [ "$DENIED_COUNT" -gt 50 ]; then
    echo "WARNUNG: $DENIED_COUNT verweigerte Zugriffe heute" | mail -s "Access Control Alert" admin@your-domain.com
fi
```

## 🐛 Troubleshooting

### Häufige Probleme und Lösungen

#### Problem: "Configuration file not found"
```bash
# Lösung: Pfad überprüfen
ls -la /var/www/html/your-website/config.php

# Falls nicht vorhanden, kopieren Sie die Datei
cp config.php /var/www/html/your-website/
```

#### Problem: "Failed to create log directory"
```bash
# Lösung: Berechtigungen prüfen und korrigieren
sudo chown -R www-data:www-data /var/www/html/your-website/access_logs
sudo chmod 755 /var/www/html/your-website/access_logs
```

#### Problem: DynDNS-Auflösung funktioniert nicht
```bash
# Lösung: DNS-Auflösung testen
nslookup ihr-hostname.dyndns.org

# PHP-DNS-Funktionen prüfen
php -r "echo gethostbyname('google.com');"
```

#### Problem: Rate Limiting zu streng
```php
// Lösung: In config.php anpassen
$rate_limit_attempts = 50; // Erhöhen oder auf 0 setzen zum Deaktivieren
```

#### Problem: Performance-Probleme
```php
// Lösung: Cache-Einstellungen optimieren
$cache_duration = 1800; // 30 Minuten
$log_retention_days = 7; // Kürzere Aufbewahrung
```

### Debug-Modus aktivieren

#### 1. Debug-Modus einschalten
```php
// In config.php
$debug_mode = true;
```

#### 2. PHP-Fehlerlog aktivieren
```php
// Am Anfang von accesscontrol.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');
```

#### 3. Logs überprüfen
```bash
# PHP-Fehlerlog
tail -f /var/log/php_errors.log

# Apache/Nginx-Logs
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log

# Access-Control-Logs
tail -f /var/www/html/your-website/access_logs/log_$(date +%Y-%m-%d).txt
```

## 🔄 Upgrade von älterer Version

### 1. Backup erstellen
```bash
# Vollständiges Backup
tar -czf backup-$(date +%Y%m%d).tar.gz /var/www/html/your-website/

# Oder nur wichtige Dateien
cp accesscontrol.php accesscontrol.php.backup
cp config.php config.php.backup
```

### 2. Neue Dateien installieren
```bash
# Neue Dateien kopieren
cp accesscontrol.php /var/www/html/your-website/
cp config.php /var/www/html/your-website/
cp admin.php /var/www/html/your-website/
```

### 3. Konfiguration migrieren
```php
// Alte Einstellungen aus der alten accesscontrol.php in config.php übertragen
$dyndns_addresses = array('ihr-alter-hostname.dyndns.org');
$fixed_ips = array('ihre-alte-ip');
$local_network_range = 'ihr-altes-netzwerk';
```

### 4. Testen
```bash
# Test-Datei erstellen
echo '<?php include "accesscontrol.php"; echo "Upgrade erfolgreich!"; ?>' > test_upgrade.php

# Test durchführen
curl http://your-domain.com/test_upgrade.php
```

## 📈 Performance-Optimierung

### 1. Cache-Einstellungen optimieren
```php
// Für stabile IPs
$cache_duration = 3600; // 1 Stunde

// Für häufige IP-Änderungen
$cache_duration = 60; // 1 Minute
```

### 2. Log-Rotation optimieren
```bash
# Tägliche Log-Rotation
0 2 * * * /usr/sbin/logrotate /etc/logrotate.d/access-control
```

### 3. Monitoring-Skripte
```bash
# Cron-Job für Monitoring
*/15 * * * * /usr/local/bin/check-access-control.sh
```

## 🔐 Sicherheitscheckliste

- [ ] Passwort in admin.php geändert
- [ ] SSL/TLS konfiguriert
- [ ] Debug-Modus deaktiviert
- [ ] Sichere Berechtigungen gesetzt
- [ ] Log-Rotation konfiguriert
- [ ] Monitoring eingerichtet
- [ ] Backup-Strategie implementiert
- [ ] Firewall-Regeln konfiguriert
- [ ] Regelmäßige Updates geplant

## 📞 Support

Bei Problemen:

1. **Logs überprüfen**: Alle relevanten Log-Dateien durchsehen
2. **Debug-Modus aktivieren**: Temporär für Fehlerdiagnose
3. **Dokumentation lesen**: README_IMPROVED.md und Inline-Kommentare
4. **Community**: GitHub Issues oder Diskussionsbereich
5. **Backup wiederherstellen**: Falls nötig, auf vorherige Version zurücksetzen

---

**Installation abgeschlossen!** 🎉

Ihr PHP-DynDNS-Access-Manager ist jetzt einsatzbereit. Vergessen Sie nicht, das Admin-Passwort zu ändern und regelmäßige Backups zu erstellen. 