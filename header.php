<?php
$uid = current_user_id();
$uname = $_SESSION['user_name'] ?? '';
$flash = get_flash();
$active = basename($_SERVER['PHP_SELF']);
$unread = 0;
if ($uid) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM inquiries WHERE artisan_id = ? AND is_read = 0');
    $st->execute([$uid]);
    $unread = (int)$st->fetchColumn();
}
$links = [['index.php', 'Storefront', 'store']];
if ($uid) {
    $links[] = ['dashboard.php', 'Inventory', 'grid'];
    $links[] = ['product_add.php', 'Add product', 'plus'];
    $links[] = ['inbox.php', 'Inbox', 'inbox'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Storefront') ?> | LikasPinas</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'ui-sans-serif', 'system-ui', 'sans-serif'] } } } };
</script>
</head>
<body class="bg-zinc-100 text-zinc-800 font-sans min-h-screen flex flex-col">

<header class="bg-red-800 text-white sticky top-0 z-30">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2 font-semibold text-xl">
      <span class="bg-amber-400 text-red-900 rounded-md p-1.5"><?= icon('store', 'w-5 h-5') ?></span>LikasPinas
    </a>
    <nav class="hidden md:flex items-center gap-1 text-sm" aria-label="Main">
      <?php foreach ($links as [$href, $label, $ic]): ?>
        <a href="<?= e($href) ?>" <?= $active === $href ? 'aria-current="page"' : '' ?> class="px-3 py-2 rounded-md flex items-center gap-2 <?= $active === $href ? 'bg-red-900' : 'hover:bg-red-700' ?>">
          <?= icon($ic, 'w-4 h-4') ?><?= e($label) ?>
          <?php if ($href === 'inbox.php' && $unread): ?><span class="bg-amber-400 text-red-900 text-xs font-bold rounded-full px-2"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
      <?php if ($uid): ?>
        <span class="px-3 py-2 flex items-center gap-2 text-red-100"><?= icon('user', 'w-4 h-4') ?><?= e($uname) ?></span>
        <a href="logout.php" class="px-3 py-2 rounded-md hover:bg-red-700 flex items-center gap-2"><?= icon('log-out', 'w-4 h-4') ?>Log out</a>
      <?php else: ?>
        <a href="login.php" class="px-3 py-2 rounded-md hover:bg-red-700 flex items-center gap-2"><?= icon('log-in', 'w-4 h-4') ?>Artisan log in</a>
        <a href="register.php" class="ml-1 px-4 py-2 rounded-md bg-amber-400 text-red-900 font-semibold hover:bg-amber-300">Join as artisan</a>
      <?php endif; ?>
    </nav>
    <button id="menuBtn" class="md:hidden p-2 rounded-md hover:bg-red-700" aria-label="Open menu" aria-expanded="false"><?= icon('menu', 'w-6 h-6') ?></button>
  </div>
  <nav id="mobileMenu" class="hidden md:hidden border-t border-red-700 px-4 py-2 text-sm" aria-label="Mobile">
    <?php foreach ($links as [$href, $label, $ic]): ?>
      <a href="<?= e($href) ?>" class="flex items-center gap-2 py-3"><?= icon($ic, 'w-4 h-4') ?><?= e($label) ?><?= ($href === 'inbox.php' && $unread) ? ' (' . $unread . ' new)' : '' ?></a>
    <?php endforeach; ?>
    <?php if ($uid): ?>
      <a href="logout.php" class="flex items-center gap-2 py-3"><?= icon('log-out', 'w-4 h-4') ?>Log out (<?= e($uname) ?>)</a>
    <?php else: ?>
      <a href="login.php" class="flex items-center gap-2 py-3"><?= icon('log-in', 'w-4 h-4') ?>Artisan log in</a>
      <a href="register.php" class="flex items-center gap-2 py-3"><?= icon('user', 'w-4 h-4') ?>Join as artisan</a>
    <?php endif; ?>
  </nav>
</header>

<main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6 sm:py-8">
<?php if ($flash): ?>
  <div class="mb-6 rounded-md border px-4 py-3 text-sm flex items-start gap-2 <?= $flash['type'] === 'error' ? 'bg-red-50 border-red-300 text-red-800' : 'bg-emerald-50 border-emerald-300 text-emerald-800' ?>" role="alert">
    <?= icon($flash['type'] === 'error' ? 'x' : 'check', 'w-5 h-5 shrink-0') ?>
    <span><?= e($flash['msg']) ?></span>
  </div>
<?php endif; ?>
