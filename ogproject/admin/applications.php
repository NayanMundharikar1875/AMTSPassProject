<?php
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/functions.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify CSRF token for state-changing actions
function verifyCsrf() {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['message'] = "Invalid CSRF token";
        redirect('applications.php');
    }
}

// Check if status column exists, if not add it
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM pass_applications LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE pass_applications ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
    }
} catch (PDOException $e) {
    error_log("Error checking status column: " . $e->getMessage());
}

// Handle application actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $appId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($appId > 0) {
        try {
            switch ($action) {
                case 'approve':
                    verifyCsrf();
                    $passNumber = 'PASS' . str_pad($appId, 6, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("SELECT pass_duration FROM pass_applications WHERE id = ?");
                    $stmt->execute([$appId]);
                    $duration = $stmt->fetchColumn();
                    $expiryDate = date('Y-m', strtotime("+{$duration} months"));
                    
                    $stmt = $pdo->prepare("UPDATE pass_applications SET status = 'approved', pass_number = ?, pass_expiry_date = ? WHERE id = ?");
                    $stmt->execute([$passNumber, $expiryDate, $appId]);
                    
                    $_SESSION['message'] = "Application #{$appId} approved successfully";
                    redirect('applications.php');
                    break;
                    
                case 'reject':
                    verifyCsrf();
                    $stmt = $pdo->prepare("UPDATE pass_applications SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$appId]);
                    $_SESSION['message'] = "Application #{$appId} rejected";
                    redirect('applications.php');
                    break;
                    
                case 'view':
                    $stmt = $pdo->prepare("SELECT * FROM pass_applications WHERE id = ?");
                    $stmt->execute([$appId]);
                    $application = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$application) {
                        $_SESSION['message'] = "Application not found";
                        redirect('applications.php');
                    }
                    break;
                    
                default:
                    $_SESSION['message'] = "Invalid action";
                    redirect('applications.php');
            }
        } catch (Exception $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
            redirect('applications.php');
        }
    }
}

// Get filter parameters with validation
$allowed_statuses = ['', 'pending', 'approved', 'rejected'];
$status = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses) ? $_GET['status'] : '';

$allowed_pass_types = ['', 'student', 'senior', 'general'];
$pass_type = isset($_GET['pass_type']) && in_array($_GET['pass_type'], $allowed_pass_types) ? $_GET['pass_type'] : '';

// Build query
$sql = "SELECT * FROM pass_applications WHERE 1=1";
$params = [];

if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if ($pass_type) {
    $sql .= " AND pass_type = ?";
    $params[] = $pass_type;
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = "Error loading applications";
    $applications = [];
}

include 'includes/header.php';
?>

<head>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-content {
            padding: 20px;
            margin: 0 auto;
            max-width: 1200px;
        }
        .dashboard-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            font-weight: 600;
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 0.85em;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .form-control-static {
            padding-top: 0;
            padding-bottom: 0;
        }
    </style>
</head>

<div class="dashboard-content">
    <div class="dashboard-header">
        <h2><i class="fas fa-file-alt"></i> Pass Applications</h2>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['message'], 'error') !== false ? 'danger' : 'success' ?>">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($application)): ?>
        <!-- Application Detail View -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Application #<?= $application['id'] ?></h3>
                <span class="badge bg-<?= 
                    isset($application['status']) ? 
                    ($application['status'] === 'approved' ? 'success' : 
                     ($application['status'] === 'rejected' ? 'danger' : 'warning')) : 
                    'warning';
                ?>">
                    <?= isset($application['status']) ? ucfirst($application['status']) : 'Pending' ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Full Name:</label>
                            <p class="form-control-static"><?= htmlspecialchars($application['full_name']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mobile:</label>
                            <p class="form-control-static"><?= htmlspecialchars($application['mobile']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email:</label>
                            <p class="form-control-static"><?= isset($application['email']) ? htmlspecialchars($application['email']) : 'N/A' ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Pass Type:</label>
                            <p class="form-control-static"><?= ucfirst($application['pass_type']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration:</label>
                            <p class="form-control-static"><?= $application['pass_duration'] ?> months</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Applied On:</label>
                            <p class="form-control-static"><?= formatDate($application['created_at']) ?></p>
                        </div>
                        <?php if (isset($application['status']) && $application['status'] === 'approved'): ?>
                            <div class="mb-3">
                                <label class="form-label">Pass Number:</label>
                                <p class="form-control-static"><?= htmlspecialchars($application['pass_number']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Expiry Date:</label>
                                <p class="form-control-static"><?= date('M Y', strtotime($application['pass_expiry_date'] . '-01')) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address:</label>
                    <div class="card card-body bg-light">
                        <?= nl2br(htmlspecialchars($application['address'])) ?>
                    </div>
                </div>
                
                <div class="payment-details mt-4">
                    <h4>Payment Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Payment Method:</label>
                                <p class="form-control-static"><?= ucfirst($application['payment_method']) ?></p>
                            </div>
                        </div>
                        <?php if (isset($application['payment_method']) && $application['payment_method'] === 'card'): ?>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Card Name:</label>
                                    <p class="form-control-static"><?= htmlspecialchars($application['card_name']) ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Card Number:</label>
                                    <p class="form-control-static">**** **** **** <?= isset($application['card_number']) ? substr($application['card_number'], -4) : '' ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="applications.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <div>
                    <?php if (!isset($application['status']) || $application['status'] !== 'approved'): ?>
                        <a href="applications.php?action=approve&id=<?= $application['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                           class="btn btn-success" 
                           onclick="return confirm('Approve this application?')">
                            <i class="fas fa-check"></i> Approve
                        </a>
                    <?php endif; ?>
                    <?php if (!isset($application['status']) || $application['status'] !== 'rejected'): ?>
                        <a href="applications.php?action=reject&id=<?= $application['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                           class="btn btn-danger ms-2" 
                           onclick="return confirm('Reject this application?')">
                            <i class="fas fa-times"></i> Reject
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Applications List -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Pass Type</th>
                                <th>Mobile</th>
                                <th>Applied On</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No applications found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>#<?= $app['id'] ?></td>
                                        <td><?= htmlspecialchars($app['full_name']) ?></td>
                                        <td><?= ucfirst($app['pass_type']) ?></td>
                                        <td><?= htmlspecialchars($app['mobile']) ?></td>
                                        <td><?= formatDate($app['created_at']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                isset($app['status']) ? 
                                                ($app['status'] === 'approved' ? 'success' : 
                                                 ($app['status'] === 'rejected' ? 'danger' : 'warning')) : 
                                                'warning';
                                            ?>">
                                                <?= isset($app['status']) ? ucfirst($app['status']) : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="applications.php?action=view&id=<?= $app['id'] ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!isset($app['status']) || $app['status'] !== 'approved'): ?>
                                                <a href="applications.php?action=approve&id=<?= $app['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   title="Approve"
                                                   onclick="return confirm('Approve this application?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!isset($app['status']) || $app['status'] !== 'rejected'): ?>
                                                <a href="applications.php?action=reject&id=<?= $app['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Reject"
                                                   onclick="return confirm('Reject this application?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>