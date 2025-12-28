<?php
declare(strict_types=1);

/**
 * AuthController - Handle login/register requests
 * Supports: MongoDB (if available) → MySQL → JSON Fallback
 * 
 * Note: MongoDB type hints are wrapped in @psalm-suppress comments
 * because the MongoDB PHP extension is optional and not always installed.
 * At runtime, if extension_loaded('mongodb') is true, these types will be available.
 * 
 * @psalm-suppress UndefinedClass MongoDB types only defined when extension is loaded
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Database.php';

// Nếu extension MongoDB có sẵn, xử lý nhanh bằng MongoDB (ưu tiên)
if (extension_loaded('mongodb')) {
    // Try environment variable first; if not set, try reading the project Database file.
    $mongoUri = getenv('QLTV_MONGO_URI');
    if (!$mongoUri) {
        $candidateFile = __DIR__ . '/../../Database/mongo_uri.txt';
        if (file_exists($candidateFile)) {
            $read = trim(@file_get_contents($candidateFile));
            if ($read !== '') {
                $mongoUri = $read;
            }
        }
    }
    // Fallback to project default if none found
    $mongoUri = $mongoUri ?: 'mongodb://localhost:27017';
    $mongoDbName = getenv('QLTV_MONGO_DB') ?: 'qltv_demo';
    try {
        /** @psalm-suppress UndefinedClass */
        $mongoManager = new MongoDB\Driver\Manager($mongoUri);
    } catch (Throwable $e) {
        // không thể kết nối Mongo — sẽ rơi về MySQL/fallback
        $mongoManager = null;
    }

    if ($mongoManager) {
        // Xử lý action trực tiếp với MongoDB
        $action = $_POST['action'] ?? '';

        if ($action === 'login') {
            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($identifier === '' || $password === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
                exit;
            }

            $filter = ['$or' => [['username' => $identifier], ['email' => $identifier]]];
            /** @psalm-suppress UndefinedClass */
            $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
            try {
                $cursor = $mongoManager->executeQuery($mongoDbName . '.NGUOIDUNG', $query);
                $docs = [];
                if (is_object($cursor) && method_exists($cursor, 'toArray')) {
                    $docs = $cursor->toArray();
                }
                $doc = $docs[0] ?? null;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi kết nối MongoDB']);
                exit;
            }

            if (!$doc) {
                echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng']);
                exit;
            }

            $stored = (array)$doc;
            $storedPassword = $stored['password'] ?? '';
            $ok = false;
            if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
                $ok = true;
            } elseif ($storedPassword !== '' && hash_equals($storedPassword, $password)) {
                // mật khẩu lưu thẳng
                $ok = true;
            }

            if (!$ok) {
                echo json_encode(['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng']);
                exit;
            }

            // Thiết lập session user
            $user = [
                'id' => $stored['ID'] ?? null,
                'username' => $stored['username'] ?? '',
                'full_name' => $stored['ho_ten'] ?? '',
                'email' => $stored['email'] ?? '',
                'address' => $stored['dia_chi'] ?? '',
                'role' => (string)($stored['loai'] ?? ''),
            ];
            $_SESSION['user'] = $user;

            echo json_encode(['success' => true, 'message' => 'Đăng nhập thành công']);
            exit;
        }

        if ($action === 'register') {
            $fullName = trim($_POST['full_name'] ?? '');
            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if ($fullName === '' || $identifier === '' || $password === '' || $passwordConfirm === '' || $email === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
                exit;
            }
            if ($password !== $passwordConfirm) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không trùng khớp']);
                exit;
            }
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Định dạng email không hợp lệ']);
                exit;
            }

            // Kiểm tra tồn tại
            $filter = ['$or' => [['email' => $email], ['username' => $identifier]]];
            /** @psalm-suppress UndefinedClass */
            $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
            try {
                $cursor = $mongoManager->executeQuery($mongoDbName . '.NGUOIDUNG', $query);
                $docs = [];
                if (is_object($cursor) && method_exists($cursor, 'toArray')) {
                    $docs = $cursor->toArray();
                }
                if (!empty($docs)) {
                    echo json_encode(['success' => false, 'message' => 'Email hoặc tên đăng nhập đã tồn tại']);
                    exit;
                }
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi MongoDB khi kiểm tra tồn tại']);
                exit;
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            /** @psalm-suppress UndefinedClass */
            $bulk = new MongoDB\Driver\BulkWrite();
            $doc = [
                'ID' => 'U' . strtoupper(bin2hex(random_bytes(3))),
                'username' => $identifier,
                'password' => $hashed,
                'ho_ten' => $fullName,
                'email' => $email,
                'dia_chi' => $address,
                'loai' => 2,
            ];
            $bulk->insert($doc);
            try {
                $mongoManager->executeBulkWrite($mongoDbName . '.NGUOIDUNG', $bulk);
                echo json_encode(['success' => true, 'message' => 'Đăng ký thành công! Vui lòng đăng nhập lại']);
                exit;
            } catch (Throwable $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi MongoDB khi lưu tài khoản']);
                exit;
            }
        }
        // Nếu không phải action hợp lệ, rơi xuống luồng SQL phía dưới
    }
}

