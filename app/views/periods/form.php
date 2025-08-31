<?php
// v1.0
use App\Helpers\View;
$mode = $mode ?? 'create';
$isEdit = ($mode === 'edit');
$action = $isEdit ? 'index.php?route=periods/update' : 'index.php?route=periods/store';
?>
<section class="space-y-4">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit období' : 'Přidat období' ?></h1>

  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-3" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    <?php endif; ?>

    <div class="grid gap-3 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Display (max 255)*</span>
        <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               type="text" name="display" required maxlength="255"
               value="<?= htmlspecialchars($item['display'] ?? '') ?>">
        <?php if (!empty($errors['display'])): ?>
          <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['display']) ?></div>
        <?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Name (max 64)*</span>
        <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               type="text" name="name" required maxlength="64"
               value="<?= htmlspecialchars($item['name'] ?? '') ?>">
        <?php if (!empty($errors['name'])): ?>
          <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['name']) ?></div>
        <?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Description (max 255)</span>
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="text" name="description" maxlength="255"
             value="<?= htmlspecialchars($item['description'] ?? '') ?>">
      <?php if (!empty($errors['description'])): ?>
        <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['description']) ?></div>
      <?php endif; ?>
    </label>

    <div class="grid gap-3 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Rok od</span>
        <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               type="text" name="yearFrom" inputmode="numeric" maxlength="5" pattern="^-?\d{1,4}$"
               value="<?= htmlspecialchars((string)($item['yearFrom'] ?? '')) ?>">
        <?php if (!empty($errors['yearFrom'])): ?>
          <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['yearFrom']) ?></div>
        <?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Rok do</span>
        <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               type="text" name="yearTo" inputmode="numeric" maxlength="5" pattern="^-?\d{1,4}$"
               value="<?= htmlspecialchars((string)($item['yearTo'] ?? '')) ?>">
        <?php if (!empty($errors['yearTo'])): ?>
          <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['yearTo']) ?></div>
        <?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Poznámka (TEXT)</span>
      <textarea class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm" rows="5"
                name="note"><?= htmlspecialchars($item['note'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="index.php?route=periods/list" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
