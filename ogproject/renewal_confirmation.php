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

// Get renewal ID from query
$id = $_GET['id'] ?? null;
if (!$id) {
  die("Invalid renewal ID.");
}

// Fetch the renewal record
$stmt = $conn->prepare("SELECT r.*, p.pass_number as original_pass_number 
                       FROM pass_renewals r
                       JOIN pass_applications p ON r.original_pass_id = p.id
                       WHERE r.id = :id");
$stmt->execute([':id' => $id]);
$renewal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$renewal) {
  die("Renewal record not found.");
}

// Also get the original pass details
$stmt = $conn->prepare("SELECT * FROM pass_applications WHERE id = :id");
$stmt->execute([':id' => $renewal['original_pass_id']]);
$originalPass = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Renewal Confirmation</title>
  <link rel="stylesheet" href="passform.css">
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; background: #f8f8f8; }
    .container { background: #fff; padding: 20px 30px; border-radius: 8px; max-width: 700px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h2 { color: #28a745; }
    .info { margin: 10px 0; }
    .label { font-weight: bold; }
    .value { margin-left: 10px; }
    .section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
    a.btn { display: inline-block; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    .pass-status {
      padding: 10px;
      background: #d4edda;
      color: #155724;
      border-radius: 4px;
      margin-bottom: 20px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>🎉 Pass Renewed Successfully!</h2>
    <div class="pass-status">
      Your AMTS bus pass has been successfully renewed. Here are your renewal details:
    </div>

    <div class="section">
      <h3>Renewal Information</h3>
      <div class="info"><span class="label">Renewal ID:</span><span class="value"><?= htmlspecialchars($renewal['id']) ?></span></div>
      <div class="info"><span class="label">Original Pass Number:</span><span class="value"><?= htmlspecialchars($renewal['original_pass_number']) ?></span></div>
      <div class="info"><span class="label">Pass Type:</span><span class="value"><?= htmlspecialchars($renewal['pass_type']) ?></span></div>
      <div class="info"><span class="label">Renewal Duration:</span><span class="value"><?= htmlspecialchars($renewal['renewal_duration']) ?> months</span></div>
      <div class="info"><span class="label">New Expiry Date:</span><span class="value"><?= date('d-m-Y', strtotime($renewal['renewal_expiry_date'])) ?></span></div>
      <div class="info"><span class="label">Renewed On:</span><span class="value"><?= date('d-m-Y H:i:s', strtotime($renewal['created_at'])) ?></span></div>
    </div>

    <div class="section">
      <h3>Pass Holder Details</h3>
      <div class="info"><span class="label">Full Name:</span><span class="value"><?= htmlspecialchars($renewal['full_name']) ?></span></div>
      <div class="info"><span class="label">Mobile:</span><span class="value"><?= htmlspecialchars($renewal['mobile']) ?></span></div>
      <div class="info"><span class="label">Email:</span><span class="value"><?= htmlspecialchars($renewal['email']) ?></span></div>
      <div class="info"><span class="label">Address:</span><span class="value"><?= nl2br(htmlspecialchars($renewal['address'])) ?></span></div>
    </div>

    <div class="section">
      <h3>Payment Details</h3>
      <div class="info"><span class="label">Method:</span><span class="value"><?= htmlspecialchars($renewal['payment_method']) ?></span></div>
      <?php if ($renewal['payment_method'] === 'card'): ?>
        <div class="info"><span class="label">Card Number:</span><span class="value">**** **** **** <?= substr($renewal['card_number'], -4) ?></span></div>
        <div class="info"><span class="label">Card Name:</span><span class="value"><?= htmlspecialchars($renewal['card_name']) ?></span></div>
        <div class="info"><span class="label">Card Expiry:</span><span class="value"><?= htmlspecialchars($renewal['card_expiry']) ?></span></div>
      <?php endif; ?>
    </div>

    <div class="section">
      <h3>Original Pass Information</h3>
      <div class="info"><span class="label">Original Issue Date:</span><span class="value"><?= date('d-m-Y', strtotime($originalPass['created_at'])) ?></span></div>
      <div class="info"><span class="label">Original Expiry Date:</span><span class="value"><?= date('d-m-Y', strtotime($originalPass['pass_expiry_date'])) ?></span></div>
    </div>

    <div class="section">
      <a href="index.php" class="btn">Return to Home</a>
      <a href="ereceipt.php?id=<?= $renewal['id'] ?>&type=renewal" class="btn">Download Receipt</a>
    </div>
  </div>
</body>
</html>