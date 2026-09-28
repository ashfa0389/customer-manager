<?php
// Shared add / edit / delete logic and form + table rendering used by every list page.

function lookup_options($field)
{
    static $cache = [];
    $l   = $field['lookup'];
    $key = $l['table'] . '.' . $l['label'];
    if (!isset($cache[$key])) {
        $rows = db()->query('SELECT id, `' . $l['label'] . '` AS label FROM `' . $l['table'] . '` ORDER BY `' . $l['label'] . '`')->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['label'];
        }
        $cache[$key] = $out;
    }
    return $cache[$key];
}

// ---------- saving ----------

function module_save($m)
{
    $pdo    = db();
    $id     = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    $me     = current_user();
    $values = [];
    $errors = [];

    foreach ($m['fields'] as $f) {
        $n     = $f['name'];
        $type  = $f['type'];
        $label = $f['label'];

        // An admin cannot change their own role (so there is always one admin left).
        if ($m['table'] === 'users' && $n === 'role' && $id === (int)$me['id']) {
            continue;
        }

        $raw = isset($_POST[$n]) ? $_POST[$n] : '';
        $raw = is_string($raw) ? trim($raw) : '';

        if ($type === 'checkbox') {
            $values[$n] = isset($_POST[$n]) ? 1 : 0;
            continue;
        }

        if ($type === 'secret') {
            if ($raw !== '') {
                $values[$n] = encrypt_secret(cut($raw, 255));
            } elseif ($id === 0) {
                $values[$n] = null;
            }
            continue;   // blank while editing = keep the saved value
        }

        if ($type === 'password') {
            if ($raw === '') {
                if ($id === 0) {
                    $errors[] = $label . ' is required.';
                }
            } elseif (strlen($raw) < 6) {
                $errors[] = $label . ' must be at least 6 characters.';
            } else {
                $values[$n] = password_hash($raw, PASSWORD_DEFAULT);
            }
            continue;
        }

        if (!empty($f['required']) && $raw === '') {
            $errors[] = $label . ' is required.';
            continue;
        }
        if ($raw === '') {
            $values[$n] = null;
            continue;
        }

        switch ($type) {
            case 'number':
            case 'year':
                if (!preg_match('/^\d{1,6}$/', $raw)) {
                    $errors[] = $label . ' must be a whole number.';
                    continue 2;
                }
                $values[$n] = (int)$raw;
                break;

            case 'money':
                if (!is_numeric($raw) || (float)$raw < 0) {
                    $errors[] = $label . ' must be a valid amount.';
                    continue 2;
                }
                $values[$n] = round((float)$raw, 2);
                break;

            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $raw);
                if (!$d || $d->format('Y-m-d') !== $raw) {
                    $errors[] = $label . ' must be a valid date.';
                    continue 2;
                }
                $values[$n] = $raw;
                break;

            case 'select':
                if (!isset($f['options'][$raw])) {
                    $errors[] = $label . ' has an invalid choice.';
                    continue 2;
                }
                $values[$n] = $raw;
                break;

            case 'lookup':
                if (!ctype_digit($raw) || !isset(lookup_options($f)[(int)$raw])) {
                    $errors[] = $label . ' has an invalid choice.';
                    continue 2;
                }
                $values[$n] = (int)$raw;
                break;

            case 'textarea':
                $values[$n] = cut($raw, 2000);
                break;

            default:
                $values[$n] = cut($raw, 255);
        }
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
        return;
    }
    if (!$values) {
        return;
    }

    try {
        if ($id > 0) {
            $sets = [];
            foreach ($values as $col => $v) {
                $sets[] = '`' . $col . '` = :' . $col;
            }
            $stmt   = $pdo->prepare('UPDATE `' . $m['table'] . '` SET ' . implode(', ', $sets) . ' WHERE id = :row_id');
            $params = $values;
            $params['row_id'] = $id;
            $stmt->execute($params);
            flash('ok', 'Changes saved.');
        } else {
            $cols = array_keys($values);
            $stmt = $pdo->prepare('INSERT INTO `' . $m['table'] . '` (`' . implode('`, `', $cols) . '`) VALUES (:' . implode(', :', $cols) . ')');
            $stmt->execute($values);
            flash('ok', ucfirst($m['singular']) . ' added.');
        }
    } catch (PDOException $ex) {
        if ($ex->getCode() === '23000') {
            flash('error', 'That value is already in use (for example the user name), or the customer no longer exists.');
        } else {
            flash('error', 'Could not save. Please check the values and try again.');
        }
    }
}

