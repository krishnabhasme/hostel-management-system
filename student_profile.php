<?php
/**
 * Student Profile Dossier - Sipna Hostel Management System
 * Preserves 100% of Stitch Student Profile UI with Simple SQL
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$student_id_param = trim($_GET['id'] ?? '');

if (empty($student_id_param)) {
    header("Location: students.php");
    exit;
}

// Handle Profile Updates & Discharge
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $year = trim($_POST['year'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');

        try {
            $stmt = $pdo->prepare("UPDATE students SET 
                name = ?, contact = ?, email = ?, department = ?, 
                year = ?, address = ?, guardian_name = ?, guardian_contact = ?
                WHERE student_id = ?");
            $stmt->execute([
                $name, $contact, $email, $department,
                $year, $address, $guardian_name, $guardian_contact,
                $student_id_param
            ]);

            setFlashMessage('success', 'Student profile updated successfully.');
        } catch (Exception $e) {
            setFlashMessage('error', 'Update failed: ' . $e->getMessage());
        }
        header("Location: student_profile.php?id=" . urlencode($student_id_param));
        exit;
    }

    if ($action === 'discharge') {
        try {
            $updAlloc = $pdo->prepare("UPDATE allocations SET status = 'Vacated' WHERE student_id = ? AND status = 'Active'");
            $updAlloc->execute([$student_id_param]);

            $updStu = $pdo->prepare("UPDATE students SET status = 'Inactive' WHERE student_id = ?");
            $updStu->execute([$student_id_param]);

            setFlashMessage('success', 'Student has been discharged and room vacated.');
        } catch (Exception $e) {
            setFlashMessage('error', 'Discharge failed: ' . $e->getMessage());
        }
        header("Location: student_profile.php?id=" . urlencode($student_id_param));
        exit;
    }
}

// Fetch Student details
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id_param]);
    $student = $stmt->fetch();

    if (!$student) {
        setFlashMessage('error', 'Student not found.');
        header("Location: students.php");
        exit;
    }

    $page_title = $student['name'] . ' - Student Profile';
    $initials = getInitials($student['name']);

    // Current Allocation
    $stmt = $pdo->prepare("SELECT 
        a.*,
        r.room_number,
        r.room_type,
        b.block_name,
        b.type as block_type
    FROM allocations a
    JOIN rooms r ON a.room_id = r.room_id
    JOIN blocks b ON r.block_id = b.block_id
    WHERE a.student_id = ? AND a.status = 'Active'
    LIMIT 1");
    $stmt->execute([$student_id_param]);
    $allocation = $stmt->fetch();

    // Fee History
    $stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$student_id_param]);
    $fee_history = $stmt->fetchAll();

    // Complaints
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->execute([$student_id_param]);
    $complaints = $stmt->fetchAll();

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col md:ml-sidebar min-w-0 bg-surface">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-gutter bg-background pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-on-surface-variant font-body-sm text-body-sm">
                <a class="hover:text-primary transition-colors flex items-center gap-1" href="students.php">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Students Directory
                </a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="font-medium text-on-surface"><?= htmlspecialchars($student['name']) ?></span>
            </div>

            <!-- Profile Header Card -->
            <div class="bg-surface border border-table-border rounded-xl p-card-padding flex flex-col md:flex-row gap-6 items-start md:items-center relative overflow-hidden shadow-sm">
                <div class="w-24 h-24 rounded-xl bg-primary text-on-primary flex items-center justify-center font-bold text-3xl shadow-sm border-2 border-surface shrink-0">
                    <?= $initials ?>
                </div>

                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="font-display-lg text-display-lg text-on-surface font-bold"><?= htmlspecialchars($student['name']) ?></h1>
                        <span class="px-2.5 py-1 rounded <?= $student['status'] === 'Active' ? 'bg-success/10 text-success border border-success/20' : 'bg-error/10 text-error border border-error/20' ?> font-label-caps text-label-caps uppercase">
                            <?= htmlspecialchars($student['status']) ?>
                        </span>
                    </div>
                    <p class="font-body-main text-body-main text-on-surface-variant mb-4">
                        Student ID: <span class="font-semibold text-primary"><?= htmlspecialchars($student['student_id']) ?></span> • Year: <?= htmlspecialchars($student['year']) ?> • Department: <?= htmlspecialchars($student['department']) ?>
                    </p>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <div class="flex items-center gap-2 text-on-surface font-body-sm text-body-sm">
                            <span class="material-symbols-outlined text-outline text-[18px]">bed</span>
                            <span>
                                <?php if ($allocation): ?>
                                    <?= htmlspecialchars($allocation['block_name']) ?> - Room <?= htmlspecialchars($allocation['room_number']) ?> (<?= htmlspecialchars($allocation['bed']) ?>)
                                <?php else: ?>
                                    <span class="text-on-surface-variant italic">No Room Allocated</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-on-surface font-body-sm text-body-sm">
                            <span class="material-symbols-outlined text-outline text-[18px]">phone</span>
                            <span><?= htmlspecialchars($student['contact']) ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-on-surface font-body-sm text-body-sm">
                            <span class="material-symbols-outlined text-outline text-[18px]">mail</span>
                            <span><?= htmlspecialchars($student['email']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 w-full md:w-auto shrink-0 relative z-10">
                    <button type="button" onclick="openModal('editStudentModal')" class="px-4 py-2 bg-primary text-on-primary rounded font-label-caps text-label-caps hover:bg-primary/90 transition-colors flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">edit_document</span>
                        EDIT DETAILS
                    </button>
                    <?php if ($student['status'] === 'Active' && $allocation): ?>
                    <form method="POST" action="student_profile.php?id=<?= urlencode($student['student_id']) ?>" onsubmit="return confirm('Discharge student and vacate room?');">
                        <input type="hidden" name="action" value="discharge">
                        <button type="submit" class="w-full px-4 py-2 bg-surface text-error border border-error/50 rounded font-label-caps text-label-caps hover:bg-error/5 transition-colors flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px]">output</span>
                            DISCHARGE
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grid Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info (Spans 2 cols) -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <!-- Demographics -->
                    <div class="bg-surface border border-table-border rounded-xl p-card-padding shadow-sm">
                        <h2 class="font-title-sm text-title-sm text-primary mb-6 flex items-center gap-2 border-b border-table-border pb-3">
                            <span class="material-symbols-outlined text-outline">badge</span>
                            Personal & Academic Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                            <div class="flex flex-col gap-1">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Gender</span>
                                <span class="font-table-data text-table-data text-on-surface"><?= htmlspecialchars($student['gender']) ?></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Department</span>
                                <span class="font-table-data text-table-data text-on-surface"><?= htmlspecialchars($student['department']) ?></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Year of Study</span>
                                <span class="font-table-data text-table-data text-on-surface"><?= htmlspecialchars($student['year']) ?></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Contact</span>
                                <span class="font-table-data text-table-data text-on-surface"><?= htmlspecialchars($student['contact']) ?></span>
                            </div>
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Permanent Address</span>
                                <span class="font-body-main text-body-main text-on-surface"><?= htmlspecialchars($student['address'] ?: 'Not recorded') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Details -->
                    <div class="bg-surface border border-table-border rounded-xl p-card-padding shadow-sm">
                        <h2 class="font-title-sm text-title-sm text-primary mb-6 flex items-center gap-2 border-b border-table-border pb-3">
                            <span class="material-symbols-outlined text-outline">family_restroom</span>
                            Guardian Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                            <div class="flex flex-col gap-1">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Guardian Name</span>
                                <span class="font-table-data text-table-data text-on-surface"><?= htmlspecialchars($student['guardian_name'] ?: 'Not provided') ?></span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="font-label-caps text-label-caps text-on-surface-variant uppercase">Guardian Phone</span>
                                <span class="font-table-data text-table-data text-on-surface"><?= htmlspecialchars($student['guardian_contact'] ?: 'Not provided') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Payment Records -->
                    <div class="bg-surface border border-table-border rounded-xl p-card-padding shadow-sm">
                        <h2 class="font-title-sm text-title-sm text-primary mb-4 flex items-center gap-2 border-b border-table-border pb-3">
                            <span class="material-symbols-outlined text-outline">payments</span>
                            Fee Payment Records
                        </h2>
                        <?php if (!empty($fee_history)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-surface-bright border-b border-table-border font-label-caps text-label-caps text-on-surface-variant">
                                        <th class="py-2 px-3">Receipt No</th>
                                        <th class="py-2 px-3">Date</th>
                                        <th class="py-2 px-3">Method</th>
                                        <th class="py-2 px-3">Amount</th>
                                        <th class="py-2 px-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-table-border font-table-data text-table-data">
                                    <?php foreach ($fee_history as $fee): ?>
                                    <tr>
                                        <td class="py-2.5 px-3 font-semibold text-primary"><?= htmlspecialchars($fee['receipt_no']) ?></td>
                                        <td class="py-2.5 px-3"><?= formatDate($fee['payment_date']) ?></td>
                                        <td class="py-2.5 px-3"><?= htmlspecialchars($fee['payment_method']) ?></td>
                                        <td class="py-2.5 px-3 font-semibold"><?= formatCurrency($fee['amount']) ?></td>
                                        <td class="py-2.5 px-3 text-right">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold <?= $fee['status'] === 'Paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' ?>">
                                                <?= htmlspecialchars($fee['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <p class="text-sm text-on-surface-variant italic">No fee payments logged for this student.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Side Column -->
                <div class="flex flex-col gap-6">
                    <!-- Current Hostel Status -->
                    <div class="bg-surface-bright border border-table-border rounded-xl p-card-padding shadow-sm">
                        <h3 class="font-label-caps text-label-caps text-on-surface-variant mb-4 uppercase font-bold">Current Hostel Status</h3>
                        
                        <?php if ($allocation): ?>
                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-12 h-12 bg-primary/10 rounded flex items-center justify-center text-primary shrink-0">
                                <span class="material-symbols-outlined text-[28px]">meeting_room</span>
                            </div>
                            <div>
                                <div class="font-title-sm text-title-sm text-on-surface font-bold">Room <?= htmlspecialchars($allocation['room_number']) ?></div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant"><?= htmlspecialchars($allocation['block_name']) ?></div>
                            </div>
                        </div>
                        <div class="bg-surface border border-table-border rounded p-3 mb-4 space-y-2">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-on-surface-variant">Bed:</span>
                                <span class="font-semibold text-on-surface"><?= htmlspecialchars($allocation['bed']) ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-on-surface-variant">Allocated On:</span>
                                <span class="font-semibold text-on-surface"><?= formatDate($allocation['allocation_date']) ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-6">
                            <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2">hotel</span>
                            <p class="text-sm text-on-surface-variant mb-4">No active room allocated</p>
                            <a href="allocations.php" class="px-4 py-2 bg-primary text-white rounded font-label-caps text-label-caps inline-block">
                                Allocate Room
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Registered Complaints -->
                    <div class="bg-white border border-table-border rounded-xl p-card-padding shadow-sm">
                        <h3 class="font-label-caps text-label-caps text-primary mb-4 flex items-center gap-2 uppercase font-bold">
                            <span class="material-symbols-outlined text-[16px]">report_problem</span>
                            Registered Complaints
                        </h3>
                        <?php if (!empty($complaints)): ?>
                            <ul class="space-y-3">
                                <?php foreach ($complaints as $cmp): ?>
                                <li class="p-3 bg-surface-container-low rounded border border-table-border/50 text-xs">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-semibold text-primary"><?= htmlspecialchars($cmp['ticket_no']) ?></span>
                                        <span class="uppercase font-bold text-[10px] px-1.5 py-0.5 rounded <?= $cmp['status'] === 'Resolved' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' ?>">
                                            <?= htmlspecialchars($cmp['status']) ?>
                                        </span>
                                    </div>
                                    <p class="font-medium text-on-surface"><?= htmlspecialchars($cmp['subject']) ?></p>
                                    <p class="text-on-surface-variant text-[11px] mt-1"><?= formatDate($cmp['created_at']) ?></p>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-xs text-on-surface-variant italic">No complaints filed by this student.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Edit Student Profile -->
<div id="editStudentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-2xl rounded-xl shadow-lg flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl">
            <h3 class="font-title-sm text-title-sm text-primary font-bold">Edit Student Details</h3>
            <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('editStudentModal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form action="student_profile.php?id=<?= urlencode($student['student_id']) ?>" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Full Name</label>
                        <input name="name" value="<?= htmlspecialchars($student['name']) ?>" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Contact Phone</label>
                        <input name="contact" value="<?= htmlspecialchars($student['contact']) ?>" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Email</label>
                        <input name="email" value="<?= htmlspecialchars($student['email']) ?>" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Department</label>
                        <input name="department" value="<?= htmlspecialchars($student['department']) ?>" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Year</label>
                        <input name="year" value="<?= htmlspecialchars($student['year']) ?>" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Guardian Name</label>
                        <input name="guardian_name" value="<?= htmlspecialchars($student['guardian_name']) ?>" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Guardian Contact</label>
                        <input name="guardian_contact" value="<?= htmlspecialchars($student['guardian_contact']) ?>" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Permanent Address</label>
                        <textarea name="address" rows="2" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface"><?= htmlspecialchars($student['address']) ?></textarea>
                    </div>
                </div>
                <div class="pt-4 border-t border-table-border flex justify-end gap-3">
                    <button type="button" onclick="closeModal('editStudentModal')" class="px-4 py-2 border border-primary text-primary rounded text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded text-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
