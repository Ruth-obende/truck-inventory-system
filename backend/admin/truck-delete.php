<?php
/**
 * =============================================================================
 * Moal General Suppliers - Delete Truck Handler
 * =============================================================================
 * Securely deletes a vehicle record and removes associated uploaded photographs.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$truckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$csrf    = $_GET['csrf_token'] ?? '';

if ($truckId <= 0 || !verify_csrf_token($csrf)) {
    set_flash_message('error', 'Invalid request or expired security token.');
    redirect(ADMIN_URL . 'trucks.php');
}

$db = getDB();

try {
    // 1. Fetch images to delete physical files
    $stmtImgs = $db->prepare('SELECT image_path FROM truck_images WHERE truck_id = :id');
    $stmtImgs->execute([':id' => $truckId]);
    $images = $stmtImgs->fetchAll();

    foreach ($images as $img) {
        $filePath = UPLOADS_PATH . $img['image_path'];
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
    }

    // 2. Delete truck (foreign keys automatically cascade in MySQL)
    $stmtDelete = $db->prepare('DELETE FROM trucks WHERE id = :id');
    $stmtDelete->execute([':id' => $truckId]);

    set_flash_message('success', 'Truck record and associated images deleted successfully.');
} catch (PDOException $e) {
    error_log('[Truck Delete Error] ' . $e->getMessage());
    set_flash_message('error', 'Failed to delete truck record: ' . $e->getMessage());
}

redirect(ADMIN_URL . 'trucks.php');
