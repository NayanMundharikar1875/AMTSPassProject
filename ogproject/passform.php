

<!-- Rest of your HTML remains the same -->

 

 <!DOCTYPE html>
 <html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New AMTS Pass | Ahmedabad Municipal Transport Service</title>
  <link rel="stylesheet" href="passform.css">
  <link rel="shortcut icon" href="amtslogo.jpeg" type="image/x-icon">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
  /* Modal styles */
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

$showSuccessModal = false;
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic input sanitization
    $passType = htmlspecialchars($_POST['pass-type']);
    $fullName = htmlspecialchars($_POST['full-name']);
    $mobile = htmlspecialchars($_POST['mobile']);
    $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : null;
    $address = htmlspecialchars($_POST['address']);
    $duration = intval($_POST['duration']);
    
    // Aadhaar number input
    $aadharNumber = isset($_POST['aadhar-number']) ? preg_replace('/\D/', '', $_POST['aadhar-number']) : '';
    if (strlen($aadharNumber) != 12) {
        $errorMessage = "Aadhaar number must be a 12-digit number.";
    }
    
    // Check for duplicate Aadhaar number
    if (empty($errorMessage)) {
        try {
            $checkStmt = $conn->prepare("SELECT id FROM pass_applications WHERE aadhar_number = :aadhar_number");
            $checkStmt->execute([':aadhar_number' => $aadharNumber]);
            if ($checkStmt->fetch()) {
                $errorMessage = "This Aadhaar number is already registered. Please use a different number or renew your existing pass.";
            }
        } catch(PDOException $e) {
            $errorMessage = "Error checking Aadhaar number: " . $e->getMessage();
        }
    }
    
    // Calculate expiry date (YYYY-MM format)
    $expiryDate = date('Y-m', strtotime("+$duration months"));
    
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

    if (empty($errorMessage)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO pass_applications 
                (pass_type, full_name, mobile, email, address, aadhar_number, payment_method, 
                 card_number, card_name, pass_expiry_date, pass_duration, cvv, created_at)
                VALUES
                (:pass_type, :full_name, :mobile, :email, :address, :aadhar_number, :payment_method, 
                 :card_number, :card_name, :pass_expiry_date, :pass_duration, :cvv, NOW())
            ");
            
            $stmt->execute([
                ':pass_type' => $passType,
                ':full_name' => $fullName,
                ':mobile' => $mobile,
                ':email' => $email,
                ':address' => $address,
                ':aadhar_number' => $aadharNumber,
                ':payment_method' => $paymentMethod,
                ':card_number' => $cardNumber,
                ':card_name' => $cardName,
                ':pass_expiry_date' => $expiryDate,
                ':pass_duration' => $duration,
                ':cvv' => $cvv
            ]);

            $lastInsertId = $conn->lastInsertId();
            $passNumber = 'AMTS' . date('Ymd') . str_pad($lastInsertId, 3, '0', STR_PAD_LEFT);

            $updateStmt = $conn->prepare("UPDATE pass_applications SET pass_number = :pass_number WHERE id = :id");
            $updateStmt->execute([
                ':pass_number' => $passNumber,
                ':id' => $lastInsertId
            ]);

            // Redirect to confirmation page with the ID
            header("Location: confirmation.php?id=" . $lastInsertId);
            exit();

        } catch(PDOException $e) {
            // Check if error is due to duplicate Aadhaar (in case the first check missed it)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'aadhar_number') !== false) {
                $errorMessage = "This Aadhaar number is already registered. Please use a different number or renew your existing pass.";
            } else {
                $errorMessage = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>


  .modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.4);
  }
  .modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
  max-width: 500px;
  border-radius: 8px;
  text-align: center;
  }
  .close-btn {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  }
  .close-btn:hover {
  color: black;
  }
  .card-details {
  display: none;
  margin-top: 15px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 5px;
  }
  </style>
 </head>
 <body>
  <div id="successModal" class="modal" style="<?php echo $showSuccessModal ? 'display:block;' : ''; ?>">
  <div class="modal-content">
  <span class="close-btn" onclick="closeModal()">&times;</span>
  <h3>Application Submitted Successfully!</h3>
  <p>Your AMTS pass application has been received. You will get a confirmation SMS shortly.</p>
  <button onclick="closeModal()" class="btn btn-primary">OK</button>
  </div>
  </div>
 

  <div class="top-header">
  <div class="container">
  <div class="top-header-content">
  <div class="date-time">
  <span id="current-date"></span>
  <span id="current-time"></span>
  </div>
  <div class="top-links">
  <a href="#"><i class="fas fa-question-circle"></i> Help</a>
  <a href="#"><i class="fas fa-phone"></i> Contact</a>
  <a href="#"><i class="fas fa-globe"></i> English</a>
  <a href="#"><i class="fas fa-globe"></i> Gujarati</a>
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
  <h2>Apply for New AMTS Bus Pass</h2>
  <p>Fill out the form below to get your bus pass for convenient travel across Ahmedabad</p>
  </div>
  </section>
 

  <section class="pass-form-section">
  <div class="container">
  <div class="section-title">
  <h2>New Pass Application</h2>
  <p>Please fill all the required details carefully</p>
  </div>
 

  <?php if ($errorMessage): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
  <?php endif; ?>
 
  

  <form class="form-container" id="passForm" method="POST" action="passform.php" enctype="multipart/form-data">
  <div class="form-row">
  <div class="form-col">
  <div class="form-group">
  <label for="pass-type">Pass Type <span class="required">*</span></label>
  <select id="pass-type" name="pass-type" required>
  <option value="">Select Pass Type</option>
  <option value="student">Student Pass</option>
  <option value="senior">Senior Citizen Pass</option>
  <option value="regular">Regular Commuter Pass</option>
  <option value="daily">Daily Pass</option>
  <option value="disable">Disabled Person Pass</option>
  <option value="women">Women's Special Pass</option>
  </select>
  </div>
  </div>
  </div>
 
  <div class="form-group">
  <label for="duration">Pass Duration (in months) <span class="required">*</span></label>
  <select name="duration" id="duration" required>
    <option value="">Select Duration</option>
    <option value="1">1 Month</option>
    <option value="3">3 Months</option>
    <option value="6">6 Months</option>
    <option value="12">12 Months</option>
  </select>
