<?php
/**
 * Status badge.
 * @var string $status
 */
?><span class="badge <?= e(status_class($status ?? 'not_started')) ?>"><?= e(status_label($status ?? 'not_started')) ?></span>
