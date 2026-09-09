<?php
/**
 * Practice Onboarding Tracker - front controller.
 *
 * Every page and every write goes through here. Routes are plain query
 * parameters (index.php?p=dashboard) so the app works on any Apache
 * configuration, with or without mod_rewrite.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

// ---------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------

/** Render a template inside the shared layout. */
function render(string $template, array $vars = []): void
{
    global $config;

    $vars['config']   = $config;
    $vars['is_admin'] = Auth::isAdmin();
    $vars['flashes']  = take_flashes();
    $vars['template'] = $template;

    extract($vars, EXTR_SKIP);
    $__template = APP_ROOT . '/templates/' . $template . '.php';

    if (!is_file($__template)) {
        http_response_code(500);
        echo 'Template not found: ' . e($template);
        exit;
    }

    require APP_ROOT . '/templates/layout.php';
}

/** Show a friendly not-found page. */
function not_found(string $message = 'That page does not exist.'): void
{
    http_response_code(404);
    render('error', ['title' => 'Not found', 'message' => $message]);
    exit;
}

// ---------------------------------------------------------------------
// Filters read from the query string
// ---------------------------------------------------------------------

function read_filters(): array
{
    return [
        'q'            => trim((string) ($_GET['q'] ?? '')),
        'practice_id'  => (int) ($_GET['practice_id'] ?? 0) ?: null,
        'product_id'   => (int) ($_GET['product_id'] ?? 0) ?: null,
        'category_id'  => (int) ($_GET['category_id'] ?? 0) ?: null,
        'status'       => isset(STATUSES[(string) ($_GET['status'] ?? '')]) ? (string) $_GET['status'] : null,
        'assignee_id'  => ($_GET['assignee_id'] ?? '') === 'none'
                            ? 'none'
                            : ((int) ($_GET['assignee_id'] ?? 0) ?: null),
        'state'        => isset(PRACTICE_STATES[(string) ($_GET['state'] ?? '')]) ? (string) $_GET['state'] : null,
        'only_blocked' => !empty($_GET['blocked']),
        'only_overdue' => !empty($_GET['overdue']),
        'only_attention' => !empty($_GET['attention']),
        'include_archived' => !empty($_GET['archived']),
        'sort'         => (string) ($_GET['sort'] ?? 'name'),
        'dir'          => (($_GET['dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc',
    ];
}

// ---------------------------------------------------------------------
// Saving a whole grid of admin rows at once
// ---------------------------------------------------------------------

/** Coerce one submitted grid cell to the right shape. */
function grid_cast(string $type, $raw)
{
    $v = is_string($raw) ? trim($raw) : $raw;
    switch ($type) {
        case 'int':     return (int) $v;
        case 'bool':    return !empty($v) ? 1 : 0;
        case 'strnull': return ($v === '' || $v === null) ? null : (string) $v;
        case 'str':
        default:        return (string) $v;
    }
}

/**
 * Apply edits from an admin grid.
 *
 * Only fields that actually changed are written, so untouched rows cost
 * nothing and the activity log stays readable. $fields is a fixed
 * whitelist defined in code, never taken from the request, which is what
 * makes interpolating the column names here safe.
 *
 * @param array<string,string> $fields column => type
 * @return array{changed:int, skipped:array<string>}
 */
function save_grid(string $table, string $entity, array $fields, array $rows, array $required = ['name']): array
{
    $changed = 0;
    $skipped = [];

    foreach ($rows as $id => $vals) {
        $id = (int) $id;
        if ($id <= 0 || !is_array($vals)) {
            continue;
        }

        $current = Database::one("SELECT * FROM {$table} WHERE id = :id", ['id' => $id]);
        if (!$current) {
            continue;
        }

        $label = (string) ($current['name'] ?? ('#' . $id));

        // A blank required field means the row is skipped, not blanked out.
        $blank = false;
        foreach ($required as $req) {
            if (array_key_exists($req, $vals) && trim((string) $vals[$req]) === '') {
                $blank = true;
            }
        }
        if ($blank) {
            $skipped[] = $label;
            continue;
        }

        $set = [];
        $args = ['id' => $id];
        $fieldsChanged = [];

        foreach ($fields as $col => $type) {
            if (!array_key_exists($col, $vals)) {
                continue;
            }
            $new = grid_cast($type, $vals[$col]);
            $old = $current[$col];

            if ((string) $old === (string) $new) {
                continue;
            }
            $set[] = "{$col} = :{$col}";
            $args[$col] = $new;
            $fieldsChanged[] = $col;
        }

        if (!$set) {
            continue;
        }

        Database::run("UPDATE {$table} SET " . implode(', ', $set) . ' WHERE id = :id', $args);
        $changed++;

        $newLabel = isset($vals['name']) ? trim((string) $vals['name']) : $label;
        Activity::log(
            $entity,
            'update',
            $id,
            null,
            $entity === 'task' ? $id : null,
            implode(', ', $fieldsChanged),
            null,
            null,
            sprintf('%s updated: %s (%s)', ucfirst($entity), $newLabel, implode(', ', $fieldsChanged))
        );
    }

    return ['changed' => $changed, 'skipped' => $skipped];
}

/** Turn a save_grid result into a message for the user. */
function grid_flash(array $result, string $noun): void
{
    if (!empty($result['skipped'])) {
        flash(sprintf(
            'Skipped %s because the name was left blank: %s',
            count($result['skipped']) === 1 ? 'one row' : count($result['skipped']) . ' rows',
            implode(', ', $result['skipped'])
        ), 'error');
    }

    if ($result['changed'] === 0 && empty($result['skipped'])) {
        flash('Nothing had changed, so nothing was saved.');
        return;
    }
    if ($result['changed'] > 0) {
        flash(sprintf(
            'Saved %d %s.',
            $result['changed'],
            $result['changed'] === 1 ? $noun : $noun . 's'
        ));
    }
}

// ---------------------------------------------------------------------
// Guard: database reachable and installed
// ---------------------------------------------------------------------

$route  = (string) ($_GET['p'] ?? 'dashboard');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    Database::pdo();
} catch (Throwable $ex) {
    error_log('DB connect failed: ' . $ex->getMessage());
    http_response_code(500);
    render('error', [
        'title'   => 'Cannot reach the database',
        'message' => 'Check the credentials in config/config.php. The exact error was written to the server error log.',
    ]);
    exit;
}

if (!Database::isInstalled() && $route !== 'login') {
    render('error', [
        'title'   => 'Database not set up yet',
        'message' => 'The tables have not been created. Open install.php in this folder to run the schema, '
                   . 'or import db/schema.sql and db/seed.sql through phpMyAdmin.',
    ]);
    exit;
}

// =====================================================================
// POST routes
// =====================================================================

if ($method === 'POST') {

    // ---- JSON endpoints (inline editing) -----------------------------
    if ($route === 'api/task-save') {
        Csrf::requireValid(true);
        Auth::requireAdmin(true);

        $practiceId = (int) ($_POST['practice_id'] ?? 0);
        $taskId     = (int) ($_POST['task_id'] ?? 0);
        $field      = (string) ($_POST['field'] ?? '');

        $allowed = ['status', 'assignee_id', 'due_date', 'notes'];
        if (!in_array($field, $allowed, true)) {
            json_out(['ok' => false, 'error' => 'Unknown field.'], 400);
        }

        $raw = $_POST['value'] ?? '';
        $raw = is_string($raw) ? trim($raw) : '';

        switch ($field) {
            case 'status':
                if (!isset(STATUSES[$raw])) {
                    json_out(['ok' => false, 'error' => 'Unknown status.'], 400);
                }
                $value = $raw;
                break;
            case 'assignee_id':
                $value = $raw === '' ? null : (int) $raw;
                break;
            case 'due_date':
                if ($raw === '') {
                    $value = null;
                } else {
                    $d = DateTime::createFromFormat('Y-m-d', $raw);
                    if (!$d || $d->format('Y-m-d') !== $raw) {
                        json_out(['ok' => false, 'error' => 'Use a YYYY-MM-DD date.'], 400);
                    }
                    $value = $raw;
                }
                break;
            default: // notes
                $value = $raw === '' ? null : mb_substr($raw, 0, 5000);
                break;
        }

        try {
            $row = Repo::saveTaskState($practiceId, $taskId, [$field => $value]);
        } catch (Throwable $ex) {
            json_out(['ok' => false, 'error' => $ex->getMessage()], 400);
        }

        $all = Repo::practiceTasks($practiceId);
        json_out([
            'ok'      => true,
            'task'    => $row,
            'rollup'  => Repo::rollup($all),
            'by_product' => array_map(
                static fn($g) => [
                    'product_id'   => $g['product_id'],
                    'product_name' => $g['product_name'],
                    'rollup'       => Repo::rollup($g['tasks']),
                ],
                array_values(Repo::groupTasks($all))
            ),
        ]);
    }

    // ---- Everything else is a form post ------------------------------
    Csrf::requireValid();

    if ($route === 'login') {
        $err = Auth::attempt((string) ($_POST['pin'] ?? ''));
        if ($err === null) {
            Activity::log('auth', 'login');
            $next = (string) ($_POST['next'] ?? '');
            redirect($next !== '' && str_starts_with($next, 'index.php') ? $next : url('dashboard'));
        }
        render('login', ['error' => $err, 'next' => (string) ($_POST['next'] ?? '')]);
        exit;
    }

    Auth::requireAdmin();

    switch ($route) {

        // ---- Practices ------------------------------------------------
        case 'practice-save': {
            $id    = (int) ($_POST['id'] ?? 0);
            $name  = post_str('name');
            if ($name === '') {
                flash('A practice needs a name.', 'error');
                redirect($id ? url('admin/practice-form', ['id' => $id]) : url('admin/practice-form'));
            }

            $fields = [
                'name'                => $name,
                'location'            => post_str('location') ?: null,
                'onboarding_state'    => isset(PRACTICE_STATES[post_str('onboarding_state')])
                                            ? post_str('onboarding_state') : 'active',
                'target_go_live_date' => post_date_or_null('target_go_live_date'),
                'notes'               => post_str('notes') ?: null,
                'is_archived'         => !empty($_POST['is_archived']) ? 1 : 0,
            ];

            if ($id > 0) {
                if (!Repo::practice($id)) {
                    not_found('That practice does not exist.');
                }
                Database::run(
                    'UPDATE practices
                        SET name = :name, location = :location, onboarding_state = :onboarding_state,
                            target_go_live_date = :target_go_live_date, notes = :notes,
                            is_archived = :is_archived
                      WHERE id = :id',
                    $fields + ['id' => $id]
                );
                Activity::log('practice', 'update', $id, $id, null, null, null, null, 'Practice details updated');
            } else {
                $slug = slugify($name);
                $n = 1;
                while (Database::scalar('SELECT 1 FROM practices WHERE slug = :s', ['s' => $slug])) {
                    $slug = slugify($name) . '-' . (++$n);
                }
                Database::run(
                    'INSERT INTO practices (name, slug, location, onboarding_state, target_go_live_date, notes, is_archived)
                     VALUES (:name, :slug, :location, :onboarding_state, :target_go_live_date, :notes, :is_archived)',
                    $fields + ['slug' => $slug]
                );
                $id = Database::lastId();
                Activity::log('practice', 'create', $id, $id, null, null, null, $name, 'Practice created');
            }

            $productIds = array_map('intval', (array) ($_POST['product_ids'] ?? []));
            $diff = Repo::setPracticeProducts($id, $productIds);
            foreach ($diff['added'] as $pid) {
                $p = Repo::product((int) $pid);
                Activity::log('practice_product', 'add', (int) $pid, $id, null, null, null,
                    $p['name'] ?? null, 'Product added: ' . ($p['name'] ?? ('#' . $pid)));
            }
            foreach ($diff['removed'] as $pid) {
                $p = Repo::product((int) $pid);
                Activity::log('practice_product', 'remove', (int) $pid, $id, null, null,
                    $p['name'] ?? null, null, 'Product removed: ' . ($p['name'] ?? ('#' . $pid)));
            }

            flash('Practice saved.');
            redirect(url('practice', ['id' => $id]));
        }

        case 'practice-delete': {
            $id = (int) ($_POST['id'] ?? 0);
            $p  = Repo::practice($id);
            if (!$p) {
                not_found('That practice does not exist.');
            }
            if (!empty($_POST['hard'])) {
                Database::run('DELETE FROM practices WHERE id = :id', ['id' => $id]);
                Activity::log('practice', 'delete', $id, null, null, null, (string) $p['name'], null,
                    'Practice deleted: ' . $p['name']);
                flash('Practice deleted.');
            } else {
                Database::run('UPDATE practices SET is_archived = 1 WHERE id = :id', ['id' => $id]);
                Activity::log('practice', 'archive', $id, $id, null, null, null, null, 'Practice archived');
                flash('Practice archived. It is hidden from the dashboard but nothing was lost.');
            }
            redirect(url('admin/practices'));
        }

        // ---- Products -------------------------------------------------
        case 'product-save': {
            $id   = (int) ($_POST['id'] ?? 0);
            $name = post_str('name');
            if ($name === '') {
                flash('A product needs a name.', 'error');
                redirect(url('admin/products'));
            }
            $desc = post_str('description') ?: null;
            $sort = (int) ($_POST['sort_order'] ?? 0);
            $active = !empty($_POST['is_active']) ? 1 : 0;

            if ($id > 0) {
                Database::run(
                    'UPDATE products SET name = :name, description = :description,
                            sort_order = :sort_order, is_active = :is_active
                      WHERE id = :id',
                    ['name' => $name, 'description' => $desc, 'sort_order' => $sort,
                     'is_active' => $active, 'id' => $id]
                );
                Activity::log('product', 'update', $id, null, null, null, null, $name, 'Product updated: ' . $name);
                flash('Product updated.');
            } else {
                $slug = slugify($name);
                $n = 1;
                while (Database::scalar('SELECT 1 FROM products WHERE slug = :s', ['s' => $slug])) {
                    $slug = slugify($name) . '-' . (++$n);
                }
                if ($sort === 0) {
                    $sort = (int) Database::scalar('SELECT COALESCE(MAX(sort_order),0) + 10 FROM products');
                }
                Database::run(
                    'INSERT INTO products (name, slug, description, sort_order, is_active)
                     VALUES (:name, :slug, :description, :sort_order, 1)',
                    ['name' => $name, 'slug' => $slug, 'description' => $desc, 'sort_order' => $sort]
                );
                Activity::log('product', 'create', Database::lastId(), null, null, null, null, $name,
                    'Product created: ' . $name);
                flash('Product added. Add its onboarding tasks next.');
            }
            redirect(url('admin/products'));
        }

        case 'product-delete': {
            $id = (int) ($_POST['id'] ?? 0);
            $p  = Repo::product($id);
            if (!$p) {
                not_found('That product does not exist.');
            }
            $inUse = (int) Database::scalar(
                'SELECT COUNT(*) FROM practice_products WHERE product_id = :id',
                ['id' => $id]
            );
            if ($inUse > 0 && empty($_POST['force'])) {
                Database::run('UPDATE products SET is_active = 0 WHERE id = :id', ['id' => $id]);
                Activity::log('product', 'deactivate', $id, null, null, null, null, null,
                    'Product deactivated: ' . $p['name']);
                flash(sprintf(
                    '%s is used by %d practice(s), so it was deactivated rather than deleted. Its tasks are hidden but nothing was lost.',
                    $p['name'],
                    $inUse
                ));
            } else {
                Database::run('DELETE FROM products WHERE id = :id', ['id' => $id]);
                Activity::log('product', 'delete', $id, null, null, null, (string) $p['name'], null,
                    'Product deleted: ' . $p['name']);
                flash('Product deleted along with its task definitions.');
            }
            redirect(url('admin/products'));
        }

        // ---- Grid saves: one button for the whole table ----------------
        case 'products-save-all': {
            $r = save_grid('products', 'product', [
                'sort_order'  => 'int',
                'name'        => 'str',
                'description' => 'strnull',
                'is_active'   => 'bool',
            ], (array) ($_POST['rows'] ?? []));
            grid_flash($r, 'product');
            redirect(url('admin/products'));
        }

        case 'categories-save-all': {
            $r = save_grid('categories', 'category', [
                'sort_order' => 'int',
                'name'       => 'str',
                'is_active'  => 'bool',
            ], (array) ($_POST['rows'] ?? []));
            grid_flash($r, 'category');
            redirect(url('admin/categories'));
        }

        case 'assignees-save-all': {
            // Reject bad addresses before touching the database.
            $rows = (array) ($_POST['rows'] ?? []);
            $bad  = [];
            foreach ($rows as $id => $vals) {
                $email = trim((string) ($vals['email'] ?? ''));
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $bad[] = trim((string) ($vals['name'] ?? ('#' . (int) $id)));
                    unset($rows[$id]);
                }
            }
            if ($bad) {
                flash('That email address does not look valid, so this row was left alone: '
                      . implode(', ', $bad), 'error');
            }

            $r = save_grid('assignees', 'assignee', [
                'name'       => 'str',
                'role_title' => 'strnull',
                'email'      => 'strnull',
                'is_active'  => 'bool',
            ], $rows);
            grid_flash($r, 'person');
            redirect(url('admin/assignees'));
        }

        case 'tasks-save-all': {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $rows      = (array) ($_POST['rows'] ?? []);

            // A task may only be moved to a category that exists.
            $validCats = array_map(static fn($c) => (int) $c['id'], Repo::categories());
            foreach ($rows as $id => $vals) {
                if (isset($vals['category_id']) && !in_array((int) $vals['category_id'], $validCats, true)) {
                    unset($rows[$id]['category_id']);
                }
            }

            $r = save_grid('tasks', 'task', [
                'name'        => 'str',
                'category_id' => 'int',
                'description' => 'strnull',
                'is_active'   => 'bool',
            ], $rows);
            grid_flash($r, 'task');
            redirect(url('admin/tasks', ['product_id' => $productId ?: null]));
        }

        // ---- Categories -----------------------------------------------
        case 'category-save': {
            $id   = (int) ($_POST['id'] ?? 0);
            $name = post_str('name');
            if ($name === '') {
                flash('A category needs a name.', 'error');
                redirect(url('admin/categories'));
            }
            $sort   = (int) ($_POST['sort_order'] ?? 0);
            $active = !empty($_POST['is_active']) ? 1 : 0;

            try {
                if ($id > 0) {
                    Database::run(
                        'UPDATE categories SET name = :name, sort_order = :sort_order, is_active = :is_active
                          WHERE id = :id',
                        ['name' => $name, 'sort_order' => $sort, 'is_active' => $active, 'id' => $id]
                    );
                    Activity::log('category', 'update', $id, null, null, null, null, $name,
                        'Category updated: ' . $name);
                    flash('Category updated.');
                } else {
                    if ($sort === 0) {
                        $sort = (int) Database::scalar('SELECT COALESCE(MAX(sort_order),0) + 10 FROM categories');
                    }
                    Database::run(
                        'INSERT INTO categories (name, sort_order, is_active) VALUES (:name, :sort_order, 1)',
                        ['name' => $name, 'sort_order' => $sort]
                    );
                    Activity::log('category', 'create', Database::lastId(), null, null, null, null, $name,
                        'Category created: ' . $name);
                    flash('Category added. It is available to every product straight away.');
                }
            } catch (PDOException $ex) {
                flash('A category with that name already exists.', 'error');
            }
            redirect(url('admin/categories'));
        }

        case 'category-delete': {
            $id = (int) ($_POST['id'] ?? 0);
            $c  = Repo::category($id);
            if (!$c) {
                not_found('That category does not exist.');
            }
            $inUse = (int) Database::scalar('SELECT COUNT(*) FROM tasks WHERE category_id = :id', ['id' => $id]);
            if ($inUse > 0) {
                Database::run('UPDATE categories SET is_active = 0 WHERE id = :id', ['id' => $id]);
                Activity::log('category', 'deactivate', $id, null, null, null, null, null,
                    'Category deactivated: ' . $c['name']);
                flash(sprintf('%s is used by %d task(s), so it was deactivated rather than deleted.', $c['name'], $inUse));
            } else {
                Database::run('DELETE FROM categories WHERE id = :id', ['id' => $id]);
                Activity::log('category', 'delete', $id, null, null, null, (string) $c['name'], null,
                    'Category deleted: ' . $c['name']);
                flash('Category deleted.');
            }
            redirect(url('admin/categories'));
        }

        // ---- Assignees ------------------------------------------------
        case 'assignee-save': {
            $id   = (int) ($_POST['id'] ?? 0);
            $name = post_str('name');
            if ($name === '') {
                flash('An assignee needs a name.', 'error');
                redirect(url('admin/assignees'));
            }
            $email = post_str('email') ?: null;
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('That email address does not look valid.', 'error');
                redirect(url('admin/assignees'));
            }
            $role   = post_str('role_title') ?: null;
            $active = !empty($_POST['is_active']) ? 1 : 0;

            if ($id > 0) {
                Database::run(
                    'UPDATE assignees SET name = :name, email = :email, role_title = :role_title,
                            is_active = :is_active
                      WHERE id = :id',
                    ['name' => $name, 'email' => $email, 'role_title' => $role,
                     'is_active' => $active, 'id' => $id]
                );
                Activity::log('assignee', 'update', $id, null, null, null, null, $name, 'Assignee updated: ' . $name);
                flash('Assignee updated.');
            } else {
                Database::run(
                    'INSERT INTO assignees (name, email, role_title, is_active)
                     VALUES (:name, :email, :role_title, 1)',
                    ['name' => $name, 'email' => $email, 'role_title' => $role]
                );
                Activity::log('assignee', 'create', Database::lastId(), null, null, null, null, $name,
                    'Assignee created: ' . $name);
                flash('Assignee added.');
            }
            redirect(url('admin/assignees'));
        }

        case 'assignee-delete': {
            $id = (int) ($_POST['id'] ?? 0);
            $a  = Repo::assignee($id);
            if (!$a) {
                not_found('That assignee does not exist.');
            }
            $inUse = (int) Database::scalar(
                'SELECT COUNT(*) FROM practice_tasks WHERE assignee_id = :id',
                ['id' => $id]
            );
            if ($inUse > 0) {
                Database::run('UPDATE assignees SET is_active = 0 WHERE id = :id', ['id' => $id]);
                Activity::log('assignee', 'deactivate', $id, null, null, null, null, null,
                    'Assignee deactivated: ' . $a['name']);
                flash(sprintf(
                    '%s is assigned to %d task(s), so they were deactivated rather than deleted. Existing assignments are untouched.',
                    $a['name'],
                    $inUse
                ));
            } else {
                Database::run('DELETE FROM assignees WHERE id = :id', ['id' => $id]);
                Activity::log('assignee', 'delete', $id, null, null, null, (string) $a['name'], null,
                    'Assignee deleted: ' . $a['name']);
                flash('Assignee deleted.');
            }
            redirect(url('admin/assignees'));
        }

        // ---- Global tasks ---------------------------------------------
        case 'task-save': {
            $id         = (int) ($_POST['id'] ?? 0);
            $productId  = (int) ($_POST['product_id'] ?? 0);
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name       = post_str('name');
            $back       = url('admin/tasks', ['product_id' => $productId ?: null]);

            if ($name === '' || !Repo::product($productId) || !Repo::category($categoryId)) {
                flash('A task needs a name, a product, and a category.', 'error');
                redirect($back);
            }

            $desc   = post_str('description') ?: null;
            $active = !empty($_POST['is_active']) ? 1 : 0;

            if ($id > 0) {
                $old = Repo::task($id);
                if (!$old) {
                    not_found('That task does not exist.');
                }
                Database::run(
                    'UPDATE tasks SET product_id = :product_id, category_id = :category_id,
                            name = :name, description = :description, is_active = :is_active
                      WHERE id = :id',
                    ['product_id' => $productId, 'category_id' => $categoryId, 'name' => $name,
                     'description' => $desc, 'is_active' => $active, 'id' => $id]
                );
                Activity::log('task', 'update', $id, null, $id, null, (string) $old['name'], $name,
                    'Global task updated: ' . $name);
                flash('Task updated everywhere it appears.');
            } else {
                Database::run(
                    'INSERT INTO tasks (product_id, category_id, name, description, sort_order, is_active)
                     VALUES (:product_id, :category_id, :name, :description, :sort_order, 1)',
                    ['product_id' => $productId, 'category_id' => $categoryId, 'name' => $name,
                     'description' => $desc, 'sort_order' => Repo::nextTaskSort($productId)]
                );
                $newId = Database::lastId();
                $count = (int) Database::scalar(
                    'SELECT COUNT(*) FROM practice_products WHERE product_id = :pid',
                    ['pid' => $productId]
                );
                Activity::log('task', 'create', $newId, null, $newId, null, null, $name,
                    'Global task created: ' . $name);
                flash(sprintf(
                    'Task added. It now appears for %d practice(s) onboarding this product, as Not Started.',
                    $count
                ));
            }
            redirect($back);
        }

        case 'task-delete': {
            $id = (int) ($_POST['id'] ?? 0);
            $t  = Repo::task($id);
            if (!$t) {
                not_found('That task does not exist.');
            }
            $back  = url('admin/tasks', ['product_id' => (int) $t['product_id']]);
            $inUse = (int) Database::scalar('SELECT COUNT(*) FROM practice_tasks WHERE task_id = :id', ['id' => $id]);

            if (!empty($_POST['hard'])) {
                Database::run('DELETE FROM tasks WHERE id = :id', ['id' => $id]);
                Activity::log('task', 'delete', $id, null, null, null, (string) $t['name'], null,
                    'Global task deleted: ' . $t['name']);
                flash('Task deleted, along with the status recorded against it for every practice.');
            } else {
                Database::run('UPDATE tasks SET is_active = 0 WHERE id = :id', ['id' => $id]);
                Activity::log('task', 'deactivate', $id, null, $id, null, null, null,
                    'Global task deactivated: ' . $t['name']);
                flash(sprintf(
                    'Task deactivated. It is hidden from every practice, and the status recorded for %d practice(s) is preserved in case you reactivate it.',
                    $inUse
                ));
            }
            redirect($back);
        }

        case 'task-reactivate': {
            $id = (int) ($_POST['id'] ?? 0);
            $t  = Repo::task($id);
            if (!$t) {
                not_found('That task does not exist.');
            }
            Database::run('UPDATE tasks SET is_active = 1 WHERE id = :id', ['id' => $id]);
            Activity::log('task', 'reactivate', $id, null, $id, null, null, null,
                'Global task reactivated: ' . $t['name']);
            flash('Task reactivated.');
            redirect(url('admin/tasks', ['product_id' => (int) $t['product_id'], 'inactive' => 1]));
        }

        case 'task-move': {
            $id  = (int) ($_POST['id'] ?? 0);
            $dir = ($_POST['dir'] ?? 'up') === 'down' ? 'down' : 'up';
            $t   = Repo::task($id);
            if (!$t) {
                not_found('That task does not exist.');
            }

            // Swap sort_order with the adjacent task in the same product.
            $neighbour = $dir === 'up'
                ? Database::one(
                    'SELECT id, sort_order FROM tasks
                      WHERE product_id = :pid AND is_active = 1
                        AND (sort_order < :so OR (sort_order = :so AND id < :id))
                      ORDER BY sort_order DESC, id DESC LIMIT 1',
                    ['pid' => (int) $t['product_id'], 'so' => (int) $t['sort_order'], 'id' => $id]
                )
                : Database::one(
                    'SELECT id, sort_order FROM tasks
                      WHERE product_id = :pid AND is_active = 1
                        AND (sort_order > :so OR (sort_order = :so AND id > :id))
                      ORDER BY sort_order ASC, id ASC LIMIT 1',
                    ['pid' => (int) $t['product_id'], 'so' => (int) $t['sort_order'], 'id' => $id]
                );

            if ($neighbour) {
                $pdo = Database::pdo();
                $pdo->beginTransaction();
                try {
                    Database::run('UPDATE tasks SET sort_order = :so WHERE id = :id',
                        ['so' => (int) $neighbour['sort_order'], 'id' => $id]);
                    Database::run('UPDATE tasks SET sort_order = :so WHERE id = :id',
                        ['so' => (int) $t['sort_order'], 'id' => (int) $neighbour['id']]);
                    // Identical sort_order values would leave the order ambiguous.
                    if ((int) $neighbour['sort_order'] === (int) $t['sort_order']) {
                        Database::run('UPDATE tasks SET sort_order = sort_order + 1 WHERE id = :id',
                            ['id' => $dir === 'up' ? (int) $neighbour['id'] : $id]);
                    }
                    $pdo->commit();
                } catch (Throwable $ex) {
                    $pdo->rollBack();
                    flash('Could not reorder that task.', 'error');
                }
            }
            redirect(url('admin/tasks', ['product_id' => (int) $t['product_id']]));
        }

        case 'tasks-renumber': {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $rows = Database::all(
                'SELECT id FROM tasks WHERE product_id = :pid ORDER BY sort_order, id',
                ['pid' => $productId]
            );
            $i = 0;
            foreach ($rows as $r) {
                $i += 10;
                Database::run('UPDATE tasks SET sort_order = :so WHERE id = :id',
                    ['so' => $i, 'id' => (int) $r['id']]);
            }
            flash('Task order renumbered cleanly.');
            redirect(url('admin/tasks', ['product_id' => $productId]));
        }

        // ---- Bulk task updates on a practice ---------------------------
        case 'bulk-save': {
            $practiceId = (int) ($_POST['practice_id'] ?? 0);
            if (!Repo::practice($practiceId)) {
                not_found('That practice does not exist.');
            }
            $taskIds = array_map('intval', (array) ($_POST['task_ids'] ?? []));
            if (!$taskIds) {
                flash('No tasks were selected.', 'error');
                redirect(url('practice', ['id' => $practiceId]));
            }

            $changes = [];
            $status = (string) ($_POST['bulk_status'] ?? '');
            if ($status !== '' && isset(STATUSES[$status])) {
                $changes['status'] = $status;
            }
            $assignee = (string) ($_POST['bulk_assignee'] ?? '');
            if ($assignee === 'clear') {
                $changes['assignee_id'] = null;
            } elseif ($assignee !== '') {
                $changes['assignee_id'] = (int) $assignee;
            }
            $due = (string) ($_POST['bulk_due'] ?? '');
            if ($due === 'clear') {
                $changes['due_date'] = null;
            } elseif ($due !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $due);
                if ($d && $d->format('Y-m-d') === $due) {
                    $changes['due_date'] = $due;
                }
            }

            if (!$changes) {
                flash('Pick at least one thing to change.', 'error');
                redirect(url('practice', ['id' => $practiceId]));
            }

            $n = Repo::bulkUpdate($practiceId, $taskIds, $changes);
            flash(sprintf('Updated %d task(s).', $n));
            redirect(url('practice', ['id' => $practiceId]));
        }

        // ---- Notes on a practice --------------------------------------
        case 'practice-notes-save': {
            $practiceId = (int) ($_POST['id'] ?? 0);
            if (!Repo::practice($practiceId)) {
                not_found('That practice does not exist.');
            }
            Database::run('UPDATE practices SET notes = :n WHERE id = :id', [
                'n'  => post_str('notes') ?: null,
                'id' => $practiceId,
            ]);
            Activity::log('practice', 'update', $practiceId, $practiceId, null, 'notes', null, null,
                'Practice notes updated');
            flash('Notes saved.');
            redirect(url('practice', ['id' => $practiceId]));
        }
    }

    not_found('Unknown action.');
}

