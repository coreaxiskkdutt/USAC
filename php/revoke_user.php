<?php
require_once __DIR__ . '/helpers.php';
requireSafetyOfficer();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$user || $user['role'] === 'safety_officer') {
    flash('Invalid user.', 'danger');
} else {
    $stmt = $db->prepare('UPDATE users SET is_approved = 0 WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    flash('Approval for ' . $user['full_name'] . ' has been revoked.', 'warning');
}

header('Location: manage_approvals.php');
exit;
