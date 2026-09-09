<?php
/**
 * Admin: list of every practice, archived ones included.
 * @var array $rows
 */
$page_title = 'Practices';
?>
<div class="page-head">
  <div>
    <h1>Practices</h1>
    <p class="sub">Add a practice, then choose which products it is onboarding. Its task list builds itself from that.</p>
  </div>
  <div class="page-actions">
    <a class="btn btn-primary" href="<?= e(url('admin/practice-form')) ?>">Add practice</a>
  </div>
</div>

<?php if (!$rows): ?>
  <div class="empty"><h2>No practices yet</h2><p>Add your first one to get started.</p></div>
<?php else: ?>
<div class="table-scroll">
<table class="grid">
  <thead>
    <tr>
      <th>Practice</th><th>Products</th><th>Progress</th>
      <th>State</th><th>Target go-live</th><th class="c-act">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= !empty($r['is_archived']) ? 'row-muted' : '' ?>">
      <td class="c-name">
        <a href="<?= e(url('practice', ['id' => (int) $r['id']])) ?>"><?= e($r['name']) ?></a>
        <?php if (!empty($r['location'])): ?><span class="row-sub"><?= e($r['location']) ?></span><?php endif; ?>
      </td>
      <td class="c-products">
        <?php if (!$r['products']): ?><span class="muted">None selected</span>
        <?php else: foreach ($r['products'] as $pn): ?><span class="chip"><?= e($pn) ?></span><?php endforeach; endif; ?>
      </td>
      <td class="c-progress"><?php $pct = (int) $r['progress']; $bar_size = 'sm'; require APP_ROOT . '/templates/partials/progress.php'; ?></td>
      <td>
        <span class="state state-<?= e($r['onboarding_state']) ?>"><?= e(PRACTICE_STATES[$r['onboarding_state']] ?? '') ?></span>
        <?php if (!empty($r['is_archived'])): ?><span class="row-sub muted">Archived</span><?php endif; ?>
      </td>
      <td><?= e(fmt_date($r['target_go_live_date'])) ?></td>
      <td class="c-act">
        <a class="btn btn-quiet btn-xs" href="<?= e(url('admin/practice-form', ['id' => (int) $r['id']])) ?>">Edit</a>
        <?php if (empty($r['is_archived'])): ?>
          <form method="post" action="<?= e(url('practice-delete')) ?>" class="inline-form"
                data-confirm="Archive <?= e($r['name']) ?>? It disappears from the dashboard but nothing is deleted.">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-quiet btn-xs">Archive</button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= e(url('practice-delete')) ?>" class="inline-form"
              data-confirm="Permanently delete <?= e($r['name']) ?> and every task status recorded for it? This cannot be undone.">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <input type="hidden" name="hard" value="1">
          <button type="submit" class="btn btn-danger btn-xs">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
