<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_id = $_SESSION['owner_id'];
    $title = trim($_POST['title']);
    $car_name = trim($_POST['car_name']);
    $model = trim($_POST['model']);
    $reg_number = strtoupper(trim($_POST['reg_number']));
    $seating_capacity = intval($_POST['seating_capacity']);
    $rent_per_day = floatval($_POST['rent_per_day']);
    $rate_per_km = floatval($_POST['rate_per_km']);
    $security_deposit = floatval($_POST['security_deposit']);
    $location = $_POST['location'];
    $is_available = ($_POST['is_available'] === '1') ? 1 : 0;
    $car_details = trim($_POST['car_details']);

    // Check for duplicate registration number
    $check = $con->prepare("SELECT id FROM cab_cars WHERE reg_number = ?");
    $check->bind_param("s", $reg_number);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Car with this registration number already exists.</div>";
    } else {
        // Image upload
        $imgName = $_FILES['car_image']['name'];
        $tmp = $_FILES['car_image']['tmp_name'];
        $imgPath = "uploads/" . uniqid() . "_" . basename($imgName);

        if (move_uploaded_file($tmp, $imgPath)) {
            $stmt = $con->prepare("INSERT INTO cab_cars (owner_id, title, car_name, model, reg_number, seating_capacity, rent_per_day, rate_per_km, security_deposit, location, is_available, car_details, car_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssidddsiss", $owner_id, $title, $car_name, $model, $reg_number, $seating_capacity, $rent_per_day, $rate_per_km, $security_deposit, $location, $is_available, $car_details, $imgPath);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Car added successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Database error while inserting car.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Failed to upload car image.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Car - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f2f4f8;
    }
    .form-box {
      max-width: 700px;
      margin: auto;
      background: white;
      padding: 30px;
      margin-top: 40px;
      margin-bottom: 40px;
      border-radius: 1rem;
      box-shadow: 0 0 20px rgba(0,0,0,0.08);
    }
  </style>
</head>
<body>

<div class="container">
  <div class="form-box">
    <h4 class="mb-4 text-center">Add a New Car</h4>
    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">

      <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" required class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Car Name</label>
        <input type="text" name="car_name" required class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Model</label>
        <input type="text" name="model" required class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Registration Number</label>
        <input type="text" name="reg_number" required class="form-control text-uppercase">
      </div>

      <div class="mb-3">
        <label class="form-label">Seating Capacity (without driver)</label>
        <input type="number" name="seating_capacity" required class="form-control" min="2" max="20">
      </div>

      <div class="mb-3">
        <label class="form-label">Rent Per Day (₹)</label>
        <input type="number" name="rent_per_day" required class="form-control" min="0" step="0.01">
      </div>

      <div class="mb-3">
        <label class="form-label">Rate Per KM (₹)</label>
        <input type="number" name="rate_per_km" required class="form-control" min="0" step="0.01">
      </div>

      <div class="mb-3">
        <label class="form-label">Security Deposit (₹)</label>
        <input type="number" name="security_deposit" required class="form-control" min="0" step="0.01">
      </div>

      <div class="mb-3">
        <label class="form-label">Location (District)</label>
        <select name="location" class="form-select" required>
          <?php include 'tn_districts.php'; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Availability</label>
        <select name="is_available" class="form-select" required>
          <option value="1">Available</option>
          <option value="0">Not Available</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Car Details / Description</label>
        <textarea name="car_details" rows="4" class="form-control" required></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Upload Car Image</label>
        <input type="file" name="car_image" accept="image/*" required class="form-control">
      </div>

      <button type="submit" class="btn btn-primary w-100">Add Car</button>
    </form>

    <div class="mt-3 text-center">
      <a href="owner_dashboard.php" class="btn btn-sm btn-secondary">← Back to Dashboard</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
