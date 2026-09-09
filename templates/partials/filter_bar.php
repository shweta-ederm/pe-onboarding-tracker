<?php
/**
 * Filter bar. The caller decides which controls appear by setting
 * $show, and supplies whichever option lists those controls need.
 *
 * @var array  $filters
 * @var array  $show        e.g. ['q','practice','product','category','status','assignee','flags','state']
 * @var string $filter_page route to submit to
 * @var array  $practices   optional
 * @var array  $products    optional
 * @var array  $categories  optional
 * @var array  $assignees   optional
 */
$show = $show ?? [];
$has  = static fn(string $k): bool => in_array($k, $show, true);

// Anything not represented by a control is carried through so the sort
// order and the current practice survive a filter change.
$carry = ['sort' => $_GET['sort'] ?? null, 'dir' => $_GET['dir'] ?? null, 'id' => $_GET['id'] ?? null];
?>
<form class="filters" method="get" action="index.php">
  <input type="hidden" name="p" value="<?= e($filter_page) ?>">
  <?php foreach ($carry as $k => $v): if ($v !== null && $v !== '') : ?>
    <input type="hidden" name="<?= e($k) ?>" value="<?= e((string) $v) ?>">
  <?php endif; endforeach; ?>

  <?php if ($has('q')): ?>
    <label class="f-field f-grow">
      <span>Search</span>
      <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Practice or task name">
    </label>
  <?php endif; ?>

  <?php if ($has('practice') && !empty($practices)): ?>
    <label class="f-field">
      <span>Practice</span>
      <select name="practice_id">
        <option value="">All practices</option>
        <?php foreach ($practices as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= ((int) ($filters['practice_id'] ?? 0) === (int) $p['id']) ? 'selected' : '' ?>>
            <?= e($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <?php if ($has('product') && !empty($products)): ?>
    <label class="f-field">
      <span>Product</span>
      <select name="product_id">
        <option value="">All products</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= ((int) ($filters['product_id'] ?? 0) === (int) $p['id']) ? 'selected' : '' ?>>
            <?= e($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <?php if ($has('category') && !empty($categories)): ?>
    <label class="f-field">
      <span>Category</span>
      <select name="category_id">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= ((int) ($filters['category_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <?php if ($has('status')): ?>
    <label class="f-field">
      <span>Status</span>
      <select name="status">
        <option value="">Any status</option>
        <?php foreach (STATUSES as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= (($filters['status'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <?php if ($has('assignee') && !empty($assignees)): ?>
    <label class="f-field">
      <span>Assignee</span>
      <select name="assignee_id">
        <option value="">Anyone</option>
        <option value="none" <?= (($filters['assignee_id'] ?? '') === 'none') ? 'selected' : '' ?>>Unassigned</option>
        <?php foreach ($assignees as $a): ?>
          <option value="<?= (int) $a['id'] ?>" <?= ((int) ($filters['assignee_id'] ?? 0) === (int) $a['id']) ? 'selected' : '' ?>>
            <?= e($a['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <?php if ($has('state')): ?>
    <label class="f-field">
      <span>Onboarding</span>
      <select name="state">
        <option value="">Any state</option>
        <?php foreach (PRACTICE_STATES as $k => $label): ?>
          <option value="<?= e($k) ?>" <?= (($filters['state'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  <?php endif; ?>

  <?php if ($has('flags')): ?>
    <div class="f-field f-flags">
      <span>Only show</span>
      <div class="f-checks">
        <label><input type="checkbox" name="blocked" value="1" <?= !empty($filters['only_blocked']) ? 'checked' : '' ?>> Blocked</label>
        <label><input type="checkbox" name="overdue" value="1" <?= !empty($filters['only_overdue']) ? 'checked' : '' ?>> Overdue</label>
        <?php if ($has('attention')): ?>
          <label><input type="checkbox" name="attention" value="1" <?= !empty($filters['only_attention']) ? 'checked' : '' ?>> Needs attention</label>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="f-actions">
    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    <a class="btn btn-quiet btn-sm" href="<?= e(url($filter_page, array_filter(['id' => $_GET['id'] ?? null]))) ?>">Clear</a>
  </div>
</form>
