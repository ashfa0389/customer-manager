<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$pdo  = db();
$year = (int)date('Y');

$customerCount = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$activePlans   = (int)$pdo->query('SELECT COUNT(*) FROM web_plans WHERE DATE_ADD(join_date, INTERVAL duration_months MONTH) >= CURDATE()')->fetchColumn();
$pending       = $pdo->query("SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS s FROM payments WHERE status = 'Pending'")->fetch();
$overdueCount  = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending' AND due_date IS NOT NULL AND due_date < CURDATE()")->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid' AND `year` = :y");
$stmt->execute(['y' => $year]);
$paidThisYear = (float)$stmt->fetchColumn();

$toCollect = $pdo->query(
    "SELECT p.amount, p.category, p.due_date, c.name AS customer,
            CASE WHEN p.due_date IS NOT NULL AND p.due_date < CURDATE() THEN 1 ELSE 0 END AS late
       FROM payments p
       JOIN customers c ON c.id = p.customer_id
      WHERE p.status = 'Pending'
      ORDER BY (p.due_date IS NULL), p.due_date ASC
      LIMIT 8"
)->fetchAll();

$endingSoon = $pdo->query(
    "SELECT c.name AS customer, DATE_ADD(w.join_date, INTERVAL w.duration_months MONTH) AS ends_on
       FROM web_plans w
       JOIN customers c ON c.id = w.customer_id
      WHERE DATE_ADD(w.join_date, INTERVAL w.duration_months MONTH)
            BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
      ORDER BY ends_on ASC
      LIMIT 8"
)->fetchAll();

$pendingCount = (int)$pending['c'];
if ($pendingCount === 0) {
    $summaryLine = 'No payments are waiting. Everything is up to date.';
} else {
    $summaryLine = $pendingCount . ($pendingCount === 1 ? ' payment is' : ' payments are') . ' waiting to be collected';
    $summaryLine .= $overdueCount > 0 ? ', ' . $overdueCount . ' overdue.' : '.';
}

$me        = current_user();
$firstName = explode(' ', trim($me['name']))[0];

$pageTitle = 'Dashboard';
$navActive = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<?php if (is_admin() && ENC_KEY === 'change-this-to-a-long-random-secret-text'): ?>
  <div class="flashes">
    <div class="flash warn">
      <span>Set your own secret text for <code>ENC_KEY</code> in <code>config.php</code> before saving real passwords. Changing it later makes saved passwords unreadable.</span>
    </div>
  </div>
<?php endif; ?>

<div class="page-head">
  <div>
    <h1><?= e(greeting()) ?>, <?= e($firstName) ?></h1>
    <p class="sub"><?= e($summaryLine) ?></p>
  </div>
</div>

<section class="summary" aria-label="Overview">
  <div>
    <div class="num"><?= $customerCount ?></div>
    <div class="lbl">Customers</div>
  </div>
  <div>
    <div class="num"><?= $activePlans ?></div>
    <div class="lbl">Active web plans</div>
  </div>
  <div>
    <div class="num"><?= e(money($pending['s'])) ?></div>
    <div class="lbl">Waiting to collect</div>
  </div>
  <div>
    <div class="num"><?= e(money($paidThisYear)) ?></div>
    <div class="lbl">Collected in <?= $year ?></div>
  </div>
</section>

<div class="split">
  <section>
    <div class="section-head">
      <h2>Payments to collect</h2>
      <a href="payments.php">See all payments</a>
    </div>
    <?php if (!$toCollect): ?>
      <div class="empty small"><p>Nothing is waiting. Pending payments will show up here.</p></div>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th scope="col">Customer</th><th scope="col">For</th><th scope="col">Due</th><th scope="col" class="num-col">Amount</th></tr>
          </thead>
          <tbody>
            <?php foreach ($toCollect as $r): ?>
              <tr>
                <td><strong class="strong"><?= e($r['customer']) ?></strong></td>
                <td><?= e($r['category']) ?></td>
                <td>
                  <?= e(fmt_date($r['due_date'])) ?>
                  <?php if ($r['late']): ?> <span class="badge bad">Overdue</span><?php endif; ?>
                </td>
                <td class="num-col"><?= e(money($r['amount'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section>
    <div class="section-head">
      <h2>Web plans ending soon</h2>
      <a href="web-plans.php">See all plans</a>
    </div>
    <?php if (!$endingSoon): ?>
      <div class="empty small"><p>No plans end in the next 30 days.</p></div>
    <?php else: ?>
      <ul class="plain-list">
        <?php foreach ($endingSoon as $r):
            $days = (int)floor((strtotime($r['ends_on']) - strtotime(date('Y-m-d'))) / 86400);
        ?>
          <li>
            <div>
              <strong class="strong"><?= e($r['customer']) ?></strong>
              <div class="muted"><?= e(fmt_date($r['ends_on'])) ?></div>
            </div>
            <?php if ($days < 0): ?>
              <span class="badge bad">Ended</span>
            <?php elseif ($days === 0): ?>
              <span class="badge warn">Ends today</span>
            <?php else: ?>
              <span class="badge warn"><?= $days === 1 ? 'Ends tomorrow' : 'In ' . $days . ' days' ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
