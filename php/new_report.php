<?php
require_once __DIR__ . '/helpers.php';
requireLogin();
$user = currentUser();
$db = getDB();

$errors = [];
$old = [
    'report_type' => 'Unsafe Act', 'title' => '', 'description' => '', 'category' => '',
    'severity' => 'Medium', 'department' => 'Operations', 'location' => '', 'date_observed' => date('Y-m-d'),
    'reported_by' => $user['full_name'] ?? '', 'contact_email' => '', 'immediate_action' => '', 'assigned_to' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($old) as $k) {
        if (isset($_POST[$k])) $old[$k] = trim($_POST[$k]);
    }
    $old['assigned_to'] = trim($_POST['assigned_to'] ?? '');

    if ($old['title'] === '') $errors[] = 'Title is required.';
    if ($old['description'] === '') $errors[] = 'Description is required.';
    if ($old['location'] === '') $errors[] = 'Location is required.';
    if ($old['reported_by'] === '') $errors[] = 'Reported by is required.';
    if ($old['assigned_to'] === '') $errors[] = 'Please specify who this report is assigned to.';

    $photoFilename = null;
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        if (allowedFile($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $photoFilename = bin2hex(random_bytes(16)) . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photoFilename);
        } else {
            $errors[] = 'Images and PDFs only!';
        }
    }

    if (empty($errors)) {
        $reportNumber = generateReportNumber();
        $stmt = $db->prepare('INSERT INTO reports (report_number, report_type, title, description, category, severity, status, department, location, date_observed, reported_by, contact_email, immediate_action, photo_filename, user_id, assigned_to) VALUES (:rn, :rt, :ti, :de, :ca, :se, :st, :dp, :lo, :do, :rb, :ce, :ia, :pf, :ui, :at)');
        $stmt->bindValue(':rn', $reportNumber, SQLITE3_TEXT);
        $stmt->bindValue(':rt', $old['report_type'], SQLITE3_TEXT);
        $stmt->bindValue(':ti', $old['title'], SQLITE3_TEXT);
        $stmt->bindValue(':de', $old['description'], SQLITE3_TEXT);
        $stmt->bindValue(':ca', $old['category'], SQLITE3_TEXT);
        $stmt->bindValue(':se', $old['severity'], SQLITE3_TEXT);
        $stmt->bindValue(':st', 'Open', SQLITE3_TEXT);
        $stmt->bindValue(':dp', $old['department'], SQLITE3_TEXT);
        $stmt->bindValue(':lo', $old['location'], SQLITE3_TEXT);
        $stmt->bindValue(':do', $old['date_observed'], SQLITE3_TEXT);
        $stmt->bindValue(':rb', $old['reported_by'], SQLITE3_TEXT);
        $stmt->bindValue(':ce', $old['contact_email'] ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':ia', $old['immediate_action'] ?: null, SQLITE3_TEXT);
        $stmt->bindValue(':pf', $photoFilename, SQLITE3_TEXT);
        $stmt->bindValue(':ui', $user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':at', $old['assigned_to'], SQLITE3_TEXT);
        $stmt->execute();
        $reportId = $db->lastInsertRowID();
        flash("Report $reportNumber created successfully.", 'success');
        header("Location: view_report.php?id=$reportId");
        exit;
    }
}

$pageTitle = 'New Report';
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title">New hazard report</div>
    <div class="page-sub">Report what you saw. Be specific about location &mdash; it's what gets this fixed fastest.</div>
  </div>
  <a href="reports.php" class="btn-ghost">&larr; Back</a>
</div>

