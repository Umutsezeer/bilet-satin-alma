<?php
// Oturumu başlat (mevcut oturuma erişmek için bu gereklidir)
session_start();

// Tüm session değişkenlerini bir kerede temizle
$_SESSION = array();

// Session'ı sunucudan tamamen sil
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>