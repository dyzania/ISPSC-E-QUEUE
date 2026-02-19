<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

requireLogin();
requireRole('user');

$userModel = new User();
$user = $userModel->getUserById(getUserId());

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name']);
    $schoolId = !empty($_POST['school_id']) ? sanitize($_POST['school_id']) : null;
    $currentPassword = $_POST['current_password'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($fullName)) {
        $error = "Full name is required.";
    } elseif (!empty($password)) {
        if (empty($currentPassword)) {
            $error = "Current password is required to set a new one.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = "Incorrect current password.";
        } elseif ($password !== $confirmPassword) {
            $error = "New passwords do not match.";
        } elseif (!empty($passwordErrors = User::validatePassword($password))) {
            $error = $passwordErrors[0];
        }
    }

    if (empty($error)) {
        if ($userModel->updateProfile(getUserId(), $fullName, $schoolId, !empty($password) ? $password : null)) {
            if (!empty($password)) {
                // Password updated successfully,auto-logout for security
                session_destroy();
                header("Location: ../login.php?update=password_success");
                exit;
            }
            $_SESSION['full_name'] = $fullName;
            $_SESSION['school_id'] = $schoolId;
            $success = "Profile updated successfully!";
            $user = $userModel->getUserById(getUserId()); // Refresh data
        } else {
            $error = "Failed to update profile.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>

    <main class="container-ultra px-4 md:px-10 py-12 pb-20">
        <div class="max-w-4xl mx-auto">
            <div class="mb-12">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <p class="text-[10px] 3xl:text-xs font-black uppercase tracking-[0.4em] text-primary-600 mb-2">Account Settings</p>
                        <h1 class="text-4xl 3xl:text-7xl font-black text-gray-900 font-heading tracking-tight leading-none">Your Profile</h1>
                        <p class="text-gray-500 font-medium mt-2 3xl:text-xl">Manage your personal information and security.</p>
                    </div>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="p-6 3xl:p-10 mb-10 text-emerald-800 bg-emerald-50 rounded-3xl border border-emerald-100 flex items-center shadow-lg shadow-emerald-100/50 animate-float" role="alert">
                    <span class="font-bold text-lg"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="p-6 3xl:p-10 mb-10 text-red-800 bg-red-50 rounded-3xl border border-red-100 flex items-center shadow-lg shadow-red-100/50" role="alert">
                    <i class="fas fa-exclamation-circle mr-4 text-2xl"></i>
                    <span class="font-bold text-lg"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <!-- Left: Profile Preview -->
                <div class="md:col-span-1">
                    <div class="bg-white rounded-[40px] p-10 shadow-premium border border-slate-50 text-center relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="w-32 h-32 bg-primary-50 rounded-[40px] flex items-center justify-center mx-auto mb-6 relative">
                                <img class="w-28 h-28 rounded-[32px] object-cover" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=15803d&color=fff&size=128&font-size=0.33" alt="">
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 font-heading tracking-tight"><?php echo $user['full_name']; ?></h2>
                            <p class="text-gray-400 font-bold text-xs uppercase tracking-widest mt-1">
                                <?php echo $user['email']; ?>
                                <?php if ($user['school_id']): ?>
                                     • ID: <?php echo $user['school_id']; ?>
                                <?php endif; ?>
                            </p>
                            
                            <div class="mt-8 pt-8 border-t border-slate-50 space-y-4">
                                <div class="flex items-center justify-between text-left">
                                    <span class="text-[10px] font-black uppercase text-gray-400">Joined</span>
                                    <span class="text-xs font-bold text-gray-700"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between text-left">
                                    <span class="text-[10px] font-black uppercase text-gray-400">Account Type</span>
                                    <span class="px-3 py-1 bg-primary-50 text-primary-600 rounded-lg text-[10px] font-black uppercase tracking-widest">Standard User</span>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -left-10 -bottom-10 text-8xl text-primary-50/50 pointer-events-none group-hover:rotate-12 transition-transform duration-700">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>

                <!-- Right: Settings Form -->
                <div class="md:col-span-2">
                    <div class="bg-white rounded-[48px] p-12 shadow-premium border border-slate-50 h-full">
                        <form method="POST" class="space-y-8">
                            <div>
                                <h3 class="text-xl font-black text-gray-900 font-heading tracking-tight mb-6 flex items-center">
                                    <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-fingerprint text-sm text-primary-600"></i>
                                    </div>
                                    Basics
                                </h3>
                                <div class="space-y-6">
                                    <div class="relative group">
                                        <label class="absolute -top-2 left-6 px-2 bg-white text-[10px] font-black uppercase tracking-widest text-primary-600 z-10">Full Name</label>
                                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[24px] focus:outline-none focus:ring-4 focus:ring-primary-100 focus:bg-white focus:border-primary-500 transition-all font-bold text-gray-700">
                                    </div>
                                    <div class="relative group">
                                        <label class="absolute -top-2 left-6 px-2 bg-white text-[10px] font-black uppercase tracking-widest text-primary-600 z-10">School ID (Optional)</label>
                                        <input type="text" name="school_id" value="<?php echo htmlspecialchars($user['school_id'] ?? ''); ?>" placeholder="" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[24px] focus:outline-none focus:ring-4 focus:ring-primary-100 focus:bg-white focus:border-primary-500 transition-all font-bold text-gray-700">
                                    </div>
                                    <div class="relative group opacity-60">
                                        <label class="absolute -top-2 left-6 px-2 bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 z-10">Email Address (Read-only)</label>
                                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="w-full px-8 py-5 bg-slate-100 border border-slate-100 rounded-[24px] font-bold text-gray-400 cursor-not-allowed">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-slate-50">
                                <h3 class="text-xl font-black text-gray-900 font-heading tracking-tight mb-6 flex items-center">
                                    <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-lock text-sm text-amber-600"></i>
                                    </div>
                                    Security
                                </h3>
                                <div class="grid grid-cols-1 gap-6 mb-6">
                                    <div class="relative group">
                                        <label class="absolute -top-2 left-6 px-2 bg-white text-[10px] font-black uppercase tracking-widest text-primary-600 z-10">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" placeholder="••••••••" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[24px] focus:outline-none focus:ring-4 focus:ring-primary-50 focus:bg-white focus:border-primary-500 transition-all font-bold text-gray-700">
                                        <button type="button" onclick="togglePassword('current_password', this)" class="absolute inset-y-0 right-0 pr-6 flex items-center text-gray-400 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="relative group">
                                        <label class="absolute -top-2 left-6 px-2 bg-white text-[10px] font-black uppercase tracking-widest text-amber-600 z-10">New Password</label>
                                        <input type="password" id="password" name="password" placeholder="••••••••" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[24px] focus:outline-none focus:ring-4 focus:ring-amber-50 focus:bg-white focus:border-amber-500 transition-all font-bold text-gray-700">
                                        <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-6 flex items-center text-gray-400 hover:text-amber-600 transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <div class="mt-2 ml-4">
                                            <p class="text-[10px] text-gray-400 font-medium leading-relaxed">
                                                <i class="fas fa-info-circle mr-1 opacity-50"></i>
                                                8+ characters, with uppercase, lowercase, number, and symbol.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="relative group">
                                        <label class="absolute -top-2 left-6 px-2 bg-white text-[10px] font-black uppercase tracking-widest text-amber-600 z-10">Confirm Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" class="w-full px-8 py-5 bg-slate-50 border border-slate-100 rounded-[24px] focus:outline-none focus:ring-4 focus:ring-amber-50 focus:bg-white focus:border-amber-500 transition-all font-bold text-gray-700">
                                        <button type="button" onclick="togglePassword('confirm_password', this)" class="absolute inset-y-0 right-0 pr-6 flex items-center text-gray-400 hover:text-amber-600 transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-10">
                                <button type="submit" class="w-full bg-slate-900 text-white py-6 rounded-3xl font-black text-xl shadow-premium hover:bg-black hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center space-x-4 group">
                                    <span>Save Changes</span>
                                    <i class="fas fa-check-circle group-hover:scale-125 transition-transform"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/chatbot-widget.php'; ?>
    <script src="<?php echo BASE_URL; ?>/js/notifications.js"></script>
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
