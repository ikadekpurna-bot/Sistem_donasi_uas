<?php
session_start();

function wajib_login() {
    if (!isset($_SESSION['id_user'])) {
        header("Location: login.php");
        exit;
    }
}

function cek_role($role_yang_diizinkan) {
    if ($_SESSION['role'] !== $role_yang_diizinkan) {
        die("<h1>Akses Ditolak!</h1><p>Hanya $role_yang_diizinkan yang boleh mengakses halaman ini.</p>");
    }
}
?>