<div style="max-width:620px;">
  <div style="background:var(--bg-raised);border:1px solid var(--line-light);border-radius:var(--radius);padding:28px;">
    <?php foreach ($errors as $err): ?>
      <div class="flash flash-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <div class="field">
        <label>Type</label>
        <div class="type-toggle" id="typeToggle">
          <div class="type-opt sel-act <?= $old['report_type'] !== 'Unsafe Condition' ? 'active' : '' ?>" data-value="Unsafe Act" onclick="selectType(this)">Unsafe Act</div>
          <div class="type-opt sel-cond <?= $old['report_type'] === 'Unsafe Condition' ? 'active' : '' ?>" data-value="Unsafe Condition" onclick="selectType(this)">Unsafe Condition</div>
        </div>
        <input type="hidden" name="report_type" id="reportTypeInput" value="<?= e($old['report_type']) ?>">
      </div>

      <div class="field">
        <label>Severity</label>
        <div class="sev-toggle" id="sevToggle">
          <?php foreach (SEVERITY_LEVELS as $s): ?>
          <div class="sev-opt <?= $old['severity'] === $s ? 'active' : '' ?>" data-sev="<?= e($s) ?>" onclick="selectSev(this)"><?= e($s) ?></div>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="severity" id="sevInput" value="<?= e($old['severity']) ?>">
      </div>

      <div class="field">
        <label for="title">Short title</label>
        <input type="text" name="title" id="title" placeholder="e.g. Missing guardrail on mezzanine, Bay 3" value="<?= e($old['title']) ?>" required>
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea name="description" id="description" placeholder="What did you observe? Include what could go wrong if it isn't addressed." required><?= e($old['description']) ?></textarea>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="category">Category</label>
          <select name="category" id="category" required>
            <?php foreach (CATEGORIES_ACT as $c): ?>
            <option value="<?= e($c) ?>"><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="department">Department</label>
          <select name="department" id="department" required>
            <?php foreach (DEPARTMENTS as $d): ?>
            <option value="<?= e($d) ?>" <?= $old['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="location">Location</label>
          <input type="text" name="location" id="location" placeholder="e.g. Warehouse B, Dock 4" value="<?= e($old['location']) ?>" required>
        </div>
        <div class="field">
          <label for="date_observed">Date observed</label>
          <input type="date" name="date_observed" id="date_observed" value="<?= e($old['date_observed']) ?>" required>
        </div>
      </div>

      <div class="row-2">
        <div class="field">
          <label for="reported_by">Your name</label>
          <input type="text" name="reported_by" id="reported_by" placeholder="e.g. R. Fernandes" value="<?= e($old['reported_by']) ?>" required>
        </div>
        <div class="field">
          <label for="contact_email">Email (optional)</label>
          <input type="email" name="contact_email" id="contact_email" placeholder="you@company.com" value="<?= e($old['contact_email']) ?>">
        </div>
      </div>

      <div class="field">
        <label for="assigned_to">Assign to</label>
        <input type="text" name="assigned_to" id="assigned_to" placeholder="e.g. Maintenance Team, J. Santos" value="<?= e($old['assigned_to']) ?>" required>
      </div>

      <div class="field">
        <label for="immediate_action">Immediate action taken (optional)</label>
        <textarea name="immediate_action" id="immediate_action" placeholder="What was done right away to mitigate the hazard?" style="min-height:60px;"><?= e($old['immediate_action']) ?></textarea>
      </div>

      <div class="field">
        <label for="photo">Photo evidence (optional)</label>
        <input type="file" name="photo" id="photo" accept=".png,.jpg,.jpeg,.gif,.pdf" style="padding:8px;">
      </div>

      <button type="submit" class="modal-submit">Submit report</button>
    </form>
  </div>
</div>

<script>
const categoriesAct = <?= json_encode(CATEGORIES_ACT) ?>;
const categoriesCond = <?= json_encode(CATEGORIES_CONDITION) ?>;

function selectType(el) {
  document.querySelectorAll('#typeToggle .type-opt').forEach(o => o.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('reportTypeInput').value = el.dataset.value;
  updateCategories();
}

function selectSev(el) {
  document.querySelectorAll('#sevToggle .sev-opt').forEach(o => o.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('sevInput').value = el.dataset.sev;
}

function updateCategories() {
  const type = document.getElementById('reportTypeInput').value;
  const cats = type === 'Unsafe Act' ? categoriesAct : categoriesCond;
  const sel = document.getElementById('category');
  sel.innerHTML = '';
  cats.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c; opt.textContent = c;
    sel.appendChild(opt);
  });
}
updateCategories();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
