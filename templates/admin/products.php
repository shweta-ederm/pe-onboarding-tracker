<?php
/**
 * Admin: products, edited inline.
 *
 * Each row is its own <form>. A form cannot legally wrap table cells,
 * so these rows are CSS grid rather than a <table>. They also stack
 * cleanly on a phone this way.
 *
 * @var array $rows
 */
$page_title = 'Products';
?>
<div class="page-head">
  <div>
    <h1>Products</h1>
    <p class="sub">Practice Engine products available for onboarding. Deactivating one hides its tasks everywhere without deleting anything.</p>
  </div>
</div>

<section class="card">
  <h2 class="card-h">Add a product</h2>
  <form method="post" action="<?= e(url('product-save')) ?>" class="row-form">
    <?= Csrf::field() ?>
    <label class="field f-grow">
      <span>Name <em>required</em></span>
      <input type="text" name="name" required maxlength="120" placeholder="e.g. Patient Portal">
    </label>
    <label class="field f-grow">
      <span>Description</span>
      <input type="text" name="description" maxlength="500" placeholder="One line, shown when picking products">
    </label>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-sm">Add product</button>
    </div>
  </form>
</section>

<div class="edit-rows cols-product">
  <div class="edit-head">
    <span>Order</span><span>Name</span><span>Description</span>
    <span class="ta-c">Tasks</span><span class="ta-c">Practices</span><span class="ta-c">Active</span><span></span>
  </div>

  <?php foreach ($rows as $r): ?>
    <div class="edit-line <?= empty($r['is_active']) ? 'is-off' : '' ?>">
      <form method="post" action="<?= e(url('product-save')) ?>" class="edit-row">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">

        <label class="cell"><span class="cell-lab">Order</span>
          <input type="number" name="sort_order" value="<?= (int) $r['sort_order'] ?>" step="10"></label>

        <label class="cell"><span class="cell-lab">Name</span>
          <input type="text" name="name" value="<?= e($r['name']) ?>" required maxlength="120"></label>

        <label class="cell"><span class="cell-lab">Description</span>
          <input type="text" name="description" value="<?= e($r['description'] ?? '') ?>" maxlength="500"></label>

        <span class="cell ta-c"><span class="cell-lab">Tasks</span>
          <a href="<?= e(url('admin/tasks', ['product_id' => (int) $r['id']])) ?>"><?= (int) $r['task_count'] ?></a></span>

        <span class="cell ta-c"><span class="cell-lab">Practices</span><?= (int) $r['practice_count'] ?></span>

        <label class="cell ta-c"><span class="cell-lab">Active</span>
          <input type="checkbox" name="is_active" value="1" <?= !empty($r['is_active']) ? 'checked' : '' ?>></label>

        <span class="cell cell-act">
          <button type="submit" class="btn btn-quiet btn-xs">Save</button>
        </span>
      </form>

      <form method="post" action="<?= e(url('product-delete')) ?>" class="edit-row-side"
            data-confirm="Remove <?= e($r['name']) ?>? If any practice uses it, it will be deactivated instead of deleted.">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
        <button type="submit" class="btn btn-danger btn-xs">Remove</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<p class="table-note">Lower order numbers appear first. Leave gaps of ten so you can slot a product in between later.</p>
