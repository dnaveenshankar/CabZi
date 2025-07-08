<?php
session_start();
include 'db.php';

if (!isset($_SESSION['owner_id'])) {
    header("Location: owner_login.php");
    exit();
}

$owner_id = $_SESSION['owner_id'];
$message = '';

// Fetch owner data
$stmt = $con->prepare("SELECT * FROM cab_owners WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$owner = $result->fetch_assoc();

if (!$owner) {
    die("Owner not found.");
}

// Update profile
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $password = trim($_POST['password']);

    // Check if email or mobile already exists for others
    $check = $con->prepare("SELECT id FROM cab_owners WHERE (email = ? OR mobile = ?) AND id != ?");
    $check->bind_param("ssi", $email, $mobile, $owner_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Email or Mobile already in use.</div>";
    } else {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $con->prepare("UPDATE cab_owners SET name = ?, email = ?, mobile = ?, password = ? WHERE id = ?");
            $update->bind_param("ssssi", $name, $email, $mobile, $hashed_password, $owner_id);
        } else {
            $update = $con->prepare("UPDATE cab_owners SET name = ?, email = ?, mobile = ? WHERE id = ?");
            $update->bind_param("sssi", $name, $email, $mobile, $owner_id);
        }

        if ($update->execute()) {
            $message = "<div class='alert alert-success'>Profile updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Update failed. Try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <h4 class="text-center mb-4">Edit Owner Profile</h4>
      <?php echo $message; ?>
      <form method="post">
        <div class="mb-3">
          <label>Name</label>
          <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($owner['name']); ?>">
        </div>
        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($owner['email']); ?>">
        </div>
        <div class="mb-3">
          <label>Mobile</label>
          <input type="text" name="mobile" class="form-control" required value="<?php echo htmlspecialchars($owner['mobile']); ?>">
        </div>
        <div class="mb-3">
          <label>New Password <small>(Leave blank to keep current)</small></label>
          <input type="password" name="password" class="form-control">
        </div>
        <div class="d-grid">
          <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
        </div>
        <div class="text-center mt-3">
          <a href="owner_dashboard.php" class="btn btn-sm btn-outline-secondary">← Back to Dashboard</a>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
