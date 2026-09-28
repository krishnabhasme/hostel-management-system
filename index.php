<?php
/**
 * Root Router - Sipna Hostel Management System
 */
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
