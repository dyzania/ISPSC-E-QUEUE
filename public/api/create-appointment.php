<?php
/**
 * API: Create Appointment
 * Books an appointment for a user
 */

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// CSRF protection
$headers = getallheaders();
$csrfToken = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$serviceId = isset($data['service_id']) ? intval($data['service_id']) : 0;
$date = isset($data['date']) ? $data['date'] : '';
$time = isset($data['time']) ? $data['time'] : '';
$userId = $_SESSION['user_id'];

if (!$serviceId || !$date || !$time) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    // Check if user already has an active appointment
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM tickets 
        WHERE user_id = ? AND is_appointment = 1 
        AND status IN ('scheduled', 'waiting', 'called', 'serving')
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        throw new Exception('You already have a scheduled ticket. Please complete or cancel it first.');
    }
    
    // Generate ticket number
    $serviceStmt = $db->prepare("SELECT service_code FROM services WHERE id = ?");
    $serviceStmt->execute([$serviceId]);
    $service = $serviceStmt->fetch(PDO::FETCH_ASSOC);
    
    $ticketStmt = $db->prepare("SELECT MAX(id) as max_id FROM tickets");
    $ticketStmt->execute();
    $maxId = $ticketStmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0;
    $ticketNumber = $service['service_code'] . '-' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
    
    // Create ticket with scheduled status
    $stmt = $db->prepare("
        INSERT INTO tickets 
        (ticket_number, user_id, service_id, appointment_date, appointment_time, 
         is_appointment, status, created_at)
        VALUES (?, ?, ?, ?, ?, 1, 'scheduled', NOW())
    ");
    $stmt->execute([$ticketNumber, $userId, $serviceId, $date, $time]);
    $ticketId = $db->lastInsertId();
    
    // Create notification
    $appointmentDateTime = date('F j, Y \a\t g:i A', strtotime("$date $time"));
    $stmt = $db->prepare("
        INSERT INTO notifications 
        (user_id, ticket_id, type, message, created_at)
        VALUES (?, ?, 'appointment_confirmed', ?, NOW())
    ");
    $message = "Your ticket has been scheduled for $appointmentDateTime. Ticket: $ticketNumber";
    $stmt->execute([$userId, $ticketId, $message]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'ticket_number' => $ticketNumber,
        'ticket_id' => $ticketId,
        'appointment_date' => $date,
        'appointment_time' => $time,
        'message' => 'Appointment booked successfully!'
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Create appointment error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
