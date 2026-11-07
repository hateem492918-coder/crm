<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

$base = '/' . trim((string)($config['app']['base_path'] ?? ''), '/');
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($base !== '/' && $base !== '' && str_starts_with($uriPath, $base)) {
    $uriPath = substr($uriPath, strlen($base)) ?: '/';
}
$path = '/' . trim($uriPath, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

function page_start(string $title): void
{
    $user = current_user();
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . ' · Kahloon CRM</title><link rel="stylesheet" href="' . e(base_path('/assets/app.css')) . '"></head><body>';
    if ($user) {
        echo '<header class="topbar"><a class="brand" href="' . e(base_path('/')) . '">Kahloon <span>CRM</span></a><nav><a href="' . e(base_path('/customers')) . '">Customers</a><a href="' . e(base_path('/leads')) . '">Leads</a></nav><div class="user-menu">' . e($user['name']) . '<form method="post" action="' . e(base_path('/logout')) . '">' . csrf_field() . '<button class="link-button" type="submit">Sign out</button></form></div></header><main class="container">';
    } else {
        echo '<main class="auth-shell">';
    }
    if ($flash) {
        echo '<div class="alert ' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }
}

function page_end(): void
{
    echo '</main><footer class="footer">Kahloon CRM · Local-first customer management</footer></body></html>';
}

function input(string $name, string $label, array $values, string $type = 'text', bool $required = false, string $placeholder = ''): void
{
    $value = $values[$name] ?? '';
    echo '<label class="field"><span>' . e($label) . ($required ? ' <b>*</b>' : '') . '</span>';
    if ($type === 'textarea') {
        echo '<textarea name="' . e($name) . '" rows="3" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder) . '">' . e($value) . '</textarea>';
    } else {
        echo '<input type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '" ' . ($required ? 'required' : '') . ' placeholder="' . e($placeholder) . '">';
    }
    echo '</label>';
}

function select_field(string $name, string $label, array $options, array $values, bool $required = false): void
{
    echo '<label class="field"><span>' . e($label) . ($required ? ' <b>*</b>' : '') . '</span><select name="' . e($name) . '" ' . ($required ? 'required' : '') . '>';
    foreach ($options as $value => $text) {
        $selected = (string)($values[$name] ?? '') === (string)$value ? ' selected' : '';
        echo '<option value="' . e($value) . '"' . $selected . '>' . e($text) . '</option>';
    }
    echo '</select></label>';
}

function record_config(string $entity): array
{
    if ($entity === 'customers') {
        return [
            'title' => 'Customers', 'singular' => 'Customer',
            'columns' => ['company_name' => 'Company', 'contact_person' => 'Contact', 'email' => 'Email', 'phone' => 'Phone', 'country' => 'Country', 'status' => 'Status'],
            'fields' => ['company_name','contact_person','email','phone','industry','country','city','website','address','source','status','assigned_to'],
        ];
    }
    return [
        'title' => 'Leads', 'singular' => 'Lead',
        'columns' => ['company_name' => 'Company', 'contact_name' => 'Contact', 'email' => 'Email', 'status' => 'Status', 'priority' => 'Priority', 'next_followup' => 'Next follow-up'],
        'fields' => ['company_name','contact_name','email','phone','industry','country','source','service_interest','status','priority','assigned_to','expected_value','currency','next_followup','remarks'],
    ];
}

function render_record_form(string $entity, array $record = [], bool $editing = false): void
{
    $cfg = record_config($entity);
    $users = db()->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name")->fetchAll();
    $assignees = ['' => 'Unassigned'];
    foreach ($users as $user) $assignees[(string)$user['id']] = $user['name'];
    page_start(($editing ? 'Edit ' : 'New ') . $cfg['singular']);
    echo '<section class="page-heading"><div><p class="eyebrow">' . e($cfg['title']) . '</p><h1>' . ($editing ? 'Edit ' : 'Add ') . e($cfg['singular']) . '</h1></div><a class="button secondary" href="' . e(base_path('/' . $entity)) . '">Back to ' . e(strtolower($cfg['title'])) . '</a></section>';
    echo '<form class="card form-grid" method="post">' . csrf_field();
    input('company_name', 'Company name', $record, 'text', true);
    if ($entity === 'customers') {
        input('contact_person', 'Contact person', $record);
    } else {
        input('contact_name', 'Contact person', $record);
    }
    input('email', 'Email', $record, 'email');
    input('phone', 'Phone', $record, 'tel');
    input('industry', 'Industry', $record);
    input('country', 'Country', $record);
    if ($entity === 'customers') {
        input('city', 'City', $record);
        input('website', 'Website', $record, 'url');
        input('source', 'Acquisition source', $record);
        select_field('status', 'Status', ['prospect'=>'Prospect','active'=>'Active','inactive'=>'Inactive'], $record);
    } else {
        input('source', 'Lead source', $record);
        input('service_interest', 'Product / service interest', $record);
        select_field('status', 'Lead status', ['new'=>'New','contacted'=>'Contacted','interested'=>'Interested','qualified'=>'Qualified','proposal'=>'Proposal sent','negotiation'=>'Negotiation','converted'=>'Converted','lost'=>'Lost'], $record);
        select_field('priority', 'Priority', ['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'], $record);
        input('expected_value', 'Expected value', $record, 'number');
        input('currency', 'Currency (ISO code)', $record);
        input('next_followup', 'Next follow-up', $record, 'datetime-local');
        input('remarks', 'Remarks', $record, 'textarea');
    }
    select_field('assigned_to', 'Assigned to', $assignees, $record);
    if ($entity === 'customers') input('address', 'Address', $record, 'textarea');
    echo '<div class="form-actions"><button class="button" type="submit">' . ($editing ? 'Save changes' : 'Create ' . e(strtolower($cfg['singular']))) . '</button><a class="button secondary" href="' . e(base_path('/' . $entity)) . '">Cancel</a></div></form>';
    page_end();
}

function normalize_record(string $entity): array
{
    $cfg = record_config($entity);
    $data = [];
    foreach ($cfg['fields'] as $field) {
        $value = trim((string)($_POST[$field] ?? ''));
        $data[$field] = $value === '' ? null : $value;
    }
    if (isset($data['email']) && $data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }
    if ($entity === 'leads') {
        if ($data['expected_value'] !== null && (!is_numeric($data['expected_value']) || (float)$data['expected_value'] < 0)) throw new InvalidArgumentException('Expected value must be a non-negative number.');
        if ($data['currency'] === null) $data['currency'] = 'PKR';
        $data['currency'] = strtoupper($data['currency']);
        if (!preg_match('/^[A-Z]{3}$/', $data['currency'])) throw new InvalidArgumentException('Currency must be a 3-letter ISO code.');
        if ($data['next_followup'] !== null) $data['next_followup'] = str_replace('T', ' ', $data['next_followup']) . (strlen($data['next_followup']) === 16 ? ':00' : '');
    }
    if ($data['assigned_to'] === null) $data['assigned_to'] = null;
    return $data;
}

function records_page(string $entity): void
{
    $cfg = record_config($entity);
    $q = trim((string)($_GET['q'] ?? ''));
    $table = $entity;
    $where = $entity === 'customers' ? 'deleted_at IS NULL' : '1=1';
    $params = [];
    if ($q !== '') {
        $where .= ' AND (company_name LIKE ? OR email LIKE ?)';
        $params = ['%' . $q . '%', '%' . $q . '%'];
    }
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT 100");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    page_start($cfg['title']);
    echo '<section class="page-heading"><div><p class="eyebrow">CRM workspace</p><h1>' . e($cfg['title']) . '</h1><p class="muted">Browse, search, and maintain your ' . e(strtolower($cfg['title'])) . '.</p></div><a class="button" href="' . e(base_path('/' . $entity . '/new')) . '">+ Add ' . e($cfg['singular']) . '</a></section>';
    echo '<form class="searchbar" method="get"><input type="search" name="q" value="' . e($q) . '" placeholder="Search company or email"><button class="button secondary" type="submit">Search</button></form>';
    echo '<div class="card table-wrap"><table><thead><tr>';
    foreach ($cfg['columns'] as $label) echo '<th>' . e($label) . '</th>';
    echo '<th>Actions</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($cfg['columns'] as $key => $label) echo '<td>' . e($row[$key] ?? '—') . '</td>';
        echo '<td class="actions"><a href="' . e(base_path('/' . $entity . '/' . $row['id'] . '/edit')) . '">Edit</a>';
        if ($entity === 'customers') echo '<form method="post" action="' . e(base_path('/customers/' . $row['id'] . '/archive')) . '" onsubmit="return confirm(\'Archive this customer?\')">' . csrf_field() . '<button class="text-danger" type="submit">Archive</button></form>';
        echo '</td></tr>';
    }
    if (!$rows) echo '<tr><td class="empty" colspan="' . (count($cfg['columns']) + 1) . '">No records found. Add your first ' . e(strtolower($cfg['singular'])) . ' to get started.</td></tr>';
    echo '</tbody></table></div>';
    page_end();
}

