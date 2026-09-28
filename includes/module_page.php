<?php
// One shared page for every list (customers, hosting, ...). The page file sets $slug first.
require_login();

$m = get_module($slug);
if ($m === null) {
    http_response_code(404);
    exit('Page not found.');
}
if (!empty($m['admin_only'])) {
    require_admin();
}

$self      = basename($_SERVER['PHP_SELF']);
$canReveal = is_admin() || STAFF_CAN_VIEW_SECRETS;
$canDelete = is_admin();

$yearFilter = null;
if (!empty($m['year_filter']) && isset($_GET['year']) && is_string($_GET['year']) && ctype_digit($_GET['year'])) {
    $yearFilter = (int)$_GET['year'];
}
$backTo = $self . ($yearFilter !== null ? '?year=' . $yearFilter : '');

// ----- handle add / edit / delete -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        flash('error', 'Your session expired. Please try again.');
        redirect($backTo);
    }
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save') {
        module_save($m);
    } elseif ($action === 'delete') {
        module_delete($m, $canDelete);
    }
    redirect($backTo);
}

// ----- load the rows -----
$years = [];
if (!empty($m['year_filter'])) {
    $yc    = $m['year_filter'];
    $years = db()->query('SELECT DISTINCT `' . $yc . '` AS y FROM `' . $m['table'] . '` WHERE `' . $yc . '` IS NOT NULL ORDER BY y DESC')->fetchAll(PDO::FETCH_COLUMN);
}

$select = ['t.*'];
foreach ($m['fields'] as $f) {
    if ($f['type'] === 'lookup') {
        $l        = $f['lookup'];
        $select[] = '(SELECT `' . $l['label'] . '` FROM `' . $l['table'] . '` WHERE id = t.`' . $f['name'] . '`) AS `' . $f['name'] . '__label`';
    }
}
if (!empty($m['extra_select'])) {
    $select[] = $m['extra_select'];
}

$sql    = 'SELECT ' . implode(', ', $select) . ' FROM `' . $m['table'] . '` t';
$params = [];
if ($yearFilter !== null) {
    $sql .= ' WHERE t.`' . $m['year_filter'] . '` = :yr';
    $params['yr'] = $yearFilter;
}
$sql .= ' ORDER BY ' . (isset($m['order']) ? $m['order'] : 't.id DESC') . ' LIMIT 1000';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$listFields = [];
foreach ($m['fields'] as $f) {
    if (!empty($f['list'])) {
        $listFields[] = $f;
    }
}
$extraCols = isset($m['extra_cols']) ? $m['extra_cols'] : [];

$sumTotal = null;
if (!empty($m['sum'])) {
    $sumTotal = 0.0;
    foreach ($rows as $r) {
        $sumTotal += (float)$r[$m['sum']];
    }
}

$deleteText = isset($m['delete_warning']) ? $m['delete_warning'] : 'Delete this record? This cannot be undone.';
$pageTitle  = $m['title'];
$navActive  = $slug;
$count      = count($rows);

include __DIR__ . '/header.php';
?>

<div class="page-head">
  <div>
    <h1><?= e($m['title']) ?></h1>
    <p class="sub">
      <?= $count ?> <?= $count === 1 ? 'record' : 'records' ?><?php if ($sumTotal !== null && $count > 0): ?>, total <?= e(money($sumTotal)) ?><?php endif; ?>
    </p>
  </div>

  <div class="head-actions">
    <?php if ($years): ?>
      <form method="get" action="<?= e($self) ?>">
        <label class="sr-only" for="yearFilter">Filter by year</label>
        <select name="year" id="yearFilter" data-autosubmit>
          <option value="">All years</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y ?>"<?= $yearFilter === (int)$y ? ' selected' : '' ?>><?= (int)$y ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    <?php endif; ?>

    <?php if ($count > 0): ?>
      <div class="search">
        <?= icon('search', 18) ?>
        <input type="search" id="tableSearch" placeholder="Search this list" aria-label="Search this list">
      </div>
    <?php endif; ?>

    <button type="button" class="btn primary" data-add><?= icon('plus', 18) ?> Add <?= e($m['singular']) ?></button>
  </div>
</div>

<?php if ($count === 0): ?>
  <div class="empty">
    <p><?= e($m['empty']) ?></p>
    <button type="button" class="btn primary" data-add><?= icon('plus', 18) ?> Add <?= e($m['singular']) ?></button>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr>
          <?php foreach ($listFields as $f): ?><th scope="col"><?= e($f['label']) ?></th><?php endforeach; ?>
          <?php foreach ($extraCols as $c): ?><th scope="col"><?= e($c['label']) ?></th><?php endforeach; ?>
          <th scope="col" class="actions"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($listFields as $f): ?><td><?= render_cell($f, $row) ?></td><?php endforeach; ?>
            <?php foreach ($extraCols as $c): ?><td><?= render_extra($c, $row) ?></td><?php endforeach; ?>
            <td class="actions">
              <button type="button" class="icon-btn" title="Edit" aria-label="Edit"
                      data-edit="<?= e(json_encode(row_edit_data($m, $row), JSON_UNESCAPED_UNICODE)) ?>"><?= icon('edit', 18) ?></button>
              <?php if ($canDelete): ?>
                <form method="post" action="<?= e($backTo) ?>" class="inline" data-confirm="<?= e($deleteText) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete"><?= icon('trash', 18) ?></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<dialog id="formDialog"
        data-title-add="Add <?= e($m['singular']) ?>"
        data-title-edit="Edit <?= e($m['singular']) ?>"
        data-can-reveal="<?= $canReveal ? '1' : '0' ?>">
  <form method="post" action="<?= e($backTo) ?>" id="recordForm" data-slug="<?= e($slug) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="0">

    <div class="dlg-head">
      <h2 id="dialogTitle">Add <?= e($m['singular']) ?></h2>
      <button type="button" class="icon-btn" data-close aria-label="Close"><?= icon('x', 20) ?></button>
    </div>

    <div class="dlg-body">
      <div class="form-grid">
        <?php foreach ($m['fields'] as $f) { echo form_field($f); } ?>
      </div>
    </div>

    <div class="dlg-foot">
      <button type="button" class="btn ghost" data-close>Cancel</button>
      <button type="submit" class="btn primary">Save</button>
    </div>
  </form>
</dialog>

<?php include __DIR__ . '/footer.php'; ?>
