<?php
// Database connection helper with MongoDB support
// Falls back to JSON file if MongoDB extension not available

declare(strict_types=1);

// ======== Configuration ========
$MONGO_URI = getenv('QLTV_MONGO_URI');
if (!$MONGO_URI) {
    $candidateFile = __DIR__ . '/../../Database/mongo_uri.txt';
    if (file_exists($candidateFile)) {
        $MONGO_URI = trim(file_get_contents($candidateFile));
    }
}
$MONGO_URI = $MONGO_URI ?: 'mongodb://localhost:27017';
$MONGO_DB_NAME = getenv('QLTV_MONGO_DB') ?: 'qltv_demo';

$DB_HOST = getenv('QLTV_DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('QLTV_DB_USER') ?: 'root';
$DB_PASS = getenv('QLTV_DB_PASS') ?: '';
$DB_NAME = getenv('QLTV_DB_NAME') ?: 'qltv_demo';
$DB_PORT = (int)(getenv('QLTV_DB_PORT') ?: '3306');

// Global variables
$GLOBAL_DB_CONNECTION = null;
$GLOBAL_DB_USE_FALLBACK = false;
$GLOBAL_DB_TYPE = null; // 'mongodb', 'mysql', or 'fallback'

function get_db_connection() {
    global $GLOBAL_DB_CONNECTION, $GLOBAL_DB_USE_FALLBACK, $GLOBAL_DB_TYPE;
    global $MONGO_URI, $MONGO_DB_NAME, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT;
    
    if ($GLOBAL_DB_CONNECTION !== null) {
        return $GLOBAL_DB_CONNECTION;
    }
    
    // Try MongoDB first (if extension available)
    if (extension_loaded('mongodb')) {
        try {
            $manager = new MongoDB\Driver\Manager($MONGO_URI);
            // Test connection with ping
            $command = new MongoDB\Driver\Command(['ping' => 1]);
            $cursor = $manager->executeCommand('admin', $command);
            
            $GLOBAL_DB_CONNECTION = $manager;
            $GLOBAL_DB_TYPE = 'mongodb';
            $GLOBAL_DB_USE_FALLBACK = false;
            return $GLOBAL_DB_CONNECTION;
        } catch (Throwable $e) {
            // MongoDB failed, try MySQL
        }
    }
    
    // Try MySQL/MariaDB
    if (class_exists('mysqli')) {
        $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
        if ($conn && $conn->connect_errno === 0) {
            $conn->set_charset('utf8mb4');
            $GLOBAL_DB_CONNECTION = $conn;
            $GLOBAL_DB_TYPE = 'mysql';
            $GLOBAL_DB_USE_FALLBACK = false;
            return $GLOBAL_DB_CONNECTION;
        }
    }
    
    // Fallback to JSON file system
    $GLOBAL_DB_USE_FALLBACK = true;
    $GLOBAL_DB_TYPE = 'fallback';
    $GLOBAL_DB_CONNECTION = new FallbackDatabase();
    return $GLOBAL_DB_CONNECTION;
}

function get_db_type(): string {
    global $GLOBAL_DB_TYPE;
    return $GLOBAL_DB_TYPE ?? 'unknown';
}

function is_using_mongodb(): bool {
    return get_db_type() === 'mongodb';
}

// ======== Fallback Database Classes ========
class FallbackDatabase {
    private $dataPath;
    private $data = [];
    
    public function __construct() {
        $this->dataPath = __DIR__ . '/../../Database/initdb.d';
        $this->loadData();
    }
    
    private function loadData() {
        $nguoidungFile = $this->dataPath . '/QLTV.NGUOIDUNG.json';
        if (file_exists($nguoidungFile)) {
            $jsonData = file_get_contents($nguoidungFile);
            $this->data['NGUOIDUNG'] = json_decode($jsonData, true) ?: [];
        }
    }
    
    public function query(string $sql) {
        // Simple query handler for SELECT statements
        if (stripos($sql, 'SELECT') === 0) {
            if (stripos($sql, 'WHERE') !== false) {
                // SELECT with WHERE - sẽ không trả dữ liệu từ query(), phải dùng prepare
                return null;
            } else if (stripos($sql, 'FROM NGUOIDUNG') !== false) {
                // SELECT * FROM NGUOIDUNG - trả tất cả users
                return new FallbackResult($this->data['NGUOIDUNG'] ?? []);
            }
        }
        return null;
    }
    
    public function prepare(string $sql) {
        return new FallbackStatement($this->data, $sql);
    }
    
    public function connect_errno(): int {
        return 0;
    }
}

class FallbackStatement {
    private $data;
    private $sql;
    private $params = [];
    private $types = '';
    private $result = null;
    
    public function __construct(&$data, string $sql) {
        $this->data = $data;
        $this->sql = $sql;
    }
    
    public function bind_param(string $types, &...$vars) {
        $this->types = $types;
        $this->params = $vars;
        return true;
    }
    
    public function execute(): bool {
        if (stripos($this->sql, 'SELECT') === 0) {
            if (stripos($this->sql, 'WHERE') !== false) {
                $this->searchUsers();
            } else {
                $this->result = [];
            }
        } elseif (stripos($this->sql, 'INSERT') === 0) {
            $this->insertUser();
        }
        return true;
    }
    
    private function searchUsers() {
        $value = $this->params[0] ?? null;
        $this->result = [];
        
        if (!isset($this->data['NGUOIDUNG']) || !$value) {
            return;
        }
        
        foreach ($this->data['NGUOIDUNG'] as $user) {
            if ($user['username'] === $value || $user['email'] === $value) {
                $this->result = [$user];
                break;
            }
        }
    }
    
    private function insertUser() {
        $newId = 'U' . strtoupper(bin2hex(random_bytes(3)));
        $newUser = [
            'ID' => $newId,
            'username' => $this->params[1] ?? '',
            'password' => $this->params[2] ?? '',
            'ho_ten' => $this->params[3] ?? '',
            'email' => $this->params[4] ?? '',
            'dia_chi' => $this->params[5] ?? '',
            'loai' => $this->params[6] ?? '2'
        ];
        
        if (!isset($this->data['NGUOIDUNG'])) {
            $this->data['NGUOIDUNG'] = [];
        }
        $this->data['NGUOIDUNG'][] = $newUser;
    }
    
    public function get_result() {
        return new FallbackResult($this->result ?? []);
    }
    
    public function store_result(): void {}
    
    public function num_rows(): int {
        return count($this->result ?? []);
    }
    
    public function close(): void {}
}

class FallbackResult {
    private $data;
    private $index = 0;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    public function fetch_assoc() {
        if ($this->index < count($this->data)) {
            return $this->data[$this->index++];
        }
        return null;
    }
}


// Properties for fallback object
if (!function_exists('mysqli_property_get')) {
    FallbackDatabase::class;
}



