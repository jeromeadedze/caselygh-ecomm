<?php

require_once __DIR__ . "/../core/core.php";
require_once __DIR__ . "/../controllers/CustomDesignController.php";

require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/admin/designs.php');
}
verify_csrf();

$decision = $_POST['decision'] ?? '';
$note = $_POST['note'] ?? '';
$ok = (new CustomDesignController())->review((int) ($_POST['design_id'] ?? 0), $decision, $note);

if (!$ok) {
    $_SESSION['error'] = $decision === 'rejected'
        ? "Add a reason for rejecting this design."
        : "Unable to save that decision. Try again.";
} elseif ($decision === 'approved') {
    $_SESSION['success'] = "Design approved for printing.";
} else {
    $_SESSION['warning'] = "Design rejected. Contact the customer about a new design or a refund.";
}
redirect('../views/admin/designs.php');
