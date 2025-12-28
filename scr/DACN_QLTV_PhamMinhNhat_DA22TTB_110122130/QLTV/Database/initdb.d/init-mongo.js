// MongoDB initialization script
// This script will run automatically when MongoDB container starts for the first time

// Switch to the QLTV database
db = db.getSiblingDB('qltv_demo');

// Create collections if they don't exist
db.createCollection('NGUOIDUNG');
db.createCollection('DANHMUC');
db.createCollection('TACGIA');
db.createCollection('SACH');
db.createCollection('PHIEUMUONSACH');
db.createCollection('MUON_TRA');
db.createCollection('SUPPORT');

// Create indexes for better query performance
db.NGUOIDUNG.createIndex({ "TenDangNhap": 1 }, { unique: true });
db.NGUOIDUNG.createIndex({ "Email": 1 });
db.SACH.createIndex({ "TenSach": 1 });
db.SACH.createIndex({ "MaDanhMuc": 1 });
db.PHIEUMUONSACH.createIndex({ "MaNguoiDung": 1 });
db.PHIEUMUONSACH.createIndex({ "NgayMuon": -1 });
db.MUON_TRA.createIndex({ "MaPhieu": 1 });

print('MongoDB initialization completed successfully!');
