<?php
require __DIR__ . '/core/middleware.php';
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/core/security.php';

wajib_login();
cek_role('Admin');

$total_users = 0;
$total_kampanye = 0;
$total_dana = 0;
$total_transaksi = 0;

try {
    $q_users = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $q_users->fetch()['total'] ?? 0;
} catch (Exception $e) {}

try {
    $q_kampanye = $pdo->query("SELECT COUNT(*) as total FROM kampanye");
    $total_kampanye = $q_kampanye->fetch()['total'] ?? 0;
} catch (Exception $e) {}

try {
    $q_dana = $pdo->query("SELECT SUM(nominal) as total FROM transaksi WHERE status_pembayaran = 'Success'");
    $total_dana = $q_dana->fetch()['total'] ?? 0;
} catch (Exception $e) {}

try {
    $q_transaksi = $pdo->query("SELECT COUNT(*) as total FROM transaksi");
    $total_transaksi = $q_transaksi->fetch()['total'] ?? 0;
} catch (Exception $e) {}

$recent_transactions = [];
try {
    $q_recent = $pdo->query("SELECT t.*, u.nama_lengkap, k.judul FROM transaksi t 
                             JOIN users u ON t.id_user = u.id_user 
                             JOIN kampanye k ON t.id_kampanye = k.id_kampanye 
                             ORDER BY t.id_transaksi DESC LIMIT 5");
    $recent_transactions = $q_recent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Admin - Sistem Donasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
        }
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
        }
        .sidebar-brand {
            padding: 24px;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-brand i {
            color: #38bdf8;
        }
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 24px;
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.2s ease;
        }
        .sidebar-menu li a:hover, .sidebar-menu li.active a {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
            border-left: 4px solid #38bdf8;
            padding-left: 20px;
        }
        .sidebar-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fca5a5;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.2s;
        }
        .logout-btn:hover {
            color: #ef4444;
        }
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
        }
        .header-title h1 {
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .header-title p {
            color: #64748b;
            margin: 4px 0 0 0;
            font-size: 14px;
        }
        .stat-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }
        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }
        .stat-info p {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            margin: 4px 0 0 0;
        }
        .bg-icon-emerald { background-color: #ecfdf5; color: #10b981; }
        .bg-icon-indigo { background-color: #e0e7ff; color: #6366f1; }
        .bg-icon-amber { background-color: #fef3c7; color: #d97706; }
        .bg-icon-cyan { background-color: #ecfeff; color: #0891b2; }
        
        .recent-section {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 28px;
            margin-top: 36px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .recent-section h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table {
            margin: 0;
        }
        .table th {
            font-weight: 600;
            color: #475569;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 16px;
        }
        .table td {
            padding: 16px;
            color: #334155;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #b45309;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .badge-failed {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-brand span, .sidebar-menu li a span, .sidebar-footer span {
                display: none;
            }
            .main-content {
                margin-left: 70px;
                padding: 24px;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-hand-holding-heart"></i>
            <span>PeduliKasih</span>
        </div>
        <ul class="sidebar-menu">
            <li class="active">
                <a href="dashboard_admin.php">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dasbor Utama</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fa-solid fa-tags"></i>
                    <span>Kelola Kategori</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fa-solid fa-users"></i>
                    <span>Kelola User</span>
                </a>
            </li>
            <li>
                <a href="#">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Verifikasi Donasi</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>Dasbor Admin</h1>
                <p>Selamat datang kembali, <?= escape_html($_SESSION['nama_lengkap']); ?>!</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= number_format($total_users); ?></h3>
                        <p>Total Pengguna</p>
                    </div>
                    <div class="stat-icon bg-icon-emerald">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= number_format($total_kampanye); ?></h3>
                        <p>Total Kampanye</p>
                    </div>
                    <div class="stat-icon bg-icon-indigo">
                        <i class="fa-solid fa-circle-nodes"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Rp <?= number_format($total_dana, 0, ',', '.'); ?></h3>
                        <p>Dana Terkumpul</p>
                    </div>
                    <div class="stat-icon bg-icon-amber">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= number_format($total_transaksi); ?></h3>
                        <p>Total Transaksi</p>
                    </div>
                    <div class="stat-icon bg-icon-cyan">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-section">
            <h2>
                <i class="fa-solid fa-clock-rotate-left"></i>
                Aktivitas Transaksi Terbaru
            </h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Donatur</th>
                            <th>Program Donasi</th>
                            <th>Nominal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi saat ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $rt): ?>
                                <tr>
                                    <td class="fw-semibold text-secondary"><?= escape_html($rt['order_id']); ?></td>
                                    <td><?= escape_html($rt['nama_lengkap']); ?></td>
                                    <td><?= escape_html($rt['judul']); ?></td>
                                    <td class="fw-bold text-success">Rp <?= number_format($rt['nominal'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($rt['status_pembayaran'] == 'Success'): ?>
                                            <span class="badge-success">Success</span>
                                        <?php elseif ($rt['status_pembayaran'] == 'Pending'): ?>
                                            <span class="badge-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="badge-failed">Failed</span>
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

</body>
</html>
