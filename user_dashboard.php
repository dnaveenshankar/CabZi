<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Mark notifications as read (inline logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $stmt = $con->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: user_dashboard.php");
    exit();
}

// Fetch unread notifications
$notif_stmt = $con->prepare("SELECT id, message FROM user_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$notif_count = count($notifications);
$notif_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .dashboard-container {
      width: 500px;
      background: white;
      padding: 30px;
      border-radius: 1rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .logo {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 50%;
    }
    .btn-tile {
      aspect-ratio: 1 / 1;
      font-size: 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .btn-tile i {
      font-size: 24px;
      margin-bottom: 5px;
    }
    .badge-notify {
      position: absolute;
      top: 8px;
      right: 16px;
      background: red;
      color: white;
      font-size: 10px;
      padding: 3px 6px;
      border-radius: 50%;
    }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="dashboard-container text-center position-relative">
    <img src="logo.jpg" alt="Cabzi Logo" class="logo mb-2">
    <h4 class="mb-4">Welcome to User Dashboard</h4>

    <!-- Dashboard Buttons -->
    <div class="row g-3 text-center">
      <div class="col-6">
        <a href="search_cabs.php" class="btn btn-primary btn-tile w-100">
          <i class="bi bi-search"></i>Search Cabs
        </a>
      </div>
      <div class="col-6">
        <a href="my_bookings.php" class="btn btn-success btn-tile w-100">
          <i class="bi bi-journal-check"></i>My Bookings
        </a>
      </div>
      <div class="col-6">
        <a href="edit_user_profile.php" class="btn btn-secondary btn-tile w-100">
          <i class="bi bi-person-lines-fill"></i>Edit Profile
        </a>
      </div>
      <div class="col-6 position-relative">
        <a href="#" class="btn btn-warning btn-tile w-100" data-bs-toggle="modal" data-bs-target="#notifModal">
          <i class="bi bi-bell-fill"></i>Notifications
        </a>
        <?php if ($notif_count > 0): ?>
          <span class="badge-notify"><?php echo $notif_count; ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Logout Button -->
    <div class="mt-4">
      <button class="btn btn-outline-danger" onclick="confirmLogout()">Logout</button>
    </div>
  </div>
</div>

<!-- Modal: Notifications -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notifModalLabel">
          <i class="bi bi-bell-fill text-warning me-2"></i>Notifications
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($notif_count > 0): ?>
          <?php foreach ($notifications as $notif): ?>
            <div class="alert alert-info small mb-2">
              <?php echo htmlspecialchars($notif['message']); ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-center">No new notifications.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <?php if ($notif_count > 0): ?>
          <form method="post" class="me-auto">
            <input type="hidden" name="mark_read" value="1">
            <button type="submit" class="btn btn-sm btn-primary">Mark All as Read</button>
          </form>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Logout Confirmation -->
<script>
  function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "logout.php";
    }
  }

  // Auto show modal if there are notifications
  window.addEventListener("load", function () {
    const notifCount = <?php echo $notif_count; ?>;
    if (notifCount > 0) {
      const modal = new bootstrap.Modal(document.getElementById("notifModal"));
      modal.show();
    }
  });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
