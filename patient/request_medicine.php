<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requirePatient();

$user = Auth::getCurrentUser();
$error = '';
$success = '';

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
    } else {
        $error = $result['message'];
    }
}

// Get patient's medicine requests
$medicine_requests = MedicineRequest::getPatientRequests($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Medicine - CLINICare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #dc3545;
            --secondary: #c82333;
        }
        body {
            background: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #b01d2b 100%);
        }
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }
        .badge-approved {
            background-color: #28a745;
        }
        .badge-rejected {
            background-color: #dc3545;
        }
        .form-label {
            font-weight: 600;
            color: #333;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-clinic-medical"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="request_medicine.php">Request Medicine</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_consultation.php">Request Consultation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="h2 mb-2"><i class="fas fa-pills"></i> Request Medicine</h1>
                    <p class="text-muted">Submit a medicine request to the health center</p>
                </div>

                <!-- Error/Success Messages -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Request Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> New Medicine Request</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label for="medicine_name" class="form-label">Medicine Name</label>
                                <input type="text" class="form-control" id="medicine_name" name="medicine_name" required placeholder="Enter medicine name">
                            </div>
                            <div class="col-md-6">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required placeholder="Enter quantity">
                            </div>
                            <div class="col-12">
                                <label for="reason" class="form-label">Reason for Request</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Explain why you need this medicine"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="submit_medicine_request" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Request History -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Your Medicine Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medicine_requests)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> You haven't submitted any medicine requests yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Medicine</th>
                                            <th>Quantity</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Requested Date</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medicine_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['medicine_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : ''); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo strtolower($request['status']); ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                                <td>
                                                    <?php if (!empty($request['admin_notes'])): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['admin_notes']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
