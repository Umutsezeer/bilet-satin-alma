<?php
session_start();
require '../includes/db.php';

// Giriş yapmamış kullanıcıyı engelle
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Şifre güncelleme formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Lütfen tüm şifre alanlarını doldurun.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Yeni şifreler eşleşmiyor.";
    } else {
        // Mevcut şifreyi yeni 'Users' tablosundan çek
        $stmt = $pdo->prepare("SELECT password FROM Users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // Yeni şifreyi hash'le ve 'Users' tablosunu güncelle
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE Users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user_id]);
            $success_message = "Şifreniz başarıyla güncellendi!";
        } else {
            $error_message = "Mevcut şifreniz yanlış.";
        }
    }
}

require '../layouts/header.php';

// Kullanıcı bilgilerini yeni 'Users' tablosundan ve 'full_name' kolonundan çek
$stmt_info = $pdo->prepare("SELECT full_name, email, balance FROM Users WHERE id = ?");
$stmt_info->execute([$user_id]);
$user_info = $stmt_info->fetch();
?>

<h1 class="mb-4">Profil Bilgileri</h1>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="mb-0">Hesap Detayları</h2>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Ad Soyad:</strong> <?= htmlspecialchars($user_info['full_name']) ?></li>
                <li class="list-group-item"><strong>E-posta:</strong> <?= htmlspecialchars($user_info['email']) ?></li>
                <li class="list-group-item"><strong>Bakiye:</strong> <?= htmlspecialchars(number_format($user_info['balance'], 2)) ?> TL</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="mb-0">Şifre Değiştir</h2>
            </div>
            <div class="card-body">
                <?php if ($success_message): ?><div class="alert alert-success"><?= $success_message ?></div><?php endif; ?>
                <?php if ($error_message): ?><div class="alert alert-danger"><?= $error_message ?></div><?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Yeni Şifre</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-primary">Şifreyi Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require '../layouts/footer.php'; ?>