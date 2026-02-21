<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Service.php';
require_once __DIR__ . '/../../models/Ticket.php';

requireLogin();
requireRole('user');

$serviceModel = new Service();
$ticketModel = new Ticket();
$db = Database::getInstance()->getConnection();

$services = $serviceModel->getAllServices();
$error = '';
$success = '';

// Check for pending feedback
$hasPendingFeedback = $ticketModel->hasPendingFeedback(getUserId());
// Check for current active ticket
$currentTicket = $ticketModel->getCurrentTicket(getUserId());


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {
    $serviceId = intval($_POST['service_id']);
    $userNote = isset($_POST['user_note']) ? trim($_POST['user_note']) : null;
    $result = $ticketModel->createTicket(getUserId(), $serviceId, $userNote);
    
    if ($result['success']) {
        $success = "Ticket created successfully!";
        header('Location: my-ticket.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Get Ticket - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>

    <main class="container-ultra px-4 md:px-10 py-8 pb-20">
        <div class="mb-12">
            <p class="text-[10px] 3xl:text-xs font-black uppercase tracking-[0.4em] text-primary-600 mb-2">Service Selection</p>
            <h1 class="text-4xl 3xl:text-7xl font-black text-gray-900 font-heading tracking-tight leading-none">Get Your Ticket</h1>
            <p class="text-gray-500 font-medium mt-2 3xl:text-xl">Choose a service to get your ticket.</p>
        </div>



        <?php if ($hasPendingFeedback): ?>
            <div class="bg-secondary-600 rounded-[32px] 3xl:rounded-[48px] p-10 3xl:p-16 text-white shadow-premium mb-12 relative overflow-hidden group">
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8 md:gap-16">
                    <div class="flex items-center space-x-6 3xl:space-x-12 text-center md:text-left">
                        <div class="w-20 3xl:w-32 h-20 3xl:h-32 bg-white/20 rounded-3xl 3xl:rounded-[48px] flex items-center justify-center animate-bounce">
                            <i class="fas fa-star-half-alt text-3xl 3xl:text-5xl"></i>
                        </div>
                        <div>
                            <h2 class="text-3xl 3xl:text-5xl font-black font-heading mb-1 tracking-tight">Feedback Required</h2>
                            <p class="text-secondary-100 font-medium 3xl:text-2xl">Please share your thoughts on your last visit before taking a new ticket.</p>
                        </div>
                    </div>
                    <a href="my-ticket.php" class="bg-white text-secondary-600 px-10 3xl:px-16 py-5 3xl:py-8 rounded-[22px] 3xl:rounded-[32px] font-black text-lg 3xl:text-2xl hover:scale-105 transition-all shadow-xl active:scale-95 flex items-center space-x-3">
                        <span>Go to My Ticket</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="absolute -right-10 top-0 text-[120px] 3xl:text-[200px] font-black text-white/10 select-none pointer-events-none uppercase tracking-tighter">REVIEW</div>
            </div>
        <?php elseif ($currentTicket): ?>
            <div class="bg-primary-600 rounded-[32px] 3xl:rounded-[48px] p-10 3xl:p-16 text-white shadow-premium mb-12 relative overflow-hidden group">
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8 md:gap-16">
                    <div class="flex items-center space-x-6 3xl:space-x-12 text-center md:text-left">
                        <div class="w-20 3xl:w-32 h-20 3xl:h-32 bg-white/20 rounded-3xl 3xl:rounded-[48px] flex items-center justify-center animate-pulse">
                            <i class="fas fa-ticket-alt text-3xl 3xl:text-5xl"></i>
                        </div>
                        <div>
                            <h2 class="text-3xl 3xl:text-5xl font-black font-heading mb-1 tracking-tight">Active Ticket Found</h2>
                            <p class="text-primary-100 font-medium 3xl:text-2xl">You currently have a ticket (<?php echo $currentTicket['ticket_number']; ?>). You can only have one active ticket at a time.</p>
                        </div>
                    </div>
                    <a href="my-ticket.php" class="bg-white text-primary-600 px-10 3xl:px-16 py-5 3xl:py-8 rounded-[22px] 3xl:rounded-[32px] font-black text-lg 3xl:text-2xl hover:scale-105 transition-all shadow-xl active:scale-95 flex items-center space-x-3">
                        <span>View My Ticket</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="absolute -right-10 top-0 text-[120px] 3xl:text-[200px] font-black text-white/10 select-none pointer-events-none uppercase tracking-tighter">ACTIVE</div>
            </div>
        <?php else: ?>
            
            <!-- Walk-in Content -->
        <div id="walkinContent">
            <?php if ($error): ?>
                <div class="p-6 3xl:p-10 mb-10 text-primary-800 bg-primary-50 rounded-3xl 3xl:rounded-[32px] border border-primary-100 flex items-center shadow-premium" role="alert">
                    <i class="fas fa-exclamation-triangle mr-4 text-2xl 3xl:text-4xl"></i>
                    <span class="font-bold text-lg 3xl:text-3xl"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 3xl:grid-cols-5 gap-6">
                <?php $counter = 1; foreach ($services as $service): 
                    $isAvailable = $service['staff_enabled_count'] > 0;
                ?>
                    <div class="bg-white rounded-[24px] md:rounded-[32px] 3xl:rounded-[48px] shadow-division border border-white <?php echo $isAvailable ? 'hover:shadow-premium hover:-translate-y-1.5' : 'grayscale opacity-60 cursor-not-allowed'; ?> transition-all duration-500 overflow-hidden flex flex-col h-full group relative">
                        <?php if (!$isAvailable): ?>
                            <div class="absolute top-4 right-4 z-20">
                                <span class="bg-rose-100 text-rose-600 text-[8px] md:text-[10px] font-black px-3 py-1 rounded-full border border-rose-200 uppercase tracking-widest shadow-sm">
                                    <i class="fas fa-plug-circle-xmark mr-1"></i>Offline
                                </span>
                            </div>
                        <?php endif; ?>
                        <!-- Card Header -->
                        <div class="px-5 md:px-8 pt-6 md:pt-8 3xl:pt-10 pb-2 md:pb-3 relative overflow-hidden">
                            <div class="bg-primary-600 w-10 md:w-12 h-10 md:h-12 3xl:w-16 3xl:h-16 rounded-lg md:rounded-xl 3xl:rounded-[24px] flex items-center justify-center text-white shadow-lg shadow-primary-100 mb-3 md:mb-4 group-hover:rotate-6 transition-transform">
                                <span class="text-sm md:text-lg 3xl:text-2xl font-black"><?php echo $counter; ?></span>
                            </div>
                            <h3 class="text-md md:text-xl 3xl:text-2xl font-black text-gray-900 font-heading leading-tight tracking-tight"><?php echo $service['service_name']; ?></h3>
                            
                            <!-- BG Abstract Text -->
                            <div class="absolute -right-2 top-0 text-5xl md:text-6xl 3xl:text-8xl font-black text-slate-50 opacity-50 select-none pointer-events-none"><?php echo $counter; ?></div>
                        </div>
                        
                        <!-- Card Content Wrapper -->
                        <div class="px-5 md:px-8 flex flex-col flex-grow">
                            <div class="flex-grow space-y-4 pt-4 border-t border-slate-50">
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-clipboard-check text-primary-600 mt-1 3xl:text-xl text-[10px]"></i>
                                    <div class="flex-1">
                                        <p class="text-[8px] 3xl:text-xs font-black text-gray-400 uppercase tracking-widest leading-none mb-1.5">Requirements</p>
                                        <div class="space-y-1 3xl:space-y-2">
                                            <?php 
                                            $reqs = preg_split('/[,\n\r]+/', $service['requirements']);
                                            foreach ($reqs as $req): 
                                                $req = trim($req);
                                                if (empty($req)) continue;
                                            ?>
                                                <div class="flex items-start space-x-2 text-[10px] md:text-xs 3xl:text-base font-bold text-gray-700">
                                                    <i class="fas fa-check-circle text-primary-500 mt-0.5 text-[8px] 3xl:text-xs"></i>
                                                    <span><?php echo htmlspecialchars($req); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-bolt text-amber-500 text-[10px] 3xl:text-xl"></i>
                                    <div>
                                        <p class="text-[8px] 3xl:text-xs font-black text-gray-400 uppercase tracking-widest leading-none mb-0.5">Target Time</p>
                                        <p class="text-[10px] md:text-xs 3xl:text-lg font-black text-gray-900"><?php echo formatMinutes($service['target_time']); ?></p>
                                    </div>
                                </div>

                                <?php if ($service['service_code'] === 'GEN-INQ'): ?>
                                    <div class="pt-4 border-t border-slate-50">
                                        <label for="note-<?php echo $service['id']; ?>" class="block text-[8px] md:text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2">Note (Optional)</label>
                                        <textarea id="note-<?php echo $service['id']; ?>" form="form-<?php echo $service['id']; ?>" name="user_note" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-[10px] font-medium focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all resize-none" placeholder="Please briefly specify your concern..." rows="2"></textarea>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Footer -->
                            <form id="form-<?php echo $service['id']; ?>" method="POST" action="" class="pb-8 md:pb-10 pt-6">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <button type="submit" <?php echo !$isAvailable ? 'disabled' : ''; ?> class="w-full <?php echo $isAvailable ? 'bg-slate-900 hover:bg-black shadow-slate-200' : 'bg-slate-300 cursor-not-allowed'; ?> text-white py-3 md:py-4 3xl:py-6 rounded-lg md:rounded-xl 3xl:rounded-2xl font-black text-xs md:text-sm 3xl:text-xl shadow-lg transition-all active:scale-95 flex items-center justify-center space-x-2">
                                    <span><?php echo $isAvailable ? 'Get Ticket' : 'Currently Unavailable'; ?></span>
                                    <i class="fas <?php echo $isAvailable ? 'fa-ticket-alt' : 'fa-clock-rotate-left opacity-50'; ?>"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php $counter++; endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/chatbot-widget.php'; ?>
    <script src="<?php echo BASE_URL; ?>/js/notifications.js"></script>
</body>
</html>
