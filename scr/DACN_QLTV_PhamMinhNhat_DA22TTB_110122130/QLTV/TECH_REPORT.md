# CHƯƠNG 3: CÔNG NGHỆ VÀ KỸ THUẬT TRIỂN KHAI

## 3.1 Tổng quan Công nghệ Sử dụng

Hệ thống Quản lý Thư viện (QLTV) được xây dựng dựa trên kiến trúc 3 lớp (3-tier architecture) bao gồm: Frontend (Tầng giao diện người dùng), Backend (Tầng xử lý logic nghiệp vụ), và Database (Tầng lưu trữ dữ liệu). Dự án được phát triển hoàn toàn từ đầu không sử dụng các framework phổ biến như Laravel, Vue.js hay React, nhằm mục đích hiểu rõ bản chất hoạt động của các công nghệ web cơ bản.

### 3.1.1 Tổng hợp Công nghệ theo Tầng

**Tầng Frontend (Presentation Layer)**
- HTML5: Xây dựng cấu trúc giao diện
- CSS3: Định dạng và styling
- JavaScript ES6+: Xử lý logic phía client

**Tầng Backend (Business Logic Layer)**
- PHP 8+: Ngôn ngữ lập trình server-side chính
- Composer: Quản lý dependencies
- MongoDB PHP Library: Thư viện kết nối MongoDB

**Tầng Database (Data Layer)**
- MongoDB: NoSQL database chính
- MySQL/MariaDB: Relational database dự phòng
- JSON Files: Lưu trữ file-based dự phòng cuối cùng

**Đặc điểm nổi bật**
- Không sử dụng framework phổ biến
- Tự xây dựng MVC pattern, routing, authentication
- Cơ chế fallback tự động giữa 3 database tiers
- RESTful API với JSON response format
- Session-based authentication

## 3.2 Frontend - Tầng Giao diện Người dùng

### 3.2.1 HTML

1. **Xây dựng cấu trúc trang web**: Tạo 5 trang chính của hệ thống bao gồm trang đăng nhập (Dangnhap.html), trang quản trị viên (Trangchuadmin.html), trang thủ thư (Trangchuthuthu.html), trang người dùng (Trangchuuser.html), và trang quản lý tài khoản (Taikhoan.html).

 2**Hiển thị dữ liệu**: Tạo các bảng (table) hiển thị danh sách sách, danh sách người dùng, lịch sử mượn/trả; tạo các card (div với class) để hiển thị thông tin chi tiết từng cuốn sách với ảnh bìa, tên sách, tác giả, số lượng còn lại.

3. **Tương tác người dùng**: Xây dựng các button, link, menu navigation để người dùng có thể thực hiện các thao tác như đăng nhập, đăng xuất, mượn sách, trả sách, tìm kiếm, và chuyển trang.

### 3.2.2 CSS

1. **Định dạng giao diện**: Thiết lập màu sắc, font chữ, kích thước, khoảng cách (margin, padding) cho tất cả các phần tử HTML. File styles.css có 849 dòng code quản lý toàn bộ styling cho 5 trang chính.Tạo hiệu ứng hoạt ảnh cho giao diện

### 3.2.3 JavaScript

Xử lý toàn bộ logic phía client, tương tác với người dùng, và giao tiếp với backend thông qua API.

**Tác dụng cụ thể**:

1. **Giao tiếp với Backend API**: Sử dụng Fetch API gửi HTTP requests đến backend và nhận JSON responses. Xử lý các thao tác như đăng nhập, đăng ký, lấy danh sách sách, mượn sách, trả sách, tìm kiếm, thông qua các API endpoints.

2. Thay đổi nội dung trang web động mà không cần reload. Thêm/xóa/sửa các phần tử HTML, Cập nhật số lượng sách còn lại theo thời gian thực.

3. **File upload preview**: Sử dụng FileReader API để đọc file ảnh người dùng chọn và hiển thị preview trước khi upload lên server. Validate file type (chỉ chấp nhận JPG, PNG) và file size (tối đa 5MB).

## 3.3 Backend 

### 3.3.1 PHP 

**Vai trò trong Hệ thống QLTV**: PHP xây dựng toàn bộ backend, xử lý mọi logic nghiệp vụ, xác thực người dùng, thao tác với database, và cung cấp RESTful APIs cho frontend.

### 3.3.3 MongoDB PHP Library

**Định nghĩa**: MongoDB PHP Library là thư viện chính thức của MongoDB để tương tác với MongoDB database từ PHP code.

**Chiến lược 3-Tier Database**:

Hệ thống implement 3 tiers (tầng) database theo thứ tự ưu tiên:

**Lợi ích của Multi-Database Strategy**:
- High availability: Để đảm bảo Ứng dụng không bao giờ bị sập vì vấn đề mất kết nối CSDL
- Flexibility: Chạy được trên nhiều môi trường khác nhau

