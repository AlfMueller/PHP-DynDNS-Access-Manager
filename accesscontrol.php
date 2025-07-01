<?php

// ============================================================================
// LOAD CONFIGURATION
// ============================================================================

// Load the configuration file
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    error_log("Configuration file not found: $config_file");
    http_response_code(500);
    exit('Configuration file not found');
}

require_once $config_file;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Validate an IP address
 * @param string $ip The IP address to validate
 * @return bool True if valid, false otherwise
 */
function validate_ip($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/**
 * Validate an IP range in CIDR notation
 * @param string $range The IP range to validate
 * @return bool True if valid, false otherwise
 */
function validate_ip_range($range) {
    if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $range)) {
        return false;
    }
    
    list($subnet, $bits) = explode('/', $range);
    if (!filter_var($subnet, FILTER_VALIDATE_IP) || $bits < 0 || $bits > 32) {
        return false;
    }
    
    return true;
}

/**
 * Check if an IP address is within a specific range
 * @param string $ip The IP address to check
 * @param string $range The IP range in CIDR notation
 * @return bool True if IP is in range, false otherwise
 */
function ip_in_range($ip, $range) {
    // Validate input parameters
    if (!validate_ip($ip)) {
        error_log("Invalid IP address: $ip");
        return false;
    }
    
    if (!validate_ip_range($range)) {
        error_log("Invalid IP range: $range");
        return false;
    }
    
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask;
    return ($ip & $mask) == $subnet;
}

/**
 * Resolve DynDNS hostnames to IP addresses
 * @param array $addresses Array of DynDNS hostnames
 * @return array Array of valid IP addresses
 */
function resolve_dyndns_addresses($addresses) {
    $ips = array();
    foreach ($addresses as $address) {
        // Set timeout for DNS resolution
        set_time_limit(10);
        
        $ip = gethostbyname($address);
        if ($ip && $ip !== $address && validate_ip($ip)) {
            $ips[] = $ip;
        } else {
            error_log("Failed to resolve DynDNS hostname: $address");
        }
    }
    return array_unique($ips); // Remove duplicates
}

/**
 * Check if the DynDNS cache should be updated
 * @param string $ip_file Path to the IP cache file
 * @param int $cache_duration Cache duration in seconds
 * @return bool True if cache should be updated, false otherwise
 */
function should_update_dyndns_cache($ip_file, $cache_duration) {
    if (!file_exists($ip_file)) {
        return true;
    }
    
    $file_time = filemtime($ip_file);
    return (time() - $file_time) > $cache_duration;
}

/**
 * Read IP addresses from a file
 * @param string $file Path to the file
 * @return array Array of IP addresses
 */
function read_ip_file($file) {
    if (!file_exists($file)) {
        return array();
    }
    
    $ips = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $valid_ips = array();
    
    foreach ($ips as $ip) {
        $ip = trim($ip);
        if (validate_ip($ip)) {
            $valid_ips[] = $ip;
        }
    }
    
    return $valid_ips;
}

/**
 * Write IP addresses to a file
 * @param string $file Path to the file
 * @param array $ips Array of IP addresses
 * @return bool True on success, false on error
 */
function write_ip_file($file, $ips) {
    $content = implode(PHP_EOL, array_unique($ips)) . PHP_EOL;
    return file_put_contents($file, $content, LOCK_EX) !== false;
}

/**
 * Unified logging function for access events
 * @param string $dir Log directory
 * @param string $ip IP address
 * @param string $script Name of the accessed script
 * @param string $status Access status ('granted' or 'denied')
 */
function log_access($dir, $ip, $script, $status) {
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $log_entry = "[$time] $ip - $script - $status - $user_agent\n";
    $log_file = "$dir/log_$date.txt";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Clean up old log files
 * @param string $log_dir Log directory
 * @param int $retention_days Retention period in days
 */
function cleanup_old_logs($log_dir, $retention_days) {
    if (!is_dir($log_dir)) {
        return;
    }
    
    $cutoff_time = time() - ($retention_days * 24 * 60 * 60);
    $files = glob("$log_dir/log_*.txt");
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff_time) {
            unlink($file);
        }
    }
}

// ============================================================================
// MAIN LOGIC
// ============================================================================

// Directory for logs and temp IP file
$log_dir = 'access_logs';

// Secure directory permissions
if (!is_dir($log_dir)) {
    if (!mkdir($log_dir, 0755, true)) {
        error_log("Failed to create log directory: $log_dir");
        http_response_code(500);
        exit('Internal server error');
    }
}

// Path to the temp IP file
$ip_file = $log_dir . '/allowed_temp_ip.txt';

// Clean up old logs (only once per day)
$cleanup_lock_file = $log_dir . '/cleanup.lock';
if (!file_exists($cleanup_lock_file) || (time() - filemtime($cleanup_lock_file)) > 86400) {
    cleanup_old_logs($log_dir, $log_retention_days);
    touch($cleanup_lock_file);
}

// Validate configuration
if (!validate_ip_range($local_network_range)) {
    error_log("Invalid local network range: $local_network_range");
    http_response_code(500);
    exit('Configuration error');
}

// Validate fixed IPs
$valid_fixed_ips = array();
foreach ($fixed_ips as $ip) {
    if (validate_ip($ip)) {
        $valid_fixed_ips[] = $ip;
    } else {
        error_log("Invalid fixed IP: $ip");
    }
}

// Add fixed IP addresses to the list of allowed IPs
$allowed_ips = $valid_fixed_ips;

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

// Validate user IP
if (!validate_ip($user_ip)) {
    error_log("Invalid user IP: $user_ip");
    http_response_code(400);
    exit('Invalid request');
}

// Check if the visitor's IP address is allowed
$access_granted = in_array($user_ip, $allowed_ips);

// Check if the visitor's IP address is within the local network range
if (!$access_granted) {
    $access_granted = ip_in_range($user_ip, $local_network_range);
}

// If access is denied, check if DynDNS cache should be updated
if (!$access_granted && should_update_dyndns_cache($ip_file, $cache_duration)) {
    // Resolve DynDNS addresses and update the IP file
    $resolved_ips = resolve_dyndns_addresses($dyndns_addresses);
    
    if (!empty($resolved_ips)) {
        write_ip_file($ip_file, $resolved_ips);
        
        // Merge fixed IP addresses and resolved IP addresses
        $allowed_ips = array_merge($valid_fixed_ips, $resolved_ips);
        
        // Check again if the visitor's IP address is allowed
        $access_granted = in_array($user_ip, $allowed_ips);
        
        // Check again if the visitor's IP address is within the local network range
        if (!$access_granted) {
            $access_granted = ip_in_range($user_ip, $local_network_range);
        }
    }
}

if (!$access_granted) {
    $current_script = $_SERVER['SCRIPT_NAME'];
    log_access($log_dir, $user_ip, $current_script, 'denied');
    
    // Set appropriate HTTP headers
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Access denied</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #d32f2f; font-size: 24px; }
        .details { color: #666; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='error'>Access denied!</div>
    <div class='details'>Your IP address ($user_ip) is not authorized to access this resource.</div>
</body>
</html>";
    exit;
}

// Access granted - set appropriate headers
header('X-Access-Control: Granted');
header('X-Client-IP: ' . $user_ip);

// Optional: Debug information (only in development environment)
if (defined('DEBUG') && DEBUG) {
    echo "<!-- Access granted for IP: $user_ip -->\n";
}

// When access is granted
log_access($log_dir, $user_ip, $_SERVER['SCRIPT_NAME'], 'granted');

// Here you can display the content of your website
// echo "Access granted!";
?>
