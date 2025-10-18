<?php
session_start();
require 'includes/db.php';

// Eğer kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit;
}

$error_message = '';
$max_attempts = 5; // Maksimum yanlış deneme sayısı
$lockout_time = 450; // Kilitleme süresi (saniye cinsinden, 450 saniye = 7.5 dakika)

// - Brute Force Önlemi -
// 1. Kullanıcı kilitli mi diye kontrol et
if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    $remaining_time = $_SESSION['lockout_time'] - time();
    $error_message = "Çok fazla hatalı deneme. Lütfen {$remaining_time} saniye sonra tekrar deneyin.";
} 
// 2. Form gönderildiyse ve kullanıcı kilitli değilse
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Lütfen tüm alanları doldurun.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 3. Giriş başarılıysa
        if ($user && password_verify($password, $user['password'])) {
            // Oturumu yenile (Session Fixation saldırısını engelle)
            session_regenerate_id(true);
            
            // Başarılı giriş sonrası deneme sayaçlarını temizle
            unset($_SESSION['login_attempts']);
            unset($_SESSION['lockout_time']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] === 'company_admin') {
                $_SESSION['firm_id'] = $user['company_id'];
            }
            header("Location: index.php");
            exit;
        } 
        // 4. Giriş başarısızsa
        else {
            // Hatalı deneme sayacını başlat veya artır
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            
            // Sayaç limite ulaştıysa, hesabı kilitle
            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['lockout_time'] = time() + $lockout_time; // Gelecekteki bir zamana kilitle
                $error_message = "Çok fazla hatalı deneme. Hesabınız 15 dakika süreyle kilitlenmiştir.";
            } else {
                $error_message = "E-posta veya şifre hatalı.";
            }
        }
    }
}

require 'layouts/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="text-center mb-0">Giriş Yap</h2>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['lockout_time']) || time() > $_SESSION['lockout_time']): ?>
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi:</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
            </div>
        </div>
    </div>
</div>

<?php require 'layouts/footer.php'; ?>