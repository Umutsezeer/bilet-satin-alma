<?php
session_start();
require '../includes/db.php';
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company_admin') { header("Location: /login.php"); exit; }
$company_id = $_SESSION['firm_id'];
$success_message = ''; $error_message = '';
if (isset($_SESSION['flash_success'])) { $success_message = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }

// KUPON SİLME (Sadece kendi firmasına ait olanı)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $coupon_id_to_delete = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = ? AND company_id = ?");
    try { $stmt->execute([$coupon_id_to_delete, $company_id]); $_SESSION['flash_success'] = "Kupon başarıyla silindi."; header("Location: firm_coupons.php"); exit;
    } catch (PDOException $e) { $error_message = "Kupon silinirken bir hata oluştu: " . $e->getMessage(); }
}

// YENİ KUPON EKLEME (Sadece kendi firmasına)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim(strtoupper($_POST['code']));
    $discount = trim($_POST['discount']);
    $usage_limit = trim($_POST['usage_limit']);
    $expiring_date = trim($_POST['expiring_date']);
    if (empty($code) || empty($discount) || empty($usage_limit) || empty($expiring_date)) { $error_message = "Lütfen tüm alanları doldurun."; }
    else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Coupons WHERE code = ?"); $stmt->execute([$code]);
        if ($stmt->fetchColumn() > 0) { $error_message = "Bu kupon kodu zaten mevcut."; }
        else {
            $coupon_id = generate_uuid();
            $stmt = $pdo->prepare("INSERT INTO Coupons (id, code, discount, usage_limit, expiring_date, company_id) VALUES (?, ?, ?, ?, ?, ?)");
            try { $stmt->execute([$coupon_id, $code, $discount, $usage_limit, $expiring_date, $company_id]); $success_message = "Yeni firma kuponu başarıyla eklendi!";
            } catch (PDOException $e) { $error_message = "Kupon eklenirken bir hata oluştu: " . $e->getMessage(); }
        }
    }
}
require '../layouts/header.php';
$stmt_firm = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = ?"); $stmt_firm->execute([$company_id]);
$firm_name = $stmt_firm->fetchColumn();

$stmt = $pdo->prepare("SELECT *, expiring_date as expiry_date FROM Coupons WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$coupons = $stmt->fetchAll();
?>
<h1 class="mb-4"><?= htmlspecialchars($firm_name ?? 'Firma') ?> - Kupon Yönetimi</h1>
<p><a href="firm_trips.php">« Sefer Yönetimine Dön</a></p><hr>
<?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

<div class="card mb-4 shadow-sm">
    <div class="card-header"><h2>Yeni Firma Kuponu Ekle</h2></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <div class="col-md-6"><label for="code" class="form-label">Kupon Kodu:</label><input type="text" class="form-control" id="code" name="code" required></div>
            <div class="col-md-6"><label for="discount" class="form-label">İndirim Oranı (%):</label><input type="number" class="form-control" id="discount" name="discount" step="0.01" min="1" max="100" required></div>
            <div class="col-md-6"><label for="usage_limit" class="form-label">Kullanım Limiti:</label><input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1" required></div>
            <div class="col-md-6"><label for="expiring_date" class="form-label">Son Kullanma Tarihi:</label><input type="date" class="form-control" id="expiring_date" name="expiring_date" required></div>
            <div class="col-12"><button type="submit" name="add_coupon" class="btn btn-primary">Ekle</button></div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-header"><h2>Mevcut Firma Kuponları</h2></div>
    <div class="card-body">
        <?php if (count($coupons) > 0): ?>
            <div class="table-responsive"><table class="table table-striped table-hover">
                <thead class="table-light"><tr><th>Kod</th><th>İndirim</th><th>Limit</th><th>Son Tarih</th><th class="text-end">İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><?= htmlspecialchars($coupon['code']) ?></td>
                        <td>%<?= htmlspecialchars($coupon['discount']) ?></td>
                        <td><?= htmlspecialchars($coupon['usage_limit']) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y', strtotime($coupon['expiry_date']))) ?></td>
                        <td class="text-end"><a href="?action=delete&id=<?= $coupon['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?');">Sil</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?><p class="text-center">Bu firmaya ait kayıtlı kupon bulunmuyor.</p><?php endif; ?>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>