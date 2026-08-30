<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /staff/dashboard.php');
    }
    exit;
}

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - SalesTrack</title>
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
                        }
                    },
                    fontFamily: {
                        sans: ['Open Sans', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    boxShadow: {
                        'card': '0px 0px 24px 0px rgba(0, 0, 0, 0.05)',
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-brand-50 text-brand-700 flex items-center justify-center p-4 antialiased selection:bg-brand-500 selection:text-white">
    <div class="w-full max-w-md bg-white rounded-md shadow-card overflow-hidden border border-brand-200">
        <!-- Brand Header -->
        <div class="bg-[#6e4598] text-white p-8 text-center">
            <h1 class="text-3xl font-extrabold tracking-tight text-white">SalesTrack</h1>
            <p class="text-white/80 text-sm mt-1 font-medium">Sales Monitoring & Management Panel</p>
        </div>

        <!-- Login Form -->
        <div class="p-6 sm:p-8 space-y-6">
            <?php if ($flashError): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-md text-sm font-semibold flex items-center gap-3" role="alert">
                    <i class="fas fa-exclamation-circle text-lg text-red-500"></i>
                    <span><?= e($flashError); ?></span>
                </div>
            <?php endif; ?>

            <form action="/actions/login.php" method="POST" class="space-y-5">
                <?= getCsrfField(); ?>

                <div>
                    <label for="username" class="block text-sm font-semibold text-brand-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-brand-300 text-sm">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" id="username" name="username" required autofocus
                            class="w-full pl-10 pr-4 py-2.5 rounded-md border border-brand-200 text-brand-700 text-sm focus:outline-none focus:border-brand-500 transition-colors placeholder:text-brand-300"
                            placeholder="Enter username">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-brand-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-brand-300 text-sm">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="w-full pl-10 pr-4 py-2.5 rounded-md border border-brand-200 text-brand-700 text-sm focus:outline-none focus:border-brand-500 transition-colors placeholder:text-brand-300"
                            placeholder="Enter password">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 bg-brand-500 hover:bg-brand-600 active:scale-98 text-white font-semibold rounded-md shadow-sm transition-colors text-sm uppercase tracking-wider">
                    Sign In <i class="fas fa-arrow-right ml-1"></i>
                </button>
            </form>
        </div>

        <div class="bg-brand-50 px-8 py-4 text-center text-xs text-brand-300 border-t border-brand-200">
            SalesTrack &copy; <?= date('Y'); ?>
        </div>
    </div>
</body>
</html>