function module_delete($m, $allowed)
{
    if (!$allowed) {
        flash('error', 'Only the admin can delete records.');
        return;
    }
    $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
    if ($id < 1) {
        return;
    }
    if (!empty($m['no_delete_self']) && $id === (int)current_user()['id']) {
        flash('error', 'You cannot delete your own account.');
        return;
    }
    try {
        $stmt = db()->prepare('DELETE FROM `' . $m['table'] . '` WHERE id = :row_id');
        $stmt->execute(['row_id' => $id]);
        flash('ok', ucfirst($m['singular']) . ' deleted.');
    } catch (PDOException $ex) {
        flash('error', 'Could not delete this record.');
    }
}

// ---------- table cells ----------

function render_cell($f, $row)
{
    $n    = $f['name'];
    $type = $f['type'];
    $v    = isset($row[$n]) ? $row[$n] : null;
    $dash = '<span class="muted">-</span>';

    switch ($type) {
        case 'url':
            if ($v === null || $v === '') {
                return $dash;
            }
            $href  = preg_match('~^https?://~i', $v) ? $v : 'https://' . $v;
            $label = rtrim(preg_replace('~^https?://~i', '', $v), '/');
            return '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer">' . e($label) . '</a>';

        case 'money':
            return $v === null ? $dash : e(money($v));

        case 'date':
            return $v === null ? $dash : e(fmt_date($v));

        case 'checkbox':
            return $v ? '<span class="badge ok">Yes</span>' : '<span class="badge muted">No</span>';

        case 'lookup':
            $lab = isset($row[$n . '__label']) ? $row[$n . '__label'] : '';
            return $lab === '' ? $dash : '<strong class="strong">' . e($lab) . '</strong>';

        case 'select':
            $lab = isset($f['options'][$v]) ? $f['options'][$v] : $v;
            if (isset($f['badge'][$v])) {
                if ($n === 'status' && !empty($row['is_overdue'])) {
                    return '<span class="badge bad">Overdue</span>';
                }
                return '<span class="badge ' . e($f['badge'][$v]) . '">' . e($lab) . '</span>';
            }
            return e($lab);

        case 'textarea':
            return $v === null || $v === '' ? $dash : e(cut($v, 60));

        default:
            if ($v === null || $v === '') {
                return $dash;
            }
            if ($n === 'name') {
                return '<strong class="strong">' . e($v) . '</strong>';
            }
            return e($v);
    }
}

function render_extra($col, $row)
{
    $v = isset($row[$col['key']]) ? $row[$col['key']] : null;
    if (!$v) {
        return '<span class="muted">-</span>';
    }
    if ($col['type'] === 'end_date') {
        $days  = (int)floor((strtotime($v) - strtotime(date('Y-m-d'))) / 86400);
        $badge = '';
        if ($days < 0) {
            $badge = ' <span class="badge bad">Ended</span>';
        } elseif ($days === 0) {
            $badge = ' <span class="badge warn">Ends today</span>';
        } elseif ($days <= 30) {
            $badge = ' <span class="badge warn">' . ($days === 1 ? 'Ends tomorrow' : 'Ends in ' . $days . ' days') . '</span>';
        }
        return e(fmt_date($v)) . $badge;
    }
    return e($v);
}

// Values the edit form needs for one row (never includes saved passwords).
function row_edit_data($m, $row)
{
    $out = ['id' => (int)$row['id']];
    foreach ($m['fields'] as $f) {
        $n = $f['name'];
        if ($f['type'] === 'password') {
            continue;
        }
        if ($f['type'] === 'secret') {
            $out['__has_' . $n] = isset($row[$n]) && $row[$n] !== null && $row[$n] !== '';
            continue;
        }
        $out[$n] = isset($row[$n]) ? $row[$n] : '';
    }
    return $out;
}

