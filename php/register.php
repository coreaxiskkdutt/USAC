<?php
require_once __DIR__ . '/helpers.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'designation' => 'Operator', 'department' => 'Operations'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['designation'] = $_POST['designation'] ?? 'Operator';
    $old['department'] = $_POST['department'] ?? 'Operations';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Full Name is required.';
    if (strlen($old['username']) < 3) $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password_confirm) $errors[] = 'Passwords must match.';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :u');
        $stmt->bindValue(':u', $old['username'], SQLITE3_TEXT);
        if ($stmt->execute()->fetchArray()) $errors[] = 'Username already taken.';

        $stmt = $db->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->bindValue(':e', $old['email'], SQLITE3_TEXT);
        if ($stmt->execute()->fetchArray()) $errors[] = 'Email already registered.';
    }

    if (empty($errors)) {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (full_name, username, email, password_hash, role, designation, department, is_approved) VALUES (:fn, :un, :em, :pw, :rl, :dg, :dp, 0)');
        $stmt->bindValue(':fn', $old['full_name'], SQLITE3_TEXT);
        $stmt->bindValue(':un', $old['username'], SQLITE3_TEXT);
        $stmt->bindValue(':em', $old['email'], SQLITE3_TEXT);
        $stmt->bindValue(':pw', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':rl', 'employee', SQLITE3_TEXT);
        $stmt->bindValue(':dg', $old['designation'], SQLITE3_TEXT);
        $stmt->bindValue(':dp', $old['department'], SQLITE3_TEXT);
        $stmt->execute();
        flash('Registration submitted. A Safety Officer will approve your account shortly.', 'info');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Register';
$authTitle = 'Create Account';
$authSub = 'Register for the UA/UC Reporting System';
?>
<?php require __DIR__ . '/includes/auth_header.php'; ?>

    <?php foreach ($errors as $err): ?>
      <div class="flash flash-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="field">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" placeholder="e.g. Juan Dela Cruz" required>
      </div>

      <div class="row-2">
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" value="<?= e($old['username']) ?>" placeholder="e.g. jdelacruz" required>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?= e($old['email']) ?>" placeholder="you@company.com" required>
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
        <input type="password" name="password" placeholder="Min. 6 characters" autocomplete="new-password" required>
      </div>

      <div class="field">
        <label>Confirm Password</label>
        <input type="password" name="password_confirm" placeholder="Re-enter password" autocomplete="new-password" required>
      </div>

      <div class="locked-note" style="margin-bottom:14px;">
        Your account will be reviewed and approved by a Safety Officer before you can log in.
      </div>

      <button type="submit" class="modal-submit">Submit for Approval</button>
    </form>

    <div class="auth-link">
      Already have an account? <a href="index.php">Log in here</a>
    </div>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
