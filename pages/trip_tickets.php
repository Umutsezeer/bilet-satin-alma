<?php
session_start();
require '../includes/db.php';

// Güvenlik kontrolleri
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company_admin') {
    header("Location: /login.php");
    exit;
}
if (!isset($_GET['trip_id'])) {
    header("Location: firm_trips.php");
    exit;
}

$company_id = $_SESSION['firm_id'];
$trip_id = $_GET['trip_id'];
$success_message = '';
$error_message = '';

if (isset($_SESSION['flash_success'])) {
    $success_message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// BİLET İPTAL ETME MANTIĞI
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['ticket_id'])) {
    $ticket_id_to_cancel = $_GET['ticket_id'];

    $stmt = $pdo->prepare("
        SELECT t.id, t.status, t.user_id, t.total_price, tr.departure_time 
        FROM Tickets t 
        JOIN Trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND tr.company_id = ?
    ");
    $stmt->execute([$ticket_id_to_cancel, $company_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $error_message = "Geçersiz bilet veya bu bileti iptal etme yetkiniz yok.";
    } elseif ($ticket['status'] !== 'active') {
        $error_message = "Bu bilet zaten iptal edilmiş veya aktif değil.";
    } else {
        $departure_timestamp = strtotime($ticket['departure_time']);
        if (($departure_timestamp - time()) / 3600 < 1) {
            $error_message = "Seferin kalkışına 1 saatten az kaldığı için bu bilet iptal edilemez.";
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
                $stmt->execute([$ticket['total_price'], $ticket['user_id']]);
                $stmt = $pdo->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$ticket_id_to_cancel]);
                $pdo->commit();
                $_SESSION['flash_success'] = "Bilet başarıyla iptal edildi ve ücret yolcuya iade edildi.";
                header("Location: trip_tickets.php?trip_id=" . $trip_id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "İptal işlemi sırasında bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

require '../layouts/header.php';

// Sefer bilgilerini çek
$stmt_trip = $pdo->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt_trip->execute([$trip_id, $company_id]);
$trip = $stmt_trip->fetch();
if (!$trip) { header("Location: firm_trips.php"); exit; }

// Bu sefere ait tüm biletleri çek
$stmt_tickets = $pdo->prepare("
    SELECT t.id as ticket_id, t.status, u.full_name as user_fullname, u.email as user_email, 
           GROUP_CONCAT(bs.seat_number, ', ') as seat_numbers
    FROM Tickets as t
    JOIN Users as u ON t.user_id = u.id
    JOIN Booked_Seats as bs ON bs.ticket_id = t.id
    WHERE t.trip_id = ?
    GROUP BY t.id
    ORDER BY MIN(bs.seat_number) ASC
");
$stmt_tickets->execute([$trip_id]);
$tickets = $stmt_tickets->fetchAll();
?>

<h1 class="mb-2">Bilet Yönetimi</h1>
<h5 class="text-muted mb-4"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?> | <?= htmlspecialchars(date('d.m.Y H:i', strtotime($trip['departure_time']))) ?></h5>
<p><a href="firm_trips.php">« Sefer Yönetimine Geri Dön</a></p>

<?php if ($success_message): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header"><h2>Sefere Ait Biletler</h2></div>
    <div class="card-body">
        <?php if (count($tickets) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light"><tr><th>Koltuk No(lar)</th><th>Yolcu Adı</th><th>Yolcu E-posta</th><th>Durum</th><th class="text-end">İşlemler</th></tr></thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="fw-bold fs-5 text-center"><?= htmlspecialchars($ticket['seat_numbers']) ?></td>
                                <td><?= htmlspecialchars($ticket['user_fullname']) ?></td>
                                <td><?= htmlspecialchars($ticket['user_email']) ?></td>
                                <td>
                                    <?php if ($ticket['status'] === 'active'): ?><span class="badge bg-success">Aktif</span><?php else: ?><span class="badge bg-secondary">İptal Edilmiş</span><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($ticket['status'] === 'active'):
                                        $can_cancel = (strtotime($trip['departure_time']) - time()) / 3600 > 1;
                                        if ($can_cancel): ?>
                                            <a href="?trip_id=<?= $trip_id ?>&action=cancel&ticket_id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu bileti yolcu adına iptal etmek istediğinizden emin misiniz?');">Yolcu Adına İptal Et</a>
                                        <?php else: ?>
                                            <span class="badge bg-danger">İptal Süresi Doldu</span>
                                        <?php endif; ?>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?><div class="alert alert-secondary">Bu sefere ait satılmış bilet bulunmuyor.</div><?php endif; ?>
    </div>
</div>

<?php require '../layouts/footer.php'; ?>