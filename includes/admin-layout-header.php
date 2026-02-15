<?php
// Modern Admin Layout Header
require_once __DIR__ . '/../config/config.php';
requireLogin();
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        const ANTIGRAVITY_BASE_URL = "<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>";
    </script>
    <script src="../js/dashboard-refresh.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-active { background: rgba(255, 255, 255, 0.1); border-left: 4px solid #ffcd00; }
        .glass-sidebar { background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); }
        
        /* Desktop-only font scaling */
        @media (min-width: 1024px) {
            html { font-size: 18px; } /* Scales rem-based values */
            body { font-size: 1.125rem; }
            .text-sm { font-size: 1.125rem !important; }
            .text-xs { font-size: 1rem !important; }
            .text-\[10px\] { font-size: 0.875rem !important; }
        }
    </style>
</head>
<body class="min-h-screen flex">
    <!-- Sidebar -->
    <?php 
    $isDashboard = str_contains($_SERVER['PHP_SELF'], 'dashboard.php');
    $sidebarClass = $isDashboard ? '-translate-x-full' : '';
    $mainContentClass = $isDashboard ? 'ml-0' : 'ml-72';
    ?>
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 w-72 glass-sidebar text-white z-50 flex flex-col transition-all duration-300 <?php echo $sidebarClass; ?>">
        <div class="p-8 border-b border-white/10">
            <a href="dashboard.php" class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center shadow-lg shadow-primary-900/20">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black font-heading tracking-tighter leading-none"><?php echo APP_NAME; ?></h1>
                    <p class="text-[10px] font-black uppercase tracking-widest text-primary-400 mt-1">Administrator</p>
                </div>
            </a>
        </div>

        <nav class="flex-1 px-4 py-8 space-y-1">
            <a href="dashboard.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'dashboard.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-chart-pie mr-4 text-lg"></i>Dashboard
            </a>
            <a href="analytics.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'analytics.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-chart-line mr-4 text-lg"></i>Analytics
            </a>
            <a href="sentiment-analytics.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'sentiment-analytics.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-brain mr-4 text-lg"></i>Sentiment
            </a>
            
            <div class="my-6 px-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">Management</p>
            </div>

            <a href="windows.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'windows.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-desktop mr-4 text-lg"></i>Windows
            </a>
            <a href="services.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'services.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-concierge-bell mr-4 text-lg"></i>Services
            </a>
            <a href="users.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'users.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-users-cog mr-4 text-lg"></i>Users
            </a>
            <a href="chatbot.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'chatbot.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-robot mr-4 text-lg"></i>AI Settings
            </a>
            <a href="history.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-bold tracking-tight transition-all duration-300 <?php echo str_contains($_SERVER['PHP_SELF'], 'history.php') ? 'bg-primary-600/20 text-white border-l-4 border-accent-500 pl-5' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                <i class="fas fa-history mr-4 text-lg"></i>Ticket History
            </a>
        </nav>

        <div class="p-4 mt-auto border-t border-white/5">
            <a href="../logout.php" class="flex items-center px-6 py-4 rounded-lg text-sm font-black text-red-400 hover:bg-red-500/10 transition-all">
                <i class="fas fa-power-off mr-4 text-lg"></i>Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div id="admin-main-content" class="flex-1 <?php echo $mainContentClass; ?> min-h-screen flex flex-col transition-all duration-300">
        <!-- Top Header -->
        <header class="h-24 glass-morphism sticky top-0 z-40 border-b border-slate-200/50 px-10 flex items-center justify-between shadow-sm">
            <div class="flex items-center flex-1 max-w-xl">
                <button onclick="toggleSidebar()" class="mr-6 text-slate-400 hover:text-primary-600 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <!-- Search Bar -->
                <div class="relative group flex-1 max-w-xl">
                    <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-primary transition-colors">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" id="adminSearchInput" placeholder="Intelligence Search... (Ctrl+K)" class="w-full bg-slate-100/50 border-none rounded-xl py-3 pl-14 pr-8 text-sm font-bold focus:ring-4 focus:ring-primary-100 focus:bg-white transition-all">
                </div>

                <script>
                    function toggleSidebar() {
                        const sidebar = document.getElementById('admin-sidebar');
                        const mainContent = document.getElementById('admin-main-content');
                        
                        if (sidebar.classList.contains('-translate-x-full')) {
                            sidebar.classList.remove('-translate-x-full');
                            mainContent.classList.remove('ml-0');
                            mainContent.classList.add('ml-72');
                        } else {
                            sidebar.classList.add('-translate-x-full');
                            mainContent.classList.remove('ml-72');
                            mainContent.classList.add('ml-0');
                        }
                    }

                    document.addEventListener('keydown', function(e) {
                        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                            e.preventDefault();
                            document.getElementById('adminSearchInput').focus();
                        }
                    });
                </script>
            </div>

            <div class="flex items-center space-x-6">
                
                <!-- Status Badge -->
                <div class="hidden lg:flex items-center space-x-2 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest">System Live</span>
                </div>

                <div class="h-8 w-px bg-slate-200"></div>

                <!-- Profile -->
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-xs font-black text-gray-900 leading-none">Admin User</p>
                        <p class="text-[10px] font-black text-primary-600 uppercase tracking-widest mt-1">Executive</p>
                    </div>
                    <img class="w-11 h-11 rounded-xl shadow-lg" src="https://ui-avatars.com/api/?name=Admin&background=0c4b05&color=fff" alt="admin photo">
                </div>
            </div>
        </header>

        <!-- Main View Area -->
        <main class="flex-1 p-4">
