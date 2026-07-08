<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'amts');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form values
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if user already exists
    $check_sql = "SELECT * FROM users WHERE name = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $name, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // User already exists
        echo "<script>
                alert('❌ User with this name or email already exists!');
              </script>";
    } else {
        // Proceed with registration
        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            echo "<script>
                    alert('🎉 Registration successful!');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            echo "<script>
                    alert('❌ Error: " . addslashes($stmt->error) . "');
                  </script>";
        }

        $stmt->close();
    }

    $check_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(to right, #74ebd5, #9face6);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .register-container {
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
    }

    .register-container h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      transition: border-color 0.3s;
    }

    .form-group input:focus {
      border-color: #5c7cfa;
      outline: none;
    }

    .toggle-password {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
    }

    .register-btn {
      width: 100%;
      padding: 12px;
      background-color: #5c7cfa;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .register-btn:hover {
      background-color: #4b66e4;
    }

    @media (max-width: 480px) {
      .register-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

  <div class="register-container">
    <h2>Create Account</h2>
    <form action="register.php" method="POST">
      <div class="form-group">
        <input type="text" name="name" placeholder="Full Name" required />
      </div>
      <div class="form-group">
        <input type="email" name="email" placeholder="Email Address" required />
      </div>
      <div class="form-group">
        <input type="password" id="password" name="password" placeholder="Password" required>
        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
      </div>
      <button type="submit" class="register-btn">Register</button>
    </form>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.toggle-password');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
  </script>

</body>
</html>
