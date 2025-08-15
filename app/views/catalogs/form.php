<?php
// v1.1 – pole: name (128), year (int, required), currency (8), description (TEXT)
use App\Helpers\View;
$mode = $mode ?? 'create'; $isEdit = ($mode === 'edit');
$action = $isEdit ? 'index.php?route=catalogs/update' : 'index.php?route=catalogs/store';
?>
<section class="space-y-4 max-w-3xl">
  <h1 class="text-lg font-semibold"><?= $isEdit ? 'Upravit katalog' : 'Vytvořit katalog' ?></h1>

  <?= View::flashHtml() ?>

  <form method="post" action="<?= $action ?>" class="space-y-4" autocomplete="off">
    <?= View::csrfField() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
    <?php endif; ?>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Název (max 128)*</span>
      <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
             type="text" name="name" required
             value="<?= htmlspecialchars($item['name'] ?? '') ?>">
      <?php if (!empty($errors['name'])): ?>
        <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['name']) ?></div>
      <?php endif; ?>
    </label>

    <div class="grid gap-4 sm:grid-cols-2">
      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Rok vydání*</span>
        <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               type="text" name="year" inputmode="numeric" required
               value="<?= htmlspecialchars((string)($item['year'] ?? '')) ?>">
        <?php if (!empty($errors['year'])): ?>
          <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['year']) ?></div>
        <?php endif; ?>
      </label>

      <label class="block">
        <span class="mb-1 block text-sm text-slate-300">Měna (max 8)*</span>
        <input class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
               type="text" name="currency" required maxlength="8"
               value="<?= htmlspecialchars($item['currency'] ?? 'CZK') ?>">
        <?php if (!empty($errors['currency'])): ?>
          <div class="mt-1 text-xs text-red-300"><?= htmlspecialchars($errors['currency']) ?></div>
        <?php endif; ?>
      </label>
    </div>

    <label class="block">
      <span class="mb-1 block text-sm text-slate-300">Popis (TEXT)</span>
      <textarea class="w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm" rows="4"
                name="description"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
    </label>

    <div class="flex justify-end gap-2 pt-2">
      <a href="<?= \App\Helpers\Url::build('catalogs/list') ?>" class="rounded-md border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">Zpět</a>
      <button class="rounded-md bg-sky-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
        <?= $isEdit ? 'Uložit změny' : 'Vytvořit' ?>
      </button>
    </div>
  </form>
</section>
