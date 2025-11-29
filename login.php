<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (Auth::isLoggedIn()) {
    if (Auth::getRole() === 'admin' || Auth::getRole() === 'doctor') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';
$login_type = $_GET['type'] ?? 'patient';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $type = $_POST['login_type'] ?? 'patient';

    if (empty($username) || empty($password)) {
        $error = 'Email/username and password are required';
    } else {
        $result = Auth::login($username, $password);

        if ($result['success']) {
            // Check if role matches the login type
            $user_role = $result['role'] ?? $_SESSION['role'] ?? '';

            if ($type === 'admin' && $user_role !== 'admin') {
                session_destroy();
                $error = 'This account is not authorized for staff access';
            } elseif ($type === 'patient' && $user_role !== 'patient') {
                session_destroy();
                $error = 'This account is not authorized for patient access';
            } else {
                if ($user_role === 'admin') {
                    header('Location: admin/dashboard.php');
                    exit();
                } else {
                    // Redirect patients to homepage
                    header('Location: index.php');
                    exit();
                }
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
    <title>Sign In - CLINICare | Barangay Health Center</title>
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="fontawesome-free-7.1.0-web/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #dc3545;
        }

        .login-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: white;
        }

        .login-header p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(220, 53, 69, 0.1);
        }

        .role-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 30px;
        }

        .role-tab {
            padding: 14px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .role-tab:hover {
            border-color: #dc3545;
            color: #dc3545;
        }

        .role-tab.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-color: #dc3545;
        }

        .role-tab i {
            font-size: 18px;
        }

        .login-section-title {
            text-align: left;
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-input:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            background: white;
        }

        .form-input::placeholder {
            color: #aaa;
        }

        .login-button {
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

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
        }

        .login-footer-text {
            font-size: 14px;
            color: #666;
        }

        .login-footer-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-footer-link:hover {
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

        .policy-text {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }

        .policy-text a {
            color: #dc3545;
            text-decoration: none;
        }

        .policy-text a:hover {
            text-decoration: underline;
        }

        .hidden-input {
            display: none;
        }

        .staff-only {
            display: none;
        }

        .staff-only.show {
            display: block;
        }

        .patient-only {
            display: block;
        }

        .patient-only.hide {
            display: none;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .role-tabs {
                gap: 8px;
            }

            .role-tab {
                padding: 12px;
                font-size: 13px;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-clinic-medical"></i>
            </div>
            <h1>CLINICare</h1>
            <p>Barangay Health Center Appointment and Record System</p>
        </div>

        <div class="login-card">
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Access your health center account</p>

            <!-- Role Tabs -->
            <div class="role-tabs">
                <button type="button" class="role-tab <?php echo $login_type === 'patient' ? 'active' : ''; ?>" onclick="switchRole('patient')">
                    <i class="fas fa-user"></i> Patient
                </button>
                <button type="button" class="role-tab <?php echo $login_type === 'admin' ? 'active' : ''; ?>" onclick="switchRole('admin')">
                    <i class="fas fa-user-tie"></i> Staff
                </button>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST">
                <input type="hidden" name="login_type" id="login_type" value="<?php echo $login_type; ?>">

                <div class="form-group">
                    <label for="username" class="form-label">Email or Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        placeholder="Enter your email or username"
                        required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Enter your password"
                        required>
                </div>

                <button type="submit" class="login-button">Sign In</button>
            </form>
            <p class="login-footer-text" style="margin-top: 10px;">
                <a href="forgot_password.php" class="login-footer-link"><i class="fas fa-key"></i> Forgot your password?</a>
            </p>

            <!-- Patient-only Links -->
            <div class="login-footer patient-only <?php echo $login_type === 'admin' ? 'hide' : ''; ?>">
                <p class="login-footer-text">
                    Don't have an account? <a href="register.php" class="login-footer-link">Register here</a>
                </p>
            </div>

            <div class="policy-text patient-only <?php echo $login_type === 'admin' ? 'hide' : ''; ?>">
                By signing in, you agree to our <a href="privacy_policy.php">Privacy Policy</a>
            </div>

            <!-- Back to Homepage Button -->
            <div style="margin-top: 20px;">
                <a href="index.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>

    <script>
        function switchRole(role) {
            document.getElementById('login_type').value = role;

            // Update tab styling
            document.querySelectorAll('.role-tab').forEach(tab => tab.classList.remove('active'));
            event.target.closest('.role-tab').classList.add('active');

            // Get patient-only elements
            const patientOnlyElements = document.querySelectorAll('.patient-only');

            if (role === 'admin') {
                // Staff login - hide patient-only elements
                patientOnlyElements.forEach(el => el.classList.add('hide'));
            } else {
                // Patient login - show patient-only elements
                patientOnlyElements.forEach(el => el.classList.remove('hide'));
            }
        }
    </script>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>