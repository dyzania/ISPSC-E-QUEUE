<?php
$pageTitle = 'Operations Center';
require_once __DIR__ . '/../../models/Window.php';
require_once __DIR__ . '/../../models/User.php';
include __DIR__ . '/../../includes/admin-layout-header.php';

$windowModel = new Window();
$userModel = new User();

// Get all windows
$windows = $windowModel->getAllWindows();
$windowCount = count($windows);
$MAX_WINDOWS = 7;

// Function to find next available window number
function getNextWindowNumber($windows) {
    for ($i = 1; $i <= 7; $i++) {
        $found = false;
        foreach ($windows as $w) {
            // Extract number from "W-0X" or "W-X"
            if (preg_match('/W-0?(\d+)/', $w['window_number'], $matches)) {
                if ((int)$matches[1] == $i) {
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) return $i;
    }
    return null;
}

$nextNum = getNextWindowNumber($windows);
$nextWindowStr = $nextNum ? 'W-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT) : 'Full';

$success_msg = '';
$error_msg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_window') {
            if ($windowCount >= $MAX_WINDOWS) {
                $error_msg = "Maximum limit of $MAX_WINDOWS windows reached.";
            } else {
                // Auto-generate login code based on window number
                $loginCode = strtolower(str_replace('-', '', $nextWindowStr)); // "W-01" becomes "w01"
                $email = $loginCode . "@window.local"; // Internal email format
                $password = $_POST['password'];
                $full_name = "Staff " . $nextWindowStr;
                
                // 1. Create User (Staff)
                if ($userModel->emailExists($email)) {
                    $error_msg = "Login code already exists.";
                } else {
                     if ($userModel->register($email, $password, $full_name, null, 'staff')) {
                        // Get the ID of the new user
                        $db = Database::getInstance()->getConnection();
                        $newUserId = $db->lastInsertId();
                        
                        // 2. Create Window linked to this user
                        $windowName = "Window $nextNum";
                        if ($windowModel->createWindow($nextWindowStr, $windowName, $newUserId)) {
                            $success_msg = "Window $nextWindowStr created! Login Code: <strong>$loginCode</strong>";
                            // Refresh
                            $windows = $windowModel->getAllWindows();
                            $windowCount = count($windows);
                            $nextNum = getNextWindowNumber($windows);
                            $nextWindowStr = $nextNum ? 'W-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT) : 'Full';
                        } else {
                            $error_msg = "Failed to create window record.";
                            // Deleting user to keep clean:
                            $db->exec("DELETE FROM users WHERE id = $newUserId");
                        }
                     } else {
                         $error_msg = "Failed to create staff user credentials.";
                     }
                }
            }
        } elseif ($_POST['action'] === 'delete_window') {
            $windowId = $_POST['window_id'];
            $staffId = $_POST['staff_id']; // Passed from form to delete the user too
            
            if ($windowModel->deleteWindow($windowId)) {
                // Delete the associated user as well
                if ($staffId) {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$staffId]);
                }
                $success_msg = "Window and associated staff account deleted.";
                $windows = $windowModel->getAllWindows();
                $windowCount = count($windows);
                $nextNum = getNextWindowNumber($windows);
                $nextWindowStr = $nextNum ? 'W-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT) : 'Full';
            } else {
                $error_msg = "Failed to delete window.";
            }
        }
    }
}
?>

