<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

$message = "Invalid or expired verification token.";
$success = false;

if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    $userModel = new User();
    $user = $userModel->getByToken($token);
    
    if ($user) {
        if ($userModel->verifyUser($user['id'])) {
            $message = "Email verified successfully! You can now access your account.";
            $success = true;
            header('Refresh: 3; url=login.php?verified=true');
        } else {
            $message = "An error occurred while verifying your account. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="mesh-gradient-container">
        <div class="mesh-gradient-item mesh-1"></div>
        <div class="mesh-gradient-item mesh-2"></div>
        <div class="mesh-gradient-item mesh-3"></div>
    </div>

    <div class="max-w-md w-full glass-morphism p-8 rounded-3xl shadow-2xl border border-white/20 text-center">
        <div class="w-20 h-20 <?php echo $success ? 'bg-secondary-100 text-secondary-600' : 'bg-primary-100 text-primary-600'; ?> rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg">
            <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> text-4xl"></i>
        </div>
        
        <h1 class="text-3xl font-black text-gray-900 mb-4 font-heading">
            <?php echo $success ? 'Account Verified!' : 'Verification Failed'; ?>
        </h1>
        
        <p class="text-gray-600 font-medium mb-8">
            <?php echo $message; ?>
        </p>

        <?php if ($success): ?>
            <div class="flex items-center justify-center space-x-2 text-indigo-600 font-bold">
                <div class="w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <span>Redirecting to login...</span>
            </div>
        <?php else: ?>
            <a href="login.php" class="inline-block bg-slate-900 text-white font-black px-8 py-3 rounded-2xl shadow-xl hover:bg-black transition-all">
                Back to Login
            </a>
        <?php endif; ?>
    </div>

    <style>
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
    </style>
</body>
</html>
