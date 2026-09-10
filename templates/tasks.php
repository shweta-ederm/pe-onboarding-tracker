<?php
/**
 * Cross-practice task list. This is the view that answers "what is
 * blocked everywhere" and "what is on my plate".
 *
 * @var array $rows
 * @var array $rollup
 * @var array $filters
 * @var array $practices
 * @var array $products
 * @var array $categories
 * @var array $assignees
 * @var bool  $is_admin
 */
$page_title = 'All tasks';
?>

<div class="page-head">
  <div>
    <h1>All tasks</h1>
    <p class="sub">
      <?= count($rows) ?> task<?= count($rows) === 1 ? '' : 's' ?> across every active practice.
      <?php if ((int) $rollup['blocked'] > 0 || (int) $rollup['overdue'] > 0): ?>
        <strong><?= (int) $rollup['blocked'] ?></strong> blocked,
        <strong><?= (int) $rollup['overdue'] ?></strong> overdue.
      <?php endif; ?>
    </p>
  </div>
  <div class="page-actions">
    <a class="btn btn-quiet" href="<?= e(url_with(['p' => 'export'])) ?>">Export CSV</a>
  </div>
</div>

<?php
$show = ['q', 'practice', 'product', 'category', 'status', 'assignee', 'flags'];
$filter_page = 'tasks';
require APP_ROOT . '/templates/partials/filter_bar.php';
?>

<?php if (!$rows): ?>
  <div class="empty">
    <h2>Nothing matches</h2>
    <p>Try clearing the filters above.</p>
  </div>
<?php else: ?>

<div class="table-scroll">
<table class="grid all-grid">
  <thead>
    <tr>
      <th>Practice</th>
      <th>Product</th>
      <th>Task</th>
      <th>Category</th>
      <th>Status</th>
      <th>Assignee</th>
      <th>Due</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r):
        $cls = '';
        if ($r['status'] === 'completed')      { $cls = 'task-done'; }
        if ($r['status'] === 'not_applicable') { $cls = 'task-na'; }
        if ($r['status'] === 'blocked')        { $cls = 'task-blocked'; }
        if (!empty($r['is_overdue']))          { $cls .= ' task-overdue'; }
    ?>
      <tr class="<?= trim($cls) ?>">
        <td class="c-name">
          <a href="<?= e(url('practice', ['id' => (int) $r['practice_id']])) ?>"><?= e($r['practice_name']) ?></a>
        </td>
        <td><span class="chip">
          <?php if (!empty($r['product_color'])): ?><span class="chip-dot" style="background: <?= e($r['product_color']) ?>"></span><?php endif; ?>
          <?= e($r['product_name']) ?></span></td>
        <td class="c-task"><?= e($r['task_name']) ?></td>
        <td><span class="muted"><?= e($r['category_name']) ?></span></td>
        <td><?php $status = (string) $r['status']; require APP_ROOT . '/templates/partials/status_badge.php'; ?></td>
        <td><?= $r['assignee_name'] ? e($r['assignee_name']) : '<span class="muted">Unassigned</span>' ?></td>
        <td class="c-due">
          <?= e(fmt_date($r['due_date'] ?? null)) ?>
          <?php if (!empty($r['is_overdue'])): ?><span class="chip chip-alert">Overdue</span><?php endif; ?>
        </td>
        <td class="c-notes-ro"><?= $r['notes'] ? e($r['notes']) : '<span class="muted">—</span>' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<p class="table-note">
  Editing happens on a practice's own page, where the whole product context is visible.
  <?php if (!$is_admin): ?>Sign in as an admin to make changes.<?php endif; ?>
</p>

<?php endif; ?>
