<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Window.php';

requireRole('staff');

$windowModel = new Window();
$window = $windowModel->getWindowByStaff($_SESSION['user_id']);

if (!$window) {
    die("No window assigned.");
}

$services = $windowModel->getWindowServices($window['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/staff-navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-list-check text-primary mr-2"></i> Managed Services
                    </h2>
                    <p class="text-gray-600 mt-1">Toggle the services you can handle at this window.</p>
                </div>
                
                <div class="px-6 pb-6 space-y-4">
                    <?php if(empty($services)): ?>
                        <div class="p-8 text-center text-gray-500">
                            No services available to manage.
                        </div>
                    <?php else: ?>
                        <?php foreach($services as $service): ?>
                            <div id="service-card-<?php echo $service['id']; ?>" class="p-6 flex items-center justify-between transition border-2 rounded-lg mb-2 <?php echo $service['is_enabled'] ? 'bg-emerald-50 border-emerald-500' : 'bg-rose-50 border-rose-500'; ?>">
                                <div class="flex-1">
                                    <h3 class="font-bold text-lg text-gray-800">
                                        <?php echo $service['service_name']; ?>
                                    </h3>
                                    <span class="text-sm font-mono text-gray-500 bg-white/50 px-2 py-1 rounded">
                                        <?php echo $service['service_code']; ?>
                                    </span>
                                </div>
                                
                                <div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" 
                                            onchange="toggleService(<?php echo $service['id']; ?>)"
                                            <?php echo $service['is_enabled'] ? 'checked' : ''; ?>>
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-emerald-600">
                                            <span id="status-<?php echo $service['id']; ?>">
                                                <?php echo $service['is_enabled'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/notifications.js"></script>
    <script>
        async function toggleService(serviceId) {
            try {
                const response = await fetch('../api/toggle-service.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        window_id: <?php echo $window['id']; ?>,
                        service_id: serviceId
                    })
                });
                
                const data = await response.json();
                if(data.success) {
                    const statusSpan = document.getElementById('status-' + serviceId);
                    const card = document.getElementById('service-card-' + serviceId);
                    
                    if(data.is_enabled) {
                        statusSpan.textContent = 'Active';
                        // Update Card Styles to Green
                        card.classList.remove('bg-rose-50', 'border-rose-500');
                        card.classList.add('bg-emerald-50', 'border-emerald-500');
                    } else {
                        statusSpan.textContent = 'Inactive';
                        // Update Card Styles to Red
                        card.classList.remove('bg-emerald-50', 'border-emerald-500');
                        card.classList.add('bg-rose-50', 'border-rose-500');
                    }
                } else {
                    await equeueAlert(data.message || 'Failed to update service status', 'Update Error');
                    location.reload(); 
                }
            } catch (err) {
                console.error(err);
                await equeueAlert('An error occurred. Please check your connection.', 'Network Error');
            }
        }
    </script>
    <script src="../js/notifications.js"></script>
</body>
</html>
