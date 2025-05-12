<?php
session_start();
session_unset(); // hapus semua variabel session
session_destroy(); // hancurkan session

// Redirect ke halaman login (bisa juga ke divisi.php, tapi akan butuh login lagi)
header("Location: login.php");
exit();
