CREATE DATABASE IF NOT EXISTS db_crowdfunding;
USE db_crowdfunding;

CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Campaigner', 'Donatur', 'Verifikator') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (nama_lengkap, email, password, role) 
VALUES ('I Putu Adi Tirta Saputra', 'adi@admin.com', '$2y$10$R0HgqO1H/4BaDNhaEX/vmeCiPVKIFiOQplq1U.jGI/NYiqUEE7KLy', 'Admin');

CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL
);

INSERT INTO kategori (nama_kategori) VALUES ('Bencana Alam'), ('Pendidikan'), ('Bantuan Medis');

CREATE TABLE kampanye (
    id_kampanye INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_kategori INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NOT NULL,
    target_dana DECIMAL(15,2) NOT NULL,
    gambar_banner VARCHAR(255) NOT NULL,
    status_kampanye ENUM('Pending', 'Active', 'Rejected', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE CASCADE
);

CREATE TABLE transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    id_kampanye INT NOT NULL,
    id_user INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    status_pembayaran ENUM('Pending', 'Success', 'Failed') DEFAULT 'Pending',
    snap_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kampanye) REFERENCES kampanye(id_kampanye) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);
