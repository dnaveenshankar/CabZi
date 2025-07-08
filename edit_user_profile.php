<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$name = $username = $mobile = $email = '';

// Fetch current user details
$stmt = $con->prepare("SELECT name, username, mobile, email FROM cab_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $username, $mobile, $email);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = trim($_POST['name']);
    $new_username = trim($_POST['username']);
    $new_mobile = trim($_POST['mobile']);
    $new_email = trim($_POST['email']);

    // Check for username conflict
    $checkStmt = $con->prepare("SELECT id FROM cab_users WHERE username = ? AND id != ?");
    $checkStmt->bind_param("si", $new_username, $user_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Username already taken. Please choose another.</div>";
    } else {
        $updateStmt = $con->prepare("UPDATE cab_users SET name = ?, username = ?, mobile = ?, email = ? WHERE id = ?");
        $updateStmt->bind_param("ssssi", $new_name, $new_username, $new_mobile, $new_email, $user_id);
        if ($updateStmt->execute()) {
            $message = "<div class='alert alert-success text-center'>Profile updated successfully.</div>";
            $name = $new_name;
            $username = $new_username;
            $mobile = $new_mobile;
            $email = $new_email;
        } else {
            $message = "<div class='alert alert-danger text-center'>Something went wrong. Please try again.</div>";
        }
        $updateStmt->close();
    }
    $checkStmt->close();
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

  <style>
    body {
      background: #f5f5f5;
    }
    .edit-container {
      width: 500px;
      background: #fff;
      padding: 30px;
      border-radius: 1rem;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .logo {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      object-fit: cover;
    }
  </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="edit-container">
    <div class="text-center mb-4">
      <img src="logo.jpg" class="logo mb-2" alt="Cabzi">
      <h4>Edit Profile</h4>
    </div>

    <?php echo $message; ?>

    <form method="POST">
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" required class="form-control" value="<?php echo htmlspecialchars($name); ?>">
      </div>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" required class="form-control" value="<?php echo htmlspecialchars($username); ?>">
      </div>
      <div class="mb-3">
        <label for="mobile" class="form-label">Mobile</label>
        <input type="text" name="mobile" required class="form-control" value="<?php echo htmlspecialchars($mobile); ?>">
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" required class="form-control" value="<?php echo htmlspecialchars($email); ?>">
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary">Update Profile</button>
      </div>
    </form>

    <div class="text-center mt-3">
      <a href="user_dashboard.php" class="btn btn-outline-secondary btn-sm">← Back to Dashboard</a>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
