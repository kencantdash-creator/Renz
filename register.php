<?php
require 'config.php';
if (current_user_id()) redirect('dashboard.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'location' => '', 'bio' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($old['name'] === '' || mb_strlen($old['name']) > 100) $errors[] = 'Enter your name or shop name (max 100 characters).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($old['location'] === '' || mb_strlen($old['location']) > 120) $errors[] = 'Enter your town or province.';
    if (mb_strlen($old['bio']) > 300) $errors[] = 'Short bio must be 300 characters or fewer.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (!$errors) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$old['email']]);
        if ($st->fetch()) {
            $errors[] = 'That email is already registered. Try logging in.';
        } else {
            $st = $pdo->prepare('INSERT INTO users (name, email, password, location, bio) VALUES (?,?,?,?,?)');
            $st->execute([$old['name'], $old['email'], password_hash($pass, PASSWORD_DEFAULT), $old['location'], $old['bio'] ?: null]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_name'] = $old['name'];
            flash('Welcome to LikasPinas. Add your first product to your inventory.');
            redirect('product_add.php');
        }
    }
}

$pageTitle = 'Join as artisan';
include 'includes/header.php';
$inputCls = 'w-full rounded-md border border-zinc-300 bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-700';
?>
<div class="max-w-md mx-auto bg-white border border-zinc-300 rounded-lg p-6 sm:p-8">
  <h1 class="text-2xl font-bold text-zinc-900">Create an artisan account</h1>
  <p class="text-sm text-zinc-600 mt-1">List your handcrafted goods and receive inquiries from buyers.</p>

  <?php if ($errors): ?>
    <ul class="mt-4 rounded-md bg-red-50 border border-red-300 text-red-800 text-sm p-3 list-disc list-inside">
      <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" class="mt-5 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label for="name" class="block text-sm font-medium mb-1">Artisan or shop name</label>
      <input id="name" name="name" required maxlength="100" value="<?= e($old['name']) ?>" class="<?= $inputCls ?>">
    </div>
    <div>
      <label for="email" class="block text-sm font-medium mb-1">Email</label>
      <input id="email" type="email" name="email" required value="<?= e($old['email']) ?>" class="<?= $inputCls ?>">
    </div>
    <div>
      <label for="location" class="block text-sm font-medium mb-1">Town or province</label>
      <input id="location" name="location" required maxlength="120" value="<?= e($old['location']) ?>" class="<?= $inputCls ?>">
    </div>
    <div>
      <label for="bio" class="block text-sm font-medium mb-1">Short bio <span class="text-zinc-500 font-normal">(optional)</span></label>
      <textarea id="bio" name="bio" rows="3" maxlength="300" placeholder="What do you make and how?" class="<?= $inputCls ?>"><?= e($old['bio']) ?></textarea>
    </div>
    <div>
      <label for="password" class="block text-sm font-medium mb-1">Password</label>
      <input id="password" type="password" name="password" required minlength="6" class="<?= $inputCls ?>">
    </div>
    <button class="w-full bg-red-800 hover:bg-red-900 text-white font-semibold rounded-md py-2.5 flex items-center justify-center gap-2"><?= icon('user', 'w-4 h-4') ?>Create account</button>
  </form>
  <p class="text-sm text-zinc-600 mt-5 text-center">Already have an account? <a href="login.php" class="text-red-800 font-semibold underline">Log in</a></p>
</div>
<?php include 'includes/footer.php'; ?>
