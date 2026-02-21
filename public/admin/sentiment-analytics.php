<?php
$pageTitle = 'Sentiment Insights';
require_once __DIR__ . '/../../models/Feedback.php';
include __DIR__ . '/../../includes/admin-layout-header.php';
?>

<!-- Add Export & Charting Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<?php
$feedbackModel = new Feedback();
$stats = $feedbackModel->getFeedbackStats();
$trends = $feedbackModel->getFeedbackTrends(30); // Last 30 days
$allFeedback = $feedbackModel->getAllFeedback();

// Granular Breakdown Logic
$db = Database::getInstance()->getConnection();
$serviceBreakdown = $db->query("
    SELECT s.service_name, 
           AVG(f.sentiment_score) as avg_score, 
           COUNT(*) as count
    FROM feedback f
    JOIN tickets t ON f.ticket_id = t.id
    JOIN services s ON t.service_id = s.id
    GROUP BY s.id
    ORDER BY avg_score DESC
")->fetchAll();

$windowBreakdown = $db->query("
    SELECT w.window_number, w.window_name,
           AVG(f.sentiment_score) as avg_score, 
           COUNT(*) as count
    FROM feedback f
    JOIN windows w ON f.window_id = w.id
    GROUP BY w.id
    ORDER BY avg_score DESC
")->fetchAll();
?>

<div class="space-y-10">
    <!-- Header -->
    <div class="flex flex-col items-center justify-center text-center gap-6">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary-600 mb-2">Intelligence & Insights</p>
            <h1 class="text-4xl 5xl:text-8xl font-black text-gray-900 font-heading tracking-tight leading-none">Sentiment Analytics</h1>
        </div>
        <div class="flex items-center space-x-3">
            <div class="px-6 py-3 bg-white border border-slate-200 rounded-lg font-bold text-gray-600 shadow-sm text-sm">
                <i class="fas fa-calendar-alt mr-2 opacity-50"></i>Last 30 Days
            </div>
            
            <div class="flex items-center p-1 bg-white border border-slate-200 rounded-xl shadow-sm">
                <button onclick="exportToExcel()" class="px-4 py-2 hover:bg-emerald-50 text-emerald-600 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </button>
                <div class="w-px h-4 bg-slate-100 mx-1"></div>
                <button onclick="exportToPDF()" class="px-4 py-2 hover:bg-rose-50 text-rose-600 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </button>
                <div class="w-px h-4 bg-slate-100 mx-1"></div>
                <button onclick="exportToWord()" class="px-4 py-2 hover:bg-blue-50 text-blue-600 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center">
                    <i class="fas fa-file-word mr-2"></i> Word
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div class="bg-white rounded-xl p-8 shadow-xl shadow-slate-200/50 border border-white flex flex-col items-center text-center">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Feedback</p>
            <p class="text-4xl font-black text-gray-900 font-heading"><?php echo $stats['total_feedback']; ?></p>
        </div>
        <div class="bg-emerald-50 rounded-xl p-8 shadow-xl shadow-emerald-100/50 border border-emerald-100 flex flex-col items-center text-center">
            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Positive Ratio</p>
            <p class="text-4xl font-black text-emerald-700 font-heading">
                <?php 
                    $pos = ($stats['very_positive'] + $stats['positive']);
                    echo $stats['total_feedback'] > 0 ? round(($pos / $stats['total_feedback']) * 100) : 0; 
                ?>%
            </p>
        </div>
        <div class="bg-slate-900 rounded-xl p-8 shadow-xl shadow-slate-900/20 text-white flex flex-col items-center text-center">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Average Score</p>
            <p class="text-4xl font-black font-heading text-primary-400">
                <?php echo number_format($stats['avg_sentiment_score'] ?? 0, 2); ?>
            </p>
        </div>
        <div class="bg-white rounded-xl p-8 shadow-xl shadow-slate-200/50 border border-white flex flex-col items-center text-center">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Health Status</p>
            <div class="flex items-center mt-2">
                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse mr-2"></span>
                <span class="font-bold text-gray-700">Excellent</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sentiment Distribution Chart -->
        <div class="lg:col-span-4 bg-white rounded-2xl p-10 shadow-2xl shadow-slate-200/40 border border-white">
            <h3 class="text-xl font-black text-gray-900 font-heading mb-8 text-center">Sentiment Distribution</h3>
            <div class="h-[300px] w-full relative">
                <canvas id="distributionChart"></canvas>
            </div>
        </div>

        <!-- Sentiment Trend Chart -->
        <div class="lg:col-span-8 bg-white rounded-2xl p-10 shadow-2xl shadow-slate-200/40 border border-white">
            <h3 class="text-xl font-black text-gray-900 font-heading mb-8 text-center">Sentiment Trends</h3>
            <div class="h-[400px] w-full relative">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Service Performance -->
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white overflow-hidden">
            <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="text-xl font-black text-gray-900 font-heading text-center">Service Performance</h3>
            </div>
            <div class="p-10">
                <div class="space-y-6">
                    <?php foreach ($serviceBreakdown as $row): ?>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-gray-700"><?php echo $row['service_name']; ?></span>
                                <span class="text-xs font-black text-primary-600"><?php echo round(($row['avg_score'] + 1) * 50); ?>% Satisfaction</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-primary-500 rounded-full" style="width: <?php echo ($row['avg_score'] + 1) * 50; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Window Performance -->
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white overflow-hidden">
            <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/50">
                <h3 class="text-xl font-black text-gray-900 font-heading text-center">Counter Performance</h3>
            </div>
            <div class="p-10">
                <div class="space-y-6">
                    <?php foreach ($windowBreakdown as $row): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-100 hover:border-primary-100 transition-all">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-slate-900 text-white rounded-lg flex items-center justify-center font-black text-[10px]">
                                    <?php echo $row['window_number']; ?>
                                </div>
                                <span class="font-bold text-gray-700 text-sm"><?php echo $row['window_name']; ?></span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-black text-gray-900"><?php echo number_format($row['avg_score'], 1); ?></div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?php echo $row['count']; ?> Feedbacks</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Feedback Feed -->
    <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white overflow-hidden">
        <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between bg-primary-900 text-white relative">
            <div class="flex-1 text-center"> <!-- Centered Feedback Header -->
                <h3 class="text-2xl font-black font-heading">Feedback Feed</h3>
                <p class="text-xs font-bold text-primary-300 uppercase tracking-widest mt-1">Real-time analysis of user comments</p>
            </div>
            
            <div class="flex items-center space-x-2 absolute right-10">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></span>
                <span class="text-[10px] font-black text-primary-400 uppercase tracking-widest">Live Updates</span>
            </div>
        </div>
        <div class="p-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach (array_slice($allFeedback, 0, 9) as $feedback): ?>
                    <div class="p-6 bg-slate-50 rounded-xl border border-slate-100 hover:border-primary-100 transition-all flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <img class="w-10 h-10 rounded-lg" src="https://ui-avatars.com/api/?name=<?php echo urlencode($feedback['user_name']); ?>&background=random" alt="">
                                <div>
                                    <div class="text-sm font-black text-gray-900"><?php echo $feedback['user_name']; ?></div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase"><?php echo date('M d, H:i', strtotime($feedback['created_at'])); ?></div>
                                </div>
                            </div>
                            <?php 
                                $sentimentIcons = [
                                    'very_positive' => ['😊', 'text-primary-800', 'bg-primary-100'],
                                    'positive' => ['🙂', 'text-primary-600', 'bg-primary-50'],
                                    'neutral' => ['😐', 'text-slate-600', 'bg-slate-50'],
                                    'negative' => ['🙁', 'text-secondary-600', 'bg-secondary-50'],
                                    'very_negative' => ['😞', 'text-secondary-800', 'bg-secondary-100']
                                ];
                                $style = $sentimentIcons[$feedback['sentiment']] ?? $sentimentIcons['neutral'];
                            ?>
                            <span class="w-10 h-10 <?php echo $style[2]; ?> rounded-lg flex items-center justify-center text-xl shadow-sm">
                                <?php echo $style[0]; ?>
                            </span>
                        </div>
                        <p class="text-gray-600 font-medium mb-6 flex-1 text-sm line-clamp-3">"<?php echo htmlspecialchars($feedback['comment']); ?>"</p>
                        <div class="pt-6 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest"><?php echo $feedback['service_name']; ?></span>
                            <span class="text-[10px] font-black <?php echo $style[1]; ?> uppercase tracking-widest"><?php echo str_replace('_', ' ', $feedback['sentiment']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Distribution Chart
        const distCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: ['V. Positive', 'Positive', 'Neutral', 'Negative', 'V. Negative'],
                datasets: [{
                    data: [
                        <?php echo $stats['very_positive']; ?>,
                        <?php echo $stats['positive']; ?>,
                        <?php echo $stats['neutral']; ?>,
                        <?php echo $stats['negative']; ?>,
                        <?php echo $stats['very_negative']; ?>
                    ],
                    backgroundColor: ['#0c4b05', '#15803d', '#94a3b8', '#b91c1c', '#8b0101'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom', labels: { font: { weight: 'black', size: 10 }, padding: 20 } }
                },
                cutout: '75%',
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trends, 'date')); ?>,
                datasets: [{
                    label: 'Sentiment Score',
                    data: <?php echo json_encode(array_column($trends, 'avg_sentiment')); ?>,
                    borderColor: '#0c4b05',
                    backgroundColor: 'rgba(12, 75, 5, 0.05)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 3,
                    pointHoverRadius: 8
                }]
            },
            options: {
                scales: {
                    y: { min: -1, max: 1, ticks: { font: { weight: 'bold' } }, grid: { borderDash: [5, 5] } },
                    x: { ticks: { font: { weight: 'bold' } }, grid: { display: false } }
                },
                plugins: {
                    legend: { display: false }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });

    // --- Export Logic ---
    const feedbackData = <?php echo json_encode($allFeedback); ?>;

    function exportToExcel() {
        const worksheet = XLSX.utils.json_to_sheet(feedbackData.map(f => ({
            'Date': new Date(f.created_at).toLocaleString(),
            'User': f.user_name,
            'Service': f.service_name,
            'Ticket': f.ticket_number,
            'Sentiment': f.sentiment.replace('_', ' ').toUpperCase(),
            'Score': f.sentiment_score,
            'Comment': f.comment
        })));
        
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Feedback");
        XLSX.writeFile(workbook, `Sentiment_Feedback_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
    }

    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');
        
        doc.setFontSize(20);
        doc.text("Sentiment Analytics Report", 14, 22);
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 30);
        
        const tableBody = feedbackData.map(f => [
            new Date(f.created_at).toLocaleDateString(),
            f.user_name,
            f.service_name,
            f.sentiment.replace('_', ' ').toUpperCase(),
            f.sentiment_score,
            f.comment
        ]);
        
        doc.autoTable({
            startY: 40,
            head: [['Date', 'User', 'Service', 'Sentiment', 'Score', 'Comment']],
            body: tableBody,
            theme: 'striped',
            headStyles: { fillColor: [15, 23, 42], fontWeight: 'bold' },
            styles: { fontSize: 8, cellPadding: 3 },
            columnStyles: { 5: { cellWidth: 80 } }
        });
        
        doc.save(`Sentiment_Report_${new Date().toISOString().split('T')[0]}.pdf`);
    }

    function exportToWord() {
        let content = `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head><meta charset='utf-8'><title>Sentiment Report</title></head>
            <body style="font-family: Arial, sans-serif;">
                <h1 style="color: #0f172a; text-align: center;">Sentiment Analytics Feed</h1>
                <p style="color: #64748b; text-align: center;">Report Date: ${new Date().toLocaleString()}</p>
                <hr>
                <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                    <tr style="background-color: #f8fafc;">
                        <th>Date</th><th>User</th><th>Service</th><th>Sentiment</th><th>Comment</th>
                    </tr>
                    ${feedbackData.map(f => `
                        <tr>
                            <td>${new Date(f.created_at).toLocaleDateString()}</td>
                            <td>${f.user_name}</td>
                            <td>${f.service_name}</td>
                            <td>${f.sentiment.replace('_', ' ')}</td>
                            <td>${f.comment}</td>
                        </tr>
                    `).join('')}
                </table>
            </body>
            </html>
        `;
        
        const blob = new Blob(['\ufeff', content], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `Sentiment_Report_${new Date().toISOString().split('T')[0]}.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php 
include __DIR__ . '/../../includes/admin-layout-footer.php'; 
?>
