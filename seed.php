<?php

require 'config.php';

$count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$done = false;

if ($count === 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw = password_hash('demo1234', PASSWORD_DEFAULT);
    $u = $pdo->prepare('INSERT INTO users (name, email, password, location, bio) VALUES (?,?,?,?,?)');
    $p = $pdo->prepare('INSERT INTO products (user_id, name, category, description, price, stock) VALUES (?,?,?,?,?,?)');

    $artisans = [
        ['Habi Weavers of Vigan', 'weaver@likaspinas.test', 'Vigan, Ilocos Sur', 'Family loom workshop weaving inabel cloth in traditional patterns.', [
            ['Inabel Woven Blanket', 'Weaving and Textiles', 'Hand-woven cotton blanket in a classic striped pattern. About 150 x 220 cm. Machine wash cold.', 1850, 6],
            ['Woven Table Runner', 'Home Decor', 'Cotton table runner woven on a wooden loom. About 35 x 140 cm.', 650, 12],
            ['Loom-Woven Coin Purse', 'Bags and Leather', 'Small zip pouch made from leftover inabel fabric.', 180, 0],
        ]],
        ['Pugad Rattan Works', 'rattan@likaspinas.test', 'Cebu City, Cebu', 'Rattan and bamboo furniture and baskets made by a community of weavers.', [
            ['Rattan Laundry Basket', 'Basketry and Rattan', 'Sturdy rattan basket with a lid. Roughly 45 cm tall.', 1200, 8],
            ['Bamboo Serving Tray Set', 'Home Decor', 'Set of three nesting bamboo trays, smooth-sanded and sealed.', 780, 3],
            ['Buri Palm Hand Bag', 'Bags and Leather', 'Lightweight woven bag with cotton lining and a leather handle.', 950, 5],
        ]],
        ['Palayok Clayworks', 'clay@likaspinas.test', 'Pampanga', 'Wheel-thrown and hand-painted pottery fired in a wood kiln.', [
            ['Terracotta Planter', 'Pottery and Ceramics', 'Medium terracotta planter with drainage hole. About 20 cm wide.', 420, 15],
            ['Clay Cooking Pot (Palayok)', 'Pottery and Ceramics', 'Traditional clay pot for slow-cooked stews. Season before first use.', 560, 2],
            ['Hand-Painted Mug', 'Pottery and Ceramics', 'Stoneware mug with a hand-painted rim. Food safe glaze.', 340, 20],
        ]],
    ];
    foreach ($artisans as [$name, $email, $loc, $bio, $items]) {
        $u->execute([$name, $email, $pw, $loc, $bio]);
        $uid = (int)$pdo->lastInsertId();
        foreach ($items as [$pn, $cat, $desc, $price, $stock]) $p->execute([$uid, $pn, $cat, $desc, $price, $stock]);
    }
    $done = true;
    $count = 3;
}

$pageTitle = 'Demo data';
include 'includes/header.php';
?>
<div class="max-w-lg mx-auto bg-white border border-zinc-300 rounded-lg p-6">
  <h1 class="text-xl font-bold">Demo data</h1>
  <?php if ($done): ?>
    <p class="mt-3 text-emerald-800 flex items-center gap-2"><?= icon('check', 'w-5 h-5') ?>Three demo artisans and nine products were added.</p>
    <p class="mt-3 text-sm text-zinc-700">Log in with <strong>weaver@likaspinas.test</strong>, <strong>rattan@likaspinas.test</strong> or <strong>clay@likaspinas.test</strong>. Password for all: <strong>demo1234</strong>.</p>
    <a href="index.php" class="inline-block mt-4 bg-red-800 text-white rounded-md px-4 py-2 text-sm font-semibold">Open storefront</a>
  <?php elseif ($count > 0): ?>
    <p class="mt-3 text-sm text-zinc-700">Demo data was skipped because the database already has accounts. Re-import <code>database.sql</code> to start fresh.</p>
    <a href="index.php" class="inline-block mt-4 text-red-800 underline text-sm">Back to storefront</a>
  <?php else: ?>
    <p class="mt-3 text-sm text-zinc-700">Add three sample artisans with nine products so the storefront is not empty during your demo.</p>
    <form method="post" class="mt-4"><?= csrf_field() ?>
      <button class="bg-red-800 hover:bg-red-900 text-white font-semibold rounded-md px-5 py-2.5">Add demo data</button>
    </form>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
