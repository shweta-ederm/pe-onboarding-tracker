<?php
/**
 * Admin: people, who are also the user accounts.
 *
 * A person with a username and a PIN can sign in. They see everything
 * and may change only the tasks assigned to them. PINs are stored
 * hashed, so they can be replaced but never read back.
 *
 * @var array $rows
 */
$page_title = 'People';
?>
<div class="page-head">
  <div>
    <h1>People</h1>
    <p class="sub">
      Everyone here can be assigned tasks. Give someone a username and a PIN and they can also
      sign in, see everything, and update the tasks that are theirs.
    </p>
  </div>
</div>

<section class="card">
  <h2 class="card-h">Add a person</h2>
  <form method="post" action="<?= e(url('assignee-save')) ?>" class="row-form">
    <?= Csrf::field() ?>
    <label class="field"><span>First name <abbr class="req" title="Required">*</abbr></span>
      <input type="text" name="first_name" required maxlength="80"></label>
    <label class="field"><span>Last name</span>
      <input type="text" name="last_name" maxlength="80"></label>
    <label class="field"><span>Role</span>
      <input type="text" name="role_title" maxlength="120" placeholder="e.g. Implementation"></label>
    <label class="field"><span>Username</span>
      <input type="text" name="username" maxlength="60" autocapitalize="none"
             spellcheck="false" placeholder="e.g. jsmith"></label>
    <label class="field"><span>6-digit PIN</span>
      <input type="text" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
             placeholder="e.g. 481920" autocomplete="off"></label>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-sm">Add person</button>
    </div>
  </form>
  <p class="muted">
    Leave username and PIN blank for somebody who should be assignable but not able to sign in.
  </p>
</section>

<form method="post" action="<?= e(url('assignees-save-all')) ?>" id="gridform"><?= Csrf::field() ?></form>

<div class="edit-rows cols-person" data-grid>
  <div class="edit-head">
    <span>First name</span><span>Last name</span><span>Role</span>
    <span>Username</span><span class="ta-c">Sign-in</span><span class="ta-c">Active</span>
  </div>

  <?php foreach ($rows as $r): $id = (int) $r['id']; ?>
    <div class="edit-line <?= empty($r['is_active']) ? 'is-off' : '' ?>" data-row="<?= $id ?>">
      <div class="edit-row">
        <label class="cell"><span class="cell-lab">First name</span>
          <input form="gridform" type="text" maxlength="80" name="rows[<?= $id ?>][first_name]"
                 value="<?= e($r['first_name'] ?? '') ?>"></label>

        <label class="cell"><span class="cell-lab">Last name</span>
          <input form="gridform" type="text" maxlength="80" name="rows[<?= $id ?>][last_name]"
                 value="<?= e($r['last_name'] ?? '') ?>"></label>

        <label class="cell"><span class="cell-lab">Role</span>
          <input form="gridform" type="text" maxlength="120" name="rows[<?= $id ?>][role_title]"
                 value="<?= e($r['role_title'] ?? '') ?>"></label>

        <label class="cell"><span class="cell-lab">Username</span>
          <input form="gridform" type="text" maxlength="60" autocapitalize="none" spellcheck="false"
                 name="rows[<?= $id ?>][username]" value="<?= e($r['username'] ?? '') ?>"></label>

        <span class="cell ta-c"><span class="cell-lab">Sign-in</span>
          <?php if (!empty($r['username']) && !empty($r['pin_hash'])): ?>
            <span class="badge st-completed" title="<?= $r['last_login']
                ? 'Last signed in ' . e(fmt_ago($r['last_login'])) : 'Has never signed in' ?>">Yes</span>
          <?php elseif (!empty($r['username'])): ?>
            <span class="badge st-waiting" title="Has a username but no PIN">No PIN</span>
          <?php else: ?>
            <span class="badge st-not-started" title="Assignable, but cannot sign in">None</span>
          <?php endif; ?>
        </span>

        <label class="cell ta-c"><span class="cell-lab">Active</span>
          <input form="gridform" type="hidden" name="rows[<?= $id ?>][is_active]" value="0">
          <input form="gridform" type="checkbox" name="rows[<?= $id ?>][is_active]" value="1"
                 <?= !empty($r['is_active']) ? 'checked' : '' ?>></label>
      </div>

      <span class="edit-row-side">
        <?php /* Its own form, so setting a PIN never rides along with a
                 grid save and cannot be triggered by accident. */ ?>
        <form method="post" action="<?= e(url('assignee-pin')) ?>" class="pin-form" data-leaves-page>
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="text" name="pin" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                 placeholder="New PIN" autocomplete="off" aria-label="New PIN for <?= e($r['name']) ?>">
          <button type="submit" class="btn btn-quiet btn-xs">Set</button>
        </form>

        <form method="post" action="<?= e(url('assignee-delete')) ?>" class="inline-form" data-leaves-page
              data-confirm="Remove <?= e($r['name']) ?>? If they hold assignments, they will be deactivated instead of deleted.">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn btn-danger btn-xs">Remove</button>
        </form>
      </span>
    </div>
  <?php endforeach; ?>
</div>

<?php require APP_ROOT . '/templates/partials/savebar.php'; ?>

<p class="table-note">
  PINs are stored scrambled and cannot be read back, by you or by anyone else. If somebody forgets
  theirs, type a new one in their row and tell them what it is. People cannot change their own PIN.
</p>
<p class="table-note">
  Deactivating someone signs them out on their next click and removes them from the assignee
  dropdown, without touching the tasks already assigned to them.
</p>
<p class="table-note">Work contacts only. Do not record patient details here.</p>
