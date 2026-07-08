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
  die("Connection failed: " . $e->getMessage());
}

// Get ID and type from query
$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'application'; // 'application' or 'renewal'

if (!$id) {
  die("Invalid ID.");
}

if ($type === 'renewal') {
  // Fetch renewal record
  $stmt = $conn->prepare("SELECT r.*, p.pass_number as original_pass_number 
                         FROM pass_renewals r
                         JOIN pass_applications p ON r.original_pass_id = p.id
                         WHERE r.id = :id");
  $stmt->execute([':id' => $id]);
  $record = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$record) {
    die("Renewal record not found.");
  }
  
  $title = "Pass Renewal Receipt";
  $transactionType = "Renewal";
} else {
  // Fetch application record
  $stmt = $conn->prepare("SELECT * FROM pass_applications WHERE id = :id");
  $stmt->execute([':id' => $id]);
  $record = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$record) {
    die("Application record not found.");
  }
  
  $title = "Pass Application Receipt";
  $transactionType = "New Application";
}

// Generate receipt HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $title; ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
    .receipt-container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .header { text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #e63946; }
    .header h1 { color: #e63946; margin-bottom: 5px; }
    .header p { margin-top: 0; color: #666; }
    .receipt-title { text-align: center; font-size: 24px; margin: 20px 0; color: #1d3557; }
    .receipt-details { margin-bottom: 30px; }
    .detail-row { display: flex; margin-bottom: 10px; }
    .detail-label { font-weight: bold; width: 200px; }
    .detail-value { flex: 1; }
    .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
    .logo { text-align: center; margin-bottom: 20px; }
    .logo img { height: 80px; }
    .status-badge {
      background-color: #28a745;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-weight: bold;
      display: inline-block;
    }
  </style>
</head>
<body>
  <div class="receipt-container">
    <div class="logo">
      <img src="amtslogo.jpeg" alt="AMTS Logo">
    </div>
    <div class="header">
      <h1>Ahmedabad Municipal Transport Service</h1>
      <p>Pass Management System</p>
    </div>
    
    <div class="receipt-title">
      <?php echo $title; ?>
      <div class="status-badge">Approved</div>
    </div>
    
    <div class="receipt-details">
      <div class="detail-row">
        <div class="detail-label">Transaction Type:</div>
        <div class="detail-value"><?php echo $transactionType; ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Transaction ID:</div>
        <div class="detail-value"><?php echo htmlspecialchars($record['id']); ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Date & Time:</div>
        <div class="detail-value"><?php echo date('d-m-Y H:i:s', strtotime($record['created_at'])); ?></div>
      </div>
      
      <?php if ($type === 'renewal'): ?>
        <div class="detail-row">
          <div class="detail-label">Original Pass Number:</div>
          <div class="detail-value"><?php echo htmlspecialchars($record['original_pass_number']); ?></div>
        </div>
      <?php else: ?>
        <div class="detail-row">
          <div class="detail-label">Pass Number:</div>
          <div class="detail-value"><?php echo htmlspecialchars($record['pass_number']); ?></div>
        </div>
      <?php endif; ?>
      
      <div class="detail-row">
        <div class="detail-label">Pass Type:</div>
        <div class="detail-value"><?php echo htmlspecialchars($record['pass_type']); ?></div>
      </div>
      
      <?php if ($type === 'renewal'): ?>
        <div class="detail-row">
          <div class="detail-label">Renewal Duration:</div>
          <div class="detail-value"><?php echo htmlspecialchars($record['renewal_duration']); ?> months</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">New Expiry Date:</div>
          <div class="detail-value"><?php echo date('d-m-Y', strtotime($record['renewal_expiry_date'])); ?></div>
        </div>
      <?php else: ?>
        <div class="detail-row">
          <div class="detail-label">Pass Duration:</div>
          <div class="detail-value"><?php echo htmlspecialchars($record['pass_duration']); ?> months</div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Expiry Date:</div>
          <div class="detail-value"><?php echo date('d-m-Y', strtotime($record['pass_expiry_date'])); ?></div>
        </div>
      <?php endif; ?>
      
      <div class="detail-row">
        <div class="detail-label">Pass Holder Name:</div>
        <div class="detail-value"><?php echo htmlspecialchars($record['full_name']); ?></div>
      </div>
      <div class="detail-row">
        <div class="detail-label">Mobile Number:</div>
        <div class="detail-value"><?php echo htmlspecialchars($record['mobile']); ?></div>
      </div>
      
      <div class="detail-row">
        <div class="detail-label">Payment Method:</div>
        <div class="detail-value"><?php echo htmlspecialchars(ucfirst($record['payment_method'])); ?></div>
      </div>
      
      <?php if ($record['payment_method'] === 'card'): ?>
        <div class="detail-row">
          <div class="detail-label">Card Last 4 Digits:</div>
          <div class="detail-value">**** **** **** <?php echo substr($record['card_number'], -4); ?></div>
        </div>
        <div class="detail-row">
          <div class="detail-label">Card Name:</div>
          <div class="detail-value"><?php echo htmlspecialchars($record['card_name']); ?></div>
        </div>
      <?php endif; ?>
    </div>
    
    <div class="footer">
      <p>This is a computer generated receipt. No signature is required.</p>
      <p>For any queries, please contact AMTS customer support at help@amts.com or call 1800-123-4567</p>
      <p>© <?php echo date('Y'); ?> Ahmedabad Municipal Transport Service. All rights reserved.</p>
    </div>
  </div>
  
  <script>
    // Automatically trigger print when page loads
    window.onload = function() {
      window.print();
    };
  </script>
</body>
</html>