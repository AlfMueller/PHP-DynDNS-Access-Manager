<?php
/**
 * Admin Interface for PHP-DynDNS-Access-Manager
 * 
 * Features:
 * - Show access statistics
 * - Manage IP blacklist
 * - Search logs
 * - Show configuration
 * - Manage cache
 */

// ============================================================================
// SECURITY
// ============================================================================

// Load configuration
$config_file = __DIR__ . '/config.php';
if (!file_exists($config_file)) {
    die('Configuration file not found');
}

require_once $config_file;

// Simple admin authentication (for production use a more robust solution)
session_start();

// Check login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
        } else {
            $login_error = 'Invalid credentials';
        }
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Check session timeout
        if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $admin_session_timeout) {
            session_destroy();
        }
        
        // Show login form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login - PHP-DynDNS-Access-Manager</title>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 50px; }
                .login-container { max-width: 400px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .login-title { text-align: center; color: #333; margin-bottom: 30px; }
                .form-group { margin-bottom: 20px; }
                label { display: block; margin-bottom: 5px; color: #666; }
                input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
                .btn { background: #007cba; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
                .btn:hover { background: #005a87; }
                .error { color: #d32f2f; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='login-container'>
                <h2 class='login-title'>Admin Login</h2>
                <?php if (isset($login_error)): ?>
                    <div class='error'><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                <form method='post'>
                    <div class='form-group'>
                        <label for='username'>Username:</label>
                        <input type='text' id='username' name='username' required>
                    </div>
                    <div class='form-group'>
                        <label for='password'>Password:</label>
                        <input type='password' id='password' name='password' required>
                    </div>
                    <button type='submit' class='btn'>Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Check session timeout
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $admin_session_timeout) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Update session time on each request
$_SESSION['admin_login_time'] = time();

// ============================================================================
// CONFIGURATION LOAD
// ============================================================================

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Loads access statistics
 */
function load_statistics($log_dir) {
    $stats = array(
        'total_requests' => 0,
        'granted_requests' => 0,
        'denied_requests' => 0,
        'unique_ips' => array(),
        'top_denied_ips' => array(),
        'recent_activity' => array(),
        'daily_stats' => array()
    );
    
    // Collect data from the last 30 days
    for ($i = 0; $i < 30; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Try different log file formats
        $log_files = array(
            "$log_dir/access_log_$date.txt",
            "$log_dir/log_$date.txt",
            "$log_dir/access_log_$date.log",
            "$log_dir/log_$date.log"
        );
        
        $daily_stats = array('granted' => 0, 'denied' => 0);
        $log_file_found = false;
        
        foreach ($log_files as $log_file) {
            if (file_exists($log_file)) {
                $log_file_found = true;
                $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                foreach ($lines as $line) {
                    // Try different log formats
                    $parts = array();
                    
                    // Format 1: [time] ip - script - status - user_agent - referer - method - uri - data - remote_ip
                    if (preg_match('/^\[([^\]]+)\] ([^-]+) - ([^-]+) - ([^-]+) - (.+)$/', $line, $matches)) {
                        $ip = trim($matches[2]);
                        $status = trim($matches[4]);
                        $parts = array($matches[1], $ip, $matches[3], $status);
                    }
                    // Format 2: time - ip - script
                    elseif (preg_match('/^([^-]+) - ([^-]+) - (.+)$/', $line, $matches)) {
                        $ip = trim($matches[2]);
                        $status = 'unknown'; // Default status for old format
                        $parts = array($matches[1], $ip, $matches[3], $status);
                    }
                    // Format 3: Simple space/tab separated
                    else {
                        $parts = explode(' ', $line);
                        if (count($parts) >= 2) {
                            $ip = trim($parts[1]);
                            $status = 'unknown';
                            $parts = array($parts[0], $ip, isset($parts[2]) ? $parts[2] : 'unknown', $status);
                        }
                    }
                    
                    if (count($parts) >= 4) {
                        $ip = $parts[1];
                        $status = $parts[3];
                        
                        // Validate IP
                        if (filter_var($ip, FILTER_VALIDATE_IP)) {
                            $stats['total_requests']++;
                            $stats['unique_ips'][$ip] = true;
                            
                            if ($status === 'granted') {
                                $stats['granted_requests']++;
                                $daily_stats['granted']++;
                            } else {
                                $stats['denied_requests']++;
                                $daily_stats['denied']++;
                                $stats['top_denied_ips'][$ip] = ($stats['top_denied_ips'][$ip] ?? 0) + 1;
                            }
                            
                            // Current activity (last 24 hours)
                            if ($i === 0) {
                                $stats['recent_activity'][] = $line;
                            }
                        }
                    }
                }
                break; // Found and processed a log file, no need to check others
            }
        }
        
        $stats['daily_stats'][$date] = $daily_stats;
    }
    
    $stats['unique_ips'] = array_keys($stats['unique_ips']);
    arsort($stats['top_denied_ips']);
    $stats['top_denied_ips'] = array_slice($stats['top_denied_ips'], 0, 20, true);
    
    return $stats;
}

/**
 * Loads the IP blacklist
 */
function load_blacklist($log_dir) {
    $blacklist_file = $log_dir . '/ip_blacklist.txt';
    if (file_exists($blacklist_file)) {
        return file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    return array();
}

/**
 * Adds an IP to the blacklist
 */
function add_to_blacklist($ip, $log_dir) {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    
    $blacklist_file = $log_dir . '/ip_blacklist.txt';
    $blacklisted_ips = load_blacklist($log_dir);
    
    if (!in_array($ip, $blacklisted_ips)) {
        $blacklisted_ips[] = $ip;
        return file_put_contents($blacklist_file, implode(PHP_EOL, $blacklisted_ips) . PHP_EOL, LOCK_EX) !== false;
    }
    
    return true;
}

/**
 * Removes an IP from the blacklist
 */
function remove_from_blacklist($ip, $log_dir) {
    $blacklist_file = $log_dir . '/ip_blacklist.txt';
    $blacklisted_ips = load_blacklist($log_dir);
    
    $key = array_search($ip, $blacklisted_ips);
    if ($key !== false) {
        unset($blacklisted_ips[$key]);
        return file_put_contents($blacklist_file, implode(PHP_EOL, $blacklisted_ips) . PHP_EOL, LOCK_EX) !== false;
    }
    
    return true;
}

function log_denied_ip($dir, $ip, $script) {
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $status = 'denied';
    $log_entry = "[$time] $ip - $script - $status - $user_agent\n";
    $log_file = "$dir/log_$date.txt";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

function log_granted_ip($dir, $ip, $script) {
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $status = 'granted';
    $log_entry = "[$time] $ip - $script - $status - $user_agent\n";
    $log_file = "$dir/log_$date.txt";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ============================================================================
// HANDLE ACTIONS
// ============================================================================

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_blacklist':
                if (isset($_POST['ip']) && add_to_blacklist($_POST['ip'], $log_dir)) {
                    $message = 'IP added to blacklist';
                } else {
                    $error = 'Error adding IP to blacklist';
                }
                break;
                
            case 'remove_blacklist':
                if (isset($_POST['ip']) && remove_from_blacklist($_POST['ip'], $log_dir)) {
                    $message = 'IP removed from blacklist';
                } else {
                    $error = 'Error removing IP from blacklist';
                }
                break;
                
            case 'clear_cache':
                $ip_file = $log_dir . '/allowed_temp_ip.txt';
                if (file_exists($ip_file)) {
                    unlink($ip_file);
                    $message = 'Cache cleared';
                } else {
                    $message = 'Cache was already empty';
                }
                break;
                
            case 'logout':
                session_destroy();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
        }
    }
}

// ============================================================================
// LOAD DATA
// ============================================================================

$stats = load_statistics($log_dir);
$blacklisted_ips = load_blacklist($log_dir);

// ============================================================================
// HTML OUTPUT
// ============================================================================

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - PHP-DynDNS-Access-Manager</title>
    <meta charset='utf-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #333; }
        .header .actions { margin-top: 10px; }
        .btn { background: #007cba; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 10px; }
        .btn:hover { background: #005a87; }
        .btn-danger { background: #d32f2f; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-success { background: #388e3c; }
        .btn-success:hover { background: #2e7d32; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; color: #333; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-box { background: #f8f9fa; padding: 20px; border-radius: 4px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007cba; }
        .stat-label { color: #666; margin-top: 5px; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #666; }
        input[type="text"] { width: 200px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .two-column { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .log-entry { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 4px; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>PHP-DynDNS-Access-Manager - Admin</h1>
            <div class='actions'>
                <a href='<?php echo $_SERVER['PHP_SELF']; ?>' class='btn'>Refresh</a>
                <form method='post' style='display: inline;'>
                    <input type='hidden' name='action' value='clear_cache'>
                    <button type='submit' class='btn btn-success'>Clear cache</button>
                </form>
                <form method='post' style='display: inline;'>
                    <input type='hidden' name='action' value='logout'>
                    <button type='submit' class='btn btn-danger'>Logout</button>
                </form>
            </div>
        </div>

        <?php if ($message): ?>
            <div class='message'><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class='error'><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class='card'>
            <h2>Access statistics (last 30 days)</h2>
            <div class='stats-grid'>
                <div class='stat-box'>
                    <div class='stat-number'><?php echo number_format($stats['total_requests']); ?></div>
                    <div class='stat-label'>Total requests</div>
                </div>
                <div class='stat-box'>
                    <div class='stat-number'><?php echo number_format($stats['granted_requests']); ?></div>
                    <div class='stat-label'>Granted requests</div>
                </div>
                <div class='stat-box'>
                    <div class='stat-number'><?php echo number_format($stats['denied_requests']); ?></div>
                    <div class='stat-label'>Denied requests</div>
                </div>
                <div class='stat-box'>
                    <div class='stat-number'><?php echo number_format(count($stats['unique_ips'])); ?></div>
                    <div class='stat-label'>Unique IPs</div>
                </div>
            </div>
        </div>

        <div class='two-column'>
            <!-- Top denied IPs -->
            <div class='card'>
                <h2>Top denied IPs</h2>
                <table>
                    <tr>
                        <th>IP address</th>
                        <th>Count</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($stats['top_denied_ips'] as $ip => $count): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ip); ?></td>
                            <td><?php echo $count; ?></td>
                            <td>
                                <?php if (in_array($ip, $blacklisted_ips)): ?>
                                    <form method='post' style='display: inline;'>
                                        <input type='hidden' name='action' value='remove_blacklist'>
                                        <input type='hidden' name='ip' value='<?php echo htmlspecialchars($ip); ?>'>
                                        <button type='submit' class='btn btn-success' style='padding: 4px 8px; font-size: 12px;'>Remove from blacklist</button>
                                    </form>
                                <?php else: ?>
                                    <form method='post' style='display: inline;'>
                                        <input type='hidden' name='action' value='add_blacklist'>
                                        <input type='hidden' name='ip' value='<?php echo htmlspecialchars($ip); ?>'>
                                        <button type='submit' class='btn btn-danger' style='padding: 4px 8px; font-size: 12px;'>Add to blacklist</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Manage blacklist -->
            <div class='card'>
                <h2>Manage IP blacklist</h2>
                <form method='post'>
                    <input type='hidden' name='action' value='add_blacklist'>
                    <div class='form-group'>
                        <label for='ip'>Add IP address:</label>
                        <input type='text' id='ip' name='ip' placeholder='192.168.1.1' required>
                        <button type='submit' class='btn btn-danger'>Add to blacklist</button>
                    </div>
                </form>

                <h3>Current blacklist (<?php echo count($blacklisted_ips); ?> IPs)</h3>
                <table>
                    <tr>
                        <th>IP address</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($blacklisted_ips as $ip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ip); ?></td>
                            <td>
                                <form method='post' style='display: inline;'>
                                    <input type='hidden' name='action' value='remove_blacklist'>
                                    <input type='hidden' name='ip' value='<?php echo htmlspecialchars($ip); ?>'>
                                    <button type='submit' class='btn btn-success' style='padding: 4px 8px; font-size: 12px;'>Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Configuration -->
        <div class='card'>
            <h2>Current configuration</h2>
            <table>
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>DynDNS hostnames</td>
                    <td><?php echo implode(', ', $dyndns_addresses); ?></td>
                </tr>
                <tr>
                    <td>Fixed IPs</td>
                    <td><?php echo implode(', ', $fixed_ips); ?></td>
                </tr>
                <tr>
                    <td>Local network</td>
                    <td><?php echo $local_network_range; ?></td>
                </tr>
                <tr>
                    <td>Cache duration</td>
                    <td><?php echo $cache_duration; ?> seconds</td>
                </tr>
                <tr>
                    <td>Log retention</td>
                    <td><?php echo $log_retention_days; ?> days</td>
                </tr>
                <tr>
                    <td>Rate limiting</td>
                    <td><?php echo $rate_limit_attempts > 0 ? $rate_limit_attempts . ' attempts/hour' : 'Disabled'; ?></td>
                </tr>
            </table>
        </div>

        <!-- Recent activity -->
        <div class='card'>
            <h2>Recent activity (today)</h2>
            <?php if (empty($stats['recent_activity'])): ?>
                <p>No activity today.</p>
            <?php else: ?>
                <?php foreach (array_slice($stats['recent_activity'], -10) as $activity): ?>
                    <div class='log-entry'><?php echo htmlspecialchars($activity); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 