### 3.4.2 MongoDB 
Database chính được ưu tiên sử dụng trong production tạo môi trường vận hành trang web

**Thông tin kết nối**:
- Database name: qltv_demo
- Connection URI: mongodb://localhost:27017
- Configuration: Đọc từ file Database/mongo_uri.txt hoặc environment variable QLTV_MONGO_URI
- Default port: 27017


**1. NGUOIDUNG 
- Lưu trữ thông tin tài khoản người dùng
- Fields: ID, username, password (hashed), ho_ten, email, dia_chi, loai (role), created_at
- Index: username (unique), email (unique)


**2. SACH
- Lưu trữ thông tin sách trong thư viện
- Fields: ID, ten_sach, tac_gia, nam_xuat_ban, ngon_ngu, danh_muc, ID_danh_muc, so_luong, trang_thai, anh_bia, vi_tri
- Index: ten_sach (text search), ID_danh_muc
- Số lượng: 15+ books

**3. MUON_TRA
- Lưu trữ lịch sử mượn/trả sách
- Fields: ID_muon_tra, ID_sach, ID_nguoi_dung, ngay_muon, ngay_tra, trang_thai
- Index: ID_sach, ID_nguoi_dung, trang_thai
- References: Link đến SACH và NGUOIDUNG

**4. PHIEUMUONSACH
- Lưu trữ phiếu mượn sách chính thức
- Fields: ID_phieu, ID_nguoi_dung, ngay_lap, trang_thai
- Index: ID_nguoi_dung

**5. DANHMUC
- Lưu trữ danh mục sách
- Fields: ID, ten_danh_muc, mo_ta
- Index: ten_danh_muc

**6. TACGIA 
- Lưu trữ thông tin tác giả
- Fields: ID, ten_tac_gia, quoc_tich, nam_sinh, tieu_su
- Index: ten_tac_gia

**7. SUPPORT 
- Lưu trữ yêu cầu hỗ trợ từ người dùng
- Fields: ID, username, title, details, screenshot_path, status, created_at, replied_by, reply_message
- Index: username, status

### 3.4.3 MySQL/MariaDB (Tier 2)

**Vai trò trong Hệ thống QLTV**: Database dự phòng thứ nhất, được sử dụng khi MongoDB không có sẵn.

**Thông tin kết nối**:
- Database name: qltv_demo
- Host: 127.0.0.1 (localhost)
- Port: 3306
- User: root (configurable qua environment variable QLTV_DB_USER)
- Password: empty string (configurable qua QLTV_DB_PASS)
- Charset: utf8mb4 để hỗ trợ Unicode đầy đủ

### 3.4.4 JSON Files - File-based Storage (Tier 3): là cách lưu trữ dữ liệu dạng text file với format JSON, không cần database server.

**Vai trò trong Hệ thống QLTV**: Phương án dự phòng cuối cùng, đảm bảo ứng dụng vẫn chạy được ngay cả khi không có MongoDB hay MySQL

**Cấu trúc Files**:

Location: Database/initdb.d/

**7 JSON Files tương ứng 7 collections/tables**:
- QLTV.NGUOIDUNG.json: 110 lines, 20+ users
- QLTV.SACH.json: 572 lines, 150+ books  
- QLTV.MUON_TRA.json: Borrow/return history
- QLTV.PHIEUMUONSACH.json: Borrow receipts
- QLTV.DANHMUC.json: Book categories
- QLTV.TACGIA.json: Authors information
- QLTV.SUPPORT.json: Support tickets


## 3.5 Tổng kết và Đánh giá

### 3.5.1 Tổng kết Stack Công nghệ

**Frontend Stack**:
- HTML5: Cấu trúc giao diện
- CSS3: Styling và layout
- JavaScript ES6+: Logic phía client


**Backend Stack**:
- PHP 8+: Logic nghiệp vụ chính
- Composer: Dependency management
- MongoDB PHP Library: Database driver


**Database Stack**:
- MongoDB: Primary NoSQL database
- MySQL: Secondary relational database
- JSON Files: Tertiary file-based storage
- Đặc điểm: Multi-tier với auto-fallback

**Infrastructure**:
- PHP Built-in Server: Development server
- Port 8000: Frontend serving
- Port 8001: Backend API (optional)

### 3.5.5 Kết luận

Hệ thống QLTV được xây dựng với stack công nghệ cơ bản nhưng hiệu quả, phù hợp với mục đích học tập và hiểu sâu về web development. Multi-database strategy với auto-fallback là điểm sáng, đảm bảo application hoạt động trong mọi môi trường. Mặc dù có limitations về scalability và features, architecture này provide solid foundation để học và understand cách các web applications hoạt động ở low-level.