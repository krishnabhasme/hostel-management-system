<?php
/**
 * Sidebar Navigation Component - Sipna Hostel Management System
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- SideNavBar (Desktop & Tablet) -->
<aside id="sidebarNav" class="fixed left-0 top-0 h-full w-[260px] bg-primary dark:bg-primary-container border-r border-outline-variant flex flex-col overflow-y-auto transition-all duration-200 ease-in-out hidden md:flex z-40">
    <!-- Brand Header with Official College Logo -->
    <div class="p-6 border-b border-white/10 flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-white p-1 flex items-center justify-center shrink-0 overflow-hidden shadow-sm">
            <img src="images/logo.png" alt="Sipna College Logo" class="w-full h-full object-contain">
        </div>
        <div class="min-w-0">
            <h1 class="font-headline-md text-base text-on-primary dark:text-on-primary-container font-bold truncate">Sipna Hostel</h1>
            <p class="font-label-caps text-label-caps text-sidebar-text/80 truncate mt-0.5">Admin Management</p>
        </div>
    </div>
    
    <!-- Navigation Links -->
    <nav class="flex-1 py-4 flex flex-col gap-1">
        <!-- Dashboard -->
        <a class="flex items-center gap-3 px-4 py-3 <?= $current_page == 'dashboard.php' ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="dashboard.php">
            <span class="material-symbols-outlined" data-icon="dashboard">dashboard</span>
            <span class="font-label-caps text-label-caps">Dashboard</span>
        </a>

        <!-- Students -->
        <a class="flex items-center gap-3 px-4 py-3 <?= ($current_page == 'students.php' || $current_page == 'student_profile.php') ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="students.php">
            <span class="material-symbols-outlined" data-icon="person">person</span>
            <span class="font-label-caps text-label-caps">Students</span>
        </a>

        <!-- Rooms -->
        <a class="flex items-center gap-3 px-4 py-3 <?= $current_page == 'rooms.php' ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="rooms.php">
            <span class="material-symbols-outlined" data-icon="bed">bed</span>
            <span class="font-label-caps text-label-caps">Rooms</span>
        </a>

        <!-- Hostel Blocks -->
        <a class="flex items-center gap-3 px-4 py-3 <?= $current_page == 'hostel_blocks.php' ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="hostel_blocks.php">
            <span class="material-symbols-outlined" data-icon="domain">domain</span>
            <span class="font-label-caps text-label-caps">Hostel Blocks</span>
        </a>

        <!-- Allocations -->
        <a class="flex items-center gap-3 px-4 py-3 <?= $current_page == 'allocations.php' ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="allocations.php">
            <span class="material-symbols-outlined" data-icon="assignment">assignment</span>
            <span class="font-label-caps text-label-caps">Allocations</span>
        </a>

        <!-- Fees -->
        <a class="flex items-center gap-3 px-4 py-3 <?= $current_page == 'fees.php' ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="fees.php">
            <span class="material-symbols-outlined" data-icon="payments">payments</span>
            <span class="font-label-caps text-label-caps">Fees</span>
        </a>

        <!-- Complaints -->
        <a class="flex items-center gap-3 px-4 py-3 <?= $current_page == 'complaints.php' ? 'bg-primary-container/20 border-l-4 border-tertiary-fixed-dim text-on-primary font-bold' : 'text-sidebar-text/80 hover:bg-on-primary/10 hover:text-on-primary border-l-4 border-transparent' ?> transition-colors" href="complaints.php">
            <span class="material-symbols-outlined" data-icon="report_problem">report_problem</span>
            <span class="font-label-caps text-label-caps">Complaints</span>
        </a>
    </nav>
    
    <!-- Footer Links (Logout) -->
    <div class="p-4 border-t border-white/10 mt-auto flex flex-col gap-1">
        <a class="flex items-center gap-3 px-4 py-3 text-red-300 hover:text-red-100 hover:bg-red-900/20 transition-colors border-l-4 border-transparent rounded-lg" href="logout.php">
            <span class="material-symbols-outlined" data-icon="logout">logout</span>
            <span class="font-label-caps text-label-caps">Logout</span>
        </a>
    </div>
</aside>

<!-- Overlay for Mobile Sidebar -->
<div id="sidebar-overlay" class="fixed inset-0 bg-on-surface/50 z-30 hidden md:hidden"></div>
