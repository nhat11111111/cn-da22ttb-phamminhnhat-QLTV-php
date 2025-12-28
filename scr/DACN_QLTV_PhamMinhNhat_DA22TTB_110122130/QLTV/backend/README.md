# Backend Docker Setup

## Chạy Backend với Docker

### Build Image
```bash
docker build -t qltv-backend .
```

### Chạy Container
```bash
docker run -d -p 8080:80 --name qltv-backend-container qltv-backend
```

### Kết nối với MongoDB
```bash
docker run -d -p 8080:80 \
  -e MONGO_URI="mongodb://mongo:27017" \
  --name qltv-backend-container \
  qltv-backend
```

### Dừng Container
```bash
docker stop qltv-backend-container
```

### Xóa Container
```bash
docker rm qltv-backend-container
```

## API Endpoint
Backend sẽ chạy tại: `http://localhost:8080`
