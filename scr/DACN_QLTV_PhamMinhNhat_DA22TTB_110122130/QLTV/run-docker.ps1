# ============================================================
# Docker Run Script cho Ứng dụng QLTV
# Tự động khởi động và quản lý ứng dụng QLTV bằng Docker
# ============================================================
#
# 📖 HƯỚNG DẪN SỬ DỤNG:
#
# Bước 1: Khởi động Docker Desktop
#   - Mở Docker Desktop và đợi nó khởi động hoàn tất
#   - Kiểm tra icon xanh ở system tray (góc phải màn hình)
#
# Bước 2: Chạy ứng dụng
#   Cách 1 (Khuyến nghị): .\run-docker.ps1
#   Cách 2: docker-compose up -d --build
#
# Bước 3: Truy cập
#   Mở trình duyệt: http://localhost:8000
#
# 🎯 CÁC TÍNH NĂNG DOCKER SETUP:
#   ✅ 2 containers: Web (PHP/Apache) + MongoDB
#   ✅ Auto-restart: Containers tự động khởi động lại khi crash
#   ✅ Health checks: Tự động kiểm tra health của services
#   ✅ Data persistence: MongoDB data được lưu trong volume
#   ✅ Development mode: Code changes tự động áp dụng (không cần rebuild)
#   ✅ Network isolation: Containers giao tiếp qua private network
#   ✅ MongoDB init: Tự động tạo collections và indexes
#
# 📋 CÁC LỆNH HỮU ÍCH:
#   .\run-docker.ps1           # Khởi động ứng dụng
#   .\run-docker.ps1 -Logs     # Xem logs
#   .\run-docker.ps1 -Status   # Kiểm tra trạng thái
#   .\run-docker.ps1 -Restart  # Khởi động lại
#   .\run-docker.ps1 -Stop     # Dừng ứng dụng
#   .\run-docker.ps1 -Rebuild  # Rebuild và khởi động lại
#   .\run-docker.ps1 -Clean    # Xóa tất cả (bao gồm data)
#
# ============================================================

param(
    [switch]$Stop,
    [switch]$Restart,
    [switch]$Rebuild,
    [switch]$Logs,
    [switch]$Status,
    [switch]$Clean,
    [switch]$Help
)

$ErrorActionPreference = "Stop"

        
        Write-Host "`n✅ Ứng dụng đã khởi động thành công!" -ForegroundColor Green
        Write-Host "`n🌐 Truy cập ứng dụng tại:" -ForegroundColor Cyan
        Write-Host "   http://localhost:8000" -ForegroundColor White -BackgroundColor Blue
        Write-Host "`n📋 Các lệnh hữu ích:" -ForegroundColor Yellow
        Write-Host "   .\run-docker.ps1 -Logs      # Xem logs" -ForegroundColor Gray
        Write-Host "   .\run-docker.ps1 -Status    # Kiểm tra trạng thái" -ForegroundColor Gray
        Write-Host "   .\run-docker.ps1 -Restart   # Khởi động lại" -ForegroundColor Gray
        Write-Host "   .\run-docker.ps1 -Stop      # Dừng containers" -ForegroundColor Gray
        Write-Host "   .\run-docker.ps1 -Help      # Xem hướng dẫn đầy đủ" -ForegroundColor Gray
        Write-Host ""
    }
}

Pop-Location
