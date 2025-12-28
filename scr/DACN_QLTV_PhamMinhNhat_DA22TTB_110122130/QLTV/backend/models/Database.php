<?php
// Model: Database connection helper using mysqli with error handling
// Fallback to JSON file if MySQL is not available

declare(strict_types=1);

$DB_HOST = getenv('QLTV_DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('QLTV_DB_USER') ?: 'root';
$DB_PASS = getenv('QLTV_DB_PASS') ?: '';
$DB_NAME = getenv('QLTV_DB_NAME') ?: 'qltv_demo';
$DB_PORT = (int)(getenv('QLTV_DB_PORT') ?: '3306');

// Global variable to store the connection or fallback handler
$GLOBAL_DB_CONNECTION = null;
$GLOBAL_DB_USE_FALLBACK = false;

function get_db_connection() {
    global $GLOBAL_DB_CONNECTION, $GLOBAL_DB_USE_FALLBACK, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT;
    
    if ($GLOBAL_DB_CONNECTION !== null) {
        return $GLOBAL_DB_CONNECTION;
    }
    
    // Try to connect to MySQL only if mysqli extension is available
    if (class_exists('mysqli')) {
        $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
        if ($conn && $conn->connect_errno === 0) {
            $conn->set_charset('utf8mb4');
            $GLOBAL_DB_CONNECTION = $conn;
            $GLOBAL_DB_USE_FALLBACK = false;
            return $conn;
        }
    }
    
    // Fallback to JSON file system
    $GLOBAL_DB_USE_FALLBACK = true;
    $GLOBAL_DB_CONNECTION = new FallbackDatabase();
    return $GLOBAL_DB_CONNECTION;
}

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
        if (stripos($sql, 'SELECT 1') === 0) {
            return true; // Table exists check
        }
        return true;
    }
    
    public function prepare(string $sql) {
        return new FallbackStatement($this->data, $sql, $this->dataPath);
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
    public $num_rows = 0;
    private $dataPath = null;
    
    public function __construct(&$data, string $sql, $dataPath = null) {
        $this->data = $data;
        $this->sql = $sql;
        $this->dataPath = $dataPath;
    }
    
    public function bind_param(string $types, &...$vars) {
        $this->types = $types;
        $this->params = $vars;
        return true;
    }
    
    public function execute(): bool {
        // Parse and execute simple queries
        if (stripos($this->sql, 'SELECT') === 0) {
            if (stripos($this->sql, 'WHERE') !== false) {
                $this->searchUsers();
            } else {
                $this->result = [];
            }
        } elseif (stripos($this->sql, 'INSERT') === 0) {
            $this->insertUser();
        }
        $this->num_rows = is_array($this->result) ? count($this->result) : 0;
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
        // New user ID generation
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
        // Persist changes back to JSON file so registrations survive across requests
        $this->saveData();
    }
    private function saveData(): void {
        $nguoidungFile = ($this->dataPath ?: (__DIR__ . '/../../Database/initdb.d')) . '/QLTV.NGUOIDUNG.json';
        try {
            $dir = dirname($nguoidungFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $json = json_encode($this->data['NGUOIDUNG'] ?? [], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            @file_put_contents($nguoidungFile, $json);
        } catch (Throwable $e) {
            // ignore write errors silently; registration still succeeds in-memory
        }
    }
    
    public function get_result() {
        return new FallbackResult($this->result ?? []);
    }
    
    public function store_result(): void {
        // For compatibility
    }
    
    public function num_rows(): int {
        return count($this->result ?? []);
    }
    
    public function close(): void {
        // For compatibility
    }
}

class FallbackResult {
    private $data;
    private $index = 0;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    public function fetch_assoc() {
        if ($this->index < count($this->data)) {
            $row = [];
            $item = $this->data[$this->index++];
            foreach ($item as $key => $value) {
                // Map 'loai' to 'role' for consistency with repo mapping
                $mappedKey = ($key === 'loai') ? 'role' : $key;
                $row['col_' . $mappedKey] = $value;
            }
            return $row;
        }
        return null;
    }
}
