<?php
require_once __DIR__ . '/helpers.php';
requireLogin();
$user = currentUser();
$db = getDB();

$where = [];
$params = [];

if (!canEditReports($user)) {
    $where[] = 'r.user_id = :uid';
    $params[':uid'] = $user['id'];
}

if (!empty($_GET['report_type'])) {
    $where[] = 'r.report_type = :rt';
    $params[':rt'] = $_GET['report_type'];
}
if (!empty($_GET['severity'])) {
    $where[] = 'r.severity = :sev';
    $params[':sev'] = $_GET['severity'];
}
if (!empty($_GET['status'])) {
    $where[] = 'r.status = :st';
    $params[':st'] = $_GET['status'];
}
if (!empty($_GET['department'])) {
    $where[] = 'r.department = :dept';
    $params[':dept'] = $_GET['department'];
}
if (!empty($_GET['search'])) {
    $where[] = '(r.title LIKE :search OR r.description LIKE :search2 OR r.location LIKE :search3 OR r.reported_by LIKE :search4 OR r.report_number LIKE :search5)';
    $s = '%' . $_GET['search'] . '%';
    $params[':search'] = $s;
    $params[':search2'] = $s;
    $params[':search3'] = $s;
    $params[':search4'] = $s;
    $params[':search5'] = $s;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM reports r $whereSql");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v, SQLITE3_TEXT);
$total = $countStmt->execute()->fetchArray()[0];
$totalPages = max(1, ceil($total / $perPage));

$orderBy = canEditReports($user) ? 'r.created_at DESC' : 'r.created_at DESC';
$stmt = $db->prepare("SELECT r.* FROM reports r $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v, SQLITE3_TEXT);
$result = $stmt->execute();
$reports = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) $reports[] = $row;

$pageTitle = 'Reports';
$filterQuery = '';
if (!empty($_GET)) {
    $qp = $_GET;
    unset($qp['page']);
    $filterQuery = http_build_query($qp) . '&';
}
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title">Reports</div>
    <div class="page-sub">
      <?= canEditReports($user) ? 'All filed reports across the organization.' : 'Your filed reports. Status changes are handled by the safety team.' ?>
    </div>
  </div>
  <a href="new_report.php" class="btn-primary">&#43; New report</a>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;width:100%;">
    <select name="report_type" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-family:var(--font-body);font-size:12px;padding:8px 10px;border-radius:var(--radius);outline:none;">
      <option value="">All types</option>
      <option value="Unsafe Act" <?= ($_GET['report_type'] ?? '') === 'Unsafe Act' ? 'selected' : '' ?>>Unsafe Act</option>
      <option value="Unsafe Condition" <?= ($_GET['report_type'] ?? '') === 'Unsafe Condition' ? 'selected' : '' ?>>Unsafe Condition</option>
    </select>
    <select name="severity" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-family:var(--font-body);font-size:12px;padding:8px 10px;border-radius:var(--radius);outline:none;">
      <option value="">All severity</option>
      <?php foreach (SEVERITY_LEVELS as $s): ?>
      <option value="<?= e($s) ?>" <?= ($_GET['severity'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-family:var(--font-body);font-size:12px;padding:8px 10px;border-radius:var(--radius);outline:none;">
      <option value="">All status</option>
      <?php foreach (STATUS_OPTIONS as $s): ?>
      <option value="<?= e($s) ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="department" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-family:var(--font-body);font-size:12px;padding:8px 10px;border-radius:var(--radius);outline:none;">
      <option value="">All departments</option>
      <?php foreach (DEPARTMENTS as $d): ?>
      <option value="<?= e($d) ?>" <?= ($_GET['department'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="search" placeholder="Search..." value="<?= e($_GET['search'] ?? '') ?>" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-family:var(--font-body);font-size:12px;padding:8px 10px;border-radius:var(--radius);outline:none;flex:1;min-width:140px;">
    <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:12px;">Search</button>
  </form>
</div>

<div class="ledger">
  <?php if ($reports): ?>
    <?php foreach ($reports as $r): ?>
    <div class="tag">
      <div class="tag-spine <?= typeClass($r['report_type']) ?>"><?= e($r['report_type']) ?></div>
      <div class="tag-body">
        <div class="tag-toprow">
          <div>
            <div class="tag-id"><?= e($r['report_number']) ?> &mdash; filed <?= date('M d, Y', strtotime($r['date_observed'])) ?></div>
            <div class="tag-title"><?= e($r['title']) ?></div>
          </div>
          <div class="status-stamp <?= statusClass($r['status']) ?>"><?= e($r['status']) ?></div>
        </div>
        <div class="tag-desc"><?= e(mb_strimwidth($r['description'], 0, 120, '...')) ?></div>
        <div class="badge-row">
          <span class="sev-badge <?= sevClass($r['severity']) ?>">Severity: <?= e($r['severity']) ?></span>
        </div>
        <div class="tag-meta">
          <span><b>Location</b> &nbsp;<?= e($r['location']) ?></span>
          <span><b>Reported by</b> &nbsp;<?= e($r['reported_by']) ?></span>
          <span><b>Dept</b> &nbsp;<?= e($r['department']) ?></span>
        </div>
        <div class="tag-actions">
          <a href="view_report.php?id=<?= $r['id'] ?>" class="action-btn">View details</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;">
      <span style="font-family:var(--font-mono);font-size:11px;color:var(--slate-light);">
        Page <?= $page ?> of <?= $totalPages ?> (<?= $total ?> total)
      </span>
      <div style="display:flex;gap:6px;">
        <?php if ($page > 1): ?>
        <a href="?<?= $filterQuery ?>page=<?= $page - 1 ?>" class="btn-ghost">&laquo; Prev</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?<?= $filterQuery ?>page=<?= $page + 1 ?>" class="btn-ghost">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty-state">
      <b>No reports found</b>
      Try a different filter or log a new one.
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
