<?php
require_once __DIR__ . '/helpers.php';
requireLogin();
$user = currentUser();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM reports WHERE id = :id');
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$report = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$report) {
    flash('Report not found.', 'danger');
    header('Location: reports.php');
    exit;
}

if (!canEditReports($user) && $report['user_id'] != $user['id']) {
    flash('Access denied.', 'danger');
    header('Location: reports.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update' && canEditReports($user)) {
        $status = $_POST['status'] ?? $report['status'];
        $severity = $_POST['severity'] ?? $report['severity'];
        $rootCause = trim($_POST['root_cause'] ?? '');
        $correctiveAction = trim($_POST['corrective_action'] ?? '');
        $assignedTo = trim($_POST['assigned_to'] ?? '');

        $stmt = $db->prepare('UPDATE reports SET status=:st, severity=:se, root_cause=:rc, corrective_action=:ca, assigned_to=:at, updated_at=datetime(\'now\') WHERE id=:id');
        $stmt->bindValue(':st', $status, SQLITE3_TEXT);
        $stmt->bindValue(':se', $severity, SQLITE3_TEXT);
        $stmt->bindValue(':rc', $rootCause ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':ca', $correctiveAction ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':at', $assignedTo ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        flash('Report ' . $report['report_number'] . ' updated successfully.', 'success');
        header("Location: view_report.php?id=$id");
        exit;
    }

    if ($action === 'delete' && canEditReports($user)) {
        if ($report['photo_filename']) {
            $path = UPLOAD_DIR . $report['photo_filename'];
            if (file_exists($path)) unlink($path);
        }
        $stmt = $db->prepare('DELETE FROM reports WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        flash('Report ' . $report['report_number'] . ' deleted.', 'warning');
        header('Location: reports.php');
        exit;
    }

    if ($action === 'reassign' && ($report['user_id'] == $user['id'] || canEditReports($user))) {
        $newAssignee = trim($_POST['assigned_to'] ?? '');
        $stmt = $db->prepare('UPDATE reports SET assigned_to=:at, reassignment_count=reassignment_count+1, updated_at=datetime(\'now\') WHERE id=:id');
        $stmt->bindValue(':at', $newAssignee ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        if ($newAssignee) {
            flash("Report reassigned to $newAssignee.", 'success');
        } else {
            flash('Assignment cleared.', 'info');
        }
        header("Location: view_report.php?id=$id");
        exit;
    }
}

$pageTitle = $report['report_number'];
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title"><?= e($report['report_number']) ?></div>
    <div class="page-sub"><?= e($report['title']) ?></div>
  </div>
  <a href="reports.php" class="btn-ghost">&larr; Back</a>
</div>

<div style="max-width:820px;">
  <div class="tag" style="margin-bottom:20px;">
    <div class="tag-spine <?= typeClass($report['report_type']) ?>"><?= e($report['report_type']) ?></div>
    <div class="tag-body">
      <div class="tag-toprow">
        <div>
          <div class="tag-id"><?= e($report['report_number']) ?> &mdash; filed <?= date('M d, Y', strtotime($report['date_observed'])) ?></div>
          <div class="tag-title"><?= e($report['title']) ?></div>
        </div>
        <div class="status-stamp <?= statusClass($report['status']) ?>"><?= e($report['status']) ?></div>
      </div>
      <div class="tag-desc"><?= e($report['description']) ?></div>
      <div class="badge-row">
        <span class="sev-badge <?= sevClass($report['severity']) ?>">Severity: <?= e($report['severity']) ?></span>
      </div>
      <div class="tag-meta">
        <span><b>Location</b> &nbsp;<?= e($report['location']) ?></span>
        <span><b>Reported by</b> &nbsp;<?= e($report['reported_by']) ?></span>
        <span><b>Dept</b> &nbsp;<?= e($report['department']) ?></span>
        <span><b>Category</b> &nbsp;<?= e($report['category']) ?></span>
        <span><b>Created</b> &nbsp;<?= date('M d, Y h:i A', strtotime($report['created_at'])) ?></span>
        <?php if ($report['assigned_to']): ?>
        <span><b>Assigned to</b> &nbsp;<?= e($report['assigned_to']) ?></span>
        <?php endif; ?>
        <?php if ($report['reassignment_count'] > 0): ?>
        <span><b>Reassignments</b> &nbsp;<?= $report['reassignment_count'] ?></span>
        <?php endif; ?>
      </div>

      <?php if ($report['immediate_action']): ?>
      <div style="margin-top:10px;padding:10px 14px;background:var(--bg-inset);border:1px solid var(--line);border-radius:var(--radius);">
        <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--slate-light);margin-bottom:4px;">Immediate Action Taken</div>
        <div style="font-size:13px;color:var(--paper-dim);white-space:pre-wrap;"><?= e($report['immediate_action']) ?></div>
      </div>
      <?php endif; ?>

      <?php if ($report['photo_filename']): ?>
      <div style="margin-top:10px;">
        <?php $lower = strtolower($report['photo_filename']); ?>
        <?php if (preg_match('/\.(png|jpg|jpeg|gif)$/', $lower)): ?>
        <img src="uploads/<?= e($report['photo_filename']) ?>" style="max-height:300px;border-radius:var(--radius);border:1px solid var(--line);">
        <?php else: ?>
        <a href="uploads/<?= e($report['photo_filename']) ?>" class="btn-ghost">View attachment</a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($report['root_cause'] || $report['corrective_action']): ?>
      <div style="margin-top:12px;padding:12px 14px;background:var(--bg-inset);border:1px solid var(--line);border-radius:var(--radius);">
        <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--slate-light);margin-bottom:8px;">Investigation</div>
        <?php if ($report['root_cause']): ?>
        <div style="margin-bottom:8px;">
          <div style="font-family:var(--font-mono);font-size:10px;color:var(--slate);margin-bottom:2px;">ROOT CAUSE</div>
          <div style="font-size:13px;color:var(--paper-dim);white-space:pre-wrap;"><?= e($report['root_cause']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($report['corrective_action']): ?>
        <div>
          <div style="font-family:var(--font-mono);font-size:10px;color:var(--slate);margin-bottom:2px;">CORRECTIVE ACTION</div>
          <div style="font-size:13px;color:var(--paper-dim);white-space:pre-wrap;"><?= e($report['corrective_action']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($report['assigned_to']): ?>
        <div style="margin-top:8px;">
          <span style="font-family:var(--font-mono);font-size:10px;color:var(--slate);">ASSIGNED TO</span>
          <span style="font-size:13px;color:var(--paper);margin-left:6px;"><?= e($report['assigned_to']) ?></span>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($report['user_id'] == $user['id']): ?>
      <div style="margin-top:12px;padding:12px 14px;background:var(--bg-inset);border:1px solid var(--line);border-radius:var(--radius);">
        <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--slate-light);margin-bottom:8px;">Reassign Report</div>
        <form method="POST" style="display:flex;gap:8px;align-items:flex-end;">
          <input type="hidden" name="action" value="reassign">
          <div style="flex:1;">
            <input type="text" name="assigned_to" value="<?= e($report['assigned_to'] ?? '') ?>" placeholder="e.g. Maintenance Team, J. Santos" style="background:var(--bg-raised);border:1px solid var(--line-light);color:var(--paper);font-size:13px;padding:8px 10px;border-radius:var(--radius);width:100%;">
          </div>
          <button type="submit" class="action-btn confirm" style="padding:8px 14px;">Assign</button>
        </form>
        <?php if ($report['reassignment_count'] > 0): ?>
        <div style="font-family:var(--font-mono);font-size:10px;color:var(--slate);margin-top:6px;">Reassigned <?= $report['reassignment_count'] ?> time<?= $report['reassignment_count'] != 1 ? 's' : '' ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if (canEditReports($user)): ?>
      <div class="tag-actions" style="margin-top:12px;">
        <form method="POST" style="width:100%;">
          <input type="hidden" name="action" value="update">
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <div style="flex:1;min-width:120px;">
              <label style="display:block;font-family:var(--font-mono);font-size:10px;letter-spacing:0.08em;text-transform:uppercase;color:var(--slate-light);margin-bottom:4px;">Status</label>
              <select name="status" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-size:12px;padding:7px 10px;border-radius:var(--radius);width:100%;">
                <?php foreach (STATUS_OPTIONS as $s): ?>
                <option value="<?= e($s) ?>" <?= $report['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="flex:1;min-width:120px;">
              <label style="display:block;font-family:var(--font-mono);font-size:10px;letter-spacing:0.08em;text-transform:uppercase;color:var(--slate-light);margin-bottom:4px;">Severity</label>
              <select name="severity" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-size:12px;padding:7px 10px;border-radius:var(--radius);width:100%;">
                <?php foreach (SEVERITY_LEVELS as $s): ?>
                <option value="<?= e($s) ?>" <?= $report['severity'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="flex:1;min-width:120px;">
              <label style="display:block;font-family:var(--font-mono);font-size:10px;letter-spacing:0.08em;text-transform:uppercase;color:var(--slate-light);margin-bottom:4px;">Override Assignment</label>
              <input type="text" name="assigned_to" value="<?= e($report['assigned_to'] ?? '') ?>" placeholder="Reassign to..." style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-size:12px;padding:7px 10px;border-radius:var(--radius);width:100%;">
            </div>
            <button type="submit" class="action-btn confirm" style="padding:7px 14px;font-weight:600;">Update</button>
          </div>
          <div style="display:flex;gap:8px;margin-top:8px;">
            <div style="flex:1;">
              <label style="display:block;font-family:var(--font-mono);font-size:10px;letter-spacing:0.08em;text-transform:uppercase;color:var(--slate-light);margin-bottom:4px;">Root Cause</label>
              <textarea name="root_cause" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-size:12px;padding:7px 10px;border-radius:var(--radius);width:100%;min-height:50px;" placeholder="Root cause analysis..."><?= e($report['root_cause'] ?? '') ?></textarea>
            </div>
            <div style="flex:1;">
              <label style="display:block;font-family:var(--font-mono);font-size:10px;letter-spacing:0.08em;text-transform:uppercase;color:var(--slate-light);margin-bottom:4px;">Corrective Action</label>
              <textarea name="corrective_action" style="background:var(--bg-inset);border:1px solid var(--line-light);color:var(--paper);font-size:12px;padding:7px 10px;border-radius:var(--radius);width:100%;min-height:50px;" placeholder="Corrective measures..."><?= e($report['corrective_action'] ?? '') ?></textarea>
            </div>
          </div>
        </form>
      </div>

      <div style="margin-top:14px;padding-top:12px;border-top:1px dashed var(--line);">
        <form method="POST" onsubmit="return confirm('Delete this report? This cannot be undone.');">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="action-btn danger">Delete report</button>
        </form>
      </div>
      <?php elseif (!canEditReports($user)): ?>
      <div class="locked-note" style="margin-top:10px;">Status is managed by the safety team.</div>
      <?php endif; ?>
    </div>
  </div>

  <div style="padding:14px 18px;background:var(--bg-raised);border:1px solid var(--line);border-radius:var(--radius);">
    <div style="font-family:var(--font-mono);font-size:10px;letter-spacing:0.1em;text-transform:uppercase;color:var(--slate-light);margin-bottom:8px;">Reporter Information</div>
    <div class="detail-grid">
      <div class="detail-item">
        <label>Name</label>
        <span><?= e($report['reported_by']) ?></span>
      </div>
      <?php if ($report['contact_email']): ?>
      <div class="detail-item">
        <label>Email</label>
        <span><a href="mailto:<?= e($report['contact_email']) ?>"><?= e($report['contact_email']) ?></a></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
