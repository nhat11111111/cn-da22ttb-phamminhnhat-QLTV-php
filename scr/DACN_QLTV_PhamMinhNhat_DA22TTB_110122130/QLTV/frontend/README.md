# Frontend Docker Setup

## Chạy Frontend với Docker

### Build Image
```bash
docker build -t qltv-frontend .
```

### Chạy Container
```bash
docker run -d -p 3000:80 --name qltv-frontend-container qltv-frontend
```

### Kết nối với Backend
```bash
docker run -d -p 3000:80 \
  -e BACKEND_URL="http://backend:8080" \
  --name qltv-frontend-container \
  qltv-frontend
```

### Dừng Container
```bash
docker stop qltv-frontend-container
```

### Xóa Container
```bash
docker rm qltv-frontend-container
```

## Truy cập Web
Frontend sẽ chạy tại: `http://localhost:3000`
