<?php
/**
 * @var string $title
 * @var string $message
 */
$page_title = $title ?? 'Something went wrong';
?>
<div class="narrow">
  <section class="card">
    <h1 class="card-h"><?= e($title ?? 'Something went wrong') ?></h1>
    <p class="prose"><?= e($message ?? '') ?></p>
    <div class="form-actions">
      <a class="btn btn-quiet" href="<?= e(url('dashboard')) ?>">Back to dashboard</a>
    </div>
  </section>
</div>
