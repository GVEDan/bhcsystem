<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Get all doctors from database
$doctors = Doctor::getAllDoctors();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - CLINICare | Barangay Health Center</title>
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

        /* About Section */
        .about-section {
            padding: 80px 20px;
            background: #fff5f6;
        }

        .about-container {
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

        .about-content {
            background: white;
            padding: 50px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            margin-bottom: 60px;
        }

        .about-content h2 {
            color: #b00020;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 26px;
        }

        .about-content p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 50px;
        }

        .mission-box, .vision-box {
            background: white;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            border-top: 5px solid #b00020;
        }

        .mission-box h3, .vision-box h3 {
            color: #b00020;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mission-box p, .vision-box p {
            color: #666;
            line-height: 1.8;
            font-size: 15px;
        }

        .mission-box i, .vision-box i {
            color: #b00020;
            font-size: 24px;
        }

        /* Team Section */
        .team-section {
            padding: 80px 20px;
            background: white;
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .doc-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 5px solid #b00020;
            width: 100%;
            transition: all 0.3s ease;
        }

        .doc-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(176, 0, 32, 0.2);
        }

        .doc-card img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #b00020;
            margin-bottom: 12px;
            background: #f0f0f0;
        }

        .doc-name {
            font-size: 18px;
            font-weight: 700;
            color: #b00020;
            margin-bottom: 5px;
        }

        .doc-spec {
            font-size: 14px;
            color: #8a0019;
            font-weight: 500;
        }

        /* Values Section */
        .values-section {
            padding: 80px 20px;
            background: #fff5f6;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .value-box {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            text-align: center;
            border-top: 5px solid #b00020;
            transition: all 0.3s ease;
        }

        .value-box:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(176, 0, 32, 0.15);
        }

        .value-box i {
            font-size: 50px;
            color: #b00020;
            margin-bottom: 20px;
        }

        .value-box h4 {
            font-size: 20px;
            font-weight: 700;
            color: #b00020;
            margin-bottom: 10px;
        }

        .value-box p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
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
            .mission-vision {
                grid-template-columns: 1fr;
            }

            .header-section h1 {
                font-size: 36px;
            }

            .values-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .about-content {
                padding: 30px;
            }
        }

        @media (max-width: 576px) {
            .header-section {
                padding: 50px 20px;
            }

            .header-section h1 {
                font-size: 28px;
            }

            .about-section, .team-section, .values-section {
                padding: 40px 20px;
            }

            .section-title {
                font-size: 24px;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
            }

            .doc-card {
                max-width: 250px;
                margin: 0 auto;
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
        <h1>About CLINICare</h1>
        <p>Your trusted healthcare partner in the Barangay</p>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="about-container">
            <div class="about-content">
                <h2><i class="fas fa-heart"></i> Who We Are</h2>
                <p>CLINICare is a comprehensive healthcare management system designed to serve the Barangay Health Center with excellence and efficiency. We are committed to bridging the gap between patients and healthcare providers through innovative technology and compassionate care.</p>
                <p>Our platform facilitates seamless communication, efficient appointment scheduling, and secure medical record management. We believe that quality healthcare should be accessible, affordable, and convenient for everyone in our community.</p>
            </div>

            <!-- Mission & Vision -->
            <div class="mission-vision">
                <div class="mission-box">
                    <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
                    <p>To provide accessible, high-quality healthcare services to all barangay residents through an innovative digital platform that promotes wellness, preventive care, and community health.</p>
                </div>
                <div class="vision-box">
                    <h3><i class="fas fa-eye"></i> Our Vision</h3>
                    <p>To become the leading healthcare system in the barangay, ensuring that every resident receives timely, equitable, and patient-centered care that improves their quality of life.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="about-container">
            <div class="section-title" style="margin-bottom: 60px;">Our Healthcare Team</div>
            <div class="doctors-grid">
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doc-card">
                        <img src="assets/img/doctor_<?php echo strtolower(str_replace(' ', '', $doctor['last_name'])); ?>.jpg" alt="Dr. <?php echo htmlspecialchars($doctor['first_name']) . ' ' . htmlspecialchars($doctor['last_name']); ?>" onerror="this.src='assets/img/default-doctor.jpg'">
                        <div class="doc-name">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></div>
                        <div class="doc-spec"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                        <?php if (!empty($doctor['bio'])): ?>
                            <div class="doc-bio" style="font-size: 12px; color: #666; margin-top: 8px; line-height: 1.4;">
                                <?php echo htmlspecialchars($doctor['bio']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="about-container">
            <div class="section-title" style="margin-bottom: 60px;">Our Core Values</div>
            <div class="values-grid">
                <div class="value-box">
                    <i class="fas fa-user-check"></i>
                    <h4>Patient-Centered</h4>
                    <p>Every decision we make is centered around improving the patient experience and health outcomes.</p>
                </div>
                <div class="value-box">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Integrity</h4>
                    <p>We maintain the highest standards of honesty, ethics, and transparency in all our operations.</p>
                </div>
                <div class="value-box">
                    <i class="fas fa-hands-helping"></i>
                    <h4>Compassion</h4>
                    <p>We provide care with empathy and understanding, treating every patient with dignity and respect.</p>
                </div>
                <div class="value-box">
                    <i class="fas fa-lightbulb"></i>
                    <h4>Innovation</h4>
                    <p>We continuously improve our services through technology and evidence-based healthcare practices.</p>
                </div>
                <div class="value-box">
                    <i class="fas fa-users"></i>
                    <h4>Community</h4>
                    <p>We are dedicated to strengthening the health and wellbeing of our entire barangay community.</p>
                </div>
                <div class="value-box">
                    <i class="fas fa-lock"></i>
                    <h4>Privacy & Security</h4>
                    <p>We protect patient information with the highest security standards and strict confidentiality protocols.</p>
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
