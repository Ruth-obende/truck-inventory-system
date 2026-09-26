<?php
/**
 * =============================================================================
 * Moal General Suppliers - Truck Deletion Handler
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Security verification failed. Please try again.');
        redirect(ADMIN_URL . 'trucks.php');
    }

    $truckId = (int)($_POST['id'] ?? 0);
    if ($truckId > 0) {
        $db = getDB();

        // Check truck exists
        $stmtTrk = $db->prepare('SELECT title, truck_code FROM trucks WHERE id = :id');
        $stmtTrk->execute([':id' => $truckId]);
        $trk = $stmtTrk->fetch();

        if ($trk) {
            // Remove associated image records
            $stmtImgs = $db->prepare('SELECT image_path FROM truck_images WHERE truck_id = :id');
            $stmtImgs->execute([':id' => $truckId]);
            $images = $stmtImgs->fetchAll();
            foreach ($images as $img) {
                $filePath = UPLOADS_PATH . $img['image_path'];
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            $db->prepare('DELETE FROM truck_images WHERE truck_id = :id')->execute([':id' => $truckId]);

            // Delete truck record
            $db->prepare('DELETE FROM trucks WHERE id = :id')->execute([':id' => $truckId]);

            set_flash_message('success', 'Truck "' . $trk['title'] . '" (' . $trk['truck_code'] . ') was successfully removed from inventory.');
        } else {
            set_flash_message('error', 'Vehicle record was not found.');
        }
    }
}

redirect(ADMIN_URL . 'trucks.php');