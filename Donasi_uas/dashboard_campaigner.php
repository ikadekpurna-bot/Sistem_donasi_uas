<?php
session_start();
require 'config/koneksi.php';
require 'core/security.php';

// Proteksi Halaman: Pastikan hanya user dengan role 'Campaigner' yang bisa mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Campaigner') {
    header("Location: login.php"); // Sesuaikan dengan nama file login tim Anda
    exit;
}

$id_user = $_SESSION['id_user'];
$nama_campaigner = $_SESSION['nama_lengkap'];

// ==========================================
// 1. PROSES SUBMIT KAMPANYE BARU
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajukan_kampanye'])) {
    $judul        = escape_html($_POST['judul']);
    $id_kategori  = intval($_POST['id_kategori']);
    $deskripsi    = escape_html($_POST['deskripsi']);
    $target_dana  = floatval($_POST['target_dana']);
    
    // Proses Upload Gambar Banner
    $nama_gambar  = $_FILES['gambar_banner']['name'];
    $tmp_gambar   = $_FILES['gambar_banner']['tmp_name'];
    
    // Pengaturan folder upload (Pastikan folder 'uploads/' sudah dibuat)
    $ekstensi     = pathinfo($nama_gambar, PATHINFO_EXTENSION);
    $nama_baru    = "banner_" . time() . "." . $ekstensi;
    $target_path  = "uploads/" . $nama_baru;

    if (move_uploaded_file($tmp_gambar, $target_path)) {
        // Query Simpan Kampanye dengan status awal 'Pending'
        $stmt = $pdo->prepare("INSERT INTO kampanye (id_user, id_kategori, judul, deskripsi, target_dana, gambar_banner, status_kampanye) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        
        if ($stmt->execute([$id_user, $id_kategori, $judul, $deskripsi, $target_dana, $nama_baru])) {
            echo "<script>alert('Kampanye berhasil diajukan! Menunggu verifikasi.'); window.location='dashboard_campaigner.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan ke database.');</script>";
        }
    } else {
        echo "<script>alert('Gagal mengupload gambar banner.');</script>";
    }
}

// ==========================================
// 2. AMBIL DATA UNTUK VIEW (Kategori & Kampanye Saya)
// ==========================================
// Ambil daftar kategori untuk dropdown form
$kategori_stmt = $pdo->query("SELECT * FROM kategori");
$daftar_kategori = $kategori_stmt->fetchAll();

// Ambil list kampanye milik campaigner saat ini + hitung total dana yang terkumpul dari tabel transaksi
$query_kampanye = "
    SELECT k.*, cat.nama_kategori,
           COALESCE(SUM(t.nominal), 0) AS total_terkumpul
    FROM kampanye k
    JOIN kategori cat ON k.id_kategori = cat.id_kategori
    LEFT JOIN transaksi t ON k.id_kampanye = t.id_kampanye AND t.status_pembayaran = 'Success'
    WHERE k.id_user = ?
    GROUP BY k.id_kampanye
    ORDER BY k.created_at DESC
";
$kampanye_stmt = $pdo->prepare($query_kampanye);
$kampanye_stmt->execute([$id_user]);
$status_kampanye_saya = $kampanye_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Campaigner</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 30px; background-color: #f9f9f9; }
        .header { background: #34495e; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        .logout-btn { color: #fff; background: #e74c3c; padding: 8px 15px; text-decoration: none; border-radius: 4px; }
        .container { display: flex; gap: 30px; }
        .box-form { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        .box-data { flex: 2; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; color: white; font-weight: bold; }
        .Pending { background-color: #f39c12; }
        .Active { background-color: #2ecc71; }
        .Rejected { background-color: #e74c3c; }
        .Completed { background-color: #3498db; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Selamat Datang, <?= htmlspecialchars($nama_campaigner); ?> (Campaigner)</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <!-- FORM PENGALIRAN KAMPANYE BARU -->
        <div class="box-form">
            <h3>Buat Pengajuan Kampanye</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Judul Kampanye:</label>
                    <input type="text" name="judul" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori:</label>
                    <select name="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($daftar_kategori as $kat): ?>
                            <option value="<?= $kat['id_kategori']; ?>"><?= $kat['nama_kategori']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Deskripsi Cerita:</label>
                    <textarea name="deskripsi" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label>Target Dana (Rp):</label>
                    <input type="number" name="target_dana" min="10000" required>
                </div>

                <div class="form-group">
                    <label>Gambar Banner:</label>
                    <input type="file" name="gambar_banner" accept="image/*" required>
                </div>

                <button type="submit" name="ajukan_kampanye" style="background:#27ae60; color:white; border:none; padding:10px 15px; cursor:pointer; width:100%; border-radius:4px;">Ajukan Sekarang</button>
            </form>
        </div>

        <!-- DAFTAR KAMPANYE SAYA -->
        <div class="box-data">
            <h3>Riwayat Kampanye Anda</h3>
            <table>
                <thead>
                    <tr>
                        <th>Banner</th>
                        <th>Detail Kampanye</th>
                        <th>Target Dana</th>
                        <th>Terkumpul</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($status_kampanye_saya)): ?>
                        <tr><td colspan="5" style="text-align:center;">Anda belum memiliki kampanye.</td></tr>
                    <?php else: ?>
                        <?php foreach($status_kampanye_saya as $row): ?>
                        <tr>
                            <td>
                                <img src="uploads/<?= $row['gambar_banner']; ?>" width="80" alt="banner" style="border-radius:4px;">
                            </td>
                            <td>
                                <strong><?= $row['judul']; ?></strong><br>
                                <small style="color:gray;">Kategori: <?= $row['nama_kategori']; ?></small>
                            </td>
                            <td>Rp <?= number_format($row['target_dana'], 0, ',', '.'); ?></td>
                            <td>Rp <?= number_format($row['total_terkumpul'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?= $row['status_kampanye']; ?>">
                                    <?= $row['status_kampanye']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>