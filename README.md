# PHP-DynDNS-Access-Manager

## Overview

PHP-DynDNS-Access-Manager is a modern, secure, and easy-to-use PHP script for managing access to your web resources based on fixed IPs, dynamic IPs (via DynDNS), and local network ranges. It features a full admin interface, logging, statistics, and a flexible configuration system.

---

## Features

- **Unified Logging:** All access attempts (granted/denied) are logged with status, IP, script, and user agent.
- **English-Only Codebase:** All comments, UI texts, and documentation are in English for international use.
- **Admin Interface:** Web-based admin panel for statistics, IP blacklist management, configuration overview, and cache control.
- **IP Blacklist:** Block unwanted IPs directly from the admin interface.
- **Access Statistics:** View access/denied requests, top denied IPs, and recent activity for the last 30 days.
- **Flexible Configuration:** All settings (DynDNS hosts, fixed IPs, local network, rate limiting, etc.) are in `config.php`.
- **Security:** Session timeout for admin, rate limiting, secure file permissions, and robust input validation.
- **Easy Integration:** Just include `accesscontrol.php` at the top of your protected PHP pages.
- **Modern UI:** Responsive, clean, and user-friendly admin dashboard.

---

## Installation

1. **Clone or Download**
   ```bash
   git clone https://github.com/youruser/PHP-DynDNS-Access-Manager.git
   cd PHP-DynDNS-Access-Manager
   ```

2. **Set Permissions**
   ```bash
   mkdir -p access_logs
   chmod 755 access_logs
   # Ensure your webserver user can write to this directory
   ```

3. **Configure**
   - Copy and edit `config.php` to set your DynDNS hostnames, fixed IPs, local network, and admin credentials.
   - Example:
     ```php
     $dyndns_addresses = array('myhost.dyndns.org', 'anotherhost.example.com');
     $fixed_ips = array('203.0.113.1', '198.51.100.2');
     $local_network_range = '192.168.1.0/24';
     $admin_username = 'admin';
     $admin_password = 'your_secure_password';
     ```

4. **Integrate Access Control**
   At the top of any PHP page you want to protect:
   ```php
   <?php
   include 'accesscontrol.php';
   // ... your page content ...
   ?>
   ```

5. **Access the Admin Interface**
   - Open `admin.php` in your browser (e.g. `https://yourdomain.com/admin.php`)
   - Login with the credentials from `config.php`

---

## Usage

- **Add/Remove Blacklist IPs:** Use the admin interface to block or unblock IPs.
- **View Statistics:** See total, granted, denied requests, and top denied IPs for the last 30 days.
- **Clear Cache:** Use the admin interface to clear the DynDNS IP cache if needed.
- **Change Configuration:** Edit `config.php` and reload your site.

---

## Log Format

Each access attempt is logged in `access_logs/log_YYYY-MM-DD.txt`:
```
[HH:MM:SS] IP - SCRIPT - STATUS - USER_AGENT
```
Example:
```
[12:34:56] 203.0.113.1 - /index.php - granted - Mozilla/5.0 ...
[12:35:01] 198.51.100.2 - /index.php - denied - Mozilla/5.0 ...
```

---

## Security Best Practices

- Change the default admin password in `config.php`!
- Use HTTPS for your admin interface.
- Set correct permissions for `access_logs` (not world-writable).
- Regularly check the logs and blacklist suspicious IPs.
- Keep your PHP version up to date.

---

## Contributing

Pull requests and issues are welcome! Please use English for all code, comments, and issues.

---

## License

MIT License. See [LICENSE](LICENSE) for details.


