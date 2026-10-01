<?php
require 'config.php';

$q = trim($_GET['q'] ?? '');
$cat = $_GET['category'] ?? '';
$onlyStock = isset($_GET['instock']);
$artisan = (int)($_GET['artisan'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 12;
if (!in_array($cat, CATEGORIES, true)) $cat = '';

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE :q1 OR p.description LIKE :q2 OR u.name LIKE :q3)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
}
if ($cat !== '') { $where[] = 'p.category = :cat'; $params[':cat'] = $cat; }
if ($onlyStock) $where[] = 'p.stock > 0';
if ($artisan > 0) { $where[] = 'p.user_id = :art'; $params[':art'] = $artisan; }
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare('SELECT COUNT(*) FROM products p JOIN users u ON u.id = p.user_id' . $w);
$st->execute($params);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $per;

$st = $pdo->prepare('SELECT p.*, u.name AS artisan_name, u.location FROM products p JOIN users u ON u.id = p.user_id' . $w
    . ' ORDER BY p.created_at DESC, p.id DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset);
$st->execute($params);
$products = $st->fetchAll();

$artisanName = '';
if ($artisan > 0) {
    $s = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $s->execute([$artisan]);
    $artisanName = (string)$s->fetchColumn();
}

$qs = function ($p) use ($q, $cat, $onlyStock, $artisan) {
    return 'index.php?' . http_build_query(array_filter(['q' => $q, 'category' => $cat, 'instock' => $onlyStock ? '1' : '', 'artisan' => $artisan ?: '', 'page' => $p > 1 ? $p : ''], fn($v) => $v !== ''));
};
$filtered = $q !== '' || $cat !== '' || $onlyStock || $artisan > 0;

$pageTitle = 'Storefront';
include 'includes/header.php';
$inputCls = 'w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-700';
?>
<section class="bg-white border border-zinc-300 rounded-lg p-5 sm:p-7 mb-6">
  <h1 class="text-2xl sm:text-4xl font-bold text-zinc-900 leading-tight">Handcrafted goods from Filipino artisans</h1>
  <p class="text-zinc-600 mt-2 max-w-2xl">Browse woven, carved, fired and stitched pieces made locally. Send an inquiry to the artisan directly from any product page.</p>

  <form method="get" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-12 items-center" role="search">
    <?php if ($artisan > 0): ?><input type="hidden" name="artisan" value="<?= $artisan ?>"><?php endif; ?>
    <div class="relative sm:col-span-2 lg:col-span-5">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"><?= icon('search', 'w-4 h-4') ?></span>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search product, description or artisan" aria-label="Search" class="<?= $inputCls ?> pl-9">
    </div>
    <select name="category" aria-label="Category" class="<?= $inputCls ?> lg:col-span-3">
      <option value="">All categories</option>
      <?php foreach (CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $c === $cat ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <label class="flex items-center gap-2 text-sm lg:col-span-2"><input type="checkbox" name="instock" value="1" <?= $onlyStock ? 'checked' : '' ?> class="w-4 h-4 accent-red-800">In stock only</label>
    <button class="bg-red-800 hover:bg-red-900 text-white font-semibold rounded-md px-4 py-2 text-sm flex items-center justify-center gap-2 lg:col-span-2"><?= icon('search', 'w-4 h-4') ?>Search</button>
  </form>
</section>

<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
  <p class="text-sm text-zinc-600">
    <?= $total ?> product<?= $total === 1 ? '' : 's' ?>
    <?php if ($artisanName !== ''): ?> by <strong><?= e($artisanName) ?></strong><?php endif; ?>
  </p>
  <?php if ($filtered): ?><a href="index.php" class="text-sm text-red-800 underline flex items-center gap-1"><?= icon('x', 'w-4 h-4') ?>Clear filters</a><?php endif; ?>
</div>

<?php if (!$products): ?>
  <div class="bg-white border border-dashed border-zinc-300 rounded-lg p-10 text-center">
    <div class="mx-auto w-12 h-12 rounded-full bg-zinc-100 flex items-center justify-center text-zinc-500"><?= icon('package', 'w-6 h-6') ?></div>
    <h2 class="font-semibold text-lg mt-3"><?= $filtered ? 'No products match your search' : 'No products yet' ?></h2>
    <p class="text-zinc-600 text-sm mt-1"><?= $filtered ? 'Try different keywords or clear the filters.' : 'Artisans can join and list their first product.' ?></p>
    <?php if (!$filtered && !current_user_id()): ?><a href="register.php" class="inline-flex items-center gap-2 mt-4 bg-red-800 text-white rounded-md px-4 py-2 text-sm font-semibold hover:bg-red-900"><?= icon('user', 'w-4 h-4') ?>Join as artisan</a><?php endif; ?>
  </div>
<?php else: ?>
  <div class="grid gap-5 grid-cols-1 min-[480px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    <?php foreach ($products as $p): [$label, $cls] = stock_badge($p['stock']); ?>
      <a href="product.php?id=<?= (int)$p['id'] ?>" class="bg-white border border-zinc-300 rounded-lg overflow-hidden flex flex-col hover:border-red-800 focus:outline-none focus:ring-2 focus:ring-red-700">
        <div class="aspect-square bg-zinc-200 flex items-center justify-center text-zinc-400 relative">
          <?php if ($p['image']): ?>
            <img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-full h-full object-cover" loading="lazy">
          <?php else: ?>
            <?= icon('image', 'w-12 h-12') ?>
          <?php endif; ?>
          <span class="absolute top-2 left-2 text-xs font-semibold border rounded-full px-2.5 py-0.5 <?= $cls ?>"><?= e($label) ?></span>
        </div>
        <div class="p-4 flex-1 flex flex-col">
          <p class="text-xs text-zinc-500 flex items-center gap-1"><?= icon('tag', 'w-3.5 h-3.5') ?><?= e($p['category']) ?></p>
          <h2 class="font-semibold text-zinc-900 leading-snug mt-1"><?= e($p['name']) ?></h2>
          <p class="text-lg font-bold text-red-800 mt-1"><?= money($p['price']) ?></p>
          <p class="mt-auto pt-3 text-xs text-zinc-500 flex items-center gap-1 border-t border-zinc-100">
            <?= icon('map-pin', 'w-3.5 h-3.5 shrink-0') ?><span class="truncate"><?= e($p['artisan_name']) ?>, <?= e($p['location']) ?></span>
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pages > 1): ?>
    <nav class="mt-8 flex items-center justify-center gap-3 text-sm" aria-label="Pagination">
      <?php if ($page > 1): ?><a href="<?= e($qs($page - 1)) ?>" class="border border-zinc-300 bg-white rounded-md px-3 py-2 flex items-center gap-1 hover:bg-zinc-50"><?= icon('chevron-left', 'w-4 h-4') ?>Previous</a><?php endif; ?>
      <span class="text-zinc-600">Page <?= $page ?> of <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= e($qs($page + 1)) ?>" class="border border-zinc-300 bg-white rounded-md px-3 py-2 flex items-center gap-1 hover:bg-zinc-50">Next<?= icon('chevron-right', 'w-4 h-4') ?></a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
