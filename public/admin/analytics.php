<?php
$pageTitle = 'Performance Reports';
include __DIR__ . '/../../includes/admin-layout-header.php';

$db = Database::getInstance()->getConnection();

// --- Analytics Calculations ---
// 1. Total Tickets Today
$stmt = $db->query("SELECT COUNT(*) as count FROM tickets WHERE DATE(created_at) = CURDATE()");
$todayTickets = $stmt->fetch()['count'];

// 2. Average Process Time
$stmt = $db->query("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) as avg_wait 
    FROM tickets 
    WHERE status IN ('called', 'serving', 'completed') AND DATE(created_at) = CURDATE()
");
$avgWaitTime = round($stmt->fetch()['avg_wait'] ?? 0);

// 3. Average Service Time
$stmt = $db->query("
    SELECT AVG(TIMESTAMPDIFF(MINUTE, served_at, completed_at)) as avg_service
    FROM tickets 
    WHERE status = 'completed' AND DATE(created_at) = CURDATE()
");
$avgServiceTime = round($stmt->fetch()['avg_service'] ?? 0);

// 4. Tickets by Service (Today)
$stmt = $db->query("
    SELECT s.service_name, s.service_code, COUNT(t.id) as count 
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    WHERE DATE(t.created_at) = CURDATE()
    GROUP BY s.service_name, s.service_code
");
$serviceStats = $stmt->fetchAll();
?>

<div class="space-y-10">
    <!-- Page Header -->
    <div class="text-center"> <!-- Centered Header -->
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary-600 mb-2">Performance & Insights</p>
        <h1 class="text-4xl 5xl:text-8xl font-black text-gray-900 font-heading tracking-tight leading-none">System Analytics</h1>
    </div>

    <!-- Metrics Strip -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Card 1 -->
        <div class="bg-white rounded-xl p-6 border border-slate-100 shadow-sm flex flex-col items-center text-center space-y-4">
            <div class="w-14 h-14 bg-primary-50 rounded-lg flex items-center justify-center text-primary-600">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Volume</p>
                <p class="text-2xl font-black text-gray-900"><?php echo $todayTickets; ?></p>
            </div>
        </div>
        <!-- Card 2 -->
        <div class="bg-white rounded-xl p-6 border border-slate-100 shadow-sm flex flex-col items-center text-center space-y-4">
            <div class="w-14 h-14 bg-amber-50 rounded-lg flex items-center justify-center text-amber-600">
                <i class="fas fa-hourglass-half text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Avg Wait</p>
                <p class="text-2xl font-black text-gray-900"><?php echo $avgWaitTime; ?>m</p>
            </div>
        </div>
        <!-- Card 3 -->
        <div class="bg-white rounded-xl p-6 border border-slate-100 shadow-sm flex flex-col items-center text-center space-y-4">
            <div class="w-14 h-14 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600">
                <i class="fas fa-tachometer-alt text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Service Speed</p>
                <p class="text-2xl font-black text-gray-900"><?php echo $avgServiceTime; ?>m</p>
            </div>
        </div>
    </div>

    <!-- Detailed Performance Reports -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Service Efficiency List (Modern Approach) -->
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white p-10">
            <h2 class="text-2xl font-black text-gray-900 font-heading mb-10 text-center">Service Efficiency</h2>
            <div class="space-y-8">
                <?php if(empty($serviceStats)): ?>
                    <p class="text-gray-400 font-bold text-center">No ticket data recorded today.</p>
                <?php else: ?>
                    <?php foreach($serviceStats as $stat): 
                        $percent = ($stat['count'] / max(1, $todayTickets)) * 100;
                        $colorClass = $percent > 40 ? 'bg-primary-500' : ($percent > 20 ? 'bg-amber-500' : 'bg-emerald-500');
                        $textColor = $percent > 40 ? 'text-primary-600' : ($percent > 20 ? 'text-amber-600' : 'text-emerald-600');
                        $bgLight = $percent > 40 ? 'bg-primary-50' : ($percent > 20 ? 'bg-amber-50' : 'bg-emerald-50');
                    ?>
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center space-x-5">
                            <div class="w-12 h-12 rounded-lg <?php echo $bgLight; ?> flex items-center justify-center <?php echo $textColor; ?> font-black text-sm border border-white shadow-sm">
                                <?php echo $stat['service_code']; ?>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 text-sm group-hover:text-primary-600 transition-colors"><?php echo $stat['service_name']; ?></h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?php echo $stat['count']; ?> Tickets Served</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 flex-1 max-w-xs ml-auto">
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="<?php echo $colorClass; ?> h-full rounded-full transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <div class="px-3 py-1 rounded-lg <?php echo $bgLight; ?> <?php echo $textColor; ?> text-[10px] font-black min-w-[50px] text-center">
                                <?php echo round($percent); ?>%
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Productivity Scoreboard -->
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white p-10">
            <h2 class="text-2xl font-black text-gray-900 font-heading mb-10 text-center">Productivity Scoreboard</h2>
            <?php
            // Mocking staff productivity for visual demonstration as seen in reference
            $staffMock = [
                ['name' => 'John Doe', 'tickets' => 42, 'score' => 98, 'color' => 'emerald'],
                ['name' => 'Jane Smith', 'tickets' => 38, 'score' => 92, 'color' => 'primary'],
                ['name' => 'Robert Johnson', 'tickets' => 25, 'score' => 74, 'color' => 'amber'],
                ['name' => 'Michael Brown', 'tickets' => 12, 'score' => 48, 'color' => 'red'],
            ];
            ?>
            <div class="space-y-6">
                <?php foreach($staffMock as $staff): ?>
                <div class="p-5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between hover:border-primary-200 transition-all cursor-default">
                    <div class="flex items-center space-x-4">
                        <img class="w-10 h-10 rounded-lg" src="https://ui-avatars.com/api/?name=<?php echo $staff['name']; ?>&background=random" alt="">
                        <div>
                            <h4 class="font-black text-gray-900 text-sm"><?php echo $staff['name']; ?></h4>
                            <p class="text-[10px] font-bold text-gray-400 uppercase"><?php echo $staff['tickets']; ?> handled Today</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                           <div class="text-sm font-black text-<?php echo $staff['color']; ?>-600"><?php echo $staff['score']; ?>%</div>
                           <div class="text-[8px] font-black text-gray-400 uppercase tracking-tighter">Efficiency</div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-<?php echo $staff['color']; ?>-50 flex items-center justify-center text-<?php echo $staff['color']; ?>-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/admin-layout-footer.php'; ?>
