<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Ticket.php';
require_once __DIR__ . '/../../models/Feedback.php';

requireLogin();
requireRole('user');

$ticketModel = new Ticket();
$feedbackModel = new Feedback();

$now = time();
$ticket = $ticketModel->getCurrentTicket(getUserId());

// Function to format duration in seconds to "Xh Ym Zs"
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

// If no active ticket, check for tickets needing feedback
if (!$ticket) {
    $ticket = $ticketModel->getPendingFeedbackTicket(getUserId());
}

$position = $ticket ? $ticketModel->getGlobalQueuePosition($ticket['id']) : 0;
$initialPosition = $ticket ? $ticketModel->getInitialQueuePosition($ticket['id']) : 0;

// Use new Advanced Wait Time calculation
$estimatedWaitSeconds = $ticket ? $ticketModel->getAdvancedEstimatedWaitTime($ticket['id'], $now) : 0;
$estimatedWait = formatDuration(round($estimatedWaitSeconds));

// Average Processing Time (APT)
$avgProcessSeconds = $ticket ? $ticketModel->getPreciseAverageProcessTime($ticket['service_id']) : null;

$isWaiting = $ticket && $ticket['status'] === 'waiting';
$isCalled = $ticket && $ticket['status'] === 'called';
$isServing = $ticket && $ticket['status'] === 'serving';
$isCompleted = $ticket && $ticket['status'] === 'completed';

// Calculate remaining time for serving state in PHP to prevent reset on refresh
$servingRemainingSeconds = null;
if ($isServing && $avgProcessSeconds && ($ticket['served_at'] ?? null)) {
    $servedAt = strtotime($ticket['served_at']);
    if ($servedAt) {
        $elapsed = time() - $servedAt;
        $servingRemainingSeconds = max(0, $avgProcessSeconds - $elapsed);
    }
}

$avgProcessTimeFormatted = $avgProcessSeconds ? formatDuration($avgProcessSeconds) : "";
$servingRemainingTimeFormatted = ($servingRemainingSeconds !== null) ? formatDuration($servingRemainingSeconds) : $avgProcessTimeFormatted;

