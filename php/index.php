<?php
require_once __DIR__ . '/helpers.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'All fields are required.';
    } else {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] === 'employee' && !$user['is_approved']) {
                $errors[] = 'Your account is pending approval by a Safety Officer.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                flash('Welcome back, ' . $user['full_name'] . '!', 'success');
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

$pageTitle = 'Log In';
$authTitle = 'Welcome Back';
$authSub = 'Sign in to the UA/UC Reporting System';
?>
<?php require __DIR__ . '/includes/auth_header.php'; ?>

    <?php foreach ($errors as $err): ?>
      <div class="flash flash-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" value="<?= e($username) ?>" placeholder="Enter your username" autocomplete="username" required>
      </div>

      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
      </div>

      <button type="submit" class="modal-submit">Log In</button>
    </form>

    <div class="auth-link">
      Don't have an account? <a href="register.php">Register here</a>
    </div>

<?php require __DIR__ . '/includes/auth_footer.php'; ?>
