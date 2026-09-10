<?php
/**
 * Dashboard: where the work sits right now.
 *
 * The practice list lives on its own page. This answers the questions
 * you cannot answer by scanning a table: who is carrying what, which
 * product is soaking up effort, which stage everything is stuck at.
 *
 * @var array $totals
 * @var array $by_status
 * @var array $by_assignee
 * @var array $by_product
 * @var array $by_category
 * @var array $upcoming
 * @var array $stalled
 * @var bool  $is_admin
 */
$page_title = 'Dashboard';

$openTotal = 0;
foreach (['not_started', 'in_progress', 'waiting', 'blocked'] as $k) {
    $openTotal += (int) ($by_status[$k] ?? 0);
}
$statusTotal = array_sum(array_map('intval', $by_status));
?>

<div class="page-head">
  <div>
    <h1>Dashboard</h1>
    <p class="sub">
      <?= (int) $totals['practices'] ?> active practice<?= $totals['practices'] === 1 ? '' : 's' ?>,
      <?= $openTotal ?> task<?= $openTotal === 1 ? '' : 's' ?> still open.
      <?php if ($totals['attention'] > 0): ?>
        <strong><?= (int) $totals['attention'] ?></strong> practice<?= $totals['attention'] === 1 ? '' : 's' ?> need attention.
      <?php else: ?>
        Nothing is blocked or overdue.
      <?php endif; ?>
    </p>
  </div>
  <div class="page-actions">
    <a class="btn btn-quiet" href="<?= e(url('practices')) ?>">All practices</a>
    <?php if ($is_admin): ?>
      <a class="btn btn-primary" href="<?= e(url('admin/practice-form')) ?>">Add practice</a>
    <?php endif; ?>
  </div>
</div>

<section class="stat-strip" aria-label="Summary">
  <div class="stat">
    <span class="stat-label">Overall progress</span>
    <span class="stat-value"><?= (int) $totals['progress'] ?>%</span>
    <span class="stat-note"><?= (int) $totals['completed'] ?> of <?= (int) $totals['countable'] ?> tasks done</span>
  </div>
  <div class="stat">
    <span class="stat-label">Needs attention</span>
    <span class="stat-value <?= $totals['attention'] > 0 ? 'v-alert' : '' ?>"><?= (int) $totals['attention'] ?></span>
    <span class="stat-note">
      <?php if ($totals['attention'] > 0): ?>
        <a href="<?= e(url('practices', ['attention' => 1])) ?>">See which</a>
      <?php else: ?>all practices healthy<?php endif; ?>
    </span>
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

<?php if ($statusTotal > 0): ?>
<section class="card">
  <h2 class="card-h">Where everything stands</h2>
  <div class="mixbar" role="img"
       aria-label="<?php
         $parts = [];
         foreach (STATUSES as $k => $lab) {
             if ((int) ($by_status[$k] ?? 0) > 0) { $parts[] = $by_status[$k] . ' ' . $lab; }
         }
         echo e(implode(', ', $parts));
       ?>">
    <?php foreach (STATUSES as $k => $label):
        $n = (int) ($by_status[$k] ?? 0);
        if ($n === 0) { continue; }
        $w = round($n * 100 / $statusTotal, 2);
    ?>
      <span class="mixbar-seg <?= e(status_class($k)) ?>" style="width: <?= $w ?>%"
            title="<?= e($label) ?>: <?= $n ?>"></span>
    <?php endforeach; ?>
  </div>
  <ul class="mixbar-key">
    <?php foreach (STATUSES as $k => $label):
        $n = (int) ($by_status[$k] ?? 0);
        if ($n === 0) { continue; }
    ?>
      <li>
        <span class="key-dot <?= e(status_class($k)) ?>"></span>
        <a href="<?= e(url('tasks', ['status' => $k])) ?>"><?= e($label) ?></a>
        <b><?= $n ?></b>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<div class="split">
  <section class="card">
    <h2 class="card-h">Open tasks by person</h2>
    <?php
      $chart_rows = array_map(static fn($r) => $r + [
          'href' => url('tasks', ['assignee_id' => $r['assignee_id'] ?: 'none']),
      ], $by_assignee);
      $chart_empty = 'Nothing open. Either everything is done, or no products are selected yet.';
      require APP_ROOT . '/templates/partials/bar_chart.php';
    ?>
    <p class="table-note">Counts exclude completed work and anything marked Not Applicable.</p>
  </section>

  <section class="card">
    <h2 class="card-h">Open tasks by product</h2>
    <?php
      $chart_rows = array_map(static fn($r) => $r + [
          'href' => url('tasks', ['product_id' => (int) $r['product_id']]),
      ], $by_product);
      $chart_empty = 'No products have open work.';
      require APP_ROOT . '/templates/partials/bar_chart.php';
    ?>
  </section>
</div>

<div class="split">
  <section class="card">
    <h2 class="card-h">Open tasks by stage</h2>
    <?php
      $chart_rows = array_map(static fn($r) => $r + [
          'href' => url('tasks', ['category_id' => (int) $r['category_id']]),
      ], $by_category);
      $chart_empty = 'No open work in any category.';
      require APP_ROOT . '/templates/partials/bar_chart.php';
    ?>
    <p class="table-note">A pile-up in one stage usually means a bottleneck rather than a busy team.</p>
  </section>

  <section class="card">
    <h2 class="card-h">Going live soon</h2>
    <?php if (!$upcoming): ?>
      <p class="muted">Nothing has a target go-live date in the next 60 days.</p>
    <?php else: ?>
      <ul class="mini-list">
        <?php foreach ($upcoming as $u):
            $pctDone = progress_pct((int) $u['completed'], (int) $u['countable']);
            $away    = (int) $u['days_away'];
        ?>
          <li>
            <div class="mini-row">
              <a class="mini-name" href="<?= e(url('practice', ['id' => (int) $u['id']])) ?>">
                <?= e($u['name']) ?>
              </a>
              <span class="mini-meta <?= $away < 0 ? 'v-alert' : ($away <= 14 ? 'v-warn' : '') ?>">
                <?php if ($away < 0): ?><?= abs($away) ?>d past
                <?php elseif ($away === 0): ?>today
                <?php else: ?><?= $away ?>d away<?php endif; ?>
              </span>
            </div>
            <?php $pct = $pctDone; $bar_size = 'sm'; require APP_ROOT . '/templates/partials/progress.php'; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<?php if ($stalled): ?>
<section class="card">
  <h2 class="card-h">Quiet for a while</h2>
  <p class="muted">
    Active practices nothing has been recorded against recently. These raise no flags, which is
    exactly why they are easy to lose track of.
  </p>
  <ul class="quiet-list">
    <?php foreach ($stalled as $s): ?>
      <li>
        <a href="<?= e(url('practice', ['id' => (int) $s['id']])) ?>"><?= e($s['name']) ?></a>
        <span class="muted"><?= (int) $s['quiet_days'] ?> days</span>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
