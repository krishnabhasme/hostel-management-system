<?php
/**
 * Student Management - Sipna Hostel Management System
 * Preserves 100% of Stitch UI layout, table, and modal with Live Database Coordination
 * Aligned with ER Diagram: STUDENTS (student_id PK)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Students Directory - Sipna Hostel';

// Handle POST actions (Create Student, Delete Student)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create New Student
    if ($action === 'create') {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $gender = $_POST['gender'] ?? 'Male';
        $department = trim($_POST['department'] ?? 'Computer Engineering');
        $year = trim($_POST['year'] ?? '1st Year');
        $address = trim($_POST['address'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');

        if (empty($student_id) || empty($name) || empty($contact)) {
            setFlashMessage('error', 'Please fill in all required student details.');
        } else {
            if (empty($email)) {
                $email = strtolower(str_replace(' ', '.', $name) . '@student.sipna.edu');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO students 
                    (student_id, name, email, contact, gender, department, year, address, guardian_name, guardian_contact, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
                $stmt->execute([
                    $student_id, $name, $email, $contact, $gender,
                    $department, $year, $address, $guardian_name, $guardian_contact
                ]);

                setFlashMessage('success', "Student {$name} ({$student_id}) registered successfully!");
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to register student: ' . $e->getMessage());
            }
        }
        header("Location: students.php");
        exit;
    }

    // Delete Student
    if ($action === 'delete') {
        $student_id = trim($_POST['student_id_val'] ?? '');
        if (!empty($student_id)) {
            try {
                // Delete associated allocations, fees, and complaints first
                $pdo->prepare("DELETE FROM allocations WHERE student_id = ?")->execute([$student_id]);
                $pdo->prepare("DELETE FROM fees WHERE student_id = ?")->execute([$student_id]);
                $pdo->prepare("DELETE FROM complaints WHERE student_id = ?")->execute([$student_id]);
                $del = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                $del->execute([$student_id]);
                setFlashMessage('success', 'Student record and associated room allocations removed successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Could not delete student: ' . $e->getMessage());
            }
        }
        header("Location: students.php");
        exit;
    }
}

// Search logic
$search = trim($_GET['search'] ?? '');

$query = "SELECT 
    s.*,
    r.room_number,
    b.block_name,
    a.bed,
    a.status as alloc_status
FROM students s
LEFT JOIN allocations a ON s.student_id = a.student_id AND a.status = 'Active'
LEFT JOIN rooms r ON a.room_id = r.room_id
LEFT JOIN blocks b ON r.block_id = b.block_id
WHERE 1=1";

$params = [];
if (!empty($search)) {
    $query .= " AND (s.student_id LIKE ? OR s.name LIKE ? OR s.contact LIKE ? OR s.department LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$query .= " ORDER BY s.student_id DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    $total_records = count($students);
} catch (Exception $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col md:ml-sidebar min-w-0 bg-surface">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-gutter bg-surface-container-lowest pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header & Action Bar -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                <div>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-bold">Student Directory</h2>
                    <p class="font-body-sm text-body-sm text-secondary mt-1">Manage hostel enrollments, add new students, and update allocations.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <!-- Search Form -->
                    <form action="students.php" method="GET" class="relative flex-1 sm:flex-none">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-sm">search</span>
                        <input name="search" class="w-full sm:w-60 pl-9 pr-4 py-2 bg-surface-container-lowest border border-outline-variant rounded focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-body-sm text-body-sm text-on-surface" placeholder="Search ID, Name..." type="text" value="<?= htmlspecialchars($search) ?>">
                    </form>
                    
                    <button type="button" onclick="openModal('addStudentModal')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded font-label-caps text-label-caps hover:bg-primary-container transition-colors shadow-sm whitespace-nowrap cursor-pointer">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Add Student
                    </button>
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="bg-surface-container-lowest border border-table-border rounded-lg overflow-x-auto shadow-sm">
                <table id="studentsTable" class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-table-border">
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Student ID</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Name</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Department</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Year</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Contact</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Room / Block</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold">Status</th>
                            <th class="px-table-cell-x py-table-cell-y font-label-caps text-label-caps text-secondary font-bold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-table-border">
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $stu): 
                                $initials = getInitials($stu['name']);
                                $has_room = !empty($stu['room_number']);
                            ?>
                            <tr class="hover:bg-surface-container-highest/30 transition-colors group">
                                <td class="px-table-cell-x py-table-cell-y font-table-data text-table-data text-primary font-semibold">
                                    <a href="student_profile.php?id=<?= urlencode($stu['student_id']) ?>" class="hover:underline">
                                        <?= htmlspecialchars($stu['student_id']) ?>
                                    </a>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                                            <?= $initials ?>
                                        </div>
                                        <span class="font-table-data text-table-data text-on-surface font-medium"><?= htmlspecialchars($stu['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y font-table-data text-table-data text-on-surface"><?= htmlspecialchars($stu['department']) ?></td>
                                <td class="px-table-cell-x py-table-cell-y font-table-data text-table-data text-secondary"><?= htmlspecialchars($stu['year']) ?></td>
                                <td class="px-table-cell-x py-table-cell-y font-table-data text-table-data text-secondary"><?= htmlspecialchars($stu['contact']) ?></td>
                                <td class="px-table-cell-x py-table-cell-y">
                                    <?php if ($has_room): ?>
                                        <div class="flex flex-col">
                                            <span class="font-table-data text-table-data text-on-surface font-semibold">Room <?= htmlspecialchars($stu['room_number']) ?> (<?= htmlspecialchars($stu['bed']) ?>)</span>
                                            <span class="font-label-caps text-label-caps text-secondary"><?= htmlspecialchars($stu['block_name']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex flex-col">
                                            <span class="font-table-data text-table-data text-on-surface font-semibold">-</span>
                                            <span class="font-label-caps text-label-caps text-secondary">Unallocated</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y">
                                    <?php if ($stu['status'] === 'Active'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-success/10 text-success border border-success/20">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-error/10 text-error border border-error/20">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="student_profile.php?id=<?= urlencode($stu['student_id']) ?>" class="text-secondary hover:text-primary transition-colors p-1" title="View Profile">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </a>
                                        <form method="POST" action="students.php" onsubmit="return confirm('Are you sure you want to delete student <?= htmlspecialchars($stu['name']) ?>? All room allocations and fees will be removed.');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="student_id_val" value="<?= htmlspecialchars($stu['student_id']) ?>">
                                            <button type="submit" class="text-secondary hover:text-error transition-colors p-1 cursor-pointer" title="Delete Student">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center p-12 text-secondary">
                                    <span class="material-symbols-outlined text-5xl text-outline-variant mb-2 block">group_add</span>
                                    <p class="font-medium text-base text-on-surface">No students found in the database.</p>
                                    <p class="text-xs text-on-surface-variant mt-1 mb-4">Click "Add Student" above to enroll a new hostel student.</p>
                                    <button type="button" onclick="openModal('addStudentModal')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white text-xs font-bold rounded shadow-sm hover:bg-primary-container">
                                        <span class="material-symbols-outlined text-sm">add</span> Add First Student
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination Footer -->
                <div class="px-4 py-3 border-t border-table-border bg-surface-container-lowest flex items-center justify-between">
                    <span class="font-body-sm text-body-sm text-secondary">Showing <?= count($students) ?> of <?= $total_records ?> entries</span>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Add Student -->
<div id="addStudentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-2xl rounded-xl shadow-xl flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl">
            <h3 class="font-title-sm text-title-sm text-on-surface font-bold">Register New Student</h3>
            <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('addStudentModal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="p-6 overflow-y-auto">
            <form action="students.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Student ID (Roll No) *</label>
                        <input name="student_id" required placeholder="e.g. STU-2024-001" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Full Name *</label>
                        <input name="name" required placeholder="Enter student full name" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Contact Phone *</label>
                        <input name="contact" required placeholder="+91 98765 43210" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Email</label>
                        <input name="email" placeholder="student@sipna.edu" type="email" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Department</label>
                        <select name="department" class="w-full px-3 py-2 border border-outline-variant rounded text-sm bg-surface text-on-surface">
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Architecture">Architecture</option>
                            <option value="MBA">MBA</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Year of Study</label>
                        <select name="year" class="w-full px-3 py-2 border border-outline-variant rounded text-sm bg-surface text-on-surface">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Gender</label>
                        <select name="gender" class="w-full px-3 py-2 border border-outline-variant rounded text-sm bg-surface text-on-surface">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Guardian Name</label>
                        <input name="guardian_name" placeholder="Parent / Guardian name" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Guardian Contact</label>
                        <input name="guardian_contact" placeholder="+91 98000 00000" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Permanent Address</label>
                        <textarea name="address" rows="2" placeholder="Full residential address" class="w-full px-3 py-2 border border-outline-variant rounded text-sm text-on-surface"></textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-table-border flex justify-end gap-3">
                    <button type="button" onclick="closeModal('addStudentModal')" class="px-4 py-2 border border-primary text-primary rounded text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded text-sm">Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
