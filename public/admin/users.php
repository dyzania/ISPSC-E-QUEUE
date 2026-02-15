<?php
$pageTitle = 'Identity Management';
require_once __DIR__ . '/../../models/User.php';
include __DIR__ . '/../../includes/admin-layout-header.php';

$userModel = new User();
$users = $userModel->getAllUsers();

// Check if getAllUsers exists, if not query directly
if(!method_exists($userModel, 'getAllUsers')) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM users ORDER BY role, full_name");
    $users = $stmt->fetchAll();
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_user') {
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $full_name = sanitize($_POST['full_name']);
            $role = 'user'; // Users management is only for regular users
            
            if ($userModel->emailExists($email)) {
                $error_msg = "Email already exists";
            } else {
                if ($userModel->register($email, $password, $full_name, null, $role)) {
                    $success_msg = "User created successfully";
                    // Refresh list or redirect
                } else {
                    $error_msg = "Failed to create user";
                }
            }
        }
    }
}
?>

<div class="space-y-10">
<div class="space-y-10">
    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="text-center w-full md:w-auto">
            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-primary-600 mb-2">Access & Security</p>
            <h1 class="text-4xl 5xl:text-8xl font-black text-gray-900 font-heading tracking-tight leading-none">User Directory</h1>
            <p class="text-gray-500 font-medium mt-2 text-sm">Manage administrative access and staff authorizations.</p>
        </div>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-8 py-4 rounded-lg shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95 flex items-center font-black text-sm font-heading">
            <i class="fas fa-user-plus mr-3"></i>Register New Account
        </button>
    </div>
    
    <?php if($success_msg): ?>
        <div class="p-6 bg-emerald-50 rounded-xl border border-emerald-100 text-emerald-800 flex items-center justify-center shadow-lg shadow-emerald-100/50 animate-float">
            <i class="fas fa-check-circle mr-4 text-2xl"></i>
            <span class="font-bold"><?php echo $success_msg; ?></span>
        </div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
        <div class="p-6 bg-rose-50 rounded-xl border border-rose-100 text-rose-800 flex items-center justify-center shadow-lg shadow-rose-100/50">
            <i class="fas fa-exclamation-circle mr-4 text-2xl"></i>
            <span class="font-bold"><?php echo $error_msg; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-center">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Identity</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Permissions</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Lifecycle</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4" class="px-10 py-20 text-center text-gray-400 font-bold">No specialized users found in the directory.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-10 py-6">
                                    <div class="flex items-center justify-center space-x-4">
                                        <img class="w-10 h-10 rounded-md border-2 border-white shadow-sm" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random" alt="">
                                        <div class="text-left"> <!-- Keep text-left for name/email readability but centered in cell -->
                                            <div class="font-black text-gray-900 text-sm mb-1"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <div class="text-[11px] text-gray-500 font-bold tracking-tight lowercase"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-10 py-6">
                                    <?php 
                                        $roleStyle = match($user['role']) {
                                            'admin' => 'bg-rose-50 text-rose-700 border-rose-100',
                                            'staff' => 'bg-primary-50 text-primary-700 border-primary-100',
                                            default => 'bg-slate-50 text-slate-700 border-slate-100'
                                        };
                                    ?>
                                    <span class="px-4 py-1.5 rounded-md text-[10px] font-black uppercase tracking-widest border <?php echo $roleStyle; ?>">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td class="px-10 py-6">
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Onboarded</div>
                                    <div class="text-xs font-bold text-gray-700"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                                </td>
                                <td class="px-10 py-6 text-center">
                                    <button class="text-slate-300 hover:text-primary-600 transition-all p-3 rounded-lg hover:bg-primary-50 active:scale-95">
                                        <i class="fas fa-shield-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div id="createModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-10 transform transition-all border border-white">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-gray-900 font-heading tracking-tight leading-none">Identity Provisioning</h3>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-300 hover:text-gray-900 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="create_user">
            
            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Full Legal Name</label>
                <input type="text" name="full_name" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="e.g. Alexander Hamilton">
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Corporate Email</label>
                <input type="email" name="email" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="alex@company.com">
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Secure Credential</label>
                <input type="password" name="password" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="••••••••••••">
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-5 rounded-lg font-black shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95">
                    Authorize Identity
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
include __DIR__ . '/../../includes/admin-layout-footer.php'; 
?>
