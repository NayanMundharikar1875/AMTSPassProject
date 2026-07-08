<?php
// admin.php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Initialize
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

// Handle actions
if ($action && $id > 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
        $message = "CSRF token validation failed";
    } else {
        try {
            switch ($action) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['message'] = "User deleted successfully.";
                    redirect('admin.php');
                    break;
                    
                case 'reset_password':
                    $newPassword = generateRandomString(10);
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $id]);
                    
                    // Store the message in session and redirect
                    $_SESSION['message'] = "Password reset successfully. New password: " . htmlspecialchars($newPassword);
                    redirect('reset_password.php');
                    break;
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}



// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search
$search = isset($_GET['search']) ? "%" . trim($_GET['search']) . "%" : '%';
$where = "WHERE name LIKE ? OR email LIKE ?";
$params = [$search, $search];

// Get Users
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalUsers / $perPage));

$stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$users = $stmt->fetchAll();

// Dashboard Stats
$newPassesStmt = $pdo->query("SELECT COUNT(*) FROM pass_applications");
$newPasses = $newPassesStmt->fetchColumn();

$renewedPassesStmt = $pdo->query("SELECT COUNT(*) FROM pass_renewals");
$renewedPasses = $renewedPassesStmt->fetchColumn();

$feedbacksStmt = $pdo->query("SELECT COUNT(*) FROM feedback");
$feedbacks = $feedbacksStmt->fetchColumn();

require_once 'includes/header.php';
?>

<style>
    .dashboard-header {
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        text-align: center;
    }
    .stat-card h3 {
        margin-bottom: 0.5rem;
        font-size: 1.8rem;
        color: #2c3e50;
    }
    .stat-card p {
        margin: 0;
        font-size: 0.9rem;
        color: #7f8c8d;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .search-form {
        min-width: 250px;
    }
</style>

<div class="container py-4">
    <div class="dashboard-header">
        <h1 class="h3 mb-0"><i class="fas fa-user-cog"></i> Admin Dashboard</h1>
        <form method="GET" class="search-form d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $newPasses; ?></h3>
            <p>New Passes</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $renewedPasses; ?></h3>
            <p>Renewed Passes</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $feedbacks; ?></h3>
            <p>Feedbacks</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">User Management</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="text-center">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <a href="editprofile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="admin.php?action=reset_password&id=<?php echo $user['id']; ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button class="btn btn-sm btn-warning" onclick="return confirm('Reset password?')"><i class="fas fa-key"></i></button>
                                </form>
                                <form method="POST" action="admin.php?action=delete&id=<?php echo $user['id']; ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>