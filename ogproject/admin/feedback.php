<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Check if feedback table exists with required columns
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'feedback'");
    if ($stmt->rowCount() == 0) {
        // Create feedback table if it doesn't exist
        $pdo->exec("CREATE TABLE feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            feedback_type ENUM('general', 'complaint', 'suggestion') NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            route VARCHAR(50),
            rating TINYINT,
            message TEXT NOT NULL,
            anonymous TINYINT(1) DEFAULT 0,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
        )");
    } else {
        // Check if status column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM feedback LIKE 'status'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE feedback ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        }
    }
} catch (PDOException $e) {
    error_log("Database error checking tables: " . $e->getMessage());
    $_SESSION['error'] = "A database configuration error occurred.";
}

// Initialize variables
$action = '';
$feedbackId = 0;
$feedback = null;
$feedbackList = [];
$status = '';
$search = '';

// Validate and sanitize action and ID parameters
if (isset($_GET['action'])) {
    $allowedActions = ['approve', 'reject', 'delete', 'view'];
    $action = in_array($_GET['action'], $allowedActions) ? $_GET['action'] : '';
    
    if ($action && isset($_GET['id'])) {
        $feedbackId = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$feedbackId) {
            $_SESSION['error'] = "Invalid feedback ID";
            redirect('feedback.php');
        }
    }
}

// Handle feedback actions
if ($action && $feedbackId) {
    try {
        switch ($action) {
            case 'approve':
                if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                    $_SESSION['error'] = "CSRF token validation failed. Please try again.";
                    redirect('feedback.php');
                }
                
                $stmt = $pdo->prepare("UPDATE feedback 
                                      SET status = 'approved', 
                                          updated_at = NOW()
                                      WHERE id = ?");
                $stmt->execute([$feedbackId]);
                
                $_SESSION['success'] = "Feedback approved successfully";
                logActivity("Approved feedback ID: $feedbackId");
                redirect('feedback.php');
                break;
                
            case 'reject':
                if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                    $_SESSION['error'] = "CSRF token validation failed. Please try again.";
                    redirect('feedback.php');
                }
                
                $stmt = $pdo->prepare("UPDATE feedback 
                                      SET status = 'rejected', 
                                          updated_at = NOW()
                                      WHERE id = ?");
                $stmt->execute([$feedbackId]);
                $_SESSION['success'] = "Feedback rejected";
                logActivity("Rejected feedback ID: $feedbackId");
                redirect('feedback.php');
                break;
                
            case 'delete':
                if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
                    $_SESSION['error'] = "CSRF token validation failed. Please try again.";
                    redirect('feedback.php');
                }
                
                $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
                $stmt->execute([$feedbackId]);
                $_SESSION['success'] = "Feedback deleted successfully";
                logActivity("Deleted feedback ID: $feedbackId");
                redirect('feedback.php');
                break;
                
            case 'view':
                $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
                $stmt->execute([$feedbackId]);
                $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$feedback) {
                    $_SESSION['error'] = "Feedback not found";
                    redirect('feedback.php');
                }
                break;
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "A database error occurred. Please try again.";
        redirect('feedback.php');
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        redirect('feedback.php');
    }
}

// Validate and set filters
$allowed_statuses = ['', 'pending', 'approved', 'rejected'];
if (isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses)) {
    $status = $_GET['status'];
}

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Build query with filters
$where = "WHERE 1=1";
$params = [];

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR message LIKE ? OR route LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// Pagination setup
$perPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

// Get total count for pagination
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM feedback $where");
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);
    
    // Ensure current page is within valid range
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    
    // Calculate offset
    $offset = ($currentPage - 1) * $perPage;
    
    // Get feedback with pagination
    $sql = "SELECT * FROM feedback $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    
    // Add limit and offset to params
    $limitParam = $perPage;
    $offsetParam = $offset;
    
    if ($params) {
        $stmt->execute(array_merge($params, [$limitParam, $offsetParam]));
    } else {
        $stmt->execute([$limitParam, $offsetParam]);
    }
    
    $feedbackList = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "A database error occurred while loading feedback.";
    $feedbackList = [];
    $totalPages = 1;
    $currentPage = 1;
}

