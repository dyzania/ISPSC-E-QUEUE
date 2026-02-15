<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/notifications.php';

class Ticket {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createTicket($userId, $serviceId, $userNote = null) {
        // Check if user has pending feedback
        if ($this->hasPendingFeedback($userId)) {
            return [
                'success' => false,
                'message' => 'Please complete feedback for your previous transaction before getting a new ticket.'
            ];
        }
        
        // Check if user already has an active ticket
        if ($this->getCurrentTicket($userId)) {
            return [
                'success' => false,
                'message' => 'You already have an active ticket. Please wait for your turn or complete your current transaction.'
            ];
        }
        
        // Generate unique ticket number
        $ticketNumber = $this->generateTicketNumber($serviceId);
        
        // Insert ticket
        $stmt = $this->db->prepare("
            INSERT INTO tickets (ticket_number, user_id, service_id, status, user_note) 
            VALUES (?, ?, ?, 'waiting', ?)
        ");
        
        $stmt->execute([$ticketNumber, $userId, $serviceId, $userNote]);
        $ticketId = $this->db->lastInsertId();
        
        // Update queue position
        $this->updateQueuePositions($serviceId);
        
        return [
            'success' => true,
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber
        ];
    }
    
    private function generateTicketNumber($serviceId) {
        // Get service code
        $stmt = $this->db->prepare("SELECT service_code FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();
        
        // Get today's count for this service
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM tickets 
            WHERE service_id = ? 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$serviceId]);
        $result = $stmt->fetch();
        
        $count = $result['count'] + 1;
        
        return $service['service_code'] . date('md') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
    
    public function hasPendingFeedback($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM tickets t
            LEFT JOIN feedback f ON t.id = f.ticket_id
            WHERE t.user_id = ? 
            AND t.status = 'completed'
            AND f.id IS NULL
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    public function getTicketById($id) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.service_code, w.window_number, w.window_name, 
                   u.full_name as user_name, u.email as user_email
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            LEFT JOIN windows w ON t.window_id = w.id
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getUserTickets($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.service_code, w.window_number, w.window_name,
                   u.full_name as user_name, u.email as user_email
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            LEFT JOIN windows w ON t.window_id = w.id
            JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getCurrentTicket($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.service_code, s.requirements, w.window_number, w.window_name, w.location_info,
                   u.full_name as user_name, u.email as user_email
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            LEFT JOIN windows w ON t.window_id = w.id
            JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ? 
            AND t.status IN ('waiting', 'called', 'serving')
            ORDER BY t.created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getQueuePosition($ticketId) {
        $stmt = $this->db->prepare("
            SELECT service_id, created_at 
            FROM tickets 
            WHERE id = ?
        ");
        
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) return 0;
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as position 
            FROM tickets 
            WHERE service_id = ? 
            AND status = 'waiting'
            AND (created_at < ? OR (created_at = ? AND id < ?))
        ");
        
        $stmt->execute([$ticket['service_id'], $ticket['created_at'], $ticket['created_at'], $ticketId]);
        $result = $stmt->fetch();
        
        return $result['position'];
    }
    
    public function getWaitingQueue($serviceId = null, $windowId = null) {
        $query = "
            SELECT t.*, s.service_name, s.service_code, u.full_name as user_name, u.email as user_email
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            JOIN users u ON t.user_id = u.id
            WHERE t.status = 'waiting'
        ";
        
        $params = [];
        
        if ($serviceId) {
            $query .= " AND t.service_id = ?";
            $params[] = $serviceId;
        }
        
        if ($windowId) {
            // Get services enabled for this window
            $query .= " AND t.service_id IN (
                SELECT service_id 
                FROM window_services 
                WHERE window_id = ? 
                AND is_enabled = 1
            )";
            $params[] = $windowId;
        }
        
        $query .= " ORDER BY t.created_at ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function callNextTicket($windowId) {
        // Check if window already has an active ticket (called or serving)
        // REMOVED: Allow multiple tickets to be called
        /*
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as active_count
            FROM tickets
            WHERE window_id = ?
            AND status IN ('called', 'serving')
        ");
        $stmt->execute([$windowId]);
        $activeCheck = $stmt->fetch();
        
        if ($activeCheck['active_count'] > 0) {
            return ['success' => false, 'message' => 'You already have an active ticket. Please complete it first.'];
        }
        */
        
        // Get window's enabled services
        $stmt = $this->db->prepare("
            SELECT service_id 
            FROM window_services 
            WHERE window_id = ? 
            AND is_enabled = 1
        ");
        $stmt->execute([$windowId]);
        $services = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($services)) {
            return ['success' => false, 'message' => 'No services enabled for this window'];
        }
        
        // Get next waiting ticket
        $placeholders = str_repeat('?,', count($services) - 1) . '?';
        $stmt = $this->db->prepare("
            SELECT * 
            FROM tickets 
            WHERE service_id IN ($placeholders) 
            AND status = 'waiting'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        
        $stmt->execute($services);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            return ['success' => false, 'message' => 'No waiting tickets'];
        }
        
        if ($ticket) {
             // Update ticket status
            $stmt = $this->db->prepare("
                UPDATE tickets 
                SET status = 'called', window_id = ?, called_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$windowId, $ticket['id']]);
            
            $ticketData = $this->getTicketById($ticket['id']);
            
            // Trigger Notifications (Web Push + Email)
            sendNotification(
                $ticketData['user_id'], 
                $ticketData['id'], 
                'turn_next', 
                "It's your turn! Please proceed to " . $ticketData['window_name']
            );
            
            // Update queue positions
            $this->updateQueuePositions($ticketData['service_id']);
            
            return [
                'success' => true,
                'ticket' => $ticketData
            ];
        } else {
             return ['success' => false, 'message' => 'No waiting tickets'];
        }
    }
    
    public function startServing($ticketId) {
        $stmt = $this->db->prepare("
            UPDATE tickets 
            SET status = 'serving', served_at = NOW() 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$ticketId]);
        
        if ($success) {
            // Get ticket details to notify user
            $ticket = $this->getTicketById($ticketId);
            sendNotification(
                $ticket['user_id'], 
                $ticketId, 
                'now_serving', 
                "You are now being served."
            );
        }
        
        return $success;
    }
    
