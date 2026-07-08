<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';


// Generate CSRF token
$csrfToken = generateCsrfToken();

// Get user ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    $_SESSION['message'] = "Invalid user ID";
    redirect('admin.php');
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['message'] = "User not found";
        redirect('admin.php');
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    redirect('admin.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "CSRF token validation failed";
    } else {
        try {
            // Determine which action to take
            if (isset($_POST['update_profile'])) {
                // Update profile information
                $name = sanitizeInput($_POST['name']);
                $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
                
                if (!$email) {
                    throw new Exception("Invalid email format");
                }
                
                // Check if email exists for another user
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                
                if ($stmt->rowCount() > 0) {
                    throw new Exception("Email already exists for another user");
                }
                
                // Update user
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $userId]);
                
                $_SESSION['message'] = "User profile updated successfully";
                redirect('admin.php');
                
            } elseif (isset($_POST['reset_password'])) {
                // Reset password
                $newPassword = generateRandomString(10);
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
                
                $_SESSION['message'] = "Password reset successfully. New password: " . htmlspecialchars($newPassword);
                redirect('reset_password.php');
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit"></i> Edit User: <?php echo htmlspecialchars($user['name']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <ul class="nav nav-tabs mb-4" id="userTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                                <i class="fas fa-user-circle"></i> Profile
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                                <i class="fas fa-key"></i> Password
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="userTabsContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Created</label>
                                    <p class="form-control-static"><?php echo formatDate($user['created_at']); ?></p>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="admin.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left"></i> Back to Admin
                                    </a>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> This will reset the user's password and generate a new random password.
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">User</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($user['name']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="reset_password" class="btn btn-warning" onclick="return confirm('Are you sure you want to reset this user\\'s password?')">
                                        <i class="fas fa-sync-alt"></i> Reset Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>