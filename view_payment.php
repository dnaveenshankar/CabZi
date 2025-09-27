<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    die("Booking ID is required.");
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id']);

// Fetch booking details
$stmt = $con->prepare("SELECT * FROM cab_bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Payment - Cabzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-4">
        <h4>Payment Details - Booking #<?php echo $booking['id']; ?></h4>
    </div>

    <div class="card mx-auto p-4" style="max-width: 400px;">
        <p><strong>Booking ID:</strong> <?php echo $booking['id']; ?></p>
        <p><strong>Amount Paid:</strong> ₹<?php echo number_format($booking['final_cost'], 2); ?></p>
        <p><strong>Payment Status:</strong> 
            <?php echo $booking['payment_status'] ?? 'Unpaid'; ?>
        </p>
        <p><strong>Payment Mode:</strong> <?php echo $booking['payment_mode'] ?? '-'; ?></p>
        <p><strong>Payment Timestamp:</strong> <?php echo $booking['payment_timestamp'] ?? '-'; ?></p>

        <?php if ($booking['payment_status'] === 'Paid'): ?>
            <div class="alert alert-success text-center mt-3">
                <i class="bi bi-check-circle"></i> Payment Completed
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center mt-3">
                <i class="bi bi-exclamation-circle"></i> Payment Pending
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 text-center">
        <a href="my_bookings.php" class="btn btn-outline-primary btn-sm">← Back to My Bookings</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
