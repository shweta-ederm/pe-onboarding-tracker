<?php
/**
 * Donut chart, drawn as inline SVG.
 *
 * A circle of circumference 100 makes each slice's dash length equal to
 * its percentage, so no trigonometry is needed and the markup stays
 * readable. No charting library, so nothing to load and it prints.
 *
 * @var array  $donut_rows   each: label, value, color
 * @var string $donut_centre big number in the middle
 * @var string $donut_sub    small caption under it
 * @var string $donut_empty  message when every value is zero
 */
$rows  = array_values(array_filter($donut_rows ?? [], static fn($r) => (int) $r['value'] > 0));
$total = 0;
foreach ($rows as $r) {
    $total += (int) $r['value'];
}
$uid = 'd' . substr(md5(serialize($rows) . mt_rand()), 0, 6);
?>
<?php if ($total === 0): ?>
  <p class="muted"><?= e($donut_empty ?? 'Nothing to show yet.') ?></p>
<?php else: ?>
  <div class="donut-wrap">
    <svg class="donut" viewBox="0 0 42 42" role="img"
         aria-label="<?php
           $parts = [];
           foreach ($rows as $r) { $parts[] = $r['value'] . ' ' . $r['label']; }
           echo e(implode(', ', $parts));
         ?>">
      <circle class="donut-hole" cx="21" cy="21" r="15.915"></circle>
      <?php
        $offset = 25; // start at twelve o'clock
        foreach ($rows as $i => $r):
            $pct = ($r['value'] * 100) / $total;
      ?>
        <circle class="donut-seg" cx="21" cy="21" r="15.915"
                stroke="<?= e($r['color']) ?>"
                stroke-dasharray="<?= round($pct, 3) ?> <?= round(100 - $pct, 3) ?>"
                stroke-dashoffset="<?= round($offset, 3) ?>"
                style="--i: <?= $i ?>">
          <title><?= e($r['label']) ?>: <?= (int) $r['value'] ?></title>
        </circle>
      <?php
            // Each slice starts where the previous one ended.
            $offset = fmod($offset - $pct + 100, 100);
        endforeach;
      ?>
    </svg>

    <?php if (!empty($donut_centre)): ?>
      <div class="donut-centre">
        <span class="donut-num"><?= e($donut_centre) ?></span>
        <?php if (!empty($donut_sub)): ?><span class="donut-sub"><?= e($donut_sub) ?></span><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <ul class="donut-key">
    <?php foreach ($rows as $r): ?>
      <li>
        <span class="key-swatch" style="background: <?= e($r['color']) ?>"></span>
        <?php if (!empty($r['href'])): ?>
          <a href="<?= e($r['href']) ?>"><?= e($r['label']) ?></a>
        <?php else: ?>
          <span><?= e($r['label']) ?></span>
        <?php endif; ?>
        <b><?= (int) $r['value'] ?></b>
        <span class="key-pct"><?= round(($r['value'] * 100) / $total) ?>%</span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php unset($donut_rows, $donut_centre, $donut_sub, $donut_empty); ?>
