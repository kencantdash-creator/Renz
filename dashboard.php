<?php
require 'config.php';
require_login();
$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $pid = (int)($_POST['product_id'] ?? 0);

    if ($action === 'update_stock') {
        $raw = trim($_POST['stock'] ?? '');
        if (!ctype_digit($raw) || (int)$raw > 99999) {
            flash('Stock count must be a whole number from 0 to 99999.', 'error');
        } else {
            $st = $pdo->prepare('UPDATE products SET stock = ? WHERE id = ? AND user_id = ?');
            $st->execute([(int)$raw, $pid, $uid]);
            flash('Stock count updated.');
        }
    } elseif ($action === 'delete') {
        $st = $pdo->prepare('SELECT image FROM products WHERE id = ? AND user_id = ?');
        $st->execute([$pid, $uid]);
        $row = $st->fetch();
        if ($row) {
            $pdo->prepare('DELETE FROM products WHERE id = ? AND user_id = ?')->execute([$pid, $uid]);
            if ($row['image'] && is_file(__DIR__ . '/uploads/' . $row['image'])) @unlink(__DIR__ . '/uploads/' . $row['image']);
            flash('Product deleted.');
        }
    }
    redirect('dashboard.php');
}

$st = $pdo->prepare('SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$st->execute([$uid]);
$products = $st->fetchAll();

$totalStock = 0; $soldOut = 0;
foreach ($products as $p) { $totalStock += (int)$p['stock']; if ((int)$p['stock'] === 0) $soldOut++; }

$pageTitle = 'Inventory';
include 'includes/header.php';
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-zinc-900">My inventory</h1>
    <p class="text-sm text-zinc-600">Update stock counts and manage your listed products.</p>
  </div>
  <a href="product_add.php" class="bg-red-800 hover:bg-red-900 text-white font-semibold rounded-md px-4 py-2.5 text-sm flex items-center justify-center gap-2"><?= icon('plus', 'w-4 h-4') ?>Add product</a>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
  <?php foreach ([['Products', count($products), 'package'], ['Total stock', $totalStock, 'grid'], ['Sold out', $soldOut, 'x'], ['New inquiries', $unread, 'inbox']] as [$l, $v, $ic]): ?>
    <div class="bg-white border border-zinc-300 rounded-lg p-4 flex items-center gap-3">
      <span class="bg-red-100 text-red-800 rounded-md p-2 shrink-0"><?= icon($ic, 'w-5 h-5') ?></span>
      <div><p class="text-2xl font-bold leading-none"><?= (int)$v ?></p><p class="text-xs text-zinc-500 mt-1"><?= e($l) ?></p></div>
    </div>
  <?php endforeach; ?>
</div>

<?php if (!$products): ?>
  <div class="bg-white border border-dashed border-zinc-300 rounded-lg p-10 text-center">
    <div class="mx-auto w-12 h-12 rounded-full bg-zinc-100 flex items-center justify-center text-zinc-500"><?= icon('package', 'w-6 h-6') ?></div>
    <h2 class="font-semibold text-lg mt-3">Your inventory is empty</h2>
    <p class="text-zinc-600 text-sm mt-1">Add your first product so buyers can find it on the storefront.</p>
    <a href="product_add.php" class="inline-flex items-center gap-2 mt-4 bg-red-800 text-white rounded-md px-4 py-2 text-sm font-semibold hover:bg-red-900"><?= icon('plus', 'w-4 h-4') ?>Add product</a>
  </div>
<?php else: ?>
  <div class="bg-white border border-zinc-300 rounded-lg overflow-x-auto">
    <table class="w-full text-sm min-w-[44rem]">
      <thead class="bg-zinc-50 border-b border-zinc-300 text-left text-zinc-600">
        <tr>
          <th class="px-4 py-3 font-semibold">Product</th>
          <th class="px-4 py-3 font-semibold">Category</th>
          <th class="px-4 py-3 font-semibold">Price</th>
          <th class="px-4 py-3 font-semibold">Stock count</th>
          <th class="px-4 py-3 font-semibold text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-zinc-200">
        <?php foreach ($products as $p): [$label, $cls] = stock_badge($p['stock']); ?>
          <tr>
            <td class="px-4 py-3">
              <a href="product.php?id=<?= (int)$p['id'] ?>" class="flex items-center gap-3 hover:underline">
                <?php if ($p['image']): ?>
                  <img src="uploads/<?= e($p['image']) ?>" alt="" class="w-12 h-12 rounded-md object-cover shrink-0">
                <?php else: ?>
                  <span class="w-12 h-12 rounded-md bg-zinc-200 text-zinc-400 flex items-center justify-center shrink-0"><?= icon('image', 'w-5 h-5') ?></span>
                <?php endif; ?>
                <span class="font-medium"><?= e($p['name']) ?></span>
              </a>
            </td>
            <td class="px-4 py-3 text-zinc-600"><?= e($p['category']) ?></td>
            <td class="px-4 py-3 font-medium whitespace-nowrap"><?= money($p['price']) ?></td>
            <td class="px-4 py-3">
              <form method="post" class="flex items-center gap-2">
                <?= csrf_field() ?><input type="hidden" name="action" value="update_stock"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <input type="number" name="stock" min="0" max="99999" step="1" value="<?= (int)$p['stock'] ?>" required aria-label="Stock count for <?= e($p['name']) ?>" class="w-20 rounded-md border border-zinc-300 px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-red-700">
                <button class="border border-zinc-300 hover:bg-zinc-50 rounded-md p-2" title="Save stock" aria-label="Save stock"><?= icon('save', 'w-4 h-4') ?></button>
              </form>
              <span class="inline-block mt-1.5 text-xs font-semibold border rounded-full px-2 py-0.5 <?= $cls ?>"><?= e($label) ?></span>
            </td>
            <td class="px-4 py-3">
              <form method="post" class="flex justify-end" onsubmit="return confirm('Delete this product permanently?');">
                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                <button class="border border-red-300 text-red-700 hover:bg-red-50 rounded-md px-3 py-2 flex items-center gap-2"><?= icon('trash', 'w-4 h-4') ?>Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