</div>


  <div class="form-row">
  <div class="form-col">
  <div class="form-group">
  <label for="full-name">Full Name <span class="required">*</span></label>
  <input type="text" id="full-name" name="full-name" required>
  </div>
  </div>
  <div class="form-col">
  <div class="form-group">
  <label for="mobile">Mobile Number <span class="required">*</span></label>
  <input type="tel" id="mobile" name="mobile" required>
  </div>
  </div>
  </div>
 

  <div class="form-group">
  <label for="email">Email Address</label>
  <input type="email" id="email" name="email">
  </div>
 
  <div class="form-group">
  <label for="address">Address <span class="required">*</span></label>
  <textarea id="address" name="address" rows="3" required></textarea>
  </div>
 

  <div class="form-group">
  <label for="aadhar-number">Aadhaar Number <span class="required">*</span></label>
  <input type="text" id="aadhar-number" name="aadhar-number" maxlength="12" pattern="\d{12}" required>
</div>



  <div class="payment-options">
                        <h4>Choose Payment Method</h4>
                        <div class="payment-methods">
                            <div class="payment-method selected">
                                <i class="fas fa-credit-card"></i>
                                <p>Credit/Debit Card</p>
                                <input type="radio" name="payment" checked>
                            </div>
                            <div class="payment-method">
                                <i class="fas fa-mobile-alt"></i>
                                <p>UPI Payment</p>
                                <input type="radio" name="payment">
                            </div>
                            <div class="payment-method">
                                <i class="fas fa-university"></i>
                                <p>Net Banking</p>
                                <input type="radio" name="payment">
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
  <label for="expiry_date">Expiry Date <span class="required">*</span></label>
  <input type="month" id="pass_expiry_date" name="passexpiry_date" placeholder="MM/YY">
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
  <button type="submit" class="btn btn-primary">Submit Application</button>
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
  <li><a href="renew.php">Renew Pass</a></li>
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

// Modal
function closeModal() {
    document.getElementById('successModal').style.display = 'none';
}

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
// Add this to your existing script section
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($errorMessage): ?>
        alert("<?php echo addslashes($errorMessage); ?>");
    <?php endif; ?>
});
<?php if ($errorMessage): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
<script>
    window.onload = function() {
        alert("<?php echo addslashes($errorMessage); ?>");
    };
</script>
<?php endif; ?>
</script>
