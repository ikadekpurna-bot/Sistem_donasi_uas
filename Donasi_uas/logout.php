<?php
session_start();
session_unset();
session_destroy();

echo "<script>alert('Anda telah berhasil keluar.'); window.location='login.php';</script>";
exit;
?>
