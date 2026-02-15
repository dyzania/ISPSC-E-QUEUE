<?php
/**
 * API: Get Available Appointment Slots
 * Returns available time slots for a given service and date
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$serviceId = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$serviceId || !$date) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Check if date is in the future
if (strtotime($date) < strtotime('today')) {
    echo json_encode(['error' => 'Cannot book appointments in the past']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get appointment settings for this service
    $stmt = $db->prepare("
        SELECT * FROM appointment_settings 
        WHERE service_id = ? AND enable_appointments = 1
    ");
    $stmt->execute([$serviceId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        echo json_encode(['error' => 'Appointments not enabled for this service']);
        exit;
    }
    
    // Check if date is within advance booking limit
    $maxDate = date('Y-m-d', strtotime("+{$settings['advance_booking_days']} days"));
    if ($date > $maxDate) {
        echo json_encode(['error' => "Cannot book more than {$settings['advance_booking_days']} days in advance"]);
        exit;
    }
    
    // Generate time slots (no capacity checking)
    $slots = [];
    $startTime = new DateTime($date . ' ' . $settings['start_time']);
    $endTime = new DateTime($date . ' ' . $settings['end_time']);
    $interval = new DateInterval('PT' . $settings['slot_duration_minutes'] . 'M');
    
    while ($startTime < $endTime) {
        $slotTime = $startTime->format('H:i:s');
        $slotDateTime = $date . ' ' . $slotTime;
        
        // Skip past time slots for today
        if ($date === date('Y-m-d') && strtotime($slotDateTime) <= time()) {
            $startTime->add($interval);
            continue;
        }
        
        $slots[] = [
            'time' => $startTime->format('g:i A'),
            'time_24h' => $slotTime,
            'available' => true,
            'remaining' => 999 // Unlimited
        ];
        
        $startTime->add($interval);
    }
    
    echo json_encode([
        'success' => true,
        'slots' => $slots,
        'settings' => [
            'slot_duration' => $settings['slot_duration_minutes']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get slots error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to retrieve available slots']);
}
