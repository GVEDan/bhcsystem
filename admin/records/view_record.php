<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();
$record_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get the record
$record = MedicalRecord::getRecord($record_id);

if (!$record) {
    header('Location: ../dashboard.php');
    exit();
}

// Get patient information
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $record['patient_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_record'])) {
    $result_update = MedicalRecord::updateRecord(
        $record_id,
        $_POST['diagnosis'],
        $_POST['symptoms'],
        $_POST['treatment'],
        $_POST['prescriptions'],
        $_POST['notes']
    );
    
    if ($result_update) {
        $success = 'Medical record updated successfully';
        $record = MedicalRecord::getRecord($record_id);
    } else {
        $error = 'Failed to update medical record: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical Record - BHC System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .patient-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .patient-info h4 {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .info-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            border-left: 3px solid #dc3545;
            padding-left: 12px;
        }
        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
        }
        .info-value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            margin-top: 3px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        .record-info {
            background: #f9f9f9;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .record-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </nav>

    <div class="container mt-4 mb-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-edit"></i> Edit Medical Record</h2>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Patient Information -->
        <div class="patient-info">
            <h4><i class="fas fa-user-circle"></i> Patient Information</h4>
            <div class="info-row">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Record ID</div>
                    <div class="info-value"><?php echo $record_id; ?></div>
                </div>
            </div>
        </div>

        <!-- Record Information -->
        <div class="record-info">
            <p><strong>Record Date:</strong> <?php echo date('M d, Y g:i A', strtotime($record['record_date'])); ?></p>
            <?php if ($record['appointment_id']): ?>
                <p><strong>Related Appointment ID:</strong> <?php echo (int)$record['appointment_id']; ?></p>
            <?php endif; ?>
            <?php if ($record['followup_required']): ?>
                <p><strong style="color: #dc3545;"><i class="fas fa-calendar-check"></i> Follow-up Required:</strong> <?php echo date('l, F d, Y', strtotime($record['followup_date'])); ?> at <?php echo date('g:i A', strtotime($record['followup_time'])); ?></p>
            <?php endif; ?>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-medical-alt"></i> Medical Record Details
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="diagnosis">
                            <i class="fas fa-diagnoses"></i> Diagnosis
                        </label>
                        <input type="text" class="form-control" id="diagnosis" name="diagnosis" placeholder="e.g., Hypertension, Common Cold" value="<?php echo htmlspecialchars($record['diagnosis'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="symptoms">
                            <i class="fas fa-symptoms"></i> Symptoms
                        </label>
                        <textarea class="form-control" id="symptoms" name="symptoms" placeholder="Describe the symptoms..." required><?php echo htmlspecialchars($record['symptoms'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="treatment">
                            <i class="fas fa-prescription-bottle"></i> Treatment
                        </label>
                        <textarea class="form-control" id="treatment" name="treatment" placeholder="Describe the treatment provided..." required><?php echo htmlspecialchars($record['treatment'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="prescriptions">
                            <i class="fas fa-pills"></i> Prescriptions
                        </label>
                        <textarea class="form-control" id="prescriptions" name="prescriptions" placeholder="List medications and dosages..." required><?php echo htmlspecialchars($record['prescriptions'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes">
                            <i class="fas fa-note-medical"></i> Additional Notes
                        </label>
                        <textarea class="form-control" id="notes" name="notes" placeholder="Any additional notes or observations..." required><?php echo htmlspecialchars($record['notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="update_record" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Record
                        </button>
                        <a href="../patients/view_patient_records.php?id=<?php echo $record['patient_id']; ?>" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Patient Records
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
