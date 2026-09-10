<?php
/**
 * Admin: people who can be assigned tasks. One Save button for the table.
 * @var array $rows
 */
$page_title = 'People';
?>
<div class="page-head">
  <div>
    <h1>People</h1>
    <p class="sub">Anyone here can be picked from the assignee dropdown on a task. Deactivating someone removes them from the dropdown without touching their existing assignments.</p>
  </div>
</div>

<section class="card">
  <h2 class="card-h">Add a person</h2>
  <form method="post" action="<?= e(url('assignee-save')) ?>" class="row-form">
    <?= Csrf::field() ?>
    <label class="field f-grow"><span>Name <abbr class="req" title="Required">*</abbr></span>
      <input type="text" name="name" required maxlength="120"></label>
    <label class="field"><span>Role</span>
      <input type="text" name="role_title" maxlength="120" placeholder="e.g. Implementation"></label>
    <label class="field f-grow"><span>Work email</span>
      <input type="email" name="email" maxlength="190"></label>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-sm">Add person</button>
    </div>
  </form>
</section>

<form method="post" action="<?= e(url('assignees-save-all')) ?>" id="gridform"><?= Csrf::field() ?></form>

<div class="edit-rows cols-person" data-grid>
  <div class="edit-head">
    <span>Name</span><span>Role</span><span>Work email</span>
    <span class="ta-c">Assigned</span><span class="ta-c">Active</span>
  </div>

  <?php foreach ($rows as $r): $id = (int) $r['id']; ?>
    <div class="edit-line <?= empty($r['is_active']) ? 'is-off' : '' ?>" data-row="<?= $id ?>">
      <div class="edit-row">
        <label class="cell"><span class="cell-lab">Name</span>
          <input form="gridform" type="text" maxlength="120" name="rows[<?= $id ?>][name]"
                 value="<?= e($r['name']) ?>"></label>

        <label class="cell"><span class="cell-lab">Role</span>
          <input form="gridform" type="text" maxlength="120" name="rows[<?= $id ?>][role_title]"
                 value="<?= e($r['role_title'] ?? '') ?>"></label>

        <label class="cell"><span class="cell-lab">Work email</span>
          <input form="gridform" type="email" maxlength="190" name="rows[<?= $id ?>][email]"
                 value="<?= e($r['email'] ?? '') ?>"></label>

        <span class="cell ta-c"><span class="cell-lab">Assigned</span><?= (int) $r['assigned_count'] ?></span>

        <label class="cell ta-c"><span class="cell-lab">Active</span>
          <input form="gridform" type="hidden" name="rows[<?= $id ?>][is_active]" value="0">
          <input form="gridform" type="checkbox" name="rows[<?= $id ?>][is_active]" value="1"
                 <?= !empty($r['is_active']) ? 'checked' : '' ?>></label>
      </div>

      <form method="post" action="<?= e(url('assignee-delete')) ?>" class="edit-row-side"
            data-confirm="Remove <?= e($r['name']) ?>? If they hold assignments, they will be deactivated instead of deleted.">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="btn btn-danger btn-xs">Remove</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<?php require APP_ROOT . '/templates/partials/savebar.php'; ?>

<p class="table-note">Work contacts only. Do not record patient details here.</p>
