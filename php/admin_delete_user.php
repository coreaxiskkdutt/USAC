<?php
require_once __DIR__ . '/helpers.php';
requireAdmin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    flash('User not found.', 'danger');
    header('Location: admin_users.php');
    exit;
}

if (isAdmin($user)) {
    $adminCount = $db->querySingle("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($adminCount <= 1) {
        flash('Cannot delete the last admin account.', 'danger');
        header('Location: admin_users.php');
        exit;
    }
}

$stmt = $db->prepare('DELETE FROM users WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$stmt->execute();
flash('User ' . $user['full_name'] . ' has been deleted.', 'warning');
header('Location: admin_users.php');
exit;
