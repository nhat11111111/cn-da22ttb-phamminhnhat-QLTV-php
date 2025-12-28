## Hướng dẫn chạy QLTV

### 1. Chuẩn bị môi trường
- Cài PHP 8 trở lên, MySQL/MariaDB 10+ và Composer (nếu cần mở rộng backend).
- Tạo database MySQL, ví dụ `qltv_demo`. Có thể đổi tên nhưng nhớ thiết lập biến môi trường `QLTV_DB_*` hoặc sửa trực tiếp trong `backend/includes/db.php`.

### 2. Import dữ liệu mẫu
```bash
# Windows PowerShell / CMD (đứng ở thư mục gốc repository)
php backend/tools/seed_from_json.php
```

Script sẽ:
1. Tạo các bảng (`NGUOIDUNG`, `SACH`, `PHIEUMUONSACH`, `MUON_TRA`, …).
2. Đọc toàn bộ dữ liệu trong `Database/initdb.d/*.json`.
3. Import vào MySQL và tạo sẵn tài khoản ví dụ `hoanglong / 123456`.


### 3. Hướng dẫn chạy trang web (Serve frontend + backend API)
**khởi chạy ở terminal**
```powershell
cd "d:\DAI HOC\NAM 4\DO_AN_CHUYEN_NGANH\DACN_QLTV_PhamMinhNhat_DA22TTB_110122130\QLTV"
php -S 127.0.0.1:8000 -t frontend
# Mở: http://127.0.0.1:8000/views/Dangnhap.html
```

**Backend API (tuỳ chọn - chạy ở terminal khác):**
Nếu cần gọi API từ frontend, khởi chạy server backend:
```powershell
cd "d:\DAI HOC\NAM 4\DO_AN_CHUYEN_NGANH\DACN_QLTV_PhamMinhNhat_DA22TTB_110122130\QLTV"
php -S 127.0.0.1:8001 -t backend
# API sẽ có sẵn tại: http://127.0.0.1:8001/api/*
```
Frontend cấu hình để call API ở port 8001 nếu cần.

---

cd "d:\DAI HOC\NAM 4\DO_AN_CHUYEN_NGANH\DACN_QLTV_PhamMinhNhat_DA22TTB_110122130\QLTV"; .\run-dev.ps1 -Port 8000
```

### 5. kiểm tra trang đăng nhập có phản hồi. Mở đường dẫn này mở giao diện trang đăng nhập để kiểm tra server khởi động thành công hay chưa.
"http://127.0.0.1:8000/views/Dangnhap.html"


### 5. Quy trình phát triển gợi ý
- Chỉnh sửa code → chạy lại `php backend/tools/seed_from_json.php` nếu thay đổi dữ liệu mẫu.
- Với môi trường thật, chỉ cần chạy script này một lần rồi sử dụng các trang đăng nhập/đăng ký bình thường.

#**Lệnh chạy nhanh Docker**


# Backend
cd backend
docker build -t qltv-backend .
docker run -d -p 8080:80 --name qltv-backend-container qltv-backend

# Frontend
cd frontend
docker build -t qltv-frontend .
docker run -d -p 3000:80 --name qltv-frontend-container qltv-frontend