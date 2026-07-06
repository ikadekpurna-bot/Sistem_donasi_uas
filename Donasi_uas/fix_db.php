<?php
require 'config/koneksi.php';
try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute(['$2y$10$R0HgqO1H/4BaDNhaEX/vmeCiPVKIFiOQplq1U.jGI/NYiqUEE7KLy', 'adi@admin.com']);
    echo "<h1>Perbaikan Berhasil!</h1>";
    echo "<p>Password admin telah direset menjadi <b>admin123</b></p>";
    echo "<a href='login.php'>Kembali ke Login</a>";
} catch(Exception $e) {
    echo "Gagal: " . $e->getMessage();
}
?>
