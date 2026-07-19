<?php
require_once __DIR__ . '/helpers.php';
requireLogin();
$user = currentUser();
$db = getDB();

$total_reports = $db->querySingle('SELECT COUNT(*) FROM reports');
$unsafe_acts = $db->querySingle("SELECT COUNT(*) FROM reports WHERE report_type = 'Unsafe Act'");
$unsafe_conditions = $db->querySingle("SELECT COUNT(*) FROM reports WHERE report_type = 'Unsafe Condition'");
$open_reports = $db->querySingle("SELECT COUNT(*) FROM reports WHERE status IN ('Open','Under Investigation','Corrective Action In Progress')");
$closed_reports = $db->querySingle("SELECT COUNT(*) FROM reports WHERE status = 'Closed'");
$critical_reports = $db->querySingle("SELECT COUNT(*) FROM reports WHERE severity = 'Critical' AND status IN ('Open','Under Investigation','Corrective Action In Progress')");

$recent = $db->query('SELECT * FROM reports ORDER BY created_at DESC LIMIT 5');
$recent_reports = [];
while ($row = $recent->fetchArray(SQLITE3_ASSOC)) $recent_reports[] = $row;

$pageTitle = 'Dashboard';
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title">Unsafe Acts &amp; Unsafe Conditions</div>
    <div class="page-sub">Report and track hazards before they become incidents. Every entry below is a live tag until it's closed out.</div>
  </div>
  <a href="new_report.php" class="btn-primary">&#43; New report</a>
</div>

<div class="stat-strip">
  <div class="stat"><div class="stat-num"><?= $total_reports ?></div><div class="stat-label">Total logged</div></div>
  <div class="stat"><div class="stat-num red"><?= $open_reports ?></div><div class="stat-label">Open</div></div>
  <div class="stat"><div class="stat-num amber"><?= $unsafe_acts ?></div><div class="stat-label">Unsafe Acts</div></div>
  <div class="stat"><div class="stat-num green"><?= $closed_reports ?></div><div class="stat-label">Closed</div></div>
</div>

<div class="ledger">
  <?php if ($recent_reports): ?>
    <?php foreach ($recent_reports as $r): ?>
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
        <div class="tag-desc"><?= e(mb_strimwidth($r['description'], 0, 160, '...')) ?></div>
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
    <div style="text-align:center;margin-top:10px;">
      <a href="reports.php" class="btn-ghost">View all reports &rarr;</a>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <b>No reports yet</b>
      Log your first hazard to get the register started.
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
