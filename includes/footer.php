<?php
/**
 * Footer Component - Sipna Hostel Management System
 */
$flash = getFlashMessage();
?>
    <!-- Toast Notification Container -->
    <?php if ($flash): ?>
    <div class="toast-container">
        <div class="toast-alert toast-<?= htmlspecialchars($flash['type']) ?>">
            <span class="material-symbols-outlined text-lg">
                <?= $flash['type'] === 'success' ? 'check_circle' : ($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'warning' ? 'warning' : 'info')) ?>
            </span>
            <div class="flex-1">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <button type="button" class="text-current opacity-70 hover:opacity-100" onclick="this.parentElement.remove()">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Project JavaScript -->
    <script src="js/script.js"></script>
</body>
</html>
