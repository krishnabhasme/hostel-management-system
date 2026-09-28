<?php
/**
 * Header Include - Sipna Hostel Management System
 * Preserves Exact Stitch Design System & Colors with Bootstrap 5 + CSS Support
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? 'Sipna College - Hostel Management System';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-surface-variant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS Utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS with CDN and Stitch Custom Theme -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
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
                        "DEFAULT": "0.25rem",
                        "sm": "0.125rem",
                        "md": "0.375rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    spacing: {
                        "sidebar": "260px",
                        "sidebar-width": "260px",
                        "sidebar-collapsed": "72px",
                        "card-padding": "1.25rem",
                        "table-cell-x": "1rem",
                        "table-cell-y": "0.75rem",
                        "gutter": "1.5rem",
                        "margin-mobile": "1rem"
                    },
                    width: {
                        "sidebar": "260px",
                        "sidebar-width": "260px"
                    },
                    margin: {
                        "sidebar": "260px",
                        "sidebar-width": "260px"
                    },
                    fontFamily: {
                        "body-main": ["Inter", "sans-serif"],
                        "headline-md": ["Inter", "sans-serif"],
                        "display-lg": ["Inter", "sans-serif"],
                        "title-sm": ["Inter", "sans-serif"],
                        "label-caps": ["Inter", "sans-serif"],
                        "table-data": ["Inter", "sans-serif"]
                    },
                    fontSize: {
                        "label-caps": ["11px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700" }],
                        "table-data": ["13px", { "lineHeight": "16px", "fontWeight": "500" }],
                        "display-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                        "body-sm": ["13px", { "lineHeight": "18px", "fontWeight": "400" }],
                        "title-sm": ["18px", { "lineHeight": "24px", "fontWeight": "600" }],
                        "headline-md-mobile": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "body-main": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "display-lg": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Project CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="flex h-full min-h-screen overflow-x-hidden text-on-surface bg-surface font-body-main text-body-main antialiased selection:bg-primary-container selection:text-on-primary-container">
