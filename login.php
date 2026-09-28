<?php
/**
 * Login Page - Sipna Hostel Management System
 * Preserves 100% of Stitch Login UI with Bootstrap 5 + CSS Support
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && ($admin['password'] === $password || password_verify($password, $admin['password']))) {
                $_SESSION['admin_id'] = $admin['username'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];

                setFlashMessage('success', 'Welcome, ' . htmlspecialchars($admin['full_name']) . '!');
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="h-full bg-surface-variant" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Sipna College Hostel Management System - Login</title>
    
    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS Utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css" rel="stylesheet">

    <!-- Tailwind CSS with CDN & Stitch Config -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-variant": "#e2e2e2",
                        "surface-container-high": "#e8e8e8",
                        "tertiary-fixed": "#ffdcbe",
                        "on-surface-variant": "#454652",
                        "tertiary-container": "#492800",
                        "inverse-on-surface": "#f1f1f1",
                        "on-primary-fixed-variant": "#343d96",
                        "on-primary-fixed": "#000767",
                        "on-primary-container": "#8690ee",
                        "on-error": "#ffffff",
                        "table-border": "#DEE2E6",
                        "surface-container-low": "#f3f3f3",
                        "on-primary": "#ffffff",
                        "tertiary": "#2c1600",
                        "on-secondary-fixed": "#1a1c1c",
                        "outline-variant": "#c6c5d4",
                        "on-tertiary-fixed": "#2c1600",
                        "secondary-fixed-dim": "#c6c6c7",
                        "on-secondary": "#ffffff",
                        "error": "#D32F2F",
                        "success": "#2E7D32",
                        "on-tertiary": "#ffffff",
                        "primary": "#000666",
                        "on-surface": "#1a1c1c",
                        "surface-tint": "#4c56af",
                        "tertiary-fixed-dim": "#ffb870",
                        "secondary": "#5d5f5f",
                        "inverse-primary": "#bdc2ff",
                        "surface-bright": "#f9f9f9",
                        "on-tertiary-fixed-variant": "#693c00",
                        "surface-container-lowest": "#ffffff",
                        "surface-dim": "#dadada",
                        "error-container": "#ffdad6",
                        "primary-container": "#1a237e",
                        "primary-fixed-dim": "#bdc2ff",
                        "on-secondary-container": "#616363",
                        "surface-container-highest": "#e2e2e2",
                        "surface": "#f9f9f9",
                        "background": "#f9f9f9",
                        "on-secondary-fixed-variant": "#454747",
                        "on-tertiary-container": "#dc8200",
                        "surface-container": "#eeeeee",
                        "on-error-container": "#93000a",
                        "warning": "#ED6C02",
                        "on-background": "#1a1c1c",
                        "primary-fixed": "#e0e0ff",
                        "sidebar-text": "#E8EAF6",
                        "outline": "#767683",
                        "secondary-fixed": "#e2e2e2",
                        "secondary-container": "#dfe0e0",
                        "inverse-surface": "#2f3131"
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    spacing: {
                        "card-padding": "1.25rem",
                        "table-cell-x": "1rem",
                        "table-cell-y": "0.75rem",
                        "sidebar-width": "260px",
                        "sidebar-collapsed": "72px",
                        "gutter": "1.5rem",
                        "margin-mobile": "1rem"
                    },
                    fontFamily: {
                        "body-main": ["Inter", "sans-serif"],
                        "headline-md": ["Inter", "sans-serif"],
                        "title-sm": ["Inter", "sans-serif"],
                        "label-caps": ["Inter", "sans-serif"],
                        "table-data": ["Inter", "sans-serif"]
                    },
                    fontSize: {
                        "label-caps": ["11px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700" }],
                        "table-data": ["13px", { "lineHeight": "16px", "fontWeight": "500" }],
                        "body-sm": ["13px", { "lineHeight": "18px", "fontWeight": "400" }],
                        "title-sm": ["18px", { "lineHeight": "24px", "fontWeight": "600" }],
                        "body-main": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Project CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="h-full flex items-center justify-center bg-surface-variant font-body-main text-on-surface antialiased p-4">
    <!-- Background Gradient (Subtle) -->
    <div class="fixed inset-0 z-0 bg-gradient-to-br from-surface-variant to-surface-container-high pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-md bg-surface border border-table-border rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden my-auto">
        <!-- Header Section with Official College Logo -->
        <div class="bg-surface-bright border-b border-table-border px-card-padding py-8 flex flex-col items-center text-center">
            <div class="w-20 h-20 rounded-2xl bg-white p-1.5 flex items-center justify-center mb-4 shadow-sm border border-table-border">
                <img src="images/logo.png" alt="Sipna College Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="font-headline-md text-xl text-primary tracking-tight font-bold">Sipna College</h1>
            <h2 class="font-title-sm text-sm text-on-surface-variant mt-1">Hostel Management System</h2>
        </div>

        <!-- Form Section -->
        <div class="px-card-padding py-6">
            <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 bg-error-container text-on-error-container rounded-lg text-sm flex items-center gap-2 border border-error/20">
                <span class="material-symbols-outlined text-error text-lg">error</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="login.php" class="space-y-5" method="POST">
                <!-- Username -->
                <div>
                    <label class="block font-label-caps text-label-caps text-on-surface uppercase mb-1.5" for="username">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">person</span>
                        </div>
                        <input class="block w-full pl-10 pr-3 py-2.5 text-body-main bg-surface border border-table-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-shadow placeholder:text-outline text-on-surface" id="username" name="username" placeholder="Username (admin)" type="text" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>" required>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block font-label-caps text-label-caps text-on-surface uppercase" for="password">Password</label>
                        <span class="font-body-sm text-xs text-on-surface-variant">Default: admin123</span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-[18px] text-on-surface-variant">lock</span>
                        </div>
                        <input class="block w-full pl-10 pr-10 py-2.5 text-body-main bg-surface border border-table-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-shadow placeholder:text-outline text-on-surface" id="password" name="password" placeholder="••••••••" type="password" value="admin123" required>
                        <button id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-on-surface-variant hover:text-on-surface transition-colors" type="button">
                            <span class="material-symbols-outlined text-[18px]">visibility_off</span>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm font-title-sm text-title-sm text-on-primary bg-primary hover:bg-primary-container focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors cursor-pointer" type="submit">
                        Sign in to Dashboard
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer Info -->
        <div class="bg-surface-container-low border-t border-table-border px-card-padding py-4 text-center">
            <p class="font-body-sm text-body-sm text-on-surface-variant">DBMS Mini Project</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Project JavaScript -->
    <script src="js/script.js"></script>
</body>
</html>
