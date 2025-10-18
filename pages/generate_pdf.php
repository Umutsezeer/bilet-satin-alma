<?php
ob_start();
session_start();
require '../includes/db.php';
require '../libs/tfpdf.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['ticket_id'])) {
    exit('Geçersiz istek.');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$company_id = $_SESSION['firm_id'] ?? null;
$ticket_id = $_GET['ticket_id'];

$stmt = $pdo->prepare("
    SELECT 
        t.user_id as owner_id, 
        tr.company_id as trip_company_id,
        u.full_name as user_fullname, tr.departure_city, tr.destination_city, tr.departure_time,
        t.total_price, f.name as firm_name, GROUP_CONCAT(bs.seat_number, ', ') as seat_numbers
    FROM Tickets as t
    JOIN Users as u ON t.user_id = u.id
    JOIN Trips as tr ON t.trip_id = tr.id
    JOIN Bus_Company as f ON tr.company_id = f.id
    JOIN Booked_Seats as bs ON bs.ticket_id = t.id
    WHERE t.id = ? AND t.status = 'active'
    GROUP BY t.id
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    exit('Bilet bulunamadı veya aktif değil.');
}

// -YETKİ KONTROLÜ -
$is_owner = ($user_id === $ticket['owner_id']);
// Değişkeni kontrol etmeden önce varsayılan olarak 'false' yaptım
$is_correct_company_admin = false; 
if ($user_role === 'company_admin') {
    $is_correct_company_admin = ($company_id === $ticket['trip_company_id']);
}

if (!$is_owner && !$is_correct_company_admin) {
    exit('Bu bileti görüntüleme yetkiniz yok.');
}

$pdf = new tfpdf();
$pdf->AddPage();
$pdf->AddFont('DejaVu','','DejaVuSans.ttf',true);
$pdf->SetFont('DejaVu','',16);

// Başlık
$pdf->Cell(0, 10, 'YOLCU BİLETİ', 0, 1, 'C');
$pdf->Ln(10);

// Bilet Bilgileri
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(40, 10, 'Firma:', 0, 0);
$pdf->Cell(0, 10, $ticket['firm_name'], 0, 1);

$pdf->Cell(40, 10, 'Yolcu Adı:', 0, 0);
$pdf->Cell(0, 10, $ticket['user_fullname'], 0, 1);

$pdf->Cell(40, 10, 'Güzergah:', 0, 0);
$pdf->Cell(0, 10, $ticket['departure_city'] . ' -> ' . $ticket['destination_city'], 0, 1);

$pdf->Cell(40, 10, 'Kalkış Zamanı:', 0, 0);
$pdf->Cell(0, 10, date('d.m.Y H:i', strtotime($ticket['departure_time'])), 0, 1);

$pdf->Cell(40, 10, 'Koltuk No(lar):', 0, 0);
$pdf->Cell(0, 10, $ticket['seat_numbers'], 0, 1);

$pdf->Cell(40, 10, 'Toplam Fiyat:', 0, 0);
$pdf->Cell(0, 10, $ticket['total_price'] . ' TL', 0, 1);

$pdf->Ln(10);
$pdf->SetFont('DejaVu', '', 10);
$pdf->Cell(0, 10, 'İyi yolculuklar dileriz!', 0, 1, 'C');

ob_end_clean(); 
$pdf->Output('D', 'Bilet-'. substr($ticket_id, 0, 8) .'.pdf');
exit;
?>