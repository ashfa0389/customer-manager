<?php
// Returns one saved (encrypted) password as JSON, only when the edit form asks for it.
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function reply($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (current_user() === null) {
    reply(['ok' => false, 'error' => 'Please log in again.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
    reply(['ok' => false, 'error' => 'Session expired. Please refresh the page.'], 400);
}
if (!(is_admin() || STAFF_CAN_VIEW_SECRETS)) {
    reply(['ok' => false, 'error' => 'Only the admin can view saved passwords.'], 403);
}

$m     = get_module(isset($_POST['m']) ? (string)$_POST['m'] : '');
$field = isset($_POST['field']) ? (string)$_POST['field'] : '';
$id    = (int)(isset($_POST['id']) ? $_POST['id'] : 0);

if ($m === null || $id < 1) {
    reply(['ok' => false, 'error' => 'Bad request.'], 400);
}

$allowed = false;
foreach ($m['fields'] as $f) {
    if ($f['name'] === $field && $f['type'] === 'secret') {
        $allowed = true;
    }
}
if (!$allowed) {
    reply(['ok' => false, 'error' => 'Bad request.'], 400);
}

$stmt = db()->prepare('SELECT `' . $field . '` FROM `' . $m['table'] . '` WHERE id = :row_id');
$stmt->execute(['row_id' => $id]);
$stored = $stmt->fetchColumn();

reply(['ok' => true, 'value' => decrypt_secret($stored === false ? '' : $stored)]);
