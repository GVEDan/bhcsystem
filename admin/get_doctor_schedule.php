<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

header('Content-Type: application/json');

// Handle get schedule AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_schedule'])) {
    $doctor_id = (int)$_POST['doctor_id'] ?? 0;
    
    if ($doctor_id) {
        $schedule = Doctor::getDoctorAvailability($doctor_id);
        $result = [];
        
        // Initialize all 7 days with default values
        for ($i = 0; $i <= 6; $i++) {
            $result[$i] = [
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_available' => false
            ];
        }
        
        // Update with actual schedule data
        foreach ($schedule as $day_schedule) {
            $day = (int)$day_schedule['day_of_week'];
            $result[$day] = [
                'start_time' => substr($day_schedule['start_time'], 0, 5),
                'end_time' => substr($day_schedule['end_time'], 0, 5),
                'is_available' => (bool)$day_schedule['is_available']
            ];
        }
        
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Invalid doctor ID']);
    }
    exit;
}

// Handle save weekly schedule AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_weekly_schedule'])) {
    $doctor_id = (int)$_POST['doctor_id'] ?? 0;
    $success_count = 0;
    $error_count = 0;
    
    if ($doctor_id) {
        for ($day = 0; $day <= 6; $day++) {
            $start_time = $_POST['start_time'][$day] ?? '';
            $end_time = $_POST['end_time'][$day] ?? '';
            $is_available = isset($_POST['available'][$day]) ? 1 : 0;
            
            if ($start_time && $end_time) {
                $sql = "INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) 
                        VALUES (?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE start_time = ?, end_time = ?, is_available = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('iisssssi', $doctor_id, $day, $start_time, $end_time, $is_available, $start_time, $end_time, $is_available);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            echo json_encode(['success' => true, 'message' => "Schedule updated successfully! ($success_count days saved)"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving schedule']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
?>
