<?php
/**
 * Sign in. One PIN box: the administrator PIN and each person's PIN
 * both go here, and the portal works out which it is.
 *
 * @var ?string $error
 * @var string  $next
 */
$page_title = 'Sign in';
?>
<div class="narrow">
  <section class="card">
    <h1 class="card-h">Sign in</h1>

    <?php if ($error): ?>
      <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <p class="muted">Enter your 6-digit PIN.</p>

    <form method="post" action="<?= e(url('login')) ?>" autocomplete="off">
      <?= Csrf::field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">

      <label class="field">
        <span>PIN <abbr class="req" title="Required">*</abbr></span>
        <input type="password" name="pin" required autofocus class="pin-input"
               inputmode="numeric" autocomplete="current-password" spellcheck="false">
      </label>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Sign in</button>
      </div>
    </form>

    <p class="table-note">Forgotten your PIN? Contact your administrator.</p>
  </section>
</div>