    public function completeTicket($ticketId, $staffNotes = null) {
        $stmt = $this->db->prepare("
            UPDATE tickets 
            SET status = 'completed', completed_at = NOW(), staff_notes = ? 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$staffNotes, $ticketId]);
        
        if ($success) {
             $ticketData = $this->getTicketById($ticketId);
             
             // Trigger Notifications (Web Push + Email)
             sendNotification(
                 $ticketData['user_id'], 
                 $ticketId, 
                 'completed', 
                 "Transaction completed. Please provide your feedback.",
                 $staffNotes
             );
        }
        
        return $success;
    }
    
    
    private function updateQueuePositions($serviceId) {
        // This is handled by counting in real-time, no need to store positions
        return true;
    }
    
    public function getTicketsWithoutFeedback($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, w.window_number
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            LEFT JOIN windows w ON t.window_id = w.id
            LEFT JOIN feedback f ON t.id = f.ticket_id
            WHERE t.user_id = ? 
            AND t.status = 'completed'
            AND f.id IS NULL
            ORDER BY t.completed_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function getAllTickets() {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, u.full_name as user_name, w.window_number
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN windows w ON t.window_id = w.id
            ORDER BY t.created_at DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentTickets($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.service_code, u.full_name as user_name, w.window_number,
                   TIMESTAMPDIFF(SECOND, t.served_at, t.completed_at) as processing_seconds
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN windows w ON t.window_id = w.id
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $tickets = $stmt->fetchAll();

        foreach ($tickets as &$ticket) {
            if ($ticket['status'] === 'completed' && $ticket['processing_seconds'] !== null) {
                $seconds = $ticket['processing_seconds'];
                $m = floor($seconds / 60);
                $s = $seconds % 60;
                $ticket['processing_time'] = ($m > 0 ? "{$m}m " : "") . "{$s}s";
            } else {
                $ticket['processing_time'] = null;
            }
        }
        
        return $tickets;
    }
    public function getActiveTicketsByWindow($windowId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.staff_notes as service_notes, u.full_name as user_name
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            JOIN users u ON t.user_id = u.id
            WHERE t.window_id = ? 
            AND t.status IN ('called', 'serving')
            AND t.is_archived = 0
            ORDER BY t.called_at DESC
        ");
        $stmt->execute([$windowId]);
        return $stmt->fetchAll();
    }

    public function getArchivedTicketsByWindow($windowId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, u.full_name as user_name,
                   TIMESTAMPDIFF(SECOND, t.served_at, NOW()) as elapsed_seconds
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            JOIN users u ON t.user_id = u.id
            WHERE t.window_id = ? 
            AND t.status IN ('called', 'serving')
            AND t.is_archived = 1
            ORDER BY t.served_at ASC
        ");
        $stmt->execute([$windowId]);
        return $stmt->fetchAll();
    }

    public function archiveTicket($ticketId, $notes = null) {
        $stmt = $this->db->prepare("
            UPDATE tickets 
            SET is_archived = 1, staff_notes = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$notes, $ticketId]);
    }

    public function resumeTicket($ticketId) {
        $stmt = $this->db->prepare("
            UPDATE tickets 
            SET is_archived = 0 
            WHERE id = ?
        ");
        return $stmt->execute([$ticketId]);
    }

    public function getWaitingQueueForWindow($windowId) {
        // This leverages the logic in getWaitingQueue but specifically filtered for a window
        return $this->getWaitingQueue(null, $windowId);
    }

    public function getQueueStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'serving' THEN 1 ELSE 0 END) as serving,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM tickets
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getPendingFeedbackTicket($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.service_code, w.window_number, w.window_name
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            LEFT JOIN windows w ON t.window_id = w.id
            LEFT JOIN feedback f ON t.id = f.ticket_id
            WHERE t.user_id = ? 
            AND t.status = 'completed'
            AND f.id IS NULL
            ORDER BY t.created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function snoozeTicket($ticketId) {
        $ticket = $this->getTicketById($ticketId);
        if (!$ticket || $ticket['status'] !== 'waiting') return false;

        // Find the ticket that is currently 3 positions behind
        // We look for tickets created AFTER this one in the same service
        $stmt = $this->db->prepare("
            SELECT created_at 
            FROM tickets 
            WHERE service_id = ? 
            AND status = 'waiting' 
            AND created_at > ?
            ORDER BY created_at ASC
            LIMIT 1 OFFSET 2
        ");
        $stmt->execute([$ticket['service_id'], $ticket['created_at']]);
        $targetTicket = $stmt->fetch();

        if ($targetTicket) {
            // Move just after the 3rd person
            $newTime = date('Y-m-d H:i:s', strtotime($targetTicket['created_at']) + 1);
        } else {
            // If fewer than 3 people behind, just move to the very end
            $newTime = date('Y-m-d H:i:s', time());
        }

        $stmt = $this->db->prepare("UPDATE tickets SET created_at = ? WHERE id = ?");
        return $stmt->execute([$newTime, $ticketId]);
    }

    public function cancelTicket($ticketId) {
        $stmt = $this->db->prepare("
            UPDATE tickets 
            SET status = 'cancelled' 
            WHERE id = ?
        ");
        
        $success = $stmt->execute([$ticketId]);
        
        if ($success) {
            $ticket = $this->getTicketById($ticketId);
            sendNotification(
                $ticket['user_id'], 
                $ticketId, 
                'cancelled', 
                "Your ticket has been cancelled."
            );
        }
        
        return $success;
    }

    public function getUserTicketHistory($userId) {
        $stmt = $this->db->prepare("
            SELECT t.*, s.service_name, s.service_code, w.window_number,
                   TIMESTAMPDIFF(SECOND, t.served_at, t.completed_at) as processing_seconds
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            LEFT JOIN windows w ON t.window_id = w.id
            WHERE t.user_id = ? 
            AND t.status IN ('completed', 'cancelled')
            ORDER BY t.created_at DESC
        ");
        
        $stmt->execute([$userId]);
        $tickets = $stmt->fetchAll();

        foreach ($tickets as &$ticket) {
            if ($ticket['status'] === 'completed' && $ticket['processing_seconds'] !== null) {
                $seconds = $ticket['processing_seconds'];
                $m = floor($seconds / 60);
                $s = $seconds % 60;
                $ticket['processing_time'] = ($m > 0 ? "{$m}m " : "") . "{$s}s";
            } else {
                $ticket['processing_time'] = null;
            }
        }
        
        return $tickets;
    }

    public function getAverageProcessTime($serviceId) {
        // Get the service's default estimated time
        $stmt = $this->db->prepare("SELECT estimated_time FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();
        $defaultTime = $service['estimated_time'] ?? 3;
        
        // Get average processing time from recently completed tickets
        $stmt = $this->db->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, served_at, completed_at)) as avg_minutes
            FROM tickets
            WHERE service_id = ?
            AND status = 'completed'
            AND served_at IS NOT NULL
            AND completed_at IS NOT NULL
            AND DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        
        $stmt->execute([$serviceId]);
        $result = $stmt->fetch();
        
        // Return calculated average if available, otherwise use service's default estimated time
        return $result['avg_minutes'] ? round($result['avg_minutes']) : $defaultTime;
    }

    public function getGlobalDailyAverageProcessTime() {
        // Get average processing time from all tickets completed today
        $stmt = $this->db->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, served_at, completed_at)) as avg_minutes
            FROM tickets
            WHERE status = 'completed'
            AND served_at IS NOT NULL
            AND completed_at IS NOT NULL
            AND DATE(completed_at) = CURDATE()
        ");
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['avg_minutes']) {
            return round($result['avg_minutes']);
        }

        // Fallback: Get average from the last 7 days across all services
        $stmt = $this->db->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, served_at, completed_at)) as avg_minutes
            FROM tickets
            WHERE status = 'completed'
            AND served_at IS NOT NULL
            AND completed_at IS NOT NULL
            AND DATE(completed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['avg_minutes'] ? round($result['avg_minutes']) : 3;
    }

