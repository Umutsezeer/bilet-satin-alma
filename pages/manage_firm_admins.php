<?php
session_start();
require '../includes/db.php';
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: /login.php"); exit; }
$success_message = ''; $error_message = '';
if (isset($_SESSION['flash_success'])) { $success_message = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $admin_id_to_delete = $_GET['id'];
    if ($admin_id_to_delete == $_SESSION['user_id']) { $error_message = "Kendi hesabınızı silemezsiniz."; }
    else {
        $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ? AND role = 'company_admin'");
        try { $stmt->execute([$admin_id_to_delete]); $_SESSION['flash_success'] = "Firma admini başarıyla silindi."; header("Location: manage_firm_admins.php"); exit;
        } catch (PDOException $e) { $error_message = "Kullanıcı silinirken bir hata oluştu: " . $e->getMessage(); }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_firm_admin'])) {
    $fullname = trim($_POST['fullname']); $email = trim($_POST['email']);
    $password = trim($_POST['password']); $firm_id_to_assign = trim($_POST['firm_id']);
    if (empty($fullname) || empty($email) || empty($password) || empty($firm_id_to_assign)) { $error_message = "Lütfen tüm alanları doldurun."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error_message = "Lütfen geçerli bir e-posta adresi girin."; }
    else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ?"); $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) { $error_message = "Bu e-posta adresi zaten kayıtlı."; }
        else {
            $user_id = generate_uuid(); $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (id, full_name, email, password, role, company_id) VALUES (?, ?, ?, ?, 'company_admin', ?)";
            try { $stmt = $pdo->prepare($sql); $stmt->execute([$user_id, $fullname, $email, $hashed_password, $firm_id_to_assign]); $success_message = "Firma admini başarıyla oluşturuldu!";
            } catch (PDOException $e) { $error_message = "Kullanıcı oluşturulurken bir hata oluştu: " . $e->getMessage(); }
        }
    }
}
require '../layouts/header.php';
$firms_stmt = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC");
$all_firms = $firms_stmt->fetchAll();
$stmt = $pdo->query("SELECT u.id, u.full_name, u.email, f.name as firm_name FROM Users u LEFT JOIN Bus_Company f ON u.company_id = f.id WHERE u.role = 'company_admin' ORDER BY u.full_name ASC");
$firm_admins = $stmt->fetchAll();
?>
<h1 class="mb-4">Firma Admin Yönetimi</h1>
<p><a href="admin_panel.php">« Admin Paneline Dön</a></p><hr>
<?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header"><h2>Yeni Firma Admini Ekle</h2></div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <div class="col-md-6"><label for="fullname" class="form-label">Ad Soyad:</label><input type="text" class="form-control" id="fullname" name="fullname" required></div>
            <div class="col-md-6"><label for="email" class="form-label">E-posta:</label><input type="email" class="form-control" id="email" name="email" required></div>
            <div class="col-md-6"><label for="password" class="form-label">Şifre:</label><input type="password" class="form-control" id="password" name="password" required></div>
            <div class="col-md-6"><label for="firm_id" class="form-label">Atanacak Firma:</label><select class="form-select" id="firm_id" name="firm_id" required><option value="">-- Firma Seçin --</option><?php foreach ($all_firms as $firm): ?><option value="<?= $firm['id'] ?>"><?= htmlspecialchars($firm['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><button type="submit" name="add_firm_admin" class="btn btn-primary">Oluştur ve Ata</button></div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-header"><h2>Mevcut Firma Adminleri</h2></div>
    <div class="card-body">
        <?php if (count($firm_admins) > 0): ?>
            <div class="table-responsive"><table class="table table-striped table-hover align-middle">
                <thead class="table-light"><tr><th>Ad Soyad</th><th>E-posta</th><th>Atandığı Firma</th><th class="text-end">İşlemler</th></tr></thead>
                <tbody><?php foreach ($firm_admins as $admin): ?><tr><td><?= htmlspecialchars($admin['full_name']) ?></td><td><?= htmlspecialchars($admin['email']) ?></td><td><?= htmlspecialchars($admin['firm_name'] ?? '...') ?></td><td class="text-end"><a href="edit_firm_admin.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a> <a href="?action=delete&id=<?= $admin['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Sil</a></td></tr><?php endforeach; ?></tbody>
            </table></div>
        <?php else: ?><p class="text-center">Sistemde kayıtlı firma admini bulunmuyor.</p><?php endif; ?>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>