<?php
/**
 * Admin: task categories.
 * @var array $rows
 */
$page_title = 'Categories';
?>
<div class="page-head">
  <div>
    <h1>Task categories</h1>
    <p class="sub">Categories group tasks inside each product. A new category is available to every product immediately.</p>
  </div>
</div>

<section class="card">
  <h2 class="card-h">Add a category</h2>
  <form method="post" action="<?= e(url('category-save')) ?>" class="row-form">
    <?= Csrf::field() ?>
    <label class="field f-grow">
      <span>Name <em>required</em></span>
      <input type="text" name="name" required maxlength="120" placeholder="e.g. Data Migration">
    </label>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-sm">Add category</button>
    </div>
  </form>
</section>

<div class="edit-rows cols-category">
  <div class="edit-head">
    <span>Order</span><span>Name</span><span class="ta-c">Tasks</span><span class="ta-c">Active</span><span></span>
  </div>

  <?php foreach ($rows as $r): ?>
    <div class="edit-line <?= empty($r['is_active']) ? 'is-off' : '' ?>">
      <form method="post" action="<?= e(url('category-save')) ?>" class="edit-row">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">

        <label class="cell"><span class="cell-lab">Order</span>
          <input type="number" name="sort_order" value="<?= (int) $r['sort_order'] ?>" step="10"></label>

        <label class="cell"><span class="cell-lab">Name</span>
          <input type="text" name="name" value="<?= e($r['name']) ?>" required maxlength="120"></label>

        <span class="cell ta-c"><span class="cell-lab">Tasks</span><?= (int) $r['task_count'] ?></span>

        <label class="cell ta-c"><span class="cell-lab">Active</span>
          <input type="checkbox" name="is_active" value="1" <?= !empty($r['is_active']) ? 'checked' : '' ?>></label>

        <span class="cell cell-act"><button type="submit" class="btn btn-quiet btn-xs">Save</button></span>
      </form>

      <form method="post" action="<?= e(url('category-delete')) ?>" class="edit-row-side"
            data-confirm="Remove <?= e($r['name']) ?>? If tasks use it, it will be deactivated instead of deleted.">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <button type="submit" class="btn btn-danger btn-xs">Remove</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>
