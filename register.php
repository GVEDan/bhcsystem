<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/email_config.php';
require_once 'includes/EmailSender.php';

if (Auth::isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $terms = $_POST['terms'] ?? false;

    // Parse full name into first and last
    $name_parts = explode(' ', trim($full_name), 2);
    $first_name = $name_parts[0];
    $last_name = $name_parts[1] ?? '';

    // Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (!$terms) {
        $error = 'You must agree to the terms and conditions';
    } else {
        $result = Auth::register($username, $email, $password, $first_name, $last_name, $contact_number);
        if ($result['success']) {
            $success = 'Account created successfully! You can now log in.';
            
            // Send registration confirmation email with password
            try {
                $emailSender = new EmailSender();
                $emailSender->sendRegistrationConfirmation($email, $full_name, $username, $password);
            } catch (Exception $e) {
                // Log error but don't show to user
                error_log("Registration email failed: " . $e->getMessage());
            }
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
    <title>Register - CLINICare</title>
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="fontawesome-free-7.1.0-web/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #fde8eb 0%, #fad5dc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 20px;
        }

        .register-wrapper {
            width: 100%;
            max-width: 700px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .back-btn {
            align-self: center;
            margin-bottom: 30px;
            padding: 10px 20px;
            border: 2px solid #999;
            background: white;
            border-radius: 8px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .back-btn:hover {
            border-color: #dc3545;
            color: #dc3545;
        }

        .register-card {
            background: white;
            border-radius: 12px;
            padding: 50px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(220, 53, 69, 0.1);
            width: 100%;
        }

        .register-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
            text-align: center;
        }

        .register-subtitle {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .form-label .required {
            color: #dc3545;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            background: white;
        }

        .form-input::placeholder {
            color: #aaa;
        }

        .form-input[type="date"] {
            position: relative;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 25px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #dc3545;
        }

        .checkbox-group label {
            font-size: 13px;
            color: #666;
            margin: 0;
            cursor: pointer;
            flex: 1;
        }

        .checkbox-group a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
        }

        .checkbox-group a:hover {
            text-decoration: underline;
        }

        .register-button {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .register-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
        }

        .register-footer-text {
            font-size: 14px;
            color: #666;
        }

        .register-footer-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-footer-link:hover {
            color: #c82333;
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
            padding: 14px 15px;
            font-size: 14px;
        }

        .alert-danger {
            background: #ffe6e6;
            color: #c82333;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: #e6f4e6;
            color: #2d5a2d;
            border-left: 4px solid #28a745;
        }

        /* Success Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-box .check-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .modal-box h2 {
            font-size: 24px;
            color: #1a1a1a;
            margin: 15px 0;
            font-weight: 700;
        }

        .modal-box p {
            color: #666;
            font-size: 14px;
            margin: 10px 0 25px 0;
            line-height: 1.6;
        }

        .modal-box a {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .modal-box a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        }

        @media (max-width: 576px) {
            .register-card {
                padding: 30px 20px;
            }

            .register-title {
                font-size: 22px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <a href="login.php" class="back-btn">← Back to Login</a>

        <div class="register-card">
            <h1 class="register-title">CLINICare Registration</h1>
            <p class="register-subtitle">Create your account at the Barangay Health Center</p>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Success Modal -->
            <div class="modal-overlay <?php echo !empty($success) ? 'show' : ''; ?>" id="successModal">
                <div class="modal-box">
                    <div class="check-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2>Account Created Successfully!</h2>
                    <p><?php echo htmlspecialchars($success); ?></p>
                    <a href="login.php">Login to CLINICare</a>
                </div>
            </div>

            <!-- Register Form -->
            <form method="POST">
                <!-- Full Name -->
                <div class="form-group form-full">
                    <label for="full_name" class="form-label">Full Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-input" 
                        placeholder="Enter your full name"
                        required
                    >
                </div>

                <!-- Username -->
                <div class="form-group form-full">
                    <label for="username" class="form-label">Username <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Choose a unique username (e.g., john.doe)"
                        required
                    >
                </div>

                <!-- Email & Contact -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="form-label">Email <span class="required">*</span></label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="your@email.com"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="contact_number" class="form-label">Contact Number <span class="required">*</span></label>
                        <input 
                            type="tel" 
                            id="contact_number" 
                            name="contact_number" 
                            class="form-input" 
                            placeholder="+63 9XX XXX XXXX"
                        >
                    </div>
                </div>

                <!-- Password & Confirm -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Min 6 characters"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Confirm password"
                            required
                        >
                    </div>
                </div>

                <!-- Date of Birth & Gender -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth" class="form-label">Date of Birth <span class="required">*</span></label>
                        <input 
                            type="date" 
                            id="date_of_birth" 
                            name="date_of_birth" 
                            class="form-input"
                            placeholder="mm/dd/yyyy"
                        >
                    </div>
                    <div class="form-group">
                        <label for="gender" class="form-label">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group form-full">
                    <label for="address" class="form-label">Address</label>
                    <input 
                        type="text" 
                        id="address" 
                        name="address" 
                        class="form-input" 
                        placeholder="Enter your address"
                    >
                </div>

                <!-- Terms & Conditions -->
                <div class="checkbox-group">
                    <input 
                        type="checkbox" 
                        id="terms" 
                        name="terms" 
                        value="1"
                        required
                    >
                    <label for="terms">
                        I agree to the <a href="#">terms and conditions</a> and <a href="#">privacy policy</a>
                    </label>
                </div>

                <button type="submit" class="register-button">Create Account</button>
            </form>

            <!-- Footer Links -->
            <div class="register-footer">
                <p class="register-footer-text">
                    Already have an account? <a href="login.php" class="register-footer-link">Sign in here</a>
                </p>
            </div>
        </div>
    </div>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>