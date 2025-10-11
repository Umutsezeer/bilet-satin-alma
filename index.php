<?php
session_start();
require 'includes/db.php';
require 'layouts/header.php';

function tr_normalize(string $s): string {
    $map = ['İ'=>'i','I'=>'ı','ı'=>'i','Ş'=>'s','ş'=>'s','Ğ'=>'g','ğ'=>'g','Ü'=>'u','ü'=>'u','Ö'=>'o','ö'=>'o','Ç'=>'c','ç'=>'c'];
    $s = strtr($s, $map);
    return mb_strtolower($s, 'UTF-8');
}

// SQL sorgusunu YENİ şemaya göre güncelle (Trips, Bus_Company, company_id)
$sql = "SELECT t.id, t.departure_city, t.destination_city, t.departure_time, t.price, f.name AS firm_name 
        FROM Trips t 
        LEFT JOIN Bus_Company f ON t.company_id = f.id 
        WHERE 1=1";
$params = [];

$from = isset($_GET['from_city']) ? trim($_GET['from_city']) : '';
$to   = isset($_GET['to_city'])   ? trim($_GET['to_city'])   : '';
$date = isset($_GET['date'])      ? trim($_GET['date'])      : '';

if (!empty($from)) {
    // Yeni şemadaki kolon adı 'departure_city'
    $sql .= " AND lower(t.departure_city) LIKE ?";
    $params[] = "%" . tr_normalize($from) . "%";
}
if (!empty($to)) {
    // Yeni şemadaki kolon adı 'destination_city'
    $sql .= " AND lower(t.destination_city) LIKE ?";
    $params[] = "%" . tr_normalize($to) . "%";
}
if (!empty($date)) {
    $sql .= " AND date(t.departure_time) = ?";
    $params[] = $date;
}

$sql .= " ORDER BY t.departure_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-5 mb-4 bg-light rounded-3">
  <div class="container-fluid py-5">
    <h1 class="display-5 fw-bold">Bilet Satın Alma Platformu</h1>
    <p class="col-md-8 fs-4">Türkiye'nin her yerine en uygun otobüs biletini bulun.</p>
  </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><h2>Sefer Ara</h2></div>
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md"><label for="from_city" class="form-label">Nereden:</label><input type="text" class="form-control" id="from_city" name="from_city" value="<?= htmlspecialchars($from) ?>"></div>
            <div class="col-md"><label for="to_city" class="form-label">Nereye:</label><input type="text" class="form-control" id="to_city" name="to_city" value="<?= htmlspecialchars($to) ?>"></div>
            <div class="col-md-auto"><label for="date" class="form-label">Tarih:</label><input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date) ?>"></div>
            <div class="col-md-auto"><button type="submit" class="btn btn-primary">Ara</button><a href="/" class="btn btn-secondary ms-2">Temizle</a></div>
        </form>
    </div>
</div>

<h2 class="mt-5">Seferler</h2>
<hr>

<?php if ($trips): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr><th>Firma</th><th>Kalkış</th><th>Varış</th><th>Tarih</th><th>Saat</th><th>Fiyat</th><th class="text-end">İşlemler</th></tr>
            </thead>
            <tbody>
                <?php foreach ($trips as $trip): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($trip['firm_name'] ?? 'Firma Bilgisi Yok') ?></strong></td>
                        <td><?= htmlspecialchars($trip['departure_city']) ?></td>
                        <td><?= htmlspecialchars($trip['destination_city']) ?></td>
                        <td><?= htmlspecialchars(date('d.m.Y', strtotime($trip['departure_time']))) ?></td>
                        <td><?= htmlspecialchars(date('H:i', strtotime($trip['departure_time']))) ?></td>
                        <td><?= htmlspecialchars($trip['price']) ?> TL</td>
                        <td class="text-end"><a href="pages/trip_details.php?id=<?= $trip['id'] ?>" class="btn btn-sm btn-info">Detaylar / Bilet Al</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Veritabanında kayıtlı sefer bulunamadı veya arama kriterlerine uygun sonuç yok.</div>
<?php endif; ?>

<?php require 'layouts/footer.php'; ?>