require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-body.bg-light {
            background-color: #f8f9fa !important;
        }
        .rating-display .star {
            color: #ffc107;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h2><i class="fas fa-comments"></i> User Feedback</h2>
            
            <div class="action-buttons">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter"></i> Filters
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($feedback)): ?>
            <!-- Feedback Detail View -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Feedback from <?php echo htmlspecialchars($feedback['anonymous'] ? 'Anonymous' : $feedback['name']); ?></h3>
                    <span class="badge bg-<?php 
                        echo $feedback['status'] === 'approved' ? 'success' : 
                             ($feedback['status'] === 'rejected' ? 'danger' : 'warning');
                    ?>">
                        <?php echo ucfirst($feedback['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Feedback Type:</label>
                                <p class="form-control-static">
                                    <?php 
                                        $types = [
                                            'general' => 'General Feedback',
                                            'complaint' => 'Complaint',
                                            'suggestion' => 'Suggestion'
                                        ];
                                        echo htmlspecialchars($types[$feedback['feedback_type']]);
                                    ?>
                                </p>
                            </div>
                            <?php if (!$feedback['anonymous']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Name:</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($feedback['name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email:</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($feedback['email']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone:</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($feedback['phone']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Submitted On:</label>
                                <p class="form-control-static"><?php echo formatDate($feedback['created_at']); ?></p>
                            </div>
                            <?php if ($feedback['route']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Bus Route:</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($feedback['route']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($feedback['rating']): ?>
                                <div class="mb-3">
                                    <label class="form-label">Rating:</label>
                                    <div class="rating-display">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $feedback['rating'] 
                                                ? '<i class="fas fa-star star"></i>' 
                                                : '<i class="far fa-star star"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Status:</label>
                                <p class="form-control-static">
                                    <span class="badge bg-<?php 
                                        echo $feedback['status'] === 'approved' ? 'success' : 
                                             ($feedback['status'] === 'rejected' ? 'danger' : 'warning');
                                    ?>">
                                        <?php echo ucfirst($feedback['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message:</label>
                        <div class="card card-body bg-light">
                            <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="feedback.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <div>
                        <?php if ($feedback['status'] !== 'approved'): ?>
                            <form action="feedback.php" method="post" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?php echo $feedback['id']; ?>">
                                <button type="submit" class="btn btn-success" 
                                    onclick="return confirm('Approve this feedback?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($feedback['status'] !== 'rejected'): ?>
                            <form action="feedback.php" method="post" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?php echo $feedback['id']; ?>">
                                <button type="submit" class="btn btn-warning ms-2" 
                                    onclick="return confirm('Reject this feedback?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form action="feedback.php" method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $feedback['id']; ?>">
                            <button type="submit" class="btn btn-danger ms-2" 
                                onclick="return confirm('Are you sure you want to delete this feedback?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Feedback List -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (!empty($status) || !empty($search)): ?>
                        <div class="alert alert-info mb-3">
                            Showing filtered results: 
                            <?php if (!empty($status)) echo "Status: " . htmlspecialchars($status) . " "; ?>
                            <?php if (!empty($search)) echo "Search: " . htmlspecialchars($search); ?>
                            <a href="feedback.php" class="float-end">Clear filters</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>Submitted On</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedbackList)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No feedback found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($feedbackList as $fb): ?>
                                        <tr>
                                            <td>#<?php echo $fb['id']; ?></td>
                                            <td>
                                                <?php 
                                                    $types = [
                                                        'general' => 'General',
                                                        'complaint' => 'Complaint',
                                                        'suggestion' => 'Suggestion'
                                                    ];
                                                    echo htmlspecialchars($types[$fb['feedback_type']]);
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($fb['anonymous'] ? 'Anonymous' : $fb['name']); ?>
                                                <?php if ($fb['route']): ?>
                                                    <br><small>Route: <?php echo htmlspecialchars($fb['route']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($fb['created_at']); ?></td>
                                            <td>
                                                <?php if ($fb['rating']): ?>
                                                    <div class="rating-display">
                                                        <?php 
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $fb['rating'] 
                                                                ? '<i class="fas fa-star star"></i>' 
                                                                : '<i class="far fa-star star"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $fb['status'] === 'approved' ? 'success' : 
                                                         ($fb['status'] === 'rejected' ? 'danger' : 'warning');
                                                ?>">
                                                    <?php echo ucfirst($fb['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="feedback.php?action=view&id=<?php echo $fb['id']; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($fb['status'] !== 'approved'): ?>
                                                    <form action="feedback.php" method="post" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="id" value="<?php echo $fb['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" 
                                                            title="Approve"
                                                            onclick="return confirm('Approve this feedback?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($fb['status'] !== 'rejected'): ?>
                                                    <form action="feedback.php" method="post" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="id" value="<?php echo $fb['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" 
                                                            title="Reject"
                                                            onclick="return confirm('Reject this feedback?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form action="feedback.php" method="post" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $fb['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                        title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this feedback?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage-1; ?>&status=<?php echo htmlspecialchars($status); ?>&search=<?php echo urlencode($search); ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($status); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage+1; ?>&status=<?php echo htmlspecialchars($status); ?>&search=<?php echo urlencode($search); ?>">
                                            Next
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="feedback.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select name="status" id="statusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="searchFilter" class="form-label">Search</label>
                        <input type="text" name="search" id="searchFilter" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, route or message">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>