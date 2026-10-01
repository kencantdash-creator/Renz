<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);
$uid = current_user_id();

$st = $pdo->prepare('SELECT p.*, u.name AS artisan_name, u.location, u.bio FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
$st->execute([$id]);
$p = $st->fetch();

if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    include 'includes/header.php';
    echo '<div class="bg-white border border-zinc-300 rounded-lg p-10 text-center"><h1 class="text-xl font-bold">Product not found</h1><p class="text-sm text-zinc-600 mt-1">It may have been removed by the artisan.</p><a href="index.php" class="inline-block mt-4 text-red-800 underline">Back to storefront</a></div>';
    include 'includes/footer.php';
    exit;
}
$isOwner = $uid && (int)$p['user_id'] === (int)$uid;

$errors = [];
$old = ['sender_name' => '', 'sender_email' => '', 'sender_phone' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isOwner) {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if (trim($_POST['website'] ?? '') !== '') {   // honeypot: bots fill this hidden field
        flash('Your inquiry was sent to the artisan.');
        redirect('product.php?id=' . $id);
    }
    if ($old['sender_name'] === '' || mb_strlen($old['sender_name']) > 100) $errors[] = 'Enter your name (max 100 characters).';
    if (!filter_var($old['sender_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address so the artisan can reply.';
    if (mb_strlen($old['sender_phone']) > 40) $errors[] = 'Phone number must be 40 characters or fewer.';
    $len = mb_strlen($old['message']);
    if ($len < 10) $errors[] = 'Write at least 10 characters in your message.';
    if ($len > 1000) $errors[] = 'Message must be 1000 characters or fewer.';

    if (!$errors) {
        $ins = $pdo->prepare('INSERT INTO inquiries (artisan_id, product_id, product_name, sender_name, sender_email, sender_phone, message) VALUES (?,?,?,?,?,?,?)');
        $ins->execute([$p['user_id'], $p['id'], $p['name'], $old['sender_name'], $old['sender_email'], $old['sender_phone'] ?: null, $old['message']]);
        flash('Your inquiry was sent to ' . $p['artisan_name'] . '. They will reply to your email.');
        redirect('product.php?id=' . $id);
    }
}

[$label, $cls] = stock_badge($p['stock']);
$pageTitle = $p['name'];
include 'includes/header.php';
$inputCls = 'w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-700';
?>
<a href="index.php" class="inline-flex items-center gap-2 text-sm text-red-800 hover:underline mb-4"><?= icon('arrow-left', 'w-4 h-4') ?>Back to storefront</a>

<div class="grid gap-6 lg:grid-cols-2">
  <div class="bg-white border border-zinc-300 rounded-lg overflow-hidden self-start">
    <div class="bg-zinc-200 flex items-center justify-center text-zinc-400 aspect-square">
      <?php if ($p['image']): ?>
        <img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover">
      <?php else: ?>
        <?= icon('image', 'w-20 h-20') ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="space-y-6">
    <section class="bg-white border border-zinc-300 rounded-lg p-5 sm:p-6">
      <p class="text-sm text-zinc-500 flex items-center gap-1"><?= icon('tag', 'w-4 h-4') ?><?= e($p['category']) ?></p>
      <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 mt-1"><?= e($p['name']) ?></h1>
      <div class="flex flex-wrap items-center gap-3 mt-3">
        <span class="text-2xl font-bold text-red-800"><?= money($p['price']) ?></span>
        <span class="text-sm font-semibold border rounded-full px-3 py-1 <?= $cls ?>"><?= e($label) ?></span>
      </div>
      <h2 class="font-semibold mt-5">About this piece</h2>
      <p class="text-zinc-700 mt-1 whitespace-pre-line break-words"><?= trim((string)$p['description']) !== '' ? e($p['description']) : 'The artisan did not add a description.' ?></p>
    </section>

    <section class="bg-white border border-zinc-300 rounded-lg p-5">
      <h2 class="font-semibold text-lg">Made by</h2>
      <p class="font-medium mt-2 flex items-center gap-2"><?= icon('user', 'w-4 h-4 text-zinc-500') ?><?= e($p['artisan_name']) ?></p>
      <p class="text-sm text-zinc-600 mt-1 flex items-center gap-2"><?= icon('map-pin', 'w-4 h-4 text-zinc-500') ?><?= e($p['location']) ?></p>
      <?php if ($p['bio']): ?><p class="text-sm text-zinc-700 mt-3"><?= e($p['bio']) ?></p><?php endif; ?>
      <a href="index.php?artisan=<?= (int)$p['user_id'] ?>" class="inline-flex items-center gap-1 text-sm text-red-800 underline mt-3">See all products by this artisan<?= icon('chevron-right', 'w-4 h-4') ?></a>
    </section>

    <section class="bg-white border border-zinc-300 rounded-lg p-5" id="inquiry">
      <h2 class="font-semibold text-lg flex items-center gap-2"><?= icon('mail', 'w-5 h-5') ?>Send an inquiry</h2>
      <?php if ($isOwner): ?>
        <p class="text-sm text-zinc-600 mt-2">This is your product. Buyer inquiries appear in your inbox.</p>
        <div class="mt-4 flex flex-col sm:flex-row gap-2">
          <a href="dashboard.php" class="text-center bg-red-800 text-white rounded-md px-4 py-2 text-sm font-semibold hover:bg-red-900">Manage inventory</a>
          <a href="inbox.php" class="text-center border border-zinc-300 rounded-md px-4 py-2 text-sm font-semibold hover:bg-zinc-50">Open inbox</a>
        </div>
      <?php else: ?>
        <p class="text-sm text-zinc-600 mt-1">Ask about availability, customization or how to order. Your message goes straight to the artisan's inbox.</p>

        <?php if ($errors): ?>
          <ul class="mt-4 rounded-md bg-red-50 border border-red-300 text-red-800 text-sm p-3 list-disc list-inside">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <form method="post" class="mt-4 space-y-4">
          <?= csrf_field() ?>
          <div class="hidden" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label for="sender_name" class="block text-sm font-medium mb-1">Your name</label>
              <input id="sender_name" name="sender_name" required maxlength="100" value="<?= e($old['sender_name']) ?>" class="<?= $inputCls ?>">
            </div>
            <div>
              <label for="sender_email" class="block text-sm font-medium mb-1">Your email</label>
              <input id="sender_email" type="email" name="sender_email" required value="<?= e($old['sender_email']) ?>" class="<?= $inputCls ?>">
            </div>
          </div>
          <div>
            <label for="sender_phone" class="block text-sm font-medium mb-1">Phone <span class="text-zinc-500 font-normal">(optional)</span></label>
            <input id="sender_phone" name="sender_phone" maxlength="40" value="<?= e($old['sender_phone']) ?>" class="<?= $inputCls ?>">
          </div>
          <div>
            <label for="message" class="block text-sm font-medium mb-1">Message</label>
            <textarea id="message" name="message" rows="4" required minlength="10" maxlength="1000" placeholder="Hello, is this still available?" class="<?= $inputCls ?>"><?= e($old['message']) ?></textarea>
          </div>
          <button class="w-full bg-red-800 hover:bg-red-900 text-white font-semibold rounded-md py-2.5 flex items-center justify-center gap-2"><?= icon('send', 'w-4 h-4') ?>Send inquiry</button>
        </form>
        <p class="mt-4 text-xs text-zinc-500 flex items-start gap-2"><?= icon('info', 'w-4 h-4 shrink-0') ?>This portal has no shopping cart, online checkout or shipping calculator. Payment and delivery are arranged directly with the artisan.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
