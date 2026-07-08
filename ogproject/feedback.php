<?php
// Database configuration
$db_host = 'localhost';  // Your database host
$db_name = 'amts';  // Your database name
$db_user = 'root';  // Your database username
$db_pass = '';  // Your database password

// Create connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $feedbackType = $_POST['feedbackType'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $route = $_POST['route'] ?? ''; // Optional field
    $rating = $_POST['rating'] ?? null; // Optional field
    $message = $_POST['message'];
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    // Simple validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($message)) $errors[] = "Message is required";

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO feedback (feedback_type, name, email, phone, route, rating, message, anonymous, created_at) 
                    VALUES (:type, :name, :email, :phone, :route, :rating, :message, :anonymous, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':type', $feedbackType);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':route', $route);
            $stmt->bindParam(':rating', $rating);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':anonymous', $anonymous, PDO::PARAM_INT);
            
            $stmt->execute();

            // ✅ Show popup on success and redirect
            echo "<script>alert('Form is successfully submitted'); window.location.href='feedback.php';</script>";
            exit();
            
            
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - AMTS Ahmedabad</title>
    <link rel="stylesheet" href="feedback.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <div class="top-header-content">
                <div class="date-time">
                    <span id="current-date"></span>
                    <span id="current-time"></span>
                </div>
                <div class="top-links">
                    <a href="contactus.html"><i class="fas fa-question-circle"></i> Help</a>
                    <a href="contactus.html"><i class="fas fa-phone"></i> Contact</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <img src="amtslogo.jpeg" alt="AMTS Logo">
                <div class="logo-text">
                    <h1>AMTS</h1>
                    <p>Ahmedabad Municipal Transport Service</p>
                </div>
            </div>
            <div class="user-actions">
                <a href="#" class="user-btn"><i class="fas fa-user"></i> Login</a>
                <a href="#" class="user-btn"><i class="fas fa-user-plus"></i> Register</a>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav>
        <div class="container">
            <ul class="main-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.html" ><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href=""><i class="fas fa-bus"></i> Information</a>
                    <ul class="sub-menu">
                        <li><a href="trafficdepartment.html"><i class="fas fa-list"></i> Traffic Management</a></li>
                        <li><a href="generaladmin.html" class="active"><i class="fas fa-redo"></i> General Administration</a></li>
                        <li><a href="workshopdepartment.html"><i class="fas fa-receipt"></i> Workshop Department</a></li>
                    </ul>
                </li>
                <li><a href="#"><i class="fas fa-id-card"></i> Pass Management</a>
                    <ul class="sub-menu">
                        <li><a href="passcategories.html"><i class="fas fa-list"></i> View Pass Categories</a></li>
                        <li><a href="renew.php"><i class="fas fa-redo"></i> Renew Pass</a></li>
                        <li><a href="ereceipt.php"><i class="fas fa-receipt"></i> Download E-Receipt</a></li>
                    </ul>
                </li>
                <li><a href="#"  class="active"><i class="fas fa-headset"></i> Support</a>
                    <ul class="sub-menu">
                        <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
                    </ul>
                </li>
                <li><a href="contactus.html"><i class="fas fa-user-cog"></i> Contect Us</a></li>
            </ul>
        </div>
    </nav>
    <!-- Hero Section -->
    <section class="hero feedback-hero">
        <div class="container">
            <h2>Share Your Feedback</h2>
            <p>Help us improve AMTS services with your valuable suggestions</p>
        </div>
    </section>

    <!-- Feedback Section -->
    <section class="feedback-section">
        <div class="container">
            <div class="section-title">
                <h2>Your Opinion Matters</h2>
                <p>We appreciate your time in helping us serve you better</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form class="feedback-form" id="feedbackForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="feedbackType">Feedback Type</label>
                    <select id="feedbackType" name="feedbackType" class="form-control" required>
                        <option value="general" <?php echo (isset($_POST['feedbackType']) && $_POST['feedbackType'] == 'general') ? 'selected' : ''; ?>>General Feedback</option>
                        <option value="complaint" <?php echo (isset($_POST['feedbackType']) && $_POST['feedbackType'] == 'complaint') ? 'selected' : ''; ?>>Complaint</option>
                        <option value="suggestion" <?php echo (isset($_POST['feedbackType']) && $_POST['feedbackType'] == 'suggestion') ? 'selected' : ''; ?>>Suggestion</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Mobile Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="route">Bus Route (if applicable)</label>
                        <input type="text" id="route" name="route" class="form-control" 
                               value="<?php echo isset($_POST['route']) ? htmlspecialchars($_POST['route']) : ''; ?>" 
                               placeholder="e.g. Route 101, 45, etc.">
                    </div>
                </div>

                <div class="form-group">
                    <label>Rate Your Experience</label>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                                <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                        <span id="rating-text">
                            <?php if (isset($_POST['rating'])): ?>
                                <?php 
                                    $ratings = [1 => "Poor", 2 => "Fair", 3 => "Good", 4 => "Very Good", 5 => "Excellent"];
                                    echo $ratings[$_POST['rating']];
                                ?>
                            <?php else: ?>
                                Select rating
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="message">Your Feedback</label>
                    <textarea id="message" name="message" class="form-control" required
                        placeholder="Please share your detailed feedback here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="anonymous" id="anonymous"
                            <?php echo (isset($_POST['anonymous'])) ? 'checked' : ''; ?>>
                        <span>Submit anonymously</span>
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>About AMTS</h3>
                    <p>Ahmedabad Municipal Transport Service (AMTS) is the public transport provider for Ahmedabad city, serving millions of passengers daily.</p>
                </div>
                <div class="footer-col">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="passcategories.html">Pass Categories</a></li>
                        <li><a href="renew.php"> Renew Pass </a></li>
                        <li><a href="#">Download E-Receipt</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Help & Support</h3>
                    <ul>
                        <li><a href="contactus.html">FAQs</a></li>
                        <li><a href="feedback.php">Give Feedback</a></li>
                        <li><a href="feedback.php">Complaint Resolution</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact Us</h3>
                     <p><i class="fas fa-map-marker-alt"></i> AMTS Head Office, Ahmedabad</p>
                    <p><i class="fas fa-phone"></i> 079-25323517/ 25391881-86</p>
                    <p><i class="fas fa-envelope"></i> amtsamc@gmail.com</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/amtsahmedabad/"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Ahmedabad Municipal Transport Service. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Update date and time
        function updateDateTime() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
            
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
        }
        
        setInterval(updateDateTime, 1000);
        updateDateTime();

       // Star rating interaction
const stars = document.querySelectorAll('.rating-stars input');
const ratingText = document.getElementById('rating-text');
const ratingTexts = {
    1: "Poor",
    2: "Fair",
    3: "Good",
    4: "Very Good",
    5: "Excellent"
};

stars.forEach(star => {
    star.addEventListener('change', function() {
        ratingText.textContent = ratingTexts[this.value];
    });
    
    // Add hover effect
    star.addEventListener('mouseover', function() {
        const currentRating = this.value;
        stars.forEach((s, index) => {
            if (index < currentRating) {
                s.nextElementSibling.style.color = '#f4a261';
            }
        });
    });
    
    star.addEventListener('mouseout', function() {
        const checkedStar = document.querySelector('.rating-stars input:checked');
        stars.forEach(s => {
            s.nextElementSibling.style.color = '#a8dadc';
        });
        if (checkedStar) {
            const currentRating = checkedStar.value;
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.nextElementSibling.style.color = '#f4a261';
                }
            });
        }
    });
});

// Initialize rating text if there's a checked star on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkedStar = document.querySelector('.rating-stars input:checked');
    if (checkedStar) {
        ratingText.textContent = ratingTexts[checkedStar.value];
    }
});
    </script>
</body>
</html>