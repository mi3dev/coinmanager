<?php
// v1.4
use App\Helpers\View;
?>
<section class="min-h-[70vh] flex items-center justify-center">
  <div class="w-full max-w-md">
    <div class="mb-6 text-center">
      <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-sky-500/20">
        <span class="h-2 w-2 rounded-full bg-sky-400 inline-block"></span>
      </div>
      <h1 class="text-xl font-semibold">Přihlášení do Coin Manager</h1>
      <p class="mt-1 text-sm text-slate-400">Zadejte své přihlašovací údaje</p>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-lg">
      <?= View::flashHtml() ?>

      <form method="post" action="index.php?route=auth/doLogin" class="space-y-4" autocomplete="off">
        <?= View::csrfField() ?>

        <label class="block">
          <span class="mb-1 block text-sm text-slate-300">Uživatelské jméno</span>
          <input type="text" name="username" required
                 class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-sky-500"
                 placeholder="např. jan.novak">
        </label>

        <label class="block">
          <span class="mb-1 block text-sm text-slate-300">Heslo</span>
          <input type="password" name="password" required
                 class="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-sm outline-none focus:border-sky-500"
                 placeholder="••••••••">
        </label>

        <div class="flex items-center justify-between text-sm">
          <div class="text-slate-400">
            
          </div>
        </div>

        <button type="submit"
                class="mt-2 w-full rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400 focus:ring-2 focus:ring-sky-600 focus:ring-offset-0">
          Přihlásit se
        </button>
      </form>
    </div>

  </div>
</section>
