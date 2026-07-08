<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMTS - Ahmedabad Municipal Transport Service</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="amtslogo.jpeg" type="image/x-icon">
</head>
<body>
    <!-- Top Header Bar -->
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
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="amtslogo.jpeg" alt="AMTS Logo">
                    <div class="logo-text">
                        <h1>AMTS</h1>
                        <p>Ahmedabad Municipal Transport Service</p>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="login.php" class="user-btn"><i class="fas fa-user"></i> Login</a>
                    <a href="register.php" class="user-btn"><i class="fas fa-user-plus"></i> Register</a>
                </div>
            </div>
        </div>
    </header>

    <marquee behavior="" direction="" style="color: white;" >Welcome To AMTS Website - India's largest Municipal Transport Service</marquee>

    <!-- Navigation Menu -->
    <nav>
        <div class="container">
            <ul class="main-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="about.html" ><i class="fas fa-info-circle"></i> About Us</a></li>
                <li><a href=""><i class="fas fa-bus"></i> Information</a>
                    <ul class="sub-menu">
                        <li><a href="passcategories.html"><i class="fas fa-list"></i> Traffic Department</a></li>
                        <li><a href="renew.php"><i class="fas fa-redo"></i> General Administration</a></li>
                        <li><a href="ereceipt.php"><i class="fas fa-receipt"></i> Workshop Department</a></li>
                    </ul>
                </li>
                <li><a href="#"><i class="fas fa-id-card"></i> Pass Management</a>
                    <ul class="sub-menu">
                        <li><a href="passcategories.html"><i class="fas fa-list"></i> View Pass Categories</a></li>
                        <li><a href="renew.php"><i class="fas fa-redo"></i> Renew Pass</a></li>
                        <li><a href="ereceipt.php"><i class="fas fa-receipt"></i> Download E-Receipt</a></li>
                    </ul>
                <li><a href="#"><i class="fas fa-headset"></i> Support</a>
                    <ul class="sub-menu">
                        <li><a href="feedback.php"><i class="fas fa-comment"></i> Give Feedback</a></li>
                    </ul>
                </li>
                <li><a href="contactus.html"><i class="fas fa-user-cog"></i> Contact Us</a></li>
            </ul>
        </div>
    </nav>


    <!-- Hero Banner -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2>Connecting Ahmedabad Since 1947</h2>
                <p>Experience the city's most extensive bus network with over 750 buses serving 300+ routes daily.</p>
                
            </div>
        </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="quick-actions">
        <div class="container">
            <h2 class="section-title">Quick Actions</h2>
            <div class="actions-grid">
                <a href="passcategories.html" class="action-card">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Purchase Pass</span>
                </a>
                <a href="renew.php" class="action-card">
                    <i class="fas fa-redo"></i>
                    <span>Renew Pass</span>
                </a>
                <a href="#" class="action-card">
                    <i class="fas fa-receipt"></i>
                    <span>E-Receipt</span>
                </a>
                <a href="" class="action-card">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Information</span>
                 
                </a>
                <a href="feedback.php" class="action-card">
                    <i class="fas fa-comment"></i>
                    <span>Give Feedback</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Pass Information Section -->
    <section class="pass-info">
        <div class="container">
            <div class="info-grid">
                <div class="info-card">
                    <h3><i class="fas fa-list"></i> Pass Categories</h3>
                    <ul>
                        <li>Student Pass</li>
                        <li>Senior Citizen Pass</li>
                        <li>Regular Commuter Pass</li>
                        <li>Daily Pass</li>
                        <li>Disable Person Pass</li>
                        <li>Women Special Pass</li>
                    </ul>
                    <a href="passcategories.html" class="info-link">View All Categories <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="info-card">
                    <h3><i class="fas fa-tag"></i> Pass Prices</h3>
                    <div class="price-table">
                        <div class="price-row">
                            <span>Student Monthly</span>
                            <span>₹400</span>
                        </div>
                        <div class="price-row">
                            <span>Women Special Pass</span>
                            <span>₹500</span>
                        </div>
                        <div class="price-row">
                            <span>Senior Citizen Pass</span>
                            <span>₹500</span>
                        </div>
                    </div>
                    <a href="passcategories.html" class="info-link">View Full Price List <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="info-card">
                    <h3><i class="fas fa-map-pin"></i> New Update </h3>
                    <div class="stand-info">
                        <p><strong>New Bus Added</strong></p>
                        <p>10 new buses have been added in bus no. 151 </p>
                        <p>Vivekanand Nagar to Iskon Mandir</p>
                    </div>
                    <a href="#" class="info-link">Follow For More Update <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section class="news-section">
        <div class="container">
            <h2 class="section-title">Latest News & Updates</h2>
            <div class="news-grid">
                <div class="news-card">
                    <div class="news-date">15 June 2023</div>
                    <h3>New Digital Pass System</h3>
                    <p>AMTS introduces digital passes that can be stored directly in your mobile wallet.</p>
                    <a href="passcategories.html" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="news-card">
                    <div class="news-date">10 June 2023</div>
                    <h3>Pass Renewal Discount</h3>
                    <p>You can renew your pass digitally</p>
                    <a href="renew.php" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="news-card">
                    <div class="news-date">05 June 2023</div>
                    <h3>New Stand Locations</h3>
                    <p>5 new bus stands added to improve accessibility in eastern Ahmedabad.</p>
                    <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
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
                        <li><a href="ereceipt.php">Download E-Receipt</a></li>
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

        // Mobile menu toggle
        document.querySelector('.menu-toggle').addEventListener('click', function() {
            document.querySelector('.main-menu').classList.toggle('active');
        });
    </script>
</body>
</html>