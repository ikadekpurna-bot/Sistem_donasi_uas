<?php
require 'koneksi.php';
require 'security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = escape_html($_POST['nama_lengkap']);
    $email = escape_html($_POST['email']);
    $password_mentah = $_POST['password'];
    $role = 'Donatur'; 

    $password_hash = password_hash($password_mentah, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $password_hash, $role]);
        echo "<script>alert('Pendaftaran Berhasil! Silakan Login.'); window.location='login.php';</script>";
    } catch (\PDOException $e) {
        echo "Error: Email mungkin sudah terdaftar.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Register</title></head>
<body>
    <h2>Daftar Akun Donatur</h2>
    <form method="POST" action="">
        <label>Nama Lengkap:</label><br>
        <input type="text" name="nama_lengkap" required><br><br>
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Daftar</button>
    </form>
</body>
</html>
