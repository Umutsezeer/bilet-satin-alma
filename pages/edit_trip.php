<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company_admin' || !isset($_GET['id'])) { header("Location: /login.php"); exit; }
$trip_id = $_GET['id'];
$company_id = $_SESSION['firm_id'];
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_city = trim($_POST['departure_city']); $arrival_city = trim($_POST['destination_city']);
    $departure_time = trim($_POST['departure_time']); $arrival_time = trim($_POST['arrival_time']);
    $seat_count = trim($_POST['capacity']); $price = trim($_POST['price']);
    if (empty($departure_city) || empty($arrival_city) || empty($departure_time) || empty($arrival_time) || empty($seat_count) || empty($price)) {
        $error_message = "Lütfen tüm alanları doldurun.";
    } else {
        $sql = "UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, capacity = ?, price = ? WHERE id = ? AND company_id = ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$departure_city, $arrival_city, $departure_time, $arrival_time, $seat_count, $price, $trip_id, $company_id]);
            $_SESSION['flash_success'] = "Sefer (ID: $trip_id) başarıyla güncellendi!";
            header("Location: firm_trips.php"); exit;
        } catch (PDOException $e) { $error_message = "Güncelleme sırasında bir hata oluştu: " . $e->getMessage(); }
    }
}
require '../layouts/header.php';
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $company_id]);
$trip = $stmt->fetch();
if (!$trip) { header("Location: firm_trips.php"); exit; }
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header"><h2 class="mb-0">Sefer Düzenle</h2></div>
            <div class="card-body">
                <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                <form method="POST" class="row g-3">
                    <div class="col-md-6"><label for="departure_city" class="form-label">Kalkış Şehri:</label><input type="text" class="form-control" id="departure_city" name="departure_city" value="<?= htmlspecialchars($trip['departure_city']) ?>" required></div>
                    <div class="col-md-6"><label for="destination_city" class="form-label">Varış Şehri:</label><input type="text" class="form-control" id="destination_city" name="destination_city" value="<?= htmlspecialchars($trip['destination_city']) ?>" required></div>
                    <div class="col-md-6"><label for="departure_time" class="form-label">Kalkış Zamanı:</label><input type="datetime-local" class="form-control" id="departure_time" name="departure_time" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($trip['departure_time']))) ?>" required></div>
                    <div class="col-md-6"><label for="arrival_time" class="form-label">Varış Zamanı:</label><input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($trip['arrival_time']))) ?>" required></div>
                    <div class="col-md-6"><label for="capacity" class="form-label">Koltuk Sayısı:</label><input type="number" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>" required></div>
                    <div class="col-md-6"><label for="price" class="form-label">Fiyat (TL):</label><input type="number" class="form-control" id="price" name="price" step="0.01" value="<?= htmlspecialchars($trip['price']) ?>" required></div>
                    <div class="col-12 mt-4"><button type="submit" class="btn btn-primary">Güncelle</button><a href="firm_trips.php" class="btn btn-secondary ms-2">İptal</a></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>