$feedbackGiven = $ticket ? $feedbackModel->getFeedbackByTicket($ticket['id']) : null;
$history = $ticketModel->getUserTicketHistory(getUserId());

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    if ($ticket) {
        $comment = sanitize($_POST['comment']);
        
        if ($feedbackModel->createFeedback($ticket['id'], getUserId(), $ticket['window_id'], $comment)) {
            header('Location: my-ticket.php?success=1');
            exit;
        }
    } else {
        // Redirect or handle error if no ticket is found
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ticket - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
    <script src="<?php echo BASE_URL; ?>/js/dashboard-refresh.js"></script>
</head>
<body class="min-h-screen pb-20">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>

    <main class="container-ultra px-4 md:px-10 py-8 pb-20" id="ticket-main-content">
        <?php if (!$ticket): ?>
            <div class="max-w-2xl mx-auto mt-20 text-center bg-white p-12 3xl:p-20 rounded-[40px] 3xl:rounded-[56px] shadow-2xl shadow-slate-200 border border-slate-100">
                <div class="w-24 3xl:w-32 h-24 3xl:h-32 bg-primary-50 rounded-3xl 3xl:rounded-[48px] flex items-center justify-center mx-auto mb-8 animate-float shadow-division">
                    <i class="fas fa-ticket-alt text-primary-600 text-4xl 3xl:text-6xl opacity-40"></i>
                </div>
                <h2 class="text-3xl 3xl:text-5xl font-black text-gray-900 mb-4 font-heading tracking-tight">No Active Ticket</h2>
                <p class="text-gray-500 text-lg 3xl:text-2xl mb-10 max-w-sm 3xl:max-w-xl mx-auto">You don't have any tickets in the queue right now. Ready to start?</p>
                <a href="get-ticket.php" class="inline-block bg-slate-900 text-white px-12 3xl:px-16 py-5 3xl:py-8 rounded-3xl 3xl:rounded-[40px] font-black text-xl 3xl:text-3xl shadow-2xl shadow-slate-200 hover:bg-black hover:-translate-y-1 transition-all active:scale-95">
                    Explore Services
                </a>
            </div>
        <?php else: ?>
            
            <!-- Ticket Status Card -->
            <div class="bg-white rounded-[32px] md:rounded-[64px] p-2 shadow-ultra border border-slate-50 mb-8 md:mb-16">
                <div class="bg-slate-900 rounded-[28px] md:rounded-[56px] p-6 md:p-20 text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-6 md:gap-20">
                            <!-- Left: Number -->
                            <div class="text-center md:text-left">
                                <?php $isCompleted = ($ticket['status'] === 'completed'); ?>
                                <p class="text-[10px] md:text-base font-black uppercase tracking-[0.4em] <?php echo $isCompleted ? 'text-secondary-400' : 'text-primary-400'; ?> mb-2 md:mb-4">Queue Ticket</p>
                                <h2 class="text-4xl md:text-[8.5rem] font-black font-heading tracking-tighter leading-none">
                                    <?php echo $ticket['ticket_number']; ?>
                                </h2>
                                <div class="mt-4 md:mt-10 flex items-center justify-center md:justify-start space-x-3 md:space-x-6">
                                    <span class="w-2.5 h-2.5 md:w-5 md:h-5 rounded-full <?php echo $isCompleted ? 'bg-secondary-500' : 'bg-primary-500'; ?> animate-ping"></span>
                                    <span class="text-xl md:text-4xl font-black uppercase tracking-[0.2em] <?php echo $isCompleted ? 'text-secondary-300' : 'text-primary-300'; ?>">
                                        <?php 
                                            if ($isCompleted) echo "COMPLETED";
                                            elseif ($isCalled) echo "PROCEED";
                                            elseif ($isServing) echo "SERVING";
                                            else echo strtoupper($ticket['status']);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Middle: Divider -->
                            <div class="hidden md:block w-px h-32 md:h-72 bg-white/10 mx-6"></div>
                            
                            <!-- Right: Service Details -->
                            <div class="flex-1 w-full md:w-auto">
                                <h3 class="text-base md:text-3xl font-black font-heading mb-4 md:mb-8 leading-tight opacity-50 text-center md:text-left"><?php echo $ticket['service_name']; ?></h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:flex md:flex-col gap-4 md:gap-8">
                                    <div class="px-6 md:px-12 py-4 md:py-10 bg-white/5 rounded-[22px] md:rounded-[32px] border border-white/10 flex items-center space-x-5 md:space-x-8 backdrop-blur-xl group/box">
                                        <div class="w-12 h-12 md:w-24 md:h-24 bg-white/10 rounded-2xl flex items-center justify-center shrink-0 border border-white/10 group-hover/box:scale-110 transition-transform">
                                            <span class="text-white font-black text-xs md:text-3xl"><?php echo $ticket['service_code']; ?></span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[8px] md:text-sm font-black uppercase tracking-widest text-white opacity-60 mb-1 md:mb-2">
                                                <?php 
                                                    if ($isWaiting) echo "Queue Position";
                                                    elseif ($isCalled) echo "Queue Status";
                                                    elseif ($isServing) echo "Queue Status";
                                                    elseif ($isCompleted) echo "Started at Position";
                                                ?>
                                            </span>
                                            <div class="flex items-baseline space-x-3">
                                                <span class="text-2xl md:text-5xl font-black <?php echo ($isWaiting) ? 'text-amber-300' : 'text-white'; ?>">
                                                    <?php 
                                                        if ($isWaiting) echo '#' . ($position + 1);
                                                        elseif ($isCalled) echo "PROCEED";
                                                        elseif ($isServing) echo "SERVING";
                                                        elseif ($isCompleted) echo '#' . $initialPosition;
                                                    ?>
                                                </span>
                                                <?php if ($isWaiting): ?>
                                                    <span class="text-[10px] md:text-lg font-bold text-amber-300">
                                                        (<?php echo $position; ?> ahead)
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-6 md:px-12 py-4 md:py-10 bg-white/5 rounded-[22px] md:rounded-[32px] border border-white/10 flex items-center space-x-5 md:space-x-8 backdrop-blur-xl group/box">
                                        <i class="fas fa-clock text-white text-xl md:text-5xl shrink-0 group-hover/box:scale-110 transition-transform"></i>
                                        <div class="flex flex-col w-full">
                                            <div class="flex flex-col gap-3 md:gap-5">
                                                <div>
                                                    <span class="text-[8px] md:text-sm font-black uppercase tracking-widest text-white/60 mb-1 md:mb-2 block">
                                                        <?php echo $isCompleted ? "Total Waiting Time" : "Avg. Process Time"; ?>
                                                    </span>
                                                    <?php if ($isCompleted): ?>
                                                        <?php 
                                                            $compAt = ($ticket['completed_at'] ?? null) ? strtotime($ticket['completed_at']) : time();
                                                            $creatAt = ($ticket['created_at'] ?? null) ? strtotime($ticket['created_at']) : time();
                                                            $totalWaitSeconds = $compAt - $creatAt;
                                                            echo '<span class="text-[12px] md:text-2xl font-bold text-amber-200/80 tracking-tight leading-none block">' . formatDuration($totalWaitSeconds) . '</span>';
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-[12px] md:text-2xl font-bold text-amber-200/80 tracking-tight leading-none block">
                                                            <?php echo $avgProcessTimeFormatted ? "~$avgProcessTimeFormatted / person" : ""; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="pt-3 md:pt-5 border-t border-white/10">
                                                    <span class="text-[8px] md:text-sm font-black uppercase tracking-widest text-white mb-1 md:mb-2 block">
                                                        <?php 
                                                            if ($isServing) echo "Estimated Process Time";
                                                            elseif ($isCompleted) echo "Total Service Processed";
                                                            else echo "Estimated Waiting Time";
                                                        ?>
                                                    </span>
                                                    <span id="ticket-metric-secondary" 
                                                          class="text-2xl md:text-5xl font-black text-amber-300 leading-none block"
                                                          data-live-countdown="1"
                                                          data-ticket-id="<?php echo $ticket['id']; ?>"
                                                          <?php 
                                                            $targetTimestampMs = 0;
                                                            if ($isServing && $avgProcessSeconds && ($ticket['served_at'] ?? null)) {
                                                                $servedAt = strtotime($ticket['served_at']);
                                                                if ($servedAt) {
                                                                    $targetTimestampMs = ($servedAt + $avgProcessSeconds) * 1000;
                                                                }
                                                            } elseif (($isWaiting || $isCalled) && $estimatedWaitSeconds > 0) {
                                                                $targetTimestampMs = ($now + $estimatedWaitSeconds) * 1000;
                                                            }
                                                          ?>
                                                          <?php if ($targetTimestampMs > 0): ?>
                                                            data-target-timestamp="<?php echo (int)round($targetTimestampMs); ?>"
                                                            data-server-now="<?php echo $now * 1000; ?>"
                                                            <?php if ($isServing): ?>data-is-serving="1"<?php endif; ?>
                                                          <?php endif; ?>>
                                                        <?php 
                                                            if ($isServing) echo $servingRemainingTimeFormatted ?: "-";
                                                            elseif ($isCompleted) {
                                                                $compAt = ($ticket['completed_at'] ?? null) ? strtotime($ticket['completed_at']) : time();
                                                                $servAt = ($ticket['served_at'] ?? null) ? strtotime($ticket['served_at']) : (($ticket['called_at'] ?? null) ? strtotime($ticket['called_at']) : $compAt);
                                                                $totalServiceSeconds = $compAt - $servAt;
                                                                echo formatDuration($totalServiceSeconds);
                                                            }
                                                            else echo $estimatedWait;
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($ticket['status'] === 'called' || $ticket['status'] === 'serving'): ?>
                            <div class="mt-6 md:mt-16 p-0.5 md:p-1 bg-white/20 rounded-[24px] md:rounded-[32px]">
                                <div class="bg-indigo-600 p-4 md:p-12 rounded-[22px] md:rounded-[30px] flex md:flex-row flex-col items-center justify-center text-center md:text-left gap-4 md:gap-12 border border-white/20">
                                    <div class="w-12 h-12 md:w-32 md:h-32 bg-white rounded-full flex items-center justify-center animate-bounce shadow-xl shrink-0">
                                        <i class="fas fa-desktop text-xl md:text-5xl text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-[8px] md:text-2xl font-black uppercase tracking-[0.5em] text-indigo-200 mb-1 md:mb-4">WINDOW</p>
                                        <h4 class="text-3xl md:text-[8rem] font-black tracking-tighter font-heading text-white leading-none"><?php echo $ticket['window_number']; ?></h4>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Bar for Waiting Users -->
                <?php if ($ticket['status'] === 'waiting'): ?>
                    <div class="px-4 md:px-10 py-4 bg-slate-50 border-t border-slate-100 rounded-b-[24px] md:rounded-b-[48px] flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-2 text-slate-400">
                            <i class="fas fa-info-circle text-[10px] md:text-base"></i>
                            <span class="text-[8px] md:text-sm font-bold uppercase tracking-widest">Adjust sequence or cancel</span>
                        </div>
                        <div class="flex items-center space-x-3 w-full sm:w-auto">
                            <button onclick="snoozeTicket(<?php echo $ticket['id']; ?>)" class="flex-1 sm:flex-initial px-6 py-4 bg-white border border-slate-200 text-slate-600 rounded-xl font-black text-xs md:text-lg hover:bg-slate-100 transition-all flex items-center justify-center shadow-sm">
                                <i class="fas fa-hourglass-half mr-2 text-amber-500"></i>
                                Step Back
                            </button>
                            <button onclick="confirmCancel(<?php echo $ticket['id']; ?>)" class="flex-1 sm:flex-initial px-6 py-4 bg-red-50 border border-red-200 text-red-600 rounded-xl font-black text-xs md:text-lg hover:bg-red-100 transition-all flex items-center justify-center shadow-sm">
                                <i class="fas fa-times-circle mr-2"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Area -->
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-10">
                <?php if ($ticket['status'] === 'completed' && !$feedbackGiven): ?>
                    <div class="bg-white rounded-[48px] p-10 shadow-premium border border-slate-50 relative overflow-hidden group">
                        <div class="relative z-10">
                            <h3 class="text-3xl font-black text-gray-900 font-heading tracking-tight mb-2">How was your visit?</h3>
                            <p class="text-gray-500 font-medium mb-4 max-w-sm text-lg">We value your time. Let us know how we can improve our service.</p>
                            
                            <?php 
                            if ($ticket['status'] === 'completed' && ($ticket['completed_at'] ?? null)) {
                                $end = strtotime($ticket['completed_at']);
                                $start = ($ticket['served_at'] ?? null) ? strtotime($ticket['served_at']) : (($ticket['called_at'] ?? null) ? strtotime($ticket['called_at']) : $end);
                                
                                if ($start && $end) {
                                    $diff = $end - $start;
                                    $m = floor($diff / 60);
                                    $s = $diff % 60;
                                    $procTime = ($m > 0 ? "{$m}m " : "") . "{$s}s";
                                    echo "<div class='mb-6 inline-flex items-center px-4 py-1.5 bg-primary-50 text-primary-600 rounded-full text-xs font-black uppercase tracking-widest'>Total Service Time: $procTime</div>";
                                }
                            }
                            ?>

                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <div class="relative group">
                                    <textarea 
                                        name="comment" 
                                        required
                                        rows="4"
                                        class="w-full px-8 py-6 bg-slate-50 border border-slate-100 rounded-[32px] focus:outline-none focus:ring-4 focus:ring-primary-100 focus:bg-white focus:border-primary-500 transition-all font-medium text-gray-700 text-lg shadow-inner placeholder-gray-300"
                                        placeholder="Enter your experience here (e.g. Great and fast service!)"
                                    ></textarea>
                                    <div class="absolute top-6 right-8 text-primary-200 group-focus-within:text-primary-600 transition-colors">
                                        <i class="fas fa-pen-nib text-xl"></i>
                                    </div>
                                </div>

                                <button type="submit" class="w-full bg-slate-900 text-white py-6 rounded-3xl font-black text-xl shadow-2xl shadow-slate-200 hover:bg-black hover:-translate-y-1 transition-all active:scale-95 flex items-center justify-center space-x-4">
                                    <span>Submit Feedback</span>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <p class="text-center text-gray-400 text-xs font-black uppercase tracking-widest">
                                    <i class="fas fa-robot mr-2"></i>Powered by AI Sentiment Analysis
                                </p>
                            </form>
                        </div>
                        
                        <div class="absolute -right-10 bottom-0 text-[150px] text-primary-50/70 pointer-events-none opacity-50 group-hover:rotate-6 transition-transform duration-700"><i class="fas fa-quote-right"></i></div>
                    </div>
                <?php elseif ($feedbackGiven): ?>
                    <div class="bg-green-50 rounded-[48px] p-12 text-center border border-green-100 shadow-xl shadow-green-100/50">
                        <div class="w-20 h-20 bg-green-500 rounded-3xl flex items-center justify-center text-white mx-auto mb-8 shadow-lg shadow-green-200">
                            <i class="fas fa-heart text-2xl"></i>
                        <h3 class="text-3xl font-black text-green-900 font-heading tracking-tight mb-2">Thank You!</h3>
                        <p class="text-green-700 font-medium text-lg">Your feedback has been recorded and analyzed. Safe travels!</p>
                        <?php 
                        if ($ticket['status'] === 'completed' && ($ticket['completed_at'] ?? null)) {
                            $end = strtotime($ticket['completed_at']);
                            $start = ($ticket['served_at'] ?? null) ? strtotime($ticket['served_at']) : (($ticket['called_at'] ?? null) ? strtotime($ticket['called_at']) : $end);
                            
                            if ($start && $end) {
                                $diff = $end - $start;
                                $m = floor($diff / 60);
                                $s = $diff % 60;
                                $procTime = ($m > 0 ? "{$m}m " : "") . "{$s}s";
                                echo "<div class='mt-6 inline-flex items-center px-4 py-1.5 bg-green-100 text-green-700 rounded-full text-xs font-black uppercase tracking-widest'>Processed in $procTime</div>";
                            }
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <!-- Service Requirements -->
                    <div class="bg-white rounded-[32px] p-6 md:p-12 shadow-premium border border-slate-50 relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-xl md:text-2xl font-black text-gray-800 font-heading tracking-tight mb-2 flex items-center">
                                <i class="fas fa-clipboard-list text-primary-600 mr-3"></i>
                                Service Requirements
                            </h3>
                            <p class="text-gray-400 text-[10px] md:text-sm font-bold uppercase tracking-widest mb-6">
                                Please ensure you have these ready
                            </p>

                            <?php if (!empty($ticket['requirements'])): ?>
                                <div class="flex flex-wrap gap-3 md:gap-4">
                                    <?php 
                                    $reqs = preg_split('/[,\n\r]+/', $ticket['requirements']);
                                    foreach ($reqs as $req): 
                                        $req = trim($req);
                                        if (empty($req)) continue;
                                    ?>
                                        <div class="px-5 py-3 md:px-8 md:py-4 bg-slate-50 border border-slate-100 rounded-2xl md:rounded-[24px] flex items-center space-x-3">
                                            <i class="fas fa-check-circle text-primary-500 text-[10px] md:text-base"></i>
                                            <span class="text-xs md:text-lg font-bold text-gray-600"><?php echo htmlspecialchars($req); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">No technical requirements</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="absolute -right-6 -bottom-6 text-[100px] text-gray-50/50 pointer-events-none z-0 rotate-12"><i class="fas fa-folder-open"></i></div>
                    </div>
                <?php endif; ?>

            
        <?php endif; ?>
    </main>

    <script src="<?php echo BASE_URL; ?>/js/live-countdown.js"></script>
    <script>
        // Initialize silent auto-refresh moved to bottom for reliability
        function getTicketMetaData() {
            // Helper to extract current state for LiveStatus
            const ticketNum = <?php echo json_encode($ticket['ticket_number'] ?? ''); ?>;
            const serviceName = <?php echo json_encode($ticket['service_name'] ?? ''); ?>;
            const status = <?php echo json_encode($ticket['status'] ?? ''); ?>;
            const windowNum = <?php echo json_encode($ticket['window_number'] ?? ''); ?>;
            const windowName = <?php echo json_encode($ticket['window_name'] ?? ''); ?>;
            
            return {
                ticket_number: ticketNum,
                service_name: serviceName,
                status: status,
                window_number: windowNum,
                window_name: windowName
            };
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Re-initialize if dashboard refreshes - Handled by LiveCountdown class
        });

        async function snoozeTicket(ticketId) {
            if (!await equeueConfirm('Moving your ticket back by 3 spots will give you more time. Proceed?', 'Snooze Ticket')) return;
            
            try {
                const response = await fetch(`${ANTIGRAVITY_BASE_URL}/api/snooze-ticket.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo generateCsrfToken(); ?>'
                    },
                    body: JSON.stringify({ ticket_id: ticketId })
                });
                
                const data = await response.json();
                if (data.success) {
                    await equeueSuccess('Ticket snoozed! You moved back 3 spots.', 'Ticket Updated');
                    window.location.reload();
                } else {
                    await equeueAlert(data.message || 'Error snoozing ticket', 'Action Failed');
                }
            } catch (error) {
                console.error(error);
                await equeueAlert('Connection error', 'Network Error');
            }
        }

        async function confirmCancel(ticketId) {
            if (!await equeueConfirm('Are you sure you want to leave the queue? This cannot be undone.', 'Cancel Ticket')) return;
            
            try {
                const response = await fetch(`${ANTIGRAVITY_BASE_URL}/api/user-cancel-ticket.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?php echo generateCsrfToken(); ?>'
                    },
                    body: JSON.stringify({ ticket_id: ticketId })
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    await equeueAlert(data.message || 'Error cancelling ticket', 'Action Failed');
                }
            } catch (error) {
                console.error(error);
                await equeueAlert('Connection error', 'Network Error');
            }
        }

        // Initialize silent auto-refresh for ticket status
        if (<?php echo ($ticket && $ticket['status'] !== 'completed') ? 'true' : 'false'; ?>) {
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof DashboardRefresh !== 'undefined') {
                    const refresh = new DashboardRefresh(['ticket-main-content'], 3000);
                }
            });
        }
    </script>

    <?php include __DIR__ . '/../../includes/chatbot-widget.php'; ?>
    <script src="<?php echo BASE_URL; ?>/js/notifications.js"></script>
</body>
</html>
