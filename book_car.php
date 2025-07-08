<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<p>Invalid request.</p>";
    exit();
}

$car_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$message = "";

// Fetch car details including owner_id
$stmt = $con->prepare("SELECT * FROM cab_cars WHERE id = ? AND is_available = 1");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();

if (!$car) {
    echo "<p>Car not found or unavailable.</p>";
    exit();
}

// Check for existing confirmed bookings
$alreadyBooked = false;
$today = date("Y-m-d");
$checkBooking = $con->prepare("SELECT id FROM cab_bookings WHERE car_id = ? AND status = 'Confirmed' AND end_date >= ?");
$checkBooking->bind_param("is", $car_id, $today);
$checkBooking->execute();
$checkBooking->store_result();
if ($checkBooking->num_rows > 0) $alreadyBooked = true;
$checkBooking->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyBooked) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $pickup_time = $_POST['pickup_time'];
    $drop_time = $_POST['drop_time'];
    $mobile = trim($_POST['mobile']);
    $type = $_POST['type'];

    if (!in_array($type, ['self', 'driver'])) {
        die("Invalid booking type.");
    }

    $pickup = ($type === 'self') ? '' : trim($_POST['pickup']);
    $drop = ($type === 'self') ? '' : trim($_POST['drop']);

    $days = max((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24), 1);
    $base_cost = $car['rent_per_day'] * $days;
    $driver_charge = ($type === 'driver') ? 1000 * $days : 0;
    $estimation = $base_cost + $driver_charge;
    $status = "Pending";

    $insert = $con->prepare("INSERT INTO cab_bookings 
        (user_id, car_id, start_date, end_date, pickup_location, drop_location, pickup_time, drop_time, mobile, type, estimation, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("iissssssssds", 
        $user_id, $car_id, $start_date, $end_date, $pickup, $drop,
        $pickup_time, $drop_time, $mobile, $type, $estimation, $status);

    if ($insert->execute()) {
        // Insert notifications

        // 1. Notification for Owner
        $owner_id = $car['owner_id']; // assuming owner_id is stored in cab_cars table
        $owner_message = "New booking request for your car '{$car['title']}' from user ID $user_id. Please review and confirm.";
        $notifOwner = $con->prepare("INSERT INTO notifications (owner_id, message) VALUES (?, ?)");
        $notifOwner->bind_param("is", $owner_id, $owner_message);
        $notifOwner->execute();
        $notifOwner->close();

        // 2. Notification for User
        $user_message = "Your booking request for '{$car['title']}' has been submitted. Estimated cost: ₹" . number_format($estimation, 2) . ". Awaiting owner confirmation.";
        $notifUser = $con->prepare("INSERT INTO user_notifications (user_id, message) VALUES (?, ?)");
        $notifUser->bind_param("is", $user_id, $user_message);
        $notifUser->execute();
        $notifUser->close();

        $message = "<div class='alert alert-success'>
            Booking requested! Estimated cost: ₹" . number_format($estimation, 2) . "<br>
            Final price and pickup/drop location will be confirmed by the car owner.
        </div>";
    } else {
        $message = "<div class='alert alert-danger'>Booking failed. Please try again.</div>";
    }
}
?>
<!-- HTML and form here (same as your current code) -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Car - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f0f2f5; }
    .form-box {
      background: white;
      padding: 30px;
      border-radius: 1rem;
      max-width: 650px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="container py-5">
  <div class="form-box">
    <div class="text-center mb-4">
      <img src="logo.jpg" class="rounded-circle mb-2" width="70" height="70">
      <h4>Book: <?php echo htmlspecialchars($car['title']); ?></h4>
    </div>

    <?php echo $message; ?>

    <?php if ($alreadyBooked): ?>
      <div class="alert alert-warning text-center">This car is already confirmed for upcoming dates.</div>
    <?php else: ?>
      <form method="POST" id="bookingForm">
        <div class="mb-3">
          <label class="form-label">Booking Type</label>
          <select name="type" id="type" required class="form-select">
            <option value="self">Self-Driving</option>
            <option value="driver">Rental with Driver</option>
          </select>
        </div>

        <div class="row mb-3">
          <div class="col">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" required class="form-control">
          </div>
          <div class="col">
            <label class="form-label">Pickup Time</label>
            <input type="time" name="pickup_time" id="pickup_time" class="form-control">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" required class="form-control">
          </div>
          <div class="col">
            <label class="form-label">Drop Time</label>
            <input type="time" name="drop_time" id="drop_time" class="form-control">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Pickup Location</label>
          <input type="text" name="pickup" id="pickup" class="form-control" placeholder="Pickup location">
          <div id="pickupNote" class="form-text text-muted d-none">Will be filled by owner after confirmation</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Drop Location</label>
          <input type="text" name="drop" id="drop" class="form-control" placeholder="Drop location">
          <div id="dropNote" class="form-text text-muted d-none">Will be filled by owner after confirmation</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Mobile (optional)</label>
          <input type="text" name="mobile" class="form-control">
        </div>

        <div class="mb-3">
          <label class="form-label">Estimated Cost</label>
          <input type="text" id="estimation" class="form-control" readonly>
        </div>

        <button type="submit" class="btn btn-success w-100">Request Booking</button>
      </form>
    <?php endif; ?>

    <div class="mt-3 text-center">
      <a href="search_cabs.php" class="btn btn-outline-secondary btn-sm">← Back to Search</a>
    </div>
  </div>
</div>

<script>
const rentPerDay = <?php echo $car['rent_per_day']; ?>;

function toggleFields() {
  const type = document.getElementById("type").value;
  const pickup = document.getElementById("pickup");
  const drop = document.getElementById("drop");
  const pickupNote = document.getElementById("pickupNote");
  const dropNote = document.getElementById("dropNote");

  if (type === "self") {
    pickup.readOnly = true;
    drop.readOnly = true;
    pickup.value = "";
    drop.value = "";
    pickupNote.classList.remove('d-none');
    dropNote.classList.remove('d-none');
  } else {
    pickup.readOnly = false;
    drop.readOnly = false;
    pickupNote.classList.add('d-none');
    dropNote.classList.add('d-none');
  }
}

function updateEstimation() {
  const type = document.getElementById("type").value;
  const start = new Date(document.getElementById("start_date").value);
  const end = new Date(document.getElementById("end_date").value);

  if (start && end && end >= start) {
    const days = Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)));
    const base = rentPerDay * days;
    const driver = type === "driver" ? 1000 * days : 0;
    const total = base + driver;
    document.getElementById("estimation").value = "₹" + total.toFixed(2);
  } else {
    document.getElementById("estimation").value = "";
  }
}

document.getElementById("type").addEventListener("change", () => {
  toggleFields();
  updateEstimation();
});
document.getElementById("start_date").addEventListener("change", updateEstimation);
document.getElementById("end_date").addEventListener("change", updateEstimation);

toggleFields(); // On page load
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
