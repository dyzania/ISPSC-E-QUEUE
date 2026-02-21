<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/MailService.php';

$error = null;
$success = null;
$context = $_GET['context'] ?? 'verification'; // 'verification' or 'reset'
$email = $_GET['email'] ?? '';

$userModel = new User();
$mailService = new MailService();

// Handle resend request
if (isset($_GET['resend']) && $_GET['resend'] === 'true' && !empty($email)) {
    $result = false;
    if ($context === 'reset') {
        $result = $userModel->requestPasswordReset($email);
    } else {
        $result = $userModel->requestVerificationOTP($email);
    }

    if ($result) {
        if ($mailService->sendOTPEmail($email, $result['full_name'], $result['code'], $context)) {
            $success = "New verification code sent to your email.";
        } else {
            $error = "Failed to send code. Please try again later.";
        }
    } else {
        $error = "Impossible to refresh protocol. Account may already be verified or does not exist.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $otpCode = implode('', $_POST['otp']); // Combine array of inputs
    $context = $_POST['context'];
    
    $user = $userModel->verifyOTP($email, $otpCode);
    
    if ($user) {
        if ($context === 'reset') {
            $_SESSION['reset_user_id'] = $user['id'];
            header('Location: reset-password.php');
            exit;
        } else {
            // Verification successful
            header('Location: login.php?verified=true');
            exit;
        }
    } else {
        $error = "Invalid or expired OTP code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .otp-bg {
            background-image: linear-gradient(to right, rgba(26,1,1,0.95) 0%, rgba(26,1,1,0.6) 100%), url('img/drone.png');
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
<body class="min-h-screen flex items-center justify-center p-4 font-sans text-white bg-primary-950 otp-bg selection:bg-primary-500/30">

    <div class="max-w-md w-full bg-primary-950/40 backdrop-blur-3xl p-8 md:p-10 rounded-[40px] shadow-2xl border border-white/10 text-center relative overflow-hidden animate-tilt">
        <!-- Back Button -->
        <a href="login.php" class="absolute top-6 left-6 w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 text-gray-400 hover:text-white hover:bg-white/10 transition-all active:scale-90 group z-20" title="Back to Login">
            <i class="fas fa-chevron-left"></i>
        </a>

        
        <div class="mb-8 font-heading">
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform rotate-3 p-3 group-hover:rotate-6 transition-all duration-500">
                <img src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-4xl font-black mb-2 tracking-tight">Verification Code</h1>
            <p class="text-gray-400 font-medium">
                We've sent a 6-digit code to <br> <span class="font-bold text-gray-200"><?php echo htmlspecialchars($email); ?></span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="p-4 mb-6 text-sm text-primary-400 bg-primary-500/10 rounded-xl border border-primary-500/20 flex items-center justify-center animate-shake">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span class="font-bold uppercase tracking-widest text-[10px]"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="p-4 mb-6 text-sm text-secondary-400 bg-secondary-500/10 rounded-xl border border-secondary-500/20 flex items-center justify-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="font-bold uppercase tracking-widest text-[10px]"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="context" value="<?php echo htmlspecialchars($context); ?>">
            
            <div class="flex justify-center space-x-2 md:space-x-4 mb-8">
                <?php for($i=0; $i<6; $i++): ?>
                    <input type="text" name="otp[]" maxlength="1" class="w-12 h-14 md:w-14 md:h-16 text-center text-2xl md:text-3xl font-black border border-white/10 rounded-xl focus:border-white focus:ring-4 focus:ring-white/10 focus:bg-white/10 outline-none transition-all shadow-sm bg-white/5 backdrop-blur-sm text-white" required oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length === 1) { try { this.nextElementSibling.focus() } catch(e) {} }">
                <?php endfor; ?>
            </div>

            <button type="submit" class="w-full bg-primary-600 text-white font-black py-4 rounded-xl shadow-xl shadow-primary-900/20 hover:bg-primary-500 hover:shadow-primary-500/40 hover:-translate-y-1 transition-all active:scale-95 text-lg tracking-tighter uppercase">
                Verify Identity
            </button>
        </form>

        <p class="mt-8 text-sm text-gray-500 font-medium">
            Didn't receive the code? <a href="?resend=true&email=<?php echo urlencode($email); ?>&context=<?php echo urlencode($context); ?>" class="text-primary-500 font-bold hover:text-primary-400 transition-colors">Resend</a>
        </p>
        
        <div class="mt-6 pt-6 border-t border-white/5">
            <a href="login.php" class="text-xs font-black uppercase tracking-widest text-gray-500 hover:text-gray-400 transition-colors">
                Back to Login
            </a>
        </div>
    </div>
    
    <script>
        // Auto-focus logic for OTP inputs
        const inputs = document.querySelectorAll('input[name="otp[]"]');
        inputs.forEach((input, index) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            
            // Paste event support
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                if (text) {
                    const chars = text.split('');
                    inputs.forEach((inp, idx) => {
                        if (chars[idx]) inp.value = chars[idx];
                    });
                    if (inputs[text.length]) inputs[text.length].focus();
                    else inputs[5].focus();
                }
            });
        });
        
        // Focus first input on load
        window.addEventListener('load', () => inputs[0].focus());
    </script>
</body>
</html>
