<?php
/**
 * Shared page shell. Included by render() after the view variables are
 * extracted, so $__template and everything the view needs is in scope.
 *
 * @var string $__template
 * @var array  $config
 * @var bool   $is_admin
 * @var array  $flashes
 */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($page_title ?? 'Onboarding') ?> · <?= e($config['app_name']) ?></title>
<link rel="stylesheet" href="assets/app.css?v=<?= e(APP_VERSION) ?>">
</head>
<body>

<a class="skip-link" href="#main">Skip to content</a>

<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= e(url('dashboard')) ?>">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-text"><?= e($config['app_name']) ?></span>
    </a>

    <nav class="nav" aria-label="Main">
      <a href="<?= e(url('dashboard')) ?>" <?= ($template === 'dashboard') ? 'class="on" aria-current="page"' : '' ?>>Dashboard</a>
      <a href="<?= e(url('practices')) ?>" <?= ($template === 'practices' || str_starts_with($template, 'admin/practice')) ? 'class="on" aria-current="page"' : '' ?>>Practices</a>
      <a href="<?= e(url('tasks')) ?>" <?= ($template === 'tasks') ? 'class="on" aria-current="page"' : '' ?>>All tasks</a>
      <?php if ($is_admin): ?>
        <a href="<?= e(url('admin/tasks')) ?>" <?= ($template === 'admin/tasks') ? 'class="on"' : '' ?>>Task library</a>
        <a href="<?= e(url('admin/products')) ?>" <?= ($template === 'admin/products') ? 'class="on"' : '' ?>>Products</a>
        <a href="<?= e(url('admin/categories')) ?>" <?= ($template === 'admin/categories') ? 'class="on"' : '' ?>>Categories</a>
        <a href="<?= e(url('admin/assignees')) ?>" <?= ($template === 'admin/assignees') ? 'class="on"' : '' ?>>People</a>
        <a href="<?= e(url('admin/activity')) ?>" <?= ($template === 'admin/activity') ? 'class="on"' : '' ?>>Activity</a>
      <?php endif; ?>
    </nav>

    <div class="topbar-right">
      <?php if ($is_admin): ?>
        <span class="mode-pill mode-admin" title="You can edit everything">Admin</span>
        <a class="btn btn-quiet btn-sm" href="<?= e(url('logout')) ?>">Sign out</a>
      <?php else: ?>
        <span class="mode-pill mode-view" title="Nothing on this page can be edited">Read only</span>
        <a class="btn btn-quiet btn-sm" href="<?= e(url('login')) ?>">Admin sign in</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main id="main" class="wrap">

  <?php foreach ($flashes as $fl): ?>
    <div class="flash flash-<?= e($fl['type']) ?>" role="status"><?= e($fl['msg']) ?></div>
  <?php endforeach; ?>

  <?php require $__template; ?>

</main>

<footer class="foot">
  <p>
    Operational onboarding data only. Do not enter patient information anywhere in this tracker.
  </p>
</footer>

<script>
  window.POT = {
    csrf: <?= json_encode(Csrf::token()) ?>,
    isAdmin: <?= $is_admin ? 'true' : 'false' ?>,
    statuses: <?= json_encode(STATUSES) ?>
  };
</script>
<script src="assets/app.js?v=<?= e(APP_VERSION) ?>"></script>
</body>
</html>
