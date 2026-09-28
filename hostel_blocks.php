<?php
/**
 * Hostel Block Management - Sipna Hostel Management System
 * Preserves 100% of Stitch Hostel Block Management UI with Live Database Coordination
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Hostel Blocks - Sipna Hostel';

// Handle POST actions (Create Block, Delete Block)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $block_name = trim($_POST['block_name'] ?? '');
        $type = $_POST['type'] ?? 'Boys Hostel';
        $warden_name = trim($_POST['warden_name'] ?? '');
        $warden_contact = trim($_POST['warden_contact'] ?? '');

        if (empty($block_name) || empty($warden_name)) {
            setFlashMessage('error', 'Please provide block name and assigned warden name.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO blocks (block_name, type, warden_name, warden_contact) VALUES (?, ?, ?, ?)");
                $stmt->execute([$block_name, $type, $warden_name, $warden_contact]);
                setFlashMessage('success', "Hostel {$block_name} created successfully!");
            } catch (Exception $e) {
                setFlashMessage('error', 'Database error: ' . $e->getMessage());
            }
        }
        header("Location: hostel_blocks.php");
        exit;
    }

    if ($action === 'delete') {
        $block_id = (int)($_POST['block_id'] ?? 0);
        if ($block_id > 0) {
            try {
                // Delete associated allocations, rooms, and block
                $pdo->prepare("DELETE FROM allocations WHERE room_id IN (SELECT id FROM rooms WHERE block_id = ?)")->execute([$block_id]);
                $pdo->prepare("DELETE FROM rooms WHERE block_id = ?")->execute([$block_id]);
                $stmt = $pdo->prepare("DELETE FROM blocks WHERE id = ?");
                $stmt->execute([$block_id]);
                setFlashMessage('success', 'Hostel block and associated rooms deleted successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Delete error: ' . $e->getMessage());
            }
        }
        header("Location: hostel_blocks.php");
        exit;
    }
}

// Fetch Blocks with real live room counts and allocation stats
try {
    $stmt = $pdo->query("SELECT 
        b.*,
        COUNT(DISTINCT r.id) as total_rooms,
        COALESCE(SUM(r.capacity), 0) as total_capacity,
        COALESCE((SELECT COUNT(*) FROM allocations a JOIN rooms r2 ON a.room_id = r2.id WHERE r2.block_id = b.id AND a.status = 'Active'), 0) as occupied_beds,
        COALESCE((SELECT COUNT(DISTINCT r3.id) FROM rooms r3 WHERE r3.block_id = b.id AND r3.status != 'Maintenance' AND (SELECT COUNT(*) FROM allocations a2 WHERE a2.room_id = r3.id AND a2.status = 'Active') < r3.capacity), 0) as available_rooms
    FROM blocks b
    LEFT JOIN rooms r ON b.id = r.block_id
    GROUP BY b.id
    ORDER BY b.block_name ASC");
    $blocks = $stmt->fetchAll();
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
    <main class="flex-1 overflow-y-auto p-gutter bg-background pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-2">
                <div>
                    <h2 class="font-headline-md text-headline-md text-on-surface font-bold">Hostel Blocks</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Manage block facilities, buildings, and assigned wardens.</p>
                </div>
                <button type="button" onclick="openModal('addBlockModal')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded font-label-caps text-label-caps hover:bg-primary-container transition-colors shadow-sm cursor-pointer whitespace-nowrap">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Add Block
                </button>
            </div>

            <!-- Block Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($blocks as $block): 
                    $total_cap = (int)$block['total_capacity'];
                    $occupied = (int)$block['occupied_beds'];
                    $avail_beds = max(0, $total_cap - $occupied);
                    $pct = $total_cap > 0 ? round(($occupied / $total_cap) * 100) : 0;
                ?>
                <div class="bg-surface-container-lowest border border-table-border rounded-lg p-card-padding hover:shadow-md transition-shadow duration-300 flex flex-col h-full group">
                    <div class="flex justify-between items-start mb-4 border-b border-table-border pb-3">
                        <div>
                            <h3 class="font-title-sm text-title-sm text-primary font-bold"><?= htmlspecialchars($block['block_name']) ?></h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= $block['type'] === 'Girls Hostel' ? 'bg-indigo-50 text-indigo-700' : 'bg-success/10 text-success' ?> mt-1">
                                <?= htmlspecialchars($block['type']) ?>
                            </span>
                        </div>
                        <form method="POST" action="hostel_blocks.php" onsubmit="return confirm('Delete <?= htmlspecialchars($block['block_name']) ?>? All associated rooms and allocations will be removed.');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="block_id" value="<?= $block['id'] ?>">
                            <button type="submit" class="text-on-surface-variant hover:text-error transition-colors p-1 cursor-pointer" title="Delete Block">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    </div>

                    <div class="flex-1 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-surface-container-low p-3 rounded border border-table-border/50">
                                <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Total Rooms</p>
                                <p class="font-title-sm text-title-sm text-on-surface font-semibold"><?= $block['total_rooms'] ?> <span class="text-xs font-normal text-secondary">(<?= $total_cap ?> Beds)</span></p>
                            </div>
                            <div class="bg-surface-container-low p-3 rounded border border-table-border/50">
                                <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">Available</p>
                                <p class="font-title-sm text-title-sm text-success font-semibold"><?= $block['available_rooms'] ?> Rooms <span class="text-xs font-normal text-secondary">(<?= $avail_beds ?> Beds)</span></p>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-end mb-1">
                                <p class="font-label-caps text-label-caps text-on-surface-variant">Occupancy (<?= $occupied ?>/<?= $total_cap ?> Beds)</p>
                                <p class="font-body-sm text-body-sm text-on-surface font-semibold"><?= $pct ?>%</p>
                            </div>
                            <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                                <div class="bg-primary h-2 rounded-full transition-all" style="width: <?= min(100, $pct) ?>%"></div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-3 border-t border-table-border/50 mt-auto">
                            <div class="w-8 h-8 rounded-full bg-tertiary-fixed-dim/20 flex items-center justify-center text-tertiary shrink-0">
                                <span class="material-symbols-outlined text-[18px]">person</span>
                            </div>
                            <div>
                                <p class="font-label-caps text-label-caps text-on-surface-variant">Warden</p>
                                <p class="font-body-sm text-body-sm text-on-surface font-medium"><?= htmlspecialchars($block['warden_name']) ?></p>
                                <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($block['warden_contact'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Add Block Placeholder Card -->
                <div onclick="openModal('addBlockModal')" class="bg-surface border-2 border-dashed border-outline-variant rounded-lg p-card-padding hover:border-primary hover:bg-surface-container-low transition-all duration-300 flex flex-col items-center justify-center text-center cursor-pointer min-h-[240px]">
                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-3">
                        <span class="material-symbols-outlined text-[24px]">add</span>
                    </div>
                    <h3 class="font-title-sm text-title-sm text-primary font-bold">Add New Block</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">Add another hostel building</p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Add Block -->
<div id="addBlockModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-xl shadow-xl flex flex-col">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl">
            <h3 class="font-title-sm text-title-sm text-primary font-bold">Add New Hostel Block</h3>
            <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('addBlockModal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form action="hostel_blocks.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Block Name *</label>
                    <input name="block_name" required placeholder="e.g. Block D, Annex Wing" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Hostel Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                        <option value="Boys Hostel">Boys Hostel</option>
                        <option value="Girls Hostel">Girls Hostel</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Warden Name *</label>
                    <input name="warden_name" required placeholder="e.g. Dr. A. P. Sharma" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Warden Contact</label>
                    <input name="warden_contact" placeholder="+91 98765 43210" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                </div>
                <div class="pt-4 border-t border-table-border flex justify-end gap-3">
                    <button type="button" onclick="closeModal('addBlockModal')" class="px-4 py-2 border border-primary text-primary rounded text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded text-sm">Save Block</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
