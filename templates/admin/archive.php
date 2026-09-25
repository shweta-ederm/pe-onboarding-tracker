<?php
/**
 * Admin: everything that has been put away.
 *
 * Archived practices and deactivated tasks are hidden from every other
 * screen and excluded from all dashboard figures. This is where they
 * can be restored, or destroyed for good.
 *
 * @var array $practices
 * @var array $tasks
 */
$page_title = 'Archive';
?>
<div class="page-head">
  <div>
    <h1>Archive</h1>
    <p class="sub">
      Archived practices and deactivated tasks. Nothing here appears anywhere else in the portal,
      and none of it counts towards any figure on the dashboard.
    </p>
  </div>
</div>

<section class="card">
  <h2 class="card-h">Archived practices<span class="tab-n"><?= count($practices) ?></span></h2>

  <?php if (!$practices): ?>
    <p class="muted">No archived practices.</p>
  <?php else: ?>
    <div class="table-scroll">
    <table class="grid">
      <thead><tr><th>Practice</th><th>Products</th><th>Progress when archived</th><th class="c-act">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($practices as $r): ?>
          <tr>
            <td class="c-name">
              <a href="<?= e(url('practice', ['id' => (int) $r['id']])) ?>"><?= e($r['name']) ?></a>
              <?php if (!empty($r['location'])): ?><span class="row-sub"><?= e($r['location']) ?></span><?php endif; ?>
            </td>
            <td class="c-products">
              <?php if (!$r['products']): ?><span class="muted">None</span>
              <?php else: foreach ($r['products'] as $p): ?>
                <span class="chip">
                  <?php if (!empty($p['color'])): ?><span class="chip-dot" style="background: <?= e($p['color']) ?>"></span><?php endif; ?>
                  <?= e($p['name']) ?></span>
              <?php endforeach; endif; ?>
            </td>
            <td class="c-progress"><?php $pct = (int) $r['progress']; $bar_size = 'sm'; require APP_ROOT . '/templates/partials/progress.php'; ?></td>
            <td class="c-act">
              <form method="post" action="<?= e(url('practice-restore')) ?>" class="inline-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="btn btn-quiet btn-xs">Restore</button>
              </form>
              <form method="post" action="<?= e(url('purge')) ?>" class="purge-form"
                    data-confirm="Permanently delete <?= e($r['name']) ?> and every task status recorded against it? This cannot be undone.">
                <?= Csrf::field() ?>
                <input type="hidden" name="kind" value="practice">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="password" name="pin" placeholder="Admin PIN" autocomplete="off"
                       aria-label="Administrator PIN to delete <?= e($r['name']) ?>" required>
                <button type="submit" class="btn btn-danger btn-xs">Delete for good</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2 class="card-h">Deactivated tasks<span class="tab-n"><?= count($tasks) ?></span></h2>

  <?php if (!$tasks): ?>
    <p class="muted">No deactivated tasks.</p>
  <?php else: ?>
    <div class="table-scroll">
    <table class="grid">
      <thead><tr><th>Task</th><th>Product</th><th>Category</th><th class="c-num">Recorded against</th><th class="c-act">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($tasks as $r): ?>
          <tr>
            <td class="c-task"><?= e($r['name']) ?></td>
            <td><span class="chip">
              <?php if (!empty($r['product_color'])): ?><span class="chip-dot" style="background: <?= e($r['product_color']) ?>"></span><?php endif; ?>
              <?= e($r['product_name']) ?></span></td>
            <td><span class="muted"><?= e($r['category_name']) ?></span></td>
            <td class="c-num"><?= (int) $r['state_count'] ?> practice<?= (int) $r['state_count'] === 1 ? '' : 's' ?></td>
            <td class="c-act">
              <form method="post" action="<?= e(url('task-reactivate')) ?>" class="inline-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="btn btn-quiet btn-xs">Restore</button>
              </form>
              <form method="post" action="<?= e(url('purge')) ?>" class="purge-form"
                    data-confirm="Permanently delete &quot;<?= e($r['name']) ?>&quot; and the status recorded against it for <?= (int) $r['state_count'] ?> practice(s)? This cannot be undone.">
                <?= Csrf::field() ?>
                <input type="hidden" name="kind" value="task">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="password" name="pin" placeholder="Admin PIN" autocomplete="off"
                       aria-label="Administrator PIN to delete <?= e($r['name']) ?>" required>
                <button type="submit" class="btn btn-danger btn-xs">Delete for good</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</section>

<p class="table-note">
  Restoring puts something back exactly as it was, with its recorded history intact. Deleting for
  good removes the record and everything recorded against it, which is why it asks for your PIN
  rather than only a confirmation box.
</p>
