<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !empty($_SESSION['admin_id'])) {
    redirect(ADMIN_URL . 'dashboard.php');
} else {
    redirect(BASE_URL . 'staff-login.php');
}