<nav class="sticky top-0 z-[9999] w-full">
    <div class="w-full">
        <div class="glass-morphism shadow-premium px-4 md:px-12 py-2 md:py-6 border-b border-white/50">
            <div class="flex items-center justify-between gap-4">
                <!-- Logo -->
                <a href="dashboard.php" class="flex items-center space-x-3 group shrink-0">
                    <img src="<?php echo BASE_URL; ?>/img/logo.png" alt="<?php echo APP_NAME; ?>" class="w-8 h-8 md:w-10 md:h-10 object-contain group-hover:rotate-6 transition-transform duration-300">
                    <div class="hidden sm:block">
                        <span class="text-xl font-black tracking-tighter font-heading text-gray-900 leading-none"><?php echo APP_NAME; ?></span>
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-primary-600">User Portal</div>
                    </div>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-2 lg:space-x-4">
                    <a href="dashboard.php" class="px-4 xl:px-6 py-3 rounded-xl text-base xl:text-lg font-black tracking-tight <?php echo str_contains($_SERVER['PHP_SELF'], 'dashboard.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-600' ?> transition-all duration-300 whitespace-nowrap">
                        <i class="fas fa-desktop mr-2 opacity-70"></i>Live Queue
                    </a>
                    <a href="get-ticket.php" class="px-4 xl:px-6 py-3 rounded-xl text-base xl:text-lg font-black tracking-tight <?php echo str_contains($_SERVER['PHP_SELF'], 'get-ticket.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-600' ?> transition-all duration-300 whitespace-nowrap">
                        <i class="fas fa-ticket-alt mr-2 opacity-70"></i>Get Ticket
                    </a>
                    <a href="my-ticket.php" class="px-4 xl:px-6 py-3 rounded-xl text-base xl:text-lg font-black tracking-tight <?php echo str_contains($_SERVER['PHP_SELF'], 'my-ticket.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-600' ?> transition-all duration-300 whitespace-nowrap">
                        <i class="fas fa-user-tag mr-2 opacity-70"></i>My Ticket
                    </a>
                    <a href="history.php" class="px-4 xl:px-6 py-3 rounded-xl text-base xl:text-lg font-black tracking-tight <?php echo str_contains($_SERVER['PHP_SELF'], 'history.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-600' ?> transition-all duration-300 whitespace-nowrap">
                        <i class="fas fa-history mr-2 opacity-70"></i>History
                    </a>
                </div>

                <!-- Right Side: Profile -->
                <div class="flex items-center space-x-3">
                    <!-- Desktop Profile Dropdown -->
                    <button id="userDropdownButton" data-dropdown-toggle="userDropdown" class="hidden lg:flex items-center space-x-2 p-1 pl-3 bg-gray-50/50 rounded-xl hover:bg-gray-100 transition-all border border-gray-100">
                        <span class="text-xs font-black text-gray-700"><?php echo explode(' ', $_SESSION['full_name'] ?? 'User')[0]; ?></span>
                        <img class="w-8 h-8 rounded-lg" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=15803d&color=fff&font-size=0.33" alt="user photo">
                    </button>

                    <!-- Mobile menu toggle & Profile -->
                    <button id="userDropdownButtonMobile" data-dropdown-toggle="userDropdownMobile" class="flex lg:hidden items-center bg-gray-50/50 rounded-xl p-1 border border-gray-100">
                        <img class="w-8 h-8 rounded-lg" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=15803d&color=fff&font-size=0.33" alt="user photo">
                    </button>
                    <button data-collapse-toggle="navbar-user" type="button" class="inline-flex lg:hidden items-center p-2 w-10 h-10 justify-center text-gray-600 rounded-xl hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all bg-gray-50 border border-gray-100">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Dropdowns & Mobile Menu -->
        <div id="userDropdown" class="z-[110] hidden bg-white/95 divide-y divide-gray-100 rounded-2xl shadow-2xl w-56 border border-gray-100 backdrop-blur-xl overflow-hidden">
            <div class="px-6 py-4 text-sm text-gray-900 border-b border-gray-50/50">
                <div class="font-black text-[10px] uppercase tracking-widest text-gray-400 mb-1">Signed in as</div>
                <div class="truncate font-black text-primary-900 text-base"><?php echo $_SESSION['full_name'] ?? 'User'; ?></div>
                <div class="text-[10px] font-bold text-gray-500 truncate mt-0.5"><?php echo $_SESSION['school_id'] ?? ''; ?></div>
            </div>
            <ul class="py-2 text-sm text-gray-700">
                <li>
                    <a href="profile.php" class="flex items-center px-6 py-3 font-bold text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-user-circle mr-3 opacity-60"></i>Account Settings
                    </a>
                </li>
            </ul>
            <div class="py-2">
                <a href="../logout.php" class="flex items-center px-6 py-3 font-bold text-red-600 hover:bg-red-50 transition-colors">
                    <i class="fas fa-power-off mr-3 opacity-60"></i>Sign out
                </a>
            </div>
        </div>

        <!-- Mobile Profile Dropdown -->
        <div id="userDropdownMobile" class="z-[110] hidden bg-white divide-y divide-gray-100 rounded-2xl shadow-2xl w-64 border border-gray-100 backdrop-blur-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Profile</p>
                <p class="font-black text-gray-900 mt-1"><?php echo $_SESSION['full_name'] ?? 'User'; ?></p>
            </div>
            <ul class="py-2 text-sm text-gray-700">
                <li><a href="profile.php" class="block px-6 py-3 font-bold text-gray-700">Settings</a></li>
                <li><a href="../logout.php" class="block px-6 py-3 font-bold text-red-600">Logout</a></li>
            </ul>
        </div>

        <!-- Mobile Navigation Menu -->
        <div class="hidden w-full md:hidden px-4 pb-4" id="navbar-user">
            <ul class="flex flex-col font-black space-y-2 p-2 bg-gray-50/50 rounded-[2rem] border border-gray-100">
                <li>
                    <a href="dashboard.php" class="flex items-center py-4 px-6 rounded-2xl <?php echo str_contains($_SERVER['PHP_SELF'], 'dashboard.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-white' ?>">
                        <i class="fas fa-desktop mr-4 text-lg"></i>Live Queue
                    </a>
                </li>
                <li>
                    <a href="get-ticket.php" class="flex items-center py-4 px-6 rounded-2xl <?php echo str_contains($_SERVER['PHP_SELF'], 'get-ticket.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-white' ?>">
                        <i class="fas fa-ticket-alt mr-4 text-lg"></i>Get Ticket
                    </a>
                </li>
                <li>
                    <a href="my-ticket.php" class="flex items-center py-4 px-6 rounded-2xl <?php echo str_contains($_SERVER['PHP_SELF'], 'my-ticket.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-white' ?>">
                        <i class="fas fa-user-tag mr-4 text-lg"></i>My Ticket
                    </a>
                </li>
                <li>
                    <a href="history.php" class="flex items-center py-4 px-6 rounded-2xl <?php echo str_contains($_SERVER['PHP_SELF'], 'history.php') ? 'bg-primary-600 text-white shadow-lg' : 'text-gray-600 hover:bg-white' ?>">
                        <i class="fas fa-history mr-4 text-lg"></i>History
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
