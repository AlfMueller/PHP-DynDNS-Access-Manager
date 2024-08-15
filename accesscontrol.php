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

// Directory for logs and temp IP file
$log_dir = 'access_logs';

// Ensure the log directory exists
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Path to the temp IP file
$ip_file = $log_dir . '/allowed_temp_ip.txt';

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
function log_denied_ip($dir, $ip, $script) {
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $log_entry = "$time - $ip - $script\n";
    $log_file = "$dir/log_$date.txt";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Add fixed IP addresses to the list of allowed IPs
$allowed_ips = $fixed_ips;

// Read IP addresses from the file and add them
$allowed_ips = array_merge($allowed_ips, read_ip_file($ip_file));

// Get the visitor's IP address
$user_ip = null;

if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // Handle cases where there might be multiple IPs in the header (comma-separated list)
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $user_ip = trim(end($ip_list)); // Take the last IP in the list
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $user_ip = $_SERVER['HTTP_X_REAL_IP'];
} else {
    $user_ip = $_SERVER['REMOTE_ADDR'];
}


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
    $current_script = $_SERVER['SCRIPT_NAME'];
    log_denied_ip($log_dir, $user_ip, $current_script);
    echo "Access denied!";
    exit;
}

echo "Access granted!";
// Here you can display the content of your website
?>
