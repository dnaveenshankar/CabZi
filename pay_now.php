<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['booking_id'])) {
    die("Invalid booking.");
}

$booking_id = intval($_POST['booking_id']);
$message = '';

// Fetch booking details
$stmt = $con->prepare("SELECT * FROM cab_bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found.");
}

// Handle marking as paid
if (isset($_POST['mark_paid']) && isset($_POST['payment_mode'])) {
    $final_cost = $booking['final_cost'] ?? $booking['estimation'];
    $payment_mode = $_POST['payment_mode'];
    $payment_timestamp = date('Y-m-d H:i:s');
    
    $update = $con->prepare("
        UPDATE cab_bookings 
        SET payment_status = 'Paid', final_cost = ?, payment_mode = ?, payment_timestamp = ? 
        WHERE id = ? AND user_id = ?
    ");
    $update->bind_param("dssii", $final_cost, $payment_mode, $payment_timestamp, $booking_id, $user_id);
    $update->execute();
    
    $message = "<div class='alert alert-success text-center'>Payment marked as paid successfully via $payment_mode.</div>";
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
}

// QR image path
$qr_path = 'uploads/qr.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Now - Cabzi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .qr-img {
            width: 250px;
            height: 250px;
            object-fit: contain;
            display: block;
            margin: 15px auto;
        }
        .payment-btn { margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-4">
        <h4>Pay Now - Booking #<?php echo $booking['id']; ?></h4>
    </div>

    <!-- Warning box -->
    <div class="alert alert-warning text-center">
        <strong>Dummy Payment Page:</strong> This is a dummy page for testing. <br>
        Payment API will be integrated here in the future.
    </div>

    <?php echo $message; ?>

    <div class="card mx-auto p-4" style="max-width: 400px;">
        <p><strong>Amount:</strong> ₹<?php echo number_format($booking['estimation'], 2); ?></p>

        <?php if ($booking['payment_status'] === 'Paid'): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle"></i> Payment Completed
            </div>
            <p><strong>Payment Mode:</strong> <?php echo $booking['payment_mode']; ?></p>
            <p><strong>Payment Timestamp:</strong> <?php echo $booking['payment_timestamp']; ?></p>
        <?php else: ?>
            <!-- QR Code -->
            <img src="<?php echo $qr_path; ?>" alt="QR Code" class="qr-img">
            <p class="text-center mb-3">Scan this QR to pay</p>

            <!-- Payment options -->
            <form method="post">
                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">

                <button type="submit" name="mark_paid" value="1" class="btn btn-primary w-100 payment-btn"
                        onclick="this.form.payment_mode.value='Debit Card'">
                    <i class="bi bi-credit-card"></i> Pay using Debit Card
                </button>

                <button type="submit" name="mark_paid" value="1" class="btn btn-warning w-100 payment-btn"
                        onclick="this.form.payment_mode.value='Credit Card'">
                    <i class="bi bi-credit-card-2-back"></i> Pay using Credit Card
                </button>

                <button type="submit" name="mark_paid" value="1" class="btn btn-success w-100 payment-btn"
                        onclick="this.form.payment_mode.value='UPI'">
                    <i class="bi bi-phone"></i> Pay using UPI
                </button>

                <input type="hidden" name="payment_mode" value="">
            </form>
        <?php endif; ?>
    </div>

    <div class="mt-4 text-center">
        <a href="my_bookings.php" class="btn btn-outline-primary btn-sm">← Back to My Bookings</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
