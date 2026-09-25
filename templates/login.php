<?php
/**
 * Sign in. Team members use a username and PIN; the administrator uses
 * the PIN from config/config.php on its own.
 *
 * @var ?string $error
 * @var string  $next
 * @var string  $mode  'member' or 'admin'
 */
$page_title = 'Sign in';
$mode = ($mode ?? 'member') === 'admin' ? 'admin' : 'member';
?>
<div class="narrow">
  <section class="card">
    <h1 class="card-h"><?= $mode === 'admin' ? 'Administrator sign in' : 'Sign in' ?></h1>

    <?php if ($error): ?>
      <div class="flash flash-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($mode === 'member'): ?>
      <p class="muted">Use the username and 6-digit PIN your administrator gave you.</p>

      <form method="post" action="<?= e(url('login')) ?>" autocomplete="off">
        <?= Csrf::field() ?>
        <input type="hidden" name="mode" value="member">
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <label class="field">
          <span>Username <abbr class="req" title="Required">*</abbr></span>
          <input type="text" name="username" required autofocus
                 autocapitalize="none" autocorrect="off" spellcheck="false">
        </label>

        <label class="field">
          <span>PIN <abbr class="req" title="Required">*</abbr></span>
          <input type="password" name="pin" required inputmode="numeric"
                 pattern="[0-9]*" maxlength="6" autocomplete="current-password">
        </label>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Sign in</button>
        </div>
      </form>

      <p class="table-note">
        Forgotten your PIN? Your administrator can set a new one for you on the People screen.
        Nobody can look up your existing PIN, including them.
      </p>
      <p class="table-note">
        <a href="<?= e(url('login', ['mode' => 'admin', 'next' => $next])) ?>">Administrator sign in</a>
      </p>

    <?php else: ?>
      <p class="muted">Enter the administrator PIN.</p>

      <form method="post" action="<?= e(url('login')) ?>" autocomplete="off">
        <?= Csrf::field() ?>
        <input type="hidden" name="mode" value="admin">
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <label class="field">
          <span>Administrator PIN <abbr class="req" title="Required">*</abbr></span>
          <input type="password" name="pin" required autofocus
                 autocomplete="current-password" spellcheck="false">
        </label>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Sign in</button>
        </div>
      </form>

      <p class="table-note">
        <a href="<?= e(url('login', ['next' => $next])) ?>">Sign in as a team member instead</a>
      </p>
    <?php endif; ?>
  </section>
</div>
