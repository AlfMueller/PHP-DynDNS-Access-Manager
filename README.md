# Dynamic-IP-Access-Control

## Description

Dynamic-IP-Access-Control is a PHP script designed to manage access to web resources based on a combination of fixed IP addresses, dynamic IP addresses resolved via DynDNS, and local network IP ranges. It ensures that only authorized IP addresses can access the protected content, while logging any denied access attempts.

## Features

- **Support for Fixed IP Addresses:** Easily add static IP addresses that are always allowed access.
- **Dynamic IP Resolution via DynDNS:** Automatically resolve and allow IP addresses associated with specified DynDNS hostnames.
- **Local Network Range Support:** Define and allow access for a specific range of local network IP addresses.
- **Logging of Denied Access Attempts:** Record IP addresses that are denied access in a log file for auditing and security purposes.
- **Automatic IP Address Updates:** Refresh the allowed IP addresses from DynDNS hostnames if access is initially denied, ensuring the list is always up-to-date.

## Usage (accesscontrol.php)

1. **Configure Fixed IP Addresses:** Add your fixed IP addresses to the `$fixed_ips` array.
2. **Add DynDNS Hostnames:** List the DynDNS hostnames in the `$dyndns_addresses` array . 
3. **Set Local Network Range:** Define your local network IP range in the `$local_network_range` variable.
4. **Do Not Change Below:** The script reads, writes, and updates IP addresses from `allowed_temp_ip.txt`, and logs denied access attempts to `log.txt`.

## Example Configuration

```php
// DynDNS addresses
$dyndns_addresses = array(
    'xxx.synology.me',
    'beta.qnet.com',
    'gamma.dyndns.org'
);

// Fixed IP addresses
$fixed_ips = array(
    '123.456.789.000',
    '111.222.333.444'
);

// Local network range
$local_network_range = '10.10.55.0/24';
```

## Installation
1. **Clone the repository to your web server.
2. **Update the $dyndns_addresses, $fixed_ips, and $local_network_range variables with your specific values.
3. **Ensure the folder `access_logs` has appropriate write permissions for the web server.
4. **Ensure `allowed_temp_ip.txt` and `log.txt` have appropriate write permissions for the web server.

OR Copy the code in your script :-)

## Contributing
Feel free to submit issues and pull requests to improve this script. Contributions are always welcome!

this `README.md` provides a clear overview of the project, its features, usage instructions, and example configuration, along with installation and contributing guidelines.
