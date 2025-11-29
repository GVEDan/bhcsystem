<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - CLINICare | Barangay Health Center</title>
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="fontawesome-free-7.1.0-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: white;
            color: #333;
            overflow-x: hidden;
        }

        html {
            scroll-behavior: smooth;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
            padding: 15px 20px !important;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 8px 12px !important;
            margin: 0 5px;
            transition: all 0.3s ease;
            border-radius: 5px;
            font-size: 15px;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white !important;
        }

        .dashboard-link {
            background: white !important;
            color: #dc3545 !important;
            padding: 10px 20px !important;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 15px;
            white-space: nowrap;
            display: inline-block;
        }

        .dashboard-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: #dc3545 !important;
        }

        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            padding: 80px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header-section h1 {
            font-size: 52px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .header-section p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            max-width: 700px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Contact Section */
        .contact-section {
            padding: 80px 20px;
            background: white;
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        .map-box {
            min-height: 450px;
            border-radius: 15px;
            overflow: hidden;
            border: 3px solid #b00020;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .map-box iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .contact-box {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .contact-box label {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .contact-box input,
        .contact-box textarea {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #ffccd2;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .contact-box input:focus,
        .contact-box textarea:focus {
            outline: none;
            border-color: #b00020;
            box-shadow: 0 0 0 3px rgba(176, 0, 32, 0.1);
        }

        .contact-box button {
            padding: 12px;
            background: #b00020;
            color: white;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .contact-box button:hover {
            background: #8a0019;
            transform: translateY(-2px);
        }

        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 60px;
        }

        .info-card {
            background: #fff5f6;
            padding: 35px;
            border-radius: 16px;
            text-align: center;
            border-top: 5px solid #b00020;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(176, 0, 32, 0.15);
        }

        .info-card i {
            font-size: 40px;
            color: #b00020;
            margin-bottom: 20px;
        }

        .info-card h4 {
            font-size: 18px;
            font-weight: 700;
            color: #b00020;
            margin-bottom: 10px;
        }

        .info-card p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            padding: 14px 15px;
            font-size: 14px;
        }

        .alert-success {
            background: #e6f4e6;
            color: #2d5a2d;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #ffe6e6;
            color: #c82333;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }

            .header-section h1 {
                font-size: 36px;
            }

            .info-cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .header-section {
                padding: 50px 20px;
            }

            .header-section h1 {
                font-size: 28px;
            }

            .contact-section {
                padding: 40px 20px;
            }

            .map-box {
                min-height: 300px;
            }

            .navbar {
                padding: 12px 15px !important;
            }

            .navbar-brand {
                font-size: 18px;
            }

            .navbar-nav {
                flex-wrap: wrap;
                gap: 8px;
            }

            .navbar-nav .nav-link {
                font-size: 13px;
                padding: 6px 8px !important;
            }

            .info-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Contact Form Alerts -->
    <?php if (isset($_GET['contact'])): ?>
        <?php if ($_GET['contact'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 0; padding: 20px; border-radius: 0; font-size: 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 2000;">
                <i class="fas fa-check-circle"></i> <strong>Success!</strong> Your message has been sent successfully. We'll get back to you soon.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['contact'] === 'error'): ?>
            <?php 
                $error_messages = [
                    'missing_fields' => 'Please fill in all required fields.',
                    'invalid_email' => 'Please enter a valid email address.',
                    'send_failed' => 'Failed to send your message. Please try again later.'
                ];
                $msg = $_GET['msg'] ?? 'unknown_error';
                $error_text = $error_messages[$msg] ?? 'An error occurred. Please try again.';
            ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 0; padding: 20px; border-radius: 0; font-size: 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 2000;">
                <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo htmlspecialchars($error_text); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heartbeat"></i> <span>CLINICare
                <p style="font-size: small; color: black; font-weight: normal">Barangay Health Center</p></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="privacy_policy.php">Privacy Policy</a></li>
                    <?php if (Auth::isLoggedIn() && Auth::getRole() === 'patient'): ?>
                        <li class="nav-item"><a class="nav-link" href="patient/profile.php"><i class="fas fa-user"></i> My Account</a></li>
                        <li class="nav-item ms-2"><a href="includes/logout.php" class="dashboard-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section class="header-section">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you — reach us anytime</p>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-container">
            <div class="contact-grid">
                <div class="map-box">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.3858428934745!2d121.03433827511154!3d14.657417876521234!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b713f733070b%3A0x7e3f8cda25d81614!2sQuezon%20City!5e0!3m2!1sen!2sph!4v1709800000000" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <form class="contact-box" method="POST" action="process_contact.php">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your name" required>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="name@example.com" required>
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Reason for your message" required>
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="4" placeholder="Write your message here..." required></textarea>
                    <button type="submit">Send Message</button>
                </form>
            </div>

            <!-- Contact Information Cards -->
            <div class="info-cards">
                <div class="info-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h4>Our Location</h4>
                    <p>Barangay Health Center<br>City of Dasmariñas, Cavite, Philippines</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-phone"></i>
                    <h4>Phone Number</h4>
                    <p>(+63) 912 345 6789<br>Available during office hours</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-envelope"></i>
                    <h4>Email Address</h4>
                    <p>info@clinicare.com<br>support@clinicare.com</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-clock"></i>
                    <h4>Office Hours</h4>
                    <p>Mon-Fri: 8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Barangay Health Center. All rights reserved.</p>
    </div>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
