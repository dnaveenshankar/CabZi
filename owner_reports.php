<?php
session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];

// Total online and offline bookings
$online_sql = "SELECT COUNT(*) as total FROM cab_bookings WHERE car_id IN (SELECT id FROM cab_cars WHERE owner_id = ?) AND offline_customer_id IS NULL";
$offline_sql = "SELECT COUNT(*) as total FROM cab_bookings WHERE car_id IN (SELECT id FROM cab_cars WHERE owner_id = ?) AND offline_customer_id IS NOT NULL";

$stmt1 = $con->prepare($online_sql);
$stmt1->bind_param("i", $owner_id);
$stmt1->execute();
$online = $stmt1->get_result()->fetch_assoc()['total'];

$stmt2 = $con->prepare($offline_sql);
$stmt2->bind_param("i", $owner_id);
$stmt2->execute();
$offline = $stmt2->get_result()->fetch_assoc()['total'];

// Most booked car
$most_booked_sql = "SELECT cc.title, COUNT(*) as total FROM cab_bookings cb JOIN cab_cars cc ON cb.car_id = cc.id WHERE cc.owner_id = ? GROUP BY cb.car_id ORDER BY total DESC LIMIT 1";
$stmt3 = $con->prepare($most_booked_sql);
$stmt3->bind_param("i", $owner_id);
$stmt3->execute();
$most_booked = $stmt3->get_result()->fetch_assoc();

// Most booked date
$date_sql = "SELECT start_date, COUNT(*) as total FROM cab_bookings WHERE car_id IN (SELECT id FROM cab_cars WHERE owner_id = ?) GROUP BY start_date ORDER BY total DESC LIMIT 1";
$stmt4 = $con->prepare($date_sql);
$stmt4->bind_param("i", $owner_id);
$stmt4->execute();
$top_date = $stmt4->get_result()->fetch_assoc();

// Booking types breakdown
$type_sql = "SELECT type, COUNT(*) as total FROM cab_bookings WHERE car_id IN (SELECT id FROM cab_cars WHERE owner_id = ?) GROUP BY type";
$stmt5 = $con->prepare($type_sql);
$stmt5->bind_param("i", $owner_id);
$stmt5->execute();
$type_result = $stmt5->get_result();
$types = [];
while ($row = $type_result->fetch_assoc()) {
    $types[$row['type']] = $row['total'];
}

// Booking statuses breakdown
$status_sql = "SELECT status, COUNT(*) as total FROM cab_bookings WHERE car_id IN (SELECT id FROM cab_cars WHERE owner_id = ?) GROUP BY status";
$stmt6 = $con->prepare($status_sql);
$stmt6->bind_param("i", $owner_id);
$stmt6->execute();
$status_result = $stmt6->get_result();
$statuses = [];
while ($row = $status_result->fetch_assoc()) {
    $statuses[$row['status']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Owner Reports</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container py-5">
  <h3 class="mb-4"><i class="bi bi-bar-chart-fill text-primary"></i> Owner Reports Dashboard</h3>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-bg-primary">
        <div class="card-body">
          <h5><i class="bi bi-cloud-arrow-down-fill"></i> Offline Bookings</h5>
          <h3><?php echo $offline; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-success">
        <div class="card-body">
          <h5><i class="bi bi-globe2"></i> Online Bookings</h5>
          <h3><?php echo $online; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card text-bg-warning">
        <div class="card-body">
          <h5><i class="bi bi-car-front-fill"></i> Most Booked Car</h5>
          <h3><?php echo $most_booked['title'] ?? 'N/A'; ?></h3>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card text-bg-dark">
        <div class="card-body">
          <h5><i class="bi bi-calendar-check"></i> Peak Booking Date</h5>
          <h3><?php echo $top_date['start_date'] ?? 'N/A'; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <canvas id="typeChart"></canvas>
    </div>
    <div class="col-md-4">
      <canvas id="statusChart"></canvas>
    </div>
  </div>

  <a href="owner_dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<script>
const typeData = {
  labels: <?php echo json_encode(array_keys($types)); ?>,
  datasets: [{
    data: <?php echo json_encode(array_values($types)); ?>,
    backgroundColor: ['#0d6efd', '#20c997', '#ffc107'],
  }]
};

const statusData = {
  labels: <?php echo json_encode(array_keys($statuses)); ?>,
  datasets: [{
    data: <?php echo json_encode(array_values($statuses)); ?>,
    backgroundColor: ['#198754', '#dc3545', '#0dcaf0', '#6c757d'],
  }]
};

new Chart(document.getElementById('typeChart'), {
  type: 'pie',
  data: typeData,
  options: { responsive: true, plugins: { title: { display: true, text: 'Booking Types' } } }
});

new Chart(document.getElementById('statusChart'), {
  type: 'pie',
  data: statusData,
  options: { responsive: true, plugins: { title: { display: true, text: 'Booking Status' } } }
});
</script>
</body>
</html>