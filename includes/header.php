<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'SalesTrack';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> - SalesTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#FCFAFF',
                            100: '#F1EDF6',
                            200: '#E4DBED',
                            300: '#C9B8DC',
                            400: '#A78BC5',
                            500: '#6e4598',
                            600: '#5e3a82',
                            700: '#37234B',
                            800: '#2A1A3A',
                            900: '#1E1229',
                        },
                    },
                    fontFamily: {
                        sans: ['Open Sans', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    boxShadow: {
                        'card': '0px 0px 24px 0px rgba(0, 0, 0, 0.05)',
                    },
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #F1EDF6; }
        ::-webkit-scrollbar-thumb { background: #C9B8DC; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #A78BC5; }
        .product-card { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .product-card:hover { transform: translateY(-2px); }
        .nav-item { position: relative; transition: color 0.3s ease; }
        .nav-item.active { color: #6e4598; font-weight: 600; }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 50%;
            transform: translateY(-50%);
            height: 2.2rem;
            width: 0.25rem;
            border-top-right-radius: 0.4rem;
            border-bottom-right-radius: 0.4rem;
            background-color: #6e4598;
        }
    </style>
</head>
<body class="h-full bg-brand-50 text-brand-700 flex flex-col md:flex-row antialiased selection:bg-brand-500 selection:text-white">

    <!-- Mobile Header Bar -->
    <header class="md:hidden bg-white text-brand-700 flex items-center justify-between px-4 py-3.5 sticky top-0 z-40 border-b border-brand-200 shadow-card">
        <a href="<?= ($currentUser['role'] ?? '') === 'admin' ? '/admin/dashboard.php' : '/staff/dashboard.php'; ?>" class="flex items-center">
            <span class="text-xl font-extrabold tracking-tight text-[#6e4598]">SalesTrack</span>
        </a>
                <span class="text-[10px] text-brand-400 font-semibold uppercase tracking-wider block"><?= e($currentUser['role'] ?? 'System'); ?> Panel</span>
            </div>
        </div>
        <button id="mobileMenuBtn" type="button" aria-label="Toggle navigation menu" class="p-2 rounded-md text-brand-500 hover:text-white hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors duration-300">
            <svg id="menuIconOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg id="menuIconClose" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </header>

    <!-- App Container -->
    <div class="flex flex-1 w-full min-h-screen">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content Viewport -->
        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 w-full overflow-y-auto">
            <!-- Top Bar with Profile Dropdown -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-brand-500 text-xl lg:text-2xl leading-normal hidden md:block"><?= e($pageTitle); ?></h2>
                <div class="flex items-center gap-4 ml-auto">
                    <div class="relative">
                        <button class="flex items-center bg-white focus:outline-none text-sm duration-150 ease-in-out p-2 rounded-md shadow-card transition" id="profileDropdownBtn" type="button">
                            <div class="h-8 w-8 rounded-md bg-brand-100 flex items-center justify-center text-brand-500 font-bold text-sm">
                                <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="font-semibold text-sm hidden lg:block ml-2 text-brand-700"><?= e($currentUser['name'] ?? 'User'); ?></span>
                            <span class="ml-2 text-brand-500"><i class="fas fa-chevron-down text-xs"></i></span>
                        </button>
                        <ul class="border border-brand-200 absolute bg-white hidden mt-2 px-4 py-2 right-0 rounded-md shadow-card text-left w-max max-w-[90vw] z-[60]" id="profileDropdownMenu">
                            <li class="py-1.5 duration-300 hover:text-brand-500 text-sm text-brand-400 transition-colors">
                                <a href="<?= ($currentUser['role'] ?? '') === 'admin' ? '/admin/dashboard.php' : '/staff/dashboard.php'; ?>">
                                    <i class="fas fa-user w-5"></i> <span>Profile</span>
                                </a>
                            </li>
                            <li class="py-1.5 duration-300 hover:text-brand-500 text-sm text-brand-400 transition-colors border-t border-brand-200">
                                <a href="/actions/logout.php">
                                    <i class="fas fa-sign-out-alt w-5"></i> <span>Log Out</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Global Flash Messages -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-900 p-4 rounded-md shadow-card flex items-start justify-between gap-3" role="alert">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-9 h-9 rounded-md bg-green-500 text-white text-sm flex-shrink-0">
                            <i class="fas fa-check"></i>
                        </span>
                        <div>
                            <p class="font-bold text-sm text-green-900">Success</p>
                            <p class="text-sm text-green-700 mt-0.5"><?= e($_SESSION['flash_success']); ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-400 hover:text-green-700 p-1 text-lg font-bold">&times;</button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-900 p-4 rounded-md shadow-card flex items-start justify-between gap-3" role="alert">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-9 h-9 rounded-md bg-red-500 text-white text-sm flex-shrink-0">
                            <i class="fas fa-exclamation"></i>
                        </span>
                        <div>
                            <p class="font-bold text-sm text-red-900">Notice</p>
                            <p class="text-sm text-red-700 mt-0.5"><?= e($_SESSION['flash_error']); ?></p>
                        </div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-700 p-1 text-lg font-bold">&times;</button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
