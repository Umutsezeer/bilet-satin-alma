<?php
// includes/db.php

$db_file = __DIR__ . '/bilet_sistemi.db';

try {
    // Veritabanı dosyasına bağlan
    $pdo = new PDO('sqlite:' . $db_file);
    // Hata modunu istisna olarak ayarla
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Varsayılan getirme modunu birleştirici dizi olarak ayarla
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Bağlantı başarısız olursa hatayı göster ve işlemi durdur
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>