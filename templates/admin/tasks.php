<?php
/**
 * Admin: the global task library for one product.
 *
 * A task added here appears in every practice onboarding this product,
 * as Not Started, with no further action needed.
 *
 * @var array  $products
 * @var int    $product_id
 * @var ?array $product
 * @var array  $categories
 * @var array  $rows
 * @var bool   $include_inactive
 */
$page_title = 'Task library';
?>
<div class="page-head">
  <div>
    <h1>Task library</h1>
    <p class="sub">
      These are global task definitions. Add one and it appears for every practice onboarding
      that product, tracked separately per practice.
    </p>
  </div>
</div>

<nav class="tabs" aria-label="Products">
  <?php foreach ($products as $p): ?>
    <a class="tab <?= ((int) $p['id'] === $product_id) ? 'on' : '' ?>"
       href="<?= e(url('admin/tasks', ['product_id' => (int) $p['id']])) ?>">
      <?= e($p['name']) ?>
      <span class="tab-n"><?= (int) $p['task_count'] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if (!$product): ?>
  <div class="empty">
    <h2>No products yet</h2>
    <p><a href="<?= e(url('admin/products')) ?>">Add a product</a> before defining tasks.</p>
  </div>
<?php else: ?>

<section class="card">
  <h2 class="card-h">Add a task to <?= e($product['name']) ?></h2>
  <form method="post" action="<?= e(url('task-save')) ?>" class="row-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="product_id" value="<?= $product_id ?>">
    <input type="hidden" name="is_active" value="1">

    <label class="field f-grow">
      <span>Task name <em>required</em></span>
      <input type="text" name="name" required maxlength="200" placeholder="e.g. Configure SMS number">
    </label>

    <label class="field">
      <span>Category <em>required</em></span>
      <select name="category_id" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="field f-grow">
      <span>Description</span>
      <input type="text" name="description" maxlength="500" placeholder="Optional detail shown under the task name">
    </label>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-sm">Add task</button>
    </div>
  </form>
  <p class="muted">
    It will land at the bottom of <?= e($product['name']) ?>'s list. Use the arrows to move it.
  </p>
</section>

<div class="toolbar">
  <span class="toolbar-label"><?= count($rows) ?> task<?= count($rows) === 1 ? '' : 's' ?></span>
  <a class="btn btn-quiet btn-sm"
     href="<?= e(url('admin/tasks', ['product_id' => $product_id, 'inactive' => $include_inactive ? null : 1])) ?>">
    <?= $include_inactive ? 'Hide deactivated' : 'Show deactivated' ?>
  </a>
  <form method="post" action="<?= e(url('tasks-renumber')) ?>" class="inline-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="product_id" value="<?= $product_id ?>">
    <button type="submit" class="btn btn-quiet btn-sm">Tidy order numbers</button>
  </form>
</div>

<?php if (!$rows): ?>
  <div class="empty"><h3>No tasks yet</h3><p>Add the first one above.</p></div>
<?php else: ?>

<div class="edit-rows cols-task">
  <div class="edit-head">
    <span>Move</span><span>Task</span><span>Category</span><span>Description</span>
    <span class="ta-c">Active</span><span></span>
  </div>

  <?php foreach ($rows as $r): ?>
    <div class="edit-line <?= empty($r['is_active']) ? 'is-off' : '' ?>">
      <span class="cell cell-move">
        <form method="post" action="<?= e(url('task-move')) ?>" class="inline-form">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <input type="hidden" name="dir" value="up">
          <button type="submit" class="btn btn-icon" title="Move up" aria-label="Move <?= e($r['name']) ?> up">↑</button>
        </form>
        <form method="post" action="<?= e(url('task-move')) ?>" class="inline-form">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <input type="hidden" name="dir" value="down">
          <button type="submit" class="btn btn-icon" title="Move down" aria-label="Move <?= e($r['name']) ?> down">↓</button>
        </form>
      </span>

      <form method="post" action="<?= e(url('task-save')) ?>" class="edit-row edit-row-task">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <input type="hidden" name="product_id" value="<?= (int) $r['product_id'] ?>">

        <label class="cell"><span class="cell-lab">Task</span>
          <input type="text" name="name" value="<?= e($r['name']) ?>" required maxlength="200"></label>

        <label class="cell"><span class="cell-lab">Category</span>
          <select name="category_id">
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= ((int) $r['category_id'] === (int) $c['id']) ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select></label>

        <label class="cell"><span class="cell-lab">Description</span>
          <input type="text" name="description" value="<?= e($r['description'] ?? '') ?>" maxlength="500"></label>

        <label class="cell ta-c"><span class="cell-lab">Active</span>
          <input type="checkbox" name="is_active" value="1" <?= !empty($r['is_active']) ? 'checked' : '' ?>></label>

        <span class="cell cell-act"><button type="submit" class="btn btn-quiet btn-xs">Save</button></span>
      </form>

      <span class="edit-row-side">
        <?php if (!empty($r['is_active'])): ?>
          <form method="post" action="<?= e(url('task-delete')) ?>" class="inline-form"
                data-confirm="Deactivate &quot;<?= e($r['name']) ?>&quot;? It disappears from every practice, and any status already recorded is kept.">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-quiet btn-xs">Deactivate</button>
          </form>
        <?php else: ?>
          <form method="post" action="<?= e(url('task-reactivate')) ?>" class="inline-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn btn-quiet btn-xs">Reactivate</button>
          </form>
        <?php endif; ?>
        <form method="post" action="<?= e(url('task-delete')) ?>" class="inline-form"
              data-confirm="Permanently delete &quot;<?= e($r['name']) ?>&quot; and the status recorded against it for every practice? This cannot be undone.">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <input type="hidden" name="hard" value="1">
          <button type="submit" class="btn btn-danger btn-xs">Delete</button>
        </form>
      </span>
    </div>
  <?php endforeach; ?>
</div>

<p class="table-note">
  Deactivate rather than delete when a task is simply no longer part of the process. Deleting
  also removes the status, assignee, due date, and notes recorded against it for every practice.
</p>

<?php endif; ?>
<?php endif; ?>
