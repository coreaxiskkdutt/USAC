<?php
require_once __DIR__ . '/helpers.php';
requireAdmin();
$db = getDB();

$users = $db->query('SELECT * FROM users ORDER BY created_at DESC');
$allUsers = [];
while ($row = $users->fetchArray(SQLITE3_ASSOC)) $allUsers[] = $row;

$pageTitle = 'User Management';
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title">User Management</div>
    <div class="page-sub">View, promote, demote, and delete all user accounts.</div>
  </div>
</div>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr><th>Name</th><th>Username</th><th>Role</th><th>Designation</th><th>Department</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($allUsers as $u): ?>
      <tr>
        <td style="color:var(--paper);font-weight:500;"><?= e($u['full_name']) ?></td>
        <td>@<?= e($u['username']) ?></td>
        <td>
          <?php if (isAdmin($u)): ?>
          <span style="color:var(--blue);font-weight:600;">Admin</span>
          <?php elseif (isSafetyOfficer($u)): ?>
          <span style="color:var(--amber);font-weight:600;">Safety Officer</span>
          <?php else: ?>
          <span style="color:var(--paper-dim);">Employee</span>
          <?php endif; ?>
        </td>
        <td><?= e($u['designation']) ?></td>
        <td><?= e($u['department']) ?></td>
        <td>
          <?php if ($u['is_approved'] || $u['role'] !== 'employee'): ?>
          <span style="color:var(--green);font-family:var(--font-mono);font-size:11px;">APPROVED</span>
          <?php else: ?>
          <span style="color:var(--amber);font-family:var(--font-mono);font-size:11px;">PENDING</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if (!isAdmin($u)): ?>
          <div style="display:flex;gap:6px;">
            <form method="POST" action="admin_toggle_role.php?id=<?= $u['id'] ?>" onsubmit="return confirm('Change role for <?= e($u['full_name']) ?>?');">
              <?php if ($u['role'] === 'employee'): ?>
              <button type="submit" class="action-btn" title="Promote to Safety Officer">Promote</button>
              <?php else: ?>
              <button type="submit" class="action-btn" title="Demote to Employee">Demote</button>
              <?php endif; ?>
            </form>
            <form method="POST" action="admin_delete_user.php?id=<?= $u['id'] ?>" onsubmit="return confirm('Delete <?= e($u['full_name']) ?>? This cannot be undone.');">
              <button type="submit" class="action-btn danger">Delete</button>
            </form>
          </div>
          <?php else: ?>
          <span style="font-family:var(--font-mono);font-size:10px;color:var(--slate);">SUPERUSER</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
