<?php
/**
 * Top Navbar Component - Sipna Hostel Management System
 */
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
?>
<!-- TopNavBar (Shared Sticky Component) -->
<header class="h-16 bg-surface dark:bg-surface-dim border-b border-table-border flex justify-between items-center px-gutter w-full z-30 shrink-0 sticky top-0">
    <!-- Left: Mobile Menu Button -->
    <div class="flex items-center gap-4">
        <button id="mobileMenuBtn" type="button" class="md:hidden text-primary p-2 -ml-2 rounded-lg hover:bg-surface-container-low transition-colors" title="Toggle Navigation Menu">
            <span class="material-symbols-outlined">menu</span>
        </button>
    </div>
    
    <!-- Right: Notifications & Profile Pill -->
    <div class="flex items-center gap-4 ml-auto">
        <!-- Notification Bell -->
        <a href="complaints.php" class="relative p-2 text-on-surface-variant hover:bg-surface-container-low rounded-full transition-colors cursor-pointer active:opacity-80" title="Active Complaints">
            <span class="material-symbols-outlined" data-icon="notifications">notifications</span>
            <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full ring-2 ring-surface"></span>
        </a>
        
        <div class="h-8 w-px bg-table-border mx-1"></div>
        
        <!-- User Profile Pill -->
        <div class="flex items-center gap-3 p-1 pr-3 rounded-full border border-transparent">
            <div class="w-8 h-8 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-xs shrink-0 border border-primary/20">
                AD
            </div>
            <div class="hidden sm:flex flex-col items-start leading-tight">
                <span class="font-title-sm text-sm text-primary dark:text-primary-fixed font-semibold"><?= htmlspecialchars($admin_name) ?></span>
                <span class="font-label-caps text-[10px] text-on-surface-variant uppercase">Administrator</span>
            </div>
        </div>
    </div>
</header>
