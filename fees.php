<?php
/**
 * Fee Management - Sipna Hostel Management System
 * Preserves 100% of Stitch Fee Management UI with Simple SQL
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Fee Management - Sipna Hostel';

// Handle POST actions (Record Payment, Delete Fee)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Record New Fee Payment
    if ($action === 'create_payment') {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $payment_method = $_POST['payment_method'] ?? 'UPI';
        $status = $_POST['status'] ?? 'Paid';
        $receipt_no = 'RCP-24-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);

        if ($student_id <= 0 || $amount <= 0) {
            setFlashMessage('error', 'Please select a student and enter a valid fee amount.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO fees (receipt_no, student_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$receipt_no, $student_id, $amount, $payment_date, $payment_method, $status]);
                setFlashMessage('success', "Payment receipt {$receipt_no} recorded successfully!");
            } catch (Exception $e) {
                setFlashMessage('error', 'Payment recording failed: ' . $e->getMessage());
            }
        }
        header("Location: fees.php");
        exit;
    }

    // Delete Fee Record
    if ($action === 'delete') {
        $fee_id = (int)($_POST['fee_id'] ?? 0);
        if ($fee_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM fees WHERE id = ?");
                $stmt->execute([$fee_id]);
                setFlashMessage('success', 'Fee payment transaction deleted.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Delete error: ' . $e->getMessage());
            }
        }
        header("Location: fees.php");
        exit;
    }
}

// Fetch Summary Stats
try {
    $total_collected = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status = 'Paid'")->fetchColumn();
    $pending_total = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE status IN ('Pending', 'Overdue')")->fetchColumn();
    $paid_students_count = (int)$pdo->query("SELECT COUNT(DISTINCT student_id) FROM fees WHERE status = 'Paid'")->fetchColumn();
    $overdue_count = (int)$pdo->query("SELECT COUNT(*) FROM fees WHERE status = 'Overdue'")->fetchColumn();

    // Fetch Students for Payment Modal
    $students_list = $pdo->query("SELECT id, student_id, name, department FROM students WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

    // Fetch Transactions with Student details
    $transactions_stmt = $pdo->query("SELECT 
        f.*,
        s.name as student_name,
        s.student_id as student_code,
        s.department
    FROM fees f
    JOIN students s ON f.student_id = s.id
    ORDER BY f.payment_date DESC, f.id DESC");
    $transactions = $transactions_stmt->fetchAll();

} catch (Exception $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col md:ml-sidebar min-w-0 bg-surface">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <!-- Main Content Canvas -->
    <main class="flex-1 overflow-y-auto p-gutter bg-surface-container-lowest pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-bold">Fee Management</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Track student fee schedules, generate receipts, and log transactions.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="openModal('recordPaymentModal')" class="flex items-center justify-center gap-2 px-4 py-2 bg-primary text-white font-label-caps text-label-caps rounded hover:bg-primary-container transition-colors shadow-sm cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">add_card</span>
                        Record Payment
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Collection -->
                <div class="bg-white border border-table-border rounded-lg p-card-padding flex flex-col relative overflow-hidden group shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center text-success">
                            <span class="material-symbols-outlined">account_balance</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase">Total Collection</p>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface"><?= formatCurrency($total_collected) ?></h3>
                    </div>
                </div>

                <!-- Pending Fees -->
                <div class="bg-white border border-table-border rounded-lg p-card-padding flex flex-col relative overflow-hidden group shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center text-warning">
                            <span class="material-symbols-outlined">pending_actions</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase">Pending Fees</p>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface"><?= formatCurrency($pending_total) ?></h3>
                    </div>
                </div>

                <!-- Paid Students -->
                <div class="bg-white border border-table-border rounded-lg p-card-padding flex flex-col relative overflow-hidden group shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">how_to_reg</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase">Paid Students</p>
                        <h3 class="font-display-lg text-display-lg font-bold text-on-surface"><?= $paid_students_count ?></h3>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="bg-white border border-table-border rounded-lg p-card-padding flex flex-col relative overflow-hidden group border-l-4 border-l-error shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 rounded-full bg-error/10 flex items-center justify-center text-error">
                            <span class="material-symbols-outlined">event_busy</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-label-caps text-label-caps text-on-surface-variant mb-1 uppercase">Overdue</p>
                        <h3 class="font-display-lg text-display-lg font-bold text-error"><?= $overdue_count ?></h3>
                    </div>
                </div>
            </div>

            <!-- Detailed Fee Table -->
            <div class="bg-white border border-table-border rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="p-4 border-b border-table-border flex flex-col sm:flex-row justify-between items-center gap-4 bg-surface-bright">
                    <h3 class="font-title-sm text-title-sm font-semibold text-on-surface">Recent Transactions</h3>
                    <div class="relative w-full sm:w-64">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[18px]">search</span>
                        <input data-table-search="feeTable" class="w-full pl-9 pr-3 py-1.5 bg-surface border border-table-border rounded text-body-sm text-on-surface focus:outline-none focus:border-primary" placeholder="Search receipt, student..." type="text">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="feeTable" class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-[#F8F9FA] border-b border-table-border font-label-caps text-label-caps text-on-surface-variant uppercase tracking-wider">
                                <th class="px-table-cell-x py-table-cell-y font-bold">Receipt No</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold">Student Name</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold">Student ID</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold">Amount</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold">Date</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold">Method</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold">Status</th>
                                <th class="px-table-cell-x py-table-cell-y font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="font-table-data text-table-data text-on-surface divide-y divide-table-border">
                            <?php if (!empty($transactions)): ?>
                                <?php foreach ($transactions as $tx): 
                                    $initials = getInitials($tx['student_name']);
                                ?>
                                <tr class="hover:bg-[#F1F3F5] transition-colors group">
                                    <td class="px-table-cell-x py-table-cell-y font-semibold text-primary"><?= htmlspecialchars($tx['receipt_no']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded bg-primary/10 text-primary text-[10px] font-bold flex items-center justify-center"><?= $initials ?></div>
                                            <a href="student_profile.php?id=<?= $tx['student_id'] ?>" class="hover:underline font-medium">
                                                <?= htmlspecialchars($tx['student_name']) ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-on-surface-variant"><?= htmlspecialchars($tx['student_code']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y font-semibold text-on-surface"><?= formatCurrency($tx['amount']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y text-on-surface-variant"><?= formatDate($tx['payment_date']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y"><?= htmlspecialchars($tx['payment_method']) ?></td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <?php if ($tx['status'] === 'Paid'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-success/10 text-success border border-success/20">Paid</span>
                                        <?php elseif ($tx['status'] === 'Pending'): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-warning/10 text-warning border border-warning/20">Pending</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-error/10 text-error border border-error/20">Overdue</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" onclick="viewReceipt(<?= htmlspecialchars(json_encode($tx)) ?>)" class="p-1 text-on-surface-variant hover:text-primary transition-colors cursor-pointer" title="View Receipt">
                                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                                            </button>
                                            <form method="POST" action="fees.php" onsubmit="return confirm('Delete transaction <?= htmlspecialchars($tx['receipt_no']) ?>?');" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="fee_id" value="<?= $tx['id'] ?>">
                                                <button type="submit" class="p-1 text-on-surface-variant hover:text-error transition-colors" title="Delete">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center p-10 text-on-surface-variant">
                                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-2 block">receipt_long</span>
                                        No fee payment records found. Use "Record Payment" above to add transactions.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Record Payment -->
<div id="recordPaymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-xl shadow-xl flex flex-col">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl">
            <h3 class="font-title-sm text-title-sm text-primary font-bold">Record Fee Payment</h3>
            <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('recordPaymentModal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form action="fees.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_payment">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Select Student *</label>
                    <select name="student_id" required class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                        <?php if (!empty($students_list)): ?>
                            <option value="" disabled selected>Select student...</option>
                            <?php foreach ($students_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['student_id']) ?> - <?= htmlspecialchars($s['department']) ?>)</option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled selected>No students found. Register student first.</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Amount (₹) *</label>
                        <input name="amount" type="number" step="100" value="45000" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Payment Date</label>
                        <input name="payment_date" type="date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                            <option value="UPI">UPI</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                            <option value="Paid" selected>Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                <div class="pt-4 border-t border-table-border flex justify-end gap-3">
                    <button type="button" onclick="closeModal('recordPaymentModal')" class="px-4 py-2 border border-primary text-primary rounded text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded text-sm">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Printable Receipt View -->
<div id="receiptModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-xl shadow-2xl flex flex-col">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl no-print">
            <h3 class="font-title-sm text-title-sm text-primary font-bold flex items-center gap-2">
                <span class="material-symbols-outlined">receipt</span> Fee Receipt
            </h3>
            <div class="flex items-center gap-2">
                <button type="button" onclick="printReceipt()" class="px-3 py-1 bg-primary text-white rounded text-xs flex items-center gap-1 cursor-pointer">
                    <span class="material-symbols-outlined text-sm">print</span> Print
                </button>
                <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('receiptModal')">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div id="printableReceipt" class="p-6 text-on-surface space-y-4">
            <div class="text-center border-b border-table-border pb-4">
                <h2 class="text-xl font-bold text-primary">Sipna College Hostel</h2>
                <div class="mt-2 inline-block px-3 py-1 bg-primary-container text-white text-xs font-bold rounded">
                    FEE RECEIPT
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-xs text-on-surface-variant block">Receipt No:</span>
                    <strong id="rcpt_no" class="text-primary font-mono">-</strong>
                </div>
                <div class="text-right">
                    <span class="text-xs text-on-surface-variant block">Date:</span>
                    <strong id="rcpt_date">-</strong>
                </div>
                <div>
                    <span class="text-xs text-on-surface-variant block">Student:</span>
                    <strong id="rcpt_student">-</strong>
                </div>
                <div class="text-right">
                    <span class="text-xs text-on-surface-variant block">Student ID:</span>
                    <strong id="rcpt_id">-</strong>
                </div>
                <div>
                    <span class="text-xs text-on-surface-variant block">Method:</span>
                    <span id="rcpt_method">-</span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-on-surface-variant block">Status:</span>
                    <span id="rcpt_status" class="font-bold text-success">PAID</span>
                </div>
            </div>
            <div class="p-4 bg-surface-container-low rounded border border-table-border text-center">
                <span class="text-xs text-on-surface-variant uppercase font-bold">Total Amount Paid</span>
                <div id="rcpt_amount" class="text-2xl font-bold text-primary mt-1">₹ 0.00</div>
            </div>
        </div>
    </div>
</div>

<script>
function viewReceipt(tx) {
    document.getElementById('rcpt_no').textContent = tx.receipt_no;
    document.getElementById('rcpt_date').textContent = tx.payment_date;
    document.getElementById('rcpt_student').textContent = tx.student_name;
    document.getElementById('rcpt_id').textContent = tx.student_code;
    document.getElementById('rcpt_method').textContent = tx.payment_method;
    document.getElementById('rcpt_status').textContent = tx.status.toUpperCase();
    document.getElementById('rcpt_amount').textContent = '₹ ' + parseFloat(tx.amount).toLocaleString('en-IN');
    openModal('receiptModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
