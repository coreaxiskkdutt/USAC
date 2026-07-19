<?php
require_once __DIR__ . '/helpers.php';
requireSafetyOfficer();
$db = getDB();

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'designation' => 'Operator', 'department' => 'HSE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['designation'] = $_POST['designation'] ?? 'Operator';
    $old['department'] = $_POST['department'] ?? 'HSE';
    $password = $_POST['password'] ?? '';

    if (!($old['full_name'] && $old['username'] && $old['email'] && $old['designation'] && $old['department'] && $password)) {
        $errors[] = 'All fields are required.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->bindValue(':u', $old['username'], SQLITE3_TEXT);
        if ($stmt->execute()->fetchArray()) $errors[] = 'Username already taken.';
    }
    if (empty($errors)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->bindValue(':e', $old['email'], SQLITE3_TEXT);
        if ($stmt->execute()->fetchArray()) $errors[] = 'Email already registered.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (full_name, username, email, password_hash, role, designation, department, is_approved) VALUES (:fn, :un, :em, :pw, :rl, :dg, :dp, 1)');
        $stmt->bindValue(':fn', $old['full_name'], SQLITE3_TEXT);
        $stmt->bindValue(':un', $old['username'], SQLITE3_TEXT);
        $stmt->bindValue(':em', $old['email'], SQLITE3_TEXT);
        $stmt->bindValue(':pw', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':rl', 'safety_officer', SQLITE3_TEXT);
        $stmt->bindValue(':dg', $old['designation'], SQLITE3_TEXT);
        $stmt->bindValue(':dp', $old['department'], SQLITE3_TEXT);
        $stmt->execute();
        flash("Safety Officer account for {$old['full_name']} created successfully.", 'success');
        header('Location: manage_approvals.php');
        exit;
    }
}

$pageTitle = 'Add Safety Officer';
?>
<?php require __DIR__ . '/includes/header.php'; ?>

<div class="topbar">
  <div>
    <div class="page-title">Add Safety Officer</div>
    <div class="page-sub">Create a Safety Officer account with full investigation and report management privileges.</div>
  </div>
  <a href="manage_approvals.php" class="btn-ghost">&larr; Back</a>
</div>

<div style="max-width:520px;">
  <div style="background:var(--bg-raised);border:1px solid var(--line-light);border-radius:var(--radius);padding:28px;">
    <?php foreach ($errors as $err): ?>
      <div class="flash flash-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="field">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" placeholder="e.g. Jane Smith" required>
      </div>
      <div class="row-2">
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" value="<?= e($old['username']) ?>" placeholder="e.g. jsmith" required minlength="3">
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?= e($old['email']) ?>" placeholder="jane@company.com" required>
        </div>
      </div>
      <div class="row-2">
        <div class="field">
          <label>Designation</label>
          <select name="designation" required>
            <?php foreach (DESIGNATIONS as $d): ?>
            <option value="<?= e($d) ?>" <?= $old['designation'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Department</label>
          <select name="department" required>
            <?php foreach (DEPARTMENTS as $d): ?>
            <option value="<?= e($d) ?>" <?= $old['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" required minlength="6">
      </div>
      <button type="submit" class="modal-submit">Create Safety Officer Account</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
