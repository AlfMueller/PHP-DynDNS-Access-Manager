<?php
/**
 * Configuration file for PHP-DynDNS-Access-Manager
 * 
 * This file contains all configurable settings for the
 * access control system. Change only the values in this area.
 */

// ============================================================================
// DYNDNS-CONFIGURATION
// ============================================================================

// DynDNS hostnames to be resolved
// Enter your DynDNS hostnames here
$dyndns_addresses = array(
    'xxx.synology.me',
    'beta.qnet.com',
    'gamma.dyndns.org'
);

// ============================================================================
// IP-CONFIGURATION
// ============================================================================

// Fixed IPs that always have access
// Enter your static IP addresses here
$fixed_ips = array(
    '123.456.789.000',
    '111.222.333.444'
);

// Local network range in CIDR notation
// Example: '192.168.1.0/24' for the entire 192.168.1.x network
$local_network_range = '10.10.55.0/24';

// ============================================================================
// CACHE- AND PERFORMANCE-SETTINGS
// ============================================================================

// Cache duration for DynDNS resolutions (in seconds)
// Recommended: 300 (5 minutes) to 1800 (30 minutes)
$cache_duration = 300;

// Log retention period (in days)
// Old log files are automatically deleted after this time
$log_retention_days = 30;

// ============================================================================
// SECURITY SETTINGS
// ============================================================================

// Debug mode (only for development environment)
// Set this to false in production environment
$debug_mode = false;

// Enable extended logging
// Logs additional information like User-Agent
$extended_logging = true;

// Enable rate limiting (number of attempts per IP per hour)
// 0 = Disabled, > 0 = Number of allowed attempts
$rate_limit_attempts = 10;

// ============================================================================
// USER INTERFACE
// ============================================================================

// Language for error messages
// Available languages: 'de', 'en'
$language = 'de';

// Customizable error messages
$error_messages = array(
    'de' => array(
        'access_denied' => 'Access denied!',
        'ip_not_allowed' => 'Your IP address (%s) is not authorized to access this resource.',
        'contact_admin' => 'Please contact the administrator if you have any questions.',
        'internal_error' => 'Internal server error',
        'config_error' => 'Configuration error',
        'invalid_request' => 'Invalid request'
    ),
    'en' => array(
        'access_denied' => 'Access denied!',
        'ip_not_allowed' => 'Your IP address (%s) is not authorized to access this resource.',
        'contact_admin' => 'Please contact the administrator if you have any questions.',
        'internal_error' => 'Internal server error',
        'config_error' => 'Configuration error',
        'invalid_request' => 'Invalid request'
    )
);

// ============================================================================
// ADVANCED SETTINGS (Only for experts)
// ============================================================================

// DNS timeout in seconds
$dns_timeout = 10;

// Maximum number of DynDNS hostnames
$max_dyndns_hostnames = 50;

// Maximum number of fixed IPs
$max_fixed_ips = 100;

// File locking for simultaneous access
$enable_file_locking = true;

// Automatic cleanup of IP cache file
// Deletes invalid IPs from the cache file
$auto_cleanup_cache = true;

// ============================================================================
// ADMIN INTERFACE SETTINGS
// ============================================================================

// Admin interface credentials
// Change these credentials for security!
$admin_username = 'admin';
$admin_password = 'your_secure_password_here';

// Admin interface settings
$admin_session_timeout = 3600; // Session timeout in seconds (1 hour)
$admin_max_login_attempts = 5; // Maximum login attempts before temporary lockout
$admin_lockout_duration = 900; // Lockout duration in seconds (15 minutes)

// ============================================================================
// CONFIGURATION VALIDATION
// ============================================================================

// Check critical settings
if ($cache_duration < 60) {
    error_log("Warning: Cache duration is very short: $cache_duration seconds");
}

if ($log_retention_days < 1) {
    error_log("Warning: Log retention is very short: $log_retention_days days");
}

if (count($dyndns_addresses) > $max_dyndns_hostnames) {
    error_log("Error: Too many DynDNS hostnames configured");
    $dyndns_addresses = array_slice($dyndns_addresses, 0, $max_dyndns_hostnames);
}

if (count($fixed_ips) > $max_fixed_ips) {
    error_log("Error: Too many fixed IPs configured");
    $fixed_ips = array_slice($fixed_ips, 0, $max_fixed_ips);
}

// Ensure Debug mode is disabled in production
if ($debug_mode && $_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    error_log("Warning: Debug mode is enabled in production environment");
    $debug_mode = false;
}

// Define Debug constant for main file
define('DEBUG', $debug_mode);

?> 