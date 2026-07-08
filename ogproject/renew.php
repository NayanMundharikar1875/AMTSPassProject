<?php
$db_host = 'localhost';
$db_name = 'amts';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errorMessage = '';
$passDetails = null;
$showForm = false;

// Check if pass exists
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_pass'])) {
    $passNumber = trim($_POST['pass_number']);
    
    if (empty($passNumber)) {
        $errorMessage = "Please enter your pass number";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM pass_applications WHERE pass_number = :pass_number");
            $stmt->execute([':pass_number' => $passNumber]);
            $passDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($passDetails) {
                $showForm = true;
            } else {
                $errorMessage = "Pass not found. Please check your pass number and try again.";
            }
        } catch(PDOException $e) {
            $errorMessage = "Database error: " . $e->getMessage();
        }
    }
}

// Process renewal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['renew_pass'])) {
    $passNumber = $_POST['pass_number'];
    $originalPassId = $_POST['original_pass_id'];
    $fullName = htmlspecialchars($_POST['full_name']);
    $mobile = htmlspecialchars($_POST['mobile']);
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : null;
    $address = htmlspecialchars($_POST['address']);
    $passType = htmlspecialchars($_POST['pass_type']);
    $duration = intval($_POST['duration']);
    
    // Calculate expiry date
    $expiryDate = date('Y-m-d', strtotime("+$duration months"));
    
    // Payment method
    $paymentMethod = $_POST['payment'] ?? 'card';
    
    // Card details (only if card payment)
    $cardNumber = null;
    $cardName = null;
    $cardExpiry = null;
    $cvv = null;
    
    if ($paymentMethod === 'card') {
        $cardNumber = isset($_POST['card-number']) ? preg_replace('/[^0-9]/', '', $_POST['card-number']) : null;
        $cardName = isset($_POST['card-name']) ? htmlspecialchars($_POST['card-name']) : null;
        $cardExpiry = isset($_POST['expiry-date']) ? htmlspecialchars($_POST['expiry-date']) : null;
        $cvv = isset($_POST['cvv']) ? htmlspecialchars($_POST['cvv']) : null;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO pass_renewals 
            (pass_number, original_pass_id, full_name, mobile, email, address, pass_type, 
             renewal_duration, renewal_expiry_date, payment_method, card_number, card_name, card_expiry, cvv)
            VALUES
            (:pass_number, :original_pass_id, :full_name, :mobile, :email, :address, :pass_type, 
             :renewal_duration, :renewal_expiry_date, :payment_method, :card_number, :card_name, :card_expiry, :cvv)
        ");
        
        $stmt->execute([
            ':pass_number' => $passNumber,
            ':original_pass_id' => $originalPassId,
            ':full_name' => $fullName,
            ':mobile' => $mobile,
            ':email' => $email,
            ':address' => $address,
            ':pass_type' => $passType,
            ':renewal_duration' => $duration,
            ':renewal_expiry_date' => $expiryDate,
            ':payment_method' => $paymentMethod,
            ':card_number' => $cardNumber,
            ':card_name' => $cardName,
            ':card_expiry' => $cardExpiry,
            ':cvv' => $cvv
        ]);

        $lastInsertId = $conn->lastInsertId();
        header("Location: renewal_confirmation.php?id=" . $lastInsertId);
        exit();

    } catch(PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Renew AMTS Pass | Ahmedabad Municipal Transport Service</title>
  <link rel="stylesheet" href="renew.css">
  <link rel="shortcut icon" href="amtslogo.jpeg" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
   .pass-details {
  background-color: #ffffff;
  padding: 25px;
  border-radius: 12px;
  margin-bottom: 25px;
  border-left: 5px solid #28a745;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.pass-details:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
}

.pass-details h3 {
  color: #28a745;
  margin-bottom: 20px;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.pass-details h3::before {
  content: "✓";
  display: inline-block;
  background-color: #e8f5e9;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.pass-info {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 15px;
}

.pass-info-item {
  flex: 1 1 220px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.pass-info-item:hover {
  background-color: #e9ecef;
}

.pass-info-item .label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
  margin-bottom: 5px;
  display: block;
}

.pass-info-item .value {
  color: #212529;
  font-size: 1.1rem;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  background-color: #e8f5e9;
  color: #28a745;
}

.renew-form {
  display: <?php echo $showForm ? 'block' : 'none'; ?>;
  animation: fadeIn 0.4s ease-out;
}

#checkPassForm {
  max-width: 650px;
  margin: 30px auto;
  padding: 30px;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.action-buttons {
  display: flex;
  gap: 15px;
  margin-top: 25px;
  flex-wrap: wrap;
}

.btn-primary {
  background-color: #28a745;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background-color: #218838;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #28a745;
  color: #28a745;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-outline:hover {
  background-color: #f1f8e9;
  transform: translateY(-2px);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .pass-info {
    gap: 15px;
  }
  
  .pass-info-item {
    flex: 1 1 100%;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .btn-primary, .btn-outline {
    width: 100%;
  }
}
  </style>
</head>
<body>
  <!-- Same header, nav, and footer as passform.php -->
  <div class="top-header">
    <div class="container">
      <div class="top-header-content">
        <div class="date-time">
          <span id="current-date"></span>
          <span id="current-time"></span>
        </div>
        <div class="top-links">
        <a href="contactus.html"><i class="fas fa-question-circle"></i> Help</a>
        <a href="contactus.html"><i class="fas fa-phone"></i> Contact</a>
        </div>
      </div>
    </div>
  </div>

  <header>
    <div class="container header-content">
      <div class="logo">
        <img src="amtslogo.jpeg" alt="AMTS Logo">
        <div class="logo-text">
          <h1>AMTS</h1>
          <p>Ahmedabad Municipal Transport Service</p>
        </div>
      </div>
      <div class="user-actions">
        <a href="#" class="user-btn"><i class="fas fa-user"></i> Login</a>
        <a href="#" class="user-btn"><i class="fas fa-user-plus"></i> Register</a>
      </div>
    </div>
  </header>

  <nav>
        <div class="container">
            <ul class="main-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.html" ><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href=""><i class="fas fa-bus"></i> Information</a>
                    <ul class="sub-menu">
                        <li><a href="trafficdepartment.html"><i class="fas fa-list"></i> Traffic Management</a></li>
                        <li><a href="generaladmin.html" class="active"><i class="fas fa-redo"></i> General Administration</a></li>
                        <li><a href="workshopdepartment.html"><i class="fas fa-receipt"></i> Workshop Department</a></li>
                    </ul>
                </li>
                <li><a href="#" class="active"><i class="fas fa-id-card"></i> Pass Management</a>
                    <ul class="sub-menu">
                        <li><a href="passcategories.html"><i class="fas fa-list"></i> View Pass Categories</a></li>
                        <li><a href="renew.php"><i class="fas fa-redo"></i> Renew Pass</a></li>
                        <li><a href="ereceipt.php"><i class="fas fa-receipt"></i> Download E-Receipt</a></li>
                    </ul>
                </li>
                <li><a href="#"><i class="fas fa-headset"></i> Support</a>
                    <ul class="sub-menu">
                        <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
                    </ul>
                </li>
                <li><a href="contactus.html"><i class="fas fa-user-cog"></i> Contect Us</a></li>
            </ul>
        </div>
    </nav>

  <section class="hero pass-hero">
    <div class="container">
      <h2>Renew Your AMTS Bus Pass</h2>
      <p>Extend your bus pass validity for continued convenient travel across Ahmedabad</p>
    </div>
  </section>

  <section class="pass-form-section">
    <div class="container">
      <div class="section-title">
        <h2>Pass Renewal</h2>
        <p>Check your pass details and renew for continued service</p>
      </div>

      <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
      <?php endif; ?>

      <form id="checkPassForm" method="POST" action="renew.php">
        <div class="form-group">
          <label for="pass_number">Enter Your Pass Number <span class="required">*</span></label>
          <input type="text" id="pass_number" name="pass_number" placeholder="AMTS20230425001" required>
        </div>
        <div class="form-actions">
          <button type="submit" name="check_pass" class="btn btn-primary">Check Pass</button>
        </div>
      </form>

      <?php if ($passDetails): ?>
        <div class="pass-details">
          <h3>Pass Found</h3>
          <div class="pass-info">
            <div class="pass-info-item">
              <span class="label">Pass Number:</span>
              <span><?php echo htmlspecialchars($passDetails['pass_number']); ?></span>
            </div>
            <div class="pass-info-item">
              <span class="label">Pass Type:</span>
              <span><?php echo htmlspecialchars($passDetails['pass_type']); ?></span>
            </div>
            <div class="pass-info-item">
              <span class="label">Holder Name:</span>
              <span><?php echo htmlspecialchars($passDetails['full_name']); ?></span>
            </div>
            <div class="pass-info-item">
              <span class="label">Expiry Date:</span>
              <span><?php echo date('d-m-Y', strtotime($passDetails['pass_expiry_date'])); ?></span>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <form class="form-container renew-form" id="renewPassForm" method="POST" action="renew.php">
        <input type="hidden" name="pass_number" value="<?php echo $passDetails ? htmlspecialchars($passDetails['pass_number']) : ''; ?>">
        <input type="hidden" name="original_pass_id" value="<?php echo $passDetails ? $passDetails['id'] : ''; ?>">
        <input type="hidden" name="pass_type" value="<?php echo $passDetails ? htmlspecialchars($passDetails['pass_type']) : ''; ?>">
        
        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="full-name">Full Name <span class="required">*</span></label>
              <input type="text" id="full-name" name="full_name" value="<?php echo $passDetails ? htmlspecialchars($passDetails['full_name']) : ''; ?>" required>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group">
              <label for="mobile">Mobile Number <span class="required">*</span></label>
              <input type="tel" id="mobile" name="mobile" value="<?php echo $passDetails ? htmlspecialchars($passDetails['mobile']) : ''; ?>" required>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?php echo $passDetails ? htmlspecialchars($passDetails['email']) : ''; ?>">
        </div>

        <div class="form-group">
          <label for="address">Address <span class="required">*</span></label>
          <textarea id="address" name="address" rows="3" required><?php echo $passDetails ? htmlspecialchars($passDetails['address']) : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label for="duration">Renewal Duration <span class="required">*</span></label>
          <select name="duration" id="duration" required>
            <option value="">Select Duration</option>
            <option value="1">1 Month</option>
            <option value="3">3 Months</option>
            <option value="6">6 Months</option>
            <option value="12">12 Months</option>
          </select>
        </div>

        <div class="payment-options">
          <h4>Choose Payment Method</h4>
          <div class="payment-methods">
            <div class="payment-method selected">
              <i class="fas fa-credit-card"></i>
              <p>Credit/Debit Card</p>
              <input type="radio" name="payment" value="card" checked>
            </div>
            <div class="payment-method">
              <i class="fas fa-mobile-alt"></i>
              <p>UPI Payment</p>
              <input type="radio" name="payment" value="upi">
            </div>
            <div class="payment-method">
              <i class="fas fa-university"></i>
              <p>Net Banking</p>
              <input type="radio" name="payment" value="netbanking">
            </div>
          </div>
        </div>

        <div id="cardDetails" class="card-details">
          <div class="form-group">
            <label for="card-number">Card Number <span class="required">*</span></label>
            <input type="text" id="card-number" name="card-number" placeholder="1234 5678 9012 3456">
          </div>
          <div class="form-group">
            <label for="card-name">Name on Card <span class="required">*</span></label>
            <input type="text" id="card-name" name="card-name">
          </div>
          <div class="form-row">
            <div class="form-col">
              <div class="form-group">
                <label for="expiry-date">Expiry Date <span class="required">*</span></label>
                <input type="month" id="expiry-date" name="expiry-date" placeholder="MM/YY">
              </div>
            </div>
            <div class="form-col">
              <div class="form-group">
                <label for="cvv">CVV <span class="required">*</span></label>
                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3">
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="reset" class="btn btn-secondary">Reset Form</button>
          <button type="submit" name="renew_pass" class="btn btn-primary">Renew Pass</button>
        </div>
      </form>
    </div>
  </section>

  <footer>
    <div class="container">
      <div class="footer-grid">
        <div class="footer-col">
          <h3>About AMTS</h3>
          <p>Ahmedabad Municipal Transport Service (AMTS) is the public transport provider for Ahmedabad city, serving millions of passengers daily.</p>
        </div>
        <div class="footer-col">
          <h3>Quick Links</h3>
          <ul>
              <li><a href="passcategories.html">Pass Categories</a></li>
              <li><a href="renew.php"> Renew Pass </a></li>
              <li><a href="ereceipt.php">Download E-Receipt</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h3>Help & Support</h3>
          <ul>
            <li><a href="contactus.html">FAQs</a></li>
            <li><a href="feedback.php">Give Feedback</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h3>Contact Us</h3>
          <p><i class="fas fa-map-marker-alt"></i> AMTS Head Office, Ahmedabad</p>
          <p><i class="fas fa-phone"></i> +91 79 12345678</p>
          <p><i class="fas fa-envelope"></i> info@amts.com</p>
          <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/amtsahmedabad/"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2023 Ahmedabad Municipal Transport Service. All Rights Reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
        document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
    }
    setInterval(updateDateTime, 1000);
    updateDateTime();

    // Card formatting
    document.getElementById('card-number').addEventListener('input', function (e) {
        this.value = this.value.replace(/[^\d\s]/g, '').replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    // Card field toggle
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function () {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;

            const methodName = this.querySelector('p').textContent.trim().toLowerCase();
            const cardDetails = document.getElementById('cardDetails');
            if (methodName.includes("card")) {
                cardDetails.style.display = 'block';
                document.getElementById('card-number').required = true;
                document.getElementById('card-name').required = true;
                document.getElementById('expiry-date').required = true;
                document.getElementById('cvv').required = true;
            } else {
                cardDetails.style.display = 'none';
                document.getElementById('card-number').required = false;
                document.getElementById('card-name').required = false;
                document.getElementById('expiry-date').required = false;
                document.getElementById('cvv').required = false;
            }
        });
    });
  </script>
</body>
</html>