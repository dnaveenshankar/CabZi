<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle cancellation
if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $stmt = $con->prepare("UPDATE cab_bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    // Fetch booking details to notify owner
$infoStmt = $con->prepare("
    SELECT cb.id, cb.car_id, cc.owner_id, cc.title
    FROM cab_bookings cb
    JOIN cab_cars cc ON cb.car_id = cc.id
    WHERE cb.id = ? AND cb.user_id = ?
");
$infoStmt->bind_param("ii", $booking_id, $user_id);
$infoStmt->execute();
$bookingInfo = $infoStmt->get_result()->fetch_assoc();

if ($bookingInfo) {
    $owner_id = $bookingInfo['owner_id'];
    $car_title = $bookingInfo['title'];
    
    $ownerMsg = "A booking for your car '{$car_title}' has been cancelled.";
    $userMsg = "You have cancelled your booking for '{$car_title}'.";

    // Insert into owner notification
    $notiOwner = $con->prepare("INSERT INTO notifications (owner_id, message) VALUES (?, ?)");
    $notiOwner->bind_param("is", $owner_id, $ownerMsg);
    $notiOwner->execute();

    // Insert into user notification
    $notiUser = $con->prepare("INSERT INTO user_notifications (user_id, message) VALUES (?, ?)");
    $notiUser->bind_param("is", $user_id, $userMsg);
    $notiUser->execute();
}

$message = "<div class='alert alert-warning text-center'>Booking has been cancelled and the owner has been notified.</div>";

}

// Fetch bookings
$query = "
    SELECT cb.*, cc.title, cc.car_image, cc.car_name, cc.model, cc.reg_number, cc.location 
    FROM cab_bookings cb
    JOIN cab_cars cc ON cb.car_id = cc.id
    WHERE cb.user_id = ?
    ORDER BY cb.created_at DESC
";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Bookings - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .logo {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
    }
    .clickable-card {
      cursor: pointer;
      transition: 0.3s;
    }
    .clickable-card:hover {
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .card-img-top {
      height: 150px;
      object-fit: cover;
    }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="text-center mb-4">
    <img src="logo.jpg" class="logo mb-2" alt="Cabzi Logo">
    <h4>My Bookings</h4>
  </div>

  <?php echo $message; ?>

  <?php if (empty($bookings)): ?>
    <p class="text-muted text-center">You haven't made any bookings yet.</p>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($bookings as $b): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card clickable-card" data-bs-toggle="modal" data-bs-target="#bookingModal<?php echo $b['id']; ?>">
            <img src="<?php echo htmlspecialchars($b['car_image']); ?>" class="card-img-top" alt="Car Image">
            <div class="card-body">
              <h5 class="card-title"><?php echo htmlspecialchars($b['title']); ?></h5>
              <p class="card-text mb-1">
                <?php echo htmlspecialchars($b['car_name']); ?> | <?php echo htmlspecialchars($b['model']); ?>
              </p>
              <p class="mb-1"><strong>From:</strong> <?php echo $b['start_date']; ?> to <?php echo $b['end_date']; ?></p>
              <span class="badge bg-<?php 
                  echo $b['status'] === 'Confirmed' ? 'success' : 
                       ($b['status'] === 'Rejected' ? 'danger' : 
                       ($b['status'] === 'Cancelled' ? 'secondary' : 'warning')); ?>">
                <?php echo $b['status']; ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="bookingModal<?php echo $b['id']; ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Booking Details - <?php echo htmlspecialchars($b['title']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <img src="<?php echo htmlspecialchars($b['car_image']); ?>" class="img-fluid rounded mb-3" alt="Car Image">
                <p><strong>Car:</strong> <?php echo htmlspecialchars($b['car_name'] . ' - ' . $b['model']); ?></p>
                <p><strong>Reg No:</strong> <?php echo htmlspecialchars($b['reg_number']); ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst($b['type']); ?></p>
                <p><strong>From:</strong> <?php echo $b['start_date']; ?> <?php echo $b['pickup_time']; ?></p>
                <p><strong>To:</strong> <?php echo $b['end_date']; ?> <?php echo $b['drop_time']; ?></p>
                <?php if ($b['pickup_location']): ?>
                  <p><strong>Pickup Location:</strong> <?php echo htmlspecialchars($b['pickup_location']); ?></p>
                <?php endif; ?>
                <?php if ($b['drop_location']): ?>
                  <p><strong>Drop Location:</strong> <?php echo htmlspecialchars($b['drop_location']); ?></p>
                <?php endif; ?>
                <p><strong>Estimation:</strong> ₹<?php echo number_format($b['estimation'], 2); ?></p>
                <p><strong>Final Cost:</strong> <?php echo $b['final_cost'] ? '₹' . number_format($b['final_cost'], 2) : 'Pending'; ?></p>
                <p><strong>Status:</strong> <?php echo $b['status']; ?></p>
                <?php if ($b['remarks']): ?>
                  <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($b['remarks'])); ?></p>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <?php if ($b['status'] !== 'Cancelled'): ?>
                  <form method="post">
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <button type="submit" name="cancel_booking" class="btn btn-danger">
                      <i class="bi bi-x-circle"></i> Cancel Booking
                    </button>
                  </form>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="mt-4 text-center">
    <a href="user_dashboard.php" class="btn btn-outline-primary btn-sm">← Back to Dashboard</a>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
