<?php
$pageTitle = 'Service Management';
require_once __DIR__ . '/../../models/Service.php';
include __DIR__ . '/../../includes/admin-layout-header.php';

$serviceModel = new Service();
$message = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'create') {
            $name = sanitize($_POST['service_name']);
            $code = sanitize($_POST['service_code']);
            $desc = sanitize($_POST['description']);
            $reqs = sanitize($_POST['requirements']);
            $time = sanitize($_POST['estimated_time']); 
            $staffNotes = isset($_POST['staff_notes']) ? sanitize($_POST['staff_notes']) : null;
            
            $result = $serviceModel->createService($name, $code, $desc, $reqs, $time, $staffNotes);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'update') {
            $id = $_POST['service_id'];
            $name = sanitize($_POST['service_name']);
            $code = sanitize($_POST['service_code']);
            $desc = sanitize($_POST['description']);
            $reqs = sanitize($_POST['requirements']);
            $time = sanitize($_POST['estimated_time']);
            $staffNotes = isset($_POST['staff_notes']) ? sanitize($_POST['staff_notes']) : null;
            
            if ($serviceModel->updateService($id, $name, $code, $desc, $reqs, $time, $staffNotes)) {
                $message = "Service updated successfully.";
            } else {
                $error = "Failed to update service.";
            }
        } elseif ($action === 'delete') {
            $id = $_POST['service_id'];
            if ($serviceModel->deleteService($id)) {
                $message = "Service deleted successfully.";
            } else {
                $error = "Failed to delete service.";
            }
        }
    }
}

$services = $serviceModel->getAllServicesAdmin();
?>

<div class="space-y-10">
    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
        <div class="text-center w-full md:w-auto">
            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-primary-600 mb-2">System Configuration</p>
            <h1 class="text-4xl 5xl:text-8xl font-black text-gray-900 font-heading tracking-tight leading-none">Service Management</h1>
            <p class="text-gray-500 font-medium mt-2 text-sm">Manage available categories and queue preferences.</p>
        </div>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-8 py-4 rounded-lg shadow-2xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95 flex items-center font-black text-sm font-heading">
            <i class="fas fa-plus mr-3"></i>Add New Service
        </button>
    </div>
    
    <?php if($message): ?>
        <div class="p-6 bg-emerald-50 rounded-xl border border-emerald-100 text-emerald-800 flex items-center justify-center shadow-lg shadow-emerald-100/50 animate-float">
            <i class="fas fa-check-circle mr-4 text-2xl"></i>
            <span class="font-bold"><?php echo $message; ?></span>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="p-6 bg-rose-50 rounded-xl border border-rose-100 text-rose-800 flex items-center justify-center shadow-lg shadow-rose-100/50">
            <i class="fas fa-exclamation-circle mr-4 text-2xl"></i>
            <span class="font-bold"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/40 border border-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-center">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Service Details</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Code</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Processing Time</th>
                        <th class="px-10 py-6 font-black text-gray-400 uppercase text-[10px] tracking-[0.3em] text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="4" class="px-10 py-20 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-xl flex items-center justify-center mb-6">
                                        <i class="fas fa-inbox text-4xl text-slate-200"></i>
                                    </div>
                                    <p class="font-bold text-xl text-gray-500">No services found.</p>
                                    <p class="text-sm">Create one to get started.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-10 py-6">
                                    <div class="flex flex-col items-center justify-center"> <!-- Centered wrapper for details -->
                                        <div class="font-black text-gray-900 text-sm mb-1"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                        <div class="text-[11px] text-gray-500 font-medium truncate max-w-xs"><?php echo htmlspecialchars($service['description']); ?></div>
                                    </div>
                                </td>
                                <td class="px-10 py-6">
                                    <span class="bg-primary-50 text-primary-700 text-[10px] font-black px-4 py-1.5 rounded-md border border-primary-100">
                                        <?php echo htmlspecialchars($service['service_code']); ?>
                                    </span>
                                </td>
                                <td class="px-10 py-6">
                                    <div class="flex items-center justify-center text-gray-600 font-bold text-sm"> <!-- Added justify-center -->
                                        <i class="far fa-clock mr-2 text-primary-400"></i>
                                        <?php echo htmlspecialchars($service['estimated_time']); ?>
                                    </div>
                                </td>
                                <td class="px-10 py-6 text-center space-x-2"> <!-- Changed text-right to text-center -->
                                    <button 
                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($service)); ?>)"
                                        class="text-slate-300 hover:text-primary-600 transition-all p-3 rounded-lg hover:bg-primary-50 active:scale-95" 
                                        title="Edit"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form id="delete-form-<?php echo $service['id']; ?>" method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                        <button type="button" 
                                                onclick="handleDeleteService(<?php echo $service['id']; ?>)"
                                                class="text-slate-300 hover:text-rose-500 transition-all p-3 rounded-lg hover:bg-rose-50 active:scale-95" 
                                                title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Service Modal -->
