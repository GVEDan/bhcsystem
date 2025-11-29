<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

Auth::requireAdmin();

$error = '';
$success = '';
$doctors = Doctor::getAllDoctors();

// Handle URL parameter actions (approve/reject from dashboard quick-actions)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $request_id = (int)$_GET['id'];
    $admin_notes = isset($_GET['notes']) ? $_GET['notes'] : '';
    
    $status_map = [
        'approve' => 'approved',
        'reject' => 'rejected'
    ];
    
    if (array_key_exists($action, $status_map)) {
        if (ConsultationRequest::updateStatus($request_id, $status_map[$action], $admin_notes)) {
            $success = 'Request ' . $action . 'ed successfully';
        } else {
            $error = 'Failed to ' . $action . ' request';
        }
    }
}

// Handle status update from form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    $doctor_id = (isset($_POST['doctor_id']) && !empty($_POST['doctor_id'])) ? (int)$_POST['doctor_id'] : null;
    $preferred_date = (isset($_POST['preferred_date']) && !empty($_POST['preferred_date'])) ? $_POST['preferred_date'] : null;
    
    // If rejecting, add rejection reason to admin_notes if provided
    if ($status === 'rejected' && !empty($rejection_reason)) {
        $admin_notes = 'Rejection Reason: ' . $rejection_reason . ($admin_notes ? '\n\nAdditional Notes: ' . $admin_notes : '');
    }
    
    if (ConsultationRequest::updateStatus($request_id, $status, $admin_notes, $doctor_id, $preferred_date)) {
        $success = 'Request updated successfully';
    } else {
        $error = 'Failed to update request';
    }
}

// Get all consultation requests
$consultation_requests = ConsultationRequest::getAllRequests();
$stats = ConsultationRequest::getStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Consultation Requests - CLINICare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
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
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
            color: white !important;
        }
        .logout-link {
            background: transparent !important;
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 8px 12px !important;
            margin: 0 !important;
            border: none !important;
            transition: all 0.3s ease;
        }
        .logout-link:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            transform: translateY(-2px);
        }
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .nav-link {
            color: #555;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
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
        .badge-completed {
            background-color: #17a2b8;
        }
        .stat-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #dc3545;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        .btn-group .btn {
            font-size: 12px;
            padding: 4px 8px;
        }
        .btn-group .btn-sm {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-heartbeat"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_consultation_requests.php">Consultation Requests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="sidebar">
                    <div style="margin-bottom: 20px;">
                        <strong style="color: #dc3545;">MENU</strong>
                    </div>
                    <a href="../dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="manage_medicine_requests.php" class="nav-link"><i class="fas fa-pills"></i> Medicine Requests</a>
                    <a href="manage_consultation_requests.php" class="nav-link active"><i class="fas fa-stethoscope"></i> Consultation Requests</a>
                    <a href="../settings/reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports & Export</a>
                    <a href="../settings/manage_doctor_availability.php" class="nav-link"><i class="fas fa-calendar"></i> Doctor Availability</a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
        <div class="row">
            <div class="col-lg-12">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="h2 mb-2"><i class="fas fa-stethoscope"></i> Consultation Requests</h1>
                    <p class="text-muted">Manage patient consultation requests</p>
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

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label">Pending Requests</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['approved']; ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                            <div class="stat-label">Rejected</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo count($consultation_requests); ?></div>
                            <div class="stat-label">Total Requests</div>
                        </div>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> All Consultation Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($consultation_requests)): ?>
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle"></i> No consultation requests yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Contact</th>
                                            <th>Consultation Type</th>
                                            <th>Assigned Doctor</th>
                                            <th>Preferred Date</th>
                                            <th>Status</th>
                                            <th>Requested Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($consultation_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($request['consultation_type']); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($request['doctor_first_name'] !== 'Not Assigned') {
                                                        echo htmlspecialchars($request['doctor_first_name'] . ' ' . $request['doctor_last_name']);
                                                    } else {
                                                        echo '<span class="text-muted">Not Assigned</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php echo (!empty($request['preferred_date']) ? date('M d, Y', strtotime($request['preferred_date'])) : '-'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo strtolower($request['status']); ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($request['requested_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <a href="?action=approve&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success" title="Approve">
                                                                <i class="fas fa-check"></i> Approve
                                                            </a>
                                                            <a href="?action=reject&id=<?php echo $request['id']; ?>" class="btn btn-sm btn-danger" title="Reject">
                                                                <i class="fas fa-times"></i> Reject
                                                            </a>
                                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Update Modal -->
                                            <div class="modal fade" id="updateModal<?php echo $request['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Consultation Request</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Patient:</strong></label>
                                                                    <p><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Consultation Type:</strong></label>
                                                                    <p><?php echo htmlspecialchars($request['consultation_type']); ?></p>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label"><strong>Description:</strong></label>
                                                                    <p><?php echo htmlspecialchars($request['description']); ?></p>
                                                                </div>
                                                                <hr>
                                                                <div class="mb-3">
                                                                    <label for="doctor<?php echo $request['id']; ?>" class="form-label">Assign Doctor</label>
                                                                    <select class="form-select" id="doctor<?php echo $request['id']; ?>" name="doctor_id">
                                                                        <option value="">-- Any Available Doctor --</option>
                                                                        <?php foreach ($doctors as $doctor): ?>
                                                                            <option value="<?php echo $doctor['id']; ?>" <?php echo ($request['patient_id'] !== null && $doctor['id'] === $request['patient_id']) ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status<?php echo $request['id']; ?>" class="form-label">Status</label>
                                                                    <select class="form-select" id="status<?php echo $request['id']; ?>" name="status" required>
                                                                        <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="approved" <?php echo $request['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                        <option value="rejected" <?php echo $request['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                        <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="preferred_date<?php echo $request['id']; ?>" class="form-label">Preferred Consultation Date</label>
                                                                    <input type="date" class="form-control" id="preferred_date<?php echo $request['id']; ?>" name="preferred_date" value="<?php echo htmlspecialchars($request['preferred_date'] ?? ''); ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="rejection_reason<?php echo $request['id']; ?>" class="form-label">Rejection Reason (if rejecting)</label>
                                                                    <textarea class="form-control" id="rejection_reason<?php echo $request['id']; ?>" name="rejection_reason" rows="2" placeholder="Explain why this request is being rejected"><?php echo htmlspecialchars($request['rejection_reason'] ?? ''); ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="notes<?php echo $request['id']; ?>" class="form-label">Admin Notes</label>
                                                                    <textarea class="form-control" id="notes<?php echo $request['id']; ?>" name="admin_notes" rows="3" placeholder="Add notes for the patient"><?php echo htmlspecialchars($request['admin_notes']); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                                <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
