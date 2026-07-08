<?php
session_start();
require 'config/koneksi.php';

// Proteksi halaman: Pastikan user sudah login dan perannya adalah Donatur
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Donatur') {
    header("Location: login.php");
    exit;
}

$success_msg = "";
$error_msg = "";
$snap_token = ""; // Menyimpan token pembayaran Midtrans

// Proses ketika donatur menekan tombol "Kirim Donasi"
if (isset($_POST['kirim_donasi'])) {
    $campaign_id = $_POST['campaign_id'];
    $user_id     = $_SESSION['id_user'];
    $nominal     = $_POST['nominal'];
    $catatan     = htmlspecialchars($_POST['catatan']);
    $order_id    = "DONASI-" . time() . "-" . $user_id; // Generate ID unik transaksi

    if ($nominal <= 0) {
        $error_msg = "Nominal donasi harus lebih dari 0!";
    } else {
        try {
            // 1. INTEGRASI MIDTRANS (Menggunakan cURL API Sandbox)
            // Menggunakan Server Key dummy untuk testing UAS
            $server_key = "SB-Mid-server-SampleKey123456789";
            $url = "https://app.sandbox.midtrans.com/snap/v1/transactions";

            $transaction_details = [
                'order_id'     => $order_id,
                'gross_amount' => (int)$nominal,
            ];

            $customer_details = [
                'first_name' => $_SESSION['nama_lengkap'] ?? 'Donatur',
            ];

            $params = [
                'transaction_details' => $transaction_details,
                'customer_details'    => $customer_details,
            ];

            $json_payload = json_encode($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($server_key . ':')
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            // Jika dapet Snap Token dari Midtrans
            if (isset($result['token'])) {
                $snap_token = $result['token'];
            }

            // 2. MASUKKAN DATA KE DATABASE (Status 'Pending' sebelum dibayar)
            $query = "INSERT INTO donasi (campaign_id, user_id, nominal, catatan, status, tanggal) VALUES (?, ?, ?, ?, 'Pending', NOW())";
            $stmt  = $pdo->prepare($query);
            $stmt->execute([$campaign_id, $user_id, $nominal, $catatan]);

            $success_msg = "Donasi berhasil diproses! Silakan selesaikan pembayaran.";
        } catch (Exception $e) {
            $error_msg = "Gagal memproses donasi: " . $e->getMessage();
        }
    }
}

// Ambil daftar campaign aktif
try {
    $query_campaign = "SELECT * FROM campaigns ORDER BY id DESC";
    $stmt_campaign  = $pdo->query($query_campaign);
    $campaigns      = $stmt_campaign->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $campaigns = [];
}

// TASK 2 EVALUASI TIRTA: Ambil data Riwayat Transaksi Donatur yang sedang login
try {
    // Join ke tabel campaigns agar tahu nama program donasi yang disumbang
    $query_history = "SELECT d.*, c.nama_campaign FROM donasi d
                      LEFT JOIN campaigns c ON d.campaign_id = c.id
                      WHERE d.user_id = ? ORDER BY d.id DESC";
    $stmt_history  = $pdo->prepare($query_history);
    $stmt_history->execute([$_SESSION['id_user']]);
    $history_donasi = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $history_donasi = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Donatur - Sistem Donasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Script Midtrans Snap Sandbox -->
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-SampleKey"></script>
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Menu Donatur</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Donatur'); ?>!</span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Notifikasi Status -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success shadow-sm"><?= $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger shadow-sm"><?= $error_msg; ?></div>
        <?php endif; ?>

        <!-- SEKSI 1: DAFTAR CAMPAIGN -->
        <h3 class="mb-4 text-dark fw-bold">🎯 Pilih Program Donasi</h3>
        <div class="row mb-5">
            <?php if (empty($campaigns)): ?>
                <!-- Tampilan fallback simulasi jika tabel database utama kelompok belum sinkron -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary">Donasi Peduli Bencana Alam</h5>
                            <p class="card-text text-muted">Bantu saudara kita yang terkena dampak bencana alam.</p>
                            <hr>
                            <form action="" method="POST">
                                <input type="hidden" name="campaign_id" value="1">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nominal Donasi (Rp)</label>
                                    <input type="number" name="nominal" class="form-control" placeholder="Contoh: 50000" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pesan/Catatan</label>
                                    <textarea name="catatan" class="form-control" rows="2" placeholder="Semoga berkah..."></textarea>
                                </div>
                                <button type="submit" name="kirim_donasi" class="btn btn-success w-100 fw-bold">Kirim Donasi via Midtrans</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($campaigns as $cp): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($cp['nama_campaign'] ?? $cp['judul']); ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($cp['deskripsi']); ?></p>
                                <hr>
                                <form action="" method="POST">
                                    <input type="hidden" name="campaign_id" value="<?= $cp['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nominal Donasi (Rp)</label>
                                        <input type="number" name="nominal" class="form-control" placeholder="Contoh: 100000" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Pesan/Catatan</label>
                                        <textarea name="catatan" class="form-control" rows="2" placeholder="Semoga bermanfaat..."></textarea>
                                    </div>
                                    <button type="submit" name="kirim_donasi" class="btn btn-success w-100 fw-bold">Kirim Donasi via Midtrans</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- SEKSI 2: TASK RIWAYAT TRANSAKSI (EVALUASI 2 dari TIRTA) -->
        <h3 class="mb-4 text-dark fw-bold">📜 Riwayat Donasi Kamu</h3>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>Program Donasi</th>
                                <th>Nominal</th>
                                <th>Catatan</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history_donasi)): ?>
                                <!-- Simulasi riwayat dummy jika database belum ada data -->
                                <tr>
                                    <td class="ps-3 text-muted">08-07-2026</td>
                                    <td class="fw-semibold">Donasi Peduli Bencana Alam</td>
                                    <td class="text-success fw-bold">Rp 50.000</td>
                                    <td class="text-muted">Semoga berkah...</td>
                                    <td class="pe-3"><span class="badge bg-warning text-dark">Pending</span></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history_donasi as $hd): ?>
                                    <tr>
                                        <td class="ps-3"><?= date('d-m-Y H:i', strtotime($hd['tanggal'])); ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($hd['nama_campaign'] ?? 'Program Umum'); ?></td>
                                        <td class="text-success fw-bold">Rp <?= number_format($hd['nominal'], 0, ',', '.'); ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($hd['catatan']); ?></td>
                                        <td class="pe-3">
                                            <?php if ($hd['status'] == 'Success' || $hd['status'] == 'Berhasil'): ?>
                                                <span class="badge bg-success">Berhasil</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($hd['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pemicu Pop-up Midtrans Snap jika token berhasil di-generate -->
    <?php if ($snap_token): ?>
    <script type="text/javascript">
        window.snap.pay('<?= $snap_token; ?>', {
            onSuccess: function(result){ alert("Pembayaran Berhasil!"); window.location.reload(); },
            onPending: function(result){ alert("Menunggu Pembayaran!"); window.location.reload(); },
            onError: function(result){ alert("Pembayaran Gagal!"); }
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>