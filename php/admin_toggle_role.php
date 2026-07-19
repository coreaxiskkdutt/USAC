<?php
require_once __DIR__ . '/helpers.php';
requireAdmin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$user || isAdmin($user)) {
    flash('Cannot change admin role from here.', 'danger');
    header('Location: admin_users.php');
    exit;
}

if ($user['role'] === 'employee') {
    $stmt = $db->prepare("UPDATE users SET role = 'safety_officer', is_approved = 1 WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    flash($user['full_name'] . ' promoted to Safety Officer.', 'success');
} elseif ($user['role'] === 'safety_officer') {
    $stmt = $db->prepare("UPDATE users SET role = 'employee' WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    flash($user['full_name'] . ' demoted to Employee.', 'warning');
}

header('Location: admin_users.php');
exit;
