<?php
/**
 * 1-Click Aiven Cloud MySQL Database Setup & Health Check Tool
 * Student Quarterly Grading System (SQGS)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to extract DB config from ENV or POST
function get_db_credentials() {
    $creds = [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'root',
        'pass' => '',
        'name' => 'sqgs_db',
        'ssl'  => false
    ];

    $conn_url = getenv('DATABASE_URL') ?: (getenv('MYSQL_URL') ?: getenv('JAWSDB_URL'));
    if (!empty($conn_url)) {
        if (preg_match('/^mysql(?:i)?:\/\/([^:]+):(.*)@([^:\/]+)(?::(\d+))?\/(.+?)(?:\?.*)?$/', $conn_url, $m)) {
            $creds['user'] = urldecode($m[1]);
            $creds['pass'] = urldecode($m[2]);
            $creds['host'] = $m[3];
            $creds['port'] = !empty($m[4]) ? (int)$m[4] : 3306;
            $creds['name'] = $m[5];
            $creds['ssl']  = true;
        } else {
            $parsed = parse_url($conn_url);
            if ($parsed) {
                if (isset($parsed['host'])) $creds['host'] = $parsed['host'];
                if (isset($parsed['port'])) $creds['port'] = (int)$parsed['port'];
                if (isset($parsed['user'])) $creds['user'] = urldecode($parsed['user']);
                if (isset($parsed['pass'])) $creds['pass'] = urldecode($parsed['pass']);
                if (isset($parsed['path'])) $creds['name'] = ltrim($parsed['path'], '/');
                $creds['ssl'] = true;
            }
        }
    }

    if (getenv('DB_SERVER')) $creds['host'] = getenv('DB_SERVER');
    elseif (getenv('DB_HOST')) $creds['host'] = getenv('DB_HOST');
    elseif (getenv('MYSQL_HOST')) $creds['host'] = getenv('MYSQL_HOST');
    elseif (getenv('MYSQLHOST')) $creds['host'] = getenv('MYSQLHOST');

    if (getenv('DB_PORT')) $creds['port'] = (int)getenv('DB_PORT');
    elseif (getenv('MYSQL_PORT')) $creds['port'] = (int)getenv('MYSQL_PORT');
    elseif (getenv('MYSQLPORT')) $creds['port'] = (int)getenv('MYSQLPORT');

    if (getenv('DB_USERNAME')) $creds['user'] = getenv('DB_USERNAME');
    elseif (getenv('DB_USER')) $creds['user'] = getenv('DB_USER');
    elseif (getenv('MYSQL_USER')) $creds['user'] = getenv('MYSQL_USER');
    elseif (getenv('MYSQLUSER')) $creds['user'] = getenv('MYSQLUSER');

    if (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '') $creds['pass'] = getenv('DB_PASSWORD');
    elseif (getenv('MYSQL_PASSWORD') !== false && getenv('MYSQL_PASSWORD') !== '') $creds['pass'] = getenv('MYSQL_PASSWORD');
    elseif (getenv('MYSQLPASSWORD') !== false && getenv('MYSQLPASSWORD') !== '') $creds['pass'] = getenv('MYSQLPASSWORD');
    elseif (getenv('DB_PASS') !== false && getenv('DB_PASS') !== '') $creds['pass'] = getenv('DB_PASS');

    if (getenv('DB_NAME')) $creds['name'] = getenv('DB_NAME');
    elseif (getenv('MYSQL_DATABASE')) $creds['name'] = getenv('MYSQL_DATABASE');
    elseif (getenv('MYSQLDATABASE')) $creds['name'] = getenv('MYSQLDATABASE');

    $is_remote = ($creds['host'] !== 'localhost' && $creds['host'] !== '127.0.0.1');
    if (getenv('DB_SSL') !== false && getenv('DB_SSL') !== '') {
        $creds['ssl'] = filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN);
    } elseif ($is_remote) {
        $creds['ssl'] = true;
    }

    // Allow user override from POST form if provided
    if (isset($_POST['override_db']) && $_POST['override_db'] == '1') {
        if (!empty($_POST['host'])) $creds['host'] = trim($_POST['host']);
        if (!empty($_POST['port'])) $creds['port'] = (int)$_POST['port'];
        if (!empty($_POST['user'])) $creds['user'] = trim($_POST['user']);
        if (isset($_POST['pass']))  $creds['pass'] = $_POST['pass'];
        if (!empty($_POST['name'])) $creds['name'] = trim($_POST['name']);
        $creds['ssl'] = isset($_POST['ssl']) && $_POST['ssl'] == '1';
    }

    return $creds;
}

// Function to establish connection
function connect_db($creds) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = mysqli_init();
    if (!$mysqli) {
        return [null, "mysqli_init failed. Check PHP MySQLi extension."];
    }
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);

    $connected = false;
    $caught_err = '';
    try {
        if ($creds['ssl']) {
            $mysqli->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL;
            if (defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT')) {
                $flags |= MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
            }
            $connected = @$mysqli->real_connect($creds['host'], $creds['user'], $creds['pass'], $creds['name'], (int)$creds['port'], NULL, $flags);
        } else {
            $connected = @$mysqli->real_connect($creds['host'], $creds['user'], $creds['pass'], $creds['name'], (int)$creds['port']);
        }
    } catch (Throwable $e) {
        $connected = false;
        $caught_err = $e->getMessage();
    }

    if (!$connected || $mysqli->connect_errno) {
        $err_msg = $mysqli->connect_error ?: ($caught_err ?: "Connection refused or host unreachable ({$creds['host']}:{$creds['port']})");
        return [null, "Error (" . ($mysqli->connect_errno ?: 2002) . "): " . $err_msg];
    }
    return [$mysqli, ""];
}

$creds = get_db_credentials();
list($mysqli, $conn_error) = connect_db($creds);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$feedback = [];
$feedback_type = '';

$required_tables = [
    'admin_list',
    'assessment_list',
    'class_list',
    'component_subject_percentage',
    'grading_components',
    'mark_list',
    'student_list',
    'subjects',
    'transmutation_table'
];

// Handle Import Action
if ($action === 'import' && $mysqli) {
    $sql_file = __DIR__ . '/database/sqgs_db.sql';
    if (!file_exists($sql_file)) {
        $feedback[] = "SQL dump file not found at: " . htmlspecialchars($sql_file);
        $feedback_type = 'danger';
    } else {
        $sql_content = file_get_contents($sql_file);
        if ($sql_content === false || empty($sql_content)) {
            $feedback[] = "Could not read or empty SQL file.";
            $feedback_type = 'danger';
        } else {
            // Clean parsing
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");
            $mysqli->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

            $lines = explode("\n", $sql_content);
            $statements = [];
            $temp_stmt = '';
            $in_comment = false;

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed)) continue;

                if (substr($trimmed, 0, 2) === '/*' && substr($trimmed, -2) !== '*/') {
                    $in_comment = true;
                    continue;
                }
                if ($in_comment) {
                    if (strpos($trimmed, '*/') !== false) {
                        $in_comment = false;
                    }
                    continue;
                }
                if (substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
                    continue;
                }

                $temp_stmt .= " " . $line;
                if (substr($trimmed, -1) === ';') {
                    $statements[] = trim($temp_stmt);
                    $temp_stmt = '';
                }
            }

            $success_count = 0;
            $fail_count = 0;
            foreach ($statements as $stmt) {
                if (empty($stmt)) continue;
                if ($mysqli->query($stmt)) {
                    $success_count++;
                } else {
                    $fail_count++;
                }
            }

            // Restore foreign key checks
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

            // Verify or ensure default admin user exists
            $admin_check = $mysqli->query("SELECT * FROM `admin_list` WHERE `username` = 'admin'");
            if (!$admin_check || $admin_check->num_rows == 0) {
                $pwd = md5('admin123');
                $mysqli->query("INSERT INTO `admin_list` (`fullname`, `username`, `password`, `status`) VALUES ('Administrator', 'admin', '{$pwd}', 1)");
            }

            $feedback[] = "Database Import Completed! Executed {$success_count} queries successfully." . ($fail_count > 0 ? " ({$fail_count} skipped/notice)" : "");
            $feedback_type = 'success';
        }
    }
}

