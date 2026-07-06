<?php
require 'middleware.php';

wajib_login();

cek_role('Admin');
?>

<!DOCTYPE html>
<html>
<head><title>Dasbor Admin</title></head>
<body>
    <h1>Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?>!</h1>
    <p>Anda login sebagai: <strong><?php echo $_SESSION['role']; ?></strong></p>
    <hr>
    <h3>Menu Navigasi</h3>
    <ul>
        <li><a href="#">Kelola Kategori</a></li>
        <li><a href="#">Kelola User</a></li>
        <li><a href="logout.php">Keluar (Logout)</a></li>
    </ul>
</body>
</html>
