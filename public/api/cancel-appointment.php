<?php
/**
 * API: Cancel Appointment
 * Cancels a scheduled appointment ticket
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
$ticketId = isset($data['ticket_id']) ? intval($data['ticket_id']) : 0;
$userId = $_SESSION['user_id'];

if (!$ticketId) {
    echo json_encode(['error' => 'Missing ticket ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify ticket belongs to user and is scheduled
    $stmt = $db->prepare("
        SELECT * FROM tickets 
        WHERE id = ? AND user_id = ? AND is_appointment = 1 AND status = 'scheduled'
    ");
    $stmt->execute([$ticketId, $userId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found or cannot be cancelled');
    }
    
    // Update ticket status to cancelled
    $stmt = $db->prepare("
        UPDATE tickets 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$ticketId]);
    
    // Create notification
    $stmt = $db->prepare("
        INSERT INTO notifications 
        (user_id, ticket_id, type, message, created_at)
        VALUES (?, ?, 'appointment_cancelled', ?, NOW())
    ");
    $message = "Your scheduled ticket {$ticket['ticket_number']} has been cancelled.";
    $stmt->execute([$userId, $ticketId, $message]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Scheduled ticket cancelled successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Cancel appointment error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
