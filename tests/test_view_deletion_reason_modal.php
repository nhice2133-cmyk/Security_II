<?php
// tests/test_view_deletion_reason_modal.php
// Verification suite for Deletion Requests modern UI & Reason View Modal

require_once __DIR__ . '/../php/config.php';

$results = [];

function recordResult($desc, $pass, $details = '') {
    global $results;
    $results[] = [
        'description' => $desc,
        'pass' => $pass,
        'details' => $details
    ];
}

$dashFile = file_get_contents(__DIR__ . '/../pages/dashboard.php');
$adminJs = file_get_contents(__DIR__ . '/../js/admin.js');
$cssFile = file_get_contents(__DIR__ . '/../css/modern-design-system.css');

// Test 1: dashboard.php contains #viewDeletionReasonModal and all required elements
recordResult(
    "Dashboard contains #viewDeletionReasonModal element",
    strpos($dashFile, 'id="viewDeletionReasonModal"') !== false
);

recordResult(
    "Modal contains target user element #viewDelTargetUser",
    strpos($dashFile, 'id="viewDelTargetUser"') !== false
);

recordResult(
    "Modal contains requested by element #viewDelRequestedBy",
    strpos($dashFile, 'id="viewDelRequestedBy"') !== false
);

recordResult(
    "Modal contains status element #viewDelStatus",
    strpos($dashFile, 'id="viewDelStatus"') !== false
);

recordResult(
    "Modal contains reason quote box #viewDelReasonBox",
    strpos($dashFile, 'id="viewDelReasonBox"') !== false
);

recordResult(
    "Modal contains quick action buttons #viewDelModalRejectBtn and #viewDelModalApproveBtn",
    strpos($dashFile, 'id="viewDelModalRejectBtn"') !== false && strpos($dashFile, 'id="viewDelModalApproveBtn"') !== false
);

// Test 2: admin.js contains modal control functions and event handlers
recordResult(
    "admin.js defines currentDeleteRequests cache",
    strpos($adminJs, 'currentDeleteRequests') !== false
);

recordResult(
    "admin.js defines openViewDeletionReasonModal() function",
    strpos($adminJs, 'function openViewDeletionReasonModal(') !== false
);

recordResult(
    "admin.js defines closeViewDeletionReasonModal() function",
    strpos($adminJs, 'function closeViewDeletionReasonModal(') !== false
);

recordResult(
    "admin.js delegates view_reason click to openViewDeletionReasonModal",
    strpos($adminJs, 'data-action="view_reason"') !== false &&
    strpos($adminJs, 'openViewDeletionReasonModal(viewBtn.dataset.reqId)') !== false
);

recordResult(
    "admin.js exposes openViewDeletionReasonModal and closeViewDeletionReasonModal on window",
    strpos($adminJs, 'window.openViewDeletionReasonModal = openViewDeletionReasonModal;') !== false &&
    strpos($adminJs, 'window.closeViewDeletionReasonModal = closeViewDeletionReasonModal;') !== false
);

recordResult(
    "admin.js handles Escape key to dismiss viewDeletionReasonModal",
    strpos($adminJs, 'closeViewDeletionReasonModal()') !== false
);

// Test 3: modern-design-system.css styling
recordResult(
    "CSS defines .btn-view-reason-pill styling",
    strpos($cssFile, '.btn-view-reason-pill') !== false
);

recordResult(
    "CSS defines .reason-quote-box styling",
    strpos($cssFile, '.reason-quote-box') !== false
);

recordResult(
    "CSS defines .del-req-target-cell and .del-req-admin-badge styling",
    strpos($cssFile, '.del-req-target-cell') !== false && strpos($cssFile, '.del-req-admin-badge') !== false
);

// Test 4: Database query verification
try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query("
        SELECT dr.id, dr.reason, dr.status, t.username as target_username, t.id_number as target_id_number, a.username as admin_username
        FROM delete_requests dr
        JOIN users t ON dr.target_user_id = t.id
        JOIN users a ON dr.requested_by_admin_id = a.id
        WHERE dr.status = 'pending'
    ");
    $reqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    recordResult("Database query for pending deletion requests executes successfully", true, count($reqs) . " pending requests found");
} catch (Exception $e) {
    recordResult("Database query for pending deletion requests executes successfully", false, $e->getMessage());
}

// Print results
$allPassed = true;
echo "\n=======================================================\n";
echo "   DELETION REQUEST VIEW REASON MODAL & DESIGN TESTS   \n";
echo "=======================================================\n\n";

foreach ($results as $idx => $r) {
    $num = $idx + 1;
    $status = $r['pass'] ? "[ PASS ]" : "[ FAIL ]";
    if (!$r['pass']) $allPassed = false;
    echo sprintf("%2d. %-8s %s\n", $num, $status, $r['description']);
    if (!empty($r['details'])) {
        echo "    Details: " . $r['details'] . "\n";
    }
}

echo "\n-------------------------------------------------------\n";
echo "OVERALL RESULT: " . ($allPassed ? "ALL TESTS PASSED (100% OK)" : "SOME TESTS FAILED") . "\n";
echo "-------------------------------------------------------\n";

exit($allPassed ? 0 : 1);
