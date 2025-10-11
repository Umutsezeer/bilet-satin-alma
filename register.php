<?php
session_start();
require 'includes/db.php';

// Eğer kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

// UUID v4 oluşturmak için bir fonksiyon
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$error_message = ''; $success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = 'user';
    if (empty($fullname) || empty($email) || empty($password)) { $error_message = "Lütfen tüm alanları doldurun."; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error_message = "Lütfen geçerli bir e-posta adresi girin."; }
    else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) { $error_message = "Bu e-posta adresi zaten kullanılıyor."; }
        else {
            $user_id = generate_uuid();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (id, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([$user_id, $fullname, $email, $hashed_password, $role]);
                $success_message = "Kayıt başarılı! Giriş sayfasına yönlendiriliyorsunuz...";
                header("Refresh: 3; url=login.php");
            } catch (PDOException $e) { $error_message = "Kayıt sırasında bir hata oluştu: " . $e->getMessage(); }
        }
    }
}
require 'layouts/header.php';
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><h2 class="text-center mb-0">Kayıt Ol</h2></div>
            <div class="card-body">
                <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                <?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
                <?php if (empty($success_message)): ?>
                <form method="POST" action="register.php">
                    <div class="mb-3"><label for="fullname" class="form-label">Ad Soyad:</label><input type="text" class="form-control" id="fullname" name="fullname" required></div>
                    <div class="mb-3"><label for="email" class="form-label">E-posta Adresi:</label><input type="email" class="form-control" id="email" name="email" required></div>
                    <div class="mb-3"><label for="password" class="form-label">Şifre:</label><input type="password" class="form-control" id="password" name="password" required></div>
                    <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">Zaten bir hesabın var mı? <a href="login.php">Giriş Yap</a></div>
        </div>
    </div>
</div>
<?php require 'layouts/footer.php'; ?>