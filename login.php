<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

// First run: no users yet, so create the admin account.
try {
    $userCount = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (PDOException $ex) {
    exit('<h3>The database tables were not found.</h3><p>Import <code>database/database.sql</code> using phpMyAdmin, then reload this page.</p>');
}
if ($userCount === 0) {
    redirect('setup.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (isset($_SESSION['lock_until']) && $_SESSION['lock_until'] > time()) {
        $error = 'Too many attempts. Please wait a minute and try again.';
    } else {
        $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

        $stmt = db()->prepare('SELECT * FROM users WHERE username = :u AND (exit_date IS NULL OR exit_date >= CURDATE()) LIMIT 1');
        $stmt->execute(['u' => $username]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => (int)$row['id'],
                'name'     => $row['name'],
                'username' => $row['username'],
                'role'     => $row['role'],
            ];
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            redirect('dashboard.php');
        }

        $_SESSION['fails'] = (isset($_SESSION['fails']) ? $_SESSION['fails'] : 0) + 1;
        if ($_SESSION['fails'] >= 5) {
            $_SESSION['lock_until'] = time() + 60;
            $_SESSION['fails']      = 0;
        }
        $error = 'The user name or password is not correct.';
    }
}

$authTitle    = 'Log in';
$authHeadline = 'Your customers, domains and plans in one calm place.';
include __DIR__ . '/includes/auth_top.php';
?>
        <h2>Log in</h2>
        <p class="muted">Use the user name and password created for you.</p>

        <?php if ($error): ?><div class="flash error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?>

        <form method="post" action="login.php">
          <?= csrf_field() ?>
          <div class="field">
            <label for="username">User name</label>
            <input type="text" id="username" name="username" autocomplete="username" required autofocus>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn primary block">Log in</button>
        </form>
<?php include __DIR__ . '/includes/auth_bottom.php'; ?>
