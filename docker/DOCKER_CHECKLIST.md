# ✅ Checklist Trước Khi Chạy Docker

## Bước 1: Cài đặt Docker Desktop

- [ ] Tải Docker Desktop: https://www.docker.com/products/docker-desktop/
- [ ] Cài đặt và khởi động lại máy nếu cần
- [ ] Mở Docker Desktop và đợi khởi động hoàn tất

## Bước 2: Kiểm Tra Docker

Mở PowerShell và chạy:

```powershell
docker --version
docker-compose --version
docker ps
```

Nếu tất cả lệnh chạy không lỗi → Docker đã sẵn sàng! ✅

## Bước 3: Khởi động Docker Desktop

**QUAN TRỌNG:** 
1. Mở Docker Desktop từ Start Menu
2. Đợi icon Docker ở system tray (góc phải màn hình) chuyển sang màu xanh
3. Docker đang chạy khi bạn thấy: "Docker Desktop is running"

## Bước 4: Chạy QLTV

### Cách 1: Sử dụng Script (Đơn giản nhất)

```powershell
cd "d:\DAI HOC\NAM 4\DO_AN_CHUYEN_NGANH\DACN_QLTV_PhamMinhNhat_DA22TTB_110122130\QLTV"
.\run-docker.ps1
```

### Cách 2: Sử dụng Docker Compose

```powershell
cd "d:\DAI HOC\NAM 4\DO_AN_CHUYEN_NGANH\DACN_QLTV_PhamMinhNhat_DA22TTB_110122130\QLTV"
docker-compose up -d --build
```

## Bước 5: Kiểm Tra

Sau 1-2 phút, kiểm tra containers:

```powershell
docker-compose ps
```

Bạn sẽ thấy 2 containers đang chạy:
- qltv_web (web application)
- qltv_mongodb (database)

## Bước 6: Truy Cập

Mở trình duyệt và vào: **http://localhost:8000**

## 🚨 Nếu Gặp Lỗi

### Lỗi: "Cannot connect to Docker daemon"

**Nguyên nhân:** Docker Desktop chưa chạy

**Giải pháp:**
1. Mở Docker Desktop
2. Đợi khởi động hoàn tất (icon xanh)
3. Chạy lại lệnh

### Lỗi: "Port 8000 already in use"

**Giải pháp:** Thay đổi port trong `docker-compose.yml`:

```yaml
web:
  ports:
    - "8080:80"  # Đổi 8000 → 8080
```

Sau đó truy cập: http://localhost:8080

### Lỗi: "Error response from daemon"

**Giải pháp:**
1. Dừng tất cả containers: `docker-compose down`
2. Khởi động lại Docker Desktop
3. Chạy lại: `docker-compose up -d --build`

## 📞 Liên Hệ

Nếu vẫn gặp vấn đề, xem file DOCKER_GUIDE.md để biết thêm chi tiết.
