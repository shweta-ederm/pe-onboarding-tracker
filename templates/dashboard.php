<?php
/**
 * Main dashboard: every practice, with progress and attention flags.
 *
 * @var array $rows
 * @var array $totals
 * @var array $filters
 * @var array $products
 * @var bool  $is_admin
 */
$page_title = 'Dashboard';

$sortLinks = [
    'name'     => 'Practice name',
    'progress' => 'Progress',
    'golive'   => 'Target go-live',
    'updated'  => 'Last updated',
    'blocked'  => 'Blocked tasks',
    'overdue'  => 'Overdue tasks',
];
$curSort = $filters['sort'] ?? 'name';
$curDir  = $filters['dir'] ?? 'asc';
?>

<div class="page-head">
  <div>
    <h1>Onboarding dashboard</h1>
    <p class="sub">
      <?= (int) $totals['practices'] ?> practice<?= $totals['practices'] === 1 ? '' : 's' ?> shown.
      <?php if ($totals['attention'] > 0): ?>
        <strong><?= (int) $totals['attention'] ?></strong> need<?= $totals['attention'] === 1 ? 's' : '' ?> attention.
      <?php else: ?>
        Nothing is blocked or overdue.
      <?php endif; ?>
    </p>
  </div>
  <?php if ($is_admin): ?>
    <div class="page-actions">
      <a class="btn btn-primary" href="<?= e(url('admin/practice-form')) ?>">Add practice</a>
    </div>
  <?php endif; ?>
</div>

<section class="stat-strip" aria-label="Portfolio summary">
  <div class="stat">
    <span class="stat-label">Overall progress</span>
    <span class="stat-value"><?= (int) $totals['progress'] ?>%</span>
    <span class="stat-note"><?= (int) $totals['completed'] ?> of <?= (int) $totals['countable'] ?> tasks done</span>
  </div>
  <div class="stat">
    <span class="stat-label">Needs attention</span>
    <span class="stat-value <?= $totals['attention'] > 0 ? 'v-alert' : '' ?>"><?= (int) $totals['attention'] ?></span>
    <span class="stat-note">practices with a blocker or overdue task</span>
  </div>
  <div class="stat">
    <span class="stat-label">Blocked tasks</span>
    <span class="stat-value <?= $totals['blocked'] > 0 ? 'v-alert' : '' ?>"><?= (int) $totals['blocked'] ?></span>
    <span class="stat-note">
      <?php if ($totals['blocked'] > 0): ?>
        <a href="<?= e(url('tasks', ['blocked' => 1])) ?>">View blocked</a>
      <?php else: ?>none<?php endif; ?>
    </span>
  </div>
  <div class="stat">
    <span class="stat-label">Overdue tasks</span>
    <span class="stat-value <?= $totals['overdue'] > 0 ? 'v-warn' : '' ?>"><?= (int) $totals['overdue'] ?></span>
    <span class="stat-note">
      <?php if ($totals['overdue'] > 0): ?>
        <a href="<?= e(url('tasks', ['overdue' => 1])) ?>">View overdue</a>
      <?php else: ?>none<?php endif; ?>
    </span>
  </div>
</section>

<?php
$show = ['q', 'product', 'state', 'flags', 'attention'];
$filter_page = 'dashboard';
require APP_ROOT . '/templates/partials/filter_bar.php';
?>

<div class="sortbar">
  <span class="sortbar-label">Sort by</span>
  <?php foreach ($sortLinks as $key => $label):
      $isOn    = ($curSort === $key);
      // Counts and progress are most useful highest-first on the first click.
      $default = in_array($key, ['blocked', 'overdue', 'progress', 'updated'], true) ? 'desc' : 'asc';
      $nextDir = $isOn ? ($curDir === 'asc' ? 'desc' : 'asc') : $default;
  ?>
    <a class="sort-link <?= $isOn ? 'on' : '' ?>" href="<?= e(url_with(['sort' => $key, 'dir' => $nextDir])) ?>">
      <?= e($label) ?><?php if ($isOn): ?><span class="caret"><?= $curDir === 'asc' ? '▲' : '▼' ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <div class="empty">
    <h2>No practices match</h2>
    <p>
      <?php if ($filters['q'] || $filters['product_id'] || $filters['state'] || $filters['only_blocked'] || $filters['only_overdue'] || $filters['only_attention']): ?>
        Try clearing the filters above.
      <?php elseif ($is_admin): ?>
        Add your first practice to get started.
      <?php else: ?>
        No practices have been set up yet.
      <?php endif; ?>
    </p>
  </div>
