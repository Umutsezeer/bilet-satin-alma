<?php
session_start();
require '../includes/db.php';
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) { header("Location: /index.php"); exit; }

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null; // Rolü de bir değişkene alıyoruz
$success_message = '';
$error_message = '';

$stmt = $pdo->prepare("SELECT t.*, f.name as firm_name FROM Trips t JOIN Bus_Company f ON t.company_id = f.id WHERE t.id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    require '../layouts/header.php';
    echo "<div class='container mt-4'><div class='alert alert-danger'>Sefer bulunamadı.</div></div>";
    require '../layouts/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_ticket'])) {
    // GÜVENLİK KONTROLÜ: Satın alma işlemini sadece 'user' rolü yapabilir
    if (!$user_id || $user_role !== 'user') {
        exit('Bu işlemi yapma yetkiniz yok.');
    }
    
    $selected_seats = $_POST['seats'] ?? [];
    if (empty($selected_seats)) {
        $error_message = "Lütfen en az bir koltuk seçin.";
    } else {
        $total_price = count($selected_seats) * $trip['price'];
        
        $stmt_balance = $pdo->prepare("SELECT balance FROM Users WHERE id = ?");
        $stmt_balance->execute([$user_id]);
        $user_balance = $stmt_balance->fetchColumn();

        if ($user_balance < $total_price) {
            $error_message = "Yetersiz bakiye! Mevcut bakiyeniz: {$user_balance} TL, Toplam Tutar: {$total_price} TL";
        } else {
            $pdo->beginTransaction();
            try {
                $ticket_id = generate_uuid();
                $stmt_ticket = $pdo->prepare("INSERT INTO Tickets (id, trip_id, user_id, total_price) VALUES (?, ?, ?, ?)");
                $stmt_ticket->execute([$ticket_id, $trip_id, $user_id, $total_price]);

                $stmt_seat = $pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (?, ?, ?)");
                foreach ($selected_seats as $seat) {
                    $stmt_seat->execute([generate_uuid(), $ticket_id, $seat]);
                }

                $new_balance = $user_balance - $total_price;
                $stmt_balance_update = $pdo->prepare("UPDATE Users SET balance = ? WHERE id = ?");
                $stmt_balance_update->execute([$new_balance, $user_id]);

                $pdo->commit();
                $success_message = "Biletiniz başarıyla satın alındı! Koltuklar: " . implode(', ', $selected_seats);
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Bilet alımı sırasında bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

require '../layouts/header.php';

$stmt_sold = $pdo->prepare("SELECT bs.seat_number FROM Booked_Seats bs JOIN Tickets t ON bs.ticket_id = t.id WHERE t.trip_id = ? AND t.status = 'active'");
$stmt_sold->execute([$trip_id]);
$sold_seats = $stmt_sold->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
    .seat-map { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; padding: 10px; max-width: 300px; }
    .seat { border: 1px solid #0d6efd; color: #0d6efd; background-color: white; padding: 10px; text-align: center; cursor: pointer; border-radius: .25rem; font-weight: bold; user-select: none; }
    .seat.sold { background-color: #6c757d; color: white; border-color: #6c757d; cursor: not-allowed; }
    .seat.selected { background-color: #198754; color: white; border-color: #198754; }
    .seat-input { position: absolute; opacity: 0; width: 0; height: 0; cursor: pointer; }
    .aisle { grid-column: 3; }
</style>

<h1 class="mb-4">Sefer Detayları ve Bilet Alma</h1>
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><h2><?= htmlspecialchars($trip['firm_name']) ?></h2></div>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Kalkış Zamanı:</strong> <?= htmlspecialchars(date('d F Y, H:i', strtotime($trip['departure_time']))) ?></li>
                    <li class="list-group-item"><strong>Fiyat (Tek Koltuk):</strong> <span class="fs-4 fw-bold text-primary"><?= htmlspecialchars($trip['price']) ?> TL</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <?php if ($user_role === 'user'): ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success h-100 d-flex flex-column justify-content-center align-items-center text-center shadow-sm">
                    <h4 class="alert-heading">İşlem Başarılı!</h4>
                    <p><?= htmlspecialchars($success_message) ?></p><hr>
                    <a href="my_tickets.php" class="btn btn-primary">Biletlerimi Gör</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header"><h2>Koltuk Seçimi</h2></div>
                        <div class="card-body">
                            <?php if ($error_message): ?><div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
                            <div class="seat-map mx-auto">
                                <?php for ($i = 1; $i <= $trip['capacity']; $i++): 
                                    $is_sold = in_array($i, $sold_seats);
                                    if ($i % 4 == 3) echo '<div class="aisle"></div>'; ?>
                                    <label class="seat <?= $is_sold ? 'sold' : '' ?>" id="seat-label-<?= $i ?>">
                                        <?= $i ?><input type="checkbox" name="seats[]" value="<?= $i ?>" class="seat-input" <?= $is_sold ? 'disabled' : '' ?> onchange="updateSeatSelection()">
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <p class="text-center mt-3">Seçili Koltuklar: <strong id="selected-seats-display">Yok</strong></p>
                            <p class="text-center mt-2 fs-5">Toplam Tutar: <strong id="total-price-display">0.00 TL</strong></p>
                        </div>
                        <div class="card-footer"><button type="submit" name="buy_ticket" class="btn btn-success w-100 btn-lg">Satın Al</button></div>
                    </div>
                </form>
            <?php endif; ?>
        <?php elseif (isset($_SESSION['user_id'])): ?>
            <div class="alert alert-warning h-100 d-flex align-items-center justify-content-center text-center shadow-sm">Bilet satın alma işlemi sadece "Yolcu" (user) rolündeki hesaplar için geçerlidir.</div>
        <?php else: ?>
            <div class="alert alert-info h-100 d-flex align-items-center justify-content-center text-center shadow-sm">Bilet satın almak için lütfen <a href="/login.php" class="alert-link">giriş yapın</a>.</div>
        <?php endif; ?>
    </div>
</div>
<script>
function updateSeatSelection() {
    document.querySelectorAll('input[name="seats[]"]:not(:checked)').forEach(checkbox => checkbox.parentElement.classList.remove('selected'));
    const selectedCheckboxes = document.querySelectorAll('input[name="seats[]"]:checked');
    let selectedSeats = [];
    selectedCheckboxes.forEach(checkbox => {
        selectedSeats.push(checkbox.value);
        checkbox.parentElement.classList.add('selected');
    });
    document.getElementById('selected-seats-display').textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Yok';
    const totalPrice = selectedSeats.length * <?= $trip['price'] ?>;
    document.getElementById('total-price-display').textContent = totalPrice.toFixed(2) + ' TL';
}
</script>
<?php require '../layouts/footer.php'; ?>