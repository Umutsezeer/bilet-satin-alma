<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || !isset($_GET['id'])) { header("Location: /login.php"); exit; }
$admin_id_to_edit = $_GET['id'];
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']); $email = trim($_POST['email']);
    $firm_id_to_assign = trim($_POST['firm_id']); $password = trim($_POST['password']);
    if (empty($fullname) || empty($email) || empty($firm_id_to_assign)) { $error_message = "Ad Soyad, E-posta ve Firma alanları zorunludur."; }
    else {
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ? AND id != ?"); $stmt->execute([$email, $admin_id_to_edit]);
        if ($stmt->fetch()) { $error_message = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor."; }
        else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE Users SET full_name = ?, email = ?, company_id = ?, password = ? WHERE id = ?";
                $params = [$fullname, $email, $firm_id_to_assign, $hashed_password, $admin_id_to_edit];
            } else {
                $sql = "UPDATE Users SET full_name = ?, email = ?, company_id = ? WHERE id = ?";
                $params = [$fullname, $email, $firm_id_to_assign, $admin_id_to_edit];
            }
            try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $_SESSION['flash_success'] = "Firma admini başarıyla güncellendi!"; header("Location: manage_firm_admins.php"); exit;
            } catch (PDOException $e) { $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage(); }
        }
    }
}
require '../layouts/header.php';
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = ? AND role = 'company_admin'");
$stmt->execute([$admin_id_to_edit]);
$firm_admin = $stmt->fetch();
if (!$firm_admin) { header("Location: manage_firm_admins.php"); exit; }
$firms_stmt = $pdo->query("SELECT id, name FROM Bus_Company ORDER BY name ASC");
$all_firms = $firms_stmt->fetchAll();
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h2 class="mb-0">Firma Admini Düzenle</h2></div>
            <div class="card-body">
                <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3"><label for="fullname" class="form-label">Ad Soyad:</label><input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($firm_admin['full_name']) ?>" required></div>
                    <div class="mb-3"><label for="email" class="form-label">E-posta:</label><input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($firm_admin['email']) ?>" required></div>
                    <div class="mb-3"><label for="firm_id" class="form-label">Atanacak Firma:</label><select class="form-select" id="firm_id" name="firm_id" required><?php foreach ($all_firms as $firm): ?><option value="<?= $firm['id'] ?>" <?= ($firm['id'] == $firm_admin['company_id']) ? 'selected' : '' ?>><?= htmlspecialchars($firm['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="mb-3"><label for="password" class="form-label">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın):</label><input type="password" class="form-control" id="password" name="password"></div>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                    <a href="manage_firm_admins.php" class="btn btn-secondary">İptal</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>