// Inspect current database tables
$existing_tables = [];
$ssl_cipher = '';
$server_version = '';
$admin_status = 'Not Found';

if ($mysqli) {
    $server_version = $mysqli->server_info;
    $ssl_res = $mysqli->query("SHOW STATUS LIKE 'Ssl_cipher'");
    if ($ssl_res && $row = $ssl_res->fetch_assoc()) {
        $ssl_cipher = !empty($row['Value']) ? $row['Value'] : 'None (Plaintext)';
    }

    $t_res = $mysqli->query("SHOW TABLES");
    if ($t_res) {
        while ($row = $t_res->fetch_array()) {
            $tname = $row[0];
            $count_res = $mysqli->query("SELECT count(*) FROM `{$tname}`");
            $rows_count = ($count_res && $cr = $count_res->fetch_array()) ? $cr[0] : 0;
            $existing_tables[$tname] = $rows_count;
        }
    }

    if (isset($existing_tables['admin_list'])) {
        $adm_res = $mysqli->query("SELECT * FROM `admin_list` WHERE `username` = 'admin'");
        if ($adm_res && $adm_res->num_rows > 0) {
            $adm_data = $adm_res->fetch_assoc();
            $admin_status = "Ready (User: admin / Name: " . htmlspecialchars($adm_data['fullname']) . ")";
        }
    }
}

