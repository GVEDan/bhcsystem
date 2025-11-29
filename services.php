<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - CLINICare | Barangay Health Center</title>
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

        /* Services Section */
        .services-section {
            padding: 80px 20px;
            background: #fff5f6;
        }

        .services-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            color: #b00020;
            margin-bottom: 50px;
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .section-title::after {
            content: '';
            width: 60px;
            height: 4px;
            background: #b00020;
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .service-box {
            width: 100%;
            padding: 60px 30px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            text-align: center;
            border-top: 5px solid #b00020;
            transition: all 0.3s ease;
        }

        .service-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(176, 0, 32, 0.15);
        }

        .service-box i {
            font-size: 50px;
            color: #b00020;
            margin-bottom: 20px;
        }

        .service-box h4 {
            font-size: 20px;
            font-weight: 700;
            color: #b00020;
            margin-bottom: 10px;
        }

        .service-box p {
            font-size: 14px;
            color: #666;
            opacity: 0.8;
            line-height: 1.6;
        }

        /* Service Details Cards */
        .service-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 80px;
        }

        .service-detail-card {
            background: white;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            border-left: 5px solid #b00020;
            transition: all 0.3s ease;
        }

        .service-detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(176, 0, 32, 0.15);
        }

        .service-detail-card h5 {
            color: #b00020;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .service-detail-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .service-detail-card li {
            color: #666;
            font-size: 14px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .service-detail-card li:before {
            content: "✓ ";
            color: #b00020;
            font-weight: bold;
            margin-right: 8px;
        }

        .service-detail-card li:last-child {
            border-bottom: none;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 992px) {
            .services-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .header-section h1 {
                font-size: 36px;
            }

            .section-title {
                font-size: 26px;
            }
        }

        @media (max-width: 576px) {
            .header-section {
                padding: 50px 20px;
            }

            .header-section h1 {
                font-size: 28px;
            }

            .services-section {
                padding: 40px 20px;
            }

            .section-title {
                font-size: 22px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .service-box {
                padding: 40px 25px;
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
        }
    </style>
</head>
<body>
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
        <h1>Our Services</h1>
        <p>Comprehensive healthcare services for your wellbeing</p>
    </section>

    <!-- Services Section -->
    <section class="services-section">
        <div class="services-container">
            <div class="section-title" style="margin-bottom: 60px;">What We Offer</div>
            <div class="services-grid">
                <div class="service-box">
                    <i class="fas fa-stethoscope"></i>
                    <h4>Medical Consultation</h4>
                    <p>Professional medical consultations with experienced healthcare providers to address your health concerns and preventive care needs.</p>
                </div>
                <div class="service-box">
                    <i class="fas fa-pills"></i>
                    <h4>Medicine Request</h4>
                    <p>Easy prescription medication management and requests through our convenient online system.</p>
                </div>
                <div class="service-box">
                    <i class="fas fa-file-medical"></i>
                    <h4>Medical Records</h4>
                    <p>Secure digital storage and management of your medical history and health records.</p>
                </div>
                <div class="service-box">
                    <i class="fas fa-heartbeat"></i>
                    <h4>Health Monitoring</h4>
                    <p>Track your health progress and receive recommendations for ongoing wellness management.</p>
                </div>
            </div>

            <!-- Service Details -->
            <div class="section-title" style="margin-bottom: 60px; margin-top: 80px;">Service Details</div>
            <div class="service-details">
                <div class="service-detail-card">
                    <h5><i class="fas fa-stethoscope"></i> Medical Consultation</h5>
                    <ul>
                        <li>General checkups</li>
                        <li>Specialist consultations</li>
                        <li>Follow-up appointments</li>
                        <li>Health assessments</li>
                        <li>Emergency consultations</li>
                    </ul>
                </div>
                <div class="service-detail-card">
                    <h5><i class="fas fa-pills"></i> Pharmacy Services</h5>
                    <ul>
                        <li>Prescription medicines</li>
                        <li>Over-the-counter medications</li>
                        <li>Medicine availability checks</li>
                        <li>Drug interaction counseling</li>
                        <li>Medication reminders</li>
                    </ul>
                </div>
                <div class="service-detail-card">
                    <h5><i class="fas fa-file-medical"></i> Medical Records</h5>
                    <ul>
                        <li>Digital health records</li>
                        <li>Test results storage</li>
                        <li>Prescription history</li>
                        <li>Diagnosis documentation</li>
                        <li>Record sharing options</li>
                    </ul>
                </div>
                <div class="service-detail-card">
                    <h5><i class="fas fa-calendar-check"></i> Appointment Services</h5>
                    <ul>
                        <li>Online booking</li>
                        <li>Doctor selection</li>
                        <li>Time slot availability</li>
                        <li>Appointment reminders</li>
                    </ul>
                </div>
                <div class="service-detail-card">
                    <h5><i class="fas fa-lock"></i> Data Security</h5>
                    <ul>
                        <li>NPR compliant</li>
                        <li>Encrypted data storage</li>
                        <li>Privacy protection</li>
                        <li>Secure access controls</li>
                        <li>Regular security audits</li>
                    </ul>
                </div>
            </div>

            <!-- Request Services Section -->
            <div class="section-title" style="margin-bottom: 60px; margin-top: 80px;">Request Services</div>
            <div class="services-grid" style="max-width: 800px; margin: 0 auto;">
                <div class="service-box request-service-box" onclick="window.location.href='index.php';" style="cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease;">
                    <i class="fas fa-pills"></i>
                    <h4>Request Medicine</h4>
                    <p>Request medicines from clinic</p>
                    <div style="font-size: 24px; color: #b00020; margin-top: 15px;">→</div>
                </div>
                <div class="service-box request-service-box" onclick="window.location.href='index.php';" style="cursor: pointer; border: 2px solid transparent; transition: all 0.3s ease;">
                    <i class="fas fa-stethoscope"></i>
                    <h4>Request Consultation</h4>
                    <p>Consult with a healthcare provider</p>
                    <div style="font-size: 24px; color: #b00020; margin-top: 15px;">→</div>
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
