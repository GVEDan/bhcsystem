<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

Auth::requirePatient();

$user = Auth::getCurrentUser();
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    global $conn;
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'First name, last name, and email are required';
    } else {
        // Check if email is already used by another user
        $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param('si', $email, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email is already in use by another account';
        } else {
            // Update user profile
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssi', $first_name, $last_name, $email, $phone, $user['id']);
            
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                
                // Update user array
                $user = Auth::getCurrentUser();
                $success = 'Profile updated successfully';
            } else {
                $error = 'Failed to update profile: ' . $conn->error;
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    global $conn;
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Get current password hash
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    // Validate current password
    if (!password_verify($current_password, $user_data['password'])) {
        $error = 'Current password is incorrect';
    } else if (empty($new_password) || empty($confirm_password)) {
        $error = 'New password and confirmation are required';
    } else if ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else if (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('si', $hashed_password, $user['id']);
        
        if ($update_stmt->execute()) {
            $success = 'Password changed successfully';
        } else {
            $error = 'Failed to change password: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CLINICare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fa;
            color: #333;
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

        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white !important;
        }

        .logout-link {
            background: transparent !important;
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 8px 12px !important;
            margin: 0 !important;
            border: none !important;
            transition: all 0.3s ease;
            display: inline-block !important;
        }

        .logout-link:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            transform: translateY(-2px);
        }

        .logout-link i {
            margin-right: 6px;
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .logout-link:hover i {
            color: white !important;
        }

        .navbar-nav .logout-link {
            background: transparent !important;
            color: rgba(255, 255, 255, 0.9) !important;
            border: none;
        }

        .navbar-nav .logout-link i {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .navbar-nav .logout-link:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }

        .navbar-nav .logout-link:hover i {
            color: white !important;
        }
        .container-main {
            max-width: 1000px;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 50px 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            text-align: center;
        }

        .profile-header h1 {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 42px;
        }

        .profile-header p {
            color: #666;
            margin: 0;
            font-size: 16px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
            padding: 25px;
            font-weight: 600;
            font-size: 18px;
        }

        .card-header i {
            margin-right: 10px;
        }

        .card-body {
            padding: 35px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 18px;
            font-size: 15px;
            transition: all 0.3s ease;
            color: #333;
        }

        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.3rem rgba(220, 53, 69, 0.15);
            color: #333;
        }

        .form-control:disabled {
            background-color: #f0f0f0;
            color: #666;
        }

        .form-control::placeholder {
            color: #999;
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 12px 35px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-secondary {
            padding: 12px 35px;
            font-weight: 600;
            border-radius: 10px;
            background: #e0e0e0;
            color: #333;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 25px;
            padding: 16px 20px;
            font-weight: 500;
            color: #333;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .nav-links {
            margin-bottom: 20px;
        }

        .nav-links a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-row p {
            color: #333;
            font-weight: 500;
        }

        .form-group small {
            color: #666;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .profile-header h1 {
                font-size: 32px;
            }
            .container-main {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-heartbeat"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Homepage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="medical_records.php"><i class="fas fa-file-medical"></i> Medical Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <!-- Profile Header -->
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Manage your account information and security settings</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Information Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user"></i> Profile Information
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <small class="text-muted">Username cannot be changed</small>
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i> Change Password
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted">At least 6 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
