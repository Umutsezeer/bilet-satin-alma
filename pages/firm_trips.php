<?php
session_start();
require '../includes/db.php';
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company_admin') { header("Location: /login.php"); exit; }
$company_id = $_SESSION['firm_id']; // Session'daki adı 'firm_id' idi, tutarlılık için böyle bırakalım.
$success_message = ''; $error_message = '';
if (isset($_SESSION['flash_success'])) { $success_message = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $trip_id_to_delete = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id_to_delete, $company_id]);
    $_SESSION['flash_success'] = "Sefer başarıyla silindi.";
    header("Location: firm_trips.php"); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $departure_city = trim($_POST['departure_city']); $arrival_city = trim($_POST['destination_city']);
    $departure_time = trim($_POST['departure_time']); $arrival_time = trim($_POST['arrival_time']);
    $seat_count = trim($_POST['capacity']); $price = trim($_POST['price']);
    if (empty($departure_city) || empty($arrival_city) || empty($departure_time) || empty($arrival_time) || empty($seat_count) || empty($price)) {
        $error_message = "Lütfen tüm alanları doldurun.";
    } else {
        $trip_id = generate_uuid();
        $sql = "INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, capacity, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trip_id, $company_id, $departure_city, $arrival_city, $departure_time, $arrival_time, $seat_count, $price]);
            $success_message = "Yeni sefer başarıyla eklendi!";
        } catch (PDOException $e) { $error_message = "Sefer eklenirken bir hata oluştu: " . $e->getMessage(); }
    }
}
require '../layouts/header.php';
$stmt_firm = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = ?");
$stmt_firm->execute([$company_id]);
$firm_name = $stmt_firm->fetchColumn();
$stmt = $pdo->prepare("SELECT * FROM Trips WHERE company_id = ? ORDER BY departure_time DESC");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1 class="mb-4"><?= htmlspecialchars($firm_name ?? 'Firma Paneli') ?> - Sefer Yönetimi</h1>
<?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header"><h2>Yeni Sefer Ekle</h2></div>
    <div class="card-body">
        <form method="POST" class="row g-3" name="add_trip_form">
            <div class="col-md-6"><label for="departure_city" class="form-label">Kalkış Şehri:</label><input type="text" class="form-control" id="departure_city" name="departure_city" required></div>
            <div class="col-md-6"><label for="destination_city" class="form-label">Varış Şehri:</label><input type="text" class="form-control" id="destination_city" name="destination_city" required></div>
            <div class="col-md-6"><label for="departure_time" class="form-label">Kalkış Zamanı:</label><input type="datetime-local" class="form-control" id="departure_time" name="departure_time" required></div>
            <div class="col-md-6"><label for="arrival_time" class="form-label">Varış Zamanı:</label><input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" required></div>
            <div class="col-md-6"><label for="capacity" class="form-label">Koltuk Sayısı:</label><input type="number" class="form-control" id="capacity" name="capacity" required></div>
            <div class="col-md-6"><label for="price" class="form-label">Fiyat (TL):</label><input type="number" class="form-control" id="price" name="price" step="0.01" required></div>
            <div class="col-12"><button type="submit" name="add_trip" class="btn btn-primary">Seferi Ekle</button></div>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-header"><h2>Mevcut Seferler</h2></div>
    <div class="card-body">
        <?php if (count($trips) > 0): ?>
            <div class="table-responsive"><table class="table table-striped table-hover align-middle">
                <thead class="table-light"><tr><th>Kalkış</th><th>Varış</th><th>Zaman</th><th>Kapasite</th><th>Fiyat</th><th class="text-end">İşlemler</th></tr></thead>
                <tbody><?php foreach ($trips as $trip): ?><tr><td><?= htmlspecialchars($trip['departure_city']) ?></td><td><?= htmlspecialchars($trip['destination_city']) ?></td><td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($trip['departure_time']))) ?></td><td><?= htmlspecialchars($trip['capacity']) ?></td><td><?= htmlspecialchars($trip['price']) ?> TL</td><td class="text-end"><a href="trip_tickets.php?trip_id=<?= $trip['id'] ?>" class="btn btn-sm btn-info">Biletler</a><a href="edit_trip.php?id=<?= $trip['id'] ?>" class="btn btn-sm btn-warning">Düzenle</a> <a href="?action=delete&id=<?= $trip['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu seferi silmek istediğinizden emin misiniz?');">Sil</a></td></tr><?php endforeach; ?></tbody>
            </table></div>
        <?php else: ?><div class="alert alert-secondary">Henüz hiç sefer eklemediniz.</div><?php endif; ?>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>