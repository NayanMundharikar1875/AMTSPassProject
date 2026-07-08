<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Handle renewal actions
$action = $_GET['action'] ?? '';
$renewalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $renewalId > 0) {
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("SELECT original_pass_id, renewal_expiry_date FROM pass_renewals WHERE id = ?");
                $stmt->execute([$renewalId]);
                $renewal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($renewal) {
                    $expiryDate = date('Y-m', strtotime($renewal['renewal_expiry_date']));
                    
                    $stmt = $pdo->prepare("UPDATE pass_applications SET pass_expiry_date = ? WHERE id = ?");
                    $stmt->execute([$expiryDate, $renewal['original_pass_id']]);
                    
                    $stmt = $pdo->prepare("UPDATE pass_renewals SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$renewalId]);
                    
                    $_SESSION['message'] = "Renewal #{$renewalId} approved successfully";
                }
                redirect('renewals.php');
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE pass_renewals SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$renewalId]);
                $_SESSION['message'] = "Renewal #{$renewalId} rejected";
                redirect('renewals.php');
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM pass_renewals WHERE id = ?");
                $stmt->execute([$renewalId]);
                $_SESSION['message'] = "Renewal deleted successfully";
                redirect('renewals.php');
                break;
                
            case 'view':
                $stmt = $pdo->prepare("SELECT r.*, p.full_name, p.pass_number as original_pass_number 
                                     FROM pass_renewals r
                                     JOIN pass_applications p ON r.original_pass_id = p.id
                                     WHERE r.id = ?");
                $stmt->execute([$renewalId]);
                $renewal = $stmt->fetch(PDO::FETCH_ASSOC);
                break;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        redirect('renewals.php');
    }
}

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$status = $_GET['status'] ?? '';
$search = isset($_GET['search']) ? "%".trim($_GET['search'])."%" : '%';

$where = "WHERE 1=1";
$params = [];

if ($status) {
    $where .= " AND r.status = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (p.full_name LIKE ? OR p.pass_number LIKE ?)";
    $params[] = $search;
    $params[] = $search;
}

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pass_renewals r JOIN pass_applications p ON r.original_pass_id = p.id $where");
$countStmt->execute($params);
$totalRenewals = $countStmt->fetchColumn();
$totalPages = ceil($totalRenewals / $perPage);

// Get renewals
$stmt = $pdo->prepare("SELECT r.*, p.full_name, p.pass_number as original_pass_number 
                      FROM pass_renewals r
                      JOIN pass_applications p ON r.original_pass_id = p.id
                      $where ORDER BY r.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$renewals = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<head>
    <link rel="stylesheet" href="style.css">
    <!-- Add Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<div class="dashboard-content">
    <div class="dashboard-header">
        <h2><i class="fas fa-sync-alt"></i> Pass Renewals</h2>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo strpos($_SESSION['message'], 'error') !== false ? 'danger' : 'success'; ?>">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($renewal)): ?>
        <!-- Single renewal view -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Renewal #<?php echo $renewal['id']; ?></h3>
                <span class="badge bg-<?php echo $renewal['status'] === 'approved' ? 'success' : ($renewal['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                    <?php echo ucfirst($renewal['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <!-- Display renewal details here -->
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Pass Number:</strong> <?php echo htmlspecialchars($renewal['original_pass_number']); ?></p>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($renewal['full_name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Duration:</strong> <?php echo $renewal['renewal_duration']; ?> months</p>
                        <p><strong>Requested On:</strong> <?php echo formatDate($renewal['created_at']); ?></p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="renewals.php" class="btn btn-secondary">Back</a>
                <a href="renewals.php?action=approve&id=<?php echo $renewal['id']; ?>" class="btn btn-success">Approve</a>
                <a href="renewals.php?action=reject&id=<?php echo $renewal['id']; ?>" class="btn btn-danger">Reject</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Renewals list -->
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pass Number</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renewals as $r): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['original_pass_number']); ?></td>
                                <td><?php echo htmlspecialchars($r['full_name']); ?></td>
                                <td>
                                <span class="badge bg-<?php echo ($r['status'] ?? '') === 'approved' ? 'success' : (($r['status'] ?? '') === 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($r['status'] ?? 'pending'); ?>
                                </span>
                                </td>
                                <td>
                                    <a href="renewals.php?action=view&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="renewals.php?action=approve&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>