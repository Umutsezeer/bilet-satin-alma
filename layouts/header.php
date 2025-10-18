<?php

header('X-Frame-Options: DENY');

if ($_SERVER['SERVER_NAME'] != 'localhost') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma Platformu</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">Bilet Platformu</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
            <a class="nav-link" href="/">Ana Sayfa</a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                <li class="nav-item"><a class="nav-link" href="/pages/my_tickets.php">Biletlerim</a></li>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'company_admin'): ?>
                <li class="nav-item"><a class="nav-link" href="/pages/firm_trips.php">Sefer Yönetimi</a></li>
                <li class="nav-item"><a class="nav-link" href="/pages/firm_coupons.php">Kupon Yönetimi</a></li>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="/pages/admin_panel.php">Admin Paneli</a></li>
            <?php endif; ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Hoş geldin, <?= htmlspecialchars(explode(' ', $_SESSION['fullname'] ?? '')[0]) ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'company_admin')): ?>
                    <li><a class="dropdown-item" href="/pages/profile.php">Profilim</a></li>
                    <li><a class="dropdown-item" href="/pages/add_credit.php">Kredi Yükle</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item disabled" href="#">Rol: <?= htmlspecialchars($_SESSION['role']) ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/logout.php">Çıkış Yap</a></li>
              </ul>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/register.php">Kayıt Ol</a></li>
            <li class="nav-item"><a class="nav-link" href="/login.php">Giriş Yap</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container mt-4 mb-5">