$all_tables_ready = (count(array_intersect($required_tables, array_keys($existing_tables))) === count($required_tables));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aiven MySQL Database Setup | SQGS</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="Font-Awesome-master/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: system-ui, -apple-system, sans-serif; }
        .card { border-radius: 10px; border: 1px solid #e2e8f0; }
        .badge-table { font-size: 0.85rem; padding: 6px 12px; margin: 3px; }
        .status-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-green { background-color: #22c55e; box-shadow: 0 0 8px #22c55e; }
        .status-red { background-color: #ef4444; box-shadow: 0 0 8px #ef4444; }
    </style>
</head>
<body class="py-4">
<div class="container col-lg-9 col-md-11">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="mb-1 text-primary fw-bold"><i class="fa fa-database"></i> Aiven Cloud Database Setup</h2>
            <p class="text-muted mb-0">Student Quarterly Grading System &mdash; 1-Click Database Setup & Health Check</p>
        </div>
        <div>
            <a href="login.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-<?php echo $feedback_type; ?> alert-dismissible fade show shadow-sm" role="alert">
            <h5 class="alert-heading"><i class="fa fa-info-circle"></i> Result:</h5>
            <?php foreach ($feedback as $fb): ?>
                <div><?php echo htmlspecialchars($fb); ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column: Connection Status & Actions -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <?php if ($mysqli): ?>
                            <span class="status-dot status-green"></span> Connected to Aiven MySQL
                        <?php else: ?>
                            <span class="status-dot status-red"></span> Connection Failed
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($mysqli): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light" style="width: 35%;">Database Host</th>
                                        <td class="font-monospace"><?php echo htmlspecialchars($creds['host']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Port</th>
                                        <td><span class="badge bg-secondary"><?php echo (int)$creds['port']; ?></span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Database Name</th>
                                        <td class="fw-bold text-success"><?php echo htmlspecialchars($creds['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">DB User</th>
                                        <td><?php echo htmlspecialchars($creds['user']); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">SSL Encryption</th>
                                        <td>
                                            <?php if ($creds['ssl']): ?>
                                                <span class="badge bg-success"><i class="fa fa-lock"></i> SSL Active</span>
                                                <small class="text-muted d-block font-monospace mt-1"><?php echo htmlspecialchars($ssl_cipher ?: 'Enabled'); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fa fa-unlock"></i> Plaintext</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Server Version</th>
                                        <td><?php echo htmlspecialchars($server_version); ?></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light">Admin Account</th>
                                        <td>
                                            <?php if ($admin_status !== 'Not Found'): ?>
                                                <span class="badge bg-success"><i class="fa fa-check"></i> <?php echo $admin_status; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fa fa-times"></i> Missing</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-3">
                            <h6 class="fw-bold"><i class="fa fa-exclamation-triangle"></i> Connection Error:</h6>
                            <p class="font-monospace small mb-0"><?php echo htmlspecialchars($conn_error); ?></p>
                        </div>
                        <p class="small text-muted">
                            Aiven Cloud MySQL requires SSL. Make sure your <strong>DB_SERVER</strong>, <strong>DB_PORT</strong>, 
                            <strong>DB_USERNAME</strong>, <strong>DB_PASSWORD</strong>, and <strong>DB_NAME</strong> environment variables are correctly configured.
                        </p>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- Actions -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($mysqli): ?>
                            <form method="POST" action="db_setup.php" onsubmit="return confirm('Kya aap database me tables aur default data import karna chahte hain?');">
                                <input type="hidden" name="action" value="import">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-file-import"></i> 1-Click Import Database (sqgs_db.sql)
                                </button>
                            </form>
                            <?php if ($all_tables_ready && $admin_status !== 'Not Found'): ?>
                                <a href="login.php" class="btn btn-success btn-lg">
                                    <i class="fa fa-sign-in-alt"></i> Go to Live Login
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="db_setup.php" class="btn btn-warning">
                                <i class="fa fa-sync"></i> Re-test Connection
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Table Status Overview -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fa fa-th-list"></i> Database Tables (<?php echo count($existing_tables); ?> / <?php echo count($required_tables); ?>)</h5>
                    <?php if ($all_tables_ready): ?>
                        <span class="badge bg-success">All Tables Ready</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Tables Incomplete</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap">
                        <?php foreach ($required_tables as $req_t): ?>
                            <?php if (isset($existing_tables[$req_t])): ?>
                                <span class="badge bg-success badge-table">
                                    <i class="fa fa-check-circle"></i> <?php echo $req_t; ?> (<?php echo $existing_tables[$req_t]; ?> rows)
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger badge-table">
                                    <i class="fa fa-times-circle"></i> <?php echo $req_t; ?> (Missing)
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Credentials & Manual Override Form -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa fa-key text-warning"></i> Default Admin Credentials</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-0">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Admin Username</span>
                            <strong class="font-monospace text-primary">admin</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Admin Password</span>
                            <strong class="font-monospace text-danger">admin123</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Login URL</span>
                            <a href="login.php" class="text-decoration-none">login.php</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Manual Test / Override Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa fa-sliders-h"></i> Manual Connection Override / Test</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="db_setup.php">
                        <input type="hidden" name="override_db" value="1">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">DB Host / Server</label>
                            <input type="text" name="host" class="form-control form-control-sm" value="<?php echo htmlspecialchars($creds['host']); ?>" required>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Port</label>
                                <input type="number" name="port" class="form-control form-control-sm" value="<?php echo (int)$creds['port']; ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">DB Name</label>
                                <input type="text" name="name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($creds['name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">DB User</label>
                            <input type="text" name="user" class="form-control form-control-sm" value="<?php echo htmlspecialchars($creds['user']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">DB Password</label>
                            <input type="password" name="pass" class="form-control form-control-sm" value="<?php echo htmlspecialchars($creds['pass']); ?>">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="ssl" value="1" id="sslCheck" <?php echo $creds['ssl'] ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="sslCheck">
                                Enable SSL Encryption (Required for Aiven)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fa fa-plug"></i> Test Connection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="js/jquery-3.6.0.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
