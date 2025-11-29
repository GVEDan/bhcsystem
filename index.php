<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Redirect admin/doctor to dashboard
if (Auth::isLoggedIn() && (Auth::getRole() === 'admin' || Auth::getRole() === 'doctor')) {
    header('Location: admin/dashboard.php');
    exit();
}

// Get all doctors from database
$doctors = Doctor::getAllDoctors();

// Get patient data if logged in
$user = null;
$appointments = [];
$records = [];
$medicine_requests = [];
$consultation_requests = [];
$error = '';
$success = '';

if (Auth::isLoggedIn() && Auth::getRole() === 'patient') {
    $user = Auth::getCurrentUser();
    $appointments = Appointment::getPatientAppointments($user['id']);
    $records = MedicalRecord::getPatientRecords($user['id']);
    $medicine_requests = MedicineRequest::getPatientRequests($user['id']);
    $consultation_requests = ConsultationRequest::getPatientRequests($user['id']);

    // Handle medicine request submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_medicine_request'])) {
        $result = MedicineRequest::submitRequest(
            $user['id'],
            $_POST['medicine_name'],
            (int)$_POST['quantity'],
            $_POST['reason']
        );
        
        if ($result['success']) {
            $success = $result['message'];
            $medicine_requests = MedicineRequest::getPatientRequests($user['id']);
        } else {
            $error = $result['message'];
        }
    }

    // Handle consultation request submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_consultation_request'])) {
        $result = ConsultationRequest::submitRequest(
            $user['id'],
            $_POST['consultation_type'],
            $_POST['description'],
            $_POST['preferred_date'] ?? null,
            $_POST['doctor_id'] ?? null
        );
        
        if ($result['success']) {
            $success = $result['message'];
            $consultation_requests = ConsultationRequest::getPatientRequests($user['id']);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CLINICare | Barangay Health Center - Quality Care for All</title>
    <link rel="stylesheet" href="styles.css">
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

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            padding: 100px 20px;
            text-align: center;
            min-height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .hero-content h1 {
            font-size: 56px;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            line-height: 1.3;
            position: relative;
            z-index: 1;
        }

        .hero-content p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 1;
        }

        .hero-btn {
            background: white;
            color: #dc3545;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 15px;
            position: relative;
            z-index: 1;
        }

        .hero-btn:hover {
            background: #f0f0f0;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
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

        .services-section {
            padding: 100px 20px;
            background: #fff5f6;
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

        .service-box.request-service-box {
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            transition: all 0.3s ease;
        }

        .service-box.request-service-box:hover {
            border-color: #b00020;
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(176, 0, 32, 0.15);
        }

        .service-box.request-service-box::after {
            content: "→";
            position: absolute;
            bottom: 20px;
            font-size: 32px;
            color: #b00020;
            opacity: 0.6;
            transition: all 0.3s ease;
        }

        .service-box.request-service-box:hover::after {
            opacity: 1;
            transform: translateX(5px);
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
        }

        .team-section {
            padding: 100px 20px;
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
            border-radius: 8px;
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

        .hours-section {
            padding: 80px 20px;
            background: #fff5f6;
            text-align: center;
        }

        .hours-box {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            border-top: 5px solid #b00020;
        }

        .hours-title {
            font-size: 22px;
            font-weight: 700;
            color: #b00020;
            margin-bottom: 15px;
        }

        .hours-box p {
            font-size: 15px;
            color: #666;
            margin-bottom: 10px;
        }

        .hours-box b {
            color: #1a1a1a;
        }

        .contact-section {
            padding: 80px 20px;
            background: white;
        }

        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .map-box {
            min-height: 350px;
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

        .footer {
            background: #1a1a1a;
            color: white;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }

        @media (max-width: 900px) {
            .hero-section {
                padding: 60px 20px;
                min-height: auto;
            }

            .hero-content h1 {
                font-size: 36px;
            }

            .hero-content p {
                font-size: 16px;
            }

            .section-title {
                font-size: 24px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .doctors-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }

            .contact-container {
                grid-template-columns: 1fr;
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

            .dashboard-link {
                font-size: 12px;
                padding: 8px 10px !important;
            }
        }

        @media (max-width: 576px) {
            .hero-content h1 {
                font-size: 28px;
            }

            .section-title {
                font-size: 20px;
            }

            .service-box {
                padding: 40px 20px;
            }

            .doctors-grid {
                grid-template-columns: 1fr;
            }

            .doc-card {
                max-width: 250px;
                margin: 0 auto;
            }
        }

        .patient-dashboard-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #fff 100%);
            padding: 40px 20px;
            min-height: 100vh;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .dashboard-header h2 {
            color: #333;
            font-weight: 700;
            margin: 0;
        }

        .btn-close-dashboard {
            background: none;
            border: none;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .btn-close-dashboard:hover {
            background: #dc3545;
            color: white;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #dc3545;
        }

        .stat-card i {
            color: #dc3545;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #dc3545;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .request-cards-section {
            margin-bottom: 50px;
        }

        .request-cards-section h3 {
            color: #333;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .request-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .request-card {
            background: white;
            border-radius: 15px;
            padding: 35px 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            text-align: center;
        }

        .request-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.15);
            border-color: #dc3545;
        }

        .request-card .card-icon {
            font-size: 50px;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .request-card .card-title {
            font-size: 19px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .request-card .card-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .request-card .card-action {
            font-size: 20px;
            color: #dc3545;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .request-card:hover .card-action {
            opacity: 1;
            transform: translateX(5px);
        }

        .service-form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h5 {
            margin: 0;
            font-weight: 700;
        }

        .form-header .btn-close {
            filter: brightness(0) invert(1);
            cursor: pointer;
        }

        .form-body {
            padding: 30px;
        }

        .appointments-list-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .appointments-list-section h5 {
            color: #333;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .appointment-item {
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .appointment-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            background: #ffffff;
        }

        .appointment-item h6 {
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .appointment-item p {
            color: #555;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-pending {
            background-color: #ffc107 !important;
            color: #000 !important;
        }

        .badge-approved {
            background-color: #28a745 !important;
            color: white !important;
        }

        .badge-rejected {
            background-color: #dc3545 !important;
            color: white !important;
        }

        .badge-completed {
            background-color: #17a2b8 !important;
            color: white !important;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .form-control:focus, .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .table {
            color: #333;
        }

        .table thead {
            background-color: #f8f9fa;
            color: #333;
        }

        .table th {
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            color: #555;
            vertical-align: middle;
        }

        .table-light {
            background-color: #f8f9fa !important;
        }

        .table-hover tbody tr:hover {
            background-color: #f0f0f0;
            color: #333;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        @media (max-width: 992px) {
            .service-form-container {
                grid-template-columns: 1fr;
            }

            .request-cards-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .request-card {
                padding: 25px 20px;
            }

            .request-card .card-icon {
                font-size: 40px;
            }

            .request-card .card-title {
                font-size: 17px;
            }

            .patient-dashboard-section {
                padding: 20px 10px;
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

    <div id="top" class="hero-section">
        <div class="hero-content">
            <?php if (Auth::isLoggedIn() && Auth::getRole() === 'patient' && $user): ?>
                <!-- Patient Greeting Only -->
                <h1>Greetings, <span style="color: #ffff00;"><?php echo htmlspecialchars($user['first_name']); ?></span></h1>
            <?php else: ?>
                <!-- General Greeting -->
                <h1>Your Trusted Healthcare<br>In your Barangay</h1>
                <p>Fast, secure & paperless clinic services for the barangay community.</p>
                <button class="hero-btn" onclick="location.href='login.php'">Request Services</button>
            <?php endif; ?>
        </div>
    </div>

    <section id="services-section" class="services-section">
        <div class="container-fluid">
            <?php if (Auth::isLoggedIn() && Auth::getRole() === 'patient' && $user): ?>
                <!-- Alert Message -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" style="margin-bottom: 30px;"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" style="margin-bottom: 30px;"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <!-- Patient Dashboard Stats -->
                <div style="text-align: center; margin: 40px 0 50px 0;">
                    <h2 style="color: #b00020; font-weight: 700; margin-bottom: 0;"><i class="fas fa-chart-line"></i> Your Statistics</h2>
                </div>
                <div id="statsDefaultView" style="margin-bottom: 80px;">
                    <div class="dashboard-stats" style="display: flex; flex-direction: row; gap: 20px; justify-content: center;">
                        <div class="stat-card" style="flex: 1; max-width: 250px;">
                            <i class="fas fa-calendar fa-2x"></i>
                            <div class="stat-number"><?php echo count($appointments); ?></div>
                            <div class="stat-label">Total Appointments</div>
                        </div>
                        <div class="stat-card" style="flex: 1; max-width: 250px;">
                            <i class="fas fa-file-medical fa-2x"></i>
                            <div class="stat-number"><?php echo count($records); ?></div>
                            <div class="stat-label">Medical Records</div>
                        </div>
                        <div class="stat-card" style="flex: 1; max-width: 250px;">
                            <i class="fas fa-clock fa-2x"></i>
                            <div class="stat-number"><?php echo count(array_filter($appointments, fn($a) => $a['status'] === 'pending')); ?></div>
                            <div class="stat-label">Pending Appointments</div>
                        </div>
                    </div>
                </div>

                <!-- Request Services Section -->
                <div class="section-title" style="margin-bottom: 60px;">Request Services</div>
                <div class="services-grid">
                    <div class="service-box request-service-box" onclick="toggleForm('medicineForm')">
                        <i class="fas fa-pills"></i>
                        <h4>Request Medicine</h4>
                        <p>Request medicines from clinic</p>
                    </div>
                    <div class="service-box request-service-box" onclick="toggleForm('consultationForm')">
                        <i class="fas fa-stethoscope"></i>
                        <h4>Request Consultation</h4>
                        <p>Consult with a healthcare provider</p>
                    </div>
                </div>
                <!-- Request Medicine Form (Hidden) -->
                <div class="service-form-container" id="medicineForm" style="display: none;">
                    <div class="form-card">
                        <div class="form-header">
                            <h5><i class="fas fa-pills"></i> Request Medicine</h5>
                            <button type="button" class="btn-close" onclick="toggleForm('medicineForm')"></button>
                        </div>
                        <div class="form-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="medicine_name" class="form-label">Medicine Name</label>
                                        <input type="text" class="form-control" id="medicine_name" name="medicine_name" placeholder="Enter medicine name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Enter quantity" min="1" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="medicine_reason" class="form-label">Reason for Request</label>
                                    <textarea class="form-control" id="medicine_reason" name="reason" rows="3" placeholder="Describe why you need this medicine" required></textarea>
                                </div>
                                <button type="submit" name="submit_medicine_request" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Submit Request</button>
                            </form>
                        </div>
                    </div>
                    <div class="appointments-list-section">
                        <h5 class="mb-3"><i class="fas fa-history"></i> Your Medicine Requests</h5>
                        <?php if (empty($medicine_requests)): ?>
                            <p class="text-muted text-center py-3">No medicine requests yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medicine_requests as $req): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($req['medicine_name'] ?? ''); ?></strong></td>
                                                <td><?php echo $req['quantity'] ?? '-'; ?></td>
                                                <td>
                                                    <?php 
                                                    $statusClass = 'badge-' . ($req['status'] ?? 'pending');
                                                    echo '<span class="badge ' . $statusClass . '">' . ucfirst($req['status'] ?? 'pending') . '</span>';
                                                    ?>
                                                </td>
                                                <td><small><?php echo date('M d, Y', strtotime($req['created_at'] ?? date('Y-m-d'))); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Request Consultation Form (Hidden) -->
                <div class="service-form-container" id="consultationForm" style="display: none;">
                    <div class="form-card">
                        <div class="form-header">
                            <h5><i class="fas fa-stethoscope"></i> Request Consultation</h5>
                            <button type="button" class="btn-close" onclick="toggleForm('consultationForm')"></button>
                        </div>
                        <div class="form-body">
                            <form method="POST" id="consultation_request_form">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="consultation_type" class="form-label">Consultation Type</label>
                                        <select class="form-select" id="consultation_type" name="consultation_type" required>
                                            <option value="">Select Type</option>
                                            <option value="General Checkup">General Checkup</option>
                                            <option value="Follow-up">Follow-up</option>
                                            <option value="Emergency">Emergency</option>
                                            <option value="Specialist">Specialist</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="consultation_doctor_id" class="form-label">Preferred Doctor</label>
                                        <select class="form-select" id="consultation_doctor_id" name="doctor_id" onchange="loadConsultationDoctorAvailability()">
                                            <option value="">Any Available</option>
                                            <?php 
                                            foreach ($doctors as $doctor): 
                                            ?>
                                                <option value="<?php echo $doctor['id']; ?>" data-specialization="<?php echo $doctor['specialization']; ?>">
                                                    Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?> - <?php echo $doctor['specialization']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block mt-2" id="consultation_doctor_info"></small>
                                    </div>
                                </div>

                                <div id="consultation_doctor_schedule" style="display: none; margin-bottom: 20px;">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-clock"></i> Doctor's Weekly Schedule</h6>
                                        <div id="consultation_schedule_content" style="margin-top: 10px;"></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="consultation_date" class="form-label">Preferred Date</label>
                                        <input type="date" class="form-control" id="consultation_date" name="preferred_date" onchange="loadConsultationAvailableSlots()">
                                        <small class="text-muted d-block mt-2" id="consultation_availability_info"></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="consultation_time" class="form-label">Preferred Time (Optional)</label>
                                        <select class="form-select" id="consultation_time" name="consultation_time">
                                            <option value="">Select Date First</option>
                                            <option value="08:00:00">08:00 AM</option>
                                            <option value="09:00:00">09:00 AM</option>
                                            <option value="10:00:00">10:00 AM</option>
                                            <option value="11:00:00">11:00 AM</option>
                                            <option value="13:00:00">01:00 PM</option>
                                            <option value="14:00:00">02:00 PM</option>
                                            <option value="15:00:00">03:00 PM</option>
                                            <option value="16:00:00">04:00 PM</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="available_doctors_section" style="display: none; margin-bottom: 20px;">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-user-md"></i> Available Doctors</h6>
                                        <div id="available_doctors_list" style="margin-top: 10px;"></div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="consultation_description" class="form-label">Describe Your Concern</label>
                                    <textarea class="form-control" id="consultation_description" name="description" rows="3" placeholder="Describe your symptoms or concerns" required></textarea>
                                </div>
                                <button type="submit" name="submit_consultation_request" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Submit Request</button>
                            </form>
                        </div>
                    </div>
                    <div class="appointments-list-section">
                        <h5 class="mb-3"><i class="fas fa-history"></i> Your Consultation Requests</h5>
                        <?php if (empty($consultation_requests)): ?>
                            <p class="text-muted text-center py-3">You have not requested any consultations yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Doctor</th>
                                            <th>Preferred Date</th>
                                            <th>Status</th>
                                            <th>Requested Date</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($consultation_requests as $req): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($req['consultation_type'] ?? ''); ?></strong></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($req['doctor_id'])) {
                                                        echo 'Dr. ' . htmlspecialchars(($req['doctor_first_name'] ?? '') . ' ' . ($req['doctor_last_name'] ?? ''));
                                                    } else {
                                                        echo 'Not Assigned';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (!empty($req['preferred_date'])) {
                                                        echo date('M d, Y', strtotime($req['preferred_date']));
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $statusClass = 'badge-' . ($req['status'] ?? 'pending');
                                                    echo '<span class="badge ' . $statusClass . '">' . ucfirst($req['status'] ?? 'pending') . '</span>';
                                                    ?>
                                                </td>
                                                <td><small><?php echo date('M d, Y', strtotime($req['created_at'] ?? date('Y-m-d'))); ?></small></td>
                                                <td><?php echo !empty($req['admin_notes']) ? htmlspecialchars($req['admin_notes']) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Services view for not logged in users -->
                <div class="section-title" style="margin-bottom: 60px;">Our Services</div>
                <div class="services-grid">
                    <div class="service-box">
                        <i class="fas fa-stethoscope"></i>
                        <h4>Consultation</h4>
                        <p>Queue-free online consultations</p>
                    </div>
                    <div class="service-box">
                        <i class="fas fa-pills"></i>
                        <h4>Medicine Request</h4>
                        <p>Request clinic medicines conveniently</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>



    <!-- Clinic Hours -->
    <section class="hours-section">
        <div class="container-fluid">
            <div class="section-title" style="margin-bottom: 60px;">Clinic Hours</div>
            <div class="hours-box">
                <div class="hours-title">We Are Open</div>
                <p><b>Mon — Fri</b> : 8:00 AM — 5:00 PM</p>
                <p><b>Saturday</b> : 8:00 AM — 12:00 PM</p>
                <p><b>Sunday</b> : Closed</p>
            </div>
        </div>
    </section>

    <!-- Healthcare Services Gallery Section - Bottom -->
    <section id="services-gallery-section" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 80px 20px;">
        <div class="container-fluid">
            <div style="text-align: center; margin-bottom: 80px;">
                <h2 style="font-size: 2.5rem; font-weight: 700; color: #333; margin-bottom: 15px;">CLINICare Gallery</h2>
                <p style="font-size: 1.1rem; color: #666; max-width: 600px; margin: 0 auto;">Comprehensive healthcare services provided by our dedicated team in the barangay</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1400px; margin: 0 auto;">
                <div style="position: relative; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0, 0, 0, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0, 0, 0, 0.1)';">
                    <div style="width: 100%; aspect-ratio: 4/3; border-radius: 16px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #b00020;">
                        <img src="assets/img/service_1.jpg" alt="Healthcare Service" onerror="this.src='https://via.placeholder.com/350x260?text=Healthcare+Service'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                </div>
                <div style="position: relative; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0, 0, 0, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0, 0, 0, 0.1)';">
                    <div style="width: 100%; aspect-ratio: 4/3; border-radius: 16px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #b00020;">
                        <img src="assets/img/service_2.jpg" alt="Healthcare Service" onerror="this.src='https://via.placeholder.com/350x260?text=Healthcare+Service'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                </div>
                <div style="position: relative; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0, 0, 0, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0, 0, 0, 0.1)';">
                    <div style="width: 100%; aspect-ratio: 4/3; border-radius: 16px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #b00020;">
                        <img src="assets/img/service_3.jpg" alt="Healthcare Service" onerror="this.src='https://via.placeholder.com/350x260?text=Healthcare+Service'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                </div>
                <div style="position: relative; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0, 0, 0, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0, 0, 0, 0.1)';">
                    <div style="width: 100%; aspect-ratio: 4/3; border-radius: 16px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #b00020;">
                        <img src="assets/img/service_4.jpg" alt="Healthcare Service" onerror="this.src='https://via.placeholder.com/350x260?text=Healthcare+Service'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                </div>
                <div style="position: relative; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0, 0, 0, 0.2)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 30px rgba(0, 0, 0, 0.1)';">
                    <div style="width: 100%; aspect-ratio: 4/3; border-radius: 16px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #b00020;">
                        <img src="assets/img/service_5.jpg" alt="Healthcare Service" onerror="this.src='https://via.placeholder.com/350x260?text=Healthcare+Service'" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="footer">
        <p>&copy; 2025 Barangay Health Center. All rights reserved.</p>
    </div>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to toggle form visibility
        function toggleForm(formId) {
            const form = document.getElementById(formId);
            const allForms = document.querySelectorAll('.service-form-container');
            
            // Hide all forms first
            allForms.forEach(f => {
                if (f.id !== formId) {
                    f.style.display = 'none';
                }
            });
            
            // Toggle current form
            if (form.style.display === 'none') {
                form.style.display = 'grid';
                setTimeout(() => {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            } else {
                form.style.display = 'none';
            }
        }

        // Function to toggle statistics graph display
        const appointmentsData = <?php echo json_encode($appointments); ?>;
        const recordsData = <?php echo json_encode($records); ?>;
        
        // Stat navigation
        const stats = ['appointments', 'records', 'pending'];
        let currentStatIndex = 0;

        function previousStat() {
            currentStatIndex = (currentStatIndex - 1 + stats.length) % stats.length;
            toggleStatGraph(stats[currentStatIndex]);
        }

        function nextStat() {
            currentStatIndex = (currentStatIndex + 1) % stats.length;
            toggleStatGraph(stats[currentStatIndex]);
        }

        function updateStatIndicator() {
            const indicator = document.getElementById('statIndicator');
            indicator.textContent = `${currentStatIndex + 1} / ${stats.length}`;
        }

        function drawAppointmentsGraph(pending, confirmed, completed, total) {
            const canvas = document.getElementById('appointmentsCanvas');
            const ctx = canvas.getContext('2d');
            const chartWidth = canvas.width - 100;
            const chartHeight = canvas.height - 80;
            const barWidth = chartWidth / 3;
            const maxValue = Math.max(pending, confirmed, completed, 1);
            const scale = chartHeight / maxValue;

            // Clear canvas
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.strokeStyle = '#e0e0e0';
            ctx.lineWidth = 1;
            for (let i = 0; i <= maxValue; i += Math.ceil(maxValue / 4)) {
                const y = canvas.height - 50 - (i * scale);
                ctx.beginPath();
                ctx.moveTo(60, y);
                ctx.lineTo(canvas.width - 20, y);
                ctx.stroke();
            }
            // Axes
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            ctx.moveTo(60, 20);
            ctx.lineTo(60, canvas.height - 50);
            ctx.lineTo(canvas.width - 20, canvas.height - 50);
            ctx.stroke();

            ctx.fillStyle = '#666';
            ctx.font = '13px "Segoe UI", Arial';
            ctx.textAlign = 'right';
            for (let i = 0; i <= maxValue; i += Math.ceil(maxValue / 4)) {
                const y = canvas.height - 50 - (i * scale);
                ctx.fillText(i, 50, y + 4);
            }

            const colors = ['#FF9D60', '#28a745', '#a855f7'];
            const labels = ['Pending', 'Confirmed', 'Completed'];
            const values = [pending, confirmed, completed];

            values.forEach((value, index) => {
                const x = 80 + (index * (chartWidth / 3)) + 15;
                const height = value * scale;
                const y = canvas.height - 50 - height;

                const gradient = ctx.createLinearGradient(x, y, x, canvas.height - 50);
                gradient.addColorStop(0, colors[index]);
                gradient.addColorStop(1, colors[index] + 'dd');

                ctx.fillStyle = gradient;
                ctx.fillRect(x, y, 70, height);

                ctx.strokeStyle = colors[index];
                ctx.lineWidth = 2;
                ctx.strokeRect(x, y, 70, height);

                ctx.fillStyle = '#333';
                ctx.font = 'bold 13px "Segoe UI", Arial';
                ctx.textAlign = 'center';
                ctx.fillText(labels[index], x + 35, canvas.height - 28);

                ctx.fillStyle = colors[index];
                ctx.font = 'bold 15px "Segoe UI", Arial';
                ctx.fillText(value, x + 35, y - 8);
            });

            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            values.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / 3)) + 50;
                const y = canvas.height - 40 - (value * scale);
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();

            ctx.fillStyle = '#333';
            values.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / 3)) + 50;
                const y = canvas.height - 40 - (value * scale);
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        function toggleStatGraph(graphId) {
            const defaultView = document.getElementById('statsDefaultView');
            const graphView = document.getElementById('statGraphView');
            const selectedStatCard = document.getElementById('selectedStatCard');
            const graphContainer = document.getElementById('graphContainer');

            if (graphId === 'appointments') {
                // Stat Card
                const totalAppointments = appointmentsData.length;
                const pending = appointmentsData.filter(a => a.status === 'pending').length;
                const confirmed = appointmentsData.filter(a => a.status === 'confirmed').length;
                const completed = appointmentsData.filter(a => a.status === 'completed').length;

                selectedStatCard.innerHTML = `
                    <i class="fas fa-calendar fa-3x" style="color: #b00020; margin-bottom: 15px;"></i>
                    <div class="stat-number">${totalAppointments}</div>
                    <div class="stat-label">Total Appointments</div>
                `;
                // Graph - Bar chart showing appointment statuses
                graphContainer.innerHTML = `
                    <h5 style="color: #b00020; margin-bottom: 20px; font-size: 16px; font-weight: 600;"><i class="fas fa-chart-bar"></i> Appointments Breakdown</h5>
                    <div style="background: white; padding: 0; border-radius: 8px;">
                        <canvas id="appointmentsCanvas" width="500" height="300" style="max-width: 100%; height: auto; display: block; background: #fafafa; border-radius: 6px;"></canvas>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 20px;">
                            <div style="text-align: center; padding: 15px; background: #fff5f5; border-radius: 6px; border-left: 4px solid #FF9D60;">
                                <div style="font-size: 24px; font-weight: bold; color: #FF9D60;">${pending}</div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">Pending</div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #f5fff5; border-radius: 6px; border-left: 4px solid #28a745;">
                                <div style="font-size: 24px; font-weight: bold; color: #28a745;">${confirmed}</div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">Confirmed</div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #fffbf0; border-radius: 6px; border-left: 4px solid #ffc107;">
                                <div style="font-size: 24px; font-weight: bold; color: #ffc107;">${completed}</div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">Completed</div>
                            </div>
                        </div>
                    </div>
                `;

                setTimeout(() => {
                    drawAppointmentsGraph(pending, confirmed, completed, totalAppointments);
                }, 100);
            } else if (graphId === 'records') {
                const totalRecords = recordsData.length;

                selectedStatCard.innerHTML = `
                    <i class="fas fa-file-medical fa-3x" style="color: #b00020; margin-bottom: 15px;"></i>
                    <div class="stat-number">${totalRecords}</div>
                    <div class="stat-label">Medical Records</div>
                `;

                graphContainer.innerHTML = `
                    <h5 style="color: #b00020; margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Records Overview</h5>
                    <div style="background: white; padding: 20px; border-radius: 8px;">
                        <canvas id="recordsCanvas" width="500" height="300" style="max-width: 100%; height: auto; border: 1px solid #e0e0e0; border-radius: 6px;"></canvas>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 15px; margin-top: 20px;">
                            <div style="text-align: center; padding: 15px; background: #f5f5f5; border-radius: 6px;">
                                <div style="font-size: 32px; font-weight: bold; color: #b00020;">${totalRecords}</div>
                                <div style="color: #666; font-size: 13px; margin-top: 5px;">Total Medical Records</div>
                            </div>
                        </div>
                    </div>
                `;

                setTimeout(() => {
                    drawRecordsGraph(totalRecords);
                }, 100);
            } else if (graphId === 'pending') {
                const totalAppointments = appointmentsData.length;
                const pendingCount = appointmentsData.filter(a => a.status === 'pending').length;

                selectedStatCard.innerHTML = `
                    <i class="fas fa-clock fa-3x" style="color: #b00020; margin-bottom: 15px;"></i>
                    <div class="stat-number">${pendingCount}</div>
                    <div class="stat-label">Pending Appointments</div>
                `;

                graphContainer.innerHTML = `
                    <h5 style="color: #b00020; margin-bottom: 20px;"><i class="fas fa-hourglass-end"></i> Pending Status</h5>
                    <div style="background: white; padding: 20px; border-radius: 8px;">
                        <canvas id="pendingCanvas" width="500" height="300" style="max-width: 100%; height: auto; border: 1px solid #e0e0e0; border-radius: 6px;"></canvas>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                            <div style="text-align: center; padding: 15px; background: #fff5f5; border-radius: 6px; border-left: 4px solid #b00020;">
                                <div style="font-size: 28px; font-weight: bold; color: #b00020;">${pendingCount}</div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">Awaiting Confirmation</div>
                            </div>
                            <div style="text-align: center; padding: 15px; background: #f5fff5; border-radius: 6px; border-left: 4px solid #28a745;">
                                <div style="font-size: 28px; font-weight: bold; color: #28a745;">${totalAppointments - pendingCount}</div>
                                <div style="color: #666; font-size: 12px; margin-top: 5px;">Resolved</div>
                            </div>
                        </div>
                    </div>
                `;

                setTimeout(() => {
                    drawPendingGraph(pendingCount, totalAppointments);
                }, 100);
            }

            currentStatIndex = stats.indexOf(graphId);
            updateStatIndicator();

            defaultView.style.display = 'none';
            graphView.style.display = 'block';
            graphView.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function drawRecordsGraph(totalRecords) {
            const canvas = document.getElementById('recordsCanvas');
            const ctx = canvas.getContext('2d');
            const chartWidth = canvas.width - 80;
            const chartHeight = canvas.height - 60;
            const barCount = 5;
            const barWidth = chartWidth / barCount;

            // Clear canvas
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Generate mock data for records trend
            const maxValue = Math.max(totalRecords * 1.2, 5);
            const scale = chartHeight / maxValue;
            const recordsData = [
                Math.floor(totalRecords * 0.3),
                Math.floor(totalRecords * 0.5),
                Math.floor(totalRecords * 0.8),
                totalRecords,
                Math.floor(totalRecords * 0.95)
            ];

            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(50, 20);
            ctx.lineTo(50, canvas.height - 40);
            ctx.lineTo(canvas.width - 20, canvas.height - 40);
            ctx.stroke();

            ctx.fillStyle = '#666';
            ctx.font = '12px Arial';
            ctx.textAlign = 'right';
            for (let i = 0; i <= maxValue; i += Math.ceil(maxValue / 4)) {
                const y = canvas.height - 40 - (i * scale);
                ctx.fillText(i, 45, y + 4);
            }

            const colors = ['#FF9D60', '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4'];
            recordsData.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / barCount)) + 10;
                const height = value * scale;
                const y = canvas.height - 40 - height;

                const gradient = ctx.createLinearGradient(x, y, x, canvas.height - 40);
                gradient.addColorStop(0, colors[index]);
                gradient.addColorStop(1, colors[index] + '99');

                ctx.fillStyle = gradient;
                ctx.fillRect(x, y, barWidth - 20, height);

                ctx.strokeStyle = '#333';
                ctx.lineWidth = 2;
                ctx.strokeRect(x, y, barWidth - 20, height);

                ctx.fillStyle = '#333';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(['Jan', 'Feb', 'Mar', 'Apr', 'May'][index], x + (barWidth - 20) / 2, canvas.height - 20);

                ctx.fillStyle = colors[index];
                ctx.font = 'bold 12px Arial';
                ctx.fillText(value, x + (barWidth - 20) / 2, y - 5);
            });

            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.beginPath();
            recordsData.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / barCount)) + 10 + (barWidth - 20) / 2;
                const y = canvas.height - 40 - (value * scale);
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();

            ctx.fillStyle = '#333';
            recordsData.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / barCount)) + 10 + (barWidth - 20) / 2;
                const y = canvas.height - 40 - (value * scale);
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        function drawPendingGraph(pendingCount, totalAppointments) {
            const canvas = document.getElementById('pendingCanvas');
            const ctx = canvas.getContext('2d');
            const chartWidth = canvas.width - 80;
            const chartHeight = canvas.height - 60;
            const barCount = 6;
            const barWidth = chartWidth / barCount;

            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            const maxValue = totalAppointments;
            const scale = chartHeight / maxValue;
            const pendingData = [
                Math.floor(pendingCount * 0.6),
                Math.floor(pendingCount * 0.8),
                pendingCount,
                Math.floor(pendingCount * 0.9),
                Math.floor(pendingCount * 0.7),
                Math.floor(pendingCount * 0.5)
            ];

            // Draw axes
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(50, 20);
            ctx.lineTo(50, canvas.height - 40);
            ctx.lineTo(canvas.width - 20, canvas.height - 40);
            ctx.stroke();

            // Y-axis labels
            ctx.fillStyle = '#666';
            ctx.font = '12px Arial';
            ctx.textAlign = 'right';
            for (let i = 0; i <= maxValue; i += Math.ceil(maxValue / 4)) {
                const y = canvas.height - 40 - (i * scale);
                ctx.fillText(i, 45, y + 4);
            }

            const colors = ['#b00020', '#d63447', '#e74c3c', '#f8a5a5', '#ff6b6b', '#c0392b'];
            pendingData.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / barCount)) + 5;
                const height = value * scale;
                const y = canvas.height - 40 - height;

                const gradient = ctx.createLinearGradient(x, y, x, canvas.height - 40);
                gradient.addColorStop(0, colors[index]);
                gradient.addColorStop(1, colors[index] + '99');

                ctx.fillStyle = gradient;
                ctx.fillRect(x, y, barWidth - 10, height);

                ctx.strokeStyle = '#333';
                ctx.lineWidth = 2;
                ctx.strokeRect(x, y, barWidth - 10, height);

                ctx.fillStyle = '#333';
                ctx.font = 'bold 11px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][index], x + (barWidth - 10) / 2, canvas.height - 20);

                ctx.fillStyle = colors[index];
                ctx.font = 'bold 12px Arial';
                ctx.fillText(value, x + (barWidth - 10) / 2, y - 5);
            });

            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.beginPath();
            pendingData.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / barCount)) + 5 + (barWidth - 10) / 2;
                const y = canvas.height - 40 - (value * scale);
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();

            ctx.fillStyle = '#333';
            pendingData.forEach((value, index) => {
                const x = 70 + (index * (chartWidth / barCount)) + 5 + (barWidth - 10) / 2;
                const y = canvas.height - 40 - (value * scale);
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        function closeStatGraph() {
            const defaultView = document.getElementById('statsDefaultView');
            const graphView = document.getElementById('statGraphView');
            
            defaultView.style.display = 'block';
            graphView.style.display = 'none';
        }

        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        let currentDoctorAvailability = {};
        let isAvailable = false;
        const allAppointmentTimes = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

        function filterAppointmentTimes(timeSelect, startTime, endTime) {
            const normalizeTime = (time) => time.includes(':') ? time.substring(0, 5) : time;
            const normalStart = normalizeTime(startTime);
            const normalEnd = normalizeTime(endTime);
            
            const availableTimes = allAppointmentTimes.filter(time => {
                return time >= normalStart && time < normalEnd;
            });

            const currentValue = timeSelect.value;
            timeSelect.innerHTML = '<option value="">-- Select Time --</option>';
            timeSelect.disabled = false;

            if (availableTimes.length === 0) {
                timeSelect.innerHTML = '<option value="">No available times</option>';
                timeSelect.disabled = true;
                return;
            }

            availableTimes.forEach(time => {
                const [hours, minutes] = time.split(':');
                const hour = parseInt(hours);
                const displayTime = hour >= 12 ? 
                    (hour === 12 ? '12:' + minutes + ' PM' : (hour - 12) + ':' + minutes + ' PM') :
                    (hour === 0 ? '12:' + minutes + ' AM' : hour + ':' + minutes + ' AM');

                const option = document.createElement('option');
                option.value = time;
                option.textContent = displayTime;
                timeSelect.appendChild(option);
            });

            if (currentValue && availableTimes.includes(currentValue)) {
                timeSelect.value = currentValue;
            } else {
                if (availableTimes.length > 0) {
                    const firstTime = availableTimes[0];
                    timeSelect.value = firstTime;
                    timeSelect.dispatchEvent(new Event('change'));
                }
            }
        }

        function loadDoctorAvailability() {
            const doctorSelect = document.getElementById('doctor_id');
            const doctorInfo = document.getElementById('doctor_info');
            const appointmentDate = document.getElementById('appointment_date');
            const scheduleDiv = document.getElementById('doctor_schedule');
            const scheduleContent = document.getElementById('schedule_content');
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            const bookBtn = document.getElementById('book_btn');
            const bookBtnHint = document.getElementById('book_btn_hint');
            
            if (doctorSelect.value) {
                const specialization = selectedOption.dataset.specialization;
                doctorInfo.textContent = '✓ Specialist in ' + specialization + ' | Available for consultations';
                doctorInfo.style.color = '#28a745';
                
                fetch('includes/check_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_schedule=1&doctor_id=' + doctorSelect.value
                })
                .then(response => response.json())
                .then(data => {
                    currentDoctorAvailability = data;
                    if (data && Object.keys(data).length > 0) {
                        let scheduleHTML = '<table class="table table-sm mb-0"><tbody>';
                        for (let dayNum = 0; dayNum < 7; dayNum++) {
                            const daySchedule = data[dayNum];
                            if (daySchedule && daySchedule.is_available) {
                                scheduleHTML += `<tr style="background: ${dayNum % 2 === 0 ? '#f9f9f9' : 'white'};">
                                    <td style="width: 40%;"><strong>${days[dayNum]}</strong></td>
                                    <td style="width: 60%; text-align: right; color: #dc3545;">
                                        <strong>${daySchedule.start_time_display} - ${daySchedule.end_time_display}</strong>
                                    </td>
                                </tr>`;
                            }
                        }
                        scheduleHTML += '</tbody></table>';
                        scheduleContent.innerHTML = scheduleHTML;
                        scheduleDiv.style.display = 'block';
                    } else {
                        scheduleDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    scheduleDiv.style.display = 'none';
                });
                
                appointmentDate.value = '';
                document.getElementById('availability_info').textContent = '';
                bookBtn.disabled = true;
                bookBtnHint.textContent = 'Select a date to check availability';
                isAvailable = false;
            } else {
                doctorInfo.textContent = '';
                scheduleDiv.style.display = 'none';
                bookBtn.disabled = true;
                bookBtnHint.textContent = '';
                isAvailable = false;
            }
        }

        function loadAvailableSlots() {
            const doctorId = document.getElementById('doctor_id').value;
            const appointmentDate = document.getElementById('appointment_date').value;
            const timeSelect = document.getElementById('appointment_time');
            const availabilityInfo = document.getElementById('availability_info');
            const bookBtn = document.getElementById('book_btn');
            const bookBtnHint = document.getElementById('book_btn_hint');
            
            if (!doctorId || !appointmentDate) {
                availabilityInfo.innerHTML = '';
                bookBtn.disabled = true;
                bookBtnHint.textContent = '';
                timeSelect.innerHTML = '<option value="">Select Date First</option>';
                timeSelect.disabled = true;
                isAvailable = false;
                return;
            }

            const date = new Date(appointmentDate);
            const dayOfWeek = date.getDay();
            const dayName = days[dayOfWeek];
            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            if (!currentDoctorAvailability || Object.keys(currentDoctorAvailability).length === 0) {
                fetch('includes/check_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_schedule=1&doctor_id=' + doctorId
                })
                .then(response => response.json())
                .then(data => {
                    currentDoctorAvailability = data;
                    loadAvailableSlots();
                })
                .catch(error => console.error('Error loading schedule:', error));
                return;
            }

            const daySchedule = currentDoctorAvailability[dayOfWeek];
            
            if (!daySchedule || !daySchedule.is_available) {
                availabilityInfo.innerHTML = `<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> <strong>${dayName}</strong>, ${formattedDate} - Doctor does not work on ${dayName}s</span>`;
                bookBtn.disabled = true;
                bookBtnHint.textContent = '⚠ Doctor is not available on this day of week';
                bookBtnHint.style.color = '#dc3545';
                isAvailable = false;
                timeSelect.innerHTML = '<option value="">Doctor Not Available</option>';
                timeSelect.disabled = true;
                return;
            }

            fetch('includes/check_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'doctor_id=' + doctorId + '&appointment_date=' + appointmentDate
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    availabilityInfo.innerHTML = `<span style="color: #28a745;"><i class="fas fa-check-circle"></i> <strong>${dayName}</strong>, ${formattedDate} - Doctor available</span>`;
                    bookBtn.disabled = false;
                    bookBtnHint.textContent = '✓ Doctor available! Select your preferred time.';
                    bookBtnHint.style.color = '#28a745';
                    isAvailable = true;
                    
                    if (data.start_time && data.end_time) {
                        filterAppointmentTimes(timeSelect, data.start_time, data.end_time);
                        timeSelect.disabled = false;
                    } else {
                        timeSelect.innerHTML = '<option value="08:00">8:00 AM</option>';
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '09:00', textContent: '9:00 AM'}));
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '10:00', textContent: '10:00 AM'}));
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '11:00', textContent: '11:00 AM'}));
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '13:00', textContent: '1:00 PM'}));
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '14:00', textContent: '2:00 PM'}));
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '15:00', textContent: '3:00 PM'}));
                        timeSelect.appendChild(Object.assign(document.createElement('option'), {value: '16:00', textContent: '4:00 PM'}));
                        timeSelect.disabled = false;
                    }
                } else {
                    availabilityInfo.innerHTML = `<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> <strong>${dayName}</strong>, ${formattedDate} - ${data.message}</span>`;
                    bookBtn.disabled = true;
                    bookBtnHint.textContent = '⚠ ' + data.message;
                    bookBtnHint.style.color = '#dc3545';
                    isAvailable = false;
                    timeSelect.innerHTML = '<option value="">No Slots Available</option>';
                    timeSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error fetching availability:', error);
                availabilityInfo.innerHTML = `<span style="color: #28a745;"><i class="fas fa-check-circle"></i> <strong>${dayName}</strong>, ${formattedDate}</span>`;
                bookBtn.disabled = false;
                bookBtnHint.textContent = '';
                isAvailable = true;
                timeSelect.disabled = false;
            });
        }

        if (document.getElementById('appointment_date')) {
            document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];
        }
        if (document.getElementById('consultation_date')) {
            document.getElementById('consultation_date').min = new Date().toISOString().split('T')[0];
        }

        let currentConsultationDoctorAvailability = {};

        function loadConsultationDoctorAvailability() {
            const doctorSelect = document.getElementById('consultation_doctor_id');
            const doctorInfo = document.getElementById('consultation_doctor_info');
            const consultationDate = document.getElementById('consultation_date');
            const scheduleDiv = document.getElementById('consultation_doctor_schedule');
            const scheduleContent = document.getElementById('consultation_schedule_content');
            const availableDoctorsSection = document.getElementById('available_doctors_section');
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            
            if (doctorSelect.value) {
                const specialization = selectedOption.dataset.specialization;
                doctorInfo.textContent = '✓ Specialist in ' + specialization + ' | Available for consultations';
                doctorInfo.style.color = '#28a745';
                availableDoctorsSection.style.display = 'none';
                
                fetch('includes/check_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_schedule=1&doctor_id=' + doctorSelect.value
                })
                .then(response => response.json())
                .then(data => {
                    currentConsultationDoctorAvailability = data;
                    if (data && Object.keys(data).length > 0) {
                        let scheduleHTML = '<table class="table table-sm mb-0"><tbody>';
                        for (let dayNum = 0; dayNum < 7; dayNum++) {
                            const daySchedule = data[dayNum];
                            if (daySchedule && daySchedule.is_available) {
                                scheduleHTML += `<tr style="background: ${dayNum % 2 === 0 ? '#f9f9f9' : 'white'};">
                                    <td style="width: 40%;"><strong>${days[dayNum]}</strong></td>
                                    <td style="width: 60%; text-align: right; color: #dc3545;">
                                        <strong>${daySchedule.start_time_display} - ${daySchedule.end_time_display}</strong>
                                    </td>
                                </tr>`;
                            }
                        }
                        scheduleHTML += '</tbody></table>';
                        scheduleContent.innerHTML = scheduleHTML;
                        scheduleDiv.style.display = 'block';
                    } else {
                        scheduleDiv.style.display = 'none';
                    }
                })
                .catch(error => {
                    scheduleDiv.style.display = 'none';
                });
                
                consultationDate.value = '';
                document.getElementById('consultation_availability_info').textContent = '';
            } else {
                doctorInfo.textContent = '';
                scheduleDiv.style.display = 'none';
                availableDoctorsSection.style.display = 'none';
                loadAvailableDoctors();
            }
        }

        function loadConsultationAvailableSlots() {
            const doctorId = document.getElementById('consultation_doctor_id').value;
            const consultationDate = document.getElementById('consultation_date').value;
            const timeSelect = document.getElementById('consultation_time');
            const availabilityInfo = document.getElementById('consultation_availability_info');
            
            if (!consultationDate) {
                availabilityInfo.innerHTML = '';
                timeSelect.innerHTML = '<option value="">Select Date First</option>';
                timeSelect.disabled = true;
                return;
            }

            const date = new Date(consultationDate);
            const dayOfWeek = date.getDay();
            const dayName = days[dayOfWeek];
            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            if (doctorId) {
                if (!currentConsultationDoctorAvailability || Object.keys(currentConsultationDoctorAvailability).length === 0) {
                    fetch('includes/check_availability.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'get_schedule=1&doctor_id=' + doctorId
                    })
                    .then(response => response.json())
                    .then(data => {
                        currentConsultationDoctorAvailability = data;
                        loadConsultationAvailableSlots();
                    })
                    .catch(error => console.error('Error loading schedule:', error));
                    return;
                }

                const daySchedule = currentConsultationDoctorAvailability[dayOfWeek];
                
                if (!daySchedule || !daySchedule.is_available) {
                    availabilityInfo.innerHTML = `<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> <strong>${dayName}</strong>, ${formattedDate} - Doctor does not work on ${dayName}s</span>`;
                    timeSelect.innerHTML = '<option value="">Doctor Not Available</option>';
                    timeSelect.disabled = true;
                    return;
                }

                fetch('includes/check_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'doctor_id=' + doctorId + '&appointment_date=' + consultationDate
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        availabilityInfo.innerHTML = `<span style="color: #28a745;"><i class="fas fa-check-circle"></i> <strong>${dayName}</strong>, ${formattedDate} - Doctor available</span>`;
                        resetTimeSelect();
                        timeSelect.disabled = false;
                    } else {
                        availabilityInfo.innerHTML = `<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> <strong>${dayName}</strong>, ${formattedDate} - ${data.message}</span>`;
                        timeSelect.innerHTML = '<option value="">Doctor Not Available</option>';
                        timeSelect.disabled = true;
                    }
                })
                .catch(error => {
                    availabilityInfo.innerHTML = `<span style="color: #28a745;"><i class="fas fa-check-circle"></i> <strong>${dayName}</strong>, ${formattedDate}</span>`;
                    resetTimeSelect();
                    timeSelect.disabled = false;
                });
            } else {
                // Any available doctor selected
                loadAvailableDoctors();
            }
        }

        function resetTimeSelect() {
            const timeSelect = document.getElementById('consultation_time');
            timeSelect.innerHTML = `
                <option value="">Select Time</option>
                <option value="08:00:00">08:00 AM</option>
                <option value="09:00:00">09:00 AM</option>
                <option value="10:00:00">10:00 AM</option>
                <option value="11:00:00">11:00 AM</option>
                <option value="13:00:00">01:00 PM</option>
                <option value="14:00:00">02:00 PM</option>
                <option value="15:00:00">03:00 PM</option>
                <option value="16:00:00">04:00 PM</option>
            `;
        }

        function loadAvailableDoctors() {
            const consultationDate = document.getElementById('consultation_date').value;
            const availableDoctorsSection = document.getElementById('available_doctors_section');
            const availableDoctorsList = document.getElementById('available_doctors_list');
            
            if (!consultationDate) {
                availableDoctorsSection.style.display = 'none';
                return;
            }

            const date = new Date(consultationDate);
            const dayOfWeek = date.getDay();
            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            // Get all doctors and check their availability
            const doctorElements = document.querySelectorAll('#consultation_doctor_id option');
            let availableDoctorsHTML = '';
            let availableCount = 0;

            doctorElements.forEach((option, index) => {
                if (option.value === '') return; // Skip "Any Available" option

                const doctorId = option.value;
                const doctorName = option.textContent;

                fetch('includes/check_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'get_schedule=1&doctor_id=' + doctorId
                })
                .then(response => response.json())
                .then(data => {
                    const daySchedule = data[dayOfWeek];
                    if (daySchedule && daySchedule.is_available) {
                        availableDoctorsHTML += `
                            <div style="padding: 10px; background: #f0f8ff; border-left: 4px solid #28a745; margin-bottom: 10px; border-radius: 4px;">
                                <strong>${doctorName}</strong><br>
                                <small style="color: #666;">Available: ${daySchedule.start_time_display} - ${daySchedule.end_time_display}</small>
                            </div>
                        `;
                        availableCount++;
                        availableDoctorsList.innerHTML = availableDoctorsHTML;
                        
                        if (availableCount === 1) {
                            availableDoctorsSection.style.display = 'block';
                        }
                    }
                })
                .catch(error => console.error('Error checking doctor availability:', error));
            });

            if (availableCount === 0) {
                setTimeout(() => {
                    if (availableDoctorsList.innerHTML === '') {
                        availableDoctorsList.innerHTML = '<p style="color: #dc3545;">No doctors available on this date.</p>';
                        availableDoctorsSection.style.display = 'block';
                    }
                }, 1000);
            }
        }
    </script>
</body>
</html>