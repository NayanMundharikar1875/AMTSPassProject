<?php
function getStatusColor($status) {
    switch(strtolower($status)) {
        case 'approved': return 'success';
        case 'rejected': return 'danger';
        case 'pending': return 'warning';
        default: return 'secondary';
    }
}

function getTypeColor($type) {
    switch(strtolower($type)) {
        case 'application': return 'primary';
        case 'renewal': return 'info';
        case 'feedback': return 'purple';
        default: return 'secondary';
    }
}



function displayMessage() {
    if (isset($_SESSION['message'])) {
        $alertType = strpos($_SESSION['message'], 'error') !== false ? 'danger' : 'success';
        echo '<div class="alert alert-'.$alertType.' alert-dismissible fade show mb-4">';
        echo $_SESSION['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMTS Admin Panel</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --purple: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --white: #ffffff;
            --sidebar-width: 250px;
            --header-height: 70px;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--white);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: var(--transition);
        }
        
        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .sidebar-brand img {
            height: 40px;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .sidebar-menu li {
            position: relative;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            color: var(--primary);
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .sidebar-menu li a:hover::before,
        .sidebar-menu li a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--primary);
            border-radius: 0 4px 4px 0;
        }
        
        .sidebar-menu li i {
            font-size: 1.1rem;
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
        }
        
        /* Header */
        .header {
            height: var(--header-height);
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .header .logo i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: 600;
        }
        
        .user-info .name {
            font-weight: 500;
            margin-right: 15px;
        }
        
        .logout-btn {
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            color: var(--danger);
        }
        
        .logout-btn i {
            margin-right: 5px;
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .dashboard-header h2 {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        /* Stat Cards */
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.users {
            border-left: 4px solid var(--success);
        }
        
        .stat-card.applications {
            border-left: 4px solid var(--info);
        }
        
        .stat-card.renewals {
            border-left: 4px solid var(--warning);
        }
        
        .stat-card.feedback {
            border-left: 4px solid var(--purple);
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .stat-title i {
            margin-right: 8px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .stat-icon {
            font-size: 1.5rem;
            color: rgba(0, 0, 0, 0.1);
        }
        
        .status-badges .badge {
            font-size: 0.7rem;
            padding: 0.35em 0.65em;
        }
        
        /* Status badge styles */
.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

/* Status colors */
.badge.bg-success {
    background-color: #28a745;
    color: white;
}

.badge.bg-danger {
    background-color: #dc3545;
    color: white;
}

.badge.bg-warning {
    background-color: #ffc107;
    color: #212529;
}

.badge.bg-secondary {
    background-color: #6c757d;
    color: white;
}
        /* Badge Colors */
        .bg-purple {
            background-color: var(--purple) !important;
        }
        
        /* Cards */
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--gray-light);
            padding: 1.25rem 1.5rem;
        }
        
        .card-title {
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        /* Tables */
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: var(--gray);
            border-top: none;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .stat-value {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="amtslogo.jpeg" alt="AMTS Logo">
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li>
                <a href="applications.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>New Passes</span>
                </a>
            </li>
            <li>
                <a href="renewals.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'renewals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sync-alt"></i>
                    <span>Renew Passes</span>
                </a>
            </li>
            <li>
                <a href="feedback.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>User Feedback</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary d-lg-none me-2 sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="logo">
                    <i class="fas fa-bus"></i>
                    <span>AMTS Admin</span>
                </div>
            </div>
            <div class="user-info">
                <div class="avatar"><?php echo substr($_SESSION['admin'], 0, 1); ?></div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['admin']); ?></div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">