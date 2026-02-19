<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/MailService.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    $userModel = new User();
    $result = $userModel->requestPasswordReset($email);
    
    if ($result) {
        $mailService = new MailService();
        if ($mailService->sendOTPEmail($email, $result['full_name'], $result['code'], 'reset')) {
            header("Location: verify-otp.php?email=" . urlencode($email) . "&context=reset");
            exit;
        } else {
            $error = "Failed to send OTP. Please try again later.";
        }
    } else {
        $error = "If an account exists with this email, an OTP has been sent."; 
        $error = "Email not found in our records.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4 font-sans text-gray-900 bg-gray-50">
    
    <div class="mesh-gradient-container">
        <div class="mesh-gradient-item mesh-1"></div>
        <div class="mesh-gradient-item mesh-2"></div>
    </div>

    <div class="max-w-md w-full glass-morphism p-8 md:p-10 rounded-[30px] shadow-2xl border border-white/20 text-center relative overflow-hidden">
        <!-- Back Button -->
        <a href="login.php" class="absolute top-6 left-6 w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100/50 hover:bg-gray-200 transition-all active:scale-90 group z-20" title="Back to Login">
            <i class="fas fa-chevron-left text-gray-600"></i>
        </a>

        
        <div class="mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform -rotate-3 p-3">
                <img src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-black mb-2 font-heading">Recovery</h1>
            <p class="text-gray-600 font-medium">
                Enter your registered email address to receive a verification code.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="p-4 mb-6 text-sm text-primary-600 bg-primary-100 rounded-xl border border-primary-200 flex items-center justify-center animate-shake">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span class="font-bold"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div class="space-y-2 text-left">
                <label class="block text-gray-500 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="email">Email Address</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400 group-focus-within:text-primary-600 transition-colors"></i>
                    </div>
                    <input type="email" id="email" name="email" required class="w-full pl-12 pr-4 py-4 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-primary-600/10 focus:border-primary-600 transition-all font-bold text-gray-800 placeholder-gray-400 shadow-sm" placeholder="john@example.com">
                </div>
            </div>

            <button type="submit" class="w-full bg-primary-600 text-white font-black py-4 rounded-xl shadow-xl shadow-primary-600/20 hover:bg-primary-700 hover:shadow-2xl hover:-translate-y-1 transition-all active:scale-95 text-lg tracking-wide uppercase flex items-center justify-center space-x-2">
                <span>Send Code</span>
                <i class="fas fa-paper-plane text-sm opacity-50"></i>
            </button>
        </form>
        
        <div class="mt-8 pt-6 border-t border-gray-200">
            <a href="login.php" class="text-xs font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors">
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>
