<?php
// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load environment variables
loadEnv(__DIR__ . '/../.env');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'equeue_system');

// Application Configuration
// -------------------------------------------------------------------------
// PRODUCTION DEPLOYMENT CHECKLIST:
// 1. Update BASE_URL below to: 'https://ispsc-queue-system.com/public'
// 2. Update database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME)
// 3. Set display_errors to 0 (line 28)
// 4. Re-enable requireLogin() and requireRole() in this file
// -------------------------------------------------------------------------

// Dynamic Base URL detection for local network/mobile access
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
// Detect if we are in the 'public' folder or root
$currentDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$publicPath = (strpos($currentDir, '/public') !== false) ? '/public' : '';
// This is a common pattern for local PHP apps to handle subdirectories
$baseUrl = $protocol . '://' . $host . '/antigravity/public';
define('BASE_URL', $baseUrl);

define('APP_NAME', 'E-Queue System');

// Email Configuration (for notifications)
define('MAILER_PATH', __DIR__ . '/../mailer/autoload.php');
define('SMTP_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('MAIL_PORT') ?: 587);
define('SMTP_USER', getenv('MAIL_USERNAME') ?: 'your-email@gmail.com');
define('SMTP_PASS', getenv('MAIL_PASSWORD') ?: 'your-app-password');
define('FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'noreply@equeue.com');
define('FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'E-Queue System');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 0 in production!

// Database Connection Class
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserializing
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Security & Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php');
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        redirect('index.php');
    }
}

function apiRequireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Security: CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Log the security event
        logSecurityEvent('CSRF_FAILURE', "Invalid token provided. Expected: {$_SESSION['csrf_token']}, Got: $token");
        die('Invalid CSRF Token. Please refresh the page and try again.');
    }
}

// Security: Rate Limiting (Basic Session-based)
function checkRateLimit($action, $limit, $windowSeconds) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $currentTime = time();
    $windowStart = $currentTime - $windowSeconds;
    
    // Initialize or cleanup old attempts
    if (!isset($_SESSION['rate_limit'][$action])) {
        $_SESSION['rate_limit'][$action] = [];
    }
    
    // Filter out old attempts
    $_SESSION['rate_limit'][$action] = array_filter($_SESSION['rate_limit'][$action], function($timestamp) use ($windowStart) {
        return $timestamp > $windowStart;
    });
    
    // Check limit
    if (count($_SESSION['rate_limit'][$action]) >= $limit) {
        logSecurityEvent('RATE_LIMIT_EXCEEDED', "Action: $action, Limit: $limit");
        jsonResponse(['success' => false, 'message' => 'Too many requests. Please slow down.'], 429);
    }
    
    // Add current attempt
    $_SESSION['rate_limit'][$action][] = $currentTime;
}

// Security: Logging
function logSecurityEvent($event, $details = '') {
    $logFile = __DIR__ . '/../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user = $_SESSION['user_id'] ?? 'GUEST';
    $logEntry = "[$timestamp] [IP:$ip] [USER:$user] [EVENT:$event] $details" . PHP_EOL;
    
    // Create logs directory if not exists
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Set secure params before starting session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_only_cookies', 1);
    
    // Only set secure flag if HTTPS is enabled
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}



function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Load Environment Variables (Simple implementation)
if (!isset($_ENV['MAIL_HOST'])) {
    loadEnv(__DIR__ . '/../.env');
}

// Chatbot API Configuration (OpenRouter)
define('OPENROUTER_API_KEY', $_ENV['OPENROUTER_API_KEY'] ?? ''); 
define('OPENROUTER_API_URL', $_ENV['OPENROUTER_API_URL'] ?? 'https://openrouter.ai/api/v1/chat/completions');
define('AI_MODEL', $_ENV['AI_MODEL'] ?? 'stepfun/step-3.5-flash:free');

// Theme Colors
define('COLOR_PRIMARY', '#0c4b05'); // Dark Green
define('COLOR_SECONDARY', '#8b0101'); // Dark Red

function injectTailwindConfig() {
    echo "
    <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css' rel='stylesheet' />
    <script src='https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js'></script>
    <script>
        const EQUEUE_BASE_URL = '" . BASE_URL . "';
    </script>
    <script src='" . BASE_URL . "/js/notifications.js?v=1.1'></script>
    <script src='" . BASE_URL . "/js/live-status.js'></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf2',
                            100: '#dbfde1',
                            200: '#bbf7c6',
                            300: '#86ef9b',
                            400: '#4ade68',
                            500: '" . COLOR_PRIMARY . "',
                            600: '#0a4004',
                            700: '#083503',
                            800: '#062a03',
                            900: '#052202',
                            950: '#021001',
                        },
                        secondary: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '" . COLOR_SECONDARY . "',
                            600: '#750101',
                            700: '#610101',
                            800: '#4c0101',
                            900: '#380101',
                        },

                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    screens: {
                        '3xl': '1800px',
                        '4xl': '2100px',
                        '5xl': '2500px',
                    },
                    maxWidth: {
                        'ultra': '2500px',
                    },
                    boxShadow: {
                        'premium': '0 20px 50px -12px rgba(0, 0, 0, 0.08), 0 10px 30px -10px rgba(0, 0, 0, 0.03)',
                        'division': '0 15px 35px -5px rgba(0, 0, 0, 0.05), 0 5px 15px -5px rgba(0, 0, 0, 0.02)',
                        'primary-premium': '0 25px 60px -15px rgba(12, 75, 5, 0.15)',
                    },
                    animation: {
                        'gradient': 'gradient 8s linear infinite',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        gradient: {
                            '0%, 100%': { 'background-size': '200% 200%', 'background-position': 'left center' },
                            '50%': { 'background-size': '200% 200%', 'background-position': 'right center' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc; /* bg-slate-50 */
            min-height: 100vh;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Outfit', sans-serif; }
        .glass-morphism {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.04);
        }
        .dark .glass-morphism {
            background: rgba(17, 24, 39, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Modern Mesh Gradient */
        .mesh-gradient-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background-color: #f8fafc;
        }

        .mesh-gradient-item {
            position: absolute;
            width: 60vw;
            height: 60vw;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: mesh-float 20s infinite alternate ease-in-out;
        }

        .mesh-1 { background-color: #0c4b05; top: -10%; left: -10%; animation-delay: 0s; }
        .mesh-2 { background-color: #8b0101; bottom: -10%; right: -10%; animation-delay: -5s; }

        @keyframes mesh-float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(10%, 10%) scale(1.2); }
        }
    </style>
    ";
}

/* 
 * DEPLOYMENT NOTES FOR ispsc-queue-system.com:
 * 1. Upload all files to the public_html or equivalent directory of your host.
 * 2. If the domain points directly to the root, your BASE_URL should be 'https://ispsc-queue-system.com/public'
 * 3. Update SMTP settings (lines 13-16) for email notifications to work on the server.
 * 4. Ensure your server's PHP version is 8.0+ for better performance.
 */
