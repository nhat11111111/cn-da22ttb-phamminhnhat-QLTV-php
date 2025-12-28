<?php
// MongoDB query helper functions
// Sử dụng cho các file khác khi cần query MongoDB

declare(strict_types=1);

/**
 * Lấy collection từ MongoDB
 * @param string $collectionName Tên collection (ví dụ: 'NGUOIDUNG')
 * @return MongoDB\Collection|null
 */
function get_mongo_collection(string $collectionName) {
    $conn = get_db_connection();
    
    if (!is_using_mongodb()) {
        return null;
    }
    
    global $MONGO_DB_NAME;
    
    try {
        if (class_exists('MongoDB\\Client') && method_exists($conn, 'selectDatabase')) {
            // MongoDB PHP Library (mongodb/mongodb)
            /** @var MongoDB\Client $conn */
            $database = $conn->selectDatabase($MONGO_DB_NAME);
            return $database->selectCollection($collectionName);
        }
    } catch (Throwable $e) {
        error_log("MongoDB collection error: " . $e->getMessage());
    }
    return null;
}

/**
 * Tìm một user theo username hoặc email (cho đăng nhập)
 * @param string $identifier Username hoặc email
 * @return array|null
 */
function find_user_by_identifier(string $identifier): ?array {
    $conn = get_db_connection();
    $dbType = get_db_type();

    // Nếu dùng MongoDB
    if ($dbType === 'mongodb' && is_using_mongodb()) {
        try {
            global $MONGO_DB_NAME;
            if (class_exists('MongoDB\\Client') && method_exists($conn, 'selectDatabase')) {
                /** @var MongoDB\Client $conn */
                $database = $conn->selectDatabase($MONGO_DB_NAME);
                $collection = $database->selectCollection('NGUOIDUNG');

                $filter = ['$or' => [['username' => $identifier], ['email' => $identifier]]];
                $result = $collection->findOne($filter);

                return $result ? (array)$result : null;
            }
        } catch (Throwable $e) {
            error_log("MongoDB findOne error: " . $e->getMessage());
        }
    }

    // Nếu dùng MySQL hoặc Fallback
    if ($dbType === 'mysql' || $dbType === 'fallback') {
        try {
            $sql = 'SELECT * FROM NGUOIDUNG WHERE username = ? OR email = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param('ss', $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                
                return $user ?: null;
            }
        } catch (Throwable $e) {
            error_log("Database findOne error: " . $e->getMessage());
        }
    }

    return null;
}

/**
 * Lấy tất cả users
 * @return array
 */
function get_all_users(): array {
    $conn = get_db_connection();
    $dbType = get_db_type();

    // Nếu dùng MongoDB
    if ($dbType === 'mongodb' && is_using_mongodb()) {
        try {
            global $MONGO_DB_NAME;
            if (class_exists('MongoDB\\Client') && method_exists($conn, 'selectDatabase')) {
                /** @var MongoDB\Client $conn */
                $database = $conn->selectDatabase($MONGO_DB_NAME);
                $collection = $database->selectCollection('NGUOIDUNG');

                $results = $collection->find([]);
                $users = [];
                foreach ($results as $doc) {
                    $users[] = (array)$doc;
                }
                return $users;
            }
        } catch (Throwable $e) {
            error_log("MongoDB find error: " . $e->getMessage());
        }
    }

    // Nếu dùng MySQL hoặc Fallback
    if ($dbType === 'mysql' || $dbType === 'fallback') {
        try {
            $sql = 'SELECT * FROM NGUOIDUNG';
            $result = $conn->query($sql);
            
            if ($result) {
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
                return $users;
            }
        } catch (Throwable $e) {
            error_log("Database find all error: " . $e->getMessage());
        }
    }

    return [];
}

/**
 * Thêm user mới vào MongoDB
 * @param array $userData ['username' => 'test', 'password' => 'hashed', 'ho_ten' => '...', ...]
 * @return string|null MongoDB ObjectId hoặc ID của document vừa thêm
 */
function insert_user(array $userData): ?string {
    $conn = get_db_connection();
    
    if (is_using_mongodb()) {
        try {
            global $MONGO_DB_NAME;
            if (class_exists('MongoDB\\Client') && method_exists($conn, 'selectDatabase')) {
                /** @var MongoDB\Client $conn */
                $database = $conn->selectDatabase($MONGO_DB_NAME);
                $collection = $database->selectCollection('NGUOIDUNG');
                
                // Tạo ID nếu chưa có
                if (!isset($userData['ID'])) {
                    $userData['ID'] = 'U' . strtoupper(bin2hex(random_bytes(3)));
                }
                
                $result = $collection->insertOne($userData);
                return (string)$result->getInsertedId();
            }
        } catch (Throwable $e) {
            error_log("MongoDB insertOne error: " . $e->getMessage());
        }
    }
    
    return null;
}

/**
 * Cập nhật user
 * @param string $userId ID của user
 * @param array $updateData Dữ liệu cần cập nhật
 * @return bool
 */
function update_user(string $userId, array $updateData): bool {
    $conn = get_db_connection();
    
    if (is_using_mongodb()) {
        try {
            global $MONGO_DB_NAME;
            if (class_exists('MongoDB\\Client') && method_exists($conn, 'selectDatabase')) {
                /** @var MongoDB\Client $conn */
                $database = $conn->selectDatabase($MONGO_DB_NAME);
                $collection = $database->selectCollection('NGUOIDUNG');
                
                $filter = ['ID' => $userId];
                $update = ['$set' => $updateData];
                
                $result = $collection->updateOne($filter, $update);
                return $result->getModifiedCount() > 0;
            }
        } catch (Throwable $e) {
            error_log("MongoDB updateOne error: " . $e->getMessage());
        }
    }
    
    return false;
}

/**
 * Xóa user
 * @param string $userId ID của user
 * @return bool
 */
function delete_user(string $userId): bool {
    $conn = get_db_connection();
    
    if (is_using_mongodb()) {
        try {
            global $MONGO_DB_NAME;
            if (class_exists('MongoDB\\Client') && method_exists($conn, 'selectDatabase')) {
                /** @var MongoDB\Client $conn */
                $database = $conn->selectDatabase($MONGO_DB_NAME);
                $collection = $database->selectCollection('NGUOIDUNG');
                
                $filter = ['ID' => $userId];
                $result = $collection->deleteOne($filter);
                
                return $result->getDeletedCount() > 0;
            }
        } catch (Throwable $e) {
            error_log("MongoDB deleteOne error: " . $e->getMessage());
        }
    }
    
    return false;
}
