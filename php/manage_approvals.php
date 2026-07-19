<?php
require_once __DIR__ . '/helpers.php';
requireSafetyOfficer();
$db = getDB();

$pending = $db->query("SELECT * FROM users WHERE role = 'employee' AND is_approved = 0 ORDER BY created_at DESC");
$pending_users = [];
while ($row = $pending->fetchArray(SQLITE3_ASSOC)) $pending_users[] = $row;

$approved = $db->query("SELECT * FROM users WHERE role = 'employee' AND is_approved = 1 ORDER BY full_name ASC");
$approved_users = [];
while ($row = $approved->fetchArray(SQLITE3_ASSOC)) $approved_users[] = $row;

$pageTitle = 'Manage Approvals';
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title">Manage Approvals</div>
    <div class="page-sub">Review and approve employee registrations.</div>
  </div>
  <a href="create_safety_officer.php" class="btn-ghost">+ Add Safety Officer</a>
</div>

<div class="tabs">
  <button class="tab active" onclick="showTab('pending',this)">Pending <?php if ($pending_users): ?><span style="color:var(--amber);margin-left:4px;">(<?= count($pending_users) ?>)</span><?php endif; ?></button>
  <button class="tab" onclick="showTab('approved',this)">Approved <?php if ($approved_users): ?><span style="color:var(--green);margin-left:4px;">(<?= count($approved_users) ?>)</span><?php endif; ?></button>
</div>

<div id="tab-pending">
  <?php if ($pending_users): ?>
  <div style="display:flex;flex-direction:column;gap:10px;">
    <?php foreach ($pending_users as $u): ?>
    <div class="user-card">
      <div class="user-avatar pending"><?= e(substr($u['full_name'], 0, 1)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= e($u['full_name']) ?></div>
        <div class="user-detail">@<?= e($u['username']) ?> &mdash; <?= e($u['designation']) ?> &mdash; <?= e($u['department']) ?></div>
        <div class="user-detail"><?= e($u['email']) ?> &mdash; Registered <?= date('M d, Y', strtotime($u['created_at'])) ?></div>
      </div>
      <form method="POST" action="approve_user.php?id=<?= $u['id'] ?>">
        <button type="submit" class="action-btn confirm" style="font-weight:600;">Approve</button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <b>No pending approvals</b>
    All employee registrations have been reviewed.
  </div>
  <?php endif; ?>
</div>

<div id="tab-approved" style="display:none;">
  <?php if ($approved_users): ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Name</th><th>Username</th><th>Designation</th><th>Department</th><th>Email</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($approved_users as $u): ?>
        <tr>
          <td style="color:var(--paper);font-weight:500;"><?= e($u['full_name']) ?></td>
          <td>@<?= e($u['username']) ?></td>
          <td><?= e($u['designation']) ?></td>
          <td><?= e($u['department']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td>
            <form method="POST" action="revoke_user.php?id=<?= $u['id'] ?>" style="display:inline;" onsubmit="return confirm('Revoke approval for <?= e($u['full_name']) ?>?');">
              <button type="submit" class="action-btn danger">Revoke</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <b>No approved employees yet</b>
  </div>
  <?php endif; ?>
</div>

<script>
function showTab(name, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-pending').style.display = name === 'pending' ? '' : 'none';
  document.getElementById('tab-approved').style.display = name === 'approved' ? '' : 'none';
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
