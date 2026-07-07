<?php
require __DIR__ . '/core/middleware.php';
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/core/security.php';

wajib_login();
cek_role('Campaigner');

$id_user = (int) $_SESSION['id_user'];
$nama_campaigner = $_SESSION['nama_lengkap'];

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS laporan_progres (
        id_laporan INT AUTO_INCREMENT PRIMARY KEY,
        id_kampanye INT NOT NULL,
        judul_laporan VARCHAR(150) NOT NULL,
        isi_laporan TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_kampanye) REFERENCES kampanye(id_kampanye) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ajukan_kampanye'])) {
        $judul       = escape_html($_POST['judul']);
        $id_kategori = (int) $_POST['id_kategori'];
        $deskripsi   = escape_html($_POST['deskripsi']);
        $target_dana = (float) $_POST['target_dana'];

        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0755, true);
        }

        $nama_gambar = $_FILES['gambar_banner']['name'];
        $tmp_gambar  = $_FILES['gambar_banner']['tmp_name'];
        $ekstensi    = pathinfo($nama_gambar, PATHINFO_EXTENSION);
        $nama_baru   = 'banner_' . time() . '.' . $ekstensi;
        $target_path = __DIR__ . '/uploads/' . $nama_baru;

        if (!empty($tmp_gambar) && move_uploaded_file($tmp_gambar, $target_path)) {
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

    if (isset($_POST['edit_kampanye'])) {
        $id_kampanye = (int) $_POST['id_kampanye'];
        $judul       = escape_html($_POST['judul']);
        $id_kategori = (int) $_POST['id_kategori'];
        $deskripsi   = escape_html($_POST['deskripsi']);
        $target_dana = (float) $_POST['target_dana'];

        $cek = $pdo->prepare("SELECT gambar_banner FROM kampanye WHERE id_kampanye = ? AND id_user = ?");
        $cek->execute([$id_kampanye, $id_user]);
        $kampanye_lama = $cek->fetch();

        $nama_baru = $kampanye_lama['gambar_banner'] ?? '';

        if (!empty($_FILES['gambar_banner']['name'])) {
            if (!is_dir(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0755, true);
            }

            $nama_gambar = $_FILES['gambar_banner']['name'];
            $tmp_gambar  = $_FILES['gambar_banner']['tmp_name'];
            $ekstensi    = pathinfo($nama_gambar, PATHINFO_EXTENSION);
            $nama_baru   = 'banner_' . time() . '.' . $ekstensi;
            $target_path = __DIR__ . '/uploads/' . $nama_baru;

            if (!move_uploaded_file($tmp_gambar, $target_path)) {
                echo "<script>alert('Gagal mengupload gambar banner baru.');</script>";
            }
        }

        $stmt = $pdo->prepare("UPDATE kampanye SET id_kategori = ?, judul = ?, deskripsi = ?, target_dana = ?, gambar_banner = ? WHERE id_kampanye = ? AND id_user = ?");
        if ($stmt->execute([$id_kategori, $judul, $deskripsi, $target_dana, $nama_baru, $id_kampanye, $id_user])) {
            echo "<script>alert('Kampanye berhasil diperbarui.'); window.location='dashboard_campaigner.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui kampanye.');</script>";
        }
    }

    if (isset($_POST['hapus_kampanye'])) {
        $id_kampanye = (int) $_POST['id_kampanye'];
        $stmt = $pdo->prepare("DELETE FROM kampanye WHERE id_kampanye = ? AND id_user = ?");
        if ($stmt->execute([$id_kampanye, $id_user])) {
            echo "<script>alert('Kampanye berhasil dihapus.'); window.location='dashboard_campaigner.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus kampanye.');</script>";
        }
    }

    if (isset($_POST['simpan_progres'])) {
        $id_kampanye  = (int) $_POST['id_kampanye'];
        $judul_laporan = escape_html($_POST['judul_laporan']);
        $isi_laporan   = escape_html($_POST['isi_laporan']);

        $cek = $pdo->prepare("SELECT id_kampanye FROM kampanye WHERE id_kampanye = ? AND id_user = ?");
        $cek->execute([$id_kampanye, $id_user]);
        if ($cek->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO laporan_progres (id_kampanye, judul_laporan, isi_laporan) VALUES (?, ?, ?)");
            if ($stmt->execute([$id_kampanye, $judul_laporan, $isi_laporan])) {
                echo "<script>alert('Laporan progres berhasil disimpan.'); window.location='dashboard_campaigner.php';</script>";
            } else {
                echo "<script>alert('Gagal menyimpan laporan progres.');</script>";
            }
        } else {
            echo "<script>alert('Kampanye yang dipilih tidak valid.');</script>";
        }
    }
}

$kategori_stmt = $pdo->query("SELECT * FROM kategori");
$daftar_kategori = $kategori_stmt->fetchAll();

$edit_kampanye = null;
if (isset($_GET['edit_id']) && !empty($_GET['edit_id'])) {
    $edit_stmt = $pdo->prepare("SELECT * FROM kampanye WHERE id_kampanye = ? AND id_user = ?");
    $edit_stmt->execute([(int) $_GET['edit_id'], $id_user]);
    $edit_kampanye = $edit_stmt->fetch();
}

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

