<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin': header('Location: admin/dashboard.php'); break;
        case 'staff': header('Location: staff/dashboard.php'); break;
        default: header('Location: user/dashboard.php'); break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISPSC - Main Registrar E-Queue System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php injectTailwindConfig(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-bg {
            background-image: linear-gradient(to right, rgba(26,1,1,0.95) 0%, rgba(26,1,1,0.6) 100%), url('img/drone.png');
            background-size: cover;
            background-position: center;
        }
        .glass-nav {
            background: rgba(26, 1, 1, 0.5);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        .text-glow {
            text-shadow: 0 0 20px rgba(139, 1, 1, 0.2); 
        }
        .text-glow-secondary {
            text-shadow: 0 0 20px rgba(12, 75, 5, 0.3);
        }
    </style>
</head>
<body class="bg-primary-950 text-white font-sans selection:bg-primary-500/30">

    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav">
        <div class="container-ultra px-4 md:px-12 py-4 md:py-6 flex items-center justify-between relative">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-lg overflow-hidden">
                    <img src="img/logo.png" alt="ISPSC Logo" class="w-8 h-8 object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="text-lg md:text-2xl font-black tracking-tight font-heading text-white leading-none uppercase">ISPSC</span>
                    <span class="text-[8px] md:text-[10px] font-bold text-primary-300 tracking-widest uppercase">Main Registrar E-Queue System</span>
                </div>
            </div>
            
            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center space-x-8 text-xs font-black uppercase tracking-[0.2em] text-gray-400">
                <a href="#features" class="hover:text-white transition-colors">Features</a>
                <a href="#stats" class="hover:text-white transition-colors">Metrics</a>
                <a href="#about" class="hover:text-white transition-colors">Vision</a>
                <div class="h-6 w-px bg-white/10 mx-2"></div>
                <a href="login.php" class="hover:text-primary-400 transition-colors">Login</a>
                <a href="register.php" class="bg-white text-slate-900 px-6 py-2.5 rounded-xl font-black transition-all hover:bg-primary-500 hover:text-white">Register</a>
            </div>

            <!-- Mobile Menu Toggle -->
            <button id="mobile-menu-toggle" class="lg:hidden text-white p-2">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <!-- Mobile Menu Dropdown -->
            <div id="mobile-menu" class="hidden absolute top-[calc(100%+1px)] left-4 right-4 bg-primary-900/95 backdrop-blur-3xl border border-white/10 p-8 flex flex-col space-y-8 lg:hidden rounded-[30px] shadow-ultra animate-in fade-in zoom-in-95 duration-300 origin-top">
                <div class="flex flex-col space-y-6">
                    <a href="#features" class="text-sm font-black uppercase tracking-[0.2em] text-gray-300 hover:text-white flex items-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mr-3"></span>
                        Features
                    </a>
                    <a href="#stats" class="text-sm font-black uppercase tracking-[0.2em] text-gray-300 hover:text-white flex items-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-secondary-500 mr-3"></span>
                        Metrics
                    </a>
                    <a href="#about" class="text-sm font-black uppercase tracking-[0.2em] text-gray-300 hover:text-white flex items-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-300 mr-3"></span>
                        Vision
                    </a>
                </div>
                
                <div class="h-px w-full bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
                
                <div class="flex flex-col gap-4">
                    <a href="login.php" class="flex items-center justify-center px-6 py-5 rounded-2xl bg-white/5 border border-white/10 text-xs font-black uppercase tracking-[0.2em] hover:bg-white/10 transition-all text-white">Login to Account</a>
                    <a href="register.php" class="flex items-center justify-center px-6 py-5 rounded-2xl bg-primary-600 text-white text-xs font-black uppercase tracking-[0.2em] shadow-xl shadow-primary-600/20 active:scale-95 transition-all">Create New Account</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center pt-20 overflow-hidden hero-bg">
        <div class="container-ultra px-6 md:px-12 relative z-10 w-full">
            <!-- Session Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="max-w-4xl mb-8 p-5 text-secondary-400 bg-secondary-500/10 rounded-2xl border border-secondary-500/20 flex items-center animate-in fade-in slide-in-from-top-4 duration-500">
                    <i class="fas fa-check-circle mr-4 text-xl"></i>
                    <span class="font-bold uppercase tracking-widest text-[10px]"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="max-w-4xl mb-8 p-5 text-primary-400 bg-primary-500/10 rounded-2xl border border-primary-500/20 flex items-center animate-shake">
                    <i class="fas fa-exclamation-triangle mr-4 text-xl"></i>
                    <span class="font-bold uppercase tracking-widest text-[10px]"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <div class="max-w-4xl">
                
                <h1 class="text-5xl md:text-[7rem] font-black leading-[0.9] font-heading tracking-tighter mb-8 text-glow drop-shadow-2xl">
                    ELIMINATE THE <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-secondary-500">WAITING LINE.</span>
                </h1>
                
                <p class="text-lg md:text-2xl text-slate-300 font-medium max-w-2xl leading-relaxed mb-12 drop-shadow-md">
                    Experience a revolutionary queue management ecosystem driven by real-time optics and predictive intelligence. Seamless, silent, and superior.
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <a href="register.php" class="w-full sm:w-auto bg-primary-600 text-white px-10 py-6 rounded-2xl font-black text-xl flex items-center justify-center space-x-4 hover:bg-primary-500 transition-all hover:shadow-2xl hover:shadow-primary-500/40 hover:-translate-y-1 active:scale-95 group">
                        <span>Register Now</span>
                        <i class="fas fa-rocket group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
                    </a>
                    <a href="#features" class="w-full sm:w-auto bg-white/5 backdrop-blur-md border border-white/10 text-white px-10 py-6 rounded-2xl font-black text-xl flex items-center justify-center space-x-4 hover:bg-white/10 transition-all active:scale-95">
                        <span>Learn More</span>
                        <i class="fas fa-play text-sm opacity-50"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 right-0 w-1/3 h-1/2 bg-secondary-600/10 blur-[120px] rounded-full translate-x-1/2 translate-y-1/2"></div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 bg-primary-950 relative">
        <div class="container-ultra px-6 md:px-12">
            <div class="text-center mb-24">
                <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.5em] text-primary-500 mb-4">Core Capabilities</p>
                <h2 class="text-4xl md:text-6xl font-black font-heading tracking-tight">ENGINEERED FOR PRECISION.</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1: Primary (Green) -->
                <div class="bg-white/5 border border-white/10 p-10 rounded-[40px] hover:bg-white/[0.08] transition-all group">
                    <div class="w-16 h-16 bg-primary-600/20 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bolt text-3xl text-primary-500"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4 font-heading text-white">Efficient Queue</h3>
                    <p class="text-slate-300 font-medium leading-relaxed">
                        Intelligent ticket distribution that balances load across windows instantly using proprietary algorithms.
                    </p>
                </div>

                <!-- Feature 2: Secondary (Emerald) -->
                <div class="bg-white/5 border border-white/10 p-10 rounded-[40px] hover:bg-white/[0.08] transition-all group">
                    <div class="w-16 h-16 bg-secondary-600/20 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-3xl text-secondary-500"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4 font-heading text-white">Real-Time Optics</h3>
                    <p class="text-slate-300 font-medium leading-relaxed">
                        Monitor every heartbeat of your facility with live dashboards and instant notification streams with secondary metrics.
                    </p>
                </div>

                <!-- Feature 3: Primary (Green/Gold Mix) -->
                <div class="bg-white/5 border border-white/10 p-10 rounded-[40px] hover:bg-white/[0.08] transition-all group">
                    <div class="w-16 h-16 bg-primary-600/20 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-brain text-3xl text-primary-500"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-4 font-heading text-white">AI Sentiment</h3>
                    <p class="text-slate-300 font-medium leading-relaxed">
                        Automated feedback analysis that understands your customers' emotions through natural language processing.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="stats" class="py-24 border-y border-white/5 bg-primary-900/50">
        <div class="container-ultra px-6 md:px-12 grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
            <div>
                <p class="text-4xl md:text-6xl font-black font-heading text-white mb-2">72<span class="text-primary-500">%</span></p>
                <p class="text-xs font-black uppercase tracking-widest text-gray-500">Uptime Reliability</p>
            </div>
            <div>
                <p class="text-4xl md:text-6xl font-black font-heading text-white mb-2">45<span class="text-primary-500">%</span></p>
                <p class="text-xs font-black uppercase tracking-widest text-gray-500">Wait Reduction</p>
            </div>
            <div>
                <p class="text-4xl md:text-6xl font-black font-heading text-white mb-2">200<span class="text-primary-500"></span></p>
                <p class="text-xs font-black uppercase tracking-widest text-gray-500">Daily Transactions</p>
            </div>
            <div>
                <p class="text-4xl md:text-6xl font-black font-heading text-white mb-2">25<span class="text-primary-500">ms</span></p>
                <p class="text-xs font-black uppercase tracking-widest text-gray-500">Lag Latency</p>
            </div>
        </div>
    </section>

    <!-- Enhanced Footer -->
    <footer class="py-20 bg-primary-950 border-t border-white/5">
        <div class="container-ultra px-6 md:px-12 flex flex-col md:flex-row justify-between items-center gap-10">
            <div class="flex items-center space-x-4 opacity-70">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1.5">
                    <img src="img/logo.png" alt="ISPSC Logo" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="text-lg font-black tracking-tight font-heading text-white leading-none uppercase">ISPSC</span>
                    <span class="text-[10px] font-bold text-primary-400 tracking-widest uppercase">Main E-Queue System</span>
                </div>
            </div>
            
            <p class="text-gray-500 font-medium text-sm">
                &copy; 2026 ISPSC Main Registrar. All rights reserved. Built for Excellence.
            </p>

            <div class="flex space-x-6 text-gray-500">
                <a href="#" class="hover:text-white transition-colors"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-white transition-colors"><i class="fab fa-github"></i></a>
                <a href="#" class="hover:text-white transition-colors"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </footer>

    <script>
        const menuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = menuToggle.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.classList.replace('fa-times', 'fa-bars');
            } else {
                icon.classList.contains('fa-bars') ? icon.classList.replace('fa-bars', 'fa-times') : icon.classList.add('fa-times');
            }
        });

        // Close menu on link click
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                menuToggle.querySelector('i').classList.replace('fa-times', 'fa-bars');
            });
        });
    </script>
</body>
</html>
