<?php
session_start();
require '../includes/db.php';
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: /login.php"); exit; }
$success_message = ''; $error_message = '';
if (isset($_SESSION['flash_success'])) { $success_message = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $firm_id_to_delete = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Bus_Company WHERE id = ?");
    try { $stmt->execute([$firm_id_to_delete]); $_SESSION['flash_success'] = "Firma başarıyla silindi."; header("Location: manage_firms.php"); exit;
    } catch (PDOException $e) { $error_message = "Firma silinirken bir hata oluştu: " . $e->getMessage(); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_firm'])) {
    $firm_name = trim($_POST['firm_name']);
    if (empty($firm_name)) { $error_message = "Firma adı boş bırakılamaz."; } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Bus_Company WHERE name = ?");
        $stmt->execute([$firm_name]);
        if ($stmt->fetchColumn() > 0) { $error_message = "Bu isimde bir firma zaten mevcut."; } else {
            $firm_id = generate_uuid();
            $stmt = $pdo->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)");
            try { $stmt->execute([$firm_id, $firm_name]); $success_message = "Yeni firma başarıyla eklendi!"; }
            catch (PDOException $e) { $error_message = "Firma eklenirken bir hata oluştu: " . $e->getMessage(); }
        }
    }
}
require '../layouts/header.php';
$stmt = $pdo->query("SELECT * FROM Bus_Company ORDER BY name ASC");
$firms = $stmt->fetchAll();
?>
<h1 class="mb-4">Firma Yönetimi</h1>
<p><a href="admin_panel.php">« Admin Paneline Dön</a></p><hr>
<?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header"><h2>Yeni Firma Ekle</h2></div>
    <div class="card-body"><form method="POST" class="row g-3 align-items-end"><div class="col"><label for="firm_name" class="form-label">Firma Adı:</label><input type="text" class="form-control" id="firm_name" name="firm_name" required></div><div class="col-auto"><button type="submit" name="add_firm" class="btn btn-primary">Ekle</button></div></form></div>
</div>
<div class="card shadow-sm">
    <div class="card-header"><h2>Mevcut Firmalar</h2></div>
    <div class="card-body">
        <?php if (count($firms) > 0): ?>
            <div class="table-responsive"><table class="table table-striped table-hover align-middle">
                <thead class="table-light"><tr><th>Firma Adı</th><th class="text-end">İşlemler</th></tr></thead>
                <tbody><?php foreach ($firms as $firm): ?><tr><td><?= htmlspecialchars($firm['name']) ?></td><td class="text-end"><a href="edit_firm.php?id=<?= $firm['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a> <a href="?action=delete&id=<?= $firm['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?');">Sil</a></td></tr><?php endforeach; ?></tbody>
            </table></div>
        <?php else: ?><p class="text-center">Sistemde kayıtlı firma bulunmuyor.</p><?php endif; ?>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>