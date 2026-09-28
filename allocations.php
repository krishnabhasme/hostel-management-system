<?php
/**
 * Room Allocation Management - Sipna Hostel Management System
 * Preserves 100% of Stitch Room Allocation UI with Live Database Coordination
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Room Allocation - Sipna Hostel';

// Handle POST actions (Create Allocation, Revoke Allocation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create Allocation
    if ($action === 'create_allocation') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $room_id = (int)($_POST['room_id'] ?? 0);
        $bed = trim($_POST['bed'] ?? 'Bed A');
        $allocation_date = !empty($_POST['allocation_date']) ? $_POST['allocation_date'] : date('Y-m-d');

        if ($student_id <= 0 || $room_id <= 0) {
            setFlashMessage('error', 'Please select both a student and an available room.');
        } else {
            try {
                // Check if student already has an active allocation
                $chkStu = $pdo->prepare("SELECT id FROM allocations WHERE student_id = ? AND status = 'Active'");
                $chkStu->execute([$student_id]);
                if ($chkStu->fetch()) {
                    setFlashMessage('error', 'This student is already allocated a room.');
                } else {
                    // Check room capacity
                    $chkRoom = $pdo->prepare("SELECT capacity, (SELECT COUNT(*) FROM allocations WHERE room_id = ? AND status = 'Active') as occupied FROM rooms WHERE id = ?");
                    $chkRoom->execute([$room_id, $room_id]);
                    $rInfo = $chkRoom->fetch();

                    if (!$rInfo || $rInfo['occupied'] >= $rInfo['capacity']) {
                        setFlashMessage('error', 'The selected room is already at full capacity.');
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO allocations (student_id, room_id, bed, allocation_date, status) VALUES (?, ?, ?, ?, 'Active')");
                        $stmt->execute([$student_id, $room_id, $bed, $allocation_date]);
                        setFlashMessage('success', 'Room allocated successfully!');
                    }
                }
            } catch (Exception $e) {
                setFlashMessage('error', 'Allocation failed: ' . $e->getMessage());
            }
        }
        header("Location: allocations.php");
        exit;
    }

    // Revoke Allocation
    if ($action === 'revoke') {
        $alloc_id = (int)($_POST['allocation_id'] ?? 0);
        if ($alloc_id > 0) {
            try {
                $upd = $pdo->prepare("UPDATE allocations SET status = 'Vacated' WHERE id = ?");
                $upd->execute([$alloc_id]);
                setFlashMessage('success', 'Room allocation revoked successfully. Bed is now available.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Revoke error: ' . $e->getMessage());
            }
        }
        header("Location: allocations.php");
        exit;
    }
}

// Fetch Unallocated Students
try {
    $unallocated_students = $pdo->query("SELECT s.id, s.student_id, s.name, s.department 
        FROM students s 
        WHERE s.status = 'Active' 
        AND s.id NOT IN (SELECT student_id FROM allocations WHERE status = 'Active')
        ORDER BY s.name ASC")->fetchAll();

    // Fetch Available Rooms (with vacant beds)
    $available_rooms_list = $pdo->query("SELECT 
        r.id, 
        r.room_number, 
        r.room_type, 
        r.capacity,
        b.block_name,
        COALESCE((SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'Active'), 0) as occupied_count
        FROM rooms r 
        JOIN blocks b ON r.block_id = b.id 
        WHERE r.status != 'Maintenance'
        AND (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'Active') < r.capacity
        ORDER BY b.block_name ASC, r.room_number ASC")->fetchAll();

    // Fetch All Current Allocations
    $allocations_stmt = $pdo->query("SELECT 
        a.id as alloc_id,
        a.bed,
        a.allocation_date,
        a.status as alloc_status,
        s.id as student_db_id,
        s.student_id,
        s.name as student_name,
        r.room_number,
        b.block_name
    FROM allocations a
    JOIN students s ON a.student_id = s.id
    JOIN rooms r ON a.room_id = r.id
    JOIN blocks b ON r.block_id = b.id
    ORDER BY a.id DESC");
    $allocations = $allocations_stmt->fetchAll();
} catch (Exception $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col md:ml-sidebar min-w-0 bg-surface">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <!-- Page Canvas -->
    <main class="flex-1 overflow-y-auto p-gutter bg-surface-container-lowest pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-display-lg text-display-lg text-on-background tracking-tight">Room Allocation</h2>
                    <p class="font-body-main text-body-main text-on-surface-variant mt-1">Assign students to available hostel rooms or manage active allocations.</p>
                </div>
            </div>

            <!-- Bento Grid Layout -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                <!-- Left Column: Form -->
                <div class="xl:col-span-4 flex flex-col gap-6">
                    <div class="bg-surface border border-table-border rounded-xl p-card-padding flex flex-col shadow-sm">
                        <div class="border-b border-table-border pb-4 mb-5">
                            <h3 class="font-title-sm text-title-sm text-on-surface flex items-center gap-2 font-bold">
                                <span class="material-symbols-outlined text-primary" data-icon="add_circle">add_circle</span>
                                New Allocation
                            </h3>
                        </div>
                        <form action="allocations.php" method="POST" class="space-y-4 flex-1">
                            <input type="hidden" name="action" value="create_allocation">
                            
                            <!-- Student Selection -->
                            <div class="space-y-1.5">
                                <label class="font-label-caps text-label-caps text-on-surface-variant block">Select Student *</label>
                                <select name="student_id" required class="w-full pl-3 pr-8 py-2.5 bg-surface border border-outline-variant rounded-lg text-body-main focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-on-surface">
                                    <?php if (!empty($unallocated_students)): ?>
                                        <option value="" disabled selected>Select unallocated student...</option>
                                        <?php foreach ($unallocated_students as $stu): ?>
                                        <option value="<?= $stu['id'] ?>">
                                            <?= htmlspecialchars($stu['name']) ?> (<?= htmlspecialchars($stu['student_id']) ?> - <?= htmlspecialchars($stu['department']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled selected>No unallocated students (All allocated)</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Room Selection -->
                            <div class="space-y-1.5">
                                <label class="font-label-caps text-label-caps text-on-surface-variant block">Hostel Room *</label>
                                <select name="room_id" required class="w-full pl-3 pr-8 py-2.5 bg-surface border border-outline-variant rounded-lg text-body-main focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-on-surface">
                                    <?php if (!empty($available_rooms_list)): ?>
                                        <option value="" disabled selected>Select available room...</option>
                                        <?php foreach ($available_rooms_list as $rm): 
                                            $free = (int)$rm['capacity'] - (int)$rm['occupied_count'];
                                        ?>
                                        <option value="<?= $rm['id'] ?>">
                                            <?= htmlspecialchars($rm['block_name']) ?> - Room <?= htmlspecialchars($rm['room_number']) ?> (<?= $rm['room_type'] ?> - <?= $free ?> Bed<?= $free > 1 ? 's' : '' ?> Free)
                                        </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled selected>No rooms with free capacity available</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Bed Selection -->
                            <div class="space-y-1.5">
                                <label class="font-label-caps text-label-caps text-on-surface-variant block">Bed Position</label>
                                <select name="bed" class="w-full pl-3 pr-8 py-2.5 bg-surface border border-outline-variant rounded-lg text-body-main focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-on-surface">
                                    <option value="Bed A">Bed A</option>
                                    <option value="Bed B">Bed B</option>
                                    <option value="Bed C">Bed C</option>
                                </select>
                            </div>

                            <!-- Date -->
                            <div class="space-y-1.5">
                                <label class="font-label-caps text-label-caps text-on-surface-variant block">Allocation Date</label>
                                <input name="allocation_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2.5 bg-surface border border-outline-variant rounded-lg text-body-main focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none text-on-surface" type="date">
                            </div>

                            <div class="pt-4 mt-auto">
                                <button class="w-full py-2.5 bg-primary text-on-primary rounded-lg font-title-sm text-title-sm flex items-center justify-center gap-2 hover:bg-primary-container transition-colors shadow-sm cursor-pointer" type="submit">
                                    <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                    Confirm Allocation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Data Table -->
                <div class="xl:col-span-8">
                    <div class="bg-surface border border-table-border rounded-xl shadow-sm overflow-hidden flex flex-col h-full min-h-[500px]">
                        <div class="px-card-padding py-4 border-b border-table-border bg-surface flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <h3 class="font-title-sm text-title-sm text-on-surface font-bold">Current Allocations</h3>
                            <div class="relative w-full sm:w-64">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[18px]">search</span>
                                <input data-table-search="allocationsTable" class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low border border-outline-variant rounded-md text-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all text-on-surface" placeholder="Filter allocations..." type="text">
                            </div>
                        </div>
                        <div class="overflow-x-auto flex-1">
                            <table id="allocationsTable" class="w-full text-left border-collapse min-w-[700px]">
                                <thead class="bg-surface-bright border-b border-table-border">
                                    <tr>
                                        <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-on-surface-variant">Student</th>
                                        <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-on-surface-variant">Block / Room</th>
                                        <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-on-surface-variant">Allocation Date</th>
                                        <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-on-surface-variant">Status</th>
                                        <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-on-surface-variant text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-table-border bg-surface font-table-data text-table-data">
                                    <?php if (!empty($allocations)): ?>
                                        <?php foreach ($allocations as $al): 
                                            $stu_initials = getInitials($al['student_name']);
                                        ?>
                                        <tr class="hover:bg-surface-container-lowest transition-colors group">
                                            <td class="px-table-cell-x py-table-cell-y">
                                                <div class="flex items-center gap-3">
                                                    <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs shrink-0">
                                                        <?= $stu_initials ?>
                                                    </div>
                                                    <div>
                                                        <a href="student_profile.php?id=<?= $al['student_db_id'] ?>" class="text-on-surface font-semibold hover:underline">
                                                            <?= htmlspecialchars($al['student_name']) ?>
                                                        </a>
                                                        <div class="text-on-surface-variant text-xs"><?= htmlspecialchars($al['student_id']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-table-cell-x py-table-cell-y">
                                                <div class="text-on-surface font-medium"><?= htmlspecialchars($al['block_name']) ?></div>
                                                <div class="text-on-surface-variant text-xs">Room <?= htmlspecialchars($al['room_number']) ?> (<?= htmlspecialchars($al['bed']) ?>)</div>
                                            </td>
                                            <td class="px-table-cell-x py-table-cell-y text-on-surface-variant">
                                                <?= formatDate($al['allocation_date']) ?>
                                            </td>
                                            <td class="px-table-cell-x py-table-cell-y">
                                                <?php if ($al['alloc_status'] === 'Active'): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold bg-success/10 text-success border border-success/20">Active</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold bg-surface-variant text-on-surface-variant border border-outline-variant">Vacated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-table-cell-x py-table-cell-y text-right">
                                                <?php if ($al['alloc_status'] === 'Active'): ?>
                                                <form method="POST" action="allocations.php" onsubmit="return confirm('Revoke allocation for <?= htmlspecialchars($al['student_name']) ?>? The room bed will become available immediately.');" class="inline">
                                                    <input type="hidden" name="action" value="revoke">
                                                    <input type="hidden" name="allocation_id" value="<?= $al['alloc_id'] ?>">
                                                    <button type="submit" class="p-1 text-on-surface-variant hover:text-error transition-colors cursor-pointer" title="Revoke Allocation">
                                                        <span class="material-symbols-outlined text-[18px]">cancel</span>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="p-10 text-center text-on-surface-variant">
                                                <span class="material-symbols-outlined text-4xl text-outline-variant mb-2 block">hotel</span>
                                                No rooms allocated yet. Use the form on the left to allocate rooms to students.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
