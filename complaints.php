<?php
/**
 * Complaint Management - Sipna Hostel Management System
 * Preserves 100% of Stitch Complaint Management UI with Simple SQL
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Complaint Management - Sipna Hostel';

// Handle POST actions (Create Ticket, Update Status, Delete Ticket)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create New Ticket
    if ($action === 'create_ticket') {
        $student_id = trim($_POST['student_id'] ?? '');
        $category = $_POST['category'] ?? 'Plumbing';
        $priority = $_POST['priority'] ?? 'Medium';
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ticket_no = 'CMP-' . rand(1000, 9999);

        if (empty($student_id) || empty($subject) || empty($description)) {
            setFlashMessage('error', 'Please select student and provide subject/description.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO complaints (ticket_no, student_id, category, subject, description, priority, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Open', CURDATE())");
                $stmt->execute([$ticket_no, $student_id, $category, $subject, $description, $priority]);
                setFlashMessage('success', "Complaint ticket {$ticket_no} logged successfully!");
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to submit complaint: ' . $e->getMessage());
            }
        }
        header("Location: complaints.php");
        exit;
    }

    // Update Complaint Status
    if ($action === 'update_status') {
        $ticket_no = trim($_POST['ticket_no'] ?? '');
        $new_status = $_POST['status'] ?? 'Open';

        if (!empty($ticket_no)) {
            try {
                $stmt = $pdo->prepare("UPDATE complaints SET status = ? WHERE ticket_no = ?");
                $stmt->execute([$new_status, $ticket_no]);
                setFlashMessage('success', 'Ticket status updated to ' . ucfirst($new_status));
            } catch (Exception $e) {
                setFlashMessage('error', 'Status update error: ' . $e->getMessage());
            }
        }
        header("Location: complaints.php");
        exit;
    }

    // Delete Complaint
    if ($action === 'delete') {
        $ticket_no = trim($_POST['ticket_no'] ?? '');
        if (!empty($ticket_no)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM complaints WHERE ticket_no = ?");
                $stmt->execute([$ticket_no]);
                setFlashMessage('success', 'Complaint ticket deleted.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Delete error: ' . $e->getMessage());
            }
        }
        header("Location: complaints.php");
        exit;
    }
}

// Fetch KPI Metrics
try {
    $total_open = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Open'")->fetchColumn();
    $high_priority = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE priority = 'High' AND status != 'Resolved'")->fetchColumn();
    $in_progress = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetchColumn();
    $resolved_count = (int)$pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();

    // Fetch Students for Modal
    $students_list = $pdo->query("SELECT student_id, name FROM students WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

    // Category filter parameter
    $category_filter = $_GET['cat'] ?? 'all';
    $search = trim($_GET['search'] ?? '');

    $query = "SELECT 
        c.*,
        s.name as student_name
    FROM complaints c
    JOIN students s ON c.student_id = s.student_id
    WHERE 1=1";

    $params = [];
    if (!empty($category_filter) && $category_filter !== 'all') {
        $query .= " AND c.category = ?";
        $params[] = $category_filter;
    }
    if (!empty($search)) {
        $query .= " AND (c.ticket_no LIKE ? OR c.subject LIKE ? OR c.description LIKE ?)";
        $term = "%$search%";
        $params = array_merge($params, [$term, $term, $term]);
    }

    $query .= " ORDER BY (c.priority = 'High') DESC, c.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll();

} catch (Exception $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col md:ml-sidebar min-w-0 bg-surface">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <!-- Canvas -->
    <main class="flex-1 overflow-y-auto p-gutter bg-surface-container-lowest pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="font-display-lg text-display-lg text-primary font-bold">Complaint Management</h1>
                    <p class="font-body-main text-body-main text-on-surface-variant mt-1">Track, assign, and resolve student maintenance tickets.</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="openModal('newTicketModal')" class="flex items-center gap-2 px-4 py-2 bg-primary text-white font-label-caps text-label-caps rounded-lg hover:bg-primary-container transition-colors shadow-sm cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        New Ticket
                    </button>
                </div>
            </div>

            <!-- Stats Overview Bento Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Open -->
                <div class="bg-white p-card-padding rounded-xl border border-table-border shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <p class="font-label-caps text-label-caps text-on-surface-variant">Total Open</p>
                        <span class="material-symbols-outlined text-outline">receipt_long</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-bold text-primary"><?= $total_open ?></h3>
                    </div>
                </div>

                <!-- High Priority -->
                <div class="bg-white p-card-padding rounded-xl border border-table-border shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <p class="font-label-caps text-label-caps text-on-surface-variant">High Priority</p>
                        <span class="material-symbols-outlined text-error">priority_high</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-bold text-error"><?= $high_priority ?></h3>
                    </div>
                </div>

                <!-- In Progress -->
                <div class="bg-white p-card-padding rounded-xl border border-table-border shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <p class="font-label-caps text-label-caps text-on-surface-variant">In Progress</p>
                        <span class="material-symbols-outlined text-warning">pending_actions</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-bold text-warning"><?= $in_progress ?></h3>
                    </div>
                </div>

                <!-- Resolved -->
                <div class="bg-white p-card-padding rounded-xl border border-table-border shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <p class="font-label-caps text-label-caps text-on-surface-variant">Resolved</p>
                        <span class="material-symbols-outlined text-success">task_alt</span>
                    </div>
                    <div class="mt-4">
                        <h3 class="text-3xl font-bold text-success"><?= $resolved_count ?></h3>
                    </div>
                </div>
            </div>

            <!-- Main Data Table Card -->
            <div class="bg-white rounded-xl border border-table-border shadow-sm overflow-hidden flex flex-col">
                <div class="flex flex-col sm:flex-row justify-between items-center p-4 border-b border-table-border bg-surface-bright gap-4">
                    <div class="flex flex-wrap gap-2">
                        <a href="complaints.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?= $category_filter === 'all' ? 'bg-primary/10 text-primary font-bold' : 'bg-transparent text-on-surface-variant hover:bg-surface-container-low border border-table-border' ?> font-label-caps text-[10px]">
                            All (<?= count($complaints) ?>)
                        </a>
                        <a href="complaints.php?cat=Plumbing" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?= $category_filter === 'Plumbing' ? 'bg-primary/10 text-primary font-bold' : 'bg-transparent text-on-surface-variant hover:bg-surface-container-low border border-table-border' ?> font-label-caps text-[10px]">
                            Plumbing
                        </a>
                        <a href="complaints.php?cat=Electrical" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?= $category_filter === 'Electrical' ? 'bg-primary/10 text-primary font-bold' : 'bg-transparent text-on-surface-variant hover:bg-surface-container-low border border-table-border' ?> font-label-caps text-[10px]">
                            Electrical
                        </a>
                        <a href="complaints.php?cat=IT Support" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?= $category_filter === 'IT Support' ? 'bg-primary/10 text-primary font-bold' : 'bg-transparent text-on-surface-variant hover:bg-surface-container-low border border-table-border' ?> font-label-caps text-[10px]">
                            IT Support
                        </a>
                    </div>
                    <div class="relative w-full sm:w-auto">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[16px]">search</span>
                        <input data-table-search="complaintsTable" class="pl-9 pr-3 py-1.5 border border-table-border rounded-lg text-sm w-full sm:w-48 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all h-8 text-on-surface" placeholder="Search subject..." type="text">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="complaintsTable" class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-[#F8F9FA] border-b border-table-border font-label-caps text-label-caps text-on-surface-variant">
                                <th class="px-table-cell-x py-table-cell-y w-28">Ticket ID</th>
                                <th class="px-table-cell-x py-table-cell-y w-32">Category</th>
                                <th class="px-table-cell-x py-table-cell-y min-w-[250px]">Subject & Description</th>
                                <th class="px-table-cell-x py-table-cell-y w-36">Student</th>
                                <th class="px-table-cell-x py-table-cell-y w-24">Priority</th>
                                <th class="px-table-cell-x py-table-cell-y w-40">Status</th>
                                <th class="px-table-cell-x py-table-cell-y w-16 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-table-border">
                            <?php if (!empty($complaints)): ?>
                                <?php foreach ($complaints as $c): ?>
                                <tr class="hover:bg-[#F1F3F5] transition-colors group">
                                    <td class="px-table-cell-x py-table-cell-y font-table-data text-table-data text-primary font-semibold">
                                        <?= htmlspecialchars($c['ticket_no']) ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y font-body-sm text-body-sm text-secondary">
                                        <?= htmlspecialchars($c['category']) ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <p class="font-table-data text-table-data text-on-surface font-semibold mb-0.5"><?= htmlspecialchars($c['subject']) ?></p>
                                        <p class="text-xs text-on-surface-variant line-clamp-1 max-w-sm"><?= htmlspecialchars($c['description']) ?></p>
                                        <span class="text-[10px] text-secondary"><?= formatDate($c['created_at']) ?></span>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y font-body-sm text-body-sm text-on-surface">
                                        <a href="student_profile.php?id=<?= urlencode($c['student_id']) ?>" class="hover:underline font-medium"><?= htmlspecialchars($c['student_name']) ?></a>
                                        <div class="text-[10px] text-secondary"><?= htmlspecialchars($c['student_id']) ?></div>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <?php if ($c['priority'] === 'High'): ?>
                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-error/10 text-error">High</span>
                                        <?php elseif ($c['priority'] === 'Medium'): ?>
                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-warning/10 text-warning">Medium</span>
                                        <?php else: ?>
                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-surface-variant text-on-surface-variant">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <form method="POST" action="complaints.php">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="ticket_no" value="<?= htmlspecialchars($c['ticket_no']) ?>">
                                            <select name="status" onchange="this.form.submit()" class="block w-full text-xs font-semibold py-1.5 pl-2 pr-6 border border-table-border rounded bg-surface cursor-pointer <?= $c['status'] === 'Open' ? 'text-error' : ($c['status'] === 'In Progress' ? 'text-warning' : 'text-success') ?>">
                                                <option value="Open" <?= $c['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
                                                <option value="In Progress" <?= $c['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Resolved" <?= $c['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-right">
                                        <form method="POST" action="complaints.php" onsubmit="return confirm('Delete complaint <?= htmlspecialchars($c['ticket_no']) ?>?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="ticket_no" value="<?= htmlspecialchars($c['ticket_no']) ?>">
                                            <button type="submit" class="p-1 text-on-surface-variant hover:text-error transition-colors cursor-pointer" title="Delete">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="p-6 text-center text-on-surface-variant">No complaint tickets found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: New Ticket -->
<div id="newTicketModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-xl shadow-xl flex flex-col">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl">
            <h3 class="font-title-sm text-title-sm text-primary font-bold">Log New Complaint Ticket</h3>
            <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('newTicketModal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form action="complaints.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_ticket">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Select Student *</label>
                    <select name="student_id" required class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                        <option value="" disabled selected>Select student...</option>
                        <?php foreach ($students_list as $s): ?>
                        <option value="<?= htmlspecialchars($s['student_id']) ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['student_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                            <option value="Plumbing">Plumbing</option>
                            <option value="Electrical">Electrical</option>
                            <option value="IT Support">IT Support</option>
                            <option value="Carpentry">Carpentry</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Priority</label>
                        <select name="priority" class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Subject *</label>
                    <input name="subject" required placeholder="e.g. Water leakage in washroom" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Detailed Description *</label>
                    <textarea name="description" rows="3" required placeholder="Describe the issue..." class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface"></textarea>
                </div>
                <div class="pt-4 border-t border-table-border flex justify-end gap-3">
                    <button type="button" onclick="closeModal('newTicketModal')" class="px-4 py-2 border border-primary text-primary rounded text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded text-sm">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
