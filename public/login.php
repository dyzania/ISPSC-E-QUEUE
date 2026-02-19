<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (!str_contains($email, '@')) {
        $email = strtolower($email) . '@window.local';
    }
    
    $userModel = new User();
    $user = $userModel->login($email, $password);
    
    if ($user && !isset($user['unverified'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['school_id'] = $user['school_id'];
        
        switch ($user['role']) {
            case 'admin': header('Location: admin/dashboard.php'); break;
            case 'staff': header('Location: staff/dashboard.php'); break;
            default: header('Location: user/dashboard.php'); break;
        }
        exit;
    } elseif ($user && isset($user['unverified'])) {
        $verifyLink = "verify-otp.php?email=" . urlencode($email) . "&context=verification";
        $error = "Account not verified. <a href='$verifyLink' class='underline font-black hover:text-white'>Verify Now</a>";
    } else {


        $error = "Invalid credentials or password";
    }
}

if (isset($_GET['verified'])) {
    $success = "Email verified successfully! You can now sign in.";
}
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Password reset successfully. Please login with your new password.";
}
if (isset($_GET['update']) && $_GET['update'] === 'password_success') {
    $success = "Password updated successfully. Please login with your new credentials.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-bg {
            background-image: linear-gradient(135deg, rgba(5, 34, 2, 0.95) 0%, rgba(2, 16, 1, 0.8) 100%), url('img/drone.png');
            background-size: cover;
            background-position: center;
        }
        .animate-tilt {
            animation: tilt 10s infinite linear;
        }
        @keyframes tilt {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(0.5deg); }
            75% { transform: rotate(-0.5deg); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 login-bg font-sans selection:bg-primary-500/30">

    <div class="max-w-[1200px] w-full grid grid-cols-1 lg:grid-cols-2 bg-primary-950/40 backdrop-blur-3xl rounded-[40px] shadow-2xl overflow-hidden border border-primary-500/20 animate-tilt">
        <!-- Brand Side -->
        <div class="hidden lg:flex flex-col justify-between p-16 bg-gradient-to-br from-primary-600/20 to-transparent relative overflow-hidden">
            <div class="z-10">
                <a href="index.php" class="flex items-center space-x-4 mb-16 group">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-2xl group-hover:rotate-6 transition-all duration-500 p-2">
                        <img src="img/logo.png" alt="ISPSC Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black tracking-tighter font-heading text-white leading-none">ISPSC MAIN</span>
                        <span class="text-sm font-bold tracking-[0.3em] text-primary-400 mt-1 uppercase">Registrar E-Queue</span>
                    </div>
                </a>
                
                <h1 class="text-6xl font-black leading-tight mb-8 font-heading text-white">
                    ISPSC MAIN <br>
                    <span class="text-primary-400 font-black">REGISTRAR E-QUEUE.</span>
                </h1>
                <p class="text-gray-400 text-xl max-w-md font-medium leading-relaxed">
                    Official e-queueing portal for ISPSC Main Registrar. Manage your walk-in service flow with precision and ease.
                </p>
            </div>

            <div class="relative z-10">
                <div class="flex items-center space-x-6">
                    <div class="w-px h-12 bg-white/20"></div>
                    <p class="text-sm text-gray-400 font-black uppercase tracking-[0.3em]">Authorized Access Only</p>
                </div>
            </div>

            <div class="absolute -top-20 -right-20 w-80 h-80 bg-primary-500/10 rounded-full blur-[100px]"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-secondary-500/10 rounded-full blur-[100px]"></div>
        </div>

        <!-- Form Side -->
        <div class="p-8 md:p-20 flex flex-col justify-center bg-primary-950/10 relative">
            <!-- Close/Back Button -->
            <a href="index.php" class="absolute top-8 right-8 w-12 h-12 flex items-center justify-center rounded-2xl bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 hover:border-white/20 transition-all active:scale-90 group z-20" title="Go Back">
                <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform duration-300"></i>
            </a>

            <div class="mb-12 text-center lg:text-left">
                <h2 class="text-4xl font-black text-white mb-3 font-heading tracking-tight">Login</h2>
                <p class="text-gray-500 font-medium">Enter your credentials to access the system.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="p-5 mb-8 text-sm text-primary-400 bg-primary-500/10 rounded-2xl border border-primary-500/20 flex items-center animate-shake">
                    <i class="fas fa-shield-virus mr-4 text-xl"></i>
                    <span class="font-bold uppercase tracking-widest text-[10px]"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="p-5 mb-8 text-sm text-secondary-400 bg-secondary-500/10 rounded-2xl border border-secondary-500/20 flex items-center">
                    <i class="fas fa-check-double mr-4 text-xl"></i>
                    <span class="font-bold uppercase tracking-widest text-[10px]"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="space-y-2">
                    <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="email">Email</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-600 group-focus-within:text-white transition-colors text-lg"></i>
                        </div>
                        <input 
                            type="text" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full pl-16 pr-6 py-5 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600"
                            placeholder="Email or Local ID"
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center ml-1">
                        <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em]" for="password">Password</label>
                        <a href="forgot-password.php" class="text-[10px] font-black uppercase tracking-widest text-primary-500 hover:text-primary-400">Forgot Password?</a>
                    </div>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                            <i class="fas fa-key text-gray-600 group-focus-within:text-white transition-colors text-lg"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full pl-16 pr-12 py-5 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600"
                            placeholder="••••••••"
                        >
                        <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-6 flex items-center text-gray-600 hover:text-white transition-colors">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-primary-600 text-white font-black py-6 rounded-2xl shadow-2xl shadow-primary-900/20 hover:bg-primary-500 hover:shadow-primary-500/40 hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center space-x-4 text-xl tracking-tighter"
                >
                    <span>LOGIN</span>
                    <i class="fas fa-arrow-right text-sm opacity-50"></i>
                </button>
            </form>

            <div class="mt-12 pt-10 border-t border-primary-500/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 font-medium text-sm">New to the system?</p>
                <a href="register.php" class="bg-primary-500/5 hover:bg-primary-500/10 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-[0.2em] border border-white/30 transition-all active:scale-95">Register</a>
            </div>
        </div>
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
