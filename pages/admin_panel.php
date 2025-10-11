<?php
session_start();
require '../includes/db.php';
// Güvenlik kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /login.php");
    exit;
}
require '../layouts/header.php';
?>

<h1 class="mb-4">Admin Paneli</h1>

<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="mb-0">Yönetim Menüsü</h2>
    </div>
    <div class="list-group list-group-flush">
        <a href="manage_firms.php" class="list-group-item list-group-item-action">Firma Yönetimi</a>
        <a href="manage_firm_admins.php" class="list-group-item list-group-item-action">Firma Admin Yönetimi</a>
        <a href="manage_coupons.php" class="list-group-item list-group-item-action">Genel Kupon Yönetimi</a>
    </div>
</div>

<?php require '../layouts/footer.php'; ?>