# Cấu Trúc MVC - Quản Lý Thư Viện (QLTV)

Dự án được tổ chức theo mô hình **MVC (Model-View-Controller)** để phân tách logic nghiệp vụ, dữ liệu và giao diện người dùng.

## Cấu Trúc Thư Mục

```
QLTV/
├── backend/
│   ├── models/                  # Model - Xử lý dữ liệu & logic nghiệp vụ
│   │   ├── Database.php         # Kết nối cơ sở dữ liệu (MySQL/Fallback)
│   │   └── AccountRepository.php  # Quản lý tài khoản local (Taikhoan.html)
│   │
│   ├── controllers/             # Controller - Xử lý request & response
│   │   ├── AuthController.php       # Đăng nhập/đăng ký
│   │   ├── SessionController.php    # Kiểm tra session
│   │   ├── LogoutController.php     # Đăng xuất
│   │   ├── DbConfigController.php   # Cấu hình DB
│   │   └── AccountController.php    # Quản lý tài khoản
│   │
│   ├── includes/                # Utilities & helpers (legacy)
│   │   └── db.php               # (cũ - giữ lại để tương thích)
│   │
│   ├── api/                     # (cũ - API endpoints cũ)
│   │
│   └── tools/                   # Tools & scripts
│       ├── init_db.php
│       └── seed_from_json.php
│
├── frontend/
│   ├── views/                   # View - Giao diện người dùng (HTML)
│   │   ├── Dangnhap.html        # Trang đăng nhập/đăng ký
│   │   ├── Trangchuuser.html    # Trang chủ người dùng
│   │   └── Taikhoan.html        # Danh sách tài khoản lưu
│   │
│   ├── api/                     # Proxy routes → controllers
│   │   ├── auth.php             # require AuthController.php
│   │   ├── check-session.php    # require SessionController.php
│   │   ├── logout.php           # require LogoutController.php
│   │   ├── db-config.php        # require DbConfigController.php
│   │   └── taikhoan.php         # require AccountController.php
│   │
│   ├── css/
│   │   └── styles.css
│   │
│   └── index.php                # Entry point (router)
│
├── Database/
│   ├── mongo_uri.txt            # MongoDB connection URI
│   ├── compass-connections.json
│   └── initdb.d/                # Seed data
│       ├── QLTV.NGUOIDUNG.json  (fallback users)
│       └── ... (other data)
│
├── README.md
```

## Giải Thích Các Phần MVC

### **1. Model (backend/models/)**
Quản lý dữ liệu và logic nghiệp vụ:

- **Database.php**: Kết nối MySQL hoặc fallback vào JSON
  - Hỗ trợ biến môi trường (`QLTV_DB_HOST`, `QLTV_DB_USER`, etc.)
  - Fallback đến `Database/initdb.d/QLTV.NGUOIDUNG.json` nếu MySQL không khả dụng
  - Provides `get_db_connection()` function

- **AccountRepository.php**: Quản lý tài khoản lưu locally
  - Đọc/ghi tài khoản trong `frontend/Taikhoan.html` (script tag JSON)
  - Method: `readAccounts()`, `addAccount(username, password)`

### **2. View (frontend/views/)**
Giao diện người dùng (HTML + CSS + JavaScript):

- **Dangnhap.html**: Đăng nhập/Đăng ký
  - Giao diện HTML + JS
  - Fetch API gọi `../api/auth.php`
  - Gợi ý username từ tài khoản đã lưu (datalist)

- **Trangchuuser.html**: Trang chủ bạn đọc
  - Hiển thị thông tin người dùng sau khi đăng nhập
  - Fetch API: `../api/check-session.php`, `../api/logout.php`

- **Taikhoan.html**: Danh sách tài khoản đã lưu
  - Hiển thị tài khoản được lưu locally

### **3. Controller (backend/controllers/)**
Xử lý request từ View và tương tác với Model:

- **AuthController.php** (POST action=login/register)
  - Validate input
  - Gọi Model (Database) để xác thực/lưu
  - Trả JSON response

- **SessionController.php** (GET check-session)
  - Check `$_SESSION['user']`
  - Trả JSON: `{loggedIn: true/false, user: {...}}`

- **LogoutController.php** (POST logout)
  - `session_destroy()`

- **DbConfigController.php** (GET)
  - Trả MongoDB URI (dev purpose)

- **AccountController.php** (GET/POST taikhoan)
  - GET: Lấy danh sách tài khoản
  - POST: Thêm tài khoản vào `Taikhoan.html`
  - Gọi `AccountRepository` model

## Luồng Xử Lý Đăng Nhập

```
Browser (View)
    ↓ POST /api/auth.php (form data)
frontend/api/auth.php (proxy)
    ↓ require backend/controllers/AuthController.php
AuthController.php (Controller)
    ↓ get_db_connection()
Database.php (Model - MySQL or Fallback)
    ↓ Check credentials
AuthController.php (Controller)
    ↓ $_SESSION['user'] = user_data
    ↓ echo json_encode(['success' => true])
Browser (View)
    ↓ Receive response
    ↓ Redirect to Trangchuuser.html
Trangchuuser.html (View)
    ↓ fetch('../api/check-session.php')
frontend/api/check-session.php (proxy)
    ↓ require backend/controllers/SessionController.php
SessionController.php (Controller)
    ↓ Check $_SESSION['user']
    ↓ echo json_encode(['loggedIn' => true, 'user' => $user])
Browser (View)
    ↓ Display user info
```

## MongoDB Support

- `AuthController.php` kiểm tra nếu extension `mongodb` có sẵn
- Đọc URI từ env var `QLTV_MONGO_URI` hoặc `Database/mongo_uri.txt`
- Ưu tiên: MongoDB > MySQL > JSON Fallback

## Chạy Dự Án

### Development Server
```bash
cd QLTV
php -S localhost:8000 -t frontend
```

### Access Points
- **Đăng nhập/Đăng ký**: http://localhost:8000/views/Dangnhap.html
- **Trang chủ**: http://localhost:8000/views/Trangchuuser.html
- **Tài khoản**: http://localhost:8000/views/Taikhoan.html

### Environment Variables (Optional)
```bash
# MySQL
export QLTV_DB_HOST=localhost
export QLTV_DB_USER=root
export QLTV_DB_PASS=password
export QLTV_DB_NAME=qltv_demo

# MongoDB
export QLTV_MONGO_URI=mongodb://localhost:27017
export QLTV_MONGO_DB=qltv_demo
```

## Lợi Ích Cấu Trúc MVC

✅ **Tách biệt trách nhiệm**: Model, View, Controller độc lập  
✅ **Dễ bảo trì**: Code có tổ chức, dễ tìm bug  
✅ **Tái sử dụng**: Model/Controller có thể dùng cho nhiều View  
✅ **Mở rộng dễ**: Thêm API endpoint mới chỉ cần thêm Controller mới  
✅ **Test được**: Unit test từng phần riêng  

---

**Phiên bản**: 1.0  
**Cấu trúc**: MVC (Model-View-Controller)  
**Ngôn ngữ**: PHP 8.5+, HTML5, JavaScript (Fetch API)  
**Cơ sở dữ liệu**: MySQL / MongoDB / JSON Fallback
