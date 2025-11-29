<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if requesting schedule
    if (isset($_POST['get_schedule'])) {
        $doctor_id = (int)$_POST['doctor_id'] ?? 0;
        
        if ($doctor_id) {
            // Get doctor's availability schedule for the week
            $sql = "SELECT day_of_week, start_time, end_time, is_available FROM doctor_availability 
                    WHERE doctor_id = ? AND is_available = 1 
                    ORDER BY day_of_week ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $schedule = [];
            while ($row = $result->fetch_assoc()) {
                $schedule[$row['day_of_week']] = [
                    'day_of_week' => $row['day_of_week'],
                    'start_time' => date('H:i', strtotime($row['start_time'])), // 24-hour format for time comparison
                    'end_time' => date('H:i', strtotime($row['end_time'])),
                    'start_time_display' => date('h:i A', strtotime($row['start_time'])), // 12-hour for display
                    'end_time_display' => date('h:i A', strtotime($row['end_time'])),
                    'is_available' => (bool)$row['is_available']
                ];
            }
            
            echo json_encode($schedule);
            exit;
        }
    }
    
    $doctor_id = (int)$_GET['doctor_id'] ?? (int)$_POST['doctor_id'] ?? 0;
    $appointment_date = $_GET['appointment_date'] ?? $_POST['appointment_date'] ?? '';
    
    if ($doctor_id && $appointment_date) {
        // Check if doctor is available on this day
        if (!Doctor::isDoctorAvailableOnDay($doctor_id, $appointment_date)) {
            echo json_encode([
                'available' => false,
                'message' => 'Doctor is not available on this date'
            ]);
            exit;
        }
        
        // Get doctor's schedule for this day to return available time slots
        $date = new DateTime($appointment_date);
        $day_of_week = (int)$date->format('w');
        
        $sql = "SELECT start_time, end_time FROM doctor_availability 
                WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $doctor_id, $day_of_week);
        $stmt->execute();
        $schedule_result = $stmt->get_result();
        $schedule = $schedule_result->fetch_assoc();
        
        // Check how many appointments exist for this doctor on this date
        $sql = "SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $doctor_id, $appointment_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $booked_slots = $row['count'];
        $max_slots = 5; // Maximum 5 appointments per slot
        $available_slots = $max_slots - $booked_slots;
        
        // Get start and end times for filtering
        $start_time = $schedule ? $schedule['start_time'] : '08:00';
        $end_time = $schedule ? $schedule['end_time'] : '17:00';
        
        if ($available_slots > 0) {
            echo json_encode([
                'available' => true,
                'available_slots' => $available_slots,
                'booked_slots' => $booked_slots,
                'start_time' => $start_time,
                'end_time' => $end_time
            ]);
        } else {
            echo json_encode([
                'available' => false,
                'message' => 'No slots available for this date',
                'start_time' => $start_time,
                'end_time' => $end_time
            ]);
        }
    } else {
        echo json_encode([
            'available' => false,
            'message' => 'Invalid parameters'
        ]);
    }
} else {
    echo json_encode([
        'available' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
