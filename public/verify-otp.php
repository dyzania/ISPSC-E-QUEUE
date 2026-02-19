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
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg transform rotate-3 p-3">
                <img src="<?php echo BASE_URL; ?>/img/logo.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl font-black mb-2 font-heading">Verification Code</h1>
            <p class="text-gray-600 font-medium">
                We've sent a 6-digit code to <br> <span class="font-bold text-gray-800"><?php echo htmlspecialchars($email); ?></span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="p-4 mb-6 text-sm text-primary-600 bg-primary-100 rounded-xl border border-primary-200 flex items-center justify-center animate-shake">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span class="font-bold"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="p-4 mb-6 text-sm text-secondary-600 bg-secondary-100 rounded-xl border border-secondary-200 flex items-center justify-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span class="font-bold"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="context" value="<?php echo htmlspecialchars($context); ?>">
            
            <div class="flex justify-center space-x-2 md:space-x-4 mb-8">
                <?php for($i=0; $i<6; $i++): ?>
                    <input type="text" name="otp[]" maxlength="1" class="w-12 h-14 md:w-14 md:h-16 text-center text-2xl md:text-3xl font-black border-2 border-gray-200 rounded-xl focus:border-primary-600 focus:ring-4 focus:ring-primary-600/10 outline-none transition-all shadow-sm bg-white/50 backdrop-blur-sm" required oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length === 1) { try { this.nextElementSibling.focus() } catch(e) {} }">
                <?php endfor; ?>
            </div>

            <button type="submit" class="w-full bg-primary-600 text-white font-black py-4 rounded-xl shadow-xl shadow-primary-600/20 hover:bg-primary-700 hover:shadow-2xl hover:-translate-y-1 transition-all active:scale-95 text-lg tracking-wide uppercase">
                Verify Identity
            </button>
        </form>

        <p class="mt-8 text-sm text-gray-500 font-medium">
            Dn't receive the code? <a href="?resend=true&email=<?php echo urlencode($email); ?>&context=<?php echo urlencode($context); ?>" class="text-primary-600 font-bold hover:underline">Resend</a>
        </p>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
            <a href="login.php" class="text-xs font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-colors">
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
