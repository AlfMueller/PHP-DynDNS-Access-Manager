<?php

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

// Do not change anything below this line
// -------------------------------------------------------

// Path to the text file with allowed IP addresses
$ip_file = 'allowed_temp_ip.txt';
// Path to the log file for denied IP addresses
$log_file = 'log.txt';

// Function to resolve hostnames to IP addresses
function resolve_dyndns_addresses($addresses) {
    $ips = array();
    foreach ($addresses as $address) {
        $ip = gethostbyname($address);
        if ($ip != $address) { // Check if the resolution was successful
            $ips[] = $ip;
        }
    }
    return $ips;
}

// Function to read IP addresses from the file
function read_ip_file($file) {
    if (file_exists($file)) {
        return file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    return array();
}

// Function to write IP addresses to the file
function write_ip_file($file, $ips) {
    file_put_contents($file, implode(PHP_EOL, $ips));
}

// Function to check if an IP address is within a specific range
function ip_in_range($ip, $range) {
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask; // Apply subnet mask
    return ($ip & $mask) == $subnet;
}

// Function to log denied IP addresses
function log_denied_ip($file, $ip) {
    $date = date('Y-m-d H:i:s');
    $log_entry = "$date - $ip\n";
    file_put_contents($file, $log_entry, FILE_APPEND);
}

// Add fixed IP addresses to the list of allowed IPs
$allowed_ips = $fixed_ips;

// Read IP addresses from the file and add them
$allowed_ips = array_merge($allowed_ips, read_ip_file($ip_file));

// Get the visitor's IP address
$user_ip = $_SERVER['REMOTE_ADDR'];

// Check if the visitor's IP address is allowed
$access_granted = in_array($user_ip, $allowed_ips);

// Check if the visitor's IP address is within the local network range
if (!$access_granted) {
    $access_granted = ip_in_range($user_ip, $local_network_range);
}

if (!$access_granted) {
    // Resolve DynDNS addresses and update the IP file
    $resolved_ips = resolve_dyndns_addresses($dyndns_addresses);
    write_ip_file($ip_file, $resolved_ips);

    // Merge fixed IP addresses and resolved IP addresses
    $allowed_ips = array_merge($fixed_ips, $resolved_ips);

    // Check again if the visitor's IP address is allowed
    $access_granted = in_array($user_ip, $allowed_ips);

    // Check again if the visitor's IP address is within the local network range
    if (!$access_granted) {
        $access_granted = ip_in_range($user_ip, $local_network_range);
    }
}

if (!$access_granted) {
    log_denied_ip($log_file, $user_ip);
    echo "Access denied!";
    exit;
}

echo "Access granted!";
// Here you can display the content of your website
?>