// =====================================================================
// GET routes
// =====================================================================

switch ($route) {

    case 'logout':
        Auth::logout();
        flash('Signed out. You are now in read-only mode.');
        redirect(url('dashboard'));

    case 'login':
        if (Auth::isAdmin()) {
            redirect(url('dashboard'));
        }
        render('login', ['error' => null, 'next' => (string) ($_GET['next'] ?? '')]);
        break;

    case 'dashboard': {
        $f    = read_filters();
        $rows = Repo::practiceSummaries($f);

        $totals = [
            'practices' => count($rows),
            'blocked'   => array_sum(array_column($rows, 'blocked')),
            'overdue'   => array_sum(array_column($rows, 'overdue')),
            'attention' => count(array_filter($rows, static fn($r) => $r['needs_attention'])),
            'completed' => array_sum(array_column($rows, 'completed')),
            'countable' => array_sum(array_column($rows, 'countable')),
        ];
        $totals['progress'] = progress_pct($totals['completed'], $totals['countable']);

        render('dashboard', [
            'rows'     => $rows,
            'totals'   => $totals,
            'filters'  => $f,
            'products' => Repo::products(),
        ]);
        break;
    }

    case 'practice': {
        $id       = (int) ($_GET['id'] ?? 0);
        $practice = Repo::practice($id);
        if (!$practice) {
            not_found('That practice does not exist.');
        }

        $f = read_filters();

        // Unfiltered rows drive the progress figures so filtering the
        // view never changes the reported percentages.
        $allRows      = Repo::practiceTasks($id);
        $filteredRows = ($f['product_id'] || $f['category_id'] || $f['status']
                         || $f['assignee_id'] || $f['only_blocked'] || $f['only_overdue'] || $f['q'])
                        ? Repo::practiceTasks($id, $f)
                        : $allRows;

        $grouped   = Repo::groupTasks($filteredRows);
        $allByProd = Repo::groupTasks($allRows);

        $byProduct = [];
        foreach ($allByProd as $pid => $g) {
            $byProduct[$pid] = [
                'product_id'   => $g['product_id'],
                'product_name' => $g['product_name'],
                'rollup'       => Repo::rollup($g['tasks']),
            ];
        }

        render('practice', [
            'practice'    => $practice,
            'grouped'     => $grouped,
            'rollup'      => Repo::rollup($allRows),
            'by_product'  => $byProduct,
            'by_category' => Repo::rollupByCategory($allRows),
            'filters'     => $f,
            'filtered'    => $filteredRows !== $allRows,
            'shown'       => count($filteredRows),
            'products'    => Repo::practiceProducts($id),
            'categories'  => Repo::categories(),
            'assignees'   => Repo::assignees(),
            'activity'    => Activity::forPractice($id, 20),
        ]);
        break;
    }

    case 'tasks': {
        $f    = read_filters();
        $rows = Repo::tasksAcrossPractices($f);
        render('tasks', [
            'rows'       => $rows,
            'rollup'     => Repo::rollup($rows),
            'filters'    => $f,
            'practices'  => Repo::practicesSimple(),
            'products'   => Repo::products(),
            'categories' => Repo::categories(),
            'assignees'  => Repo::assignees(),
        ]);
        break;
    }

    case 'export': {
        $f     = read_filters();
        $rows  = Repo::tasksAcrossPractices($f, 5000);
        $stamp = date('Y-m-d');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="onboarding-tasks-' . $stamp . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Practice', 'Product', 'Category', 'Task', 'Status', 'Assignee', 'Due date', 'Overdue', 'Notes', 'Last updated']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['practice_name'],
                $r['product_name'],
                $r['category_name'],
                $r['task_name'],
                status_label($r['status']),
                $r['assignee_name'] ?? '',
                $r['due_date'] ?? '',
                !empty($r['is_overdue']) ? 'Yes' : '',
                $r['notes'] ?? '',
                $r['updated_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    // ---- Admin screens -------------------------------------------------

    case 'admin/practices':
        Auth::requireAdmin();
        render('admin/practices', [
            'rows'    => Repo::practiceSummaries(['include_archived' => true, 'sort' => 'name']),
        ]);
        break;

    case 'admin/practice-form': {
        Auth::requireAdmin();
        $id       = (int) ($_GET['id'] ?? 0);
        $practice = $id > 0 ? Repo::practice($id) : null;
        if ($id > 0 && !$practice) {
            not_found('That practice does not exist.');
        }
        render('admin/practice_form', [
            'practice' => $practice,
            'products' => Repo::products(false),
            'selected' => $practice ? Repo::practiceProductIds((int) $practice['id']) : [],
        ]);
        break;
    }

    case 'admin/products':
        Auth::requireAdmin();
        render('admin/products', ['rows' => Repo::products(false)]);
        break;

    case 'admin/categories':
        Auth::requireAdmin();
        render('admin/categories', ['rows' => Repo::categories(false)]);
        break;

    case 'admin/assignees':
        Auth::requireAdmin();
        render('admin/assignees', ['rows' => Repo::assignees(false)]);
        break;

    case 'admin/tasks': {
        Auth::requireAdmin();
        $productId       = (int) ($_GET['product_id'] ?? 0);
        $includeInactive = !empty($_GET['inactive']);
        $products        = Repo::products(false);

        if ($productId === 0 && $products) {
            $productId = (int) $products[0]['id'];
        }

        render('admin/tasks', [
            'products'         => $products,
            'product_id'       => $productId,
            'product'          => $productId ? Repo::product($productId) : null,
            'categories'       => Repo::categories(),
            'rows'             => $productId
                                    ? Repo::tasks(['product_id' => $productId, 'include_inactive' => $includeInactive])
                                    : [],
            'include_inactive' => $includeInactive,
        ]);
        break;
    }

    case 'admin/activity': {
        Auth::requireAdmin();
        $rows = Database::all(
            'SELECT l.*, pr.name AS practice_name, t.name AS task_name
               FROM activity_log l
               LEFT JOIN practices pr ON pr.id = l.practice_id
               LEFT JOIN tasks t      ON t.id = l.task_id
              ORDER BY l.created_at DESC, l.id DESC
              LIMIT 300'
        );
        render('admin/activity', ['rows' => $rows]);
        break;
    }

    default:
        not_found();
}