<?php else: ?>

<div class="table-scroll">
<table class="grid dash-grid">
  <thead>
    <tr>
      <th class="c-name">Practice</th>
      <th class="c-products">Products</th>
      <th class="c-progress">Progress</th>
      <th class="c-num">Done</th>
      <th class="c-num">Left</th>
      <th class="c-num">Blocked</th>
      <th class="c-num">Overdue</th>
      <th class="c-golive">Target go-live</th>
      <th class="c-updated">Last updated</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r):
      $href = url('practice', ['id' => (int) $r['id']]);
      $rowCls = $r['needs_attention'] ? 'row-alert' : '';
      if ($r['onboarding_state'] === 'completed') { $rowCls .= ' row-done'; }
      if ($r['onboarding_state'] === 'on_hold')   { $rowCls .= ' row-hold'; }
      $days = $r['days_to_golive'];
  ?>
    <tr class="<?= trim($rowCls) ?>">
      <td class="c-name">
        <a class="practice-link" href="<?= e($href) ?>"><?= e($r['name']) ?></a>
        <span class="row-sub">
          <?php if (!empty($r['location'])): ?><?= e($r['location']) ?> · <?php endif; ?>
          <span class="state state-<?= e($r['onboarding_state']) ?>"><?= e(PRACTICE_STATES[$r['onboarding_state']] ?? '') ?></span>
          <?php if (!empty($r['is_archived'])): ?> · <span class="muted">Archived</span><?php endif; ?>
        </span>
      </td>

      <td class="c-products">
        <?php if (!$r['products']): ?>
          <span class="muted">No products selected</span>
        <?php else: ?>
          <?php foreach ($r['products'] as $pn): ?><span class="chip"><?= e($pn) ?></span><?php endforeach; ?>
        <?php endif; ?>
      </td>

      <td class="c-progress">
        <?php $pct = (int) $r['progress']; require APP_ROOT . '/templates/partials/progress.php'; ?>
      </td>

      <td class="c-num"><?= (int) $r['completed'] ?></td>
      <td class="c-num"><?= (int) $r['remaining'] ?></td>

      <td class="c-num">
        <?php if ((int) $r['blocked'] > 0): ?>
          <a class="pill pill-alert" href="<?= e(url('practice', ['id' => (int) $r['id'], 'blocked' => 1])) ?>"><?= (int) $r['blocked'] ?></a>
        <?php else: ?><span class="muted">0</span><?php endif; ?>
      </td>

      <td class="c-num">
        <?php if ((int) $r['overdue'] > 0): ?>
          <a class="pill pill-warn" href="<?= e(url('practice', ['id' => (int) $r['id'], 'overdue' => 1])) ?>"><?= (int) $r['overdue'] ?></a>
        <?php else: ?><span class="muted">0</span><?php endif; ?>
      </td>

      <td class="c-golive">
        <?= e(fmt_date($r['target_go_live_date'])) ?>
        <?php if ($days !== null && $r['onboarding_state'] !== 'completed'): ?>
          <span class="row-sub <?= $days < 0 ? 'v-alert' : ($days <= 14 ? 'v-warn' : '') ?>">
            <?php if ($days < 0): ?><?= abs($days) ?> days past
            <?php elseif ($days === 0): ?>today
            <?php else: ?>in <?= $days ?> days<?php endif; ?>
          </span>
        <?php endif; ?>
      </td>

      <td class="c-updated"><span class="muted"><?= e(fmt_ago($r['last_updated'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<p class="table-note">
  Progress counts Completed tasks against every task except those marked Not Applicable.
  A practice is flagged when it has a blocked task or an overdue one.
</p>

<?php endif; ?>
