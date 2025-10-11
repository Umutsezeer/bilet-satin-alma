<?php
session_start();
require 'includes/db.php';
if (isset($_SESSION['user_id'])) { header("Location: /index.php"); exit; }
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if (empty($email) || empty($password)) { $error_message = "Lütfen tüm alanları doldurun."; }
    else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] === 'company_admin') {
                $_SESSION['firm_id'] = $user['company_id'];
            }
            header("Location: index.php");
            exit;
        } else { $error_message = "E-posta veya şifre hatalı."; }
    }
}
require 'layouts/header.php';
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><h2 class="text-center mb-0">Giriş Yap</h2></div>
            <div class="card-body">
                <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                <form method="POST" action="login.php">
                    <div class="mb-3"><label for="email" class="form-label">E-posta Adresi:</label><input type="email" class="form-control" id="email" name="email" required></div>
                    <div class="mb-3"><label for="password" class="form-label">Şifre:</label><input type="password" class="form-control" id="password" name="password" required></div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
            </div>
            <div class="card-footer text-center">Hesabın yok mu? <a href="register.php">Kayıt Ol</a></div>
        </div>
    </div>
</div>
<?php require 'layouts/footer.php'; ?>