if ($path === '/login' && $method === 'GET') {
    if (current_user()) redirect('/');
    page_start('Sign in');
    echo '<div class="auth-card"><a class="brand dark" href="' . e(base_path('/login')) . '">Kahloon <span>CRM</span></a><p class="eyebrow">Welcome back</p><h1>Sign in to your workspace</h1><form method="post" action="' . e(base_path('/login')) . '">' . csrf_field();
    input('email', 'Work email', [], 'email', true);
    input('password', 'Password', [], 'password', true);
    echo '<button class="button full" type="submit">Sign in</button></form></div>';
    page_end();
    exit;
}

if ($path === '/login' && $method === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $stmt = db()->prepare("SELECT u.id, u.name, u.email, u.password_hash, u.status, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && $user['status'] === 'active' && password_verify((string)($_POST['password'] ?? ''), $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id'=>(int)$user['id'], 'name'=>$user['name'], 'email'=>$user['email'], 'role'=>$user['role']];
        db()->prepare('UPDATE users SET last_login = UTC_TIMESTAMP() WHERE id = ?')->execute([$user['id']]);
        flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect('/');
    }
    flash('error', 'Email or password is incorrect.');
    redirect('/login');
}

if ($path === '/logout' && $method === 'POST') {
    verify_csrf();
    $_SESSION = [];
    session_regenerate_id(true);
    redirect('/login');
}

