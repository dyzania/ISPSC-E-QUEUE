<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Ticket.php';
require_once __DIR__ . '/../../models/Window.php';
require_once __DIR__ . '/../../models/Service.php';

requireLogin();
requireRole('user');

$ticketModel = new Ticket();
$windowModel = new Window();

$currentTicket = $ticketModel->getCurrentTicket(getUserId());
$pendingFeedback = $ticketModel->getPendingFeedbackTicket(getUserId());
$activeWindows = $windowModel->getActiveWindows();
$waitingTickets = $ticketModel->getWaitingQueue();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
    <script src="../js/dashboard-refresh.js"></script>
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>

    <main class="container-ultra px-4 md:px-10 py-8 4xl:py-20" id="dashboard-content-container">
        <!-- Welcome Header -->
        <div class="mb-6 md:mb-10 4xl:mb-20 flex flex-col md:flex-row md:items-center justify-between gap-4 md:gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl 3xl:text-6xl 5xl:text-8xl font-black text-gray-900 mb-1 font-heading tracking-tight leading-none">Live Dashboard</h1>
                <p class="text-gray-500 font-medium text-xs md:text-sm 3xl:text-xl 5xl:text-3xl">Real-time status of services and windows.</p>
            </div>
            
            <?php if (!$currentTicket): ?>
            <a href="get-ticket.php" class="bg-primary-600 text-white px-6 md:px-8 py-2 md:py-4 3xl:px-12 5xl:px-20 3xl:py-6 5xl:py-10 rounded-xl md:rounded-2xl 3xl:rounded-[32px] 5xl:rounded-[48px] font-black shadow-primary-premium hover:bg-primary-700 hover:-translate-y-1 transition-all flex items-center justify-center space-x-2 md:space-x-3 group text-sm md:text-lg 3xl:text-2xl 5xl:text-4xl">
                <i class="fas fa-plus-circle text-base md:text-xl 3xl:text-3xl 5xl:text-5xl group-hover:rotate-90 transition-transform duration-300"></i>
                <span>Get New Ticket</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Notification Banner if being called -->
        <?php if ($currentTicket && ($currentTicket['status'] === 'called' || $currentTicket['status'] === 'serving')): ?>
            <!-- Notification Banner for Called/Serving -->
            <div class="mb-6 4xl:mb-20 bg-slate-900 rounded-[1.5rem] md:rounded-[2rem] 5xl:rounded-[60px] shadow-premium overflow-hidden">
                <div class="bg-indigo-600/10 backdrop-blur-xl p-3 md:p-6 3xl:p-10 5xl:p-20 flex flex-col md:flex-row items-center md:justify-between gap-3 md:gap-6 relative text-left">
                    <div class="flex flex-row items-center space-x-3 md:space-x-6 3xl:space-x-10 5xl:space-x-20 relative z-10 w-full md:w-auto">
                        <div class="w-10 h-10 md:w-16 3xl:w-24 5xl:w-40 bg-indigo-600 rounded-lg md:rounded-2xl 3xl:rounded-[32px] 5xl:rounded-[48px] flex items-center justify-center animate-bounce shadow-lg shadow-indigo-500/20 shrink-0">
                            <i class="fas fa-bullhorn text-white text-base md:text-2xl 3xl:text-4xl 5xl:text-7xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg md:text-2xl 3xl:text-3xl 5xl:text-5xl font-black text-white font-heading leading-tight mb-0.5 md:mb-0 uppercase tracking-tight">Being Called</h2>
                            <p class="text-indigo-200 font-bold text-[10px] md:text-base 3xl:text-xl 5xl:text-3xl">
                                <?php if ($currentTicket['window_number']): ?>
                                <span class="bg-white text-indigo-600 px-2 md:px-3 5xl:px-6 py-0.5 md:py-1 5xl:py-2 rounded md:rounded-lg 5xl:rounded-2xl font-black text-[10px] md:text-base uppercase"><?php echo $currentTicket['window_number']; ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <a href="my-ticket.php" class="w-full md:w-auto bg-white text-slate-900 px-5 md:px-8 py-2.5 md:py-4 rounded-lg md:rounded-2xl font-black hover:bg-slate-50 transition-all relative z-10 flex items-center justify-center shadow-lg text-xs md:text-lg whitespace-nowrap">
                        <span>View Ticket</span>
                        <i class="fas fa-arrow-right ml-2 md:ml-3"></i>
                    </a>
                </div>
            </div>
        <?php elseif ($pendingFeedback): ?>
            <!-- Notification Banner for Feedback -->
            <div class="mb-6 4xl:mb-20 bg-amber-500 rounded-[1.5rem] md:rounded-[2rem] 5xl:rounded-[60px] shadow-premium overflow-hidden">
                <div class="bg-white/10 backdrop-blur-xl p-3 md:p-6 3xl:p-10 5xl:p-20 flex flex-col md:flex-row items-center justify-between gap-4 relative">
                    <div class="flex items-center space-x-3 md:space-x-6 relative z-10">
                        <div class="w-10 md:w-16 h-10 md:h-16 bg-white rounded-lg md:rounded-2xl flex items-center justify-center animate-bounce shadow-lg shadow-amber-600/20">
                            <i class="fas fa-star text-amber-500 text-base md:text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-lg md:text-2xl font-black text-white font-heading leading-tight">Thank You!</h2>
                            <p class="text-amber-100 font-bold text-[10px] md:text-base">
                                Ticket <span class="bg-white text-amber-600 px-1.5 py-0.5 rounded font-black"><?php echo $pendingFeedback['ticket_number']; ?></span> is complete.
                            </p>
                        </div>
                    </div>
                    <a href="my-ticket.php" class="bg-slate-900 text-white px-5 md:px-8 py-2.5 md:py-4 rounded-lg md:rounded-2xl font-black hover:bg-black transition-all relative z-10 flex items-center shadow-lg text-xs md:text-lg">
                        <span>Give Feedback</span>
                        <i class="fas fa-comment-dots ml-2 md:ml-3"></i>
                    </a>
                </div>
            </div>
        <?php elseif ($currentTicket): ?>
            <div class="mb-6 4xl:mb-20 bg-white border border-primary-100 rounded-[1.5rem] md:rounded-[2rem] 5xl:rounded-[60px] p-3 md:p-6 3xl:p-10 5xl:p-20 shadow-premium flex flex-col md:flex-row items-center justify-between gap-4 overflow-hidden relative group">
                <div class="flex items-center space-x-3 md:space-x-6 3xl:space-x-10 5xl:space-x-20 min-w-0 flex-1 w-full md:w-auto">
                    <div class="w-8 md:w-14 h-8 md:h-14 bg-primary-50 rounded md:rounded-2xl flex items-center justify-center group-hover:bg-primary-100 transition-colors shrink-0">
                        <i class="fas fa-clock text-primary-600 text-sm md:text-xl"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[8px] md:text-[10px] 3xl:text-sm 5xl:text-xl font-black uppercase tracking-[0.2em] text-primary-600 mb-0.5 md:mb-1 leading-none">Your Active Ticket</p>
                        <h2 class="text-base md:text-xl 3xl:text-3xl 5xl:text-5xl font-black text-gray-900 font-heading leading-tight truncate">
                            <?php echo $currentTicket['ticket_number']; ?>
                        </h2>
                    </div>
                </div>
                <div class="flex items-center space-x-3 md:space-x-8 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 border-gray-50 pt-3 md:pt-0">
                    <div class="text-left md:text-right">
                        <p class="text-[8px] md:text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-0.5">Status</p>
                        <p class="text-[10px] md:text-sm font-black text-primary-600 uppercase">Waiting in Line</p>
                    </div>
                    <a href="my-ticket.php" class="bg-primary-50 text-primary-700 hover:bg-primary-600 hover:text-white px-4 md:px-5 py-2 md:py-3 rounded md:rounded-2xl transition-all duration-300 shadow-sm font-black text-[10px] md:text-base uppercase whitespace-nowrap">
                        View Position <i class="fas fa-chevron-right ml-1 md:ml-2 text-[10px] md:text-[10px] 3xl:text-sm 5xl:text-2xl"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row gap-10 5xl:gap-20">
            <!-- Windows Section -->
            <div class="w-full lg:w-[65%] space-y-8 5xl:space-y-16">
                <div class="flex items-center justify-between border-b border-gray-100 5xl:pb-10">
                    <h3 class="text-2xl 3xl:text-4xl 5xl:text-6xl font-black text-gray-900 font-heading">Service Windows</h3>
                    <span class="px-4 py-1.5 5xl:px-8 5xl:py-3 bg-green-100 text-green-700 rounded-full text-xs 3xl:text-sm 5xl:text-2xl font-black tracking-widest uppercase flex items-center">
                        <span class="w-1.5 h-1.5 5xl:w-4 5xl:h-4 bg-green-500 rounded-full mr-2 5xl:mr-4 animate-pulse"></span>
                        <?php echo count($activeWindows); ?> Active
                    </span>
                </div>

                <?php if (empty($activeWindows)): ?>
                    <div class="bg-white rounded-3xl 5xl:rounded-[64px] p-12 3xl:p-20 5xl:p-40 text-center border-2 border-dashed border-gray-100 shadow-sm">
                        <div class="w-20 3xl:w-32 5xl:w-60 h-20 3xl:h-32 5xl:h-60 bg-gray-50 rounded-3xl 3xl:rounded-[48px] 5xl:rounded-[80px] flex items-center justify-center mx-auto mb-6 5xl:mb-12">
                            <i class="fas fa-door-closed text-gray-300 text-3xl 3xl:text-5xl 5xl:text-8xl"></i>
                        </div>
                        <h4 class="text-xl 3xl:text-3xl 5xl:text-5xl font-bold text-gray-400 mb-2">Windows Closed</h4>
                        <p class="text-gray-400 max-w-xs 3xl:max-w-xl 5xl:max-w-4xl mx-auto text-sm 3xl:text-lg 5xl:text-3xl">Service windows are currently closed. Please check back during business hours.</p>
                    </div>
                <?php else: ?>
                    <div id="active-windows-container" class="flex overflow-x-auto pb-4 snap-x snap-mandatory gap-4 -mx-4 px-4 md:mx-0 md:px-0 md:grid md:grid-cols-2 xl:grid-cols-3 5xl:grid-cols-4 md:gap-6 5xl:gap-12 scrollbar-hide">
                        <?php foreach ($activeWindows as $window): ?>
                            <div class="min-w-[85%] md:min-w-0 snap-center bg-white rounded-[32px] 5xl:rounded-[60px] p-6 3xl:p-10 5xl:p-16 shadow-division border border-gray-50 hover:shadow-premium hover:-translate-y-1 transition-all duration-300 group">
                                <div class="flex items-start justify-between mb-8 5xl:mb-16">
                                    <div class="flex items-center space-x-4 3xl:space-x-6 5xl:space-x-10">
                                        <div class="w-14 3xl:w-20 5xl:w-32 h-14 3xl:h-24 5xl:h-40 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-2xl 3xl:rounded-[32px] 5xl:rounded-[48px] flex items-center justify-center text-white shadow-lg shadow-indigo-100 group-hover:rotate-6 transition-transform relative overflow-hidden">
                                            <span class="text-xl 3xl:text-4xl 5xl:text-6xl font-black relative z-10"><?php echo str_replace('W-', '', $window['window_number']); ?></span>
                                            <div class="absolute inset-0 bg-white/10 group-hover:scale-150 transition-transform duration-700"></div>
                                        </div>
                                        <div>
                                            <h4 class="text-lg 3xl:text-3xl 5xl:text-5xl font-black text-gray-900 tracking-tight leading-none mb-1 5xl:mb-4"><?php echo $window['window_name']; ?></h4>
                                            <p class="text-[10px] 3xl:text-sm 5xl:text-2xl font-bold text-gray-400 uppercase tracking-[0.2em]"><?php echo $window['window_number']; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="px-3 5xl:px-6 py-1 5xl:py-2 bg-green-50 text-green-600 rounded-lg 5xl:rounded-xl text-[10px] 3xl:text-xs 5xl:text-xl font-black tracking-widest uppercase mb-2">
                                            Online
                                        </span>
                                    </div>
                                </div>

                                <!-- Now Serving Display -->
                                <div class="bg-slate-50 rounded-2xl 3xl:rounded-[24px] 5xl:rounded-[40px] p-5 3xl:p-6 5xl:p-10 mb-6 5xl:mb-12 border border-slate-100 group-hover:bg-slate-900 group-hover:border-slate-800 transition-all duration-500">
                                    <p class="text-[9px] 3xl:text-xs 5xl:text-lg font-black text-gray-400 uppercase tracking-[0.3em] mb-2 group-hover:text-slate-500 transition-colors">Now Serving</p>
                                    <div class="serving-ticket-display">
                                    <?php if ($window['serving_ticket']): ?>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xl 3xl:text-3xl 5xl:text-5xl font-black text-gray-900 font-heading group-hover:text-white transition-colors"><?php echo $window['serving_ticket']; ?></span>
                                            <span class="flex h-3 w-3 3xl:h-4 3xl:w-4 5xl:h-8 5xl:w-8 relative">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 3xl:h-4 3xl:w-4 5xl:h-8 5xl:w-8 bg-primary-500"></span>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center justify-between opacity-40">
                                            <span class="text-xl 3xl:text-3xl 5xl:text-5xl font-black text-gray-400 font-heading group-hover:text-slate-600 transition-colors">Counter Idle</span>
                                            <i class="fas fa-moon text-gray-300 text-lg 3xl:text-2xl 5xl:text-4xl group-hover:text-slate-700 transition-colors"></i>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                </div>

                                <div class="pt-6 5xl:pt-10 border-t border-gray-50 flex items-center space-x-3 3xl:space-x-5 5xl:space-x-10">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($window['staff_name']); ?>&background=eff6ff&color=1e40af&font-weight=bold" class="w-8 3xl:w-14 5xl:w-24 h-8 3xl:h-14 5xl:h-24 rounded-lg 3xl:rounded-2xl 5xl:rounded-[32px]" alt="">
                                    <div class="flex-1 overflow-hidden">
                                        <p class="text-[10px] 3xl:text-xs 5xl:text-xl font-black text-gray-300 uppercase tracking-widest leading-none mb-1">Counter Staff</p>
                                        <p class="text-sm 3xl:text-xl 5xl:text-3xl font-bold text-gray-700 truncate"><?php echo $window['staff_name']; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Queue List Section -->
            <div class="w-full lg:w-[35%] space-y-8 5xl:space-y-16">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 5xl:pb-10">
                    <h3 class="text-2xl 5xl:text-5xl font-black text-gray-900 font-heading">Queue Waitlist</h3>
                    <i class="fas fa-sync-alt text-gray-300 5xl:text-4xl animate-spin-slow cursor-pointer hover:text-primary-600 transition-colors"></i>
                </div>

                <div class="bg-white rounded-[40px] 5xl:rounded-[64px] shadow-premium border border-gray-50 overflow-hidden">
                    <div id="queueTableBody" class="divide-y divide-gray-50">
                        <?php if (empty($waitingTickets)): ?>
                            <div class="p-12 5xl:p-32 text-center py-20 5xl:py-40">
                                <div class="w-16 h-16 5xl:w-32 5xl:h-32 bg-gray-50 rounded-2xl 5xl:rounded-[48px] flex items-center justify-center mx-auto mb-4 5xl:mb-10 text-gray-300">
                                    <i class="fas fa-mug-hot text-2xl 5xl:text-5xl"></i>
                                </div>
                                <p class="font-black text-gray-300 tracking-widest uppercase text-xs 5xl:text-3xl">Queue is Empty</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $globalAvgTime = $ticketModel->getGlobalDailyAverageProcessTime();
                            foreach ($waitingTickets as $index => $ticket): 
                                $individualWait = $index * $globalAvgTime;
                                $isOwnTicket = $currentTicket && $ticket['id'] === $currentTicket['id'];
                            ?>
                                <div class="p-4 md:p-6 5xl:p-14 flex items-center justify-between hover:bg-gray-50 transition-colors group <?php echo $isOwnTicket ? 'bg-primary-50/50 border-l-4 border-primary-600' : ''; ?>">
                                    <div class="flex items-center space-x-4 md:space-x-6 5xl:space-x-12 min-w-0 flex-1">
                                        <div class="w-12 md:w-14 5xl:w-24 h-12 md:h-14 5xl:h-24 shrink-0">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($ticket['user_name']); ?>&background=<?php echo $isOwnTicket ? '15803d' : '0f172a'; ?>&color=fff&font-size=0.35&bold=true" 
                                                 class="w-full h-full rounded-2xl 5xl:rounded-[32px] shadow-sm border border-slate-100 group-hover:scale-105 transition-transform" 
                                                 alt="">
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center space-x-2">
                                                <p class="font-black text-gray-900 text-sm md:text-base 5xl:text-3xl leading-none mb-1 truncate"><?php echo $ticket['ticket_number']; ?></p>
                                                <?php if($isOwnTicket): ?>
                                                    <span class="bg-primary-600 text-white text-[8px] 5xl:text-lg font-black px-1.5 py-0.5 rounded uppercase tracking-tighter">YOU</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[10px] 5xl:text-xl font-bold text-gray-400 uppercase tracking-wider truncate"><?php echo $ticket['service_name']; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0 ml-4">
                                        <span class="px-2 5xl:px-4 py-0.5 5xl:py-2 bg-primary-50 text-primary-600 rounded-lg 5xl:rounded-2xl text-[9px] md:text-[10px] 5xl:text-xl font-black uppercase tracking-wider mb-1">
                                            Waiting
                                        </span>
                                        <p class="text-[9px] 5xl:text-lg font-bold text-gray-600 tracking-tight flex items-center">
                                            <i class="fas fa-history mr-1 opacity-30 text-[8px]"></i>
                                            ~<?php echo $individualWait; ?>m wait
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <style>
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 8s linear infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animate-gradient {
            background-size: 200% auto;
            animation: gradient 3s linear infinite;
        }
        /* Mobile Carousel Scrollbar Hide */
        .scrollbar-hide::-webkit-scrollbar {
            display: none !important;
        }
        .scrollbar-hide {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
    </style>

    <script>
        // Initialize Real-Time Auto-Refresh for everything
        // This replaces all old manual timers with a robust, mobile-resilient segment refresh
        new DashboardRefresh(['dashboard-content-container'], 10000);
    </script>
    

    <?php include __DIR__ . '/../../includes/chatbot-widget.php'; ?>
    <script src="../js/notifications.js"></script>
</body>
</html>