<div class="space-y-10">
    <div class="flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-6"> <!-- Adjusted for centering but keeping button to right? Or fully center? User said "justify center". Let's center the text, maybe keep layout or center everything. -->
        <!-- Actually "justify center all the text" usually implies centered alignment for headers and data. 
             If I use text-center on the container, everything centers. 
             But the button might look weird if not handled. 
             Let's try to center the HEADER text specifically. 
        -->
        <div class="w-full text-center md:w-auto md:text-left"> <!-- Re-reading: "justify center all the text". 
             I'll stick to what I did for Dashboard: Centered headers. -->
        </div>
        <!-- Wait, I should look at what I did for dashboard. I centered the headers. 
             For tables, I should center the TH and TD.
        -->
    </div>
    
    <!-- Redoing the block above to be safer -->
    
    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="text-center md:text-left w-full md:w-auto"> <!-- keeping md:text-left might contradict "justify center all". 
             Let's make it ALWAYS center for the "text". 
             The user said "justify and align center *all* the text". 
             So I will enforce text-center. 
        -->
             <div class="text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-primary-600 mb-2">Operations Center</p>
                <h1 class="text-4xl 5xl:text-8xl font-black text-gray-900 font-heading tracking-tight leading-none">Window Management</h1>
                <p class="text-gray-500 font-medium mt-2 text-sm">Control service counters and assigned staff credentials.</p>
            </div>
        </div>
        
        <?php if ($windowCount < $MAX_WINDOWS): ?>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-8 py-4 rounded-lg shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95 flex items-center font-black text-sm font-heading">
                <i class="fas fa-plus mr-3"></i>Deploy <?php echo $nextWindowStr; ?>
            </button>
        <?php else: ?>
            <div class="bg-slate-100 text-slate-400 px-8 py-4 rounded-lg font-black flex items-center text-sm border border-slate-200">
                <i class="fas fa-lock mr-3"></i>Maximum Capacity reached
            </div>
        <?php endif; ?>
    </div>
    
    <?php if($success_msg): ?>
        <div class="p-6 bg-emerald-50 rounded-xl border border-emerald-100 text-emerald-800 flex items-center justify-center shadow-lg shadow-emerald-100/50">
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
            <table class="w-full text-center"> <!-- Changed text-left to text-center -->
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Module ID</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Internal Name</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Access Code</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Availability</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Control</th> <!-- Removed text-right, added text-center -->
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($windows)): ?>
                        <tr>
                            <td colspan="5" class="px-10 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-xl flex items-center justify-center mb-6">
                                        <i class="fas fa-desktop text-3xl text-slate-200"></i>
                                    </div>
                                    <p class="font-bold text-gray-400">No service modules deployed.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($windows as $window): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-10 py-6">
                                    <div class="flex items-center justify-center space-x-3"> <!-- Added justify-center -->
                                        <div class="w-10 h-10 rounded-md bg-primary-50 text-primary-600 flex items-center justify-center font-black text-xs border border-primary-100 shadow-sm">
                                            <?php echo $window['window_number']; ?>
                                        </div>
                                        <span class="font-black text-gray-900 text-xs tracking-tight">Terminal</span>
                                    </div>
                                </td>
                                <td class="px-10 py-6 text-sm font-bold text-gray-600"><?php echo htmlspecialchars($window['window_name']); ?></td>
                                <td class="px-10 py-6">
                                    <?php 
                                    $loginCode = $window['staff_email'] ? explode('@', $window['staff_email'])[0] : 'N/A';
                                    ?>
                                    <span class="bg-slate-50 text-slate-600 px-4 py-1.5 rounded-md text-[10px] font-black border border-slate-100 tracking-widest uppercase">
                                        <?php echo htmlspecialchars($loginCode); ?>
                                    </span>
                                </td>
                                <td class="px-10 py-6">
                                    <span class="px-2 py-1 flex items-center justify-center space-x-2"> <!-- Added justify-center -->
                                        <span class="w-2 h-2 rounded-full <?php echo $window['is_active'] ? 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]' : 'bg-slate-300'; ?>"></span>
                                        <span class="text-[10px] font-black uppercase tracking-widest <?php echo $window['is_active'] ? 'text-emerald-600' : 'text-slate-400'; ?>">
                                            <?php echo $window['is_active'] ? 'Live' : 'Standby'; ?>
                                        </span>
                                    </span>
                                </td>
                                <td class="px-10 py-6 text-center"> <!-- Changed text-right to text-center -->
                                    <form id="delete-form-<?php echo $window['id']; ?>" method="POST" class="inline-block">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_window">
                                        <input type="hidden" name="window_id" value="<?php echo $window['id']; ?>">
                                        <input type="hidden" name="staff_id" value="<?php echo $window['staff_id']; ?>">
                                        <button type="button" 
                                                onclick="handleDeleteWindow(<?php echo $window['id']; ?>, '<?php echo $window['window_number']; ?>')"
                                                class="text-slate-300 hover:text-rose-500 transition-all p-3 rounded-lg hover:bg-rose-50 active:scale-95" 
                                                title="Terminate Module">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-10 transform transition-all border border-white">
        <div class="flex justify-between items-center mb-8">
            <div>
                <p class="text-[10px] font-black text-primary-600 uppercase tracking-widest mb-1">Module Provisioning</p>
                <h3 class="text-2xl font-black text-gray-900 font-heading tracking-tight leading-none">Activate Terminal <?php echo $nextWindowStr; ?></h3>
            </div>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-300 hover:text-gray-900 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create_window">
            
            <div class="p-6 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Auto-Generated Access Code</p>
                    <p class="text-xl font-black text-gray-900 font-mono tracking-tighter"><?php echo strtolower(str_replace('-', '', $nextWindowStr)); ?></p>
                </div>
                <div class="w-12 h-12 bg-white rounded-md border border-slate-100 flex items-center justify-center text-primary-600 shadow-sm">
                    <i class="fas fa-key"></i>
                </div>
            </div>
            
            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Secure Passkey</label>
                <input type="password" name="password" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="••••••••••••">
                <p class="text-[10px] text-gray-400 mt-2 ml-2">This password will be used by staff to access the terminal.</p>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-5 rounded-lg font-black shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95">
                    Deploy Service Module
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    async function handleDeleteWindow(windowId, windowNumber) {
        if (await equeueConfirm(`WARNING: Terminating ${windowNumber} will revoke all staff access codes. Proceed?`, 'Terminate Module')) {
            document.getElementById(`delete-form-${windowId}`).submit();
        }
    }
</script>

<?php 
include __DIR__ . '/../../includes/admin-layout-footer.php'; 
?>