require_login();

if ($path === '/' && $method === 'GET') {
    $stats = [
        'Customers' => (int)db()->query('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL')->fetchColumn(),
        'Leads' => (int)db()->query('SELECT COUNT(*) FROM leads')->fetchColumn(),
        'Qualified leads' => (int)db()->query("SELECT COUNT(*) FROM leads WHERE status = 'qualified'")->fetchColumn(),
        'Follow-ups due' => (int)db()->query('SELECT COUNT(*) FROM leads WHERE next_followup IS NOT NULL AND next_followup <= UTC_TIMESTAMP() AND status NOT IN (\'converted\',\'lost\')')->fetchColumn(),
    ];
    page_start('Dashboard');
    echo '<section class="page-heading"><div><p class="eyebrow">Overview</p><h1>Good work starts with a clear view.</h1><p class="muted">Your CRM activity at a glance.</p></div></section><section class="stat-grid">';
    foreach ($stats as $label => $value) echo '<article class="card stat"><span>' . e($label) . '</span><strong>' . e($value) . '</strong><small>Current records</small></article>';
    echo '</section><section class="quick-grid"><article class="card"><p class="eyebrow">Get started</p><h2>Manage relationships</h2><p class="muted">Keep customer details and sales leads organized in one place.</p><div class="button-row"><a class="button" href="' . e(base_path('/customers/new')) . '">Add customer</a><a class="button secondary" href="' . e(base_path('/leads/new')) . '">Add lead</a></div></article><article class="card"><p class="eyebrow">Your workspace</p><h2>' . e(current_user()['role']) . '</h2><p class="muted">Signed in as ' . e(current_user()['email']) . '</p><a href="' . e(base_path('/customers')) . '">Browse customers →</a><br><a href="' . e(base_path('/leads')) . '">Browse leads →</a></article></section>';
    page_end();
    exit;
}

foreach (['customers', 'leads'] as $entity) {
    if ($path === '/' . $entity && $method === 'GET') { records_page($entity); exit; }
    if ($path === '/' . $entity . '/new' && $method === 'GET') { render_record_form($entity); exit; }
    if ($path === '/' . $entity . '/new' && $method === 'POST') {
        verify_csrf();
        try {
            $data = normalize_record($entity);
            $data['created_by'] = current_user()['id'];
            $columns = array_keys($data);
            $sql = 'INSERT INTO ' . $entity . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $stmt = db()->prepare($sql);
            $stmt->execute(array_values($data));
            audit($entity, (int)db()->lastInsertId(), 'created', null, $data);
            flash('success', record_config($entity)['singular'] . ' created.');
            redirect('/' . $entity);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect('/' . $entity . '/new');
        }
    }
    if (preg_match('#^/' . $entity . '/([0-9]+)/edit$#', $path, $match)) {
        $id = (int)$match[1];
        $query = $entity === 'customers' ? 'SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL' : 'SELECT * FROM leads WHERE id = ?';
        $stmt = db()->prepare($query);
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if (!$record) { http_response_code(404); exit('Record not found.'); }
        if ($method === 'GET') { render_record_form($entity, $record, true); exit; }
        if ($method === 'POST') {
            verify_csrf();
            try {
                $data = normalize_record($entity);
                $old = $record;
                $sets = implode(',', array_map(static fn($field) => $field . ' = ?', array_keys($data)));
                $stmt = db()->prepare('UPDATE ' . $entity . ' SET ' . $sets . ' WHERE id = ?');
                $stmt->execute([...array_values($data), $id]);
                audit($entity, $id, 'updated', $old, $data);
                flash('success', record_config($entity)['singular'] . ' updated.');
                redirect('/' . $entity);
            } catch (InvalidArgumentException $exception) {
                flash('error', $exception->getMessage());
                redirect('/' . $entity . '/' . $id . '/edit');
            }
        }
    }
}

if (preg_match('#^/customers/([0-9]+)/archive$#', $path, $match) && $method === 'POST') {
    verify_csrf();
    $id = (int)$match[1];
    $stmt = db()->prepare('UPDATE customers SET deleted_at = UTC_TIMESTAMP() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) audit('customers', $id, 'archived');
    flash('success', 'Customer archived.');
    redirect('/customers');
}

http_response_code(404);
page_start('Not found');
echo '<section class="card"><h1>Page not found</h1><p>The page you requested does not exist.</p><a href="' . e(base_path('/')) . '">Return to dashboard</a></section>';
page_end();
