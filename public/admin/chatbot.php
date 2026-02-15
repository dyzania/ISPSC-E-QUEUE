<?php
$pageTitle = 'AI Context Settings';
require_once __DIR__ . '/../../models/Chatbot.php';
include __DIR__ . '/../../includes/admin-layout-header.php';

$db = Database::getInstance()->getConnection();
$chatbot = new Chatbot();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Allow HTML content from Quill
    $content = $_POST['content'];
    
    if (empty(trim(strip_tags($content)))) {
        $error = 'Context content cannot be empty.';
    } else {
        if ($chatbot->updateContext($content)) {
            $message = 'Chatbot knowledge base updated successfully!';
        } else {
            $error = 'Failed to update database.';
        }
    }
}

// Get current chatbot data
// Initial data check handled by migration script
// $chatbot_data is no longer needed as we call getContext() directly in the view
?>
<?php
if ($message): ?>
    <div class="p-6 mb-8 text-emerald-800 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center shadow-lg shadow-emerald-100/50 animate-float" role="alert">
        <i class="fas fa-check-circle mr-4 text-2xl"></i>
        <span class="font-bold text-lg"><?php echo $message; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="p-6 mb-8 text-red-800 bg-red-50 rounded-xl border border-red-100 flex items-center shadow-lg shadow-red-100/50" role="alert">
        <i class="fas fa-exclamation-circle mr-4 text-2xl"></i>
        <span class="font-bold text-lg"><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
    <!-- Settings Form -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/50 border border-white overflow-hidden">
            <div class="bg-slate-900 px-8 py-6 flex items-center justify-between">
                <h3 class="text-xl font-black text-white font-heading tracking-tight">
                    <i class="fas fa-robot mr-3 text-primary-400"></i>AI Context Data
                </h3>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active System</span>
            </div>
            
            <div class="p-10">
                <div class="mb-8 p-6 bg-primary-50 rounded-lg border border-primary-100 text-sm text-primary-900">
                    <h4 class="font-black uppercase tracking-widest text-[10px] mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>Executive Guidance
                    </h4>
                    <p class="font-medium">The data below forms the AI's "Brain". Be precise with business hours, physical location, service requirements, and organizational policies to ensure accurate responses.</p>
                </div>

                <form method="POST" id="chatbotForm">
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4 ml-2">
                            <label for="content" class="text-gray-700 text-xs font-black uppercase tracking-[0.2em]">Knowledge Base Content</label>
                            <div class="flex items-center space-x-2">
                                <input type="file" id="docUpload" accept=".pdf,.docx" class="hidden">
                                <button type="button" onclick="document.getElementById('docUpload').click()" 
                                    class="text-xs font-bold text-primary-600 hover:text-primary-800 bg-primary-50 hover:bg-primary-100 px-3 py-1.5 rounded-lg transition-colors flex items-center">
                                    <i class="fas fa-file-upload mr-2"></i>Import Doc/PDF
                                </button>
                            </div>
                        </div>
                        
                        <!-- Hidden input to store Quill content -->
                        <input type="hidden" name="content" id="hiddenContent">
                        
                        <!-- Quill Editor Container -->
                        <div id="editor-container" class="bg-white rounded-xl border border-slate-200 shadow-inner" style="height: 400px;">
                            <?php echo $chatbot->getContext(); ?>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-slate-900 hover:bg-black text-white font-black py-4 px-10 rounded-lg shadow-xl shadow-slate-200 transition transform hover:-translate-y-1 active:scale-95 flex items-center">
                            <i class="fas fa-save mr-3"></i>Deploy Intelligence
                        </button>
                    </div>
                </form>

                <!-- Quill JS & File Upload Script -->
                <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
                <script>
                    // Initialize Quill
                    var quill = new Quill('#editor-container', {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                [{ 'header': [1, 2, 3, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                [{ 'color': [] }, { 'background': [] }],
                                ['clean']
                            ]
                        },
                        placeholder: 'Enter company info, FAQs, and policies here...'
                    });

                    // Handle Form Submission
                    document.getElementById('chatbotForm').onsubmit = function() {
                        var content = document.querySelector('#hiddenContent');
                        content.value = quill.root.innerHTML;
                        return true;
                    };

                    // Handle File Upload
                    document.getElementById('docUpload').addEventListener('change', async function(e) {
                        const file = e.target.files[0];
                        if (!file) return;

                        const formData = new FormData();
                        formData.append('file', file);

                        // Show loading state
                        const btn = this.nextElementSibling;
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Extracting...';
                        btn.disabled = true;

                        try {
                            const response = await fetch('import-doc.php', {
                                method: 'POST',
                                body: formData
                            });

                            const data = await response.json();
                            
                            if (data.error) {
                                alert('Error extracting text: ' + data.error);
                            } else if (data.text) {
                                // Insert text at cursor position or at the end
                                const range = quill.getSelection(true);
                                quill.insertText(range.index, '\n' + data.text + '\n');
                                alert('Document imported successfully!');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Failed to process document.');
                        } finally {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            this.value = ''; // Reset file input
                        }
                    });
                </script>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="lg:col-span-4 space-y-8">
        <div class="bg-white rounded-2xl p-10 shadow-2xl shadow-slate-200/50 border border-white">
            <h3 class="text-xl font-black text-gray-900 font-heading mb-6 tracking-tight">System Status</h3>
            <div class="space-y-6">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Model</span>
                    <span class="text-xs font-bold text-gray-700">Step-3.5-Flash</span>
                </div>
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Response Time</span>
                    <span class="text-xs font-bold text-emerald-600">~1.2s</span>
                </div>
            </div>
        </div>

        <div class="bg-indigo-600 rounded-2xl p-10 shadow-2xl shadow-indigo-200 relative overflow-hidden group">
            <div class="relative z-10 text-white">
                <h3 class="text-xl font-black font-heading mb-4 tracking-tight text-white">Live Testing</h3>
                <p class="text-indigo-100 text-sm font-medium mb-8 leading-relaxed">Instantly verify knowledge base updates using the integrated chat widget.</p>
                <button onclick="document.getElementById('chatToggleBtn').click()" class="w-full bg-white text-indigo-700 font-black py-4 rounded-lg shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all flex items-center justify-center space-x-2">
                    <i class="fas fa-comment-dots text-lg"></i>
                    <span>Test Updates</span>
                </button>
            </div>
            <!-- Decorative Icon -->
            <div class="absolute -right-6 -bottom-6 text-9xl text-white/10 group-hover:rotate-12 transition-transform duration-700">
                <i class="fas fa-robot"></i>
            </div>
        </div>
    </div>
</div>

<?php 
include __DIR__ . '/../../includes/chatbot-widget.php';
include __DIR__ . '/../../includes/admin-layout-footer.php'; 
?>
