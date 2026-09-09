<?php
/**
 * Progress bar.
 * @var int    $pct
 * @var string $bar_label  optional text shown to the right
 * @var string $bar_size   '' or 'sm'
 */
$pct  = max(0, min(100, (int) ($pct ?? 0)));
$size = ($bar_size ?? '') === 'sm' ? ' bar-sm' : '';
$tone = $pct >= 100 ? ' bar-done' : '';
?>
<div class="bar-wrap<?= $size ?>">
  <div class="bar<?= $tone ?>" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
    <span style="width: <?= $pct ?>%"></span>
  </div>
  <span class="bar-num"><?= $pct ?>%</span>
  <?php if (!empty($bar_label)): ?><span class="bar-label"><?= e($bar_label) ?></span><?php endif; ?>
</div>
<?php unset($bar_label, $bar_size); ?>
