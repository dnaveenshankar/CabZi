<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$search = '';
$location = '';
$cars = [];

// Handle Search
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $search = trim($_POST['search']);
    $location = trim($_POST['location']);

    $query = "SELECT * FROM cab_cars WHERE is_available = 1";
    $params = [];

    if ($search !== '') {
        $query .= " AND (reg_number LIKE ? OR car_name LIKE ? OR model LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = &$searchTerm;
        $params[] = &$searchTerm;
        $params[] = &$searchTerm;
    }

    if ($location !== '') {
        $query .= " AND location = ?";
        $params[] = &$location;
    }

    $stmt = $con->prepare($query);

    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $cars = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search Cabs - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    .logo {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 50%;
    }
    .car-card {
      border: 1px solid #ccc;
      border-radius: 10px;
      overflow: hidden;
      transition: 0.3s;
    }
    .car-card:hover {
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .car-img {
      height: 180px;
      width: 100%;
      object-fit: cover;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <div class="text-center mb-4">
    <img src="logo.jpg" class="logo mb-2" alt="Cabzi">
    <h4>Search Available Cabs</h4>
  </div>

  <!-- Search Form -->
  <form method="POST" class="row g-3 mb-4">
    <div class="col-md-5">
      <input type="text" name="search" placeholder="TN number, car name, model" class="form-control" value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="col-md-4">
      <select name="location" class="form-select">
        <option value="">All Locations</option>
        <?php
        include 'tn_districts.php'; // contains $districts = ['Chennai', 'Coimbatore', ...];
        foreach ($districts as $dist) {
            $selected = ($location == $dist) ? 'selected' : '';
            echo "<option value='$dist' $selected>$dist</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-md-3 d-grid">
      <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
    </div>
  </form>

  <!-- Search Results -->
  <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <?php if (empty($cars)): ?>
      <p class="text-muted text-center">No matching cars found.</p>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($cars as $car): ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="car-card">
              <img src="<?php echo htmlspecialchars($car['car_image']); ?>" class="car-img" alt="Car">
              <div class="p-3">
                <h5><?php echo htmlspecialchars($car['title']); ?></h5>
                <p class="mb-1 text-muted"><?php echo htmlspecialchars($car['car_name'] . ' - ' . $car['model']); ?></p>
                <p class="mb-1"><strong>Reg:</strong> <?php echo htmlspecialchars($car['reg_number']); ?></p>
                <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($car['location']); ?></p>
                <p class="mb-1"><strong>Rate/km:</strong> ₹<?php echo $car['rate_per_km']; ?> |
                  <strong>Rent/day:</strong> ₹<?php echo $car['rent_per_day']; ?></p>
                <div class="d-grid mt-2">
                  <a href="book_car.php?id=<?php echo $car['id']; ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle"></i> Book Now
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
  <!-- Back Button -->
  <div class="mt-4 text-center">
    <a href="user_dashboard.php" class="btn btn-outline-secondary btn-sm">
      ← Back to Dashboard
    </a>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
