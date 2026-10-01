<?php
require 'config.php';
require_login();

$errors = [];
$old = ['name' => '', 'category' => '', 'description' => '', 'price' => '', 'stock' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if ($old['name'] === '' || mb_strlen($old['name']) > 120) $errors[] = 'Enter a product name (max 120 characters).';
    if (!in_array($old['category'], CATEGORIES, true)) $errors[] = 'Choose a category.';
    if (mb_strlen($old['description']) > 1500) $errors[] = 'Description must be 1500 characters or fewer.';
    if (!is_numeric($old['price']) || (float)$old['price'] < 0 || (float)$old['price'] > 9999999) $errors[] = 'Enter a valid price.';
    if (!ctype_digit($old['stock']) || (int)$old['stock'] > 99999) $errors[] = 'Stock count must be a whole number from 0 to 99999.';

    [$filename, $imgErr] = check_image($_FILES['image'] ?? null);
    if ($imgErr) $errors[] = $imgErr;

    if (!$errors) {
        if ($filename) {
            $dir = __DIR__ . '/uploads';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $filename)) {
                $errors[] = 'Could not save the photo. Check that the uploads folder is writable.';
            }
        }
    }
    if (!$errors) {
        $st = $pdo->prepare('INSERT INTO products (user_id, name, category, description, price, stock, image) VALUES (?,?,?,?,?,?,?)');
        $st->execute([current_user_id(), $old['name'], $old['category'], $old['description'], round((float)$old['price'], 2), (int)$old['stock'], $filename]);
        flash('Product added to your inventory.');
        redirect('dashboard.php');
    }
}

$pageTitle = 'Add product';
include 'includes/header.php';
$inputCls = 'w-full rounded-md border border-zinc-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-700';
?>
<div class="max-w-2xl mx-auto bg-white border border-zinc-300 rounded-lg p-6 sm:p-8">
  <h1 class="text-2xl font-bold text-zinc-900">Add a product</h1>
  <p class="text-sm text-zinc-600 mt-1">New products appear on the storefront right away.</p>

  <?php if ($errors): ?>
    <ul class="mt-4 rounded-md bg-red-50 border border-red-300 text-red-800 text-sm p-3 list-disc list-inside">
      <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="mt-5 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label for="name" class="block text-sm font-medium mb-1">Product name</label>
      <input id="name" name="name" required maxlength="120" value="<?= e($old['name']) ?>" class="<?= $inputCls ?>">
    </div>
    <div>
      <label for="category" class="block text-sm font-medium mb-1">Category</label>
      <select id="category" name="category" required class="<?= $inputCls ?>">
        <option value="">Select category</option>
        <?php foreach (CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $c === $old['category'] ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="price" class="block text-sm font-medium mb-1">Price (PHP)</label>
        <input id="price" type="number" name="price" required min="0" step="0.01" inputmode="decimal" value="<?= e($old['price']) ?>" class="<?= $inputCls ?>">
      </div>
      <div>
        <label for="stock" class="block text-sm font-medium mb-1">Stock count</label>
        <input id="stock" type="number" name="stock" required min="0" max="99999" step="1" inputmode="numeric" value="<?= e($old['stock']) ?>" class="<?= $inputCls ?>">
      </div>
    </div>
    <div>
      <label for="description" class="block text-sm font-medium mb-1">Description <span class="text-zinc-500 font-normal">(optional)</span></label>
      <textarea id="description" name="description" rows="4" maxlength="1500" placeholder="Materials, size, technique and care" class="<?= $inputCls ?>"><?= e($old['description']) ?></textarea>
    </div>
    <div>
      <label for="image" class="block text-sm font-medium mb-1">Photo <span class="text-zinc-500 font-normal">(optional)</span></label>
      <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm text-zinc-600 border border-zinc-300 rounded-md file:mr-3 file:rounded-md file:border-0 file:bg-red-800 file:text-white file:px-4 file:py-2 file:font-semibold hover:file:bg-red-900">
      <p class="text-xs text-zinc-500 mt-1">JPG, PNG, WEBP or GIF, up to 3 MB.</p>
    </div>
    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
      <a href="dashboard.php" class="text-center border border-zinc-300 rounded-md px-5 py-2.5 font-semibold hover:bg-zinc-50">Cancel</a>
      <button class="bg-red-800 hover:bg-red-900 text-white font-semibold rounded-md px-5 py-2.5 flex items-center justify-center gap-2"><?= icon('plus', 'w-4 h-4') ?>Add product</button>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
