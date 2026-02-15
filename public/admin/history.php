<?php
$pageTitle = 'Ticket History';
require_once __DIR__ . '/../../models/Ticket.php';
include __DIR__ . '/../../includes/admin-layout-header.php';

$ticketModel = new Ticket();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$history = $ticketModel->getGlobalHistory($startDate, $endDate);
?>

<div class="space-y-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary-600 mb-2">Logs & Archive</p>
            <h1 class="text-4xl font-black text-gray-900 font-heading tracking-tight">Ticket History</h1>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex flex-wrap items-center gap-4">
            <div class="flex flex-col">
                <label class="text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Start Date</label>
                <input type="date" name="start_date" value="<?php echo $startDate; ?>" 
                       class="bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-primary-500">
            </div>
            <div class="flex flex-col">
                <label class="text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">End Date</label>
                <input type="date" name="end_date" value="<?php echo $endDate; ?>"
                       class="bg-slate-50 border-none rounded-xl text-sm font-bold focus:ring-primary-500">
            </div>
            <div class="flex items-end h-full mt-5">
                <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white rounded-xl font-bold hover:bg-primary-700 transition-all shadow-lg shadow-primary-900/20 text-sm">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
                <?php if ($startDate || $endDate): ?>
                    <a href="history.php" class="ml-2 px-6 py-2.5 bg-slate-100 text-gray-600 rounded-xl font-bold hover:bg-slate-200 transition-all text-sm">
                        Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-50">
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Ticket</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Customer</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Service</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Window</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Notes</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Proc. Time</th>
                        <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Completed At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="8" class="px-8 py-12 text-center text-slate-400 font-medium">
                                No tickets found for this period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $ticket): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-6">
                                    <span class="font-black text-slate-900"><?php echo $ticket['ticket_number']; ?></span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs uppercase">
                                            <?php echo substr($ticket['user_name'], 0, 2); ?>
                                        </div>
                                        <span class="font-bold text-slate-700"><?php echo $ticket['user_name']; ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-primary-50 text-primary-600 rounded-full text-[10px] font-black uppercase tracking-wider">
                                        <?php echo $ticket['service_name']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="font-bold text-slate-600">
                                        <?php echo $ticket['window_name'] ? "W-{$ticket['window_number']} ({$ticket['window_name']})" : '-'; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <?php 
                                    $statusColor = $ticket['status'] === 'completed' ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50';
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?php echo $statusColor; ?>">
                                        <span class="w-1 h-1 rounded-full bg-current mr-2"></span>
                                        <?php echo $ticket['status']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <?php if ($ticket['staff_notes']): ?>
                                        <div class="max-w-[200px] text-xs font-medium text-slate-500 truncate" title="<?php echo htmlspecialchars($ticket['staff_notes']); ?>">
                                            "<?php echo $ticket['staff_notes']; ?>"
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-6 font-bold text-slate-500">
                                    <?php echo $ticket['processing_time']; ?>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-bold text-slate-900">
                                        <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                    </div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">
                                        <?php echo date('h:i A', strtotime($ticket['created_at'])); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/admin-layout-footer.php'; ?>
