# Installation Guide - PHP-DynDNS-Access-Manager

## Prerequisites
- PHP 7.4 or higher
- Web server (Apache, Nginx, etc.)
- Write permissions for the web directory
- Internet access for DynDNS resolution

## 1. Download the Files
```bash
git clone https://github.com/youruser/PHP-DynDNS-Access-Manager.git
cd PHP-DynDNS-Access-Manager
```

## 2. Copy Files to Your Web Server
```bash
cp -r * /var/www/html/your-website/
```

## 3. Set Permissions
```bash
mkdir -p /var/www/html/your-website/access_logs
chmod 755 /var/www/html/your-website/access_logs
# Make sure your webserver user can write to this directory
```

## 4. Configure
Edit `config.php` to set your DynDNS hostnames, fixed IPs, local network, and admin credentials:
```php
$dyndns_addresses = array('yourhost.dyndns.org', 'anotherhost.example.com');
$fixed_ips = array('203.0.113.1', '198.51.100.2');
$local_network_range = '192.168.1.0/24';
$admin_username = 'admin';
$admin_password = 'your_secure_password';
```

## 5. Integrate Access Control
At the top of any PHP page you want to protect:
```php
<?php
include 'accesscontrol.php';
// ... your page content ...
?>
```

## 6. Test the Installation
Open your protected page in the browser. You should see "Access granted!" or "Access denied!" depending on your IP and configuration.

## 7. Use the Admin Interface
- Open `admin.php` in your browser (e.g. `https://yourdomain.com/admin.php`)
- Login with the credentials from `config.php`
- Manage blacklist, view statistics, and clear cache as needed

## 8. Security Recommendations
- Change the default admin password in `config.php`!
- Use HTTPS for your admin interface.
- Set correct permissions for `access_logs` (not world-writable).
- Regularly check the logs and blacklist suspicious IPs.
- Keep your PHP version up to date.

## 9. Troubleshooting
- Check file permissions if logs or cache are not written.
- Check your PHP error log for issues.
- Make sure your DynDNS hostnames resolve correctly from PHP (use `gethostbyname()` in a test script).

## 10. Updating
To update, pull the latest changes and review the changelog. Always backup your config and logs before updating.

---

**Installation complete!**

Your PHP-DynDNS-Access-Manager is now ready to use. 