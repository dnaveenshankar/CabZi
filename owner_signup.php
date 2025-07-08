<?php
include 'db.php';

$name = $username = $mobile = $email = $password = '';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $mobile = trim($_POST['mobile']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if username exists
    $checkQuery = $con->prepare("SELECT id FROM cab_owners WHERE username = ?");
    $checkQuery->bind_param("s", $username);
    $checkQuery->execute();
    $checkQuery->store_result();

    if ($checkQuery->num_rows > 0) {
        $message = "<div class='alert alert-danger'>Username already exists. Please choose another.</div>";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertQuery = $con->prepare("INSERT INTO cab_owners (name, username, mobile, email, password) VALUES (?, ?, ?, ?, ?)");
        $insertQuery->bind_param("sssss", $name, $username, $mobile, $email, $hashedPassword);
        if ($insertQuery->execute()) {
            $message = "<div class='alert alert-success'>Signup successful. You can now <a href='owner_login.php'>login</a>.</div>";
            $name = $username = $mobile = $email = $password = '';
        } else {
            $message = "<div class='alert alert-danger'>Something went wrong. Please try again.</div>";
        }
    }

    $checkQuery->close();
    $insertQuery->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Owner Signup - Cabzi</title>
    <link rel="icon" href="logo.jpg" type="image/jpeg">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f4f4;
        }

        .signup-container {
            width: 500px;
            background: #fff;
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
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="signup-container">
        <div class="text-center mb-4">
            <img src="logo.jpg" alt="Cabzi Logo" class="logo">
            <h4 class="mt-2">Owner Signup</h4>
        </div>

        <?php echo $message; ?>

        <form method="post" action="">
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
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary w-100">Signup</button>
        </form>

        <div class="mt-3 text-center">
            Already have an account? <a href="owner_login.php">Login</a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
