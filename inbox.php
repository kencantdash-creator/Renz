<?php
require 'config.php';
require_login();
$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $mid = (int)($_POST['id'] ?? 0);
    if ($action === 'read') {
        $pdo->prepare('UPDATE inquiries SET is_read = 1 WHERE id = ? AND artisan_id = ?')->execute([$mid, $uid]);
    } elseif ($action === 'read_all') {
        $pdo->prepare('UPDATE inquiries SET is_read = 1 WHERE artisan_id = ?')->execute([$uid]);
        flash('All inquiries marked as read.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM inquiries WHERE id = ? AND artisan_id = ?')->execute([$mid, $uid]);
        flash('Inquiry deleted.');
    }
    redirect('inbox.php');
}

$st = $pdo->prepare('SELECT * FROM inquiries WHERE artisan_id = ? ORDER BY is_read ASC, created_at DESC, id DESC');
$st->execute([$uid]);
$messages = $st->fetchAll();
$unreadNow = 0;
foreach ($messages as $m) if (!$m['is_read']) $unreadNow++;

$pageTitle = 'Inbox';
include 'includes/header.php';
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-zinc-900">Inquiry inbox</h1>
    <p class="text-sm text-zinc-600"><?= count($messages) ?> message<?= count($messages) === 1 ? '' : 's' ?>, <?= $unreadNow ?> unread</p>
  </div>
  <?php if ($unreadNow): ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="read_all">
      <button class="border border-zinc-300 bg-white hover:bg-zinc-50 rounded-md px-4 py-2 text-sm font-semibold flex items-center gap-2"><?= icon('check', 'w-4 h-4') ?>Mark all as read</button>
    </form>
  <?php endif; ?>
</div>

<?php if (!$messages): ?>
  <div class="bg-white border border-dashed border-zinc-300 rounded-lg p-10 text-center">
    <div class="mx-auto w-12 h-12 rounded-full bg-zinc-100 flex items-center justify-center text-zinc-500"><?= icon('inbox', 'w-6 h-6') ?></div>
    <h2 class="font-semibold text-lg mt-3">No inquiries yet</h2>
    <p class="text-zinc-600 text-sm mt-1">When a buyer sends a message from one of your product pages, it will show up here.</p>
  </div>
<?php else: ?>
  <div class="space-y-3 max-w-3xl">
    <?php foreach ($messages as $m): ?>
      <article class="bg-white border rounded-lg p-4 <?= $m['is_read'] ? 'border-zinc-300' : 'border-red-800 border-l-4' ?>">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="font-semibold flex items-center gap-2 flex-wrap"><?= icon('user', 'w-4 h-4 text-zinc-500') ?><?= e($m['sender_name']) ?>
              <?php if (!$m['is_read']): ?><span class="text-xs font-semibold bg-red-800 text-white rounded-full px-2 py-0.5">New</span><?php endif; ?></p>
            <p class="text-sm text-zinc-600 mt-0.5 flex items-center gap-1"><?= icon('package', 'w-4 h-4') ?>About:
              <?php if ($m['product_id']): ?><a href="product.php?id=<?= (int)$m['product_id'] ?>" class="underline text-red-800"><?= e($m['product_name']) ?></a><?php else: ?><?= e($m['product_name']) ?> (removed)<?php endif; ?></p>
          </div>
          <p class="text-xs text-zinc-500"><?= e(date('M j, Y g:i A', strtotime($m['created_at']))) ?></p>
        </div>
        <p class="mt-3 text-zinc-700 whitespace-pre-line break-words"><?= e($m['message']) ?></p>
        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-sm text-zinc-600">
          <span class="flex items-center gap-1"><?= icon('mail', 'w-4 h-4') ?><?= e($m['sender_email']) ?></span>
          <?php if ($m['sender_phone']): ?><span class="flex items-center gap-1"><?= icon('phone', 'w-4 h-4') ?><?= e($m['sender_phone']) ?></span><?php endif; ?>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
          <a href="mailto:<?= e($m['sender_email']) ?>?subject=<?= e(rawurlencode('Re: ' . $m['product_name'])) ?>" class="bg-red-800 hover:bg-red-900 text-white text-sm font-semibold rounded-md px-4 py-2 flex items-center gap-2"><?= icon('send', 'w-4 h-4') ?>Reply by email</a>
          <?php if (!$m['is_read']): ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button class="border border-zinc-300 hover:bg-zinc-50 text-sm font-semibold rounded-md px-4 py-2 flex items-center gap-2"><?= icon('check', 'w-4 h-4') ?>Mark as read</button></form>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('Delete this inquiry?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="border border-red-300 text-red-700 hover:bg-red-50 text-sm font-semibold rounded-md px-4 py-2 flex items-center gap-2"><?= icon('trash', 'w-4 h-4') ?>Delete</button></form>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
