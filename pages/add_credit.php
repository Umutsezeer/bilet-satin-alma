<?php
session_start();
require '../includes/db.php';

// Güvenlik: Giriş yapmamış veya rolü 'admin' olan kullanıcılar bu sayfayı göremez.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    header("Location: /index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credit'])) {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    // Girilen tutar geçerli bir sayı mı ve pozitif mi diye kontrol et
    if ($amount === false || $amount <= 0) {
        $error_message = "Lütfen geçerli ve pozitif bir tutar girin.";
    } else {
        try {
            // Veritabanındaki kullanıcı bakiyesini güncelle
            $stmt = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            $success_message = htmlspecialchars(number_format($amount, 2)) . " TL başarıyla hesabınıza eklendi!";
        } catch (PDOException $e) {
            $error_message = "Bakiye eklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}

require '../layouts/header.php';

// Güncel bakiyeyi çek
$stmt_balance = $pdo->prepare("SELECT balance FROM Users WHERE id = ?");
$stmt_balance->execute([$user_id]);
$current_balance = $stmt_balance->fetchColumn();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="mb-0">Kredi Yükle</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Mevcut Bakiyeniz:</strong> <?= htmlspecialchars(number_format($current_balance, 2)) ?> TL
                </div>

                <?php if ($success_message): ?><div class="alert alert-success"><?= $success_message ?></div><?php endif; ?>
                <?php if ($error_message): ?><div class="alert alert-danger"><?= $error_message ?></div><?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Yüklenecek Tutar (TL):</label>
                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="1" placeholder="Örn: 100" required>
                    </div>
                    <button type="submit" name="add_credit" class="btn btn-primary w-100">Yüklemeyi Onayla</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require '../layouts/footer.php'; ?>