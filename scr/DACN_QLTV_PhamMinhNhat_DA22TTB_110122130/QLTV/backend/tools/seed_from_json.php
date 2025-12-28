<?php
declare(strict_types=1);

/**
 * CLI script: tạo schema MySQL và import dữ liệu mẫu từ thư mục Database/initdb.d
 * Cách chạy (PowerShell/CMD): php backend/tools/seed_from_json.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Script chỉ dùng cho CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/db.php';

const DATA_DIR = __DIR__ . '/../../Database/initdb.d';

$conn = get_db_connection();
$conn->set_charset('utf8mb4');

try {
    $conn->begin_transaction();
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    ensure_schema($conn);
    truncate_tables($conn, [
        'MUON_TRA',
        'PHIEUMUONSACH',
        'SACH',
        'TACGIA',
        'DANHMUC',
        'SUPPORT',
        'NGUOIDUNG',
    ]);
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, "Không thể chuẩn bị schema: {$e->getMessage()}\n");
    exit(1);
}

seed_categories($conn, load_json('QLTV.DANHMUC.json'));
seed_authors($conn, load_json('QLTV.TACGIA.json'));
seed_users($conn, load_json('QLTV.NGUOIDUNG.json'));
seed_books($conn, load_json('QLTV.SACH.json'));
seed_borrow_slips($conn, load_json('QLTV.PHIEUMUONSACH.json'));
seed_borrow_history($conn, load_json('QLTV.MUON_TRA.json'));
seed_support($conn, load_json('QLTV.SUPPORT.json'));

echo "✅ Import thành công. Có thể đăng nhập bằng tài khoản hoanglong / 123456\n";

function ensure_schema(mysqli $conn): void {
    $statements = [
        <<<SQL
        CREATE TABLE IF NOT EXISTS `DANHMUC` (
            `ID_danh_muc` VARCHAR(20) NOT NULL PRIMARY KEY,
            `ten_danh_muc` VARCHAR(255) NOT NULL,
            `mo_ta` TEXT NULL,
            `ngay_tao` DATE NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS `TACGIA` (
            `ID_tac_gia` VARCHAR(20) NOT NULL PRIMARY KEY,
            `ten_tac_gia` VARCHAR(255) NOT NULL,
            `quoc_tich` VARCHAR(255) NULL,
            `ngay_sinh` DATE NULL,
            `tieu_su` TEXT NULL,
            `anh_dai_dien` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS `NGUOIDUNG` (
            `ID` VARCHAR(20) NOT NULL PRIMARY KEY,
            `username` VARCHAR(191) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `ho_ten` VARCHAR(255) NOT NULL,
            `dia_chi` TEXT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `loai` VARCHAR(10) NOT NULL DEFAULT '2',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS `SACH` (
            `ID` VARCHAR(20) NOT NULL PRIMARY KEY,
            `ten_sach` VARCHAR(255) NOT NULL,
            `tac_gia` VARCHAR(255) NULL,
            `nam_xuat_ban` VARCHAR(10) NULL,
            `ngon_ngu` VARCHAR(100) NULL,
            `trang_thai` VARCHAR(100) NULL,
            `vi_tri` VARCHAR(255) NULL,
            `id_nguoi_them` VARCHAR(50) NULL,
            `ngay_them` DATE NULL,
            `anh_bia` TEXT NULL,
            `danh_muc` VARCHAR(255) NULL,
            `ID_danh_muc` VARCHAR(20) NULL,
            `so_luong` INT NOT NULL DEFAULT 0,
            CONSTRAINT `fk_sach_danhmuc` FOREIGN KEY (`ID_danh_muc`) REFERENCES `DANHMUC`(`ID_danh_muc`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS `PHIEUMUONSACH` (
            `ID_phieu` VARCHAR(20) NOT NULL PRIMARY KEY,
            `ID_nguoi_dung` VARCHAR(20) NOT NULL,
            `ngay_muon` DATE NOT NULL,
            `ngay_tra_du_kien` DATE NULL,
            `trang_thai` VARCHAR(50) NOT NULL,
            `sach_muon` JSON NULL,
            CONSTRAINT `fk_phieu_user` FOREIGN KEY (`ID_nguoi_dung`) REFERENCES `NGUOIDUNG`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS `MUON_TRA` (
            `ID_muon_tra` VARCHAR(20) NOT NULL PRIMARY KEY,
            `ID_phieu` VARCHAR(20) NOT NULL,
            `ID_nguoi_dung` VARCHAR(20) NOT NULL,
            `ID_sach` VARCHAR(20) NOT NULL,
            `ten_sach` VARCHAR(255) NOT NULL,
            `ngay_muon` DATE NOT NULL,
            `ngay_tra_du_kien` DATE NULL,
            `ngay_tra` DATE NULL,
            `trang_thai` VARCHAR(50) NOT NULL,
            CONSTRAINT `fk_mt_phieu` FOREIGN KEY (`ID_phieu`) REFERENCES `PHIEUMUONSACH`(`ID_phieu`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_mt_user` FOREIGN KEY (`ID_nguoi_dung`) REFERENCES `NGUOIDUNG`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `fk_mt_book` FOREIGN KEY (`ID_sach`) REFERENCES `SACH`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
        <<<SQL
        CREATE TABLE IF NOT EXISTS `SUPPORT` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `content` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL,
    ];

    foreach ($statements as $sql) {
        if (!$conn->query($sql)) {
            throw new RuntimeException('Lỗi tạo bảng: ' . $conn->error);
        }
    }
}

function truncate_tables(mysqli $conn, array $tables): void {
    foreach ($tables as $table) {
        if (!$conn->query('TRUNCATE TABLE `' . $conn->real_escape_string($table) . '`')) {
            throw new RuntimeException('Không thể xoá dữ liệu bảng ' . $table . ': ' . $conn->error);
        }
    }
}

function load_json(string $filename): array {
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) {
        throw new RuntimeException("Không tìm thấy file dữ liệu {$filename}");
    }
    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException("Không đọc được file {$filename}");
    }
    $data = json_decode($content, true);
    if (!is_array($data)) {
        throw new RuntimeException("File {$filename} không phải JSON hợp lệ");
    }
    return $data;
}

function seed_categories(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `DANHMUC` (`ID_danh_muc`, `ten_danh_muc`, `mo_ta`, `ngay_tao`) VALUES (?,?,?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $id = (string)($row['ID_danh_muc'] ?? '');
        $name = (string)($row['ten_danh_muc'] ?? '');
        $desc = $row['mo_ta'] ?? null;
        $created = $row['ngay_tao'] ?? null;
        $stmt->bind_param('ssss', $id, $name, $desc, $created);
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import DANHMUC (" . count($rows) . " dòng)\n";
}

function seed_authors(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `TACGIA` (`ID_tac_gia`, `ten_tac_gia`, `quoc_tich`, `ngay_sinh`, `tieu_su`, `anh_dai_dien`) VALUES (?,?,?,?,?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $id = (string)($row['ID_tac_gia'] ?? '');
        $name = (string)($row['ten_tac_gia'] ?? '');
        $nation = $row['quoc_tich'] ?? null;
        $dob = $row['ngay_sinh'] ?? null;
        $bio = $row['tieu_su'] ?? null;
        $avatar = $row['anh_dai_dien'] ?? null;
        $stmt->bind_param('ssssss', $id, $name, $nation, $dob, $bio, $avatar);
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import TACGIA (" . count($rows) . " dòng)\n";
}

function seed_users(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `NGUOIDUNG` (`ID`,`username`,`password`,`ho_ten`,`dia_chi`,`email`,`loai`) VALUES (?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $id = (string)($row['ID'] ?? '');
        $username = (string)($row['username'] ?? '');
        $password = (string)($row['password'] ?? '');
        $name = (string)($row['ho_ten'] ?? '');
        $address = $row['dia_chi'] ?? '';
        $email = (string)($row['email'] ?? '');
        $role = (string)($row['loai'] ?? '2');
        $stmt->bind_param('sssssss', $id, $username, $password, $name, $address, $email, $role);
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import NGUOIDUNG (" . count($rows) . " dòng)\n";
}

function seed_books(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `SACH` (`ID`,`ten_sach`,`tac_gia`,`nam_xuat_ban`,`ngon_ngu`,`trang_thai`,`vi_tri`,`id_nguoi_them`,`ngay_them`,`anh_bia`,`danh_muc`,`ID_danh_muc`,`so_luong`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $id = (string)($row['ID'] ?? '');
        $name = (string)($row['ten_sach'] ?? '');
        $author = $row['tac_gia'] ?? null;
        $published = isset($row['nam_xuat_ban']) ? (string)$row['nam_xuat_ban'] : null;
        $language = $row['ngon_ngu'] ?? null;
        $status = $row['trang_thai'] ?? null;
        $shelf = $row['vi_tri'] ?? null;
        $adder = $row['id_nguoi_them'] ?? null;
        $addedDate = $row['ngay_them'] ?? null;
        $cover = $row['anh_bia'] ?? null;
        $category = $row['danh_muc'] ?? null;
        $categoryId = $row['ID_danh_muc'] ?? null;
        $stock = isset($row['so_luong']) ? (string)$row['so_luong'] : '0';
        $stmt->bind_param(
            'sssssssssssss',
            $id,
            $name,
            $author,
            $published,
            $language,
            $status,
            $shelf,
            $adder,
            $addedDate,
            $cover,
            $category,
            $categoryId,
            $stock
        );
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import SACH (" . count($rows) . " dòng)\n";
}

function seed_borrow_slips(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `PHIEUMUONSACH` (`ID_phieu`,`ID_nguoi_dung`,`ngay_muon`,`ngay_tra_du_kien`,`trang_thai`,`sach_muon`) VALUES (?,?,?,?,?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $id = (string)($row['ID_phieu'] ?? '');
        $userId = (string)($row['ID_nguoi_dung'] ?? '');
        $borrowDate = $row['ngay_muon'] ?? null;
        $dueDate = $row['ngay_tra_du_kien'] ?? null;
        $status = (string)($row['trang_thai'] ?? 'Đang mượn');
        $books = json_encode($row['sach_muon'] ?? [], JSON_UNESCAPED_UNICODE);
        $stmt->bind_param('ssssss', $id, $userId, $borrowDate, $dueDate, $status, $books);
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import PHIEUMUONSACH (" . count($rows) . " dòng)\n";
}

function seed_borrow_history(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `MUON_TRA` (`ID_muon_tra`,`ID_phieu`,`ID_nguoi_dung`,`ID_sach`,`ten_sach`,`ngay_muon`,`ngay_tra_du_kien`,`ngay_tra`,`trang_thai`) VALUES (?,?,?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $id = (string)($row['ID_muon_tra'] ?? '');
        $slip = (string)($row['ID_phieu'] ?? '');
        $userId = (string)($row['ID_nguoi_dung'] ?? '');
        $bookId = (string)($row['ID_sach'] ?? '');
        $bookName = (string)($row['ten_sach'] ?? '');
        $borrowDate = $row['ngay_muon'] ?? null;
        $dueDate = $row['ngay_tra_du_kien'] ?? null;
        $returnDate = $row['ngay_tra'] ?? null;
        $status = (string)($row['trang_thai'] ?? 'Đang mượn');
        $stmt->bind_param('sssssssss', $id, $slip, $userId, $bookId, $bookName, $borrowDate, $dueDate, $returnDate, $status);
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import MUON_TRA (" . count($rows) . " dòng)\n";
}

function seed_support(mysqli $conn, array $rows): void {
    $sql = 'INSERT INTO `SUPPORT` (`title`,`content`) VALUES (?,?)';
    $stmt = $conn->prepare($sql);
    foreach ($rows as $row) {
        $title = (string)($row['title'] ?? '');
        $content = (string)($row['content'] ?? '');
        $stmt->bind_param('ss', $title, $content);
        $stmt->execute();
    }
    $stmt->close();
    echo "• Đã import SUPPORT (" . count($rows) . " dòng)\n";
}