// ---------- form fields ----------

function select_html($name, $id, $options, $selected, $required, $placeholder)
{
    $h = '<select name="' . e($name) . '" id="' . e($id) . '"' . ($required ? ' required' : '') . '>';
    if ($selected === '' || $selected === null) {
        $h .= '<option value="">' . e($placeholder) . '</option>';
    }
    foreach ($options as $val => $lab) {
        $sel = ((string)$val === (string)$selected) ? ' selected' : '';
        $h  .= '<option value="' . e($val) . '"' . $sel . '>' . e($lab) . '</option>';
    }
    return $h . '</select>';
}

function form_field($f)
{
    $name  = $f['name'];
    $id    = 'f_' . $name;
    $type  = $f['type'];
    $req   = !empty($f['required']) ? ' required' : '';
    $def   = isset($f['default']) ? $f['default'] : '';
    $wide  = in_array($type, ['textarea', 'checkbox'], true) ? ' wide' : '';
    $n     = e($name);
    $i     = e($id);

    $h = '<div class="field' . $wide . '">';

    if ($type === 'checkbox') {
        $checked = !empty($def) ? ' checked' : '';
        return $h . '<label class="check"><input type="checkbox" name="' . $n . '" id="' . $i . '" value="1"' . $checked . '> ' . e($f['label']) . '</label></div>';
    }

    $h .= '<label for="' . $i . '">' . e($f['label']) . (!empty($f['required']) ? ' <span class="req" aria-hidden="true">*</span>' : '') . '</label>';

    switch ($type) {
        case 'textarea':
            $h .= '<textarea name="' . $n . '" id="' . $i . '" rows="3" maxlength="2000"></textarea>';
            break;

        case 'select':
            $h .= select_html($name, $id, $f['options'], $def, !empty($f['required']), 'Choose...');
            break;

        case 'lookup':
            $h .= select_html($name, $id, lookup_options($f), $def, !empty($f['required']), 'Choose...');
            break;

        case 'secret':
            $h .= '<div class="secret-wrap"><input type="password" name="' . $n . '" id="' . $i . '" autocomplete="new-password" maxlength="255" data-secret>'
                . '<button type="button" class="icon-btn" data-toggle-secret title="Show or hide" aria-label="Show or hide">' . icon('eye', 18) . '</button></div>'
                . '<div class="hint" data-secret-hint hidden>Saved. Leave blank to keep it. '
                . '<button type="button" class="link-btn" data-reveal="' . $n . '" hidden>Show saved value</button></div>';
            break;

        case 'password':
            $h .= '<div class="secret-wrap"><input type="password" name="' . $n . '" id="' . $i . '" autocomplete="new-password" minlength="6" maxlength="100" data-secret data-pw>'
                . '<button type="button" class="icon-btn" data-toggle-secret title="Show or hide" aria-label="Show or hide">' . icon('eye', 18) . '</button></div>'
                . '<div class="hint" data-secret-hint hidden>Leave blank to keep the current password.</div>';
            break;

        case 'number':
            $h .= '<input type="number" min="0" step="1" name="' . $n . '" id="' . $i . '" value="' . e($def) . '"' . $req . '>';
            break;

        case 'year':
            $h .= '<input type="number" min="1990" max="2100" step="1" name="' . $n . '" id="' . $i . '" value="' . e($def) . '"' . $req . '>';
            break;

        case 'money':
            $h .= '<input type="number" min="0" step="0.01" name="' . $n . '" id="' . $i . '" value="' . e($def) . '"' . $req . '>';
            break;

        case 'date':
            $h .= '<input type="date" name="' . $n . '" id="' . $i . '" value="' . e($def) . '"' . $req . '>';
            break;

        case 'url':
            $h .= '<input type="text" inputmode="url" name="' . $n . '" id="' . $i . '" maxlength="255" value="' . e($def) . '"' . $req . '>';
            break;

        default:
            $h .= '<input type="text" name="' . $n . '" id="' . $i . '" maxlength="255" value="' . e($def) . '"' . $req . '>';
    }

    return $h . '</div>';
}