<div id="createModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-10 transform transition-all border border-white">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-gray-900 font-heading tracking-tight">Add New Service</h3>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-300 hover:text-gray-900 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="create">
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Service Name</label>
                    <input type="text" name="service_name" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="e.g. Cash Deposit">
                </div>
                <div>
                    <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Service Code</label>
                    <input type="text" name="service_code" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="e.g. A">
                </div>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Description</label>
                <textarea name="description" rows="2" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="Brief description of the service..."></textarea>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Requirements</label>
                <textarea name="requirements" rows="3" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="• Valid ID&#10;• Completed Form"></textarea>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Staff Notes (Internal)</label>
                <textarea name="staff_notes" rows="2" class="w-full px-6 py-4 bg-amber-50 border border-amber-100 rounded-lg focus:ring-4 focus:ring-amber-100 focus:bg-white transition-all text-sm font-bold text-amber-900" placeholder="Important notes visible to staff..."></textarea>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Est. Processing Time</label>
                <input type="text" name="estimated_time" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold" placeholder="e.g. 15 mins">
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-5 rounded-lg font-black shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95">
                    Deploy Service
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 transition-opacity p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-10 transform transition-all border border-white">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-gray-900 font-heading tracking-tight">Edit Service</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-300 hover:text-gray-900 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="service_id" id="edit_service_id">
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Service Name</label>
                    <input type="text" name="service_name" id="edit_service_name" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold">
                </div>
                <div>
                    <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Service Code</label>
                    <input type="text" name="service_code" id="edit_service_code" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold">
                </div>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Description</label>
                <textarea name="description" id="edit_description" rows="2" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold"></textarea>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Requirements</label>
                <textarea name="requirements" id="edit_requirements" rows="3" class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold"></textarea>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Staff Notes (Internal)</label>
                <textarea name="staff_notes" id="edit_staff_notes" rows="2" class="w-full px-6 py-4 bg-amber-50 border border-amber-100 rounded-lg focus:ring-4 focus:ring-amber-100 focus:bg-white transition-all text-sm font-bold text-amber-900"></textarea>
            </div>

            <div>
                <label class="block text-gray-400 text-[10px] font-black uppercase tracking-widest mb-2 ml-2">Est. Processing Time</label>
                <input type="text" name="estimated_time" id="edit_estimated_time" required class="w-full px-6 py-4 bg-slate-50 border border-slate-100 rounded-lg focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all text-sm font-bold">
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-5 rounded-lg font-black shadow-xl shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-1 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    async function handleDeleteService(serviceId) {
        if (await equeueConfirm('Are you sure you want to delete this service? This cannot be undone.', 'Delete Service')) {
            document.getElementById(`delete-form-${serviceId}`).submit();
        }
    }

    function openEditModal(service) {
        document.getElementById('edit_service_id').value = service.id;
        document.getElementById('edit_service_name').value = service.service_name;
        document.getElementById('edit_service_code').value = service.service_code;
        document.getElementById('edit_description').value = service.description;
        document.getElementById('edit_requirements').value = service.requirements;
        document.getElementById('edit_estimated_time').value = service.estimated_time;
        document.getElementById('edit_staff_notes').value = service.staff_notes || '';
        
        document.getElementById('editModal').classList.remove('hidden');
    }
</script>

<?php 
include __DIR__ . '/../../includes/admin-layout-footer.php'; 
?>
