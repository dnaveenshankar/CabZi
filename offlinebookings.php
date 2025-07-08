<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$message = "";
$owner_id = $_SESSION['owner_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = intval($_POST['car_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? '';
    $drop_time = $_POST['drop_time'] ?? '';
    $mobile = trim($_POST['mobile'] ?? '');
    $type = $_POST['type'] ?? '';
    $pickup = trim($_POST['pickup'] ?? '');
    $drop = trim($_POST['drop'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $final_cost = floatval($_POST['final_cost'] ?? 0);
    $status = $_POST['status'] ?? 'Confirmed';

    // Handle offline customer
    $customer_option = $_POST['customer_option'] ?? '';
    if ($customer_option === 'new') {
        $new_name = trim($_POST['new_name'] ?? '');
        $new_mobile = trim($_POST['new_mobile'] ?? '');
        $new_address = trim($_POST['new_address'] ?? '');

        $insertCustomer = $con->prepare("INSERT INTO offline_customers (name, mobile, address) VALUES (?, ?, ?)");
        $insertCustomer->bind_param("sss", $new_name, $new_mobile, $new_address);
        $insertCustomer->execute();
        $offline_customer_id = $insertCustomer->insert_id;
        $user_id = 0; // no linked cab_users
    } else {
        $offline_customer_id = intval($_POST['existing_customer_id'] ?? 0);
        $user_id = 0;
    }

    // Get estimation
    $days = max((strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24), 1);
    $rent_query = $con->prepare("SELECT rent_per_day FROM cab_cars WHERE id = ? AND owner_id = ?");
    $rent_query->bind_param("ii", $car_id, $owner_id);
    $rent_query->execute();
    $rent_result = $rent_query->get_result();
    $car = $rent_result->fetch_assoc();
    $rent_per_day = $car['rent_per_day'] ?? 0;
    $estimation = $rent_per_day * $days;

    // Insert booking
    $stmt = $con->prepare("INSERT INTO cab_bookings (user_id, offline_customer_id, car_id, start_date, end_date, pickup_location, drop_location, pickup_time, drop_time, mobile, type, estimation, final_cost, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssssssssdss", $user_id, $offline_customer_id, $car_id, $start_date, $end_date, $pickup, $drop, $pickup_time, $drop_time, $mobile, $type, $estimation, $final_cost, $remarks, $status);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success text-center'>Offline booking created successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Error saving booking. Please try again.</div>";
    }
}

// Fetch cars for dropdown
$cars = [];
$stmt = $con->prepare("SELECT id, title FROM cab_cars WHERE owner_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cars[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Offline Booking - Cabzi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .form-container {
        max-width: 800px;
        margin: auto;
        padding: 2rem;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="form-container">
    <h4 class="text-center mb-4">Offline Cab Booking</h4>
    <?php echo $message; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Car</label>
        <select name="car_id" class="form-select" required>
          <option value="">Select Car</option>
          <?php foreach ($cars as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Booking Type</label>
        <select name="type" class="form-select" required>
          <option value="self">Self-Driving</option>
          <option value="driver">Rental with Driver</option>
        </select>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control" required>
        </div>
        <div class="col">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control" required>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Pickup Time</label>
          <input type="time" name="pickup_time" class="form-control">
        </div>
        <div class="col">
          <label class="form-label">Drop Time</label>
          <input type="time" name="drop_time" class="form-control">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Pickup Location</label>
        <input type="text" name="pickup" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Drop Location</label>
        <input type="text" name="drop" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Mobile</label>
        <input type="text" name="mobile" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Final Cost</label>
        <input type="number" name="final_cost" class="form-control" step="0.01">
      </div>

      <div class="mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Booking Status</label>
        <select name="status" class="form-select">
          <option value="Confirmed">Confirmed</option>
          <option value="Pending">Pending</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>

      <hr>
      <h5>Customer Info</h5>
      <div class="mb-3">
        <label class="form-label">Customer Option</label>
        <select name="customer_option" class="form-select">
          <option value="new">New Customer</option>
          <option value="existing">Existing Offline Customer</option>
        </select>
      </div>

      <div id="newCustomerFields">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="new_name" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Mobile</label>
          <input type="text" name="new_mobile" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Address</label>
          <input type="text" name="new_address" class="form-control">
        </div>
      </div>
<div class="mb-3 text-end">
  <a href="owner_dashboard.php" class="btn btn-outline-secondary">← Back to Dashboard</a>
</div>

      <button type="submit" class="btn btn-primary w-100">Submit Booking</button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