// Hàm hỗ trợ
function resolve_user_repository($conn): array {
    $candidates = [
        'NGUOIDUNG' => [
            'columns' => [
                'id' => 'ID',
                'username' => 'username',
                'password' => 'password',
                'full_name' => 'ho_ten',
                'email' => 'email',
                'address' => 'dia_chi',
                'role' => 'loai',
            ],
            'identifiers' => ['username', 'email'],
            'default_role' => '2',
        ],
        'users' => [
            'columns' => [
                'id' => 'id',
                'username' => 'email',
                'password' => 'password_hash',
                'full_name' => 'full_name',
                'email' => 'email',
                'address' => null,
                'role' => 'role',
            ],
            'identifiers' => ['email'],
            'default_role' => 'user',
        ],
    ];

    foreach ($candidates as $table => $config) {
        $probeSql = 'SELECT 1 FROM ' . wrap_identifier($table) . ' LIMIT 1';
        if ($conn->query($probeSql) !== false) {
            $config['table'] = $table;
            return $config;
        }
    }

    throw new RuntimeException('Chưa cấu hình bảng người dùng trong cơ sở dữ liệu');
}

function wrap_identifier(string $value): string {
    return '`' . str_replace('`', '``', $value) . '`';
}

function alias_name(string $alias): string {
    return 'col_' . $alias;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Hành động không hợp lệ'];

try {
    $conn = get_db_connection();
    $userRepo = resolve_user_repository($conn);

    if ($action === 'login') {
        $response = handle_login($conn, $userRepo);
    } elseif ($action === 'register') {
        $response = handle_register($conn, $userRepo);
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()];
}

echo json_encode($response);
exit;

function handle_login($conn, array $repo): array {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin'];
    }

    $user = find_user_by_identifier($conn, $repo, $identifier);

    // If user not found in DB, try fallback to saved accounts in frontend/views/Taikhoan.html
    if (!$user) {
        try {
            require_once __DIR__ . '/../models/AccountRepository.php';
            $repoAccounts = new AccountRepository();
            $saved = $repoAccounts->readAccounts();
            if (is_array($saved) && count($saved) > 0) {
                foreach ($saved as $acc) {
                    $uname = $acc['username'] ?? '';
                    $pwd = $acc['password'] ?? '';
                    if ($uname !== '' && $uname === $identifier && $pwd !== '') {
                        // Support hashed passwords (password_verify) and legacy plain equality
                        $matched = false;
                        if (password_verify($password, $pwd)) {
                            $matched = true;
                        } elseif (hash_equals($pwd, $password)) {
                            $matched = true;
                        }

                        if ($matched) {
                            // matched saved account; construct user-like array
                            // Use loai from account if available, otherwise map by username
                            $role = $acc['loai'] ?? '2';
                            if (empty($role)) {
                                if ($uname === 'nhatthuthu') $role = 3;
                                elseif ($uname === 'admin130') $role = 1;
                                elseif ($uname === 'nhat') $role = 2;
                                else $role = 2;
                            }
                            $user = [
                                'id' => null,
                                'username' => $uname,
                                'full_name' => $acc['full_name'] ?? $uname,
                                'email' => $acc['email'] ?? null,
                                'address' => $acc['address'] ?? null,
                                'role' => (string)$role,
                                'password' => $pwd
                            ];
                            break;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            // ignore fallback errors
        }
    }

    if (!$user || !verify_password($password, $user['password'])) {
        return ['success' => false, 'message' => 'Tài khoản hoặc mật khẩu không đúng'];
    }

    unset($user['password']);
    $_SESSION['user'] = $user;

    return ['success' => true, 'message' => 'Đăng nhập thành công'];
}

function handle_register($conn, array $repo): array {
    $fullName = trim($_POST['full_name'] ?? '');
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($fullName === '' || $identifier === '' || $password === '' || $passwordConfirm === '' || $email === '') {
        return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'message' => 'Mật khẩu xác nhận không trùng khớp'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Định dạng email không hợp lệ'];
    }

    if (user_exists($conn, $repo, 'email', $email)) {
        return ['success' => false, 'message' => 'Email đã được sử dụng'];
    }

    $usernameColumn = $repo['columns']['username'] ?? null;
    $emailColumn = $repo['columns']['email'] ?? null;
    if ($usernameColumn && $usernameColumn !== $emailColumn && user_exists($conn, $repo, 'username', $identifier)) {
        return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    save_new_user($conn, $repo, [
        'full_name' => $fullName,
        'username' => $identifier,
        'email' => $email,
        'address' => $address,
        'password' => $hashedPassword,
    ]);

    return ['success' => true, 'message' => 'Đăng ký thành công! Vui lòng đăng nhập lại'];
}

function find_user_by_identifier($conn, array $repo, string $value): ?array {
    $table = wrap_identifier($repo['table']);
    $columns = $repo['columns'];
    $identifierColumns = array_map('wrap_identifier', $repo['identifiers']);

    $selectParts = [];
    foreach ($columns as $alias => $column) {
        if ($column === null) {
            $selectParts[] = 'NULL AS ' . alias_name($alias);
            continue;
        }
        $selectParts[] = wrap_identifier($column) . ' AS ' . alias_name($alias);
    }

    $conditions = implode(' OR ', array_map(fn($col) => "{$col} = ?", $identifierColumns));
    $sql = sprintf(
        'SELECT %s FROM %s WHERE (%s) LIMIT 1',
        implode(', ', $selectParts),
        $table,
        $conditions
    );

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Không thể chuẩn bị truy vấn đăng nhập');
    }

    $paramTypes = str_repeat('s', count($identifierColumns));
    $values = array_fill(0, count($identifierColumns), $value);
    $stmt->bind_param($paramTypes, ...$values);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    return [
        'id' => $row['col_id'] ?? null,
        'username' => $row['col_username'] ?? '',
        'full_name' => $row['col_full_name'] ?? '',
        'email' => $row['col_email'] ?? '',
        'address' => $row['col_address'] ?? '',
        'role' => $row['col_role'] ?? '',
        'password' => $row['col_password'] ?? '',
    ];
}

function user_exists($conn, array $repo, string $alias, string $value): bool {
    $column = $repo['columns'][$alias] ?? null;
    if ($column === null || $value === '') {
        return false;
    }

    $sql = sprintf(
        'SELECT 1 FROM %s WHERE %s = ? LIMIT 1',
        wrap_identifier($repo['table']),
        wrap_identifier($column)
    );
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $value);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function save_new_user($conn, array $repo, array $payload): void {
    $columns = $repo['columns'];
    $table = wrap_identifier($repo['table']);

    if ($repo['table'] === 'NGUOIDUNG') {
        $idCol = wrap_identifier($columns['id']);
        $usernameCol = wrap_identifier($columns['username']);
        $passwordCol = wrap_identifier($columns['password']);
        $nameCol = wrap_identifier($columns['full_name']);
        $emailCol = wrap_identifier($columns['email']);
        $addressCol = wrap_identifier($columns['address']);
        $roleCol = wrap_identifier($columns['role']);

        $newId = generate_user_id($conn, $repo);
        $roleValue = $repo['default_role'];

        $sql = "INSERT INTO {$table} ({$idCol}, {$usernameCol}, {$passwordCol}, {$nameCol}, {$emailCol}, {$addressCol}, {$roleCol})
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Không thể tạo tài khoản mới');
        }
        $addressValue = $payload['address'] !== '' ? $payload['address'] : null;
        $stmt->bind_param(
            'sssssss',
            $newId,
            $payload['username'],
            $payload['password'],
            $payload['full_name'],
            $payload['email'],
            $addressValue,
            $roleValue
        );
        $stmt->execute();
        $stmt->close();
        return;
    }

    // Default users table
    $nameCol = wrap_identifier($columns['full_name']);
    $emailCol = wrap_identifier($columns['email']);
    $passwordCol = wrap_identifier($columns['password']);
    $roleCol = wrap_identifier($columns['role']);

    $sql = "INSERT INTO {$table} ({$nameCol}, {$emailCol}, {$passwordCol}, {$roleCol}) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Không thể tạo tài khoản mới');
    }
    $roleValue = $repo['default_role'] ?: 'user';
    $stmt->bind_param('ssss', $payload['full_name'], $payload['email'], $payload['password'], $roleValue);
    $stmt->execute();
    $stmt->close();
}

function generate_user_id($conn, array $repo): string {
    $idColumn = $repo['columns']['id'] ?? null;
    if (!$idColumn) {
        return '';
    }
    $table = wrap_identifier($repo['table']);
    $column = wrap_identifier($idColumn);

    do {
        $candidate = 'U' . strtoupper(bin2hex(random_bytes(3)));
        $sql = "SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $candidate;
        }
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $candidate;
}

function verify_password(string $input, string $hash): bool {
    if ($hash === '') {
        return false;
    }
    if (password_verify($input, $hash)) {
        return true;
    }
    return hash_equals($hash, $input);
}
