<?php
require __DIR__ . '/core/middleware.php';
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/core/security.php';

wajib_login();
cek_role('Donatur');

$success_msg = "";
$error_msg = "";
$snap_token = ""; 

if (isset($_POST['kirim_donasi'])) {
    $id_kampanye = (int)$_POST['id_kampanye'];
    $id_user     = $_SESSION['id_user'];
    $nominal     = (float)$_POST['nominal'];
    $order_id    = "DONASI-" . time() . "-" . $id_user;

    if ($nominal <= 0) {
        $error_msg = "Nominal donasi harus lebih dari 0!";
    } else {
        try {
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

            if (isset($result['token'])) {
                $snap_token = $result['token'];
            }

            $query = "INSERT INTO transaksi (order_id, id_kampanye, id_user, nominal, status_pembayaran, snap_token, created_at) 
                      VALUES (?, ?, ?, ?, 'Pending', ?, NOW())";
            $stmt  = $pdo->prepare($query);
            $stmt->execute([$order_id, $id_kampanye, $id_user, $nominal, $snap_token]);

            $success_msg = "Donasi berhasil diproses! Silakan selesaikan pembayaran.";
        } catch (Exception $e) {
            $error_msg = "Gagal memproses donasi: " . $e->getMessage();
        }
    }
}

try {
    $query_campaign = "SELECT * FROM kampanye WHERE status_kampanye = 'Active' ORDER BY id_kampanye DESC";
    $stmt_campaign  = $pdo->query($query_campaign);
    $campaigns      = $stmt_campaign->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $campaigns = [];
}

try {
    $query_history = "SELECT t.*, k.judul FROM transaksi t
                      LEFT JOIN kampanye k ON t.id_kampanye = k.id_kampanye
                      WHERE t.id_user = ? ORDER BY t.id_transaksi DESC";
                      
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
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-SampleKey"></script>
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Menu Donatur</a>
            <div class="d-flex">
                <span class="navbar-text text-white me-3">Halo, <?= escape_html($_SESSION['nama_lengkap']); ?>!</span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($success_msg): ?>
            <div class="alert alert-success shadow-sm"><?= $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger shadow-sm"><?= $error_msg; ?></div>
        <?php endif; ?>

        <h3 class="mb-4 text-dark fw-bold">🎯 Pilih Program Donasi</h3>
        <div class="row mb-5">
            <?php if (empty($campaigns)): ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">Belum ada program donasi aktif saat ini.</div>
                </div>
            <?php else: ?>
                <?php foreach ($campaigns as $cp): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($cp['gambar_banner'])): ?>
                                <img src="uploads/<?= htmlspecialchars($cp['gambar_banner']); ?>" class="card-img-top" alt="Banner Kampanye" style="height: 180px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <h5 class="card-title fw-bold text-primary"><?= escape_html($cp['judul']); ?></h5>
                                    <p class="card-text text-muted"><?= escape_html(substr($cp['deskripsi'], 0, 100)) . '...'; ?></p>
                                    <p class="card-text text-dark fw-semibold">Target: Rp <?= number_format($cp['target_dana'], 0, ',', '.'); ?></p>
                                </div>
                                <hr>
                                <form action="" method="POST">
                                    <input type="hidden" name="id_kampanye" value="<?= $cp['id_kampanye']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nominal Donasi (Rp)</label>
                                        <input type="number" name="nominal" class="form-control" placeholder="Contoh: 100000" required min="1000">
                                    </div>
                                    <button type="submit" name="kirim_donasi" class="btn btn-success w-100 fw-bold">Kirim Donasi via Midtrans</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h3 class="mb-4 text-dark fw-bold">📜 Riwayat Donasi Kamu</h3>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>Order ID</th>
                                <th>Program Donasi</th>
                                <th>Nominal</th>
                                <th class="pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history_donasi)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted p-4">Kamu belum pernah berdonasi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history_donasi as $hd): ?>
                                    <tr>
                                        <td class="ps-3"><?= date('d-m-Y H:i', strtotime($hd['created_at'])); ?></td>
                                        <td class="text-muted small"><?= escape_html($hd['order_id']); ?></td>
                                        <td class="fw-semibold"><?= escape_html($hd['judul'] ?? 'Program Umum'); ?></td>
                                        <td class="text-success fw-bold">Rp <?= number_format($hd['nominal'], 0, ',', '.'); ?></td>
                                        <td class="pe-3">
                                            <?php if ($hd['status_pembayaran'] == 'Success'): ?>
                                                <span class="badge bg-success">Berhasil</span>
                                            <?php elseif ($hd['status_pembayaran'] == 'Pending'): ?>
                                                <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Gagal</span>
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

    <?php if ($snap_token): ?>
    <script type="text/javascript">
        window.snap.pay('<?= $snap_token; ?>', {
            onSuccess: function(result){
                fetch('update_status_ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + encodeURIComponent(result.order_id) + '&status=Success'
                })
                .then(response => response.json())
                .then(data => {
                    alert("Pembayaran Berhasil!");
                    window.location.reload();
                })
                .catch(error => {
                    console.error("Error: ", error);
                    window.location.reload();
                });
            },
            onPending: function(result){
                alert("Menunggu Pembayaran!");
                window.location.reload();
            },
            onError: function(result){
                alert("Pembayaran Gagal!");
            }
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>