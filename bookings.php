<?php
session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
$message = '';

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    $status = $_POST['status'];
    $final_cost = $_POST['final_cost'];
    $pickup = trim($_POST['pickup_location']);
    $drop = trim($_POST['drop_location']);

    $stmt = $con->prepare("UPDATE cab_bookings SET status = ?, final_cost = ?, pickup_location = ?, drop_location = ? WHERE id = ?");
    $stmt->bind_param("sdssi", $status, $final_cost, $pickup, $drop, $booking_id);
    $stmt->execute();

    // Fetch booking details for notification
    $user_stmt = $con->prepare("SELECT user_id, offline_customer_id FROM cab_bookings WHERE id = ?");
    $user_stmt->bind_param("i", $booking_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $row = $user_result->fetch_assoc();

    if ($row['user_id']) {
        $user_id = $row['user_id'];
        $notif_msg = "Your booking ID #$booking_id has been updated. Status: $status. Final cost: ₹$final_cost.";
        $notif_insert = $con->prepare("INSERT INTO user_notifications (user_id, message) VALUES (?, ?)");
        $notif_insert->bind_param("is", $user_id, $notif_msg);
        $notif_insert->execute();
    }

    $message = "<div class='alert alert-success'>Booking updated successfully.</div>";
}

// Fetch bookings
$query = "
    SELECT cb.*, 
           IF(cb.user_id IS NOT NULL, cu.name, oc.name) AS user_name,
           IF(cb.user_id IS NOT NULL, cu.email, oc.email) AS email,
           IF(cb.user_id IS NOT NULL, cu.mobile, oc.mobile) AS user_mobile,
           cc.title
    FROM cab_bookings cb
    JOIN cab_cars cc ON cb.car_id = cc.id
    LEFT JOIN cab_users cu ON cb.user_id = cu.id
    LEFT JOIN offline_customers oc ON cb.offline_customer_id = oc.id
    WHERE cc.owner_id = ?
    ORDER BY cb.created_at DESC
";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Owner Bookings - Cabzi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .profile-icon { cursor: pointer; }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4>Owner Bookings</h4>
    <div>
      <a href="offlinebookings.php" class="btn btn-outline-primary">➕ Offline Booking</a>
      <a href="owner_dashboard.php" class="btn btn-outline-secondary">← Back</a>
    </div>
  </div>
  <?php echo $message; ?>

  <?php if (empty($bookings)): ?>
    <p class="text-muted text-center">No bookings found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>Car</th>
            <th>User</th>
            <th>Booking Dates</th>
            <th>Estimation</th>
            <th>Pickup/Drop</th>
            <th>Status</th>
            <th>Payment</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?php echo htmlspecialchars($b['title']); ?></td>
            <td>
              <?php echo htmlspecialchars($b['user_name']); ?>
              <i class="bi bi-person-circle text-primary profile-icon" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $b['id']; ?>"></i>
            </td>
            <td><?php echo $b['start_date'] . ' → ' . $b['end_date']; ?></td>
            <td>₹<?php echo number_format($b['estimation'], 2); ?></td>
            <td>
              <div><strong>Pickup:</strong> <?php echo htmlspecialchars($b['pickup_location']); ?></div>
              <div><strong>Drop:</strong> <?php echo htmlspecialchars($b['drop_location']); ?></div>
            </td>
            <td>
              <span class="badge bg-<?php
                echo $b['status'] === 'Confirmed' ? 'success' :
                     ($b['status'] === 'Cancelled' ? 'secondary' :
                     ($b['status'] === 'Rejected' ? 'danger' : 'warning'));
              ?>"><?php echo $b['status']; ?></span>
            </td>
            <td>
              <?php if($b['payment_status'] === 'Paid'): ?>
                <span class="badge bg-success">Paid</span>
                <button class="btn btn-sm btn-outline-info mt-1" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $b['id']; ?>">View</button>
              <?php elseif($b['status'] === 'Confirmed'): ?>
                <span class="badge bg-warning">Unpaid</span>
                <a href="pay_now.php" class="btn btn-sm btn-outline-success mt-1">Pay Now</a>
              <?php else: ?>
                <span class="badge bg-secondary">-</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $b['id']; ?>">Edit</button>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?php echo $b['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST">
                  <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Booking #<?php echo $b['id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p><strong>Estimation:</strong> ₹<?php echo number_format($b['estimation'], 2); ?></p>
                    <p><strong>Dates:</strong> <?php echo $b['start_date'] . ' → ' . $b['end_date']; ?></p>

                    <div class="mb-2">
                      <label class="form-label">Pickup Location</label>
                      <input type="text" name="pickup_location" class="form-control" value="<?php echo htmlspecialchars($b['pickup_location']); ?>">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Drop Location</label>
                      <input type="text" name="drop_location" class="form-control" value="<?php echo htmlspecialchars($b['drop_location']); ?>">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Final Cost</label>
                      <input type="number" name="final_cost" step="0.01" class="form-control" value="<?php echo $b['final_cost'] ?? ''; ?>">
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Status</label>
                      <select name="status" class="form-select">
                        <option value="Pending" <?php if ($b['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Confirmed" <?php if ($b['status'] === 'Confirmed') echo 'selected'; ?>>Confirmed</option>
                        <option value="Cancelled" <?php if ($b['status'] === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        <option value="Rejected" <?php if ($b['status'] === 'Rejected') echo 'selected'; ?>>Rejected</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- User Modal -->
          <div class="modal fade" id="userModal<?php echo $b['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Customer Details</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <p><strong>Name:</strong> <?php echo htmlspecialchars($b['user_name']); ?></p>
                  <p><strong>Email:</strong> <?php echo htmlspecialchars($b['email']); ?></p>
                  <p><strong>Mobile:</strong> <?php echo htmlspecialchars($b['user_mobile']); ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Modal -->
          <div class="modal fade" id="paymentModal<?php echo $b['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Payment Details - Booking #<?php echo $b['id']; ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <?php if($b['payment_status'] === 'Paid'): ?>
                    <p><strong>Status:</strong> <?php echo $b['payment_status']; ?></p>
                    <p><strong>Amount:</strong> ₹<?php echo number_format($b['final_cost'] ?? $b['estimation'], 2); ?></p>
                    <p><strong>Payment Mode:</strong> <?php echo $b['payment_mode']; ?></p>
                    <p><strong>Payment Timestamp:</strong> <?php echo $b['payment_timestamp']; ?></p>
                    <?php if(file_exists('upload+s/')): ?>
                      <img src="uploads/pqr.ng" class="img-fluid mt-2" alt="Payment QR">
                    <?php endif; ?>
                  <?php else: ?>
                    <p>No payment has been made yet.</p>
                  <?php endif; ?>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>

        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
