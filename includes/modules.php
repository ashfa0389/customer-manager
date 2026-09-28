<?php
// Describes every list page (customers, hosting, ...): its table and its form fields.
// To add or rename a field, change it here (and in database/database.sql).
//
// Field types: text, url, number, year, money, date, select, lookup, checkbox, textarea,
//              secret (saved encrypted), password (saved as a hash)

function modules()
{
    $thisYear = (int)date('Y');

    return [

        'staff' => [
            'title'          => 'Staff',
            'singular'       => 'staff member',
            'table'          => 'users',
            'admin_only'     => true,
            'no_delete_self' => true,
            'order'          => 't.name ASC',
            'empty'          => 'No staff yet. Add a staff member so they can log in and help manage customers.',
            'fields'         => [
                ['name' => 'name',      'label' => 'Name',      'type' => 'text', 'required' => true, 'list' => true],
                ['name' => 'year',      'label' => 'Year',      'type' => 'year', 'list' => true],
                ['name' => 'join_date', 'label' => 'Join date', 'type' => 'date', 'list' => true],
                ['name' => 'exit_date', 'label' => 'Exit date', 'type' => 'date', 'list' => true],
                ['name' => 'role',      'label' => 'Role',      'type' => 'select', 'required' => true, 'list' => true,
                 'options' => ['staff' => 'Staff', 'admin' => 'Admin'], 'default' => 'staff',
                 'badge' => ['admin' => 'info', 'staff' => 'muted']],
                ['name' => 'username',      'label' => 'User name', 'type' => 'text', 'required' => true, 'list' => true],
                ['name' => 'password_hash', 'label' => 'Password',  'type' => 'password'],
            ],
        ],

        'customers' => [
            'title'          => 'Customers',
            'singular'       => 'customer',
            'table'          => 'customers',
            'order'          => 't.name ASC',
            'delete_warning' => 'Delete this customer and all their hosting, web plans, backups and payments?',
            'empty'          => 'No customers yet. Add your first customer to start tracking their website, domain and hosting.',
            'fields'         => [
                ['name' => 'name',             'label' => 'Customer name',       'type' => 'text', 'required' => true, 'list' => true],
                ['name' => 'website',          'label' => 'Website',             'type' => 'url',  'list' => true],
                ['name' => 'domain_name',      'label' => 'Domain',              'type' => 'text', 'list' => true],
                ['name' => 'domain_login_url', 'label' => 'Domain login link',   'type' => 'url'],
                ['name' => 'domain_username',  'label' => 'Domain user name',    'type' => 'text'],
                ['name' => 'domain_password',  'label' => 'Domain password',     'type' => 'secret'],
                ['name' => 'domain_auth_id',   'label' => 'Authentication ID',   'type' => 'secret'],
                ['name' => 'maintained_by_me', 'label' => 'Maintenance by me',   'type' => 'checkbox', 'list' => true, 'default' => 1],
                ['name' => 'notes',            'label' => 'Notes',               'type' => 'textarea'],
            ],
        ],

        'hosting' => [
            'title'    => 'Hosting',
            'singular' => 'hosting account',
            'table'    => 'hosting',
            'order'    => 't.id DESC',
            'empty'    => 'No hosting accounts yet. Add one to keep each customer\'s hosting login in one place.',
            'fields'   => [
                ['name' => 'customer_id',      'label' => 'Customer',          'type' => 'lookup', 'required' => true, 'list' => true,
                 'lookup' => ['table' => 'customers', 'label' => 'name']],
                ['name' => 'provider',         'label' => 'Hosting provider',  'type' => 'text', 'list' => true],
                ['name' => 'login_url',        'label' => 'Login URL',         'type' => 'url',  'list' => true],
                ['name' => 'username',         'label' => 'User name',         'type' => 'text', 'list' => true],
                ['name' => 'password',         'label' => 'Password',          'type' => 'secret'],
                ['name' => 'maintained_by_me', 'label' => 'Maintenance by me', 'type' => 'checkbox', 'list' => true, 'default' => 1],
                ['name' => 'notes',            'label' => 'Notes',             'type' => 'textarea'],
            ],
        ],

        'web_plans' => [
            'title'        => 'Web plans',
            'singular'     => 'web plan',
            'table'        => 'web_plans',
            'order'        => 't.join_date DESC',
            'sum'          => 'amount',
            'extra_select' => 'DATE_ADD(t.join_date, INTERVAL t.duration_months MONTH) AS ends_on',
            'extra_cols'   => [['key' => 'ends_on', 'label' => 'Ends on', 'type' => 'end_date']],
            'empty'        => 'No web plans yet. Add a plan to track when each customer joined, what they pay and when it ends.',
            'fields'       => [
                ['name' => 'customer_id',     'label' => 'Customer',            'type' => 'lookup', 'required' => true, 'list' => true,
                 'lookup' => ['table' => 'customers', 'label' => 'name']],
                ['name' => 'join_date',       'label' => 'Join date',           'type' => 'date',   'required' => true, 'list' => true],
                ['name' => 'amount',          'label' => 'Amount',              'type' => 'money',  'required' => true, 'list' => true],
                ['name' => 'duration_months', 'label' => 'Duration (months)',   'type' => 'number', 'required' => true, 'list' => true, 'default' => 12],
            ],
        ],

        'backups' => [
            'title'    => 'Backups',
            'singular' => 'backup plan',
            'table'    => 'backups',
            'order'    => 't.join_date DESC',
            'sum'      => 'amount',
            'empty'    => 'No backup plans yet. Add one when a customer joins the backup service.',
            'fields'   => [
                ['name' => 'customer_id', 'label' => 'Customer',  'type' => 'lookup', 'required' => true, 'list' => true,
                 'lookup' => ['table' => 'customers', 'label' => 'name']],
                ['name' => 'join_date',   'label' => 'Join date', 'type' => 'date',   'required' => true, 'list' => true],
                ['name' => 'amount',      'label' => 'Amount',    'type' => 'money',  'required' => true, 'list' => true],
            ],
        ],

        'payments' => [
            'title'        => 'Payments',
            'singular'     => 'payment',
            'table'        => 'payments',
            'order'        => 't.`year` DESC, t.id DESC',
            'year_filter'  => 'year',
            'sum'          => 'amount',
            'extra_select' => "CASE WHEN t.status = 'Pending' AND t.due_date IS NOT NULL AND t.due_date < CURDATE() THEN 1 ELSE 0 END AS is_overdue",
            'empty'        => 'No payments yet. Add a payment to see what is paid and what is still waiting.',
            'fields'       => [
                ['name' => 'customer_id', 'label' => 'Customer', 'type' => 'lookup', 'required' => true, 'list' => true,
                 'lookup' => ['table' => 'customers', 'label' => 'name']],
                ['name' => 'category',    'label' => 'Payment for', 'type' => 'select', 'required' => true, 'list' => true, 'default' => 'Web plan',
                 'options' => ['Web plan' => 'Web plan', 'Backup' => 'Backup', 'Domain' => 'Domain', 'Hosting' => 'Hosting', 'Other' => 'Other']],
                ['name' => 'amount',      'label' => 'Amount',      'type' => 'money',  'required' => true, 'list' => true],
                ['name' => 'year',        'label' => 'Year',        'type' => 'year',   'required' => true, 'list' => true, 'default' => $thisYear],
                ['name' => 'due_date',    'label' => 'Due date',    'type' => 'date',   'list' => true],
                ['name' => 'paid_date',   'label' => 'Paid date',   'type' => 'date'],
                ['name' => 'status',      'label' => 'Status',      'type' => 'select', 'required' => true, 'list' => true, 'default' => 'Pending',
                 'options' => ['Pending' => 'Pending', 'Paid' => 'Paid'],
                 'badge'   => ['Paid' => 'ok', 'Pending' => 'warn']],
                ['name' => 'notes',       'label' => 'Notes',       'type' => 'textarea'],
            ],
        ],

    ];
}

function get_module($slug)
{
    $all = modules();
    return isset($all[$slug]) ? $all[$slug] : null;
}
