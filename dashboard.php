<?php
/**
 * Admin Dashboard - Sipna Hostel Management System
 * Preserves 100% of Stitch UI Layout with Live Database Coordination
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Dashboard Overview - Sipna Hostel';

try {
    // 1. Total Students
    $total_students = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();

    // 2. Room & Bed Statistics (Live from allocations)
    $total_rooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    $total_beds = (int)$pdo->query("SELECT COALESCE(SUM(capacity), 0) FROM rooms")->fetchColumn();
    $occupied_beds = (int)$pdo->query("SELECT COUNT(*) FROM allocations WHERE status = 'Active'")->fetchColumn();
    $available_beds = max(0, $total_beds - $occupied_beds);
    $available_rooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms r WHERE r.status != 'Maintenance' AND (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.room_id AND a.status = 'Active') < r.capacity")->fetchColumn();
    $occupied_rooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms r WHERE (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.room_id AND a.status = 'Active') >= r.capacity")->fetchColumn();
    $occupancy_pct = $total_beds > 0 ? round(($occupied_beds / $total_beds) * 100) : 0;

    // 3. Pending Fees
    $pending_fees = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status IN ('Pending', 'Overdue')")->fetchColumn();
    $overdue_count = (int)$pdo->query("SELECT COUNT(*) FROM fees WHERE status = 'Overdue'")->fetchColumn();

    // 4. Active Complaints
    $active_complaints = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status != 'Resolved'")->fetchColumn();

    // 5. Recent Student Registrations
    $stmt = $pdo->query("SELECT 
        s.student_id,
        s.name,
        s.department,
        s.year,
        s.status as student_status,
        r.room_number,
        b.block_name,
        a.status as allocation_status
    FROM students s
    LEFT JOIN allocations a ON s.student_id = a.student_id AND a.status = 'Active'
    LEFT JOIN rooms r ON a.room_id = r.room_id
    LEFT JOIN blocks b ON r.block_id = b.block_id
    ORDER BY s.student_id DESC LIMIT 5");
    $recent_students = $stmt->fetchAll();

    // 6. Recent Complaints Feed
    $recent_complaints = $pdo->query("SELECT c.*, s.name as student_name FROM complaints c JOIN students s ON c.student_id = s.student_id ORDER BY c.created_at DESC LIMIT 4")->fetchAll();

    // Monthly Fee Collection Map
    $monthly_collections = [
        'Jul' => 0.0, 'Aug' => 0.0, 'Sep' => 0.0, 'Oct' => 0.0,
        'Nov' => 0.0, 'Dec' => 0.0, 'Jan' => 0.0
    ];

    // Check if any fees exist
    $fee_rows = $pdo->query("SELECT MONTH(payment_date) as m, SUM(amount) as amt FROM fees WHERE status = 'Paid' GROUP BY MONTH(payment_date)")->fetchAll();
    $month_map = [7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec', 1 => 'Jan'];
    foreach ($fee_rows as $frow) {
        $m_idx = (int)$frow['m'];
        if (isset($month_map[$m_idx])) {
            $monthly_collections[$month_map[$m_idx]] = round($frow['amt'] / 100000, 1);
        }
    }

} catch (Exception $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col md:ml-sidebar min-w-0 bg-surface">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <!-- Main Canvas -->
    <main class="flex-1 overflow-y-auto p-gutter pb-24">
        <!-- Dashboard Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h2 class="font-display-lg text-display-lg text-primary mb-1">Dashboard Overview</h2>
                <p class="text-on-surface-variant">Welcome back. Real-time status of hostel students, rooms, and fees.</p>
            </div>
            <div class="flex gap-3">
                <a href="students.php" class="px-4 py-2 bg-white border border-primary text-primary font-medium rounded-lg hover:bg-surface-container-low transition-colors text-sm flex items-center gap-2 shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    Manage Students
                </a>
                <a href="allocations.php" class="px-4 py-2 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors text-sm shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Allocation
                </a>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <!-- Card 1 -->
            <div class="bg-white rounded-xl p-card-padding border border-table-border flex flex-col justify-between hover:shadow-[0px_4px_12px_rgba(0,0,0,0.03)] transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">school</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-success text-xs font-medium bg-success/10 px-2 py-1 rounded-full">
                        <span class="material-symbols-outlined text-[12px]">trending_up</span> Active
                    </span>
                </div>
                <div>
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Total Students</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface"><?= $total_students ?></h3>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-white rounded-xl p-card-padding border border-table-border flex flex-col justify-between hover:shadow-[0px_4px_12px_rgba(0,0,0,0.03)] transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">meeting_room</span>
                    </div>
                    <span class="text-xs text-on-surface-variant font-medium">
                        <?= $available_beds ?> Beds Free
                    </span>
                </div>
                <div>
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Available Rooms</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface"><?= $available_rooms ?></h3>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-white rounded-xl p-card-padding border border-table-border flex flex-col justify-between hover:shadow-[0px_4px_12px_rgba(0,0,0,0.03)] transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">bed</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-on-surface-variant text-xs font-medium bg-surface-container px-2 py-1 rounded-full">
                        <?= $occupancy_pct ?>% Occupancy
                    </span>
                </div>
                <div>
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Occupied Beds</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface"><?= $occupied_beds ?> <span class="text-sm font-normal text-secondary">/ <?= $total_beds ?></span></h3>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="bg-white rounded-xl p-card-padding border border-table-border flex flex-col justify-between hover:shadow-[0px_4px_12px_rgba(0,0,0,0.03)] transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">account_balance_wallet</span>
                    </div>
                    <?php if ($overdue_count > 0): ?>
                    <span class="inline-flex items-center gap-1 text-error text-xs font-medium bg-error/10 px-2 py-1 rounded-full">
                        <?= $overdue_count ?> Overdue
                    </span>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Pending Fees</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface"><?= formatCurrency($pending_fees) ?></h3>
                </div>
            </div>

            <!-- Card 5 -->
            <div class="bg-white rounded-xl p-card-padding border border-table-border flex flex-col justify-between hover:shadow-[0px_4px_12px_rgba(0,0,0,0.03)] transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <span class="material-symbols-outlined">error_outline</span>
                    </div>
                    <?php if ($active_complaints > 0): ?>
                    <span class="inline-flex items-center gap-1 text-warning text-xs font-medium bg-warning/10 px-2 py-1 rounded-full">
                        Action Req.
                    </span>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Active Complaints</p>
                    <h3 class="font-display-lg text-display-lg text-on-surface"><?= $active_complaints ?></h3>
                </div>
            </div>
        </div>

        <!-- Bento Grid: Main Table & Complaint Cards -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <!-- Left 2 Cols: Students Table Card -->
            <div class="xl:col-span-2 bg-white rounded-xl border border-table-border shadow-sm flex flex-col">
                <div class="p-card-padding border-b border-table-border flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="font-title-sm text-title-sm text-on-surface font-semibold">Recent Student Registrations</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Latest students added to the hostel database</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="students.php" class="text-primary hover:underline text-xs font-semibold flex items-center gap-1">
                            View All <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="mainDataTable" class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-table-border font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">
                                <th class="px-table-cell-x py-table-cell-y">Student</th>
                                <th class="px-table-cell-x py-table-cell-y">Department</th>
                                <th class="px-table-cell-x py-table-cell-y">Year</th>
                                <th class="px-table-cell-x py-table-cell-y">Room/Block</th>
                                <th class="px-table-cell-x py-table-cell-y">Status</th>
                                <th class="px-table-cell-x py-table-cell-y text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-table-border font-table-data text-table-data">
                            <?php if (!empty($recent_students)): ?>
                                <?php foreach ($recent_students as $stu): 
                                    $initials = getInitials($stu['name']);
                                    $has_room = !empty($stu['room_number']);
                                ?>
                                <tr class="hover:bg-surface-container-low/50 transition-colors group">
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                                                <?= $initials ?>
                                            </div>
                                            <div>
                                                <a href="student_profile.php?id=<?= urlencode($stu['student_id']) ?>" class="text-on-surface font-medium hover:text-primary transition-colors">
                                                    <?= htmlspecialchars($stu['name']) ?>
                                                </a>
                                                <div class="text-[11px] text-on-surface-variant font-mono"><?= htmlspecialchars($stu['student_id']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-on-surface"><?= htmlspecialchars($stu['department']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y text-on-surface-variant"><?= htmlspecialchars($stu['year']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <?php if ($has_room): ?>
                                            <span class="inline-flex items-center gap-1 font-medium text-on-surface">
                                                <span class="material-symbols-outlined text-[14px] text-primary">meeting_room</span>
                                                <?= htmlspecialchars($stu['room_number']) ?> (<?= htmlspecialchars($stu['block_name']) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant italic text-xs">Unallocated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <?php if ($stu['student_status'] === 'Active'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-success/10 text-success">Active</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-error/10 text-error">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-right">
                                        <a href="student_profile.php?id=<?= urlencode($stu['student_id']) ?>" class="text-secondary hover:text-primary p-1 inline-block" title="View Profile">
                                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center p-8 text-secondary">
                                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-1 block">group</span>
                                        No students registered yet. Click <a href="students.php" class="text-primary underline">Add Student</a> to start.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right 1 Col: Recent Complaints -->
            <div class="bg-white rounded-xl border border-table-border shadow-sm flex flex-col">
                <div class="p-card-padding border-b border-table-border flex justify-between items-center">
                    <div>
                        <h3 class="font-title-sm text-title-sm text-on-surface font-semibold">Active Complaints</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">Tickets needing warden review</p>
                    </div>
                    <a href="complaints.php" class="text-primary hover:underline text-xs font-semibold flex items-center gap-1">
                        All <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>

                <div class="p-card-padding flex-1 divide-y divide-table-border">
                    <?php if (!empty($recent_complaints)): ?>
                        <?php foreach ($recent_complaints as $c): ?>
                        <div class="py-3 first:pt-0 last:pb-0 flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full <?= $c['priority'] === 'High' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning' ?> flex items-center justify-center shrink-0 mt-0.5">
                                <span class="material-symbols-outlined text-[18px]">warning</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="font-medium text-sm text-on-surface truncate"><?= htmlspecialchars($c['subject']) ?></p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full <?= $c['status'] === 'Open' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning' ?>">
                                        <?= htmlspecialchars($c['status']) ?>
                                    </span>
                                </div>
                                <p class="text-xs text-on-surface-variant truncate mt-0.5"><?= htmlspecialchars($c['student_name']) ?> • <?= htmlspecialchars($c['category']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-secondary">
                            <span class="material-symbols-outlined text-4xl text-outline-variant mb-1 block">task_alt</span>
                            No open complaints.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Links Section -->
        <div class="bg-surface-container-low rounded-xl p-card-padding border border-table-border">
            <h4 class="font-title-sm text-title-sm text-primary font-bold mb-3">Quick Navigation</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <a href="students.php" class="p-3 bg-white rounded-lg border border-table-border hover:border-primary transition-all flex items-center gap-3 group">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">group</span>
                    <div>
                        <div class="text-sm font-bold text-on-surface">Students</div>
                        <div class="text-[11px] text-on-surface-variant">Add / Edit students</div>
                    </div>
                </a>
                <a href="rooms.php" class="p-3 bg-white rounded-lg border border-table-border hover:border-primary transition-all flex items-center gap-3 group">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">meeting_room</span>
                    <div>
                        <div class="text-sm font-bold text-on-surface">Rooms</div>
                        <div class="text-[11px] text-on-surface-variant">Manage room inventory</div>
                    </div>
                </a>
                <a href="hostel_blocks.php" class="p-3 bg-white rounded-lg border border-table-border hover:border-primary transition-all flex items-center gap-3 group">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">apartment</span>
                    <div>
                        <div class="text-sm font-bold text-on-surface">Hostel Blocks</div>
                        <div class="text-[11px] text-on-surface-variant">Building & wardens</div>
                    </div>
                </a>
                <a href="fees.php" class="p-3 bg-white rounded-lg border border-table-border hover:border-primary transition-all flex items-center gap-3 group">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">payments</span>
                    <div>
                        <div class="text-sm font-bold text-on-surface">Fee Collection</div>
                        <div class="text-[11px] text-on-surface-variant">Log payments & receipts</div>
                    </div>
                </a>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
