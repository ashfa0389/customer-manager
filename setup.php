<?php
// Runs once: creates the first admin account. It locks itself as soon as a user exists.
require __DIR__ . '/includes/bootstrap.php';

try {
    $userCount = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (PDOException $ex) {
    exit('<h3>The database tables were not found.</h3><p>Import <code>database/database.sql</code> using phpMyAdmin, then reload this page.</p>');
}
if ($userCount > 0) {
    redirect('login.php');
}

$error = '';
$name  = '';
$user  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cut(trim(isset($_POST['name']) ? (string)$_POST['name'] : ''), 120);
    $user = cut(trim(isset($_POST['username']) ? (string)$_POST['username'] : ''), 60);
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($name === '' || $user === '') {
        $error = 'Please enter your name and a user name.';
    } elseif (strlen($pass) < 8) {
        $error = 'The password must be at least 8 characters.';
    } else {
        $stmt = db()->prepare("INSERT INTO users (name, join_date, role, username, password_hash) VALUES (:n, CURDATE(), 'admin', :u, :p)");
        $stmt->execute(['n' => $name, 'u' => $user, 'p' => password_hash($pass, PASSWORD_DEFAULT)]);
        flash('ok', 'Admin account created. You can log in now.');
        redirect('login.php');
    }
}

$authTitle    = 'Set up';
$authHeadline = 'Let us create your admin account.';
include __DIR__ . '/includes/auth_top.php';
?>
        <h2>Create the admin account</h2>
        <p class="muted">This is the account you will use to manage everything. You can add staff later.</p>

        <?php if ($error): ?><div class="flash error" role="alert"><span><?= e($error) ?></span></div><?php endif; ?>

        <form method="post" action="setup.php">
          <?= csrf_field() ?>
          <div class="field">
            <label for="name">Your name</label>
            <input type="text" id="name" name="name" value="<?= e($name) ?>" required autofocus>
          </div>
          <div class="field">
            <label for="username">User name</label>
            <input type="text" id="username" name="username" value="<?= e($user) ?>" autocomplete="username" required>
          </div>
          <div class="field">
            <label for="password">Password (at least 8 characters)</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
          </div>
          <button type="submit" class="btn primary block">Create admin account</button>
        </form>
<?php include __DIR__ . '/includes/auth_bottom.php'; ?>
