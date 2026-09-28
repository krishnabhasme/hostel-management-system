/**
 * JavaScript Utilities for Sipna Hostel Management System
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileSidebar = document.getElementById('sidebarNav') || document.getElementById('mobile-sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    if (mobileMenuBtn && mobileSidebar) {
        mobileMenuBtn.addEventListener('click', function (e) {
            e.preventDefault();
            mobileSidebar.classList.toggle('hidden');
            mobileSidebar.classList.toggle('flex');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('hidden');
            }
        });
    }

    if (sidebarOverlay && mobileSidebar) {
        sidebarOverlay.addEventListener('click', function () {
            mobileSidebar.classList.add('hidden');
            mobileSidebar.classList.remove('flex');
            sidebarOverlay.classList.add('hidden');
        });
    }

    // 2. Auto-dismiss Toasts
    const toasts = document.querySelectorAll('.toast-alert');
    toasts.forEach(function (toast) {
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function () {
                toast.remove();
            }, 300);
        }, 4000);
    });

    // 3. Password Visibility Toggle on Login
    const togglePasswordBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            const icon = togglePasswordBtn.querySelector('.material-symbols-outlined');
            if (icon) {
                icon.textContent = isPassword ? 'visibility' : 'visibility_off';
            }
        });
    }

    // 4. Quick Table Filter
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function (input) {
        input.addEventListener('keyup', function () {
            const targetTableId = input.getAttribute('data-table-search');
            const filterValue = input.value.toLowerCase().trim();
            const table = document.getElementById(targetTableId);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filterValue) ? '' : 'none';
            });
        });
    });
});

// Modal Open Helper
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

// Modal Close Helper
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// Print Receipt Helper
function printReceipt() {
    window.print();
}
