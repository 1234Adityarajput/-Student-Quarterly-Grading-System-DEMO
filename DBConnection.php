<?php
class DBConnection {
    protected $db;

    function __construct() {
        $host = 'localhost';
        $user = 'root';
        $password = '';
        $dbname = 'sqgs_db';
        $port = 3306;
        $use_ssl = false;

        // 1. Support for full Cloud Service URI (Aiven, Heroku, etc.)
        $conn_url = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: getenv('JAWSDB_URL'));
        if (!empty($conn_url)) {
            // Regex handles special characters in passwords (e.g. #, @, $, etc.) safely
            if (preg_match('/^mysql(?:i)?:\/\/([^:]+):(.*)@([^:\/]+)(?::(\d+))?\/(.+?)(?:\?.*)?$/', $conn_url, $m)) {
                $user = urldecode($m[1]);
                $password = urldecode($m[2]);
                $host = $m[3];
                $port = !empty($m[4]) ? (int)$m[4] : 3306;
                $dbname = $m[5];
                $use_ssl = true;
            } else {
                $parsed = parse_url($conn_url);
                if ($parsed) {
                    if (isset($parsed['host'])) $host = $parsed['host'];
                    if (isset($parsed['port'])) $port = (int)$parsed['port'];
                    if (isset($parsed['user'])) $user = urldecode($parsed['user']);
                    if (isset($parsed['pass'])) $password = urldecode($parsed['pass']);
                    if (isset($parsed['path'])) $dbname = ltrim($parsed['path'], '/');
                    $use_ssl = true;
                }
            }
        }

        // 2. Support all standard environment variable name variants
        // Server / Host
        if (getenv('DB_SERVER')) $host = getenv('DB_SERVER');
        elseif (getenv('DB_HOST')) $host = getenv('DB_HOST');
        elseif (getenv('MYSQL_HOST')) $host = getenv('MYSQL_HOST');
        elseif (getenv('MYSQLHOST')) $host = getenv('MYSQLHOST');

        // Port (supports Aiven 5-digit custom ports e.g. 26284)
        if (getenv('DB_PORT')) $port = (int)getenv('DB_PORT');
        elseif (getenv('MYSQL_PORT')) $port = (int)getenv('MYSQL_PORT');
        elseif (getenv('MYSQLPORT')) $port = (int)getenv('MYSQLPORT');

        // Username
        if (getenv('DB_USERNAME')) $user = getenv('DB_USERNAME');
        elseif (getenv('DB_USER')) $user = getenv('DB_USER');
        elseif (getenv('MYSQL_USER')) $user = getenv('MYSQL_USER');
        elseif (getenv('MYSQLUSER')) $user = getenv('MYSQLUSER');

        // Password
        if (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '') $password = getenv('DB_PASSWORD');
        elseif (getenv('MYSQL_PASSWORD') !== false && getenv('MYSQL_PASSWORD') !== '') $password = getenv('MYSQL_PASSWORD');
        elseif (getenv('MYSQLPASSWORD') !== false && getenv('MYSQLPASSWORD') !== '') $password = getenv('MYSQLPASSWORD');
        elseif (getenv('DB_PASS') !== false && getenv('DB_PASS') !== '') $password = getenv('DB_PASS');
        elseif (getenv('PASSWORD') !== false && getenv('PASSWORD') !== '') $password = getenv('PASSWORD');

        // Database Name
        if (getenv('DB_NAME')) $dbname = getenv('DB_NAME');
        elseif (getenv('MYSQL_DATABASE')) $dbname = getenv('MYSQL_DATABASE');
        elseif (getenv('MYSQLDATABASE')) $dbname = getenv('MYSQLDATABASE');

        // SSL Encryption (enforced by Aiven require_secure_transport=ON)
        $is_remote = ($host !== 'localhost' && $host !== '127.0.0.1');
        if (getenv('DB_SSL') !== false && getenv('DB_SSL') !== '') {
            $use_ssl = filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN);
        } else if ($is_remote) {
            $use_ssl = true;
        }

        // Support BASE_URL
        if (getenv('BASE_URL') && !defined('BASE_URL')) {
            define('BASE_URL', rtrim(getenv('BASE_URL'), '/'));
        }

        // Initialize mysqli instance
        $this->db = mysqli_init();
        if (!$this->db) {
            die("mysqli_init failed: PHP MySQLi extension initialization error.");
        }

        // Set connect timeout to 10 seconds
        $this->db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

        // Connect with SSL flag for cloud databases like Aiven
        if ($use_ssl) {
            $this->db->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL;
            if (defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT')) {
                $flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
            @$this->db->real_connect($host, $user, $password, $dbname, (int)$port, NULL, $flags);
        } else {
            @$this->db->real_connect($host, $user, $password, $dbname, (int)$port);
        }

        // Handle connection failure gracefully
        if ($this->db->connect_errno) {
            $current_script = basename($_SERVER['PHP_SELF'] ?? '');
            // Do not terminate if db_setup.php is loading, let db_setup.php present its UI
            if ($current_script !== 'db_setup.php') {
                $err_msg = "Database Connection Failed (" . $this->db->connect_errno . "): " . $this->db->connect_error;
                die("<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Database Connection Error</title>
    <link rel='stylesheet' href='css/bootstrap.min.css'>
</head>
<body class='bg-light py-5'>
    <div class='container col-md-8 offset-md-2'>
        <div class='card border-danger shadow'>
            <div class='card-header bg-danger text-white'>
                <h4 class='mb-0'>Database Connection Notice</h4>
            </div>
            <div class='card-body'>
                <p class='lead text-danger font-monospace'><strong>" . htmlspecialchars($err_msg) . "</strong></p>
                <hr>
                <p>Database server tak connection establish nahi ho paya. Please verify karein:</p>
                <ul>
                    <li>Render Environment Variables: <code>DB_SERVER</code>, <code>DB_PORT</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, <code>DB_NAME</code>, <code>DB_SSL</code>.</li>
                    <li>Aiven Cloud console me service <strong>RUNNING</strong> state me ho.</li>
                </ul>
                <div class='d-flex gap-2 mt-4'>
                    <a href='db_setup.php' class='btn btn-primary'>Open 1-Click Database Setup (db_setup.php)</a>
                    <a href='login.php' class='btn btn-outline-secondary'>Retry Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>");
            }
        }
    }

    function db_connect() {
        return $this->db;
    }

    function is_connected() {
        return ($this->db && !$this->db->connect_errno);
    }

    function __destruct() {
        if ($this->db && !($this->db->connect_errno)) {
            @$this->db->close();
        }
    }
}

function format_num($number = '', $decimal = '') {
    if (is_numeric($number)) {
        $ex = explode('.', (string)$number);
        $dec_len = isset($ex[1]) ? strlen($ex[1]) : 0;
        if (is_numeric($decimal)) {
            return number_format((float)$number, (int)$decimal);
        } else {
            return number_format((float)$number, $dec_len);
        }
    } else {
        return '0';
    }
}

$database = new DBConnection();
$conn = $database->db_connect();
