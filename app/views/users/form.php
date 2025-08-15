<?php // v1.1
use App\Core\Session;
use App\Helpers\View;
$mode = $mode ?? 'create';
$isEdit = ($mode === 'edit');
$action = $isEdit ? 'index.php?route=users/update' : 'index.php?route=users/store';
?>
<section class="space-y-4 max-w-xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit uživatele' : 'Přidat uživatele' ?></h1>

  <form method="post" action="<?= $action ?>" class="space-y-3" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
    <?php endif; ?>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Username</span>
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="text" name="username" required value="<?= htmlspecialchars($user['username'] ?? '') ?>">
    </label>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">E‑mail</span>
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="email" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
    </label>

    <div class="grid gap-3 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Role</span>
        <select name="role" class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
          <?php
            $roles = ['admin'=>'admin','collector'=>'collector','guest'=>'guest'];
            $sel = $user['role'] ?? 'collector';
            foreach ($roles as $val=>$label) {
              $s = ($sel === $val) ? 'selected' : '';
              echo "<option value=\"$val\" $s>$label</option>";
            }
          ?>
        </select>
      </label>

      <label class="block flex items-center gap-2 mt-7">
        <input type="checkbox" name="active" value="1" <?= !empty($user['active']) ? 'checked' : '' ?>>
        <span class="text-sm">Aktivní účet</span>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300"><?= $isEdit ? 'Nové heslo (nepovinné)' : 'Heslo' ?></span>
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="password" name="password" <?= $isEdit ? '' : 'required' ?> >
      <?php if ($isEdit): ?>
        <span class="block text-xs text-slate-400 mt-1">Nech prázdné, pokud nechceš heslo měnit.</span>
      <?php endif; ?>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="index.php?route=users/list" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
