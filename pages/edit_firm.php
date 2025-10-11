<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id'])) { header("Location: /login.php"); exit; }
$firm_id = $_GET['id'];
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firm_name = trim($_POST['firm_name']);
    if (empty($firm_name)) { $error_message = "Firma adı boş bırakılamaz."; } else {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM Bus_Company WHERE name = ? AND id != ?");
            $stmt_check->execute([$firm_name, $firm_id]);
            if ($stmt_check->fetch()) { $error_message = "Bu isimde başka bir firma zaten mevcut."; } else {
                $stmt = $pdo->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
                $stmt->execute([$firm_name, $firm_id]);
                $_SESSION['flash_success'] = "Firma adı başarıyla güncellendi!";
                header("Location: manage_firms.php"); exit;
            }
        } catch (PDOException $e) { $error_message = "Güncelleme sırasında hata: " . $e->getMessage(); }
    }
}
require '../layouts/header.php';
$stmt = $pdo->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$stmt->execute([$firm_id]);
$firm = $stmt->fetch();
if (!$firm) { header("Location: manage_firms.php"); exit; }
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h2 class="mb-0">Firma Düzenle</h2></div>
            <div class="card-body">
                <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3"><label for="firm_name" class="form-label">Firma Adı:</label><input type="text" class="form-control" id="firm_name" name="firm_name" value="<?= htmlspecialchars($firm['name']) ?>" required></div>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <a href="manage_firms.php" class="btn btn-secondary">İptal</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>