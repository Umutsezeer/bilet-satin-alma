<?php
session_start();
require '../includes/db.php';
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header("Location: /login.php"); exit; }
$success_message = ''; $error_message = '';
if (isset($_SESSION['flash_success'])) { $success_message = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }

if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['ticket_id'])) {
    $ticket_id_to_cancel = $_GET['ticket_id'];
    $stmt = $pdo->prepare("SELECT t.id, t.status, t.user_id, t.total_price, tr.departure_time FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$ticket_id_to_cancel, $user_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) { $error_message = "Geçersiz bilet."; }
    elseif ($ticket['status'] !== 'active') { $error_message = "Bu bilet zaten iptal edilmiş."; }
    else {
        $departure_timestamp = strtotime($ticket['departure_time']);
        if (($departure_timestamp - time()) / 3600 < 1) {
            $error_message = "Sefere 1 saatten az kaldığı için bilet iptal edilemez.";
        } else {
            $pdo->beginTransaction();
            try {
                $stmt_refund = $pdo->prepare("UPDATE Users SET balance = balance + ? WHERE id = ?");
                $stmt_refund->execute([$ticket['total_price'], $user_id]);
                $stmt_cancel = $pdo->prepare("UPDATE Tickets SET status = 'cancelled' WHERE id = ?");
                $stmt_cancel->execute([$ticket_id_to_cancel]);
                $pdo->commit();
                $_SESSION['flash_success'] = "Biletiniz başarıyla iptal edildi ve ücret iadesi yapıldı.";
                header("Location: my_tickets.php"); exit;
            } catch (Exception $e) { $pdo->rollBack(); $error_message = "İptal işlemi sırasında bir hata oluştu."; }
        }
    }
}

require '../layouts/header.php';
$stmt_balance = $pdo->prepare("SELECT balance FROM Users WHERE id = ?"); $stmt_balance->execute([$user_id]);
$current_balance = $stmt_balance->fetchColumn();

$stmt_tickets = $pdo->prepare("
    SELECT t.id as ticket_id, t.status, t.total_price, tr.departure_city, tr.destination_city, tr.departure_time, f.name as firm_name, 
           GROUP_CONCAT(bs.seat_number, ', ') as seat_numbers
    FROM Tickets as t
    JOIN Trips as tr ON t.trip_id = tr.id
    JOIN Bus_Company as f ON tr.company_id = f.id
    JOIN Booked_Seats as bs ON bs.ticket_id = t.id
    WHERE t.user_id = ? GROUP BY t.id ORDER BY tr.departure_time DESC
");
$stmt_tickets->execute([$user_id]);
$tickets = $stmt_tickets->fetchAll();
?>
<h1 class="mb-4">Biletlerim</h1>
<div class="alert alert-info shadow-sm"><strong>Mevcut Bakiyeniz:</strong> <?= number_format($current_balance, 2) ?> TL</div>
<?php if ($success_message): ?><div class="alert alert-success shadow-sm"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger shadow-sm"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
<div class="card shadow-sm">
    <div class="card-header"><h2>Satın Alınmış Biletler</h2></div>
    <div class="card-body">
        <?php if (count($tickets) > 0): ?>
            <div class="table-responsive"><table class="table table-striped table-hover align-middle">
                <thead class="table-light"><tr><th>Firma</th><th>Güzergah</th><th>Kalkış Zamanı</th><th>Koltuk No(lar)</th><th>Toplam Fiyat</th><th>Durum</th><th class="text-end">İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= htmlspecialchars($ticket['firm_name']) ?></td>
                            <td><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></td>
                            <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($ticket['departure_time']))) ?></td>
                            <td class="fw-bold fs-5 text-center"><?= htmlspecialchars($ticket['seat_numbers']) ?></td>
                            <td><?= htmlspecialchars($ticket['total_price']) ?> TL</td>
                            <td><?php if ($ticket['status'] === 'active'): ?><span class="badge bg-success">Aktif</span><?php else: ?><span class="badge bg-secondary">İptal Edilmiş</span><?php endif; ?></td>
                            <td class="text-end">
                                <?php if ($ticket['status'] === 'active'):
                                    $can_cancel = (strtotime($ticket['departure_time']) - time()) / 3600 > 1; ?>
                                    <a href="generate_pdf.php?ticket_id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-outline-primary">PDF</a>
                                    <?php if ($can_cancel): ?><a href="?action=cancel&ticket_id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?');">İptal Et</a><?php endif; ?>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?> <div class="alert alert-secondary">Henüz hiç bilet satın almadınız.</div> <?php endif; ?>
    </div>
</div>
<?php require '../layouts/footer.php'; ?>