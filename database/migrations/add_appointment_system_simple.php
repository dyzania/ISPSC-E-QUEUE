<?php
/**
 * Simple Database Migration: Add Appointment Scheduling System
 * Run: php database/migrations/add_appointment_system_simple.php
 */

// Database credentials
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'equeue_system';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage() . "\n");
}

echo "Starting migration...\n\n";

// 1. Add columns to tickets table
echo "1. Adding appointment columns to tickets table...\n";
try {
    $pdo->exec("ALTER TABLE tickets ADD COLUMN appointment_date DATE NULL AFTER service_id");
    echo "   ✓ Added appointment_date\n";
} catch (PDOException $e) {
    echo "   ⚠ appointment_date: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE tickets ADD COLUMN appointment_time TIME NULL AFTER appointment_date");
    echo "   ✓ Added appointment_time\n";
} catch (PDOException $e) {
    echo "   ⚠ appointment_time: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE tickets ADD COLUMN is_appointment BOOLEAN DEFAULT 0 AFTER appointment_time");
    echo "   ✓ Added is_appointment\n";
} catch (PDOException $e) {
    echo "   ⚠ is_appointment: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE tickets ADD COLUMN auto_generated BOOLEAN DEFAULT 0 AFTER is_appointment");
    echo "   ✓ Added auto_generated\n";
} catch (PDOException $e) {
    echo "   ⚠ auto_generated: " . $e->getMessage() . "\n";
}

// 2. Create appointment_slots table
echo "\n2. Creating appointment_slots table...\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS appointment_slots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            service_id INT NOT NULL,
            slot_date DATE NOT NULL,
            slot_time TIME NOT NULL,
            max_capacity INT DEFAULT 5,
            current_bookings INT DEFAULT 0,
            is_available BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            UNIQUE KEY unique_slot (service_id, slot_date, slot_time),
            INDEX idx_date_time (slot_date, slot_time),
            INDEX idx_service_date (service_id, slot_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Created appointment_slots table\n";
} catch (PDOException $e) {
    echo "   ⚠ " . $e->getMessage() . "\n";
}

// 3. Create appointment_settings table
echo "\n3. Creating appointment_settings table...\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS appointment_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            service_id INT NOT NULL,
            enable_appointments BOOLEAN DEFAULT 1,
            advance_booking_days INT DEFAULT 7,
            slot_duration_minutes INT DEFAULT 30,
            start_time TIME DEFAULT '09:00:00',
            end_time TIME DEFAULT '17:00:00',
            slots_per_interval INT DEFAULT 5,
            reminder_minutes INT DEFAULT 30,
            allow_cancellation BOOLEAN DEFAULT 1,
            cancellation_deadline_hours INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            UNIQUE KEY unique_service (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "   ✓ Created appointment_settings table\n";
} catch (PDOException $e) {
    echo "   ⚠ " . $e->getMessage() . "\n";
}

// 4. Insert default settings
echo "\n4. Inserting default settings for services...\n";
try {
    $stmt = $pdo->query("SELECT id FROM services WHERE is_active = 1");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $insertStmt = $pdo->prepare("
        INSERT IGNORE INTO appointment_settings 
        (service_id, enable_appointments, advance_booking_days, slot_duration_minutes, 
         start_time, end_time, slots_per_interval, reminder_minutes)
        VALUES (?, 1, 7, 30, '09:00:00', '17:00:00', 5, 30)
    ");
    
    $count = 0;
    foreach ($services as $service) {
        $insertStmt->execute([$service['id']]);
        $count++;
    }
    
    echo "   ✓ Inserted settings for $count service(s)\n";
} catch (PDOException $e) {
    echo "   ⚠ " . $e->getMessage() . "\n";
}

echo "\n✅ Migration completed!\n";
echo "Appointment scheduling system is ready to use.\n";
