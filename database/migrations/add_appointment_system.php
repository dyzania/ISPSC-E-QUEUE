<?php
/**
 * Database Migration: Add Appointment Scheduling System
 * 
 * Standalone migration script that doesn't require application bootstrap
 * Run: php database/migrations/add_appointment_system.php
 */

// Database credentials (from config.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'equeue_system');

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✓ Database connected successfully\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

class AppointmentSystemMigration {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function up() {
        echo "Starting appointment system migration...\n\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Step 1: Modify tickets table
            echo "1. Adding appointment columns to tickets table...\n";
            $this->addTicketColumns();
            
            // Step 2: Create appointment_slots table
            echo "2. Creating appointment_slots table...\n";
            $this->createAppointmentSlotsTable();
            
            // Step 3: Create appointment_settings table
            echo "3. Creating appointment_settings table...\n";
            $this->createAppointmentSettingsTable();
            
            // Step 4: Insert default settings for existing services
            echo "4. Inserting default appointment settings...\n";
            $this->insertDefaultSettings();
            
            $this->pdo->commit();
            
            echo "\n✅ Migration completed successfully!\n";
            echo "Appointment scheduling system is now ready.\n";
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
    
    public function down() {
        echo "Rolling back appointment system migration...\n\n";
        
        try {
            $this->pdo->beginTransaction();
            
            echo "1. Dropping appointment_settings table...\n";
            $this->pdo->exec("DROP TABLE IF EXISTS appointment_settings");
            
            echo "2. Dropping appointment_slots table...\n";
            $this->pdo->exec("DROP TABLE IF EXISTS appointment_slots");
            
            echo "3. Removing appointment columns from tickets table...\n";
            // Check if columns exist before dropping
            $columns = ['appointment_date', 'appointment_time', 'is_appointment', 'auto_generated'];
            foreach ($columns as $column) {
                try {
                    $this->pdo->exec("ALTER TABLE tickets DROP COLUMN $column");
                } catch (PDOException $e) {
                    // Column might not exist, continue
                }
            }
            
            $this->pdo->commit();
            
            echo "\n✅ Rollback completed successfully!\n";
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "\n❌ Rollback failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function addTicketColumns() {
        // Check if columns already exist
        $stmt = $this->pdo->query("SHOW COLUMNS FROM tickets LIKE 'appointment_date'");
        if ($stmt->rowCount() > 0) {
            echo "   ⚠ Columns already exist, skipping...\n";
            return;
        }
        
        $sql = "
            ALTER TABLE tickets 
            ADD COLUMN appointment_date DATE NULL AFTER service_id,
            ADD COLUMN appointment_time TIME NULL AFTER appointment_date,
            ADD COLUMN is_appointment BOOLEAN DEFAULT 0 AFTER appointment_time,
            ADD COLUMN auto_generated BOOLEAN DEFAULT 0 AFTER is_appointment
        ";
        
        $this->pdo->exec($sql);
        echo "   ✓ Added appointment_date, appointment_time, is_appointment, auto_generated\n";
    }
    
    private function createAppointmentSlotsTable() {
        $sql = "
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
        ";
        
        $this->pdo->exec($sql);
        echo "   ✓ Created appointment_slots table\n";
    }
    
    private function createAppointmentSettingsTable() {
        $sql = "
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
        ";
        
        $this->pdo->exec($sql);
        echo "   ✓ Created appointment_settings table\n";
    }
    
    private function insertDefaultSettings() {
        // Get all active services
        $stmt = $this->pdo->query("SELECT id FROM services WHERE is_active = 1");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($services) == 0) {
            echo "   ⚠ No active services found, skipping default settings\n";
            return;
        }
        
        $insertSql = "
            INSERT INTO appointment_settings 
            (service_id, enable_appointments, advance_booking_days, slot_duration_minutes, 
             start_time, end_time, slots_per_interval, reminder_minutes)
            VALUES (?, 1, 7, 30, '09:00:00', '17:00:00', 5, 30)
            ON DUPLICATE KEY UPDATE service_id = service_id
        ";
        
        $stmt = $this->pdo->prepare($insertSql);
        
        foreach ($services as $service) {
            $stmt->execute([$service['id']]);
        }
        
        echo "   ✓ Inserted default settings for " . count($services) . " service(s)\n";
    }
}

// Run migration
if (php_sapi_name() === 'cli') {
    $migration = new AppointmentSystemMigration($pdo);
    
    $action = isset($argv[1]) ? $argv[1] : 'up';
    
    if ($action === 'up') {
        $migration->up();
    } elseif ($action === 'down') {
        $migration->down();
    } else {
        echo "Usage: php add_appointment_system.php [up|down]\n";
        exit(1);
    }
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}
