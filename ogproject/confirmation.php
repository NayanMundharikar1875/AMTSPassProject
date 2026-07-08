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

// Get application ID from query
$id = $_GET['id'] ?? null;
if (!$id) {
  die("Invalid application ID.");
}

// Fetch the record
$stmt = $conn->prepare("SELECT * FROM pass_applications WHERE id = :id");
$stmt->execute([':id' => $id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
  die("Application not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Confirmation</title>
  <link rel="stylesheet" href="passform.css">
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; background: #f8f8f8; }
    .container { background: #fff; padding: 20px 30px; border-radius: 8px; max-width: 700px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #28a745; }
    .info { margin: 10px 0; }
    .label { font-weight: bold; }
    .value { margin-left: 10px; }
    .section { margin-top: 20px; }
    a.btn { display: inline-block; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    .aadhar-display { 
      background: #f1f1f1; 
      padding: 10px; 
      border-radius: 5px; 
      font-family: monospace;
      letter-spacing: 2px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🎉 Application Submitted Successfully!</h2>
    <p>Thank you, your AMTS bus pass application has been recorded. Here are your details:</p>

    <div class="section">
      <div class="info"><span class="label">Application ID:</span><span class="value"><?= htmlspecialchars($application['id']) ?></span></div>
      <div class="info"><span class="label">Pass Type:</span><span class="value"><?= htmlspecialchars($application['pass_type']) ?></span></div>
      <div class="info"><span class="label">Full Name:</span><span class="value"><?= htmlspecialchars($application['full_name']) ?></span></div>
      <div class="info"><span class="label">Mobile:</span><span class="value"><?= htmlspecialchars($application['mobile']) ?></span></div>
      <div class="info"><span class="label">Email:</span><span class="value"><?= htmlspecialchars($application['email']) ?></span></div>
      <div class="info"><span class="label">Address:</span><span class="value"><?= nl2br(htmlspecialchars($application['address'])) ?></span></div>
      <div class="info"><span class="label">Pass Number:</span><span class="value"><?= htmlspecialchars($application['pass_number']) ?></span></div>
      <div class="info">
        <span class="label">Aadhaar Number:</span>
        <span class="value aadhar-display">
          <?= 
            // Display Aadhaar number with formatting (XXXX-XXXX-XXXX)
            chunk_split(htmlspecialchars($application['aadhar_number']), 4, '-')
          ?>
        </span>
      </div>
    </div>

    <div class="section">
      <h3>Pass Duration</h3>
      <div class="info"><span class="label">Created On:</span><span class="value"><?= date('d-m-Y', strtotime($application['created_at'])) ?></span></div>
      <div class="info"><span class="label">Expires On:</span><span class="value"><?= date('d-m-Y', strtotime($application['pass_expiry_date'] . '-01')) ?></span></div>
    </div>

    <div class="section">
      <h3>Payment Details</h3>
      <div class="info"><span class="label">Method:</span><span class="value"><?= htmlspecialchars($application['payment_method']) ?></span></div>
      <?php if ($application['payment_method'] === 'card'): ?>
        <div class="info"><span class="label">Card Number:</span><span class="value">**** **** **** <?= substr($application['card_number'], -4) ?></span></div>
        <div class="info"><span class="label">Card Name:</span><span class="value"><?= htmlspecialchars($application['card_name']) ?></span></div>
        <div class="info"><span class="label">Card Expiry:</span><span class="value"><?= htmlspecialchars($application['expiry_date']) ?></span></div>
      <?php endif; ?>
    </div>

    <div class="section">
      <a href="index.php" class="btn">Return to Home</a>
      <a href="javascript:window.print()" class="btn" style="margin-left: 10px;"><i class="fas fa-print"></i> Print Confirmation</a>
    </div>
  </div>
</body>
</html>