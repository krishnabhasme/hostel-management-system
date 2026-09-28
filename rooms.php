<?php
/**
 * Room Management - Sipna Hostel Management System
 * Preserves 100% of Stitch Room Management UI with Live Database Coordination
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$page_title = 'Room Management - Sipna Hostel';

// Handle POST actions (Create Room, Update Status, Delete Room)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create Room
    if ($action === 'create') {
        $block_id = (int)($_POST['block_id'] ?? 0);
        $room_number = trim($_POST['room_number'] ?? '');
        $room_type = $_POST['room_type'] ?? 'Double';
        $capacity = (int)($_POST['capacity'] ?? 2);
        $fee = (float)($_POST['fee'] ?? 45000.00);

        if ($block_id <= 0 || empty($room_number)) {
            setFlashMessage('error', 'Please select a hostel block and specify a room number.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO rooms (block_id, room_number, room_type, capacity, fee, status) VALUES (?, ?, ?, ?, ?, 'Available')");
                $stmt->execute([$block_id, $room_number, $room_type, $capacity, $fee]);
                setFlashMessage('success', "Room {$room_number} added successfully!");
            } catch (Exception $e) {
                setFlashMessage('error', 'Database error: ' . $e->getMessage());
            }
        }
        header("Location: rooms.php");
        exit;
    }

    // Toggle Status (Maintenance / Available)
    if ($action === 'update_status') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'Available';

        if ($room_id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $room_id]);
                setFlashMessage('success', 'Room status updated successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Failed to update status: ' . $e->getMessage());
            }
        }
        header("Location: rooms.php");
        exit;
    }

    // Delete Room
    if ($action === 'delete') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        if ($room_id > 0) {
            try {
                // Delete allocations first then room
                $pdo->prepare("DELETE FROM allocations WHERE room_id = ?")->execute([$room_id]);
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->execute([$room_id]);
                setFlashMessage('success', 'Room deleted successfully.');
            } catch (Exception $e) {
                setFlashMessage('error', 'Delete error: ' . $e->getMessage());
            }
        }
        header("Location: rooms.php");
        exit;
    }
}

// Fetch Blocks for Filter and Modal
try {
    $blocks = $pdo->query("SELECT id, block_name, type FROM blocks ORDER BY block_name ASC")->fetchAll();
} catch (Exception $e) {
    $blocks = [];
}

// Filter parameters
$block_filter = $_GET['block'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$query = "SELECT 
    r.*,
    b.block_name,
    b.type as block_type,
    COALESCE((SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'Active'), 0) as occupied_beds
FROM rooms r
JOIN blocks b ON r.block_id = b.id
WHERE 1=1";

$params = [];
if (!empty($block_filter) && $block_filter !== 'all') {
    $query .= " AND r.block_id = ?";
    $params[] = (int)$block_filter;
}
if (!empty($status_filter) && $status_filter !== 'all') {
    if ($status_filter === 'Maintenance') {
        $query .= " AND r.status = 'Maintenance'";
    } elseif ($status_filter === 'Full') {
        $query .= " AND r.status != 'Maintenance' AND (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'Active') >= r.capacity";
    } elseif ($status_filter === 'Available') {
        $query .= " AND r.status != 'Maintenance' AND (SELECT COUNT(*) FROM allocations a WHERE a.room_id = r.id AND a.status = 'Active') < r.capacity";
    }
}

$query .= " ORDER BY b.block_name ASC, r.room_number ASC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll();
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
    <main class="flex-1 overflow-y-auto p-4 md:p-gutter bg-background pb-24">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header & Actions -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="font-display-lg text-display-lg text-on-surface font-bold">Room Management</h1>
                    <p class="font-body-main text-body-main text-secondary mt-1">Manage room inventory, bed capacities, and real-time availability.</p>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <button type="button" onclick="openModal('addRoomModal')" class="flex-1 md:flex-none bg-primary text-on-primary font-table-data text-table-data px-4 py-2 rounded flex items-center justify-center gap-2 hover:bg-primary-container transition-colors shadow-sm cursor-pointer whitespace-nowrap">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Add Room
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-surface border border-table-border rounded-lg p-card-padding shadow-sm">
                <h3 class="font-label-caps text-label-caps text-on-surface-variant mb-4 uppercase tracking-wider">Filter Rooms</h3>
                <form action="rooms.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Block Filter -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-label-caps text-label-caps text-on-surface-variant">Hostel Block</label>
                        <select name="block" class="w-full bg-surface border border-table-border rounded px-3 py-2 font-body-main text-body-main text-on-surface focus:outline-none focus:border-primary">
                            <option value="all">All Blocks</option>
                            <?php foreach ($blocks as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $block_filter == $b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['block_name']) ?> (<?= htmlspecialchars($b['type']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex flex-col gap-1.5">
                        <label class="font-label-caps text-label-caps text-on-surface-variant">Status</label>
                        <select name="status" class="w-full bg-surface border border-table-border rounded px-3 py-2 font-body-main text-body-main text-on-surface focus:outline-none focus:border-primary">
                            <option value="all">All Statuses</option>
                            <option value="Available" <?= $status_filter === 'Available' ? 'selected' : '' ?>>Available (Vacant Beds)</option>
                            <option value="Full" <?= $status_filter === 'Full' ? 'selected' : '' ?>>Full (100% Occupied)</option>
                            <option value="Maintenance" <?= $status_filter === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-surface-container-low text-on-surface font-table-data text-table-data px-4 py-2 border border-table-border rounded hover:bg-surface-variant transition-colors flex items-center justify-center gap-2 cursor-pointer">
                            <span class="material-symbols-outlined text-sm">filter_list</span>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Table Card -->
            <div class="bg-surface border border-table-border rounded-lg overflow-hidden shadow-sm">
                <div class="p-4 border-b border-table-border flex justify-between items-center bg-surface-bright">
                    <h3 class="font-title-sm text-title-sm text-on-surface font-semibold">Room Directory</h3>
                    <span class="font-body-sm text-body-sm text-secondary">Showing <?= count($rooms) ?> rooms</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-table-border font-label-caps text-label-caps text-on-surface-variant uppercase">
                                <th class="px-table-cell-x py-table-cell-y w-20 text-center">Room</th>
                                <th class="px-table-cell-x py-table-cell-y">Block</th>
                                <th class="px-table-cell-x py-table-cell-y">Type</th>
                                <th class="px-table-cell-x py-table-cell-y text-center">Capacity & Occupancy</th>
                                <th class="px-table-cell-x py-table-cell-y">Annual Fee</th>
                                <th class="px-table-cell-x py-table-cell-y">Status</th>
                                <th class="px-table-cell-x py-table-cell-y text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-table-border font-table-data text-table-data">
                            <?php if (!empty($rooms)): ?>
                                <?php foreach ($rooms as $r): 
                                    $occupied = (int)$r['occupied_beds'];
                                    $capacity = (int)$r['capacity'];
                                    $is_maintenance = ($r['status'] === 'Maintenance');
                                    $is_full = ($occupied >= $capacity);
                                    $free_beds = max(0, $capacity - $occupied);
                                ?>
                                <tr class="hover:bg-surface-container-lowest transition-colors group">
                                    <td class="px-table-cell-x py-table-cell-y font-semibold text-center text-primary text-base">
                                        <?= htmlspecialchars($r['room_number']) ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-on-surface font-medium">
                                        <?= htmlspecialchars($r['block_name']) ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-secondary">
                                        <?= htmlspecialchars($r['room_type']) ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-center font-medium">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="text-on-surface font-bold"><?= $occupied ?> / <?= $capacity ?> Beds</span>
                                            <span class="text-[10px] <?= $free_beds > 0 ? 'text-success' : 'text-secondary' ?>"><?= $free_beds > 0 ? "({$free_beds} Free)" : "(Full)" ?></span>
                                        </div>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y font-medium text-on-surface">
                                        <?= formatCurrency($r['fee']) ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y">
                                        <?php if ($is_maintenance): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-error-container text-on-error-container border border-[#FFB4AB]">Maintenance</span>
                                        <?php elseif ($is_full): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-surface-container-highest text-on-surface border border-outline-variant">Full</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#E8F5E9] text-success border border-[#A5D6A7]">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <form method="POST" action="rooms.php" class="inline">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $is_maintenance ? 'Available' : 'Maintenance' ?>">
                                                <button type="submit" class="p-1 text-on-surface-variant hover:text-warning cursor-pointer" title="<?= $is_maintenance ? 'Mark Available' : 'Mark Maintenance' ?>">
                                                    <span class="material-symbols-outlined text-[18px]">build</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="rooms.php" onsubmit="return confirm('Delete room <?= htmlspecialchars($r['room_number']) ?>? All associated allocations will be removed.');" class="inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="p-1 text-on-surface-variant hover:text-error cursor-pointer" title="Delete Room">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center p-6 text-on-surface-variant">No rooms found matching criteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Add Room -->
<div id="addRoomModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-on-background/40 backdrop-blur-[2px] p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-xl shadow-xl flex flex-col">
        <div class="px-6 py-4 border-b border-table-border flex justify-between items-center bg-surface-bright rounded-t-xl">
            <h3 class="font-title-sm text-title-sm text-primary font-bold">Add New Room</h3>
            <button type="button" class="text-secondary hover:text-on-surface p-1" onclick="closeModal('addRoomModal')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form action="rooms.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Hostel Block *</label>
                    <select name="block_id" required class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                        <?php foreach ($blocks as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['block_name']) ?> (<?= htmlspecialchars($b['type']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Room Number *</label>
                    <input name="room_number" required placeholder="e.g. 101, 204" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Room Type</label>
                        <select name="room_type" class="w-full px-3 py-2 border border-table-border rounded text-sm bg-surface text-on-surface">
                            <option value="Single">Single (1 Bed)</option>
                            <option value="Double" selected>Double (2 Beds)</option>
                            <option value="Triple">Triple (3 Beds)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Bed Capacity</label>
                        <input name="capacity" type="number" min="1" max="6" value="2" required class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant mb-1">Annual Fee (₹)</label>
                    <input name="fee" type="number" step="500" value="45000" class="w-full px-3 py-2 border border-table-border rounded text-sm text-on-surface">
                </div>
                <div class="pt-4 border-t border-table-border flex justify-end gap-3">
                    <button type="button" onclick="closeModal('addRoomModal')" class="px-4 py-2 border border-primary text-primary rounded text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded text-sm">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
