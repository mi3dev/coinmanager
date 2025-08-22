<?php
// v1.6
use App\Core\Auth;
use App\Helpers\View;
use App\Helpers\Url;

$user = Auth::user();
$role = $user['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle ?? 'Aplikace mincí') ?></title>

  <!-- Tailwind + Alpine -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-950 text-slate-100">
  <!-- Topbar -->
  <header x-data="{open:false, userMenu:false}" class="border-b border-slate-800 bg-slate-900/70 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-14 items-center justify-between">
        <!-- Brand -->
        <div class="flex items-center gap-3">
          <a href="<?= Url::build('dashboard/index') ?>" class="flex items-center gap-2 font-semibold tracking-wide">
            <span class="inline-block h-2 w-2 rounded-full bg-sky-400"></span>
            Coin Manager
          </a>
        </div>

        <!-- Desktop nav -->
        <nav class="hidden md:flex items-center gap-2">
          <a class="px-3 py-2 text-sm rounded-md hover:bg-slate-800" href="<?= Url::build('dashboard/index') ?>">Dashboard</a>

          <?php if (in_array($role, ['admin','collector'], true)): ?>
	    
	    <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= \App\Helpers\Url::build('catalogs/list') ?>">Katalogy</a>

          <?php endif; ?>

          <?php if (in_array($role, ['admin','collector'], true)): ?>
            <div x-data="{open:false}" class="relative">
              <button @click="open=!open" @click.outside="open=false"
                      class="px-3 py-2 text-sm rounded-md hover:bg-slate-800 flex items-center gap-1">
                Sbírka
                <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
              </button>
              <div x-show="open" x-transition
                   class="absolute left-0 mt-2 w-56 rounded-md border border-slate-800 bg-slate-900 shadow-lg">
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('collectiontrays/list') ?>">Plata</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('collection/list') ?>">Moje sbírka</a>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($role === 'guest'): ?>
            <a class="px-3 py-2 text-sm rounded-md hover:bg-slate-800" href="<?= Url::build('guest/collections') ?>">Veřejné sbírky</a>
          <?php endif; ?>

          <?php if ($role === 'admin'): ?>
            <!-- Číselníky -->
            <div x-data="{open:false}" class="relative">
              <button @click="open=!open" @click.outside="open=false"
                      class="px-3 py-2 text-sm rounded-md hover:bg-slate-800 flex items-center gap-1">
                Číselníky
                <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
              </button>
              <div x-show="open" x-transition
                   class="absolute left-0 mt-2 w-56 rounded-md border border-slate-800 bg-slate-900 shadow-lg">
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('periods/list') ?>">Období</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('coindenominations/list') ?>">Hodnoty</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('coinmetals/list') ?>">Kovy</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('coinedges/list') ?>">Hrany</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('coinmints/list') ?>">Mincovny</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('cointypes/list') ?>">Typy</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('coindesigners/list') ?>">Autoři</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('coinrarities/list') ?>">Vzácnosti</a>
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= \App\Helpers\Url::build('coincatalogitems/list') ?>">Nominály (položky)</a>
                <!-- sem postupně přibudou: Typy mincí, Kovy, Hrany, Mincovny, ... -->
              </div>
            </div>
            <!-- Správa (jen admin) -->
            <div x-data="{open:false}" class="relative">
              <button @click="open=!open" @click.outside="open=false"
                      class="px-3 py-2 text-sm rounded-md hover:bg-slate-800 flex items-center gap-1">
                Správa
                <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
              </button>
              <div x-show="open" x-transition
                   class="absolute left-0 mt-2 w-56 rounded-md border border-slate-800 bg-slate-900 shadow-lg">
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('users/list') ?>">Uživatelé</a>
              </div>
            </div>
          <?php endif; ?>
        </nav>

        <!-- User area -->
        <div class="flex items-center gap-2">
          <?php if ($user): ?>
            <div class="relative">
              <button @click="userMenu = !userMenu" class="flex items-center gap-2 rounded-md px-3 py-2 hover:bg-slate-800">
                <span class="hidden sm:inline text-sm"><?= htmlspecialchars($user['username'] ?: $user['email']) ?></span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-sky-500/20 text-sky-300"><?= htmlspecialchars($role) ?></span>
                <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor"><path d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"/></svg>
              </button>
              <div x-show="userMenu" @click.outside="userMenu=false" x-transition
                   class="absolute right-0 mt-2 w-48 rounded-md border border-slate-800 bg-slate-900 shadow-lg">
                <a class="block px-4 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('dashboard/index') ?>">Můj panel</a>
                <div class="my-1 border-t border-slate-800"></div>
                <a class="block px-4 py-2 text-sm text-red-300 hover:bg-red-950/30" href="<?= Url::build('auth/logout') ?>">Odhlásit</a>
              </div>
            </div>
          <?php else: ?>
            <a class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400"
               href="<?= Url::build('auth/login') ?>">Přihlášení</a>
          <?php endif; ?>

          <!-- Mobile burger -->
          <button class="md:hidden rounded-md p-2 hover:bg-slate-800" @click="open = !open" aria-label="Menu">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile nav -->
      <div class="md:hidden" x-show="open" x-transition>
        <nav class="border-t border-slate-800 py-2">
          <a class="block px-3 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('dashboard/index') ?>">Dashboard</a>

          <?php if (in_array($role, ['admin','collector'], true)): ?>
            <div class="px-3 py-1 text-xs uppercase text-slate-500">Číselníky</div>
            <a class="block px-3 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('periods/list') ?>">Období</a>
            <a class="block px-3 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('catalogs/list') ?>">Katalog</a>
          <?php endif; ?>

          <?php if ($role === 'collector'): ?>
            <a class="block px-3 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('collection/list') ?>">Moje sbírka</a>
          <?php endif; ?>

          <?php if ($role === 'guest'): ?>
            <a class="block px-3 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('guest/collections') ?>">Veřejné sbírky</a>
          <?php endif; ?>

          <?php if ($role === 'admin'): ?>
            <div class="px-3 py-1 text-xs uppercase text-slate-500">Správa</div>
            <a class="block px-3 py-2 text-sm hover:bg-slate-800" href="<?= Url::build('users/list') ?>">Uživatelé</a>
          <?php endif; ?>

          <?php if ($user): ?>
            <div class="border-t border-slate-800 my-2"></div>
            <a class="block px-3 py-2 text-sm text-red-300 hover:bg-red-950/30" href="<?= Url::build('auth/logout') ?>">Odhlásit</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
    <?= View::flashHtml() ?>
    <?= $content ?? '' ?>
  </main>

  <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-400">
    © <?= date('Y') ?> Coin Manager
  </footer>
</body>
</html>
