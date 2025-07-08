<?php
session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$ownerID = $_SESSION['owner_id'];
$ownerName = $_SESSION['owner_name'] ?? 'Cab Owner';

// --- Notification Handling ---
if (isset($_POST['mark_read'])) {
    $nid = intval($_POST['notification_id']);
    $con->query("UPDATE notifications SET is_read = 1 WHERE id = $nid AND owner_id = $ownerID");
}

if (isset($_POST['delete_notification'])) {
    $nid = intval($_POST['notification_id']);
    $con->query("DELETE FROM notifications WHERE id = $nid AND owner_id = $ownerID");
}

if (isset($_POST['logout'])) {
    session_destroy();
    echo "<script>window.location.href='owner_login.php';</script>";
    exit();
}

$countResult = $con->query("SELECT COUNT(*) AS unread_count FROM notifications WHERE owner_id = $ownerID AND is_read = 0");
$unreadCount = ($countResult->num_rows) ? $countResult->fetch_assoc()['unread_count'] : 0;

$notifications = [];
$result = $con->query("SELECT * FROM notifications WHERE owner_id = $ownerID ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Owner Dashboard - Cabzi</title>
    <link rel="icon" href="logo.jpg" type="image/jpeg">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f1f3f5;
            font-family: 'Segoe UI', sans-serif;
        }

        .logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -6px;
            background: red;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
        }

        .btn-tile {
            aspect-ratio: 1 / 1;
            padding: 10px;
            font-size: 1.6rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            border-radius: 0;
        }

        .btn-tile i {
            font-size: 1.6rem;
            margin-bottom: 3px;
        }


        .dashboard-wrapper {
            min-height: 100vh;
        }

        .dashboard-card {
            max-width: 900px;
            width: 100%;
            background: #fff;
            border-radius: 1rem;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .modal-body .btn {
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container dashboard-wrapper d-flex align-items-center justify-content-center">
        <div class="dashboard-card">
            <!-- Topbar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <img src="logo.jpg" class="logo" alt="Logo">
                    <h5 class="mb-0">Welcome, <?php echo htmlspecialchars($ownerName); ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notifications -->
                    <div class="position-relative" role="button" onclick="showNotifications()">
                        <i class="bi bi-bell fs-4 text-primary"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Logout -->
                    <button class="btn btn-outline-danger btn-sm" onclick="confirmLogout()">Logout</button>
                </div>
            </div>

            <!-- Square Buttons -->
            <div class="row g-3 text-center">
                <div class="col-6 col-md-4">
                    <a href="add_car.php" class="btn btn-primary btn-tile w-100"><i class="bi bi-plus-circle"></i>Add
                        Cars</a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="my_cars.php" class="btn btn-success btn-tile w-100"><i class="bi bi-car-front-fill"></i>My
                        Cars</a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="bookings.php" class="btn btn-warning btn-tile w-100"><i
                            class="bi bi-calendar-check-fill"></i>Bookings</a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="owner_reports.php" class="btn btn-dark btn-tile w-100"><i
                            class="bi bi-graph-up"></i>Report</a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="edit_owner_profile.php" class="btn btn-secondary btn-tile w-100"><i
                            class="bi bi-person-lines-fill"></i>Edit Profile</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notifications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center">No notifications.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $note): ?>
                            <div class="border rounded p-3 mb-3 <?php echo $note['is_read'] ? 'bg-light' : ''; ?>">
                                <p class="mb-1"><?php echo htmlspecialchars($note['message']); ?></p>
                                <small class="text-muted"><?php echo $note['created_at']; ?></small>
                                <form method="post" class="mt-2 d-flex justify-content-end gap-2">
                                    <input type="hidden" name="notification_id" value="<?php echo $note['id']; ?>">
                                    <?php if (!$note['is_read']): ?>
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success">Mark as
                                            Read</button>
                                    <?php endif; ?>
                                    <button type="submit" name="delete_notification"
                                        class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">Are you sure you want to logout?</div>
                <div class="modal-footer">
                    <form method="post">
                        <button type="submit" name="logout" class="btn btn-danger">Yes, Logout</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            var logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
            logoutModal.show();
        }

        function showNotifications() {
            var notifModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            notifModal.show();
        }
    </script>
</body>

</html>