<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/EmailSender.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();
$pre_selected_patient = $_GET['patient_id'] ?? null;

$patients = $conn->query("SELECT id, first_name, last_name, email FROM users WHERE role = 'patient' ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';
$success_patient_id = null;
$success_patient_name = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_record'])) {
    $patient_id = (int)$_POST['patient_id'];
    $followup_required = isset($_POST['followup_required']) ? 1 : 0;
    $followup_date = !empty($_POST['followup_date']) ? $_POST['followup_date'] : null;
    $followup_time = !empty($_POST['followup_time']) ? $_POST['followup_time'] : null;
    
    $result = MedicalRecord::addRecord(
        $patient_id,
        !empty($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null,
        $_POST['diagnosis'],
        $_POST['symptoms'],
        $_POST['treatment'],
        $_POST['prescriptions'],
        $_POST['notes'],
        [],
        $followup_required,
        $followup_date,
        $followup_time
    );
    
    if ($result['success']) {
        $success = $result['message'];
        $success_patient_id = $patient_id;
        $record_id = $result['record_id'];
        
        // Get patient name and email for the modal and email
        $stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $success_patient_name = $patient['first_name'] . ' ' . $patient['last_name'];
        
        // Send email notification to patient
        try {
            $emailSender = new EmailSender();
            $recordDetails = "Diagnosis: " . $_POST['diagnosis'] . "\n"
                           . "Symptoms: " . $_POST['symptoms'] . "\n"
                           . "Treatment: " . $_POST['treatment'] . "\n"
                           . "Prescriptions: " . $_POST['prescriptions'] . "\n"
                           . "Notes: " . $_POST['notes'];
            
            // Add follow-up details if applicable
            if ($followup_required && $followup_date) {
                $recordDetails .= "\n\nFollow-up Checkup Required: " . date('M d, Y', strtotime($followup_date));
                if ($followup_time) {
                    $recordDetails .= " at " . date('h:i A', strtotime($followup_time));
                }
            }
            
            $emailSender->sendMedicalRecordNotification(
                $patient['email'],
                $success_patient_name,
                $user['first_name'] . ' ' . $user['last_name'],
                $recordDetails,
                $record_id
            );
        } catch (Exception $e) {
            // Log error but don't show to user - record was created successfully
            error_log("Medical record email failed: " . $e->getMessage());
        }
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Medical Record - BHC System</title>
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
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-file-medical-alt"></i> Create Medical Record</h2>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">-- Select Patient --</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['id']; ?>" <?php echo ($pre_selected_patient == $patient['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?> (<?php echo htmlspecialchars($patient['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="appointment_id" class="form-label">Appointment (Optional)</label>
                        <input type="number" class="form-control" id="appointment_id" name="appointment_id" placeholder="Enter appointment ID if related">
                    </div>

                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <input type="text" class="form-control" id="diagnosis" name="diagnosis" placeholder="e.g., Hypertension, Common Cold" required>
                    </div>

                    <div class="mb-3">
                        <label for="symptoms" class="form-label">Symptoms</label>
                        <textarea class="form-control" id="symptoms" name="symptoms" rows="3" placeholder="Describe the symptoms..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="treatment" class="form-label">Treatment</label>
                        <textarea class="form-control" id="treatment" name="treatment" rows="3" placeholder="Describe the treatment provided..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="prescriptions" class="form-label">Prescriptions</label>
                        <textarea class="form-control" id="prescriptions" name="prescriptions" rows="3" placeholder="Enter prescriptions, one per line (optional)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-calendar-check"></i> Follow-up Checkup</h5>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="followup_required" name="followup_required" onchange="toggleFollowupFields()">
                            <label class="form-check-label" for="followup_required">
                                Schedule a follow-up checkup for this patient
                            </label>
                        </div>
                    </div>

                    <div id="followupFields" style="display: none;" class="ps-3 border-start border-3" style="border-color: #dc3545;">
                        <div class="mb-3">
                            <label for="followup_date" class="form-label">Recommended Follow-up Date</label>
                            <input type="date" class="form-control" id="followup_date" name="followup_date">
                            <small class="text-muted">Patient will be notified of this scheduled checkup</small>
                        </div>

                        <div class="mb-3">
                            <label for="followup_time" class="form-label">Recommended Follow-up Time (Optional)</label>
                            <input type="time" class="form-control" id="followup_time" name="followup_time">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="create_record" class="btn btn-primary"><i class="fas fa-save"></i> Create Record</button>
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleFollowupFields() {
            const checkbox = document.getElementById('followup_required');
            const followupFields = document.getElementById('followupFields');
            const followupDate = document.getElementById('followup_date');
            
            if (checkbox.checked) {
                followupFields.style.display = 'block';
            } else {
                followupFields.style.display = 'none';
                followupDate.value = '';
                document.getElementById('followup_time').value = '';
            }
        }
    </script>
    
    <?php if ($success_patient_id): ?>
    <script>
        // Show success modal automatically
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title"><i class="fas fa-check-circle"></i> Record Created Successfully!</h5>
            </div>
            <div class="modal-body text-center py-4">
                <div style="font-size: 48px; color: #28a745; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h6 class="mb-3">Medical record has been created for:</h6>
                <p style="font-size: 18px; font-weight: 600; color: #333; margin-bottom: 25px;">
                    <?php echo htmlspecialchars($success_patient_name); ?>
                </p>
                <p style="color: #666; font-size: 14px;">What would you like to do next?</p>
            </div>
            <div class="modal-footer border-0 gap-2">
                <a href="../patients/view_patient_records.php?id=<?php echo $success_patient_id; ?>" class="btn btn-success flex-grow-1">
                    <i class="fas fa-file-medical"></i> View Medical Records
                </a>
                <button type="button" class="btn btn-primary flex-grow-1" onclick="window.location.href='create_record.php';">
                    <i class="fas fa-plus-circle"></i> Create Another Record
                </button>
            </div>
        </div>
    </div>
</div>
