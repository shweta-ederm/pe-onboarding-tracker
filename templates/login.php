<?php
/**
 * Admin sign in.
 * @var ?string $error
 * @var string  $next
 */
$page_title = 'Admin sign in';
?>
<div class="narrow">
  <section class="card">
    <h1 class="card-h">Admin sign in</h1>
    <p class="muted">
      The dashboard is readable without signing in. A PIN is only needed to make changes.
    </p>

    <?php if ($error): ?>
      <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('login')) ?>" autocomplete="off">
      <?= Csrf::field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <label class="field">
        <span>Admin PIN</span>
        <input type="password" name="pin" autocomplete="current-password" required autofocus
               inputmode="numeric" spellcheck="false">
      </label>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Sign in</button>
        <a class="btn btn-quiet" href="<?= e(url('dashboard')) ?>">Back to dashboard</a>
      </div>
    </form>
  </section>
</div>
