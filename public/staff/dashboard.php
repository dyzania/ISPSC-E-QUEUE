<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Ticket.php';
require_once __DIR__ . '/../../models/Window.php';

requireLogin();
requireRole('staff');

$ticketModel = new Ticket();
$windowModel = new Window();

$window = $windowModel->getWindowByStaff(getUserId());
$servingTicket = null;
$waitingTickets = [];

if ($window) {
    // Automatically set window as active and enable all services ONLY ONCE per session
    // This allows manual changes to persist after refreshing the page
    if (!isset($_SESSION['window_initialized'])) {
        $windowModel->setWindowStatus($window['id'], true);
        $windowModel->enableAllServices($window['id']);
        $_SESSION['window_initialized'] = true;
    }
    
    $activeTickets = $ticketModel->getActiveTicketsByWindow($window['id']);
    $archivedTickets = $ticketModel->getArchivedTicketsByWindow($window['id']);
    $waitingTickets = $ticketModel->getWaitingQueueForWindow($window['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Counter - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    <script src="../js/dashboard-refresh.js"></script>
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/staff-navbar.php'; ?>

    <main class="container-ultra max-w-[2000px] mx-auto py-8 px-4 md:px-10">
        <?php if (!$window): ?>
            <div class="max-w-2xl mx-auto mt-20 text-center bg-white p-12 rounded-2xl shadow-2xl shadow-slate-200 border border-slate-100">
                <div class="w-24 h-24 bg-rose-50 rounded-xl flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-exclamation-triangle text-rose-500 text-4xl"></i>
                </div>
                <h2 class="text-3xl font-black text-gray-900 mb-4 font-heading">No Window Assigned</h2>
                <p class="text-gray-500 text-lg">You are not currently assigned to any service window. Please contact your administrator to get started.</p>
            </div>
        <?php else: ?>
            
            <!-- Compact Header -->
            <div class="bg-white rounded-2xl p-6 5xl:p-12 shadow-xl shadow-slate-200/50 border border-white mb-8 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                <div class="flex items-center gap-6 5xl:gap-12 relative z-10">
                    <div class="w-16 5xl:w-32 h-16 5xl:h-32 bg-slate-900 rounded-2xl 5xl:rounded-[48px] flex items-center justify-center text-white font-black text-2xl 5xl:text-5xl shadow-lg">
                        <?php echo $window['window_number']; ?>
                    </div>
                    <div>
                        <p class="text-xs 5xl:text-xl font-bold text-gray-400 uppercase tracking-widest mb-1 5xl:mb-4">Operational Counter</p>
                        <h2 class="text-2xl 5xl:text-5xl font-black text-gray-900 leading-none"><?php echo $window['window_name']; ?></h2>
                    </div>
                </div>

                <div class="flex items-center gap-6 5xl:gap-12 relative z-10">
                    <div class="flex flex-col items-end">
                        <p class="text-[10px] 5xl:text-xl font-black text-gray-400 uppercase tracking-widest mb-2">Service Status</p>
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-black uppercase tracking-wider <?php echo $window['is_active'] ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                <?php echo $window['is_active'] ? 'Online' : 'On Break'; ?>
                            </span>
                            <button onclick="toggleBreakMode(<?php echo $window['id']; ?>, <?php echo $window['is_active']; ?>, this)" 
                                    class="relative inline-flex h-10 w-20 5xl:h-20 5xl:w-40 items-center rounded-full transition-all duration-300 focus:outline-none shadow-inner <?php echo $window['is_active'] ? 'bg-emerald-500' : 'bg-rose-500'; ?>">
                                <span class="sr-only">Toggle Online Status</span>
                                <span class="inline-block h-8 w-8 5xl:h-16 5xl:w-16 transform rounded-full bg-white shadow-xl transition-all duration-300 transform <?php echo $window['is_active'] ? 'translate-x-11 5xl:translate-x-22' : 'translate-x-1'; ?>"></span>
                                <div class="absolute inset-0 flex items-center justify-between px-3 5xl:px-6 pointer-events-none">
                                    <i class="fas fa-check text-[10px] 5xl:text-2xl text-white <?php echo $window['is_active'] ? 'opacity-100' : 'opacity-0'; ?> transition-opacity"></i>
                                    <i class="fas fa-coffee text-[10px] 5xl:text-2xl text-white <?php echo !$window['is_active'] ? 'opacity-100' : 'opacity-0'; ?> transition-opacity"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Decorative BG -->
                <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-slate-50 to-transparent opacity-50 pointer-events-none"></div>
            </div>

            <!-- Performance Snapshot Row -->
            <div id="performance-snapshot-container">
                <?php $staffStats = $ticketModel->getStaffDailyStats(getUserId()); ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Served Today</p>
                            <h4 class="text-2xl font-black text-slate-900"><?php echo $staffStats['total_served']; ?></h4>
                        </div>
                        <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                            <i class="fas fa-check-double text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Avg. Speed</p>
                            <h4 class="text-2xl font-black text-slate-900"><?php echo $staffStats['avg_processing_time']; ?></h4>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                            <i class="fas fa-bolt text-xl"></i>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Customer Mood</p>
                            <h4 class="text-2xl font-black text-slate-900"><?php echo $staffStats['avg_rating']; ?> <span class="text-xs text-slate-400">/ 5</span></h4>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-500">
                            <i class="fas fa-smile text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsive 12-Column Grid Layout -->
            <div class="grid grid-cols-1 md:grid-cols-12 xl:grid-cols-[20%_25%_52%] gap-8">
                
                <!-- Column 1: Archived / On Hold (20%) -->
                <div class="md:col-span-5 xl:col-auto space-y-6" id="archived-tickets-container">
                     <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl 5xl:text-4xl font-black text-gray-900 font-heading">Archived</h3>
                        <span class="px-3 5xl:px-6 py-1 5xl:py-3 bg-amber-100 text-amber-700 rounded-lg 5xl:rounded-2xl text-xs 5xl:text-2xl font-black uppercase tracking-wide"><?php echo count($archivedTickets); ?> On Hold</span>
                    </div>

                    <div class="space-y-4 5xl:space-y-10">
                        <?php if (empty($archivedTickets)): ?>
                            <div class="bg-slate-50 rounded-2xl p-10 text-center border-2 border-dashed border-slate-200">
                                <p class="text-sm 5xl:text-3xl font-bold text-gray-400">No archived tickets</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($archivedTickets as $ticket): ?>
                            <div class="bg-white p-5 5xl:p-12 rounded-2xl shadow-sm border border-slate-100 flex flex-col gap-4 5xl:gap-10">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4 5xl:gap-10">
                                        <div class="w-12 5xl:w-24 h-12 5xl:h-24 bg-amber-50 rounded-2xl 5xl:rounded-[32px] flex items-center justify-center font-black text-sm 5xl:text-3xl text-amber-600">
                                            <?php echo $ticket['ticket_number']; ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm 5xl:text-3xl"><?php echo $ticket['user_name']; ?></p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <i class="fas fa-clock text-[10px] 5xl:text-2xl text-amber-500"></i>
                                                <span class="text-[10px] 5xl:text-2xl font-bold text-amber-600 elapsed-timer" data-seconds="<?php echo $ticket['elapsed_seconds']; ?>">0s</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1 block">Internal Notes</label>
                                    <textarea id="staff-notes-<?php echo $ticket['id']; ?>" 
                                              class="w-full bg-slate-50 border-slate-100 rounded-xl p-3 text-[10px] font-medium focus:ring-primary-500 focus:border-primary-500 transition-all"
                                              placeholder="Enter any internal notes about this transaction..." rows="2"><?php echo $ticket['staff_notes'] ?? 'Your document is ready to be received'; ?></textarea>
                                </div>
                                
                                <button onclick="completeTicket(<?php echo $ticket['id']; ?>, this)" class="w-full py-3 5xl:py-8 bg-slate-50 hover:bg-indigo-50 text-indigo-600 font-bold rounded-xl 5xl:rounded-[32px] transition-colors text-xs 5xl:text-2xl flex items-center justify-center gap-2 5xl:gap-6">
                                    <i class="fas fa-check"></i> Complete Service
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 2: Upcoming (Waitlist) (25%) -->
                <div class="md:col-span-7 xl:col-auto space-y-6" id="waiting-tickets-container">
                     <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl 5xl:text-4xl font-black text-gray-900 font-heading">Upcoming</h3>
                        <span class="px-3 5xl:px-6 py-1 5xl:py-3 bg-slate-100 text-slate-500 rounded-lg 5xl:rounded-2xl text-xs 5xl:text-2xl font-black uppercase tracking-wide"><?php echo count($waitingTickets); ?> Waiting</span>
                    </div>

                    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-white overflow-hidden min-h-[500px]">
                        <?php if (empty($waitingTickets)): ?>
                            <div class="flex flex-col items-center justify-center h-full p-10 5xl:p-20 text-center opacity-50">
                                <i class="fas fa-coffee text-4xl 5xl:text-8xl mb-4 5xl:mb-10 text-slate-300"></i>
                                <p class="font-bold 5xl:text-4xl text-slate-400">Queue is empty</p>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-slate-50">
                                <?php foreach (array_slice($waitingTickets, 0, 8) as $ticket): ?>
                                <div class="p-6 5xl:p-14 flex items-center justify-between hover:bg-gradient-to-r hover:from-slate-50 hover:to-transparent transition-all duration-150 group">
                                    <div class="flex items-center gap-5 5xl:gap-12 flex-1">
                                        <div class="w-14 5xl:w-28 h-14 5xl:h-28 bg-gradient-to-br from-slate-100 to-slate-200 rounded-2xl 5xl:rounded-[36px] flex items-center justify-center font-black text-sm 5xl:text-4xl text-slate-600 group-hover:from-slate-900 group-hover:to-slate-800 group-hover:text-white group-hover:shadow-xl group-hover:shadow-slate-300 transition-all duration-200 relative overflow-hidden">
                                            <span class="relative z-10"><?php echo $ticket['ticket_number']; ?></span>
                                            <div class="absolute inset-0 bg-white/10 group-hover:scale-150 transition-transform duration-400"></div>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-black text-gray-900 text-base 5xl:text-3xl mb-1 tracking-tight"><?php echo $ticket['service_name']; ?></p>
                                            <p class="text-xs 5xl:text-2xl font-bold text-gray-500 uppercase tracking-wider"><?php echo $ticket['user_name']; ?></p>
                                        </div>
                                    </div>
                                    <div class="w-3 5xl:w-6 h-3 5xl:h-6 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 shadow-lg shadow-indigo-200 group-hover:scale-125 transition-transform duration-200"></div>
                                </div>
                                <?php endforeach; ?>
                                <?php if(count($waitingTickets) > 8): ?>
                                    <div class="p-4 5xl:p-10 text-center text-xs 5xl:text-2xl font-bold text-gray-400 bg-slate-50">
                                        + <?php echo count($waitingTickets) - 8; ?> more
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 3: Active Transaction (55%) -->
                <div class="md:col-span-12 xl:col-auto space-y-6" id="active-transaction-container">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl 5xl:text-4xl font-black text-gray-900 font-heading">Active Transaction</h3>
                        <?php if (!empty($activeTickets)): ?>
                            <span class="px-3 5xl:px-6 py-1 5xl:py-3 bg-indigo-100 text-indigo-700 rounded-lg 5xl:rounded-2xl text-xs 5xl:text-2xl font-black uppercase tracking-wide">Serving</span>
                        <?php else: ?>
                            <span class="px-3 5xl:px-6 py-1 5xl:py-3 bg-slate-100 text-slate-500 rounded-lg 5xl:rounded-2xl text-xs 5xl:text-2xl font-black uppercase tracking-wide">Idle</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($activeTickets)): ?>
                        <?php foreach ($activeTickets as $ticket): ?>
                        <div class="bg-white rounded-2xl overflow-hidden shadow-2xl shadow-indigo-100 border border-white relative group">
                            <!-- Card Header -->
                            <div class="bg-slate-900 p-8 5xl:p-20 text-white relative overflow-hidden">
                                <div class="relative z-10 text-center">
                                    <p class="text-xs 5xl:text-2xl font-black uppercase tracking-[0.3em] opacity-50 mb-4 5xl:mb-10">Current Ticket</p>
                                    <div class="text-6xl 5xl:text-9xl font-black font-heading mb-2 group-hover:scale-110 transition-transform duration-300"><?php echo $ticket['ticket_number']; ?></div>
                                    <div class="inline-block px-4 py-1 5xl:px-10 5xl:py-4 bg-white/10 rounded-full text-xs 5xl:text-3xl font-bold border border-white/10 backdrop-blur-sm mt-4">
                                        <?php echo strtoupper($ticket['status']); ?>
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-transparent"></div>
                                <div class="absolute -right-6 -bottom-6 text-9xl 5xl:text-[20rem] text-white opacity-5 rotate-12 select-none font-black"><?php echo $ticket['ticket_number']; ?></div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-8 5xl:p-20">
                                <div class="text-center mb-8 5xl:mb-20">
                                    <p class="text-xs 5xl:text-2xl font-bold text-gray-400 uppercase tracking-widest mb-2 5xl:mb-6">Customer Name</p>
                                    <h4 class="text-2xl 5xl:text-6xl font-black text-gray-900 leading-tight mb-1"><?php echo $ticket['user_name']; ?></h4>
                                    <p class="text-sm 5xl:text-3xl font-bold text-indigo-600"><?php echo $ticket['service_name']; ?></p>
                                    
                                    <?php if (!empty($ticket['service_notes'])): ?>
                                        <div class="mt-4 5xl:mt-10 bg-amber-50 border border-amber-200 rounded-xl p-4 5xl:p-10 text-left">
                                            <p class="text-[10px] 5xl:text-xl font-black text-amber-500 uppercase tracking-wider mb-1 5xl:mb-4"><i class="fas fa-info-circle mr-1"></i> Special Instructions</p>
                                            <p class="text-xs 5xl:text-2xl font-medium text-amber-800 leading-relaxed"><?php echo nl2br(htmlspecialchars($ticket['service_notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($ticket['user_note'])): ?>
                                        <div class="mt-4 5xl:mt-10 bg-blue-50 border border-blue-200 rounded-xl p-4 5xl:p-10 text-left">
                                            <p class="text-[10px] 5xl:text-xl font-black text-blue-500 uppercase tracking-wider mb-1 5xl:mb-4"><i class="fas fa-comment-alt mr-1"></i> Customer Note</p>
                                            <p class="text-xs 5xl:text-2xl font-medium text-blue-800 leading-relaxed"><?php echo nl2br(htmlspecialchars($ticket['user_note'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($ticket['status'] === 'serving'): ?>
                                    <div class="mb-8">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 block">Internal Transaction Notes</label>
                                        <textarea id="staff-notes-<?php echo $ticket['id']; ?>" 
                                                  class="w-full bg-slate-50 border-slate-100 rounded-2xl p-4 text-sm font-medium focus:ring-primary-500 focus:border-primary-500 transition-all"
                                                  placeholder="Enter any internal notes about this transaction..." rows="3"><?php echo $ticket['staff_notes'] ?? 'Your document is ready to be received'; ?></textarea>
                                    </div>
                                <?php endif; ?>

                                <div class="space-y-3 5xl:space-y-8">
                                    <?php if ($ticket['status'] === 'called'): ?>
                                        <button type="button" onclick="startServing(<?php echo $ticket['id']; ?>, this)" class="relative z-10 w-full py-4 5xl:py-10 bg-indigo-600 text-white font-black rounded-2xl 5xl:rounded-[40px] shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all text-sm 5xl:text-4xl flex items-center justify-center gap-2 5xl:gap-6">
                                            <i class="fas fa-play"></i> Start Serving
                                        </button>
                                        <div class="flex gap-3 5xl:gap-8">
                                            <button type="button" onclick="cancelTicket(<?php echo $ticket['id']; ?>, this)" class="relative z-10 flex-1 py-4 5xl:py-10 bg-white border border-slate-200 text-slate-600 font-bold rounded-2xl 5xl:rounded-[40px] hover:bg-slate-50 transition-all text-sm 5xl:text-3xl">
                                                No Show
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" onclick="completeTicket(<?php echo $ticket['id']; ?>, this)" class="relative z-10 w-full py-4 5xl:py-10 bg-emerald-500 text-white font-black rounded-2xl 5xl:rounded-[40px] shadow-lg shadow-emerald-200 hover:bg-emerald-600 hover:-translate-y-0.5 transition-all text-sm 5xl:text-4xl flex items-center justify-center gap-2 5xl:gap-6">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" onclick="archiveTicket(<?php echo $ticket['id']; ?>, this)" class="relative z-10 w-full py-3 5xl:py-8 bg-amber-50 text-amber-600 font-bold rounded-xl hover:bg-amber-100 transition-all text-xs 5xl:text-2xl flex items-center justify-center gap-2 5xl:gap-6">
                                        <i class="fas fa-box-archive"></i> Move to Archive
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Call Next Mini-Button -->
                        <?php if ($window['is_active']): ?>
                            <button type="button" onclick="callNext(<?php echo $window['id']; ?>, this)" class="relative z-10 w-full py-4 5xl:py-10 border-2 border-dashed border-indigo-200 text-indigo-500 font-bold rounded-2xl hover:bg-indigo-50 hover:border-indigo-300 transition-all flex items-center justify-center gap-2 5xl:gap-6 text-sm 5xl:text-3xl">
                                <i class="fas fa-plus"></i> Call Another
                            </button>
                        <?php else: ?>
                            <button disabled class="w-full py-4 5xl:py-10 border-2 border-dashed border-slate-200 text-slate-400 font-bold rounded-2xl cursor-not-allowed flex items-center justify-center gap-2 5xl:gap-6 text-sm 5xl:text-3xl">
                                <i class="fas fa-ban"></i> Go Online to Call Only
                            </button>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="bg-white rounded-2xl p-8 5xl:p-32 text-center shadow-xl shadow-slate-200/50 border border-slate-100 py-20 5xl:py-40">
                            <div class="w-20 5xl:w-48 h-20 5xl:h-48 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6 5xl:mb-16 animate-pulse">
                                <i class="fas fa-bell text-indigo-500 text-2xl 5xl:text-7xl"></i>
                            </div>
                            <h3 class="text-xl 5xl:text-5xl font-black text-gray-900 mb-2 5xl:mb-10">Ready to Serve</h3>
                            <?php if ($window['is_active']): ?>
                                <button onclick="callNext(<?php echo $window['id']; ?>, this)" class="mt-6 5xl:mt-10 px-8 5xl:px-20 py-4 5xl:py-10 bg-indigo-600 text-white font-black rounded-2xl 5xl:rounded-[40px] shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all text-sm 5xl:text-4xl uppercase tracking-widest">
                                    Call Next Ticket
                                </button>
                            <?php else: ?>
                                <button disabled class="mt-6 5xl:mt-10 px-8 5xl:px-20 py-4 5xl:py-10 bg-slate-200 text-slate-400 font-black rounded-2xl 5xl:rounded-[40px] cursor-not-allowed text-sm 5xl:text-4xl uppercase tracking-widest">
                                    You are Offline
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <script>
        // Define Base URL for Notifications
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
    <script src="../js/notifications.js"></script>
    
    <script>
        function setLoading(btn, isLoading, showText = true) {
            // console.log('setLoading called', { btn, isLoading, showText });
            try {
                if (isLoading) {
                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    btn.style.cursor = 'not-allowed';
                    const originalText = btn.innerHTML;
                    btn.setAttribute('data-original-text', originalText);
                    if (showText) {
                        btn.innerHTML = `<i class="fas fa-circle-notch animate-spin mr-2"></i> Processing...`;
                    } else {
                        // Just add the spinner to the existing content if it's a toggle
                        btn.innerHTML = `<i class="fas fa-circle-notch animate-spin"></i>`;
                    }
                } else {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    btn.innerHTML = btn.getAttribute('data-original-text');
                }
            } catch (e) {
                console.error('Error in setLoading:', e);
                // alert('Error in UI update: ' + e.message);
            }
        }

        function toggleBreakMode(windowId, currentStatus, btn) {
            console.log('toggleBreakMode called', { windowId, currentStatus });
            try {
                const newStatus = currentStatus ? 0 : 1;
                setLoading(btn, true, false);
                
                fetch('../api/set-window-status.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ window_id: windowId, is_active: newStatus })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Notify and delay reload
                        document.dispatchEvent(new CustomEvent('equeue:toast', { 
                            detail: { 
                                type: 'success', 
                                title: newStatus ? 'Back Online' : 'On Break',
                                message: newStatus ? 'You are now visible to customers.' : 'Window set to break mode.'
                            } 
                        }));
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message || 'Error updating status', 'Status Error');
                    }
                })
                .catch(async err => {
                    console.error('toggleBreakMode error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in toggleBreakMode:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        function callNext(windowId, btn) {
            console.log('callNext called', { windowId, btn });
            try {
                setLoading(btn, true);
                fetch('../api/call-ticket.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ window_id: windowId })
                })
                .then(res => res.json())
                .then(data => {
                    console.log('callNext data:', data);
                    if (data.success) {
                        window.location.reload();
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message, 'Action Failed');
                    }
                })
                .catch(async err => {
                    console.error('callNext error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in callNext:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        function startServing(ticketId, btn) {
            console.log('startServing called', { ticketId });
            try {
                setLoading(btn, true);
                fetch('../api/start-serving.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ticket_id: ticketId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Notify and delay reload
                        document.dispatchEvent(new CustomEvent('equeue:toast', { 
                            detail: { type: 'serving', message: 'Service started' } 
                        }));
                        setTimeout(() => window.location.reload(), 3500);
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message || 'Error starting service', 'Action Failed');
                    }
                })
                .catch(async err => {
                    console.error('startServing error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in startServing:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        async function cancelTicket(ticketId, btn) {
            console.log('cancelTicket called', { ticketId });
            try {
                if (!await equeueConfirm('Are you sure you want to cancel this ticket (No Show)?', 'Confirm No-Show')) return;
                
                setLoading(btn, true);
                fetch('../api/cancel-ticket.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ticket_id: ticketId })
                })
                .then(res => res.json())
                .then(async data => {
                    if (data.success) {
                        // Notify and delay reload
                        document.dispatchEvent(new CustomEvent('equeue:toast', { 
                            detail: { type: 'cancelled', message: 'Ticket cancelled successfully' } 
                        }));
                        setTimeout(() => window.location.reload(), 3500);
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message || 'Error cancelling ticket', 'Action Failed');
                    }
                })
                .catch(async err => {
                    console.error('cancelTicket error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in cancelTicket:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        function completeTicket(ticketId, btn) {
            console.log('completeTicket called', { ticketId, btn });
            try {
                const notesEl = document.getElementById(`staff-notes-${ticketId}`);
                const notes = notesEl ? notesEl.value : '';
                
                setLoading(btn, true);
                fetch('../api/complete-ticket.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ticket_id: ticketId, notes: notes })
                })
                .then(res => {
                    console.log('completeTicket response status:', res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('completeTicket data:', data);
                    if (data.success) {
                        window.location.reload();
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message, 'Action Failed');
                    }
                })
                .catch(async err => {
                    console.error('completeTicket fetch error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Network error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in completeTicket:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        async function archiveTicket(ticketId, btn) {
            console.log('archiveTicket called', { ticketId, btn });
            try {
                if (!await equeueConfirm('Move this ticket to archive? Use this for long-running transactions.', 'Archive Ticket')) return;
                
                const notesEl = document.getElementById(`staff-notes-${ticketId}`);
                const notes = notesEl ? notesEl.value : '';

                setLoading(btn, true);
                fetch('../api/archive-ticket.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ticket_id: ticketId, notes: notes })
                })
                .then(res => res.json())
                .then(async data => {
                    console.log('archiveTicket data:', data);
                    if (data.success) {
                        // Notify and delay reload
                        document.dispatchEvent(new CustomEvent('equeue:toast', { 
                            detail: { type: 'success', message: 'Ticket moved to archive' } 
                        }));
                        setTimeout(() => window.location.reload(), 3500);
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message || 'Error archiving ticket', 'Action Failed');
                    }
                })
                .catch(async err => {
                    console.error('archiveTicket error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in archiveTicket:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        function resumeTicket(ticketId, btn) {
            console.log('resumeTicket called', { ticketId });
            try {
                setLoading(btn, true);
                fetch('../api/resume-ticket.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ticket_id: ticketId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Notify and delay reload
                        document.dispatchEvent(new CustomEvent('equeue:toast', { 
                            detail: { type: 'success', message: 'Ticket resumed successfully' } 
                        }));
                        setTimeout(() => window.location.reload(), 3500);
                    } else {
                        setLoading(btn, false);
                        await equeueAlert(data.message || 'Error resuming ticket', 'Action Failed');
                    }
                })
                .catch(async err => {
                    console.error('resumeTicket error:', err);
                    setLoading(btn, false);
                    await equeueAlert('Error: ' + err.message, 'Network Error');
                });
            } catch (e) {
                console.error('Error in resumeTicket:', e);
                equeueAlert('Script error: ' + e.message, 'System Error');
            }
        }

        // Live Timer for Archived Tickets
        setInterval(() => {
            document.querySelectorAll('.elapsed-timer').forEach(el => {
                let seconds = parseInt(el.getAttribute('data-seconds'));
                seconds++;
                el.setAttribute('data-seconds', seconds);
                
                // Format: Xd Xh Xm Xs
                let d = Math.floor(seconds / (3600*24));
                let h = Math.floor(seconds % (3600*24) / 3600);
                let m = Math.floor(seconds % 3600 / 60);
                let s = Math.floor(seconds % 60);
                
                let timeStr = '';
                if(d > 0) timeStr += d + 'd ';
                if(h > 0) timeStr += h + 'h ';
                if(m > 0 || h > 0 || d > 0) timeStr += m + 'm ';
                timeStr += s + 's';
                
                el.innerText = timeStr;
            });
        }, 1000);

        // Initialize Real-Time Auto-Refresh
        new DashboardRefresh([
            'performance-snapshot-container',
            'archived-tickets-container',
            'waiting-tickets-container',
            'active-transaction-container'
        ], 10000); // Refresh every 10 seconds

        // --- Staff Idle Wake-up Alert Logic ---
        let lastEmptyStartTime = null;
        const STAGNANT_THRESHOLD = 15 * 60; // 15 minutes in seconds
        
        // Notification Sound (Simple clean alert)
        const alertSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        alertSound.volume = 0.7;

        // Browsers block audio until first interaction
        document.addEventListener('mousedown', () => {
            if (alertSound.paused && alertSound.currentTime === 0) {
                // "Prime" the audio on first click
                alertSound.play().then(() => {
                    alertSound.pause();
                    alertSound.currentTime = 0;
                }).catch(() => {});
            }
        }, { once: true });

        function getWaitingTicketCount() {
            const container = document.getElementById('waiting-tickets-container');
            if (!container) return 0;
            // Count elements with data-ticket-id (assuming they exist) or just direct children that aren't empty placeholders
            // Based on staff/dashboard.php structure, waiting tickets are inside cards.
            // Let's count elements with class 'bg-white' which represents a ticket card in the column
            return container.querySelectorAll('.bg-white.rounded-3xl').length;
        }

        // Initialize state
        if (getWaitingTicketCount() === 0) {
            lastEmptyStartTime = Date.now();
        }

        document.addEventListener('dashboard:updated', (e) => {
            if (e.detail.id === 'waiting-tickets-container') {
                const currentCount = getWaitingTicketCount();
                const now = Date.now();

                if (currentCount > 0) {
                    if (lastEmptyStartTime !== null) {
                        const secondsIdle = (now - lastEmptyStartTime) / 1000;
                        // Trigger Notification via centralized system (Sound + Toast + Native)
                        if (secondsIdle >= STAGNANT_THRESHOLD) {
                            document.dispatchEvent(new CustomEvent('equeue:toast', { 
                                detail: { 
                                    type: 'turn_next', 
                                    title: 'WAKE UP! NEW TICKET!', 
                                    message: 'A new ticket has arrived after idle period!',
                                    native: true 
                                } 
                            }));
                        }
                        lastEmptyStartTime = null; // No longer empty
                    }
                } else {
                    // Queue is currently empty
                    if (lastEmptyStartTime === null) {
                        lastEmptyStartTime = now;
                    }
                }
            }
        });
    </script>
    <script src="../js/notifications.js"></script>
</body>
</html>
