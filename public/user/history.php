<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Ticket.php';

requireLogin();
requireRole('user');

$ticketModel = new Ticket();
$history = $ticketModel->getUserTicketHistory(getUserId());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
</head>
<body class="min-h-screen pb-20">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>

    <main class="container-ultra px-4 md:px-10 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h3 class="text-3xl md:text-4xl font-black text-gray-900 font-heading tracking-tight">Transaction History</h3>
                    <p class="text-gray-500 font-medium">A complete record of your past visits and tickets.</p>
                </div>
                <div class="px-6 py-2 bg-slate-900 text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-slate-200">
                    <?php echo count($history); ?> Sessions
                </div>
            </div>

            <?php if (empty($history)): ?>
                <div class="bg-white rounded-[40px] p-20 text-center border border-slate-100 shadow-premium">
                    <div class="w-24 h-24 bg-slate-50 rounded-[32px] flex items-center justify-center mx-auto mb-8">
                        <i class="fas fa-history text-slate-200 text-4xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-gray-900 mb-2 font-heading">No history yet</h2>
                    <p class="text-gray-400 font-medium mb-8">Your past tickets will appear here once completed.</p>
                    <a href="get-ticket.php" class="inline-flex items-center space-x-2 text-primary-600 font-black uppercase tracking-widest text-sm hover:text-primary-700 transition-colors">
                        <span>Get your first ticket</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-8">
                    <?php foreach ($history as $pastTicket): ?>
                        <div class="bg-white rounded-[24px] md:rounded-[40px] p-4 md:p-10 shadow-division border border-slate-50 hover:shadow-premium hover:-translate-y-1 transition-all duration-500 group relative overflow-hidden flex flex-col h-full">
                            
                            <div class="absolute -right-2 -bottom-2 md:-right-6 md:-bottom-6 text-[60px] md:text-[140px] font-black group-hover:<?php echo $pastTicket['status'] === 'completed' ? 'text-emerald-50/40' : 'text-red-50/40'; ?> text-slate-50 transition-colors pointer-events-none z-0">
                                <i class="fas fa-receipt"></i>
                            </div>

                            <div class="relative z-10 flex flex-col h-full">
                                <!-- Top Row: Code and Status -->
                                <div class="flex items-start justify-between mb-3 md:mb-6">
                                    <div class="flex items-center space-x-3 md:space-x-4 min-w-0 flex-1">
                                        <div class="w-12 h-12 md:w-16 md:h-16 shrink-0 <?php echo $pastTicket['status'] === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'; ?> rounded-[18px] md:rounded-[24px] flex items-center justify-center text-xs md:text-xl font-black shadow-sm group-hover:scale-105 group-hover:rotate-3 transition-all duration-500">
                                            <?php echo $pastTicket['service_code']; ?>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-base md:text-2xl font-black text-gray-900 tracking-tight leading-none mb-1 md:mb-1.5 truncate"><?php echo $pastTicket['ticket_number']; ?></h4>
                                            <p class="text-[9px] md:text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center truncate">
                                                <i class="far fa-calendar-alt mr-1.5 md:mr-2"></i>
                                                <?php echo date('M d, Y • H:i', strtotime($pastTicket['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="px-2 py-0.5 md:px-4 md:py-1.5 <?php echo $pastTicket['status'] === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'; ?> rounded-lg md:rounded-xl text-[7px] md:text-[10px] font-black uppercase tracking-widest shadow-sm">
                                            <?php echo $pastTicket['status']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Middle Section: Service and Duration -->
                                <div class="flex-grow bg-slate-50 rounded-[20px] md:rounded-[32px] p-3 md:p-6 border border-slate-100 mb-3 md:mb-6 group-hover:bg-slate-900 transition-all duration-500">
                                    <div class="flex flex-col h-full justify-between gap-2 md:gap-3">
                                        <div class="flex flex-col gap-0.5 md:gap-1">
                                            <span class="text-[7px] md:text-[11px] font-black <?php echo $pastTicket['status'] === 'completed' ? 'text-emerald-600 group-hover:text-emerald-400' : 'text-red-600 group-hover:text-red-400'; ?> uppercase tracking-widest">Service Area</span>
                                            <h5 class="text-[11px] md:text-base font-black text-gray-800 leading-tight group-hover:text-white transition-colors"><?php echo $pastTicket['service_name']; ?></h5>
                                        </div>
                                        
                                        <?php if ($pastTicket['processing_time']): ?>
                                            <div class="flex items-center justify-between pt-1.5 md:pt-3 border-t border-slate-200 group-hover:border-slate-800">
                                                <span class="text-[8px] md:text-xs font-bold text-gray-400 group-hover:text-slate-500 uppercase tracking-wider">Duration</span>
                                                <span class="text-[10px] md:text-base font-black text-gray-900 group-hover:text-white"><?php echo $pastTicket['processing_time']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Bottom Row: Window and Success Indicator -->
                                <div class="flex items-center justify-between pt-2.5 md:pt-4 border-t border-slate-100/50">
                                    <div class="flex items-center space-x-2 md:space-x-3">
                                        <div class="w-6 h-6 md:w-8 md:h-8 rounded-full bg-slate-100 flex items-center justify-center group-hover:bg-slate-800 transition-colors">
                                            <i class="fas fa-desktop text-[8px] md:text-[10px] text-slate-400 group-hover:text-slate-500"></i>
                                        </div>
                                        <span class="text-[10px] md:text-sm font-black text-gray-500 group-hover:text-gray-400">Win <?php echo $pastTicket['window_number'] ?: '--'; ?></span>
                                    </div>
                                    
                                    <div class="shrink-0">
                                        <?php if ($pastTicket['status'] === 'completed'): ?>
                                            <div class="w-6 h-6 md:w-10 md:h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center shadow-lg shadow-emerald-100/50">
                                                <i class="fas fa-check text-[8px] md:text-sm"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="w-6 h-6 md:w-10 md:h-10 bg-red-100 text-red-600 rounded-full flex items-center justify-center shadow-lg shadow-red-100/50">
                                                <i class="fas fa-times text-[8px] md:text-sm"></i>
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
    </main>

    <?php include __DIR__ . '/../../includes/chatbot-widget.php'; ?>
    <script src="<?php echo BASE_URL; ?>/js/notifications.js"></script>
</body>
</html>