    public function getGlobalHistory($startDate = null, $endDate = null) {
        $query = "
            SELECT t.*, s.service_name, u.full_name as user_name, w.window_name, w.window_number,
                   TIMESTAMPDIFF(SECOND, t.served_at, t.completed_at) as processing_seconds
            FROM tickets t
            JOIN services s ON t.service_id = s.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN windows w ON t.window_id = w.id
            WHERE t.status IN ('completed', 'cancelled')
        ";
        
        $params = [];
        
        if ($startDate) {
            $query .= " AND DATE(t.created_at) >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $query .= " AND DATE(t.created_at) <= ?";
            $params[] = $endDate;
        }
        
        $query .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $tickets = $stmt->fetchAll();

        foreach ($tickets as &$ticket) {
            if ($ticket['status'] === 'completed' && $ticket['processing_seconds'] !== null) {
                $seconds = $ticket['processing_seconds'];
                $m = floor($seconds / 60);
                $s = $seconds % 60;
                $ticket['processing_time'] = ($m > 0 ? "{$m}m " : "") . "{$s}s";
            } else {
                $ticket['processing_time'] = '-';
            }
        }
        
        return $tickets;
    }

    public function getStaffDailyStats($staffId) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_served,
                AVG(TIMESTAMPDIFF(SECOND, t.served_at, t.completed_at)) as avg_processing_seconds,
                (SELECT AVG(f.rating) 
                 FROM feedback f 
                 JOIN tickets t2 ON f.ticket_id = t2.id 
                 JOIN windows w2 ON t2.window_id = w2.id 
                 WHERE w2.staff_id = ? 
                 AND DATE(t2.completed_at) = CURDATE()) as avg_rating
            FROM tickets t
            JOIN windows w ON t.window_id = w.id
            WHERE w.staff_id = ? 
              AND t.status = 'completed' 
              AND DATE(t.completed_at) = CURDATE()
        ");
        
