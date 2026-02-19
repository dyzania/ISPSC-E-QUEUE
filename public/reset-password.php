<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

// Ensure user is authorized to reset password (via OTP)
if (!isset($_SESSION['reset_user_id'])) {
    header('Location: login.php');
    exit;
}

$userModel = new User();
$targetUser = $userModel->getUserById($_SESSION['reset_user_id']);
$userRole = $targetUser['role'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $passwordErrors = [];
        if ($userRole === 'user') {
            $passwordErrors = User::validatePassword($password);
        } else {
            // Simple validation for Staff/Admin
            if (strlen($password) < 8) {
                $passwordErrors[] = "Password must be at least 8 characters long.";
            }
        }

        if (!empty($passwordErrors)) {
            $error = $passwordErrors[0];
        } else {
            // Reset password
            if ($userModel->resetPassword($_SESSION['reset_user_id'], $password)) {
            // Clear session variable
            unset($_SESSION['reset_user_id']);
            
            // Redirect with success message
            header('Location: login.php?reset=success');
            exit;
        } else {
                $error = "Failed to reset password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
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
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform rotate-6 p-3">
                <img src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-black mb-2 font-heading">New Access Key</h1>
            <p class="text-gray-600 font-medium">
                Set a new password for your account.
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
                <label class="block text-gray-500 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="password">New Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <i class="fas fa-key text-gray-400 group-focus-within:text-primary-600 transition-colors"></i>
                    </div>
                    <input type="password" id="password" name="password" required class="w-full pl-12 pr-12 py-4 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-primary-600/10 focus:border-primary-600 transition-all font-bold text-gray-800 placeholder-gray-400 shadow-sm" placeholder="••••••••">
                    <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-600 transition-colors">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if ($userRole === 'user'): ?>
                <p class="mt-2 text-[10px] text-gray-400 font-medium ml-1">
                    <i class="fas fa-info-circle mr-1 opacity-50"></i>
                    Must contain at least 8 characters, including uppercase, lowercase, number, and special character.
                </p>
                <?php else: ?>
                <p class="mt-2 text-[10px] text-gray-400 font-medium ml-1">
                    <i class="fas fa-info-circle mr-1 opacity-50"></i>
                    Minimum of 8 characters required.
                </p>
                <?php endif; ?>
            </div>

            <div class="space-y-2 text-left">
                <label class="block text-gray-500 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="confirm_password">Confirm Password</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                        <i class="fas fa-check-double text-gray-400 group-focus-within:text-primary-600 transition-colors"></i>
                    </div>
                    <input type="password" id="confirm_password" name="confirm_password" required class="w-full pl-12 pr-12 py-4 bg-white/50 border border-gray-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-primary-600/10 focus:border-primary-600 transition-all font-bold text-gray-800 placeholder-gray-400 shadow-sm" placeholder="••••••••">
                    <button type="button" onclick="togglePassword('confirm_password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-primary-600 transition-colors">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-primary-600 text-white font-black py-4 rounded-xl shadow-xl shadow-primary-600/20 hover:bg-primary-700 hover:shadow-2xl hover:-translate-y-1 transition-all active:scale-95 text-lg tracking-wide uppercase flex items-center justify-center space-x-2">
                <span>Update Password</span>
                <i class="fas fa-save text-sm opacity-50"></i>
            </button>
        </form>
    </div>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
