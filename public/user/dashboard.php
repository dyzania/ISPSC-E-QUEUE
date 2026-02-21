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

$now = time();
$currentTicket = $ticketModel->getCurrentTicket(getUserId());
$pendingFeedback = $ticketModel->getPendingFeedbackTicket(getUserId());
$activeWindows = $windowModel->getActiveWindows();
$waitingTickets = $ticketModel->getWaitingQueue();

// Helper function to format duration in seconds to "Xh Ym Zs"
function formatDuration($seconds) {
    if ($seconds <= 0) return "0s";
    
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    
    $parts = [];
    if ($h > 0) $parts[] = $h . "h";
    if ($m > 0) $parts[] = $m . "m";
    if ($s > 0 || empty($parts)) $parts[] = $s . "s";
    
    return implode(" ", $parts);
}
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
    <script src="<?php echo BASE_URL; ?>/js/dashboard-refresh.js"></script>
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>

    <main class="container-ultra px-4 md:px-10 py-8 4xl:py-20">
        <div id="header-sync">
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
        </div>

        <div id="notification-sync">
            <?php 
            $bannerTicket = null;
            $bannerType = ''; 
            
            if ($pendingFeedback) {
                $bannerTicket = $pendingFeedback;
                $bannerType = 'completed';
                $accentBase = 'amber';
                $statusText = 'Completed';
                $icon = 'fa-star';
                $actionLabel = 'Give Feedback';
                $actionIcon = 'fa-comment-dots';
                $actionUrl = 'my-ticket.php';
            } elseif ($currentTicket) {
                $bannerTicket = $currentTicket;
                $bannerType = $currentTicket['status'];
                $accentBase = 'primary';
                $actionUrl = 'my-ticket.php';
                $actionIcon = 'fa-chevron-right';
                $actionLabel = 'View Position';
                
                if ($bannerType === 'called') {
                    $statusText = 'You are being called';
                    $icon = 'fa-bullhorn animate-bounce';
                } elseif ($bannerType === 'serving') {
                    $statusText = 'Now Serving';
                    $icon = 'fa-concierge-bell';
                } else {
                    $statusText = 'Waiting in Line';
                    $icon = 'fa-clock';
                }
            }

            if ($bannerTicket): 
                $bgClass = ($accentBase === 'amber') ? "bg-white border border-amber-100" : "bg-white border border-primary-100";
                $iconBg = ($accentBase === 'amber') ? "bg-amber-50 shadow-lg shadow-amber-100" : "bg-primary-50 shadow-lg shadow-primary-100";
                $textAccent = ($accentBase === 'amber') ? "text-amber-500" : "text-primary-600";
                $btnClass = ($accentBase === 'amber') ? "bg-amber-500 text-white hover:bg-amber-600 shadow-amber-200" : "bg-primary-50 text-primary-700 hover:bg-primary-600 hover:text-white shadow-primary-100";
            ?>
                <!-- Unified Ticket Status Banner -->
                <div class="mb-6 4xl:mb-20 <?php echo $bgClass; ?> rounded-[1.5rem] md:rounded-[2rem] 5xl:rounded-[60px] p-4 md:p-6 3xl:p-10 5xl:p-20 shadow-premium flex flex-col gap-6 overflow-hidden relative group">
                    <!-- Top Section: Identity (Icon | Label + Number) -->
                    <div class="flex items-center space-x-4 md:space-x-6 3xl:space-x-10 5xl:space-x-20">
                        <div class="w-12 h-12 md:w-16 3xl:w-24 5xl:w-40 <?php echo $iconBg; ?> rounded-xl md:rounded-2xl 3xl:rounded-[32px] 5xl:rounded-[48px] flex items-center justify-center shrink-0">
                            <i class="fas <?php echo $icon; ?> <?php echo $textAccent; ?> text-lg md:text-2xl 3xl:text-4xl 5xl:text-7xl"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] md:text-xs 3xl:text-sm 5xl:text-xl font-black uppercase tracking-[0.2em] text-gray-400 mb-1 leading-none">Your Active Ticket</p>
                            <h2 class="text-xl md:text-2xl 3xl:text-4xl 5xl:text-7xl font-black text-gray-900 font-heading leading-tight truncate">
                                <?php echo $bannerTicket['ticket_number']; ?>
                            </h2>
                        </div>
                    </div>

                    <!-- Bottom Section: Status (Left) & Action (Right) -->
                    <div class="flex items-center justify-between border-t border-gray-50 pt-4 md:pt-8">
                        <div>
                            <p class="text-[8px] md:text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">Status</p>
                            <p class="text-[11px] md:text-sm 3xl:text-base 5xl:text-3xl font-black <?php echo $textAccent; ?> uppercase tracking-tight">
                                <?php echo $statusText; ?>
                            </p>
                        </div>
                        <a href="<?php echo $actionUrl; ?>" class="<?php echo $btnClass; ?> px-4 md:px-6 py-2.5 md:py-4 rounded-xl md:rounded-2xl transition-all duration-300 shadow-division font-black text-[10px] md:text-base 3xl:text-xl 5xl:text-3xl uppercase whitespace-nowrap flex items-center">
                            <?php echo $actionLabel; ?> <i class="fas <?php echo $actionIcon; ?> ml-2 text-[8px] md:text-sm 3xl:text-lg 5xl:text-2xl"></i>
                        </a>
                    </div>
                </div>

            <?php endif; ?>
        </div>

        <div class="flex flex-col gap-10 5xl:gap-20">
            <!-- Windows Section -->
            <div class="w-full space-y-8 5xl:space-y-16">
                <div class="flex items-center justify-between border-b border-gray-100 5xl:pb-10">
                    <h3 class="text-2xl 3xl:text-4xl 5xl:text-6xl font-black text-gray-900 font-heading">Service Windows</h3>
                    <div id="window-count-sync">
                        <span class="px-4 py-1.5 5xl:px-8 5xl:py-3 bg-green-100 text-green-700 rounded-full text-xs 3xl:text-sm 5xl:text-2xl font-black tracking-widest uppercase flex items-center">
                            <span class="w-1.5 h-1.5 5xl:w-4 5xl:h-4 bg-green-500 rounded-full mr-2 5xl:mr-4 animate-pulse"></span>
                            <?php echo count($activeWindows); ?> Active
                        </span>
                    </div>
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
                    <!-- Hidden Sync Source for Real-time Updates -->
                    <div id="window-sync-source" class="hidden">
                        <?php foreach ($activeWindows as $window): ?>
                            <div class="sync-item" 
                                 data-id="<?php echo $window['id']; ?>" 
                                 data-active="1" 
                                 data-ticket="<?php echo $window['serving_ticket']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="window-container" class="flex flex-nowrap gap-4 5xl:gap-12 w-full overflow-x-auto scrollbar-hide py-4 -mx-4 px-4 md:mx-0 md:px-0 scroll-smooth cursor-grab active:cursor-grabbing">
                        <?php foreach ($activeWindows as $window): ?>
                            <div class="window-item flex-none w-[280px] md:w-[320px] lg:w-[calc(25%-12px)] 5xl:w-[calc(25%-36px)]" data-window-id="<?php echo $window['id']; ?>">
                                    <div class="window-card bg-white rounded-[32px] 5xl:rounded-[60px] p-6 3xl:p-10 5xl:p-16 shadow-division border border-gray-50 hover:shadow-premium transition-all duration-300 group h-full">
                                        <div class="flex items-start justify-between mb-8 5xl:mb-16">
                                            <div class="flex items-center space-x-4 3xl:space-x-6 5xl:space-x-10">
                                                <div class="w-14 3xl:w-20 5xl:w-32 h-14 3xl:h-24 5xl:h-40 bg-primary-600 rounded-2xl 3xl:rounded-[32px] 5xl:rounded-[48px] flex items-center justify-center text-white shadow-lg shadow-primary-900/20 group-hover:rotate-6 transition-transform relative overflow-hidden">
                                                    <span class="text-xl 3xl:text-4xl 5xl:text-6xl font-black relative z-10"><?php echo str_replace('W-', '', $window['window_number']); ?></span>
                                                    <div class="absolute inset-0 bg-white/10 group-hover:scale-150 transition-transform duration-700"></div>
                                                </div>
                                                <div>
                                                    <h4 class="text-lg 3xl:text-3xl 5xl:text-5xl font-black text-gray-900 tracking-tight leading-none mb-1 5xl:mb-4"><?php echo $window['window_name']; ?></h4>
                                                    <p class="text-[10px] 3xl:text-sm 5xl:text-2xl font-bold text-gray-400 uppercase tracking-[0.2em]"><?php echo $window['window_number']; ?></p>
                                                </div>
                                            </div>
                                            <div class="flex flex-col items-end">
                                                <span class="window-status-badge px-3 5xl:px-6 py-1 5xl:py-2 bg-green-50 text-green-600 rounded-lg 5xl:rounded-xl text-[10px] 3xl:text-xs 5xl:text-xl font-black tracking-widest uppercase mb-2">
                                                    Online
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Now Serving Display -->
                                        <?php 
                                        $isServing = !empty($window['serving_ticket']);
                                        $containerClasses = $isServing 
                                            ? 'bg-secondary-700 shadow-inner' 
                                            : 'bg-secondary-200/70';
                                        $labelClasses = $isServing ? 'text-white/50' : 'text-secondary-900/40';
                                        ?>
                                        <div class="serving-container <?php echo $containerClasses; ?> rounded-2xl 3xl:rounded-[24px] 5xl:rounded-[40px] p-5 3xl:p-6 5xl:p-10 mb-2 border border-transparent transition-all duration-500">
                                            <p class="serving-label text-[9px] 3xl:text-xs 5xl:text-lg font-black <?php echo $labelClasses; ?> uppercase tracking-[0.3em] mb-2 transition-colors">Now Serving</p>
                                            <div class="serving-ticket-display">
                                            <?php if ($isServing): ?>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xl 3xl:text-3xl 5xl:text-5xl font-black text-white font-heading transition-colors"><?php echo $window['serving_ticket']; ?></span>
                                                    <span class="flex h-3 w-3 3xl:h-4 3xl:w-4 5xl:h-8 5xl:w-8 relative">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-40"></span>
                                                        <span class="relative inline-flex rounded-full h-3 w-3 3xl:h-4 3xl:w-4 5xl:h-8 5xl:w-8 bg-white"></span>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xl 3xl:text-3xl 5xl:text-5xl font-black text-secondary-900/40 font-heading transition-colors">Counter Idle</span>
                                                    <i class="fas fa-moon text-secondary-300 text-lg 3xl:text-2xl 5xl:text-4xl transition-colors"></i>
                                                </div>
                                            <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>

            <!-- Queue List Section -->
            <div class="w-full space-y-8 5xl:space-y-16" id="waitlist-sync">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 5xl:pb-10">
                    <h3 class="text-2xl 5xl:text-5xl font-black text-gray-900 font-heading">Queue Waitlist</h3>
                    <i class="fas fa-sync-alt text-gray-300 5xl:text-4xl animate-spin-slow cursor-pointer hover:text-primary-600 transition-colors"></i>
                </div>
                <!-- Rest of waitlist unchanged -->

                <div class="bg-white rounded-[40px] 5xl:rounded-[64px] shadow-premium border border-gray-50 overflow-hidden group/waitlist">
                    <div id="queueTableBody" class="grid grid-cols-1 lg:grid-cols-2 lg:divide-x lg:divide-slate-100/80">
                        <?php if (empty($waitingTickets)): ?>
                            <div class="lg:col-span-2 p-12 5xl:p-32 text-center py-20 5xl:py-40">
                                <div class="w-16 h-16 5xl:w-32 5xl:h-32 bg-gray-50 rounded-2xl 5xl:rounded-[48px] flex items-center justify-center mx-auto mb-4 5xl:mb-10 text-gray-300">
                                    <i class="fas fa-mug-hot text-2xl 5xl:text-5xl"></i>
                                </div>
                                <p class="font-black text-gray-300 tracking-widest uppercase text-xs 5xl:text-3xl">Queue is Empty</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            foreach ($waitingTickets as $ticket): 
                                $globalPos = $ticketModel->getGlobalQueuePosition($ticket['id']);
                                $estWaitSeconds = $ticketModel->getAdvancedEstimatedWaitTime($ticket['id'], $now);
                                $estWaitFormatted = formatDuration(round($estWaitSeconds));
                                $isOwnTicket = $currentTicket && $ticket['id'] === $currentTicket['id'];
                            ?>
                                <?php 
                                    $itemClasses = "p-4 md:p-6 5xl:p-14 flex items-center justify-between transition-all duration-300 group/item border-b border-slate-50 ";
                                    if ($isOwnTicket) {
                                        $itemClasses .= "bg-primary-50/30 border-l-4 border-primary-500 shadow-[inset_0_0_30px_rgba(37,99,235,0.05)] relative z-10 scale-[1.01] rounded-sm ring-1 ring-primary-100/50";
                                    } else {
                                        $itemClasses .= "hover:bg-slate-50/80";
                                    }
                                ?>
                                <div class="<?php echo $itemClasses; ?>">
                                    <div class="flex items-center space-x-4 md:space-x-6 5xl:space-x-12 min-w-0 flex-1">
                                        <div class="w-12 md:w-14 5xl:w-24 h-12 md:h-14 5xl:h-24 shrink-0 relative">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($ticket['user_name']); ?>&background=<?php echo $isOwnTicket ? '15803d' : '0f172a'; ?>&color=fff&font-size=0.35&bold=true" 
                                                 class="w-full h-full rounded-2xl 5xl:rounded-[32px] shadow-sm border border-slate-100 group-hover:scale-105 transition-transform" 
                                                 alt="">
                                            <div class="absolute -top-1 -right-1 bg-primary-600 text-white w-5 h-5 5xl:w-10 5xl:h-10 rounded-full flex items-center justify-center text-[8px] 5xl:text-lg font-black border-2 border-white shadow-sm">
                                                <?php echo $globalPos; ?>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center space-x-2">
                                                <p class="font-black text-gray-900 text-sm md:text-base 5xl:text-3xl leading-none mb-1 truncate"><?php echo $ticket['ticket_number']; ?></p>
                                                <?php if($isOwnTicket): ?>
                                                    <span class="bg-primary-600 text-[8px] 5xl:text-lg font-black px-2 py-0.5 rounded-full text-white tracking-widest flex items-center shadow-sm">
                                                        <span class="w-1 h-1 bg-white rounded-full mr-1.5 animate-pulse"></span>
                                                        YOU
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[10px] 5xl:text-xl font-bold text-gray-400 uppercase tracking-wider truncate"><?php echo $ticket['service_name']; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0 ml-4">
                                        <span class="px-2 5xl:px-4 py-0.5 5xl:py-2 bg-primary-50 text-primary-600 rounded-lg 5xl:rounded-2xl text-[9px] md:text-[10px] 5xl:text-xl font-black uppercase tracking-wider mb-1">
                                            Waiting
                                        </span>
                                        <p class="text-[9px] 5xl:text-lg font-bold text-gray-600 tracking-tight flex items-center italic"
                                           data-live-countdown="1"
                                           data-ticket-id="<?php echo $ticket['id']; ?>"
                                           data-target-timestamp="<?php echo (int)round(($now + $estWaitSeconds) * 1000); ?>"
                                           data-server-now="<?php echo $now * 1000; ?>">
                                            <i class="fas fa-history mr-1 opacity-30 text-[8px]"></i>
                                            <?php echo $estWaitFormatted; ?>
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
    function syncWindowsData() {
        const syncSource = document.getElementById('window-sync-source');
        if (!syncSource) return;

        const syncItems = syncSource.querySelectorAll('.sync-item');
        const container = document.getElementById('window-container');
        if (!container) return;

        syncItems.forEach(item => {
            const id = item.dataset.id;
            const ticket = item.dataset.ticket;
            
            const windowItems = container.querySelectorAll(`.window-item[data-window-id="${id}"]`);
            
            windowItems.forEach(slide => {
                const display = slide.querySelector('.serving-ticket-display');
                const servingContainer = slide.querySelector('.serving-container');
                const servingLabel = slide.querySelector('.serving-label');
                const isServing = !!ticket;

                if (servingContainer) {
                    const baseContainer = "serving-container rounded-2xl 3xl:rounded-[24px] 5xl:rounded-[40px] p-5 3xl:p-6 5xl:p-10 mb-2 border border-transparent transition-all duration-500";
                    servingContainer.className = isServing 
                        ? `${baseContainer} bg-secondary-700 shadow-inner`
                        : `${baseContainer} bg-secondary-200/70`;
                }

                if (servingLabel) {
                    servingLabel.className = `serving-label text-[9px] 3xl:text-xs 5xl:text-lg font-black uppercase tracking-[0.3em] mb-2 transition-colors ${isServing ? 'text-white/50' : 'text-secondary-900/40'}`;
                }

                const badge = slide.querySelector('.window-status-badge');
                if (badge) {
                    badge.className = `window-status-badge px-3 5xl:px-6 py-1 5xl:py-2 bg-green-50 text-green-600 rounded-lg 5xl:rounded-xl text-[10px] 3xl:text-xs 5xl:text-xl font-black tracking-widest uppercase mb-2`;
                }

                if (display) {
                    let html = '';
                    if (isServing) {
                        html = `
                            <div class="flex items-center justify-between">
                                <span class="text-xl 3xl:text-3xl 5xl:text-5xl font-black text-white font-heading transition-colors">${ticket}</span>
                                <span class="flex h-3 w-3 3xl:h-4 3xl:w-4 5xl:h-8 5xl:w-8 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-40"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 3xl:h-4 3xl:w-4 5xl:h-8 5xl:w-8 bg-white"></span>
                                </span>
                            </div>`;
                    } else {
                        html = `
                            <div class="flex items-center justify-between">
                                <span class="text-xl 3xl:text-3xl 5xl:text-5xl font-black text-secondary-900/40 font-heading transition-colors">Counter Idle</span>
                                <i class="fas fa-moon text-secondary-900/40 text-lg 3xl:text-2xl 5xl:text-4xl transition-colors"></i>
                            </div>`;
                    }
                    if (display.innerHTML !== html) display.innerHTML = html;
                }
            });
        });
    }

    // Initialize Real-Time Auto-Refresh for specific stable segments
    new DashboardRefresh(['header-sync', 'notification-sync', 'window-count-sync', 'waitlist-sync', 'window-sync-source'], 3000);

    document.addEventListener('dashboard:updated', (e) => {
        if (e.detail.id === 'window-sync-source') {
            syncWindowsData();
        }
    });

    function initDragScroll() {
        const container = document.getElementById('window-container');
        if (!container) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        container.addEventListener('mousedown', (e) => {
            isDown = true;
            container.classList.add('active');
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
        });

        container.addEventListener('mouseleave', () => {
            isDown = false;
            container.classList.remove('active');
        });

        container.addEventListener('mouseup', () => {
            isDown = false;
            container.classList.remove('active');
        });

        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2; // scroll-fast
            container.scrollLeft = scrollLeft - walk;
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        syncWindowsData();
        initDragScroll();
    });
    </script>
    <script src="<?php echo BASE_URL; ?>/js/live-countdown.js"></script>
    

    <?php include __DIR__ . '/../../includes/chatbot-widget.php'; ?>
    <script src="<?php echo BASE_URL; ?>/js/notifications.js"></script>
</body>
</html>