$progress_stmt = $pdo->prepare("SELECT lp.*, k.judul FROM laporan_progres lp JOIN kampanye k ON lp.id_kampanye = k.id_kampanye WHERE k.id_user = ? ORDER BY lp.created_at DESC");
$progress_stmt->execute([$id_user]);
$daftar_progres = $progress_stmt->fetchAll();
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
        .container { display: flex; gap: 30px; flex-wrap: wrap; }
        .box-form { flex: 1; min-width: 320px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        .box-data { flex: 2; min-width: 360px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
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
        .btn { border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-submit { background: #27ae60; color: white; }
        .small { color: gray; font-size: 12px; }
        .card { border: 1px solid #eee; border-left: 4px solid #3498db; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Selamat Datang, <?= htmlspecialchars($nama_campaigner); ?> (Campaigner)</h2>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <div class="box-form">
            <h3><?= $edit_kampanye ? 'Edit Kampanye' : 'Buat Pengajuan Kampanye' ?></h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if ($edit_kampanye): ?>
                    <input type="hidden" name="edit_kampanye" value="1">
                    <input type="hidden" name="id_kampanye" value="<?= (int) $edit_kampanye['id_kampanye']; ?>">
                <?php else: ?>
                    <input type="hidden" name="ajukan_kampanye" value="1">
                <?php endif; ?>

                <div class="form-group">
                    <label>Judul Kampanye:</label>
                    <input type="text" name="judul" value="<?= $edit_kampanye ? htmlspecialchars($edit_kampanye['judul']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Kategori:</label>
                    <select name="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($daftar_kategori as $kat): ?>
                            <option value="<?= $kat['id_kategori']; ?>" <?= $edit_kampanye && $edit_kampanye['id_kategori'] == $kat['id_kategori'] ? 'selected' : ''; ?>><?= $kat['nama_kategori']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Deskripsi Cerita:</label>
                    <textarea name="deskripsi" rows="5" required><?= $edit_kampanye ? htmlspecialchars($edit_kampanye['deskripsi']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Target Dana (Rp):</label>
                    <input type="number" name="target_dana" min="10000" value="<?= $edit_kampanye ? htmlspecialchars($edit_kampanye['target_dana']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Gambar Banner:</label>
                    <input type="file" name="gambar_banner" accept="image/*" <?= $edit_kampanye ? '' : 'required'; ?>>
                    <?php if ($edit_kampanye): ?>
                        <div class="small">Biarkan kosong jika tidak ingin mengubah banner lama.</div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-submit" style="width:100%;"><?= $edit_kampanye ? 'Simpan Perubahan' : 'Ajukan Sekarang' ?></button>
                <?php if ($edit_kampanye): ?>
                    <a href="dashboard_campaigner.php" class="btn" style="background:#ecf0f1; color:#333; margin-top:10px; width:100%; text-align:center;">Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>

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
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($status_kampanye_saya)): ?>
                        <tr><td colspan="6" style="text-align:center;">Anda belum memiliki kampanye.</td></tr>
                    <?php else: ?>
                        <?php foreach ($status_kampanye_saya as $row): ?>
                        <tr>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($row['gambar_banner']); ?>" width="80" alt="banner" style="border-radius:4px;">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($row['judul']); ?></strong><br>
                                <small style="color:gray;">Kategori: <?= htmlspecialchars($row['nama_kategori']); ?></small>
                            </td>
                            <td>Rp <?= number_format($row['target_dana'], 0, ',', '.'); ?></td>
                            <td>Rp <?= number_format($row['total_terkumpul'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?= $row['status_kampanye']; ?>">
                                    <?= $row['status_kampanye']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="dashboard_campaigner.php?edit_id=<?= (int) $row['id_kampanye']; ?>" class="btn btn-edit">Edit</a>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus kampanye ini?');">
                                    <input type="hidden" name="hapus_kampanye" value="1">
                                    <input type="hidden" name="id_kampanye" value="<?= (int) $row['id_kampanye']; ?>">
                                    <button type="submit" class="btn btn-delete">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="container" style="margin-top:30px;">
        <div class="box-form">
            <h3>Update Laporan Progres Donasi</h3>
            <form method="POST" action="">
                <input type="hidden" name="simpan_progres" value="1">
                <div class="form-group">
                    <label>Pilih Kampanye:</label>
                    <select name="id_kampanye" required>
                        <option value="">-- Pilih Kampanye --</option>
                        <?php foreach ($status_kampanye_saya as $row): ?>
                            <option value="<?= (int) $row['id_kampanye']; ?>"><?= htmlspecialchars($row['judul']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Judul Laporan:</label>
                    <input type="text" name="judul_laporan" required>
                </div>
                <div class="form-group">
                    <label>Isi Laporan Progres:</label>
                    <textarea name="isi_laporan" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-submit" style="width:100%;">Simpan Laporan</button>
            </form>
        </div>

        <div class="box-data">
            <h3>Riwayat Laporan Progres</h3>
            <?php if (empty($daftar_progres)): ?>
                <div class="card">Belum ada laporan progres yang tersimpan.</div>
            <?php else: ?>
                <?php foreach ($daftar_progres as $laporan): ?>
                    <div class="card">
                        <strong><?= htmlspecialchars($laporan['judul_laporan']); ?></strong><br>
                        <span class="small">Kampanye: <?= htmlspecialchars($laporan['judul']); ?> | <?= date('d M Y H:i', strtotime($laporan['created_at'])); ?></span>
                        <p><?= nl2br(htmlspecialchars($laporan['isi_laporan'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>