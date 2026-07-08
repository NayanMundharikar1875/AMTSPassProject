<?php
// Database config
$db_host = 'localhost';
$db_name = 'amts';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$receipt = null;
$error = '';
$isRenewal = false;
$showSearchForm = true;

// Handle both types of requests:
// 1. Direct access with ID (from confirmation pages)
// 2. Search by pass number and mobile (from receipt lookup)

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check for direct access with ID
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $type = $_GET['type'] ?? 'application'; // 'application' or 'renewal'
        $showSearchForm = false;

        if ($type === 'renewal') {
            // Fetch renewal record
            $stmt = $conn->prepare("SELECT r.*, p.pass_number as original_pass_number 
                                  FROM pass_renewals r
                                  JOIN pass_applications p ON r.original_pass_id = p.id
                                  WHERE r.id = :id");
            $stmt->execute([':id' => $id]);
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($receipt) {
                $isRenewal = true;
                $title = "Pass Renewal Receipt";
                $transactionType = "Renewal";
            }
        } else {
            // Fetch application record
            $stmt = $conn->prepare("SELECT * FROM pass_applications WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($receipt) {
                $title = "Pass Application Receipt";
                $transactionType = "New Application";
            }
        }

        if (!$receipt) {
            $error = 'No receipt found for the provided ID.';
            $showSearchForm = true;
        }
    }
    // Check for search by pass number and mobile
    elseif (isset($_GET['pass_number']) && isset($_GET['mobile'])) {
        $passNumber = $_GET['pass_number'];
        $mobile = $_GET['mobile'];

        // First try to find in pass_applications
        $stmt = $conn->prepare("SELECT * FROM pass_applications WHERE pass_number = :pass_number AND mobile = :mobile");
        $stmt->execute([
            ':pass_number' => $passNumber,
            ':mobile' => $mobile
        ]);

        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receipt) {
            // If not found in applications, try pass_renewals
            $stmt = $conn->prepare("SELECT r.*, p.pass_number as original_pass_number 
                                  FROM pass_renewals r
                                  JOIN pass_applications p ON r.original_pass_id = p.id
                                  WHERE r.pass_number = :pass_number AND r.mobile = :mobile");
            $stmt->execute([
                ':pass_number' => $passNumber,
                ':mobile' => $mobile
            ]);
            
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($receipt) {
                $isRenewal = true;
                $title = "Pass Renewal Receipt";
                $transactionType = "Renewal";
            }
        } else {
            $title = "Pass Application Receipt";
            $transactionType = "New Application";
        }

        if (!$receipt) {
            $error = 'No receipt found for the provided details.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AMTS Pass Receipt</title>
    <link rel="stylesheet" href="passform.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Search form styles */
        .receipt-section {
            padding: 60px 0;
            background-color: var(--white);
        }
        
        .search-form {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--light-gray);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Receipt styles */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0px; color: var(--text); }
        .receipt-container { max-width: 800px; margin: 20px auto; border: 1px solid var(--gray); padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid var(--primary); }
        .header h1 { color: var(--primary); margin-bottom: 5px; }
        .header p { margin-top: 0; color: var(--dark-gray); }
        .receipt-title { text-align: center; font-size: 24px; margin: 20px 0; color: var(--dark); }
        .receipt-details { margin-bottom: 30px; }
        .detail-row { display: flex; margin-bottom: 10px; }
        .detail-label { font-weight: bold; width: 200px; color: var(--dark); }
        .detail-value { flex: 1; }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo img { height: 80px; }
        .status-badge {
            background-color: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        .no-results { 
            text-align: center; 
            padding: 30px; 
            background-color: var(--light-gray); 
            border-radius: 8px;
            margin-top: 20px;
        }
        .download-actions {
            text-align: center;
            margin-top: 30px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header and navigation -->
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
                <a href="login.php" class="user-btn"><i class="fas fa-user"></i> Login</a>
                <a href="register.php" class="user-btn"><i class="fas fa-user-plus"></i> Register</a>
            </div>
        </div>
    </header>

    <nav>
        <div class="container">
            <ul class="main-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.html" "><i class="fas fa-info-circle"></i> About Us</a></li>
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

    <section class="receipt-section">
        <div class="container">
            <?php if ($showSearchForm): ?>
                <div class="section-title">
                    <h2>Find Your Receipt</h2>
                    <p>Enter your pass details to download your receipt</p>
                </div>

                <form class="search-form" method="GET">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="pass_number">Pass Number</label>
                                <input type="text" id="pass_number" name="pass_number" placeholder="Enter pass number" 
                                       value="<?= isset($_GET['pass_number']) ? htmlspecialchars($_GET['pass_number']) : '' ?>" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="mobile">Registered Mobile Number</label>
                                <input type="tel" id="mobile" name="mobile" required placeholder="Enter mobile number"
                                       value="<?= isset($_GET['mobile']) ? htmlspecialchars($_GET['mobile']) : '' ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">Reset</button>
                        <button type="submit" class="btn btn-primary">Search Receipt</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="section-title">
                    <h2>Your Receipt</h2>
                    <p>Transaction details</p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="no-results">
                    <i class="fas fa-receipt" style="font-size: 50px; color: var(--primary); margin-bottom: 20px;"></i>
                    <h3>No Receipt Found</h3>
                    <p><?= htmlspecialchars($error) ?></p>
                    <a href="ereceipt.php" class="btn btn-primary">Try Again</a>
                </div>
            <?php elseif ($receipt): ?>
                <div class="receipt-container" id="receiptToPrint">
                    <div class="logo">
                        <img src="amtslogo.jpeg" alt="AMTS Logo">
                    </div>
                    <div class="header">
                        <h1>Ahmedabad Municipal Transport Service</h1>
                        <p>Pass Management System</p>
                    </div>
                    
                    <div class="receipt-title">
                        <?= $title ?>
                        <div class="status-badge">Approved</div>
                    </div>
                    
                    <div class="receipt-details">
                        <div class="detail-row">
                            <div class="detail-label">Transaction Type:</div>
                            <div class="detail-value"><?= $transactionType ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Transaction ID:</div>
                            <div class="detail-value"><?= htmlspecialchars($receipt['id']) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Date & Time:</div>
                            <div class="detail-value"><?= date('d-m-Y H:i:s', strtotime($receipt['created_at'])) ?></div>
                        </div>
                        
                        <?php if ($isRenewal): ?>
                            <div class="detail-row">
                                <div class="detail-label">Original Pass Number:</div>
                                <div class="detail-value"><?= htmlspecialchars($receipt['original_pass_number']) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="detail-row">
                                <div class="detail-label">Pass Number:</div>
                                <div class="detail-value"><?= htmlspecialchars($receipt['pass_number']) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <div class="detail-label">Pass Type:</div>
                            <div class="detail-value"><?= htmlspecialchars($receipt['pass_type']) ?></div>
                        </div>
                        
                        <?php if ($isRenewal): ?>
                            <div class="detail-row">
                                <div class="detail-label">Renewal Duration:</div>
                                <div class="detail-value"><?= htmlspecialchars($receipt['renewal_duration']) ?> months</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">New Expiry Date:</div>
                                <div class="detail-value"><?= date('d-m-Y', strtotime($receipt['renewal_expiry_date'])) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="detail-row">
                                <div class="detail-label">Pass Duration:</div>
                                <div class="detail-value"><?= htmlspecialchars($receipt['pass_duration']) ?> months</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Expiry Date:</div>
                                <div class="detail-value"><?= date('d-m-Y', strtotime($receipt['pass_expiry_date'])) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <div class="detail-label">Pass Holder Name:</div>
                            <div class="detail-value"><?= htmlspecialchars($receipt['full_name']) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Mobile Number:</div>
                            <div class="detail-value"><?= htmlspecialchars($receipt['mobile']) ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Payment Method:</div>
                            <div class="detail-value"><?= htmlspecialchars(ucfirst($receipt['payment_method'])) ?></div>
                        </div>
                        
                        <?php if ($receipt['payment_method'] === 'card'): ?>
                            <div class="detail-row">
                                <div class="detail-label">Card Last 4 Digits:</div>
                                <div class="detail-value">**** **** **** <?= substr($receipt['card_number'], -4) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Card Name:</div>
                                <div class="detail-value"><?= htmlspecialchars($receipt['card_name']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="footer">
                        <p>This is a computer generated receipt. No signature is required.</p>
                        <p>For any queries, please contact AMTS customer support at help@amts.com or call 1800-123-4567</p>
                        <p>© <?= date('Y') ?> Ahmedabad Municipal Transport Service. All rights reserved.</p>
                    </div>
                </div>
                
                <div class="download-actions">
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                        <button class="btn btn-primary" onclick="downloadPDF()">
                            <i class="fas fa-download"></i> Download as PDF
                        </button>
                        <a href="ereceipt.php" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Search Another
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
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
                        <li><a href="feedback.php">Complaint Resolution</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Us</h3>
                     <p><i class="fas fa-map-marker-alt"></i> AMTS Head Office, Ahmedabad</p>
                    <p><i class="fas fa-phone"></i> 079-25323517/ 25391881-86</p>
                    <p><i class="fas fa-envelope"></i> amtsamc@gmail.com</p>
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

    <!-- html2pdf.js from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

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

    function downloadPDF() {
        const element = document.getElementById('receiptToPrint');
        const opt = {
            margin:       0.5,
            filename:     'AMTS_Receipt_<?= isset($receipt['id']) ? $receipt['id'] : '' ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save();
    }
    </script>
</body>
</html>