<?php
require __DIR__ . '/core/middleware.php';
require __DIR__ . '/config/koneksi.php';

wajib_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'] === 'Success' ? 'Success' : 'Failed';
    $id_user = $_SESSION['id_user'];

    try {
        $stmt = $pdo->prepare("UPDATE transaksi SET status_pembayaran = ? WHERE order_id = ? AND id_user = ?");
        $stmt->execute([$status, $order_id, $id_user]);
        
        echo json_encode(['success' => true, 'message' => 'Status transaksi berhasil diperbarui.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui database: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap atau request tidak valid.']);
}
?>
