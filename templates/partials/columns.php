<?php
/**
 * Vertical column chart, inline SVG.
 *
 * Used where the categories are few and ordered, such as the onboarding
 * stages, where a left-to-right reading order carries meaning that a
 * horizontal bar list does not.
 *
 * @var array  $col_rows   each: label, value, color (optional), href (optional), sub (optional)
 * @var string $col_empty  message when there is nothing to plot
 */
$rows = $col_rows ?? [];
$max  = 0;
foreach ($rows as $r) {
    $max = max($max, (int) $r['value']);
}
?>
<?php if (!$rows || $max === 0): ?>
  <p class="muted"><?= e($col_empty ?? 'Nothing to plot yet.') ?></p>
<?php else: ?>
  <div class="cols" role="img"
       aria-label="<?php
         $parts = [];
         foreach ($rows as $r) { $parts[] = $r['label'] . ': ' . (int) $r['value']; }
         echo e(implode(', ', $parts));
       ?>">
    <?php foreach ($rows as $i => $r):
        $v = (int) $r['value'];
        $h = $max > 0 ? max(3, (int) round($v * 100 / $max)) : 0;
        $c = $r['color'] ?? null;
    ?>
      <div class="col" style="--i: <?= $i ?>">
        <span class="col-val"><?= $v ?></span>
        <div class="col-track">
          <?php if (!empty($r['href'])): ?><a href="<?= e($r['href']) ?>" class="col-link" aria-label="<?= e($r['label']) ?>: <?= $v ?>"><?php endif; ?>
            <span class="col-fill" style="height: <?= $h ?>%<?= $c ? '; background: ' . e($c) : '' ?>"
                  title="<?= e($r['label']) ?>: <?= $v ?>"></span>
          <?php if (!empty($r['href'])): ?></a><?php endif; ?>
        </div>
        <span class="col-label" title="<?= e($r['label']) ?>"><?= e($r['label']) ?></span>
        <?php if (!empty($r['sub'])): ?><span class="col-sub"><?= e($r['sub']) ?></span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php unset($col_rows, $col_empty); ?>
