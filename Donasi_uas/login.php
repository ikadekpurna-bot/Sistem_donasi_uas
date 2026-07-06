<?php
session_start();
require 'config/koneksi.php';
require 'core/security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = escape_html($_POST['email']);
    $password_input = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password_input, $user['password'])) {
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

        if ($user['role'] == 'Admin') header("Location: dashboard_admin.php");
        else if ($user['role'] == 'Donatur') header("Location: dashboard_donatur.php");
        else if ($user['role'] == 'Campaigner') header("Location: dashboard_campaigner.php");
        else if ($user['role'] == 'Verifikator') header("Location: dashboard_verifikator.php");
        exit;
    } else {
        echo "<script>alert('Email atau Password salah!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
    <h2>Login Sistem</h2>
    <form method="POST" action="">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
