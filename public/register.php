<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/MailService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $fullName = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $schoolId = !empty($_POST['school_id']) ? sanitize($_POST['school_id']) : null;
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($password !== $confirmPassword) {
        $error = "Access keys do not match.";
    } else {
        $passwordErrors = User::validatePassword($password);
        if (!empty($passwordErrors)) {
            $error = $passwordErrors[0];
        } else {
            $userModel = new User();
            $mailService = new MailService();
            
            if ($userModel->emailExists($email)) {
                $error = "Identity already registered in the grid.";
            } else {
                // Register and get OTP
                $otpCode = $userModel->register($email, $password, $fullName, $schoolId, 'user');
                
                if ($otpCode) {
                    // Send OTP Email
                    if ($mailService->sendOTPEmail($email, $fullName, $otpCode, 'verification')) {
                        // Redirect to OTP verification page
                        header("Location: verify-otp.php?email=" . urlencode($email) . "&context=verification");
                        exit;
                    } else {
                        $error = "Registration successful, but failed to send OTP. Please contact support.";
                    }
                } else {
                    $error = "Protocol failure. Identity or School ID already synchronized.";
                }
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
    <title>Register Identity - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .register-bg {
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
<body class="min-h-screen flex items-center justify-center p-4 register-bg font-sans selection:bg-primary-500/30">

    <div class="max-w-[1240px] w-full grid grid-cols-1 lg:grid-cols-2 bg-primary-950/40 backdrop-blur-3xl rounded-[40px] shadow-2xl overflow-hidden border border-primary-500/20 animate-tilt">
        
        <!-- Form Side -->
        <div class="p-8 md:p-16 lg:p-20 flex flex-col justify-center bg-primary-950/10 order-2 lg:order-1 relative">
            <!-- Close/Back Button -->
            <a href="index.php" class="absolute top-8 left-8 w-12 h-12 flex items-center justify-center rounded-2xl bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 hover:border-white/20 transition-all active:scale-90 group z-20" title="Go Back">
                <i class="fas fa-times text-xl group-hover:rotate-90 transition-transform duration-300"></i>
            </a>

            <div class="mb-10 text-center lg:text-left">
                <h2 class="text-4xl font-black text-white mb-3 font-heading tracking-tight">Register</h2>
                <p class="text-gray-500 font-medium">Create your account to join the queueing system.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="p-4 mb-6 text-sm text-primary-400 bg-primary-500/10 rounded-2xl border border-primary-500/20 flex items-center animate-shake">
                    <i class="fas fa-shield-virus mr-3 text-lg"></i>
                    <span class="font-bold uppercase tracking-widest text-[9px]"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="p-4 mb-6 text-sm text-secondary-400 bg-secondary-500/10 rounded-2xl border border-secondary-500/20 flex items-center">
                    <i class="fas fa-check-double mr-3 text-lg"></i>
                    <span class="font-bold uppercase tracking-widest text-[9px]"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4 md:space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="space-y-2 text-left">
                        <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="full_name">Full Name</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-600 group-focus-within:text-white transition-colors"></i>
                            </div>
                            <input type="text" id="full_name" name="full_name" required class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600" placeholder="John Doe">
                        </div>
                    </div>
                    <div class="space-y-2 text-left">
                        <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="school_id">School ID</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fas fa-id-badge text-gray-600 group-focus-within:text-white transition-colors"></i>
                            </div>
                            <input type="text" id="school_id" name="school_id" class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600" placeholder="e.g. 2024-01">
                        </div>
                    </div>
                </div>

                <div class="space-y-2 text-left">
                    <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="email">Email</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                            <i class="fas fa-envelope-open-text text-gray-600 group-focus-within:text-white transition-colors"></i>
                        </div>
                        <input type="email" id="email" name="email" required class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600" placeholder="john@example.com">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="space-y-2 text-left">
                        <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="password">Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-600 group-focus-within:text-white transition-colors"></i>
                            </div>
                            <input type="password" id="password" name="password" required class="w-full pl-12 pr-12 py-4 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600" placeholder="••••••••">
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-600 hover:text-white transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="mt-2 text-[10px] text-gray-400 font-medium ml-1">
                            <i class="fas fa-info-circle mr-1 opacity-50"></i>
                            Must contain at least 8 characters, including uppercase, lowercase, number, and special character.
                        </p>
                    </div>
                    <div class="space-y-2 text-left">
                        <label class="block text-gray-400 text-[10px] font-black uppercase tracking-[0.3em] ml-1" for="confirm_password">Confirm Password</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fas fa-shield-check text-gray-600 group-focus-within:text-white transition-colors"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required class="w-full pl-12 pr-12 py-4 bg-white/5 border border-white/10 rounded-2xl focus:outline-none focus:ring-4 focus:ring-white/10 focus:border-white transition-all font-medium text-white placeholder-gray-600" placeholder="••••••••">
                            <button type="button" onclick="togglePassword('confirm_password', this)" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-600 hover:text-white transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary-600 text-white font-black py-5 rounded-2xl shadow-2xl shadow-primary-900/20 hover:bg-primary-500 hover:shadow-primary-500/40 hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center space-x-4 text-lg">
                    <span>REGISTER</span>
                    <i class="fas fa-magic text-sm opacity-50"></i>
                </button>
            </form>

            <div class="mt-10 pt-8 border-t border-primary-500/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 font-medium text-xs">Already have an account?</p>
                <a href="login.php" class="bg-primary-500/5 hover:bg-primary-500/10 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-[0.2em] border border-white/30 transition-all active:scale-95">Login</a>
            </div>
        </div>

        <!-- Brand Side -->
        <div class="hidden lg:flex flex-col justify-between p-16 bg-gradient-to-bl from-primary-600/20 to-transparent relative overflow-hidden order-1 lg:order-2">
            <div class="z-10">
                <a href="index.php" class="flex items-center space-x-4 mb-16 group justify-end">
                    <div class="flex flex-col text-right">
                        <span class="text-2xl font-black tracking-tighter font-heading text-white leading-none">ISPSC MAIN</span>
                        <span class="text-sm font-bold tracking-[0.3em] text-primary-400 mt-1 uppercase">Registrar E-Queue</span>
                    </div>
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-2xl group-hover:rotate-6 transition-all duration-500 p-2">
                        <img src="img/logo.png" alt="ISPSC Logo" class="w-full h-full object-contain">
                    </div>
                </a>
                
                <h1 class="text-6xl font-black leading-tight mb-8 font-heading text-white text-right">
                    JOIN THE <br>
                    <span class="text-primary-400 font-black">REGISTRAR QUEUE.</span>
                </h1>
                <p class="text-gray-400 text-xl max-w-sm ml-auto text-right font-medium leading-relaxed">
                    Efficiency starts with an identity. Register now to experience the next-gen Registrar e-queueing grid.
                </p>
            </div>

            <div class="relative z-10 text-right">
                <div class="flex justify-end space-x-2">
                    <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                    <div class="w-2 h-2 rounded-full bg-primary-500/40"></div>
                    <div class="w-2 h-2 rounded-full bg-primary-500/10"></div>
                </div>
            </div>

            <div class="absolute -top-20 -right-20 w-80 h-80 bg-primary-500/10 rounded-full blur-[100px]"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-secondary-500/10 rounded-full blur-[100px]"></div>
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
