<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cabzi</title>
  <link rel="icon" href="logo.jpg" type="image/jpeg" />

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Custom CSS -->
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
    }

    .logo {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 50%;
    }

    .section-card {
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: 0.3s;
    }

    .section-card:hover {
      transform: translateY(-5px);
    }

    .btn-custom {
      width: 100%;
      margin-bottom: 10px;
    }

    @media (min-width: 768px) {
      .btn-custom {
        width: 45%;
        margin: 5px;
      }
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="w-100">
      <div class="text-center mb-4">
        <img src="logo.jpg" alt="Cabzi Logo" class="logo mb-3" />
        <h2 class="fw-bold">Welcome to Cabzi</h2>
        <p class="text-muted">Your one-stop solution for cab rentals and car sharing</p>
      </div>

      <div class="row justify-content-center g-4">
        <!-- Cab Owner Section -->
        <div class="col-md-5">
          <div class="p-4 bg-white section-card text-center">
            <h4>Cab Owners</h4>
            <p class="text-muted">Want to leave your car for rentals?</p>
            <div class="d-flex flex-column flex-md-row justify-content-center">
              <a href="owner_login.php" class="btn btn-primary btn-custom">Login</a>
              <a href="owner_signup.php" class="btn btn-outline-primary btn-custom">Signup</a>
            </div>
          </div>
        </div>

        <!-- Cab User Section -->
        <div class="col-md-5">
          <div class="p-4 bg-white section-card text-center">
            <h4>Cab Users</h4>
            <p class="text-muted">Looking for a cab or rental cars?</p>
            <div class="d-flex flex-column flex-md-row justify-content-center">
              <a href="user_login.php" class="btn btn-success btn-custom">Login</a>
              <a href="user_signup.php" class="btn btn-outline-success btn-custom">Signup</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
