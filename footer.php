</main>
<footer class="bg-white border-t border-zinc-300 text-center text-sm text-zinc-500 py-5 px-4">
  LikasPinas &copy; <?= date('Y') ?> &middot; A showcase portal for local handcrafted goods. Orders are arranged directly with each artisan.
</footer>
<script>
  const btn = document.getElementById('menuBtn');
  const menu = document.getElementById('mobileMenu');
  btn.addEventListener('click', () => {
    const open = menu.classList.toggle('hidden') === false;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
</script>
</body>
</html>
