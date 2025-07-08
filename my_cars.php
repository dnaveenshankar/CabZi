<?php
session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
$message = '';

// Toggle availability
if (isset($_POST['toggle_availability'])) {
    $car_id = intval($_POST['car_id']);
    $new_status = intval($_POST['new_status']);
    $update = $con->prepare("UPDATE cab_cars SET is_available = ? WHERE id = ? AND owner_id = ?");
    $update->bind_param("iii", $new_status, $car_id, $owner_id);
    $update->execute();

    if ($new_status == 1) {
        $message = "<div class='alert alert-success text-center'>Car marked as <strong>Available</strong>.</div>";
    } else {
        $message = "<div class='alert alert-warning text-center'>Car marked as <strong>Unavailable</strong>.</div>";
    }
}

// Fetch all cars for this owner
$stmt = $con->prepare("SELECT * FROM cab_cars WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Cars - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background: #f5f7fa;
    }
    .car-card {
      border: 1px solid #ddd;
      border-radius: 10px;
      overflow: hidden;
      cursor: pointer;
      transition: 0.3s;
    }
    .car-card:hover {
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .car-img {
      height: 180px;
      object-fit: cover;
      width: 100%;
    }
  </style>
</head>
<body>

<div class="container py-5">
  <h4 class="text-center mb-4">My Cars</h4>
  <?php echo $message; ?>

  <!-- Search Input -->
  <div class="row justify-content-center mb-4">
    <div class="col-md-6">
      <input type="text" id="searchInput" class="form-control" placeholder="Search by car name, model, TN number or title...">
    </div>
  </div>

  <?php if (empty($cars)): ?>
    <p class="text-muted text-center">No cars added yet.</p>
  <?php else: ?>
    <div class="row g-4" id="carContainer">
      <?php foreach ($cars as $car): ?>
        <?php
          $car_id = $car['id'];
          $title = htmlspecialchars($car['title']);
          $car_name = htmlspecialchars($car['car_name']);
          $model = htmlspecialchars($car['model']);
          $reg = htmlspecialchars($car['reg_number']);
        ?>
        <div class="col-12 col-md-6 col-lg-4 car-card-box">
          <div class="car-card" data-bs-toggle="modal" data-bs-target="#carModal<?php echo $car_id; ?>" data-search="<?php echo strtolower("$title $car_name $model $reg"); ?>">
            <img src="<?php echo $car['car_image']; ?>" class="car-img" alt="Car Image">
            <div class="p-3">
              <h5 class="mb-1"><?php echo $title; ?></h5>
              <p class="mb-1 text-muted"><?php echo $model; ?> | <?php echo $reg; ?></p>
              <span class="badge <?php echo $car['is_available'] ? 'bg-success' : 'bg-danger'; ?>">
                <?php echo $car['is_available'] ? 'Available' : 'Unavailable'; ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="carModal<?php echo $car_id; ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><?php echo $title; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-5">
                    <img src="<?php echo $car['car_image']; ?>" class="img-fluid rounded" alt="Car Image">
                  </div>
                  <div class="col-md-7">
                    <p><strong>Car Name:</strong> <?php echo $car_name; ?></p>
                    <p><strong>Model:</strong> <?php echo $model; ?></p>
                    <p><strong>Registration:</strong> <?php echo $reg; ?></p>
                    <p><strong>Seating:</strong> <?php echo $car['seating_capacity']; ?> seats</p>
                    <p><strong>Rent/Day:</strong> ₹<?php echo $car['rent_per_day']; ?></p>
                    <p><strong>Rate/KM:</strong> ₹<?php echo $car['rate_per_km']; ?></p>
                    <p><strong>Security Deposit:</strong> ₹<?php echo $car['security_deposit']; ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($car['location']); ?></p>
                    <p><strong>Status:</strong> <?php echo $car['is_available'] ? '✅ Available' : '❌ Unavailable'; ?></p>
                    <p><strong>Details:</strong> <?php echo nl2br(htmlspecialchars($car['car_details'])); ?></p>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <form method="post" class="me-auto">
                  <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                  <input type="hidden" name="new_status" value="<?php echo $car['is_available'] ? '0' : '1'; ?>">
                  <button type="submit" name="toggle_availability"
                          class="btn <?php echo $car['is_available'] ? 'btn-danger' : 'btn-success'; ?>">
                    <?php echo $car['is_available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>
                  </button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="mt-4 text-center">
    <a href="owner_dashboard.php" class="btn btn-outline-primary btn-sm">← Back to Dashboard</a>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Filter JS -->
<script>
  const searchInput = document.getElementById('searchInput');
  const carCards = document.querySelectorAll('.car-card-box');

  searchInput.addEventListener('input', function () {
    const searchText = this.value.toLowerCase();

    carCards.forEach(card => {
      const searchableText = card.querySelector('.car-card').getAttribute('data-search');
      if (searchableText.includes(searchText)) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  });
</script>

</body>
</html>
