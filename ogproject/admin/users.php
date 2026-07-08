<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    $_SESSION['message'] = "CSRF token validation failed";
    redirect('users.php');
}

// Handle user actions
$action = $_GET['action'] ?? '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $userId > 0) {
    try {
        switch ($action) {
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $_SESSION['message'] = "User deleted successfully";
                break;

            case 'edit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $name = sanitizeInput($_POST['name']);
                    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

                    if (!$email) throw new Exception("Invalid email format");

                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $userId]);
                    $_SESSION['message'] = "User updated successfully";
                }
                break;
        }
        redirect('users.php');
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        redirect('users.php');
    }
}

// Handle add new user
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = sanitizeInput($_POST['name']);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];

        if (!$email) throw new Exception("Invalid email format");
        if (strlen($password) < 8) throw new Exception("Password must be at least 8 characters");

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) throw new Exception("Email already exists");

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $passwordHash]);

        $_SESSION['message'] = "User added successfully";
        redirect('users.php');
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        redirect('users.php');
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? "%".trim($_GET['search'])."%" : '%';
$where = "WHERE name LIKE ? OR email LIKE ?";
$params = [$search, $search];

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <!-- Bootstrap CSS + JS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</head>

<body>
<div class="dashboard-content">
    <div class="dashboard-header d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-users"></i> User Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>

    <?php displayMessage(); ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3 row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info edit-user" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>">Previous</a>
                        </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($_GET['search'] ?? ''); ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="users.php?action=add" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      <div class="modal-body">
        <div class="mb-3">
          <label for="name" class="form-label">Full Name</label>
          <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" required minlength="8">
          <div class="form-text">Minimum 8 characters</div>
          <div class="password-strength mt-2"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add User</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="" id="editUserForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      <div class="modal-body">
        <div class="mb-3">
          <label for="edit_name" class="form-label">Full Name</label>
          <input type="text" id="edit_name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="edit_email" class="form-label">Email Address</label>
          <input type="email" id="edit_email" name="email" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit modal load
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('editUserForm').action = `users.php?action=edit&id=${this.dataset.id}`;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_email').value = this.dataset.email;
        });
    });

    // Password strength
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.querySelector('.password-strength');

    if (passwordInput && strengthIndicator) {
        passwordInput.addEventListener('input', function () {
            const strength = calculatePasswordStrength(this.value);
            let text = '', css = '';

            if (strength <= 1) {
                text = 'Weak'; css = 'text-danger';
            } else if (strength == 2) {
                text = 'Moderate'; css = 'text-warning';
            } else {
                text = 'Strong'; css = 'text-success';
            }

            strengthIndicator.innerHTML = `Strength: <span class="${css}">${text}</span>`;
        });
    }
});

function calculatePasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    return score;
}
</script>
</body>
</html>
