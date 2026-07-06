CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Campaigner', 'Donatur', 'Verifikator') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (nama_lengkap, email, password, role) 
VALUES ('I Putu Adi Tirta Saputra', 'adi@admin.com', '$2y$10$wT8/iPj0l5k7h.9g6rQv.e', 'Admin');
