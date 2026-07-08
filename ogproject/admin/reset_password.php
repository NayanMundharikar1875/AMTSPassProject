<?php
$conn = new mysqli('localhost', 'root', '', 'amts');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $new_password = password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT);

    // Check if the email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        // Update password
        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $new_password, $email);
        if ($update->execute()) {
            // Redirect using JavaScript (to show alert first)
            echo "<script>
                    alert('✅ Password successfully updated!');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        } else {
            echo "<script>alert('❌ Failed to update password.');</script>";
        }
        $update->close();
    } else {
        echo "<script>alert('❌ No account found with this email.');</script>";
    }

    $check->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  
  <!-- Include Font Awesome for the eye icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .forgot-container {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }

    .forgot-container h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 15px;
      position: relative;
    }

    .form-group input {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    .toggle-password {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
    }

    button {
      width: 100%;
      padding: 10px;
      background: #5c7cfa;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="forgot-container">
    <h2>Reset Your Password</h2>
    <form method="POST" action="">
      <div class="form-group">
        <input type="email" name="email" placeholder="Enter your email" required />
      </div>
      <div class="form-group">
        <input type="password" id="password" name="new_password" placeholder="Enter new password" required>
        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
      </div>
      <button type="submit">Reset Password</button>
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
