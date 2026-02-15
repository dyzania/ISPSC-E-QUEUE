<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

requireLogin();
requireRole('user');

$db = Database::getInstance()->getConnection();

// Get active services that have appointments enabled
$stmt = $db->prepare("
    SELECT s.*, aps.enable_appointments 
    FROM services s
    LEFT JOIN appointment_settings aps ON s.id = aps.service_id
    WHERE s.is_active = 1 AND (aps.enable_appointments = 1 OR aps.enable_appointments IS NULL)
    ORDER BY s.service_name
");
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if user has active appointment
$stmt = $db->prepare("
    SELECT t.*, s.service_name 
    FROM tickets t
    JOIN services s ON t.service_id = s.id
    WHERE t.user_id = ? AND t.is_appointment = 1 
    AND t.status IN ('scheduled', 'waiting', 'called', 'serving')
    ORDER BY t.created_at DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$activeAppointment = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <title>Schedule Appointment - Antigravity</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen">
    <?php include __DIR__ . '/../../includes/user-navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        
        <!-- Header -->
        <div class="mb-8">
            <a href="dashboard.php" class="inline-flex items-center text-slate-600 hover:text-slate-900 mb-4 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <h1 class="text-4xl font-black text-slate-900 mb-2">
                <i class="fas fa-calendar-check text-primary-600 mr-3"></i>Schedule Appointment
            </h1>
            <p class="text-slate-600">Book a time slot to skip the queue</p>
        </div>

        <?php if ($activeAppointment): ?>
            <!-- Active Appointment Alert -->
            <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-lg mb-8">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-amber-500 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="font-bold text-amber-900 mb-2">You have an active appointment</h3>
                        <p class="text-amber-800 mb-3">
                            <strong><?php echo htmlspecialchars($activeAppointment['service_name']); ?></strong><br>
                            <?php if ($activeAppointment['appointment_date']): ?>
                                <?php echo date('F j, Y \a\t g:i A', strtotime($activeAppointment['appointment_date'] . ' ' . $activeAppointment['appointment_time'])); ?>
                            <?php endif; ?><br>
                            Ticket: <strong><?php echo htmlspecialchars($activeAppointment['ticket_number']); ?></strong>
                        </p>
                        <p class="text-sm text-amber-700">Please complete or cancel your current appointment before booking a new one.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Booking Form -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                
                <!-- Step 1: Select Service -->
                <div id="step1" class="p-8">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold mr-4">1</div>
                        <h2 class="text-2xl font-bold text-slate-900">Select Service</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($services as $service): ?>
                            <button onclick="selectService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>')" 
                                    class="service-btn p-6 border-2 border-slate-200 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition text-left group">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-bold text-lg text-slate-900 group-hover:text-primary-700"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                        <?php if ($service['description']): ?>
                                            <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($service['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($service['estimated_time']): ?>
                                            <p class="text-xs text-slate-500 mt-2">
                                                <i class="far fa-clock mr-1"></i> ~<?php echo $service['estimated_time']; ?> min
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <i class="fas fa-chevron-right text-slate-400 group-hover:text-primary-600"></i>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Select Date -->
                <div id="step2" class="p-8 hidden">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold mr-4">2</div>
                        <h2 class="text-2xl font-bold text-slate-900">Select Date</h2>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Choose a date</label>
                        <input type="date" id="appointmentDate" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                               class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:border-primary-500 focus:outline-none text-lg"
                               onchange="loadTimeSlots()">
                    </div>
                    
                    <button onclick="goToStep(1)" class="text-slate-600 hover:text-slate-900 mt-4">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </button>
                </div>

                <!-- Step 3: Select Time -->
                <div id="step3" class="p-8 hidden">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold mr-4">3</div>
                        <h2 class="text-2xl font-bold text-slate-900">Select Time</h2>
                    </div>
                    
                    <div id="timeSlotsContainer" class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                        <!-- Time slots will be loaded here -->
                    </div>
                    
                    <button onclick="goToStep(2)" class="text-slate-600 hover:text-slate-900">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </button>
                </div>

                <!-- Step 4: Confirm -->
                <div id="step4" class="p-8 hidden">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold mr-4">4</div>
                        <h2 class="text-2xl font-bold text-slate-900">Confirm Appointment</h2>
                    </div>
                    
                    <div class="bg-slate-50 rounded-xl p-6 mb-6">
                        <h3 class="font-bold text-slate-900 mb-4">Appointment Details</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-slate-600">Service:</span>
                                <span class="font-bold text-slate-900" id="confirmService"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Date:</span>
                                <span class="font-bold text-slate-900" id="confirmDate"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-600">Time:</span>
                                <span class="font-bold text-slate-900" id="confirmTime"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button onclick="goToStep(3)" class="flex-1 px-6 py-3 border-2 border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-50 transition">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <button onclick="confirmAppointment()" id="confirmBtn" class="flex-1 px-6 py-3 bg-primary-600 text-white font-bold rounded-lg hover:bg-primary-700 transition">
                            <i class="fas fa-check mr-2"></i> Confirm Booking
                        </button>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <script>
        let selectedService = null;
        let selectedServiceName = '';
        let selectedDate = '';
        let selectedTime = '';
        let selectedTime24h = '';

        function selectService(serviceId, serviceName) {
            selectedService = serviceId;
            selectedServiceName = serviceName;
            goToStep(2);
        }

        function goToStep(step) {
            // Hide all steps
            for (let i = 1; i <= 4; i++) {
                document.getElementById('step' + i).classList.add('hidden');
            }
            // Show selected step
            document.getElementById('step' + step).classList.remove('hidden');
        }

        async function loadTimeSlots() {
            const date = document.getElementById('appointmentDate').value;
            if (!date) return;
            
            selectedDate = date;
            
            const container = document.getElementById('timeSlotsContainer');
            container.innerHTML = '<div class="col-span-full text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-primary-600"></i></div>';
            
            try {
                const response = await fetch(`../api/get-available-slots.php?service_id=${selectedService}&date=${date}`);
                const data = await response.json();
                
                if (data.error) {
                    container.innerHTML = `<div class="col-span-full text-center text-red-600 py-8">${data.error}</div>`;
                    return;
                }
                
                if (data.slots.length === 0) {
                    container.innerHTML = '<div class="col-span-full text-center text-slate-600 py-8">No available slots for this date</div>';
                    return;
                }
                
                container.innerHTML = '';
                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.className = `p-4 border-2 rounded-lg font-bold transition ${
                        slot.available 
                            ? 'border-slate-200 hover:border-primary-500 hover:bg-primary-50 text-slate-900' 
                            : 'border-slate-100 bg-slate-50 text-slate-400 cursor-not-allowed'
                    }`;
                    btn.innerHTML = `
                        <div class="text-lg">${slot.time}</div>
                        <div class="text-xs mt-1">${slot.available ? slot.remaining + ' slots' : 'Full'}</div>
                    `;
                    btn.disabled = !slot.available;
                    if (slot.available) {
                        btn.onclick = () => selectTime(slot.time, slot.time_24h);
                    }
                    container.appendChild(btn);
                });
                
                goToStep(3);
                
            } catch (error) {
                console.error('Error loading slots:', error);
                container.innerHTML = '<div class="col-span-full text-center text-red-600 py-8">Failed to load time slots</div>';
            }
        }

        function selectTime(time, time24h) {
            selectedTime = time;
            selectedTime24h = time24h;
            
            // Update confirmation
            document.getElementById('confirmService').textContent = selectedServiceName;
            document.getElementById('confirmDate').textContent = new Date(selectedDate).toLocaleDateString('en-US', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });
            document.getElementById('confirmTime').textContent = selectedTime;
            
            goToStep(4);
        }

        async function confirmAppointment() {
            const btn = document.getElementById('confirmBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Booking...';
            
            try {
                const response = await fetch('../api/create-appointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        service_id: selectedService,
                        date: selectedDate,
                        time: selectedTime24h
                    })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert('Error: ' + data.error);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i> Confirm Booking';
                    return;
                }
                
                // Success - redirect to dashboard
                alert('Appointment booked successfully! Ticket: ' + data.ticket_number);
                window.location.href = 'dashboard.php';
                
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to book appointment. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i> Confirm Booking';
            }
        }
    </script>

</body>
</html>