        $stmt->execute([$staffId, $staffId]);
        $stats = $stmt->fetch();
        
        // Format processing time
        $seconds = (float)($stats['avg_processing_seconds'] ?? 0);
        $totalSeconds = (int)round($seconds);
        $m = floor($totalSeconds / 60);
        $s = $totalSeconds % 60;
        $stats['avg_processing_time'] = ($m > 0 ? "{$m}m " : "") . "{$s}s";
        
        // Format rating (out of 5)
        $stats['avg_rating'] = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : '0';
        
        return $stats;
    }
    public function getGlobalAverageProcessTime() {
        $stmt = $this->db->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, served_at, completed_at)) as avg_minutes
            FROM tickets
            WHERE status = 'completed'
            AND served_at IS NOT NULL
            AND completed_at IS NOT NULL
            AND DATE(completed_at) = CURDATE()
        ");
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['avg_minutes'] ? round($result['avg_minutes']) : 0;
    }

    public function getPeakHour() {
        $stmt = $this->db->prepare("
            SELECT HOUR(created_at) as hour, COUNT(*) as count
            FROM tickets
            WHERE DATE(created_at) = CURDATE()
            GROUP BY hour
            ORDER BY count DESC
            LIMIT 1
        ");
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result ? str_pad($result['hour'], 2, '0', STR_PAD_LEFT) . ':00' : 'N/A';
    }
}
